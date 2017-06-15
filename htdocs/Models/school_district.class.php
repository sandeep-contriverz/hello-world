<?php

namespace Hmg\Models;

class SchoolDistrict
{
    private $_table = 'school_districts';
    public $districts = array();

    public function __construct()
    {
    }

    public function getSchoolDistrictById($id)
    {
        $sql = 'SELECT name FROM `' . $this->_table . '` WHERE id = "' . mysql_real_escape_string($id) . '"';
        $rs = mysql_query($sql);
        $row = mysql_fetch_array($rs, MYSQL_ASSOC);

        $name = '';
        if (isset($row['name'])) {
            $name = $row['name'];
        }
        return $name;
    }

    public function displaySelect($name, $selected, $label = '', $tabIndex = '')
    {
        $sql = 'SELECT * FROM `' . $this->_table . '` WHERE 1 ORDER BY name';
        $rs = mysql_query($sql);
        if ($label) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '';
        }
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $options .= '<option value="' . $row['id'] . '"' . ($selected == $row['id'] ? ' selected="selected"' : '') . '>' . $row['name'] . '</option>';
        }
        $select = '<select id="' . $name . '" class="school_district" name="' . $name . '"  tabindex="' . $tabIndex . '">' . $options . '</select>';
        return $select;
    }

    public function getAll()
    {
        $rows = array();
        $sql  = 'SELECT * FROM `' . $this->_table . '` WHERE 1 ORDER BY name';
        $rs   = mysql_query($sql);
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function save()
    {
        //echo "<pre>";print_r($this->districts);die;
        if (is_array($this->districts)) {
            foreach ($this->districts as $id => $name) {
                if ($id) {
                    if ($name) {
                        $sql = 'UPDATE `' . $this->_table . '` SET name = "' . mysql_real_escape_string(trim($name)) . '" WHERE id = "' . mysql_real_escape_string($id) . '"';

                    } else {
                        // Delete record from the table
                        $sql = 'DELETE FROM `' . $this->_table . '` WHERE id = "' . mysql_real_escape_string($id) . '"';
                    }
                } else if ($name) {
                    //check if its already exists
                    $sql_check  = 'SELECT * FROM `' . $this->_table . '` WHERE name = "' . mysql_real_escape_string($name) . '"';
                    $rs_check  = mysql_query($sql_check);
                    $row_check = mysql_fetch_array($rs_check, MYSQL_ASSOC);
                    if(!empty($row_check) && isset($row_check['name']))
                        return false;
                    $sql = 'INSERT INTO `' . $this->_table .'` (`name`) values("' . mysql_real_escape_string(trim($name)) .  '")';
                                  
                }
                //echo $sql;
                $rs = mysql_query($sql);
                if(!$id)
                {
                    $id = mysql_insert_id();
                    $sql = 'INSERT INTO `school_district_zipcodes` SET district_id = "' . mysql_real_escape_string($id) . '", zip_code = "0", disabled="1"';
                    $rs = @mysql_query($sql);
                }
               
            }
            return true;
        }
    }

    public function updateRecordDisabled($id, $value)
    {
        if ($id) {
            $sql = 'UPDATE `' . $this->_table . '` SET `disabled` = "' . mysql_real_escape_string($value ? '1' : '0') . '" WHERE id = "' . mysql_real_escape_string($id) . '"';
            $rs = mysql_query($sql) or die(mysql_error());
        }
        return $rs;
    }
}
