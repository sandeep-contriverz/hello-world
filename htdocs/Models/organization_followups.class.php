<?php
namespace Hmg\Models;

use Hmg\Models\RegionCounties;
use Hmg\Models\ContactFollowUp;
use Hmg\Models\Setting;

class OrganizationFollowUpss
{
    private $_table = 'organizations';
    private $_sorts = array('follow_up_date'=>'ASC');
    public $sorts = array();
    
    private $_start = 0;
    private $_limit = 50;
    private $_search = null;
    private $_filters = null;
    private $_mysql_error = null;
	
	private $_settingJoins = array(
        //'event_type',
        //'referred_to',
        'organization_name',
        'organization_type',
        'partnership_level',
        'region',
        'mode_of_contact',
        //'organization_status',
    );
    private $_settingTable = 'settings';
    
	
	private $_joinTable = 'organization_startend';
    public $count_records = false;
	
	
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
        $filter_by_1 = '';
        $filter_by_2 = '';
        $filter_by_date = '';
        $filter_by_region = '';
		
		$order_by                  = '';
        $filter_by                 = '';
       
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
        $join_clause               = '';
        $join_selects              = '';
        $having_clause             = '';
        $group_clause              = '';
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
                         . ' ON ' . mysql_real_escape_string($key) . '_id = ' . mysql_real_escape_string($key) . '.id';
                }
            }
        }
		
        if (is_array($this->_sorts)) {
            $order_by_fields = '';
            $concat = false;
            foreach ($this->_sorts as $field => $dir) {
                
				if($field === 'oragnization_name' || $field === 'organization')
				{
				    $sortOrder = mysql_real_escape_string($dir);
                    $order_by_fields .= 'org_name ' . $sortOrder;
				}
				
				else if($field === 'hmg_worker')
				{
				    $sortOrder = mysql_real_escape_string($dir);
                    $order_by_fields .= 'users.hmg_worker ' . $sortOrder;
				}
								
				else if($field === 'referred_to')
				{
				    $sortOrder = mysql_real_escape_string($dir);
					
                   $order_by_fields .= ' referral_name_new '. $sortOrder.' , site_name_2 '.$sortOrder;
				}				
                else if($field === 'follow-up_date')
				{
				    $sortOrder = mysql_real_escape_string($dir);
					
                   $order_by_fields .= 'follow_up_date ' .  $sortOrder;
				}

                else if($field === 'contact_name')
				{
				    $sortOrder = mysql_real_escape_string($dir);
					
                   $order_by_fields .= 'ct_name ' .  $sortOrder;
				}
				
				else 
				{
                    $order_by_fields .= ($concat ? ', ' : '') . mysql_real_escape_string($field) . ' ' . mysql_real_escape_string($dir);
                }
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
                        . 'LOWER(rs.name) LIKE "%' . mysql_real_escape_string(strtolower($value)).'%" OR LOWER(organization_name.name) LIKE "%' . mysql_real_escape_string(strtolower($value)) . '%"';
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
	

                $sql = '
           SELECT "organization" type,
                   organization_follow_up.organization_sites_id ,
region.id region_id, 
/*os.id,*/
organizations.id, 
os.status, 
organizations.organization_name_id, 
/*organization_follow_up.hmg_worker, */
                   users.hmg_worker,
organization_follow_up.referral_date, 
organization_follow_up.notes, 
referred_to_id,referred_to_type,  
rs.name referred_to, 
service_id, 
s.name service, 
organization_follow_up.notes, 
t.name follow_up_task, 
org.name org_name, 
sites.name as site, 
NULL as   ct_name,
t.name, 
organizations.primary_phone, 
organizations.fax, 
CONCAT(
IF(
organizations.primary_phone, "P", 
"Z"
), 
IF(organizations.fax, "F", "Z")
) contact, 
follow_up_date, 
DATE_FORMAT(follow_up_date, "%m/%d/%Y") follow_up_date_formatted, 
done,
result'.$join_selects.',
referred_to.name as org_name_2, 
referred_to_site.name as site_name_2 ,
organization_follow_up.id fid, IF(referred_to_type="info", rs.name, referred_to.name) referral_name_new  
               FROM organization_follow_up';

$sql .=   ' 
                   LEFT JOIN organization_sites os ON os.id=organization_follow_up.organization_sites_id
                   LEFT JOIN organizations ON os.organization_id = organizations.id                
                   LEFT JOIN `contacts` ct ON organization_follow_up.organization_sites_id = `ct`.organization_sites_id
               ';
$sql .= $join_clause;

$sql .=   ' LEFT JOIN settings rs ON organization_follow_up.referred_to_id = rs.id
               LEFT JOIN settings s ON organization_follow_up.service_id = s.id
LEFT JOIN settings org ON organizations.organization_name_id = org.id
LEFT JOIN settings t ON organization_follow_up.follow_up_task_id = t.id
               LEFT JOIN `settings` status ON status = `status`.id
               LEFT JOIN settings sites ON os.organization_site_id = sites.id
LEFT JOIN organization_sites os2 ON os2.id = organization_follow_up.referred_to_id 
LEFT JOIN organizations o2 ON os2.organization_id = o2.id 
LEFT JOIN `settings` referred_to_site ON os2.organization_site_id = referred_to_site.id 
LEFT JOIN settings referred_to on referred_to.id = o2.organization_name_id
LEFT JOIN users ON users.id=organization_follow_up.hmg_worker
WHERE follow_up_date != "0000-00-00"'; 
                
          $sql .= 	($filter_by_1 ? ' AND (' . $filter_by_1 . ')' : '')
                
                . ($filter_by_date ? ' AND (' . $filter_by_date . ')' : '')
				. ($filter_by_region ? ' AND county IN (' . implode(', ', $filter_by_counties) . ')' : '') 
                . ($filter_by_city ? ' AND (' . $filter_by_city . ')' : '')
				. ($filter_by_county ? ' AND (' . $filter_by_county . ')' : '')
                . ($filter_by_2 ? ' AND (' . $filter_by_2 . ')' : '')
				. ($filter_by ? ' AND (' . $filter_by . ')' : '')
			
                . ($filter_by_region ? ' AND county IN (' . implode(', ', $filter_by_counties) . ')' : '')
				;
          $sql .= ' Group By fid ';
                 $sql .= $order_by;
				//$sql .= ' Group By organization_name_id '. $order_by;
			
        if ($addLimit) {
            //echo 'Start: ' . $this->_start . ' Limit: ' . $this->_limit;
            $sql .= (is_numeric($this->_start) && $this->_limit ? ' LIMIT ' . $this->_start . ', ' . $this->_limit : ($this->_limit ? ' LIMIT 0, 50' : ''));
        }
       
       return $sql;
    }

    public function getList($merge=false )
    {

        if($merge) { 
        $contactFollowUps = new ContactFollowUp;

        $contactFollowUps->setFilter($this->_filters);
        $contactFollowUps->set('_sorts', $this->_sorts);
        
        $crecs = $contactFollowUps->getList();

        
        $sql = $this->buildQuery(false);        
		$rows = array(); $recs = array();
        $rs = mysql_query($sql) or die(mysql_error() . $sql);
         while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) 
                $rows[] = $row;
         if(!empty($rows) && !empty($crecs))   
             $recs = array_merge($rows , $crecs);
         elseif (!empty($rows))
             $recs = $rows;
         elseif (!empty($crecs))
             $recs = $crecs;
          
         
         foreach($this->_sorts as $k=>$v){
            $field = $k;
            $order = $v;
            break;
         }
         if(empty($this->_sorts))
             aasort($recs , 'org_name');
         elseif($field == 'organization')
             aasort($recs , 'org_name',$order);
         elseif($field == 'contact_name')
             aasort($recs , 'ct_name',$order);
         elseif($field == 'follow_up_date')
             aasort($recs , 'follow_up_date',$order);
         elseif($field == 'service')
             aasort($recs , 'service',$order);
         else 
             aasort($recs , $field,$order);

         
         return $recs;



        }
        
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

    public function getCount($merge=false)
    {
       $contactFollowUps = new ContactFollowUp;
       $cnum = 0;
        if($merge)

        $cnum = $contactFollowUps->getCount();

 

        $sql = $this->buildQuery();
        $rs = mysql_query($sql) or die(mysql_error() . $sql);
        return mysql_num_rows($rs)+$cnum;
    }




}


 function aasort (&$array, $key, $type = 'asc') {
    $setting = new Setting();

    $sorter=array();
    $ret=array();
    reset($array);
    foreach ($array as $ii => $va) {        
        $sorter[$ii]=$va[$key];
    }

    if($type == 'desc') {
       arsort($sorter);
    } else {
       asort($sorter);
    }
    
    foreach ($sorter as $ii => $va) {
       $ret[$ii]=$array[$ii];
    }
    $array=$ret;
}

 function cmp($a, $b)
{
    return strcmp($a["org_name_2"], $b["org_name_2"]) ;
}
