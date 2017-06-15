<?php

namespace Hmg\Models;

class Volunteers
{
    private $_table = 'volunteers';
    private $_sorts = array('last_name' => 'ASC', 'first_name' => 'ASC');
    private $_filters = null;
    private $_start = 0;
    private $_limit = 50;
    private $_mysql_error = null;

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

    private function buildQuery($addLimit = true, $getNextRecord = false, $currentId = null)
    {
    
        $order_by = '';
        $filter_by = '';
        $having = '';
        if (is_array($this->_sorts)) {
            $order_by = ' ORDER BY ';
            $concat = false;
            foreach ($this->_sorts as $field => $dir) {
                $order_by .= ($concat ? ', ' : '') . mysql_real_escape_string($field) . ' ' . mysql_real_escape_string($dir);
                $concat = true;
            }
        } 
        if (is_array($this->_filters)) {
            //var_dump($this->_filters); exit;
            foreach ($this->_filters as $filterName => $value) {
                if ($filterName == 'quick' && $value) {
                    $having = 'HAVING CONCAT_WS("", first_name, last_name, volunteering_type, phone, email) LIKE "%' . mysql_real_escape_string($value) . '%"';
                } else if ($filterName == 'status' && $value) {
                    if ($this->_search || $value) {
                        $filter_by .= ($filter_by ? ' AND ' : '')
                         . (strpos($filterName, ',') ? 'CONCAT_WS(" ",'  . $filterName . ')' : mysql_real_escape_string($filterName))
                         . ' LIKE "%' . mysql_real_escape_string(($this->_search ? $this->_search : $value)) . '%"';
                    }
                } else if ($filterName == 'area' && $value) { //241016
                    $filter_by .= ($filter_by ? ' AND find_in_set(' : ' find_in_set(')
                     . mysql_real_escape_string($value).', volunteering_type) ';
                } else if ($value) {
                    $filter_by .= ($filter_by ? ' AND ' : '')
                     . mysql_real_escape_string($value) . ' = "1"';
                }
            }
        } 
        /* 241016
        $sql = 'SELECT * , concat_ws( ",",
                    if ( family_event = "1", "Family Event", "" ) , if ( data_entry = "1", "Data Entry", "" ),
                    if ( care_coordination = "1", "Care Coordination", "" ),
                    if ( special_projects = "1", "Special Projects", "" ),
                    if ( parent_mentor = "1", "Parent Mentor", "" ),
                    if ( eagle_scout = "1", "Eagle Scout", "" ) ) as `areas` FROM `' . mysql_real_escape_string($this->_table) . '`
                WHERE 1 '
                . ($filter_by ? ' AND (' . $filter_by . ')' : '')
                . ($having ? $having : '')
                . $order_by;
                //echo $sql;
        241016
        */
        
        $sql = 'SELECT * FROM `' . mysql_real_escape_string($this->_table) . '`
                WHERE 1 '
                . ($filter_by ? ' AND (' . $filter_by . ')' : '')
                . ($having ? $having : '')
                . $order_by;
        $rs = mysql_query($sql);
        if ($addLimit) {
            //echo 'Start: ' . $this->_start . ' Limit: ' . $this->_limit;
            $sql .= (is_numeric($this->_start) && $this->_limit ? ' LIMIT ' . $this->_start . ', ' . $this->_limit : ($this->_limit ? ' LIMIT 0, 50' : ''));
        }
        //echo $sql;
        return $sql;
    }

    public function getList()
    { 
        $sql = $this->buildQuery();
        $rs = mysql_query($sql) or die(mysql_error() . $sql);
        if ($rs) {
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                //fetch volunteer areas (volunteering_type)
                $row['areas'] = '';
                if(!empty($row['volunteering_type'])) {
                    $sql2 = "SELECT GROUP_CONCAT( name ) as areas FROM `settings` 
                        WHERE id IN (".$row['volunteering_type'].") group by type";
                    $rs2 = mysql_query($sql2) or die(mysql_error() . $sql2);
                    $row2 = mysql_fetch_array($rs2, MYSQL_ASSOC);
                    //echo "<pre>";print_r($row2);
                    if(!empty($row2) && !empty($row2['areas'])) {
                        $row['areas'] = $row2['areas'];
                    }
                }
                $rows[] = $row;
            }
            //echo "<pre>";print_r($rows);die;
            if (isset($rows)) {
                return $rows;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getCount()
    {
        $sql = $this->buildQuery(false);
        $rs = mysql_query($sql) or die(mysql_error() . $sql);
        return mysql_num_rows($rs);
    }
}
