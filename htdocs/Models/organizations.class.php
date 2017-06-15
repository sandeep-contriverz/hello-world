<?php

namespace Hmg\Models;

use Hmg\Models\RegionCounties;

class Organizations
{
    private $_table = 'organizations';
    private $_settingJoins = array(
        //'event_type',
        //'referred_to',
        'organization_type',
        'partnership_level',
        'region',
        'mode_of_contact',
        //'organization_status',
    );
    private $_settingTable = 'settings';
    private $_joinTable    = 'organization_startend';
    private $_sorts = array('organization_name' => 'asc', 'site' => 'asc');
    //array(' CAST(organization_name AS UNSIGNED), organization_name' => '', "case when site is null then 1 else 0 end, site" => '');
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
        $order_by                  = '';//' ORDER BY organization_name asc, CASE WHEN sites.name IS NULL THEN 1 ELSE 0 END asc '; //' ORDER BY organization_name asc, sites.name asc ';
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
        $group_clause              = ' GROUP BY `os`.id ';
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
        //$this->_filters = array();
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
                    && strpos($filterName, 'status')      !== 0
                    && strpos($filterName, 'event_type_id')  !== 0
                    && strpos($filterName, 'type_of_contact_id')  !== 0
                    && strpos($filterName, 'child_id')    !== 0
                    && strpos($filterName, 'region_id')   !== 0
                    && strpos($filterName, 'primary_phone')   !== 0
                    && strpos($filterName, 'success_story')   !== 0
                    && strpos($filterName, 'organization_type_id')   !== 0
					&& strpos($filterName, 'contact_name')   !== 0
                    && strpos($filterName, 'organization_type_id')   !== 0
                    && strpos($filterName, 'mode_of_contact_id')   !== 0
					&& strpos($filterName, 'partnership_level_id')   !== 0
                    && $filterName != 'quick') {
                    if ($this->_search || $value) {
                        $filter_by .= ($filter_by ? ' AND ' : '')
                        . (strpos($filterName, ',') ? 'LOWER(CONCAT_WS(" ",'  . $filterName . '))' : mysql_real_escape_string($filterName))
                        . ' LIKE "%' . strtolower(mysql_real_escape_string(($this->_search ? $this->_search : $value))) . '%"';
                    }
                } else if ($filterName == 'status' && $value) {
                    //$join_clause .= ' LEFT JOIN `settings` status ON `' . $this->_table . '`.status = `status`.id ';
                    $filter_by .= ($filter_by ? ' AND ' : '')
                     . ' os.status = "' . strtolower(mysql_real_escape_string(($this->_search ? $this->_search : $value))) . '"';
                } else if ($filterName == 'mode_of_contact_id' && $value) {
                    $filter_by .= ($filter_by ? ' AND ' : '')
                     . ' organizations.mode_of_contact_id = "' . ($this->_search ? $this->_search : $value) . '"';
                }else if ($filterName == 'partnership_level_id' && $value) {
                    $filter_by .= ($filter_by ? ' AND ' : '')
                     . ' organizations.partnership_level_id = "' . ($this->_search ? $this->_search : $value) . '"';
                }else if ($filterName == 'organization_type_id' && $value) {
                    $filter_by .= ($filter_by ? ' AND ' : '')
                     . ' organizations.organization_type_id = "' . ($this->_search ? $this->_search : $value) . '"';
                } else if (strpos($filterName, 'date') && $value) {
                    $dateValue = date('Y-m-d', strtotime(str_replace('-', '/', $value)));
                    switch($filterName) {
                        case 'start_date':
                            $filter_by_date .= ($filter_by_date ? ' AND ' : '') . 'organization_startend.start_date >= "' . $dateValue . '"';
                            break;
                        case 'end_date':
                            $filter_by_date .= ($filter_by_date ? ' AND ' : '') . 'organization_startend.start_date <= "' . $dateValue . '"';
                            break;
                    }
                    if (! $joinedStartEnd) {
                        $join_clause .= ' LEFT JOIN `' . $this->_joinTable . '` ON `' . $this->_table . '`.id = `' . $this->_joinTable . '`.parent_id ';
                        //$group_clause = ' GROUP BY `' . $this->_table . '`.id ';
                        $joinedStartEnd = true;
                    }
                } else if (strpos($filterName, 'age') !== false && $value) {
                    $ageDate = date('Y-m-d', strtotime('-' . $value . ' months'));
                    switch($filterName) {
                        case 'age_min':
                            $filter_by_age .= ($filter_by_age ? ' AND ' : '') . 'birth_date <= "' . $ageDate . '"';
                            break;
                        case 'age_max':
                            $filter_by_age .= ($filter_by_age ? ' AND ' : '') . 'birth_date >= "' . $ageDate . '"';
                            break;
                        default:
                    }
                    if (!$joinedChildren) {
                        $join_clause .= ' LEFT JOIN `children` c ON `' . $this->_table . '`.id = `c`.parent_id ';
                        $joinedChildren = true;
                    }
                } else if ($filterName == 'school_district' && $value) {
                    // Join school districts
                    $join_clause .= ' INNER JOIN `school_district_zipcodes` zc ON `' . $this->_table . '`.zip = `zc`.zip_code ';
                    $filter_by_school_district = ' zc.district_id = "' . mysql_real_escape_string($value) . '"';
                } else if (($filterName == 'city') && $value) {
                    if (is_array($value)) {
                        $citySql = '';
                        foreach ($value as $cityName) {
                            $citySql .= ($citySql ? ' OR ' : '') . '`' . mysql_real_escape_string($filterName) . '` LIKE "%' . $cityName . '%"';
                        }
                        $filter_by_city .= $citySql;
                    } else {
                        $filter_by_city .= '`' . mysql_real_escape_string($filterName) . '` LIKE "%' . mysql_real_escape_string($value) . '%"';
                    }
                } else if (($filterName == 'county') && $value) {
                    if (is_array($value)) {
                        $countySql = '';
                        foreach ($value as $countyName) {
                            $countySql .= ($countySql ? ' OR ' : '') . '`' . mysql_real_escape_string($filterName) . '` LIKE "%' . $countyName . '%"';
                        }
                        $filter_by_county .= $countySql;
                    } else {
                        $filter_by_county .= '`' . mysql_real_escape_string($filterName) . '` LIKE "%' . mysql_real_escape_string($value) . '%"';
                    }
                    //$group_clause = ' GROUP BY `' . $this->_table . '`.id ';
                } else if (($filterName == 'region_id') && $value) {
                    //$filter_by_region = true;
                    /*$regionCounties = new RegionCounties($value);
                    $countiesList = $regionCounties->getList();
                    $countyNames = [];
                    foreach ($countiesList as $result) {
                        $countyNames[] = '"' . mysql_real_escape_string($result['county']) . '"';
                    }
                    $filter_by_counties = $countyNames;*/
                    $filter_by .= ($filter_by ? ' AND ' : '')
                        . ' organizations.region_id = "' . mysql_real_escape_string(strtolower($value)) . '"';
                }
				else if (($filterName == 'organization_type_id') && $value) {
							
					$filter_by .= ($filter_by ? ' AND ' : '')
								. 'organization_type_id = "' . mysql_real_escape_string(strtolower($value)) . '"';
						}
				else if (($filterName == 'event_type_id') && $value) {
                    $join_clause .= ' LEFT JOIN `events` ev ON `' . $this->_table . '`.id = `ev`.organization_sites_id ';
                    if(is_array($value)) {
                        $filter_by .= ($filter_by ? ' AND ' : '') .'(';
                        $i = 1;
                        foreach($value as $val) {
                            if($i > 1)
                                $filter_by .= ' OR ';
                            $filter_by .= 'ev.event_type_id = "' . mysql_real_escape_string(strtolower($val)) . '"';
                            $i++;
                        }
                        $filter_by .= ')';
                    } else {
                        $filter_by .= ($filter_by ? ' AND ' : '')
                        . 'ev.event_type_id = "' . mysql_real_escape_string(strtolower($value)) . '"';
                    }
                    $filter_by .= ($filter_by ? ' AND ' : '')
                        . 'ev.event_id != ""';
                } else if (($filterName == 'type_of_contact_id') && $value) {
                    $join_clause .= ' LEFT JOIN `contacts` ct ON `' . $this->_table . '`.id = `ct`.organization_sites_id ';
                    $filter_by .= ($filter_by ? ' AND ' : '')
                        . 'ct.type_of_contact_id = "' . mysql_real_escape_string(strtolower($value)) . '"';
                } else if (($filterName == 'zip') && $value) {
                    if (is_array($value)) {
                        $zipSql = '';
                        foreach ($value as $zipName) {
                            $zipSql .= ($zipSql ? ' OR ' : '') . '`' . mysql_real_escape_string($filterName) . '` LIKE "%' . $zipName . '%"';
                        }
                        $filter_by_zip .= $zipSql;
                    } else {
                        $filter_by_zip .= '`' . mysql_real_escape_string($filterName) . '` LIKE "%' . mysql_real_escape_string($value) . '%"';
                    }
                } else if (($filterName == 'child_first_name' && $value) || ($filterName == 'child_last_name' && $value)) {
                    $filter_by_child .= ' AND ';
                    if($filterName == 'child_first_name' && $value) {
                        $filter_by_child .= 'c.first LIKE "%'.mysql_real_escape_string($value).'%" ' ;
                    }
                    if(($filterName == 'child_first_name' && $value) && ($filterName == 'child_last_name' && $value)) {
                        $filter_by_child .= ' AND ';
                    }
                    if($filterName == 'child_last_name' && $value) {
                        $filter_by_child .= ' c.last LIKE "%'.mysql_real_escape_string($value).'%"';
                    }
                    $filter_by_child .= ' ';
                    if (!$joinedChildren) {
                        $join_clause .= ' LEFT JOIN `children` c ON `' . $this->_table . '`.id = `c`.parent_id ';
                        $joinedChildren = true;
                    }
                } else if ($filterName == 'child_id' && $value) {
                    $filter_by_child_id = ' AND c.id = "' . mysql_real_escape_string($value) . '"';
                    if (!$joinedChildren) {
                        $join_clause .= ' LEFT JOIN `children` c ON `' . $this->_table . '`.id = `c`.parent_id ';
                        $joinedChildren = true;
                    }
                } else if ($filterName == 'issue' && $value) {
                    // Join school districts
                    $join_clause .= ' LEFT JOIN `family_referrals` fr ON `' . $this->_table . '`.id = `fr`.family_id ';
                    if (!$joinedChildren) {
                        $join_clause .= ' LEFT JOIN `children` c ON `' . $this->_table . '`.id = `c`.parent_id ';
                        $joinedChildren = true;
                    }
                    $join_clause .= ' LEFT JOIN `child_referrals` cr ON `c`.id = `cr`.child_id ';
                    $filter_by_issue = ' fr.issue_id = "' . mysql_real_escape_string($value) . '" OR cr.issue_id = "' . mysql_real_escape_string($value) . '"';
                    //$group_clause = ' GROUP BY `' . $this->_table . '`.id ';
                } else if ($filterName == 'quick' && $value) {
                    $values = explode(':',$value);
                    if(isset($values[1])){
                        $filter_by .= ($filter_by ? ' AND ' : '')
                        . '(LOWER(orgn.name) LIKE "%' . mysql_real_escape_string(strtolower($values[0])) . '%" and LOWER(sites.name) LIKE "%' . mysql_real_escape_string(strtolower(trim($values[1]))) . '%" )';
                    }
                    else{
                    $filter_by .= ($filter_by ? ' AND ' : '')
                        . '(LOWER(orgn.name) LIKE "%' . mysql_real_escape_string(strtolower($value)) . '%" OR LOWER(sites.name) LIKE "%' . mysql_real_escape_string(strtolower($value)) . '%" OR `os`.id = "'.mysql_real_escape_string(strtolower($value)).'")';
                    }
                } else if (strpos($filterName, 'family_code') === 0 && $value) {
                    $having_clause = ' HAVING family_code LIKE "%' . mysql_real_escape_string($value) . '%"';
                } else if($filterName == 'primary_phone' && !empty($value)) {
                    $value = trim($value);
                    //strip the spaces, dashes, (), etc before search
                    $filter_by_phone .= ' AND (primary_phone LIKE "%'.mysql_real_escape_string($value).'%"';
                    $value = str_replace('(', '', $value);
                    $value = str_replace(')', '', $value);
                    $filter_by_phone .= ' OR primary_phone LIKE "%'.mysql_real_escape_string($value).'%"';
                    $value = str_replace('{', '', $value);
                    $value = str_replace('}', '', $value);
                    $filter_by_phone .= ' OR primary_phone LIKE "%'.mysql_real_escape_string($value).'%"';
                    $value = str_replace('[', '', $value);
                    $value = str_replace(']', '', $value);
                    $filter_by_phone .= ' OR primary_phone LIKE "%'.mysql_real_escape_string($value).'%"';
                    $value = str_replace('|', '', $value);
                    $filter_by_phone .= ' OR primary_phone LIKE "%'.mysql_real_escape_string($value).'%"';
                    $value = str_replace('-', '', $value);
                    $filter_by_phone .= ' OR primary_phone LIKE "%'.mysql_real_escape_string($value).'%"';
                    $filter_by_phone .= ' OR REPLACE(primary_phone, "-", "") LIKE "%'.mysql_real_escape_string($value).'%"';
                    
                    $filter_by_phone .= ') ';
                }
                else if($filterName == 'family_heard_id' && !empty($value)) {
                    $filter_by_heard_id = " AND family_heard_id='$value' ";
                } else if ($filterName == 'success_story' && !empty($value)) { //261016
                    if($value == 'No') {
                        $filter_by .= ($filter_by ? ' AND ' : '')
                         . ' (success_story = "' . mysql_real_escape_string($value) . '" || success_story = "")';
                     } else {
                        $filter_by .= ($filter_by ? ' AND ' : '')
                         . ' success_story = "' . mysql_real_escape_string(($value)) . '"';
                     }
                }
                 else if ($filterName == 'provider_or_clinic' && !empty($value)) { //261016
                    $join_clause .= ' JOIN `family_provider` fp ON `' . $this->_table . '`.id = `fp`.family_id ';
                    $join_clause .= ' JOIN `providers` ps ON `fp`.provider_id = `ps`.id ';
                    //extract first name, last name and employer name from input
                    $extract   = explode(', ', $value);
                    $extract2  = isset($extract[1]) ? explode(' - ', $extract[1]) : '';
                    $firstName = isset($extract[0]) ? $extract[0] : '';
                    $lastName  = isset($extract2[0]) ? $extract2[0] : '';
                    $employer  = isset($extract2[1]) ? $extract2[1] : '';
                    $filter_by .= ($filter_by ? ' AND ' : '')
                        . ' (ps.first_name like "%' . mysql_real_escape_string($firstName) . '%" && ps.last_name like "%' . mysql_real_escape_string($lastName) . '%" && ps.employer like "%' . mysql_real_escape_string($employer) . '%")';
                }
				else if($filterName == 'contact_name' && !empty($value) )
				{
					$join_clause .=' LEFT JOIN contacts oc ON os.id = oc.organization_sites_id ';
					$join_selects .=' ,oc.first,oc.last';
					$filter_by .= ($filter_by ? ' AND ' : '')
					. ' (oc. first like "%' . mysql_real_escape_string($value) . '%" OR oc. last like "%' . mysql_real_escape_string($value) . '%")' ;
					
				}
					
            }
        }
        $sql = 'SELECT `' . mysql_real_escape_string($this->_table) . '`.*, orgn.name as organization_name, `os`.`organization_site_id` as organization_site_id, 
            sites.name as site, GROUP_CONCAT(service.service_id) service_terms, os.id as organization_sites_id, os.status';
        /*if($joinedStartEnd)
            $sql .= ', DATE_FORMAT(startend.start_date, "%m/%d/%Y") formatted_start_date, DATE_FORMAT(startend.end_date, "%m/%d/%Y") formatted_end_date ';
        */
		
		$join_clause .= ' LEFT JOIN `users`  ON `' . $this->_table . '`.`hmg_worker` = `users`.id ';
        $join_selects .=' , users.hmg_worker as hmgworker ';
        $sql .= $join_selects
                . ' FROM organization_sites os LEFT JOIN `' . mysql_real_escape_string($this->_table) . '` ON os.organization_id=`' .            
                        mysql_real_escape_string($this->_table) . '`.id LEFT JOIN settings orgn ON orgn.id=`'.$this->_table.'`.organization_name_id 
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
                    /******************
                    //For Notes Functionality
                    $qd = mysql_query( 'select Status,Notes,Partnership from import_clinics where ClinicName="'.$row['organization_name'].'" and Site="'.$row['site'].'"') or die(mysql_error());
                    $statuss = mysql_fetch_array($qd);
                    
                    $status    = trim($statuss['Status']);                
                    $status_id = 0;
                    //check organization_status in settings
                    $row_status = array();
                    if(!empty($status)) {
                        $check_status = 'Select * from settings where LOWER(name)="'.strtolower(trim($status)).'" 
                            AND type="organization_status"';
                        $rs_status    = mysql_query($check_status) or die($check_status);
                        $row_status   = mysql_fetch_array($rs_status, MYSQL_ASSOC);
                    }
                    if(!empty($row_status)) {
                        $status_id = $row_status['id'];
                    } elseif(!empty($status)) {
                        //new record
                        $sql_up  = "Insert into settings set name='".$status."', type='organization_status', disabled='0'";
                        $update  = mysql_query($sql_up) or die($sql_up);
                        $status_id = mysql_insert_id();
                    }
                    mysql_query("update organization_sites set status='".$status_id."' where id=".$row['organization_sites_id']);

                    $status    = trim($statuss['Notes']);                
                    if( empty($status) ){
                        mysql_query("delete from  organization_notes where organization_sites_id=".$row['organization_sites_id']);
                    }
                    $partnership_level    = trim($statuss['Partnership']);
                    $partnership_level_id = 0;
                    $row_partn = array();
                    //check partnership_level in settings
                    if(!empty($partnership_level)) {
                        $check_partn = 'Select * from settings where LOWER(name)="'.strtolower($partnership_level).'" AND type="partnership_level"';
                        $rs_partn  = mysql_query($check_partn) or die($check_partn);
                        $row_partn = mysql_fetch_array($rs_partn, MYSQL_ASSOC);
                    }
                    if(!empty($row_partn)) {
                        $partnership_level_id = $row_partn['id'];
                    } elseif(!empty($partnership_level)) { //add new partnership_level
                        $sql_up  = "Insert into settings set name='".$partnership_level."', type='partnership_level', disabled='0'";
                        $update  = mysql_query($sql_up) or die($sql_up);
                        $partnership_level_id = mysql_insert_id();
                    }
                    mysql_query("update organizations set partnership_level_id = '".$partnership_level_id."' where id=".$row['id']);
                    ************************/

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

    public function getOrganizationPositionById($id)
    {
        $sql = '
            SELECT
                *
            FROM
                (
                SELECT
                    @rowcount:=@rowcount+1 "pos", f.id
                FROM
                    (SELECT org.* FROM `' . $this->_table . '` org JOIN settings st ON st.id=org.organization_name_id ORDER BY st.name) f,
                    (SELECT @rowcount:=-1) r
                ) f2
            WHERE f2.id = "' . mysql_real_escape_string($id) . '"';
            $rs = mysql_query($sql);
            $row = mysql_fetch_array($rs, MYSQL_ASSOC);
        if (isset($row['pos'])) {
            return $row['pos'];
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
