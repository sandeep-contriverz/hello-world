<?php

namespace Hmg\Models;

class ContactFollowUps
{
    private $_table = 'contact_follow_up';
    private $_settingJoins = array(
        'service'
        //'referred_to',
    );
    private $_settingTable = 'settings';
    private $_sorts = array(
        'done'                 => 'ASC',
        'follow_up_date'     => 'DESC'
    );
    private $_contact_id = null;
    private $_start = 0;
    private $_limit = 20;
    private $_search = null;
    private $_filters = null;
    private $_mysql_error = null;
	private $_joinTable = 'organization_startend';
    public function __construct($contact_id)
    {
        $this->_contact_id = $contact_id;
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
                $filter_by_1 = '';
        $filter_by_2 = '';

        $filter_by = '';
        $filter_by_date = '';
        $filter_by_region = '';
        $join_clause = '';
        $join_selects = '';
        $having_clause = '';
        $group_clause = '';

        $filter_by_school_district = '';
        $filter_by_city            = '';
        $filter_by_county          = '';
        $filter_by_zip             = '';
        $filter_by_issue           = '';
        $filter_by_age             = '';
        $filter_by_child           = '';
        $filter_by_child_id        = '';
        $filter_by_phone           = '';
        $filter_by_heard_id        = '';
        
        $joinedStartEnd            = false;
        $joinedChildren            = false;
		if (!empty($_SESSION['user']['region_id'])) {
            $this->_filters['region_id'] = $_SESSION['user']['region_id'];
        }

        if (is_array($this->_settingJoins)) {
            foreach ($this->_settingJoins as $key) {
                if ($key) {
                    $join_selects .= ', ' . mysql_real_escape_string($key) . '.name ' . mysql_real_escape_string($key);
                    $join_clause .= ' LEFT JOIN `' . $this->_settingTable . '` ' . mysql_real_escape_string($key)
                         . ' ON contact_follow_up.' . mysql_real_escape_string($key) . '_id = ' . mysql_real_escape_string($key) . '_id';
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
            foreach ($this->_filters as $filterName => $value) 
			{              		 
				
				if( $filterName == 'follow_up_task' ){
					$filterName = 'follow_up_task_id';
				}
				if(!is_array($value))
                   $value = trim($value);
                if (!strpos($filterName, 'date')
                    && $filterName                        != 'city'
                    && $filterName                        != 'zip'
                    && $filterName                        != 'county'
                    && strpos($filterName, 'status')      !== 0
                    && strpos($filterName, 'event_type_id')  !== 0
                    && strpos($filterName, 'type_of_contact_id')  !== 0
					&& strpos($filterName, 'partnership_level_id')  !== 0
					&& strpos($filterName, 'resource_database_id')  !== 0
                    && strpos($filterName, 'child_id')    !== 0
                    && strpos($filterName, 'region_id')   !== 0
					&& strpos($filterName, 'hmg_worker')   !== 0
					&& strpos($filterName, 'contact_name')   !== 0
                    && strpos($filterName, 'done')   !== 0
                    && $filterName != 'quick') {
                    if ($this->_search || $value) {
                        $filter_by .= ($filter_by ? ' AND ' : '')
                        . (strpos($filterName, ',') ? 'LOWER(CONCAT_WS(" ",'  . $filterName . '))' : mysql_real_escape_string($filterName))
                        . ' LIKE "%' . strtolower(mysql_real_escape_string(($this->_search ? $this->_search : $value))) . '%"';
                    }
					
                }
				else if (($filterName == 'done' && $value!="All")) 
                {
                    $filter_by .= ($filter_by ? ' AND ' : '')
                        . 'done = "' . mysql_real_escape_string($value) . '"';
                        
                }
				else if (($filterName == 'region_id') && $value) 
				{                    
                     $filter_by .= ($filter_by ? ' AND ' : '')
                        . 'organizations.region_id = "' . mysql_real_escape_string(strtolower($value)) . '"';
						
                }
				else if (($filterName == 'partnership_level_id') && $value) 
				{                    
                     $filter_by .= ($filter_by ? ' AND ' : '')
                        . 'organizations.partnership_level_id = "' . mysql_real_escape_string(strtolower($value)) . '"';
						
                }
				else if (($filterName == 'resource_database_id') && $value) 
				{                    
                     $filter_by .= ($filter_by ? ' AND ' : '')
                        . 'organizations.partnership_level_id = "' . mysql_real_escape_string(strtolower($value)) . '"';
						
                }
				else if (strpos($filterName, 'date') && $value) 
				{					
					
                   $dateValue = date('Y-m-d', strtotime(str_replace('-', '/', $value)));
                    switch($filterName) {
                        case 'start_date':
                           $filter_by_date .= ($filter_by_date ? ' AND ' : '') . 'organization_startend.start_date >= "' . $dateValue . '"';
                            break;
                        case 'end_date':
                            $filter_by_date .= ($filter_by_date ? ' AND ' : '') . 'organization_startend.start_date <= "' . $dateValue . '"';
                            break;
                    }
                    if (!$joinedStartEnd) 
					{
                        $join_clause .= ' LEFT JOIN `' . $this->_joinTable . '` ON `' . $this->_table . '`.id = `' . $this->_joinTable . '`.parent_id ';
                        $group_clause = ' GROUP BY `' . $this->_table . '`.id ';
                        $joinedStartEnd = true;
						
                    }
                }
                
				else if ($filterName == 'status' && $value) {
                    $setting = new Setting();
                    $filter_by_1 .= ($filter_by_1 ? ' AND ' : '')
                         . 'os.status = "' . mysql_real_escape_string($value) . '"';
                    $filter_by_2 .= ($filter_by_2 ? ' AND ' : '')
                         . 'os.status = "' . mysql_real_escape_string($value) . '"';
                }
				
				else if(($filterName == 'event_type_id') && $value) {
                    $join_clause .= ' JOIN `events` ev ON `' . $this->_table. '`.id = `ev`.organization_sites_id ';
                    if(is_array($value)) {
                        $filter_by .= ($filter_by ? ' AND ' : '') .'(';
                        $i = 1;
                        foreach($value as $val) {
                            if($i > 1)
                                $filter_by .= ' OR ';
                            $filter_by .= 'ev.event_id = "' . mysql_real_escape_string(strtolower($val)) . '"';
                            $i++;
                        }
                        $filter_by .= ')';
                    } else {
                        $filter_by .= ($filter_by ? ' AND ' : '')
                        . 'ev.event_id = "' . mysql_real_escape_string(strtolower($value)) . '"';
                    }
                    
                }
				
			   else if (($filterName == 'type_of_contact_id') && $value) {
                    $join_clause .= ' LEFT JOIN `contacts` ct ON `' . $this->_table . '`.id = `ct`.organization_sites_id ';
                    if(is_array($value)) {
                        $filter_by .= ($filter_by ? ' AND ' : '') .'(';
                        $i = 1;
                        foreach($value as $val) {
                            if($i > 1)
                                $filter_by .= ' OR ';
                            $filter_by .= 'ct.type_of_contact_id = "' . mysql_real_escape_string(strtolower($val)) . '"';
                            $i++;
                        }
                        $filter_by .= ')';
                    } else {
                        $filter_by .= ($filter_by ? ' AND ' : '')
                            . 'ct.type_of_contact_id = "' . mysql_real_escape_string(strtolower($value)) . '"';
                    }
                }
				
				else if ($filterName == 'quick' && $value) {
                    $filter_by .= ($filter_by ? ' AND ' : '')
                        . 'LOWER(organization_name.name) LIKE "%' . mysql_real_escape_string(strtolower($value)) . '%"';
                } else if ($filterName == 'hmg_worker' && $value) {
                    $filter_by .= ($filter_by ? ' AND ' : '')
                        . 'users.hmg_worker = "' . $value . '"';
                }
				
				else  if (($filterName == 'city') && $value) 
				 {
                    if (is_array($value)) {
                        $citySql = '';
                        foreach ($value as $cityName) 
						{
							
						$citySql .= ($citySql ? ' OR ' : '') . ' organizations.'. mysql_real_escape_string($filterName) . ' LIKE "%' . $cityName . '%"';
                        }
                      $filter_by_city .= $citySql;
                    } else {
                        $filter_by_city .= 'organizations.'. mysql_real_escape_string($filterName) . ' LIKE "%' . mysql_real_escape_string($value) . '%"';
                    }
                }
				
				else if (($filterName == 'county') && $value) {
                    if (is_array($value)) {
                        $countySql = '';
                        foreach ($value as $countyName) {
                            $countySql .= ($countySql ? ' OR ' : '') . 'organizations.'. mysql_real_escape_string($filterName) . ' LIKE "%' . $countyName . '%"';
                        }
                        $filter_by_county .= $countySql;
                    } else {
                        $filter_by_county .= 'organizations.' . mysql_real_escape_string($filterName) . ' LIKE "%' . mysql_real_escape_string($value) . '%"';
                    }
                    $group_clause = ' GROUP BY `' . $this->_table . '`.id ';
                }
			}
			
        }
	
        $sql = 'SELECT `' . mysql_real_escape_string($this->_table) . '`.*'
                . ', DATE_FORMAT(referral_date, "%m/%d/%y") referral_date_formatted'
                . ', DATE_FORMAT(follow_up_date, "%m/%d/%y") follow_up_date_formatted,
                referred_to.name referred_to, contact_follow_up.id cfid '
                . $join_selects
				. ', referred_to.name organization_name,referred_to_type' 
				. ', sn.name site_name, users.hmg_worker'
                . ', referred_to.disabled referred_to_disabled'
                . ', service.disabled service_disabled'
                . ' FROM `' . mysql_real_escape_string($this->_table) . '`'
                .' LEFT JOIN organization_sites os ON os.id=referred_to_id
                   LEFT JOIN organizations o ON o.id=os.organization_id 
                   LEFT JOIN `settings` referred_to ON o.organization_name_id = referred_to.id 
				   LEFT JOIN `settings` referred_to_site ON organization_site_id = referred_to.id 
				   LEFT JOIN settings sn on sn.id=os.organization_site_id 
                   LEFT JOIN users ON users.id=`' . mysql_real_escape_string($this->_table) . '`.hmg_worker'
                . $join_clause
                . ' WHERE contact_id = "' . mysql_real_escape_string($this->_contact_id) . '" '
                .' GROUP By `' . mysql_real_escape_string($this->_table) . '`.id '
                . $order_by;

                  $sql .= 	($filter_by_1 ? ' AND (' . $filter_by_1 . ')' : '')
                
                . ($filter_by_date ? ' AND (' . $filter_by_date . ')' : '')
				. ($filter_by_region ? ' AND county IN (' . implode(', ', $filter_by_counties) . ')' : '') 
                . ($filter_by_city ? ' AND (' . $filter_by_city . ')' : '')
				. ($filter_by_county ? ' AND (' . $filter_by_county . ')' : '')
                . ($filter_by_2 ? ' AND (' . $filter_by_2 . ')' : '')
				. ($filter_by ? ' AND (' . $filter_by . ')' : '')
			
                . ($filter_by_region ? ' AND county IN (' . implode(', ', $filter_by_counties) . ')' : '')
				;

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
