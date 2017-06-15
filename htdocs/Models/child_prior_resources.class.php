<?php

namespace Hmg\Models;

class ChildPriorResources
{
    private $_table = 'child_prior_resources';
    private $_sorts = array('date_enrolled' => 'DESC', 'id' => 'DESC');
    private $_child_id = null;
    private $_start = 0;
    private $_limit = 20;
    private $_search = null;
    private $_filters = null;
    private $_mysql_error = null;

    public function __construct($child_id)
    {
        $this->_child_id = $child_id;
    }

    public function set($key, $value)
    {
        $this->$key = $value;
    }

    public function get($key)
    {
        return $this->$key;
    }

    private function buildQuery($addLimit = false)
    {
        $order_by = '';
        $filter_by = '';
        $filter_by_date = '';
        $join_clause = '';
        $join_selects = '';
        $having_clause = '';
        $group_clause = '';
        if (is_array($this->_sorts)) {
            $order_by_fields = '';
            $concat = false;
            foreach ($this->_sorts as $field => $dir) {
                $order_by_fields .= ($concat ? ', ' : '') . mysql_real_escape_string($field) . ' ' . mysql_real_escape_string($dir);
                $concat = true;
            }
            $order_by = ($concat ? ' ORDER BY ' . $order_by_fields : '');
        }
        $sql = 'SELECT `' . mysql_real_escape_string($this->_table) . '`.*'
                . ', DATE_FORMAT(date_enrolled, "%m/%d/%y") date_enrolled_formatted'
                . $join_selects
                . ' FROM `' . mysql_real_escape_string($this->_table) . '`'
                . $join_clause
                . ' WHERE child_id = "' . mysql_real_escape_string($this->_child_id) . '" '
                . $order_by;
        if ($addLimit) {
            //echo 'Start: ' . $this->_start . ' Limit: ' . $this->_limit;
            $sql .= (is_numeric($this->_start) && $this->_limit ? ' LIMIT ' . $this->_start . ', ' . $this->_limit : ($this->_limit ? ' LIMIT 0, 20' : ''));
        }
        return $sql;
    }

    public function getList()
    {
        $sql = $this->buildQuery();
        $rs = mysql_query($sql) or die(mysql_error() . $sql);
        if ($rs) {
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $rows[] = $row;
            }
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
