<?php

namespace Hmg\Models;

class SettingEnum
{
    private $_table = null;
    private $_field = null;
    private $_valid = false;
    public $settings = array();
    public $message = null;

    public function __construct($table, $field)
    {
        $this->_table     = $table;
        $this->_field     = $field;
        $this->_valid = $this->valid();
        $this->setSettings();
    }

    public function setTable($table)
    {
        $this->_table = $table;
    }

    public function setField($field)
    {
        $this->_field = $field;
    }

    public function valid()
    {
        $sql = "show tables";
        $rs = mysql_query($sql);
        while ($row = mysql_fetch_array($rs)) {
            if ($this->_table == ($row[0])) {
                $valid_table = true;
                break(1);
            }
        }
        if ($valid_table) {
            $sql = "show columns from " . mysql_real_escape_string($this->_table);
            $rs = mysql_query($sql) or die(mysql_error());
            while ($column = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                if ($column['Field'] == $this->_field) {
                    $valid_field = true;
                    break(1);
                }
            }
            if ($valid_field) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function setSettings()
    {
        if ($this->_valid) {
            $sql = 'SHOW COLUMNS FROM `' . $this->_table . '` LIKE  "' . mysql_real_escape_string($this->_field) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                $row = mysql_fetch_array($rs, MYSQL_ASSOC);
                $values = explode(',', str_replace('enum(', '', rtrim($row['Type'], ')')));
                if (is_array($values)) {
                    foreach ($values as $key => $value) {
                        $values[$key] = trim($value, "'");
                    }
                    natcasesort($values);
                    $this->settings = $values;
                } else {
                    $this->settings = array();
                }
            }
        } else {
            $this->settings = array();
        }
    }

    public function save()
    {
        if (is_array($this->settings)) {
            $enumValues = '';
            foreach ($this->settings as $setting) {
                $enumValues .= ($enumValues ? ',' : '') . "'" . mysql_real_escape_string($setting) . "'";
            }
        }
        if ($enumValues) {
            $sql = 'ALTER TABLE `' . mysql_real_escape_string($this->_table) . '` CHANGE `' . mysql_real_escape_string($this->_field) . '` `' . mysql_real_escape_string($this->_field) . '` ENUM(' . $enumValues . ') null';
            $rs = mysql_query($sql);
            $affectedRows = mysql_affected_rows();
            if ($affectedRows) {
                $this->setSettings();
            }
        }
        return $affectedRows;
    }
}
