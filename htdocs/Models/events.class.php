<?php

namespace Hmg\Models;

use Hmg\Models\RegionCounties;

class Events
{
    private $_table = 'events';
    private $_settingJoins = array(        
        'organization_type',
        'partnership_level',
        'region',
        'mode_of_contact',        
    );
    private $_settingTable = 'settings';
    private $_joinTable    = 'organization_startend';
    private $_sorts = array('organization_name' => 'asc', 'site' => 'asc');
    private $_start   = 0;
    private $_limit   = 50;
    private $_search  = null;
    private $_filters = null;
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
        $order_by                  = '';
        $filter_by                 = '';
        $filter_by_date            = '';
        $filter_by_school_district = '';
        $filter_by_city            = '';
        $filter_by_county          = '';
        $filter_by_zip             = '';
        $filter_by_issue           = '';
        $filter_by_age             = '';
        $filter_by_child           = '';
        $filter_by_child_id        = '';
        $filter_by_region          = '';
        $filter_by_phone           = '';
        $filter_by_heard_id        = '';
        $join_clause               = '';
        $join_selects              = '';
        $having_clause             = '';
        $group_clause              = ' group by event_id ';
        $joinedStartEnd            = false;
        $joinedChildren            = false;
		$joinedContectName         = false;
	
        if (!empty($_SESSION['user']['region_id'])) {
            $this->_filters['region_id'] = $_SESSION['user']['region_id'];
        }
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
                if($field == 'primary_contact') {
                    $field = 'first_name_1';
                }
                if($field == 'organization') {
                    $field = 'organization_name';
                }
				
                /*if($field == 'site') {
                    $field = 'sites.name';
                }*/
                $order_by_fields .= ($concat ? ', ' : '') . ($field) . ' ' . mysql_real_escape_string($dir);
                $concat = true;
            }
            $order_by = ($concat ? ' ORDER BY ' . $order_by_fields : '');
        }
        
        //print_r($this->_filters);
        if (is_array($this->_filters)) {
            //echo '<pre>'; print_r($this->_filters); 
            foreach ($this->_filters as $filterName => $value) {
                //echo $filterName . '=>' . $value . '<br />';
                if(!is_array($value))
                    $value = trim($value);

                if (!strpos($filterName, 'date')
                    && $filterName                        != 'city'
                    && $filterName                        != 'zip'
                    && $filterName                        != 'county'                    
                    && strpos($filterName, 'event_type_id')  !== 0
                    && strpos($filterName, 'outreach_type_id')  !== 0
                    && strpos($filterName, 'event_county_id')    !== 0
                    && strpos($filterName, 'hmg_worker')   !== 0
                    && strpos($filterName, 'region_id')   !== 0
                    && strpos($filterName, 'event_language')   !== 0
                    && strpos($filterName, 'time_of_day')   !== 0
                    && strpos($filterName, 'organization_name')   !== 0
                    && $filterName != 'quick') {
                    if ($this->_search || $value) {
                        $filter_by .= ($filter_by ? ' AND ' : '')
                        . (strpos($filterName, ',') ? 'LOWER(CONCAT_WS(" ",'  . $filterName . '))' : mysql_real_escape_string($filterName))
                        . ' LIKE "%' . strtolower(mysql_real_escape_string(($this->_search ? $this->_search : $value))) . '%"';
                    }
                } else if ($filterName == 'organization_name' && $value) {
                    $having_clause = ' having organization_name like "%' . ($this->_search ? $this->_search : $value) . '%"';
                }else if ($filterName == 'hmg_worker' && $value) {
                    $filter_by .= ($filter_by ? ' AND ' : '')
                     . ' users.hmg_worker = "%' . ($this->_search ? $this->_search : $value) . '%"';
                } else if (($filterName == 'region_id') && $value) {                    
                    $filter_by .= ($filter_by ? ' AND ' : '')
                        . ' organizations.region_id = "' . mysql_real_escape_string(strtolower($value)) . '"';
                }else if (($filterName == 'outreach_type_id') && $value) {
                            
                    $filter_by .= ($filter_by ? ' AND ' : '')
                                . 'outreach_type_id = "' . mysql_real_escape_string(strtolower($value)) . '"';
                }else if (($filterName == 'event_county_id') && $value) {
                            
                    $filter_by .= ($filter_by ? ' AND ' : '')
                                . 'event_county_id = "' . mysql_real_escape_string(strtolower($value)) . '"';
                }else if (($filterName == 'event_language') && $value) {
                            
                    $filter_by .= ($filter_by ? ' AND ' : '')
                                . 'event_language = "' . mysql_real_escape_string(strtolower($value)) . '"';
                }else if (($filterName == 'event_language') && $value) {
							
					$filter_by .= ($filter_by ? ' AND ' : '')
								. 'time_of_day = "' . mysql_real_escape_string(strtolower($value)) . '"';
				}
				else if (($filterName == 'event_type_id') && $value) {                    
                    if(is_array($value)) {
                        $filter_by .= ($filter_by ? ' AND ' : '') .'(';
                        $i = 1;
                        foreach($value as $val) {
                            if($i > 1)
                                $filter_by .= ' OR ';
                            $filter_by .= 'event_type_id = "' . mysql_real_escape_string(strtolower($val)) . '"';
                            $i++;
                        }
                        $filter_by .= ')';
                    } else {
                        $filter_by .= ($filter_by ? ' AND ' : '')
                        . 'event_type_id = "' . mysql_real_escape_string(strtolower($value)) . '"';
                    }
                }else if (strpos($filterName, 'date') && $value) {
                    $dateValue = date('Y-m-d', strtotime(str_replace('-', '/', $value)));
                    switch($filterName) {
                        case 'event_start_date':
                             $filter_by .= ($filter_by ? ' AND ' : ''). 'event_date >= "' . $dateValue . '"';
                            break;
                        case 'event_end_date':
                             $filter_by .= ($filter_by ? ' AND ' : '') . 'event_date <= "' . $dateValue . '"';
                            break;
                    }                    
                }                
				
					
            }
        }
        
        
        
        $join_clause .= ' LEFT JOIN `users`  ON `events`.`hmg_worker` = `users`.id ';
        $join_selects .=' , users.hmg_worker as hmgworker ';
        
        $sql = 'SELECT `' . mysql_real_escape_string($this->_table) . '`.*, orgn.name as organization_name, `os`.`organization_site_id` as organization_site_id, 
            sites.name as site, GROUP_CONCAT(service.service_id) service_terms, os.id as organization_sites_id, os.status';
        
		
		
         $sql .= $join_selects
                . ' FROM events left join organization_sites os on events.organization_sites_id = os.id 
                LEFT JOIN organizations ON os.organization_id=organizations.id 
                LEFT JOIN settings orgn ON orgn.id=organizations.organization_name_id 
                LEFT JOIN service ON service.referred_to_id=os.id 
                LEFT JOIN settings sites ON sites.id=os.organization_site_id 
                LEFT JOIN `settings` status ON status = `status`.id '
               
                . $join_clause
                . ' WHERE 1 '
                . ($filter_by ? ' AND (' . $filter_by . ')' : '')
                . ($filter_by_date ? ' AND (' . $filter_by_date . ')' : '')
                . ($filter_by_school_district ? ' AND (' . $filter_by_school_district . ')' : '')
                . ($filter_by_city ? ' AND (' . $filter_by_city . ')' : '')
                . ($filter_by_county ? ' AND (' . $filter_by_county . ')' : '')
                . ($filter_by_zip ? ' AND (' . $filter_by_zip . ')' : '')
                . ($filter_by_issue ? ' AND (' . $filter_by_issue . ')' : '')
                . ($filter_by_age ? ' AND (' . $filter_by_age . ')' : '')
                . ($filter_by_child ? $filter_by_child : '')
                . ($filter_by_child_id ? $filter_by_child_id : '')
                . ($filter_by_region ? ' AND county IN (' . implode(', ', $filter_by_counties) . ')' : '')
                . ($filter_by_phone ? $filter_by_phone : '')
                . ($filter_by_heard_id ? $filter_by_heard_id : '')
                . $group_clause
                . $having_clause
                . $order_by;
        if ($addLimit) {            
            $sql .= (is_numeric($this->_start) && $this->_limit ? ' LIMIT ' . $this->_start . ', ' . $this->_limit : ($this->_limit ? ' LIMIT 0, 20' : ''));
        }        
        return $sql;
    }

    public function getList($limit=true)
    {
        $sql = $this->buildQuery($limit);
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
