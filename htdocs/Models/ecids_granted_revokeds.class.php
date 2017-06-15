<?php

namespace Hmg\Models;

class EcidsGrantedRevokeds
{
    private $_table = 'ecids_granted_revoked';
    private $_sorts = array('granted' => 'DESC');
    private $_start = 0;
    private $_fkey = 'family_id';
    private $_fkeyValue = 0;
    private $_mysql_error = null;

    public function __construct($fkeyValue = null)
    {
        if ($fkeyValue) {
            $this->_fkeyValue = $fkeyValue;
        }
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
        $sql = 'SELECT *, DATE_FORMAT(granted, "%m/%d/%Y") formatted_start_date, DATE_FORMAT(revoked, "%m/%d/%Y") formatted_end_date'
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
        $sql = 'SELECT MIN(DATE_FORMAT(granted, "%m/%d/%Y")) formatted_start_date, MAX(DATE_FORMAT(revoked, "%m/%d/%Y")) formatted_end_date'
                . ' FROM `' . mysql_real_escape_string($this->_table) . '`'
                . ' WHERE `' . mysql_real_escape_string($this->_fkey) . '` = "' . mysql_real_escape_string($this->_fkeyValue) . '" '
                . ' GROUP BY ' . mysql_real_escape_string($this->_fkey);
        //echo $sql;die;
        $rs = mysql_query($sql);
        return $row = mysql_fetch_array($rs, MYSQL_ASSOC);
    }

    public function getMaxDates()
    {
        $sql = 'SELECT MAX(DATE_FORMAT(granted, "%m/%d/%Y")) formatted_start_date, MAX(DATE_FORMAT(revoked, "%m/%d/%Y")) formatted_end_date'
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

    public function getLastCloseReason($filters = array())
    {
        $filter_date = '';
        $rows = array();
        $sql  = 'SELECT *'
                . ' FROM `' . mysql_real_escape_string($this->_table) . '`'
                . ' WHERE `' . mysql_real_escape_string($this->_fkey) . '` = "' . mysql_real_escape_string($this->_fkeyValue) . '" ';
        if(isset($filters['start_date']) && !empty($filters['start_date'])) {
            $filter_date = ' AND start_date >= "'.date('Y-m-d', strtotime($filters['start_date'])).'"';
        }
        if(isset($filters['end_date']) && !empty($filters['end_date'])) {
            $filter_date = ' AND start_date Between "'.date('Y-m-d', strtotime($filters['start_date'])).'" AND "'.date('Y-m-d', strtotime($filters['end_date'])).'"';
        }
        $sql .= $filter_date;
        if(empty($filter_date))
            $sql .= ' ORDER BY id DESC LIMIT 0, 1';

        //echo $sql;die;
        $rs = mysql_query($sql);
        if(isset($filters['start_date']) && !empty($filters['start_date'])) {
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $rows[] = $row;
            }
            return $rows;
        }
        $row = mysql_fetch_array($rs, MYSQL_ASSOC);
        return $row['reason'];
    }
}
