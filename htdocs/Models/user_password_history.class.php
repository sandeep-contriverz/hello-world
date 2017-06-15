<?php

namespace Hmg\Models;

class UserPasswordHistory
{
    private $_table = 'user_password_history';
    public $data = array();
    public $message = null;

    public function __construct(array $data = null)
    {
        if (is_array($data)) {
            $this->setdata($data);
        }
    }

    public function setData($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $this->data[$key] = $value;
            }
        }
    }

    public function getAllPasswordsByUserId($userId)
    {
        if (!is_numeric($userId)) {
            return null;
        }
        $data = array();
        if (is_numeric($userId)) {
            $sql = 'SELECT * FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `user_id` = "' . mysql_real_escape_string($userId) . '"';
            $rs = mysql_query($sql);
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $data[$row['id']] = $row;
            }
        }

        return $data;
    }

    public function getPasswordsByUserIdAndPassword($userId, $password)
    {
        if (!is_numeric($userId)) {
            return null;
        }
        $data = array();
        if (is_numeric($userId)) {
            $sql = 'SELECT * FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `user_id` = "' . mysql_real_escape_string($userId) . '" AND password = "' . mysql_real_escape_string(MD5($password)) . '"';
            $rs = mysql_query($sql);
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $data[$row['id']] = $row;
            }
        }

        return $data;
    }

    public function getLastPasswordByUserId($userId)
    {
        if (!is_numeric($userId)) {
            return null;
        }
        $row = array();
        if (is_numeric($userId)) {
            $sql = 'SELECT * FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `user_id` = "' . mysql_real_escape_string($userId) . '"
                    ORDER BY id DESC LIMIT 1';
            $rs = mysql_query($sql);
            $row = mysql_fetch_array($rs, MYSQL_ASSOC);
        }

        return $row;
    }

    public function save()
    {
        $setFields = '';
        foreach ($this->data as $field => $value) {
            $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
        }
        if (isset($this->data) && isset($this->data["id"])) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . '
                    WHERE `id` = "' . mysql_real_escape_string($this->data['id']) . '"';
            $rs = mysql_query($sql);
        } else {
            $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields;
            $rs = mysql_query($sql);
            $id = mysql_insert_id();
            if ($id) {
                $this->data['id'] = $id;
            }
        }
        if (mysql_affected_rows()) {
            return true;
        } else {
            return false;
        }
    }
}
