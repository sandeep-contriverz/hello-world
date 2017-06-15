<?php

namespace Hmg\Models;

class SchoolDistrictZipcodes
{
    private $_table = 'school_district_zipcodes';
    public $zipcodes = array();

    public function __construct()
    {
    }

    public function getSchoolDistrictZipCodes()
    {
        $sql = 'SELECT `sd`.`id`, `sd`.`name`, `sdz`.`zip_code` FROM `' . $this->_table . '` sdz LEFT JOIN school_districts sd ON sdz.district_id = sd.id WHERE 1 ORDER BY sd.name, sdz.zip_code';
        $rs = mysql_query($sql);

        $schoolDistrictZips = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $schoolDistrictZips[] = $row;
        }

        return $schoolDistrictZips;
    }

    public function getAll($district_id = null)
    {
        $rows = array();
        $where = '';
        if(!empty($district_id)) {
            $where = ' AND sdz.district_id="'.$district_id.'" ';
        }
        //$sql = 'SELECT `sdz`.`id`, `sdz`.`district_id`, `sdz`.`zip_code` as name, `sdz`.`disabled`, `sd`.`name` as district  FROM `' . $this->_table . '` sdz LEFT JOIN school_districts sd ON sdz.district_id = sd.id WHERE 1 '.$where.'  ORDER BY sd.name, sdz.zip_code';
        $sql = 'SELECT `sdz`.`id`, `sdz`.`district_id`, `sdz`.`zip_code` as name, `sdz`.`disabled`, `sd`.`name` as district  FROM `school_districts` sd LEFT JOIN `' . $this->_table . '` sdz ON sdz.district_id = sd.id WHERE 1 '.$where.'  ORDER BY sd.name, sdz.zip_code';
        $rs   = mysql_query($sql);
        //echo $sql;
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            if(!empty($district_id)) {
                $rows[] = $row;
            } else {
                $rows[$row['district']][] = $row;
            }
        }
//echo "<pre>";print_r($rows);die;
        return $rows;
    }

    public function save($district_id = 0)
    {
        //echo "<pre>";print_r($this->zipcodes);die;
        if (is_array($this->zipcodes)) {
            foreach ($this->zipcodes as $id => $name) {
                if ($id) {
                    if ($name) {
                        $sql = 'UPDATE `' . $this->_table . '` SET zip_code = "' . mysql_real_escape_string(trim($name)) . '" WHERE id = "' . mysql_real_escape_string($id) . '"';

                    } else {
                        // Delete record from the table
                        $sql = 'DELETE FROM `' . $this->_table . '` WHERE id = "' . mysql_real_escape_string($id) . '"';
                    }
                } else if ($name) {
                    $sql = 'INSERT INTO `' . $this->_table . '` SET district_id = "' . mysql_real_escape_string($district_id) . '", zip_code = "' . mysql_real_escape_string(trim($name)) .  '"';   
                                  
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
