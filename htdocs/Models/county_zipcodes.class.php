<?php

namespace Hmg\Models;

class CountyZipcodes
{
    private $_table = 'county_zipcodes';

    public function __construct()
    {
    }

    public function getCountyZipCodes()
    {
        $sql = 'SELECT `c`.`id`, `c`.`name` county, `cz`.`zip_code` FROM `' . $this->_table . '` cz LEFT JOIN settings c ON cz.county_id = c.id WHERE 1 ORDER BY c.county, cz.zip_code';
        $rs = mysql_query($sql);

        $countyZips = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $countyZips[] = $row;
        }

        return $countyZips;
    }

    public function getAll($county_id = null)
    {
        $rows = array();
        $where = '';
        if(!empty($county_id)) {
            $where = ' AND cz.county_id="'.$county_id.'" ';
        }
        //$sql = 'SELECT `cz`.`id`, `cz`.`county_id`, `cz`.`city`, `cz`.`zip_code` as name, `cz`.`disabled`, `sd`.`name` as county FROM `' . $this->_table . '` cz LEFT JOIN settings sd ON cz.county_id = sd.id WHERE 1 '.$where.'  ORDER BY sd.name, cz.zip_code';
        $sql = 'SELECT `cz`.`id`, `sd`.`id` county_id, `ct`.`name` as city, `cz`.`zip_code` as name, `cz`.`disabled`, `sd`.`name` as county FROM settings sd 
            LEFT JOIN `' . $this->_table . '` cz ON cz.county_id = sd.id 
            LEFT JOIN settings ct ON ct.id = cz.city
            WHERE 1 '.$where.' AND sd.type="county" ORDER BY sd.name, `ct`.`name`';
        $rs   = mysql_query($sql);
        //echo $sql;
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            if(!empty($county_id)) {
                $rows[] = $row;
            } else {
                $rows[$row['county']][] = $row;
            }
        }
//echo "<pre>";print_r($rows);die;
        return $rows;
    }

    public function save($zip = 0, $county_id = 0)
    {
        if(empty($zip))
            return false;
        //echo "<pre>";print_r($this->zipcodes);die;
        if (is_array($this->zipcodes)) {
            foreach ($this->zipcodes as $id => $name) {
                if ($id) {
                    if ($name) {
                        $sql = 'UPDATE `' . $this->_table . '` SET city = "' . mysql_real_escape_string(trim($name)) . '", zip_code = "' . mysql_real_escape_string(trim($zip)) . '" WHERE id = "' . mysql_real_escape_string($id) . '"';

                    } else {
                        // Delete record from the table
                        $sql = 'DELETE FROM `' . $this->_table . '` WHERE id = "' . mysql_real_escape_string($id) . '"';
                    }
                } else if ($name) {
                    $sql = 'INSERT INTO `' . $this->_table . '` SET city = "' . mysql_real_escape_string(trim($name)) . '", county_id = "' . mysql_real_escape_string($county_id) . '", zip_code = "' . mysql_real_escape_string(trim($zip)) .  '"';   
                                  
                }
                $rs = mysql_query($sql);
                if(!$id)
                {
                    $id = mysql_insert_id();
                }
               
            }
            return true;
            //return $this->getAll($district_id);
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
