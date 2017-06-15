<?php

namespace Hmg\Models;

use Hmg\Models\RegionCounties;

class FollowUps
{
    private $_sorts = array('follow_up_date' => 'ASC');
    private $_start = 0;
    private $_limit = 50;
    private $_search = null;
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
        $order_by = '';
        $filter_by_1 = '';
        $filter_by_2 = '';
        $filter_by_date = '';
        $filter_by_region = '';
        if (!empty($_SESSION['user']['region_id'])) {
            $filter_by_region = true;
            $regionCounties = new RegionCounties($_SESSION['user']['region_id']);
            $countiesList = $regionCounties->getList();
            $countyNames = [];
            foreach ($countiesList as $result) {
                $countyNames[] = '"' . mysql_real_escape_string($result['county']) . '"';
            }
            $filter_by_counties = $countyNames;
        }
        if (is_array($this->_sorts)) {
            $order_by_fields = '';
            $concat = false;
            foreach ($this->_sorts as $field => $dir) {

                if ($field === 'best_times_start') {
                    $sortOrder = mysql_real_escape_string($dir);
                    $order_by_fields .= 'bts1 ' . $sortOrder . ', btsam ' . $sortOrder . ', btspm ' . $sortOrder;
                    
                }else if ($field === 'referred_to'){
                    $sortOrder = mysql_real_escape_string($dir);
                    $order_by_fields  .= ' referral_name_new '.$sortOrder;

                }
                else if ($field === 'best_times_end') {
                    $sortOrder = mysql_real_escape_string($dir);
                    $order_by_fields .= 'bte1 ' . $sortOrder . ', bteam ' . $sortOrder . ', btepm ' . $sortOrder;
                } else {
                    $order_by_fields .= ($concat ? ', ' : '') . mysql_real_escape_string($field) . ' ' . mysql_real_escape_string($dir);
                }
                $concat = true;
            }
            $order_by = ($concat ? ' ORDER BY ' . $order_by_fields : '');
        }
        if (is_array($this->_filters)) {
            foreach ($this->_filters as $filterName => $value) {
                if ($filterName == 'hmg_worker' && $value) {
                    $filter_by_1 .= ($filter_by_1 ? ' AND ' : '')
                         . 'u.hmg_worker = "' . mysql_real_escape_string($value) . '"';
                    $filter_by_2 .= ($filter_by_2 ? ' AND ' : '')
                         . 'u.hmg_worker = "' . mysql_real_escape_string($value) . '"';
                }
                if ($filterName == 'language_id' && $value) {
                    $filter_by_1 .= ($filter_by_1 ? ' AND ' : '')
                         . 'settings.id = "' . mysql_real_escape_string($value) . '"';
                    $filter_by_2 .= ($filter_by_2 ? ' AND ' : '')
                         . 'settings.id = "' . mysql_real_escape_string($value) . '"';
                }
                if ($filterName == 'status' && $value) {
                    $setting = new Setting();
                    $filter_by_1 .= ($filter_by_1 ? ' AND ' : '')
                         . 'families.status = "' . mysql_real_escape_string($value) . '"';
                    $filter_by_2 .= ($filter_by_2 ? ' AND ' : '')
                         . 'families.status = "' . mysql_real_escape_string($value) . '"';
                }
                if ($filterName == 'follow_up_task' && $value) {
                    $setting = new Setting();
                    $filter_by_1 .= ($filter_by_1 ? ' AND ' : '')
                         . 'follow_up_task_id = "' . mysql_real_escape_string($value) . '"';
                    $filter_by_2 .= ($filter_by_2 ? ' AND ' : '')
                         . 'follow_up_task_id = "' . mysql_real_escape_string($value) . '"';
                }
                if ($filterName == 'done' && ($value === '1' || $value === '0')) {
                    $filter_by_1 .= ($filter_by_1 ? ' AND ' : '')
                         . 'done = "' . mysql_real_escape_string($value) . '"';
                    $filter_by_2 .= ($filter_by_2 ? ' AND ' : '')
                         . 'done = "' . mysql_real_escape_string($value) . '"';
                }
                if (strpos($filterName, 'date') && $value) {
                    $dateValue = date('Y-m-d', strtotime(str_replace('-', '/', $value)));
                    switch($filterName) {
                        case 'start_date':
                            $filter_by_1 .= ($filter_by_1 ? ' AND ' : '') . 'follow_up_date >= "' . $dateValue . '"';
                            $filter_by_2 .= ($filter_by_2 ? ' AND ' : '') . 'follow_up_date >= "' . $dateValue . '"';
                            break;
                        case 'end_date':
                            $filter_by_1 .= ($filter_by_1 ? ' AND ' : '') . 'follow_up_date <= "' . $dateValue . '"';
                            $filter_by_2 .= ($filter_by_2 ? ' AND ' : '') . 'follow_up_date <= "' . $dateValue . '"';
                            break;
                    }
                }
            }
        }
		
		
	$sql = '
            SELECT "family" AS type,
                    family_id,
                    null child_id,
                    CONCAT(last_name_1, ", ", first_name_1) parent_name,
                    "" child_name,
                    settings.name language,
                    best_times,
                    best_times_days,
                    best_times_start,
                    best_times_end,
                    IF(best_times_start="Any Time", 0, IF(best_times_start REGEXP "AM$", 1, (IF(best_times_start REGEXP "PM$", 2, 3)))) bts1,
                    IF(best_times_start REGEXP "AM$", LPAD(SUBSTRING_INDEX(best_times_start, " ", 1), 5, "0"), 1) btsam,
                    IF(best_times_start REGEXP "PM$", LPAD(SUBSTRING_INDEX(best_times_start, " ", 1), 5, "2"), 1) btspm,
                    IF(best_times_end="Any Time", 0, IF(best_times_end REGEXP "AM$", 1, (IF(best_times_end REGEXP "PM$", 2, 3)))) bte1,
                    IF(best_times_end REGEXP "AM$", LPAD(SUBSTRING_INDEX(best_times_end, " ", 1), 5, "0"), 1) bteam,
                    IF(best_times_end REGEXP "PM$", LPAD(SUBSTRING_INDEX(best_times_end, " ", 1), 5, "2"), 1) btepm,
                    contact_phone,
                    contact_text,
                    contact_email,
                    CONCAT(IF(contact_email,"E","Z"),IF(contact_phone,"P","Z"),IF(contact_text,"T","Z")) contact,
                    u.hmg_worker,
                    referral_date,
                    referred_to_id,referred_to_type,
                    rs.name referred_to,
                    service_id,
                    s.name service,
                    family_follow_up.notes,
                    t.name follow_up_task,
                    follow_up_date,
                    DATE_FORMAT(follow_up_date, "%m/%d/%Y") follow_up_date_formatted,
                    done,
                    result,
					referred1_to.name organization_name,
					sn.name site_name,
					IF(referred_to_type="info", rs.name, referred1_to.name) referral_name_new 
                FROM family_follow_up
                LEFT JOIN families ON family_follow_up.family_id = families.id
                LEFT JOIN settings ON families.language_id = settings.id
                LEFT JOIN settings rs ON family_follow_up.referred_to_id = rs.id
                LEFT JOIN settings s ON family_follow_up.service_id = s.id
                LEFT JOIN settings t ON family_follow_up.follow_up_task_id = t.id
				LEFT JOIN organization_sites os ON referred_to_id = os.id
				LEFT JOIN organizations o ON o.id = os.organization_id  
				LEFT JOIN `settings` referred1_to ON organization_name_id = referred1_to.id 
				LEFT JOIN `settings` referred_to_site ON organization_site_id = referred1_to.id
				LEFT JOIN settings sn on sn.id=os.organization_site_id
				LEFT JOIN users u on u.id=family_follow_up.hmg_worker

                WHERE follow_up_date != "0000-00-00"'
                . ($filter_by_1 ? ' AND (' . $filter_by_1 . ')' : '')
                . ($filter_by_region ? ' AND county IN (' . implode(', ', $filter_by_counties) . ')' : '') . '
                UNION ALL
                SELECT "child" AS type,
                    parent_id family_id,
                    child_id id,
                    CONCAT(last_name_1, ", ", first_name_1) parent_name,
                    CONCAT(children.first, " ", children.last) child_name,
                    settings.name language,
                    best_times,
                    best_times_days,
                    best_times_start,
                    best_times_end,
                    IF(best_times_start="Any Time", 0, IF(best_times_start REGEXP "AM$", 1, (IF(best_times_start REGEXP "PM$", 2, 3)))) bts1,
                    IF(best_times_start REGEXP "AM$", LPAD(SUBSTRING_INDEX(best_times_start, " ", 1), 5, "0"), 1) btsam,
                    IF(best_times_start REGEXP "PM$", LPAD(SUBSTRING_INDEX(best_times_start, " ", 1), 5, "2"), 1) btspm,
                    IF(best_times_end="Any Time", 0, IF(best_times_end REGEXP "AM$", 1, (IF(best_times_end REGEXP "PM$", 2, 3)))) bte1,
                    IF(best_times_end REGEXP "AM$", LPAD(SUBSTRING_INDEX(best_times_end, " ", 1), 5, "0"), 1) bteam,
                    IF(best_times_end REGEXP "PM$", LPAD(SUBSTRING_INDEX(best_times_end, " ", 1), 5, "2"), 1) btepm,
                    contact_phone,
                    contact_text,
                    contact_email,
                    CONCAT(IF(contact_email,"E","Z"),IF(contact_phone,"P","Z"),IF(contact_text,"T","Z")) contact,
                    u.hmg_worker,
                    referral_date,
                    referred_to_id,referred_to_type,
                    rs.name referred_to,
                    service_id,
                    s.name service,
                    child_follow_up.notes,
                    t.name follow_up_task,
                    follow_up_date,
                    DATE_FORMAT(follow_up_date, "%m/%d/%Y") follow_up_date_formatted,
                    done,
                    result,
					referred1_to.name organization_name,
					sn.name site_name,
					IF(referred_to_type="info", rs.name, referred1_to.name) referral_name_new 
                FROM child_follow_up
                LEFT JOIN children ON child_follow_up.child_id = children.id
                LEFT JOIN families ON children.parent_id = families.id
                LEFT JOIN settings ON families.language_id = settings.id
                LEFT JOIN settings rs ON child_follow_up.referred_to_id = rs.id
                LEFT JOIN settings s ON child_follow_up.service_id = s.id
                LEFT JOIN settings t ON child_follow_up.follow_up_task_id = t.id
				LEFT JOIN organization_sites os ON referred_to_id = os.id
				LEFT JOIN organizations o ON o.id = os.organization_id  
				LEFT JOIN `settings` referred1_to ON organization_name_id = referred1_to.id 
				LEFT JOIN `settings` referred_to_site ON organization_site_id = referred1_to.id LEFT JOIN settings sn on sn.id=os.organization_site_id
                LEFT JOIN users u on u.id=child_follow_up.hmg_worker
                WHERE follow_up_date != "0000-00-00" AND parent_id != 0 AND families.id IS NOT null'
                . ($filter_by_2 ? ' AND (' . $filter_by_2 . ')' : '')
                . ($filter_by_region ? ' AND county IN (' . implode(', ', $filter_by_counties) . ')' : '')
                . $order_by;
        if ($addLimit) {
            //echo 'Start: ' . $this->_start . ' Limit: ' . $this->_limit;
            $sql .= (is_numeric($this->_start) && $this->_limit ? ' LIMIT ' . $this->_start . ', ' . $this->_limit : ($this->_limit ? ' LIMIT 0, 50' : ''));
        }
         $sql . "\n";
         
        return $sql;
    }

    public function getList()
    {
		
        $sql = $this->buildQuery();
        //echo $sql;
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
