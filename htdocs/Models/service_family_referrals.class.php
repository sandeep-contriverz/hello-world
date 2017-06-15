<?php

namespace Hmg\Models;

class ServiceFamilyReferrals
{
    private $_table = 'family_referrals';
    private $_settingJoins = array(
        
        'issue',
        'service'
    );
    private $_settingTable = 'settings';
    private $_sorts = array('referred_to.name' => 'ASC', 'service.name' => 'ASC', 'outcomes' => 'ASC');
    private $_family_id = null;
    private $_start = 0;
    private $_limit = 20;
    private $_search = null;
    private $_filters = null;
    private $_mysql_error = null;

    public function __construct($service_id)
    {
        $this->_service_id = $service_id;
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
        $group_clause = 'GROUP BY referred_to_id, service_id, outcomes';
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
        $sql = 'SELECT `' . mysql_real_escape_string($this->_table) . '`.*, DATE_FORMAT(referral_date, "%m/%d/%y") referral_date_formatted, count(*) totals '
                . $join_selects
                . ', referred_to.name organization_name' 
                . ', sn.name site_name'
                . ', referred_to.disabled referred_to_disabled'
                . ', service.disabled service_disabled,u.hmg_worker'
                . ' FROM `' . mysql_real_escape_string($this->_table) . '`'
                . ' LEFT JOIN organization_sites os 
                    ON referred_to_id = os.id  
                    LEFT JOIN organizations o ON o.id = os.organization_id 
                    LEFT JOIN `settings` referred_to ON organization_name_id = referred_to.id 
                    LEFT JOIN `settings` referred_to_site ON organization_site_id = referred_to.id 
                    LEFT JOIN settings sn on sn.id=os.organization_site_id
                    LEFT JOIN `users` u ON family_referrals.hmg_worker = u.id'
                . $join_clause
                . ' WHERE service_id = "' . mysql_real_escape_string($this->_service_id) . '" '
                . $group_clause
                . $order_by;
        if ($addLimit) {
            
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
