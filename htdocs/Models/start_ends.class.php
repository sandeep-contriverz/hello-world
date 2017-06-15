<?php

namespace Hmg\Models;

class StartEnds
{
    private $_table = 'startend';
    private $_sorts = array('start_date' => 'DESC');
    private $_start = 0;
    private $_fkey = 'parent_id';
    private $_fkeyValue = 0;
    private $_mysql_error = null;

    public function __construct($fkeyValue = null,$type)
    {
        if ($fkeyValue) {
            $this->_fkeyValue = $fkeyValue;
        }
        if($type == 'share')
            $this->_table = 'sharing_history';
    }

    public function set($key, $value)
    {
        $this->$key = $value;
    }

    public function get($key)
    {
        $key = '_' . $key;
        return $this->$key;
    }

    public function getList()
    {
        if (is_array($this->_sorts)) {
            $order_by = 'ORDER BY ';
            $concat = false;
            foreach ($this->_sorts as $field => $dir) {
                $order_by .= ($concat ? ', ' : '') . mysql_real_escape_string($field) . ' ' . mysql_real_escape_string($dir);
                $concat = true;
            }
        }
        $sql = 'SELECT *, DATE_FORMAT(start_date, "%m/%d/%Y") formatted_start_date, DATE_FORMAT(end_date, "%m/%d/%Y") formatted_end_date'
                . ' FROM `' . mysql_real_escape_string($this->_table) . '`'
                . ' WHERE `' . mysql_real_escape_string($this->_fkey) . '` = "' . mysql_real_escape_string($this->_fkeyValue) . '" '
                . $order_by;
        $rs = mysql_query($sql);
        if ($rs) {
            $rows = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $rows[] = $row;
            }
            return $rows;
        } else {
            return false;
        }
    }

    public function getMaxMinDates()
    {
        $sql = 'SELECT MIN(DATE_FORMAT(start_date, "%m/%d/%Y")) formatted_start_date, MAX(DATE_FORMAT(end_date, "%m/%d/%Y")) formatted_end_date'
                . ' FROM `' . mysql_real_escape_string($this->_table) . '`'
                . ' WHERE `' . mysql_real_escape_string($this->_fkey) . '` = "' . mysql_real_escape_string($this->_fkeyValue) . '" '
                . ' GROUP BY ' . mysql_real_escape_string($this->_fkey);
        $rs = mysql_query($sql);
        return $row = mysql_fetch_array($rs, MYSQL_ASSOC);
    }

    /**
     *  Get all start dates data
     */
    public function getAllStartDates()
    {
        $sql = 'SELECT count(*) as count'
                . ' FROM `' . mysql_real_escape_string($this->_table) . '`'
                . ' WHERE `' . mysql_real_escape_string($this->_fkey) . '` = "' . mysql_real_escape_string($this->_fkeyValue) . '" '
                . ' ORDER BY id DESC';
        //echo $sql. "<br>";
        $rs = mysql_query($sql);
        return $row = mysql_fetch_array($rs, MYSQL_ASSOC);
    }

    public function getLastCloseReason()
    {
        $sql = 'SELECT reason'
                . ' FROM `' . mysql_real_escape_string($this->_table) . '`'
                . ' WHERE `' . mysql_real_escape_string($this->_fkey) . '` = "' . mysql_real_escape_string($this->_fkeyValue) . '" '
                . ' ORDER BY id DESC LIMIT 0, 1';
        $rs = mysql_query($sql);
        $row = mysql_fetch_array($rs, MYSQL_ASSOC);

        return $row['reason'];
    }
}
