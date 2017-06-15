<?php

namespace Hmg\Models;

class Provider
{
    private $_table = 'providers';
    public $provider = array();
    public $message = null;

    public function __construct()
    {
    }

    public function setProvider($provider)
    {
        // always add new data
        $this->provider = array();
        if (is_array($provider)) {
            foreach ($provider as $key => $value) {
                $this->provider[$key] = $value;
            }
        }
    }

    public function setById($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT p.*, `s`.`name` role FROM `' . mysql_real_escape_string($this->_table) . '` p
                    LEFT JOIN settings s ON p.role_id = s.id
                    WHERE p.`id` = "' . mysql_real_escape_string($id) . '"';
            $rs = mysql_query($sql);
            if (mysql_num_rows($rs)) {
                $this->setProvider(mysql_fetch_array($rs, MYSQL_ASSOC));
            }
        }
    }

    public function getAll()
    {
        return $this->provider;
    }

    public function save()
    {
        $setFields = '';
        foreach ($this->provider as $field => $value) {
            //if ($value) {
                $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
            //}
        }
        if ($this->provider["id"]) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . ', modified = NOW()
                    WHERE `id` = "' . mysql_real_escape_string($this->provider['id']) . '"';
            $rs = mysql_query($sql) or die($sql);
        } else {
            $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . ', modified = NOW()';
            $rs = mysql_query($sql) or die($sql);
            $id = mysql_insert_id();
            if ($id) {
                $this->provider['id'] = $id;
            }
        }
        if (mysql_affected_rows()) {
            $this->setById($this->provider['id']);
            return true;
        } else {
            $this->setById($this->provider['id']);
            return false;
        }
    }

    public function deleteFamilyProvider()
    {
        if ($this->provider["id"]) {
            $sql = 'DELETE FROM `family_provider`
                    WHERE `provider_id` = "' . mysql_real_escape_string($this->provider['id']) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function delete()
    {
        if ($this->provider["id"]) {
            $sql = 'DELETE FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($this->provider['id']) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function displayEnumSelect($name, $field, $selected, $label = '')
    {
        $sql = 'SHOW COLUMNS FROM `' . $this->_table . '` LIKE  "' . mysql_real_escape_string($field) . '"';
        $rs = mysql_query($sql);
        if ($label) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '';
        }
        if ($rs) {
            $row = mysql_fetch_array($rs, MYSQL_ASSOC);
            $values = explode(',', str_replace('enum(', '', rtrim($row['Type'], ')')));
            sort($values);
            if (is_array($values)) {
                foreach ($values as $key => $value) {
                    $trimmed = trim($value, "'");
                    if ($trimmed) {
                        $options .= '<option value="' . $trimmed . '"' . ($selected == $trimmed ? ' selected="selected"' : '') . '>' . $trimmed . '</option>';
                    }
                }
            }
            $select = '<select id="' . $field . '" class="' . $field . '" name="' . $name . '">' . $options . '</select>';
            return $select;
        }
    }

    public function displaySelect($field, $name, $selected, $label = '', $tabIndex = '')
    {
        $sql = 'SELECT `' . mysql_real_escape_string($field) . '` FROM `' . $this->_table . '`'
        . ' WHERE `' . mysql_real_escape_string($field) . '` != "" '
        . ' GROUP BY `' . mysql_real_escape_string($field) . '`'
        . ' ORDER BY `' . mysql_real_escape_string($field) . '`';
        $rs = mysql_query($sql);
        if ($label) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '';
        }
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $options .= '<option value="' . $row[$field] . '"' . ($selected == $row[$field] ? ' selected="selected"' : '') . '>' . $row[$field] . '</option>';
        }
        $select = '<select id="' . $name . '" name="' . $name . '" tabindex="' . $tabIndex . '">' . $options . '</select>';
        return $select;
    }
}
