<?php

namespace Hmg\Models;
use  Hmg\Models\User;

class ChildFollowUps
{
    private $_table = 'child_follow_up';
    private $_settingJoins = array(
        //'referred_to',
        'service'
    );
    private $_settingTable = 'settings';
    private $_sorts = array(
        'done'                 => 'ASC',
        'follow_up_date'     => 'DESC'
    );
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
        $sql = 'SELECT `' . mysql_real_escape_string($this->_table) . '`.*'
                . ', DATE_FORMAT(referral_date, "%m/%d/%y") referral_date_formatted'
                . ', DATE_FORMAT(follow_up_date, "%m/%d/%y") follow_up_date_formatted'
                . $join_selects
				. ', referred_to.name organization_name' 
				. ', sn.name site_name'
                . ', referred_to.disabled referred_to_disabled'
                . ', service.disabled service_disabled'
                . ' FROM `' . mysql_real_escape_string($this->_table) . '`'
				. ' LEFT JOIN organization_sites os 
					ON referred_to_id = os.id  
					LEFT JOIN organizations o ON o.id = os.organization_id 
					LEFT JOIN `settings` referred_to ON organization_name_id = referred_to.id 
					LEFT JOIN `settings` referred_to_site ON organization_site_id = referred_to.id 
					LEFT JOIN settings sn on sn.id=os.organization_site_id '
                . $join_clause
                . ' WHERE child_id = "' . mysql_real_escape_string($this->_child_id) . '" '
                . $order_by;
        if ($addLimit) {
            //echo 'Start: ' . $this->_start . ' Limit: ' . $this->_limit;
            $sql .= (is_numeric($this->_start) && $this->_limit ? ' LIMIT ' . $this->_start . ', ' . $this->_limit : ($this->_limit ? ' LIMIT 0, 20' : ''));
        }
        //echo $sql;
        return $sql;
    }

    public function getList()
    {
        $sql = $this->buildQuery();
        $rs = mysql_query($sql) or die(mysql_error() . $sql);
        if ($rs) {
            $user = new User;

            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                
                $row['hmg_worker'] = $user->getById($row['hmg_worker']);
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

    public function displayFieldSelect($name, $field, $selected, $label = '', $tabIndex = '', $multiSelect = false)
    {
        $sql = 'SELECT `' . mysql_real_escape_string($field) . '` FROM `' . $this->_table . '`
            WHERE 1 AND `' . mysql_real_escape_string($field) . '` != ""
            GROUP BY `' . mysql_real_escape_string($field) . '` ORDER BY `' . mysql_real_escape_string($field) . '`';
        $rs = mysql_query($sql);
        if ($label) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '';
        }
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $options .= '<option value="' . $row[$field] . '"' . (is_array($selected) ? (in_array($row[$field], $selected) ? ' selected' : '') : !empty($selected) && $selected == $row[$field] ? ' selected="selected"' : '') . '>' . $row[$field] . '</option>';
        }
        $select = '<select' . ($multiSelect ? ' multiple' : '') . ' id="' . $name . '" class="select" name="' . $name . ($multiSelect ? '[]' : '') . '" tabindex="' . $tabIndex . '">' . $options . '</select>';
        return $select;
    }
}
