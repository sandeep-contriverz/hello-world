<?php

namespace Hmg\Models;

class FamilyScreenings
{
    private $_table = 'family_screenings';
    private $_settingJoins = array();
    private $_settingTable = 'settings';
    private $_sorts = array('date_sent' => 'DESC', 'id' => 'DESC');
    private $_family_id = null;
    private $_start = 0;
    private $_limit = 20;
    private $_search = null;
    private $_filters = null;
    private $_mysql_error = null;

    public function __construct($family_id)
    {
        $this->_family_id = $family_id;
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
        if (is_array($this->_settingJoins)) {
            foreach ($this->_settingJoins as $key) {
                if ($key) {
                    $join_selects .= ', ' . mysql_real_escape_string($key) . '.name ' . mysql_real_escape_string($key);
                    $join_clause .= ' LEFT JOIN `' . $this->_settingTable . '` ' . mysql_real_escape_string($key)
                         . ' ON ' . mysql_real_escape_string($key) . '_id = ' . mysql_real_escape_string($key) . '.id';
                }
            }
        }
        if (is_array($this->_sorts)) {
            $order_by_fields = '';
            $concat = false;
            foreach ($this->_sorts as $field => $dir) {
                $order_by_fields .= ($concat ? ', ' : '') . mysql_real_escape_string($field) . ' ' . mysql_real_escape_string($dir);
                $concat = true;
            }
            $order_by = ($concat ? ' ORDER BY ' . $order_by_fields : '');
        }
        if (is_array($this->_filters)) {
            foreach ($this->_filters as $filterName => $value) {
                if (!strpos($filterName, 'date')) {
                    if ($value) {
                        $filter_by .= ($filter_by ? ' AND ' : '')
                        . (strpos($filterName, ',') ? 'LOWER(CONCAT_WS(" ",'  . $filterName . '))' : mysql_real_escape_string($filterName))
                        . ' LIKE "%' . strtolower(mysql_real_escape_string($value)) . '%"';
                    }
                } elseif (strpos($filterName, 'date') && $value) {
                    $dateValue = date('Y-m-d', strtotime(str_replace('-', '/', $value)));
                    switch ($filterName) {
                        case 'scored_start_date':
                            $filter_by_date .= ($filter_by_date ? ' AND ' : '') . 'date_scored >= "' . $dateValue . '"';
                            break;
                        case 'scored_end_date':
                            $filter_by_date .= ($filter_by_date ? ' AND ' : '') . 'date_scored <= "' . $dateValue . '"';
                            break;
                    }
                }
            }
        }
        $sql = 'SELECT `' . mysql_real_escape_string($this->_table) . '`.*'
                . ', DATE_FORMAT(date_sent, "%m/%d/%y") date_sent_formatted'
                . ', DATE_FORMAT(date_sent_provider, "%m/%d/%y") date_sent_provider_formatted,
                    DATE_FORMAT(date_scored, "%m/%d/%y") date_scored_formatted'
                . $join_selects
                . ' FROM `' . mysql_real_escape_string($this->_table) . '`'
                . $join_clause
                . ' WHERE family_id = "' . mysql_real_escape_string($this->_family_id) . '" '
                . ($filter_by ? ' AND (' . $filter_by . ')' : '')
                . ($filter_by_date ? ' AND (' . $filter_by_date . ')' : '')
                . $order_by;
        if ($addLimit) {
            //echo 'Start: ' . $this->_start . ' Limit: ' . $this->_limit;
            $sql .= (is_numeric($this->_start) && $this->_limit ? ' LIMIT ' . $this->_start . ', ' . $this->_limit : ($this->_limit ? ' LIMIT 0, 20' : ''));
        }
        //echo $sql;
        return $sql;
    }

    public function getList($addLimit = false)
    {
	
        $sql = $this->buildQuery($addLimit);
		
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

    public function displayEnumSelect($name, $field, $selected, $label = '', $tabIndex = '')
    {
        $sql = 'SHOW COLUMNS FROM `' . $this->_table . '` LIKE  "' . mysql_real_escape_string($field) . '"';
        $rs = mysql_query($sql);
        if ($rs) {
            $row = mysql_fetch_array($rs, MYSQL_ASSOC);
            $values = explode(',', str_replace('enum(', '', rtrim($row['Type'], ')')));
            sort($values);
            if (is_array($values)) {
                if ($label) {
                    $options = '<option value="">' . $label . '</option>';
                } else {
                    $options = '';
                }
                foreach ($values as $key => $value) {
                    $trimmed = trim($value, "'");
                    $options .= '<option value="' . $trimmed . '"' . ($selected == $trimmed ? ' selected="selected"' : '') . '>' . $trimmed . '</option>';
                }
            }
            $select = '<select id="' . $field . '" class="' . $field . '" name="' . $name . '" tabindex="' . $tabIndex . '">' . $options . '</select>';
            return $select;
        }
    }

    public function getFamilyScreenings($family_id = null) {
        $sql = 'Select * from families c 
                JOIN family_screenings cds ON cds.family_id=c.id
                Where id="'.$family_id.'"';

        $rs = mysql_query($sql) or die(mysql_error() . $sql);
        return mysql_num_rows($rs);
    }
}
