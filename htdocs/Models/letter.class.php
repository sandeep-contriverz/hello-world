<?php

namespace Hmg\Models;

class Letter
{
    private $_table = 'letter';
    public $letter = array();
    public $message = null;

    public function __construct()
    {
    }

    public function setLetter($letter)
    {
        // always add new data
        $this->letter= array();
        if (is_array($letter)) {
            foreach ($letter as $key => $value) {
                $this->letter[$key] = $value;
            }
        }
    }

    public function setById($id)
    {
        if ($id) {
            $sql = 'SELECT * FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($id) . '"';
            $rs = mysql_query($sql);
            if (mysql_num_rows($rs)) {
                $this->setLetter(mysql_fetch_array($rs, MYSQL_ASSOC));
            }
        }
    }

    public function getAll()
    {
        return $this->letter;
    }

    public function save()
    {
        foreach ($this->letter as $field => $value) {
            //if ($value) {
                $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
            //}
        }
        if ($this->letter["id"]) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . ', modified = NOW()
                    WHERE `id` = "' . mysql_real_escape_string($this->letter['id']) . '"';
            $rs = mysql_query($sql) or die($sql);
        } else {
            $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . ', modified = NOW()';
            $rs = mysql_query($sql) or die($sql);
            $id = mysql_insert_id();
            if ($id) {
                $this->letter['id'] = $id;
            }
        }
        if (mysql_affected_rows()) {
            $this->setById($this->letter['id']);
            return true;
        } else {
            $this->setById($this->letter['id']);
            return false;
        }
    }

    public function delete()
    {
        if ($this->letter["id"]) {
            $sql = 'DELETE FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($this->letter['id']) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function displayEnumSelect($name, $field, $selected)
    {
        $sql = 'SHOW COLUMNS FROM `' . $this->_table . '` LIKE  "' . mysql_real_escape_string($field) . '"';
        $rs = mysql_query($sql);
        if ($rs) 
		{
            $row = mysql_fetch_array($rs, MYSQL_ASSOC);
            $values = explode(',', str_replace('enum(', '', rtrim($row['Type'], ')')));
            sort($values);
            if (is_array($values)) {
                $options = '';
                foreach ($values as $key => $value) {
                    $trimmed = trim($value, "'");
                    $options .= '<option value="' . $trimmed . '"' . ($selected == $trimmed ? ' selected="selected"' : '') . '>' . $trimmed . '</option>';
                }
            }
            $select = '<select id="' . $field . '" class="' . $field . '" name="' . $name . '">' . $options . '</select>';
            
			return $select;
        }
    }
}
