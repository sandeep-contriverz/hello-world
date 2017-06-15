<?php

namespace Hmg\Models;

use Hmg\Models\RegionCounties;

class Families
{
    private $_table = 'families';
    private $_settingJoins = array(
        'relationship_1',
        'relationship_2',
        'language',
        'who_called',
        'family_heard',
        'call_reason',
        'race',
        'ethnicity'
    );
    private $_settingTable = 'settings';
    private $_joinTable = 'startend';
    private $_sorts = array('last_name_1' => 'ASC', 'first_name_1' => 'ASC');
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

    private function buildQuery($addLimit = true, $getNextRecord = false, $currentId = null, $required_dates = false)
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
        $group_clause              = '';
        $joinedStartEnd            = false;
        $joinedChildren            = false;
        $joindedUsers              = false;
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
                

                                if($field == 'hmg_worker') {
                                 $field = 'users.hmg_worker';
                                }

                                if($field == 'best_time' )
                                    $field = 'best_times';
                                
                                if($field == 'contact_type' )
                                    $field = 'contact_phone,contact_email,contact_text';
           
                $order_by_fields .= ($concat ? ', ' : '') . mysql_real_escape_string($field) . ' ' . mysql_real_escape_string($dir);
                $concat = true;
            }
            $order_by = ($concat ? ' ORDER BY ' . $order_by_fields : '');
        }
        if (is_array($this->_filters)) {
            //echo '<pre>'; var_dump($this->_filters); exit;
           
            foreach ($this->_filters as $filterName => $value) {
                $is_quick_enable = true;
                if(!is_array($value)) {
                    $value = trim($value);
                }
                //echo $filterName . '=>' . $value . '<br />';
                /** 301116 If any text box filter is set then unset quick filter **/
                if (($filterName == 'child_first_name' && $value) 
                        || ($filterName == 'child_last_name' && $value)
                        || ($filterName == 'first_name_1' && $value)
                        || ($filterName == 'last_name_1' && $value)
                        || ($filterName == 'family_code' && $value)
                        || ($filterName == 'child_id' && $value)
                        || ($filterName == 'primary_phone' && $value)
                        || ($filterName == 'email' && $value)
                        || ($filterName == 'provider_or_clinic' && $value)
                    ) {
                    $is_quick_enable = false;
                    $_SESSION['families']['filters']['quick'] = '';
                    unset($this->_filters['quick']);
                }
                if (!strpos($filterName, 'date')
                    && strpos($filterName, 'age')         !== 0
                    && $filterName                        != 'school_district'
                    && $filterName                        != 'city'
                    && $filterName                        != 'zip'
                    && $filterName                        != 'county'
                    && $filterName                        != 'issue'
                    && $filterName                        != 'family_heard_id'
                    && strpos($filterName, 'status')      !== 0
                    && strpos($filterName, 'hmg_worker')  !== 0
                    && strpos($filterName, 'family_code') !== 0
                    && strpos($filterName, 'child_first_name')  !== 0
                    && strpos($filterName, 'child_last_name')  !== 0
                    && strpos($filterName, 'child_id')    !== 0
                    && strpos($filterName, 'region_id')   !== 0
                    && strpos($filterName, 'primary_phone')   !== 0
                    && strpos($filterName, 'success_story')   !== 0
                    && strpos($filterName, 'provider_or_clinic') !== 0
                    && $filterName != 'quick') {
                    if ($this->_search || $value) {
                        if($filterName == 'email') {
                            $filterName = 'families.email';
                        }
                        if($filterName == 'zip') {
                            $filterName = 'families.zip';
                        }
                        $filter_by .= ($filter_by ? ' AND ' : '')
                        . (strpos($filterName, ',') ? 'LOWER(CONCAT_WS(" ",'  . $filterName . '))' : mysql_real_escape_string($filterName))
                        . ' LIKE "%' . strtolower(mysql_real_escape_string(($this->_search ? $this->_search : $value))) . '%"';
                    }
                } else if ($filterName == 'status' && $value) {
                    $filter_by .= ($filter_by ? ' AND ' : '')
                     . ' families.status = "' . strtolower(mysql_real_escape_string(($this->_search ? $this->_search : $value))) . '"';
                } else if ($filterName == 'hmg_worker' && $value) {
                    $filter_by .= ($filter_by ? ' AND ' : '')
                     . ' users.hmg_worker LIKE "%' . strtolower(mysql_real_escape_string(($this->_search ? $this->_search : $value))) . '%"';

                    
             
                                 
                } else if (strpos($filterName, 'date') && $value) {
                    $dateValue = date('Y-m-d', strtotime(str_replace('-', '/', $value)));
                    switch($filterName) {
                        case 'start_date':
                            $filter_by_date .= ($filter_by_date ? ' AND ' : '') . 'start_date >= "' . $dateValue . '"';
                            break;
                        case 'end_date':
                            $filter_by_date .= ($filter_by_date ? ' AND ' : '') . 'start_date <= "' . $dateValue . '"';
                            break;
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
                            $citySql .= ($citySql ? ' OR ' : '') . 'families.`' . mysql_real_escape_string($filterName) . '` LIKE "%' . $cityName . '%"';
                        }
                        $filter_by_city .= $citySql;
                    } else {
                        $filter_by_city .= 'families.`' . mysql_real_escape_string($filterName) . '` LIKE "%' . mysql_real_escape_string($value) . '%"';
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
                    $group_clause = ' GROUP BY `' . $this->_table . '`.id ';
                } else if (($filterName == 'region_id') && $value) {
                    $filter_by_region = true;
                    $regionCounties = new RegionCounties($value);
                    $countiesList = $regionCounties->getList();
                    $countyNames = [];
                    foreach ($countiesList as $result) {
                        $countyNames[] = '"' . mysql_real_escape_string($result['county']) . '"';
                    }
                    $filter_by_counties = $countyNames;
                } else if (($filterName == 'zip') && $value) {
                    if (is_array($value)) {
                        $zipSql = '';
                        foreach ($value as $zipName) {
                            $zipSql .= ($zipSql ? ' OR ' : '') . '`families`.`' . mysql_real_escape_string($filterName) . '` LIKE "%' . $zipName . '%"';
                        }
                        $filter_by_zip .= $zipSql;
                    } else {
                        $filter_by_zip .= '`families`.`' . mysql_real_escape_string($filterName) . '` LIKE "%' . mysql_real_escape_string($value) . '%"';
                    }
                } else if (($filterName == 'child_first_name' && $value) || ($filterName == 'child_last_name' && $value)) {
                    //$filter_by_child = ' AND CONCAT(c.first, c.last) LIKE "%' . mysql_real_escape_string($value) . '%"';
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
                    $group_clause = ' GROUP BY `' . $this->_table . '`.id ';
                } else if ($filterName == 'quick' && $is_quick_enable) {
                    $filter_by .= ($filter_by ? ' AND ' : '')
                        . 'LOWER(CONCAT_WS(" ", first_name_1, last_name_1, first_name_2, last_name_2,
                        CONCAT(LEFT(first_name_1, 1), LEFT(last_name_1, 1), CAST(`' . $this->_table . '`.id AS CHAR)))) LIKE "%' . mysql_real_escape_string(strtolower($value)) . '%"';
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
                    
                    /*$filter_by_phone .= ' OR REPLACE(primary_phone, "(", "") LIKE "%'.mysql_real_escape_string($value).'%"';
                    $filter_by_phone .= ' OR REPLACE(primary_phone, ")", "") LIKE "%'.mysql_real_escape_string($value).'%"';
                    $filter_by_phone .= ' OR REPLACE(primary_phone, "{", "") LIKE "%'.mysql_real_escape_string($value).'%"';
                    $filter_by_phone .= ' OR REPLACE(primary_phone, "}", "") LIKE "%'.mysql_real_escape_string($value).'%"';
                    $filter_by_phone .= ' OR REPLACE(primary_phone, "-", "") LIKE "%'.mysql_real_escape_string($value).'%"';
                    */
                    $filter_by_phone .= ') ';
                }
                else if($filterName == 'family_heard_id' && !empty($value)) {
                    $filter_by_heard_id .= " AND family_heard_id='$value' ";
                }
                else if($filterName == 'how_heard_details_id' && !empty($value)) {
                    $filter_by_heard_id .= " AND how_heard_details_id='$value' ";
                }
                else if($filterName == 'point_of_entry' && !empty($value)) {
                    $filter_by .= ($filter_by ? ' AND ' : '')
                         . ' point_of_entry = "' . mysql_real_escape_string(($value)) . '"';
                }
                else if ($filterName == 'success_story' && !empty($value)) { //261016
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
                    $join_clause .= ' JOIN `contacts` ps ON `fp`.contact_id = `ps`.id ';
                    //extract first name, last name and employer name from input
                    $extract   = explode(' - ', $value);
                    $extract2  = isset($extract[0]) ? explode(' ', $extract[0]) : '';
                    //$firstName = isset($extract[0]) ? $extract[0] : '';
                    $firstName  = isset($extract2[0]) ? $extract2[0] : '';
                    $lastName  = isset($extract2[1]) ? $extract2[1] : '';
                    $filter_by .= ($filter_by ? ' AND ' : '')
                        . ' (ps.first like "%' . mysql_real_escape_string($firstName) . '%" && ps.last like "%' . mysql_real_escape_string($lastName) . '%" )';
                }
            }
            
        }
        //if (!$joinedStartEnd && $required_dates) {
            $join_clause .= ' LEFT JOIN `' . $this->_joinTable . '` ON `' . $this->_table . '`.id = `' . $this->_joinTable . '`.parent_id ';
            $group_clause = ' GROUP BY `' . $this->_table . '`.id ';
            $joinedStartEnd = true;
        //}
        $sql = 'SELECT `' . mysql_real_escape_string($this->_table) . '`.*,'
                . ' CONCAT(LEFT(first_name_1, 1), LEFT(last_name_1, 1), `' . $this->_table . '`.id) family_code,'
                . ' CONCAT(last_name_1, ", ", first_name_1) parent_name ';
        if($joinedStartEnd)
            $sql .= ', DATE_FORMAT(startend.start_date, "%m/%d/%Y") formatted_start_date, DATE_FORMAT(startend.end_date, "%m/%d/%Y") formatted_end_date ';
    $join_clause .= ' LEFT JOIN `users`  ON `' . $this->_table . '`.`hmg_worker` = `users`.id ';
    $join_selects .=' , users.hmg_worker ';
        $sql .= $join_selects
                . ' FROM `' . mysql_real_escape_string($this->_table) . '`'
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
                . (!empty($filter_by_counties) ? ' AND county IN (' . implode(', ', $filter_by_counties) . ')' : '')
                . ($filter_by_phone ? $filter_by_phone : '')
                . ($filter_by_heard_id ? $filter_by_heard_id : '')
                . $group_clause
                . $having_clause
                . $order_by;
        if ($addLimit) {
            //echo 'Start: ' . $this->_start . ' Limit: ' . $this->_limit;
            $sql .= (is_numeric($this->_start) && $this->_limit ? ' LIMIT ' . $this->_start . ', ' . $this->_limit : ($this->_limit ? ' LIMIT 0, 20' : ''));
        }
        //echo $sql . '<br />'; //exit;
        return $sql;
    }

    public function getList($required_dates = false,$tri = true)
    {
        $sql = $this->buildQuery($tri, false, null, $required_dates);
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

    public function getFamilyPositionById($id)
    {
        $sql = '
            SELECT
                *
            FROM
                (
                SELECT
                    @rowcount:=@rowcount+1 "pos", f.id
                FROM
                    (SELECT * FROM `' . $this->_table . '` ORDER BY last_name_1, first_name_1) f,
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
