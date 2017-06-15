<?php

namespace Hmg\Models;

class User
{
    private $_table = 'users';
    private $_userField = 'email';
    private $_passField = 'password';
    public $username = null;
    public $password = null;
    public $loggedIn = false;
    public $user = array();
    public $message = null;

    public function __construct($username = null, $password = null, $table = null, $userField = null, $passField = null)
    {
        $this->username = $username;
        $this->password = $password;
        if ($table) {
            $this->_table = $table;
        }
        if ($userField) {
            $this->_userField = $userField;
        }
        if ($passField) {
            $this->_passField = $passField;
        }
    }

    public function login()
    {
        $sql = '
            SELECT
                *
            FROM `' . mysql_real_escape_string($this->_table) . '`
            WHERE `' . mysql_real_escape_string($this->_userField) . '` = "' . mysql_real_escape_string($this->username) . '"
                AND `' . mysql_real_escape_string($this->_passField) . '` = MD5("' . mysql_real_escape_string($this->password) . '")
                AND status = "1"';
        $rs = mysql_query($sql);
        if ($rs) {
            $user = mysql_fetch_array($rs, MYSQL_ASSOC);
            if ($user) {
                $this->user = $user;
                unset($this->user['password']);
                $this->loggedIn = true;
            }
        }
    }

    public function setUser($user)
    {
        if (is_array($user)) {
            foreach ($user as $key => $value) {
                $this->user[$key] = $value;
            }
        }
    }

    public function setById($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT * FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($id) . '"';
            $rs = mysql_query($sql);
            if (mysql_num_rows($rs)) {
                $this->setUser(mysql_fetch_array($rs, MYSQL_ASSOC));
            }
        }
    }

        public function getById($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT hmg_worker FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($id) . '"';
            $rs = mysql_query($sql);
            if (mysql_num_rows($rs)) {
                $rec=mysql_fetch_array($rs, MYSQL_ASSOC);
                return $rec['hmg_worker'];
            }
            else
                return false;
        }
    }
        
        public function getByName($name)
    {
        if (!is_numeric($name)) {
            $sql = 'SELECT id FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `hmg_worker` = "' . mysql_real_escape_string($name) . '"';
            $rs = mysql_query($sql);
            if (mysql_num_rows($rs)) {
                $rec=mysql_fetch_array($rs, MYSQL_ASSOC);
                return $rec['id'];
            }
            else
                return false;
        }
    }


    /* public function setByHmgWorker($hmgWorker) */
    /* { */
    /*     $sql = 'SELECT * FROM `' . mysql_real_escape_string($this->_table) . '` */
    /*             WHERE `hmg_worker` = "' . mysql_real_escape_string($hmgWorker) . '"'; */
    /*     $rs = mysql_query($sql); */
    /*     if (mysql_num_rows($rs)) { */
    /*         $this->setUser(mysql_fetch_array($rs, MYSQL_ASSOC)); */
    /*     } */
    /* } */

public function setByHmgWorker($hmgWorker)
   {
       if(is_numeric($hmgWorker)) {
           $sql = 'SELECT * FROM `' . mysql_real_escape_string($this->_table) . '` WHERE `id` = "' . mysql_real_escape_string($hmgWorker) . '"';
       } else {
           $sql = 'SELECT * FROM `' . mysql_real_escape_string($this->_table) . '` WHERE `hmg_worker` = "' . mysql_real_escape_string($hmgWorker) . '"';
       }
       $rs = mysql_query($sql);
       if (mysql_num_rows($rs)) {
           $this->setUser(mysql_fetch_array($rs, MYSQL_ASSOC));
       }
   }


        

    public function validateEmail()
    {
        $sql = 'SELECT * FROM `' . mysql_real_escape_string($this->_table) . '`
                WHERE `email` = "' . mysql_real_escape_string($this->user["email"]) . '"' . ($this->user["id"] ? ' AND `id` != "' . mysql_real_escape_string($this->user["id"]) . '"' : '') ;
        $rs = mysql_query($sql);
        if (mysql_num_rows($rs)) {
            return false;
        } else {
            return true;
        }
    }

    public function validatePassword($password)
    {
        $valid = true;
        $errors = array();

        $length     = strlen($password);
        $hasCapital = preg_match('/[A-Z]+/', $password);
        $hasLower   = preg_match('/[a-z]+/', $password);
        $hasNumeric = preg_match('/[0-9]+/', $password);
        $hasInValidCharacters = preg_match('/[[:space:]]+/', $password);

        if ($length < 7 || $length > 20) {
                $errors[] = "Length must be at least 7 characters, but not more than 20";
        }

        if (!$hasCapital) {
                $errors[] = "Password must have at least one capital letter";
        }

        if (!$hasLower) {
                $errors[] = "Password must have at least one lower case letter";
        }

        if (!$hasNumeric) {
                $errors[] = "Password must contain at least one number";
        }

        if ($hasInValidCharacters) {
                $errors[] = "Password must not contain invalid characters";
        }

        if (count($errors)) {
                return array('valid' => false, 'errors' => $errors);
        } else {
                return array('valid' => true);
        }
    }

    public function getAll()
    {
        return $this->user;
    }

    public function save()
    {
        $setFields = '';
        foreach ($this->user as $field => $value) {
            if ($field == $this->_passField) {
                if ($value) {
                    $value = md5($value);
                } else {
                    // Don't update the password field if it is empty
                    continue;
                }
            }
            $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
        }
        if ($this->user["id"]) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . '
                    WHERE `id` = "' . mysql_real_escape_string($this->user['id']) . '"';
            $rs = mysql_query($sql);
        } else {
            $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields;
            $rs = mysql_query($sql);
            $id = mysql_insert_id();
            if ($id) {
                $this->user['id'] = $id;
            }
        }
        if (mysql_affected_rows()) {
            return true;
        } else {
            return false;
        }
    }

    public function delete()
    {
        if ($this->user["id"]) {
            $sql = 'DELETE FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($this->user['id']) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                return true;
            } else {
                return false;
            }
        }
    }
}
