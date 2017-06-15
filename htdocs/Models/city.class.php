<?php

namespace Hmg\Models;

class City
{
    private $_table = 'cities';
    private $_start = 0;
    private $_limit = 20;

    public function __construct()
    {
    }
    public function set($key, $value)
    {
        $this->$key = $value;
    }

    public function get($key)
    {
        return $this->$key;
    }
    public function displaySelect($name, $selected, $label = '', $tabIndex = '', $style = '')
    {
        $sql = 'SELECT * FROM `' . $this->_table . '` WHERE 1 ORDER BY city';
        $rs = mysql_query($sql);
        if ($label) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '';
        }
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $options .= '<option value="' . $row['city'] . '"' . ($selected == $row['city'] ? ' selected="selected"' : '') . '>' . $row['city'] . '</option>';
        }
        $select = '<select id="' . $name . '" class="city" style="' . $style . '" name="' . $name . '"  tabindex="' . $tabIndex . '">' . $options . '</select>';
        return $select;
    }

    public function getcities(){
        $sql = 'SELECT * FROM `' . $this->_table . '` LIMIT '.$this->_start.','.$this->_limit.'';
        $rs = mysql_query($sql);
        if($rs){
            while($row = mysql_fetch_array($rs,MYSQL_ASSOC)){
                $rows[]=$row;
            }
            if(isset($rows)){
                return $rows;
            }else{
                return false;
            }
        }
    }

    public function getcount(){
        $sql = 'SELECT * FROM `'.$this->_table.'`';
        $rs = mysql_query($sql);
        return mysql_num_rows($rs);
    }
}
