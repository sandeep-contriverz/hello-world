<?php

namespace Hmg\Models;

class Zip
{
    private $_table = 'county_zipcodes';
    private $_countyId = null;
    public $zipcodes = array();
    public $message = null;

    public function __construct($countyId = null, $natsort = false)
    {
        $this->setCountyId($countyId);
        $this->setZipcodes($natsort);
    }

    public function setCountyId($countyId)
    {
        $this->_countyId = $countyId;
    }

    public function getCountyId()
    {
        return $this->_countyId;
    }

    public function setZipcodes($natsort = false)
    {
        $sql = 'SELECT `z`.*, city.name city FROM `' . $this->_table . '` z 
                JOIN settings city ON city.id = z.city
                WHERE 1 AND `z`.disabled != "1"'
            . ($this->_countyId ? ' AND `id` = "' . mysql_real_escape_string($this->_countyId) . '"' : '')
            . ' ORDER BY ' . ($natsort ? ' Length(zip_code) ASC,' : '') . ' zip_code ASC';
        
        $rs = mysql_query($sql) or die($sql . '<br />' . mysql_error());
        if ($rs) {
            $zipcodes = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $zipcodes[$row['zip_code']] = array('countyId' => $row['id'], 'city' => $row['city']);
            }
            $this->zipcodes = $zipcodes;
        }
    }

    public function getCountyByZip($zip)
    {
        $county = array();
        $sql = 'SELECT z.id, c.name county FROM `' . $this->_table . '` z '
            . 'JOIN settings c ON c.id = z.county_id '
            . 'WHERE `zip_code` = "' . mysql_real_escape_string($zip) . '" '
            . 'LIMIT 1';
        
        $rs = mysql_query($sql) or die($sql . '<br />' . mysql_error());
        if (mysql_num_rows($rs)) {
            $row = mysql_fetch_array($rs, MYSQL_ASSOC);
            $county = $row;
        }

        return $county;
    }

    public function getCountyByCity($city)
    {
        $county = array();
        $sql = 'SELECT z.id, c.name county FROM `' . $this->_table . '` z '
            . 'JOIN settings c ON c.id = z.county_id 
                JOIN settings city ON city.id = z.city '
            . 'WHERE `city`.name = "' . mysql_real_escape_string($city) . '" '
            . 'LIMIT 1';
       
        $rs = mysql_query($sql) or die($sql . '<br />' . mysql_error());
        if (mysql_num_rows($rs)) {
            $row = mysql_fetch_array($rs, MYSQL_ASSOC);
            $county = $row;
        }

        return $county;
    }

    public function getZipcodesByCity($city)
    {
        $sql = 'SELECT `z`.*, city.name city FROM z `' . $this->_table . '` 
            JOIN settings city ON city.id = z.city
            WHERE `city`.`name` = "' . mysql_real_escape_string($city) . '"'
            . ' ORDER BY city.name ASC, ' . ($natsort ? ' Length(zip_code) ASC,' : '');
        $rs = mysql_query($sql) or die($sql . '<br />' . mysql_error());
        if ($rs) {
            $zipcodes = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $zipcodes[$row['zip_code']] = array('countyId' => $row['id'], 'city' => $row['city']);
            }
            $this->zipcodes = $zipcodes;
        }
    }

    public function displaySelect($name, $selected, $label = '', $tabIndex = '', $required = false, $addtlclasses = null, $useId = true, $natsort = false)
    {
        if ($label && ! is_array($selected)) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '';
        }
        $key = 'name';
        if ($useId) {
            $key = 'id';
        }
        foreach ($this->zipcodes as $key => $value) {
            $options .= '<option value="' . $key . '"' . ((is_array($selected) && in_array($key, $selected)) || $selected == $key ? ' selected="selected"' : '') . '>' . $key . '</option>';
        }
        $select = '<select id="' . $name . '" class="setting' . ($required ? ' required' : '') . ($addtlclasses ? ' ' . $addtlclasses : '') . '" name="' . $name . (is_array($selected) ? '[]' : '') . '" tabindex="' . $tabIndex . '" ' . (is_array($selected) ? ' multiple="mutliple" size="5"' : '') . '>' . $options . '</select>';
        return $select;
    }
}
