<?php

namespace Hmg\Models;

class Counts
{
    private $_filters = null;
    private $_table = 'families';
    private $_joinTable = 'startend';
    public $_order_by = '';
    public $_type = '';

    public function __construct($_type = '')
    {
        $this->_type = isset($_REQUEST['type']) ? $_REQUEST['type'] : $_type;
        if (isset($_SESSION['family-drill-sorts']) && is_array($_SESSION['family-drill-sorts'])
            && !empty($_SESSION['family-drill-sorts'])) {
            $order_by_fields = '';
            $concat = false;
            foreach ($_SESSION['family-drill-sorts'] as $field => $dir) {
                if($field == 'primary_contact') {
                    $field = 'first_name_1';
                }
                if($field == 'child' || $field == 'child_name') {
                    $field = 'first';
                }
                if($field == 'language') {
                    $field = 'language';
                }
                if($field == 'hmg_worker') {
                    $field = 'hmg_worker';
                    if($this->_type == 'familyDrill')
                        $field = 'u.'.$field;
                }
                
                $order_by_fields .= ($concat ? ', ' : '') . mysql_real_escape_string($field) . ' ' . mysql_real_escape_string($dir);
                $concat = true;
            }
            $this->_order_by = ($concat ? ' ORDER BY ' . $order_by_fields : '');
        }
    }

    public function set($key, $value)
    {
        $this->$key = $value;
    }

    public function get($key)
    {
        return $this->$key;
    }

    public function getSchoolDistrictZipCodes($district_id)
    {
        $sql = 'SELECT * FROM school_district_zipcodes
                WHERE district_id = "'.mysql_real_escape_string($district_id).'"';
        $rs = mysql_query($sql);
        $zipCodes = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $zipCodes[] = $row['zip_code'];
        }

        return $zipCodes;
    }

    public function getFiltersSql($date_field = 'start_date', $default_table = 'families')
    {
        $filter_by = '';
        $filter_by_contact = '';
        $join_clause = $join_clause_2 = '';
        $join_clause_3 =  '';
        $group_clause = '';
        $filter_by_date = '';
        $joinedStartEnd = false;
        $date_table = '';
       

        //echo '<pre>'; var_dump($this->_filters); echo '</pre>';
        if (is_array($this->_filters)) {
            foreach ($this->_filters as $filterName => $value) {
                if (strpos($filterName, 'date') && $value) {
                    $dateValue = date('Y-m-d', strtotime(str_replace('-', '/', $value)));
                    if ($date_field == 'start_date') {
                        $date_table = 'startend';
                    }
                    switch ($filterName) {
                        case 'start_date':
                            $filter_by_date .= ($filter_by_date ? ' AND ' : '').($date_table == 'startend' ? 'startend.' : '').$date_field.' >= "'.$dateValue.'"';
                            break;
                        case 'end_date':
                            $filter_by_date .= ($filter_by_date ? ' AND ' : '').($date_table == 'startend' ? 'startend.' : '').$date_field.' <= "'.$dateValue.'"';
                            break;

                  
                    }
                    if (!$joinedStartEnd) {
                        $exclude_types = array('familyDrill', 'family', 'callReasonDrill', 'howheardDrill');
                        if(!empty($this->_type) && in_array($this->_type, $exclude_types)){
                            $join_clause .= ' LEFT JOIN startend ON startend.parent_id = families.id ';
                        } else {
                        
                        $join_clause .= '
                        LEFT JOIN (
                            SELECT id, parent_id, min(start_date) start_date, max(end_date) end_date, reason
                            FROM startend
                            GROUP BY parent_id
                        ) startend ON startend.parent_id = families.id';
                        } //ends else
                        //$join_clause .= ' LEFT JOIN `' . $this->_joinTable . '` ON `' . $this->_table . '`.id = `' . $this->_joinTable . '`.parent_id ';
                        $joinedStartEnd = true;
                    }
                    //$group_clause = ' GROUP BY `' . $this->_table . '`.id ';
                } elseif (strpos($filterName, 'city') !== false && is_array($value)) {
                    $cities = implode('","', $value);
                    $filter_by .= ($filter_by ? ' AND ' : '')
                    .'`'.$default_table.'`.`city` IN ("'.$cities.'")';
                } elseif (strpos($filterName, 'county') !== false && is_array($value)) {
                    $counties = implode('","', $value);
                    $filter_by .= ($filter_by ? ' AND ' : '')
                    .'`'.$default_table.'`.`county` IN ("'.$counties.'")';
                } elseif (strpos($filterName, 'zip') !== false && is_array($value)) {
                    $zipRange = implode(',', $value);
                    $filter_by .= ($filter_by ? ' AND ' : '')
                    .'`'.$default_table.'`.`zip` IN ('.$zipRange.')';
                } elseif ($filterName == 'zip' && is_numeric($value)) {
                    $filter_by .= ($filter_by ? ' AND ' : '')
                    .'`'.$default_table.'`.`'.mysql_real_escape_string($filterName).'` = "'.mysql_real_escape_string($value).'"';
                } elseif (strpos($filterName, 'school_district') !== false && $value) {
                    $zipCodes = $this->getSchoolDistrictZipCodes($value);
                    $zipRange = implode(',', $zipCodes);
                    $filter_by .= ($filter_by ? ' AND ' : '')
                    .'`'.$default_table.'`.zip IN ('.$zipRange.')';
                } elseif (strpos($filterName, 'region_id') !== false && $value) {
                    $regionCounties = new RegionCounties($value);
                    $countiesList = $regionCounties->getList();
                    if(!empty($countiesList)) { //021116
                        $countyNames = [];
                        foreach ($countiesList as $result) {
                            $countyNames[] = '"'.mysql_real_escape_string($result['county']).'"';
                        }
                        $filter_by_counties = $countyNames;
                        $filter_by .= ($filter_by ? ' AND ' : '')
                        .'`families`.`county` IN ('.implode(', ', $filter_by_counties).')';
                    } else {
                        $filter_by .= ($filter_by ? ' AND ' : '')
                        .'`families`.`county` IN (1)';
                    }
                } elseif (strpos($filterName, 'provider_or_clinic') !== false && !empty($value)) { //261016
                   
                    $join_clause_2 = ' JOIN `family_provider` fp ON `' . $this->_table . '`.id = `fp`.family_id ';
                    
                    
                    $join_clause_2 .= ' JOIN `providers` ps ON `fp`.provider_id = `ps`.id ';
                    $join_clause_3 .= ' JOIN `providers` ps ON `fp`.provider_id = `ps`.id ';
                    //extract first name, last name and employer name from input
                    $extract   = explode(', ', $value);
                    $extract2  = isset($extract[1]) ? explode(' - ', $extract[1]) : '';
                    $firstName = isset($extract[0]) ? $extract[0] : '';
                    $lastName  = isset($extract2[0]) ? $extract2[0] : '';
                    $employer  = isset($extract2[1]) ? $extract2[1] : '';
                    $filter_by .= ($filter_by ? ' AND ' : '')
                        . ' (ps.first_name like "%' . mysql_real_escape_string($firstName) . '%" && ps.last_name like "%' . mysql_real_escape_string($lastName) . '%" && ps.employer like "%' . mysql_real_escape_string($employer) . '%")';
                }elseif ( strpos($filterName , 'hmg_worker') !== false && !empty($value)) {
                    $filter_by .= ($filter_by ? ' AND ' : '');
                    $filter_by .= " u.hmg_worker = '$value' ";

                    $join_clause_2 .= ' left JOIN users u ON u.id = families.hmg_worker '; 
                    $join_clause_3 .= ' left JOIN users u ON u.id = families.hmg_worker '; 
                }

                elseif ($value) {
                    $filter_by .= ($filter_by ? ' AND ' : '')
                    .'`'.$default_table.'`.`'.mysql_real_escape_string($filterName).'` = "'.mysql_real_escape_string($value).'"';
                }
            }
            $filter_by .= ($filter_by && $filter_by_date ? ' AND ('.$filter_by_date.')' : ($filter_by_date ? $filter_by_date : ''));
        }


        return array(
            'filter_by'     => $filter_by,
            'filter_by_contact' => $filter_by_contact,
            'join_clause'   => $join_clause,
            'join_clause_2' => $join_clause_2,
            'join_clause_3' => $join_clause_3,
            'group_clause'  => $group_clause,
        );
    }

    public function getFiltersSqlOrg($date_field = 'start_date', $default_table = 'organizations')
    {
        $filter_by      = '';
        $join_clause    = '';
        $join_clause_2  = '';
        $group_clause   = '';
        $filter_by_date = '';
        $joinedStartEnd = false;
        $date_table     = '';
       

        
        if (is_array($this->_filters)) {
            foreach ($this->_filters as $filterName => $value) {
                
                if (strpos($filterName, 'date') && $value) {
                    $dateValue = date('Y-m-d', strtotime(str_replace('-', '/', $value)));
                    if ($date_field == 'start_date') {
                        $date_table = 'organization_startend';
                    }
                    switch ($filterName) {
                        case 'start_date':
                            $filter_by_date .= ($filter_by_date ? ' AND ' : '').'organization_startend.'.$date_field.' >= "'.$dateValue.'"';
                            break;
                        case 'end_date':
                            $filter_by_date .= ($filter_by_date ? ' AND ' : '').'organization_startend.'.$date_field.' <= "'.$dateValue.'"';
                            break;

                  
                    }
                    if (!$joinedStartEnd) {                        
                            $join_clause .= ' LEFT JOIN organization_startend ON organization_startend.parent_id = organizations.id ';                        
                            $joinedStartEnd = true;
                    }
                    //$group_clause = ' GROUP BY `' . $this->_table . '`.id ';
                } elseif (strpos($filterName, 'city') !== false && is_array($value)) {
                    
                    $cities = implode('","', $value);
                    $filter_by .= ($filter_by ? ' AND ' : '').'`'.$default_table.'`.`city` IN ("'.$cities.'")';
                    
                } elseif (strpos($filterName, 'county') !== false && is_array($value)) {
                    
                    $counties = implode('","', $value);
                    $filter_by .= ($filter_by ? ' AND ' : '').'`'.$default_table.'`.`county` IN ("'.$counties.'")';
                    
                } elseif (strpos($filterName, 'zip') !== false && is_array($value)) {
                    
                    $zipRange = implode(',', $value);
                    $filter_by .= ($filter_by ? ' AND ' : '').'`'.$default_table.'`.`zip` IN ('.$zipRange.')';
                    
                } elseif ($filterName == 'zip' && is_numeric($value)) {
                    
                    $filter_by .= ($filter_by ? ' AND ' : '').'`'.$default_table.'`.`'.mysql_real_escape_string($filterName).'` = "'.mysql_real_escape_string($value).'"';
                    
                } elseif (strpos($filterName, 'school_district') !== false && $value) {
                    
                    $join_clause .= ' INNER JOIN `school_district_zipcodes` zc ON `' . $default_table . '`.zip = `zc`.zip_code ';
                    $filter_by .= ($filter_by ? ' AND ' : ''). ' zc.district_id = "' . mysql_real_escape_string($value) . '"';
                    
                } elseif (strpos($filterName, 'region_id') !== false && $value) {
                    
                    $filter_by .= ($filter_by ? ' AND ' : ''). 'organizations.region_id = "' . mysql_real_escape_string(strtolower($value)) . '"';
                    
                } elseif ( strpos($filterName , 'hmg_worker') !== false && !empty($value)) {
                    
                    $filter_by .= ($filter_by ? ' AND ' : ''). ' organizations.hmg_worker = "%' . ($value) . '%"';
                }
                elseif ( strpos($filterName , 'status') !== false && !empty($value)) {
                    
                    $filter_by .= ($filter_by ? ' AND ' : ''). ' os.status = "' . ($value) . '"';
                }
                
                

                elseif ($value) {
                    //$filter_by .= ($filter_by ? ' AND ' : '')
                    //.'`'.$default_table.'`.`'.mysql_real_escape_string($filterName).'` = "'.mysql_real_escape_string($value).'"';
                }
            }
            $filter_by .= ($filter_by && $filter_by_date ? ' AND ('.$filter_by_date.')' : ($filter_by_date ? $filter_by_date : ''));
        }


        return array(
            'filter_by'     => $filter_by,
            'join_clause'   => $join_clause,
            'join_clause_2' => $join_clause_2,
            'group_clause'  => $group_clause,
        );
    }

    public function getFiltersSqlEvent($date_field = 'event_date', $default_table = 'organizations')
    {
        $filter_by      = '';
        $join_clause    = '';
        $join_clause_2  = '';
        $group_clause   = '';
        $filter_by_date = '';
        $joinedStartEnd = false;
        $date_table     = '';
       

        
        if (is_array($this->_filters)) {
            foreach ($this->_filters as $filterName => $value) {
                
                if (strpos($filterName, 'date') && $value) {
                    $dateValue = date('Y-m-d', strtotime(str_replace('-', '/', $value)));
                    
                    $date_table = 'events';
                    
                    switch ($filterName) {
                        case 'start_date':
                            $filter_by_date .= ($filter_by_date ? ' AND ' : '').'events.'.$date_field.' >= "'.$dateValue.'"';
                            break;
                        case 'end_date':
                            $filter_by_date .= ($filter_by_date ? ' AND ' : '').'events.'.$date_field.' <= "'.$dateValue.'"';
                            break;

                  
                    }
                    
                } elseif (strpos($filterName, 'city') !== false && is_array($value)) {
                    
                    $cities = implode('","', $value);
                    $filter_by .= ($filter_by ? ' AND ' : '').'`'.$default_table.'`.`city` IN ("'.$cities.'")';
                    
                } elseif (strpos($filterName, 'county') !== false && is_array($value)) {
                    
                    $counties = implode('","', $value);
                    $filter_by .= ($filter_by ? ' AND ' : '').'`'.$default_table.'`.`county` IN ("'.$counties.'")';
                    
                } elseif (strpos($filterName, 'zip') !== false && is_array($value)) {
                    
                    $zipRange = implode(',', $value);
                    $filter_by .= ($filter_by ? ' AND ' : '').'`'.$default_table.'`.`zip` IN ('.$zipRange.')';
                    
                } elseif ($filterName == 'zip' && is_numeric($value)) {
                    
                    $filter_by .= ($filter_by ? ' AND ' : '').'`'.$default_table.'`.`'.mysql_real_escape_string($filterName).'` = "'.mysql_real_escape_string($value).'"';
                    
                } elseif (strpos($filterName, 'school_district') !== false && $value) {
                    
                    $join_clause .= ' INNER JOIN `school_district_zipcodes` zc ON `' . $default_table . '`.zip = `zc`.zip_code ';
                    $filter_by .= ($filter_by ? ' AND ' : ''). ' zc.district_id = "' . mysql_real_escape_string($value) . '"';
                    
                } elseif (strpos($filterName, 'region_id') !== false && $value) {
                    
                    $filter_by .= ($filter_by ? ' AND ' : ''). 'organizations.region_id = "' . mysql_real_escape_string(strtolower($value)) . '"';
                    
                } elseif ( strpos($filterName , 'hmg_worker') !== false && !empty($value)) {
                    
                    $filter_by .= ($filter_by ? ' AND ' : ''). ' organizations.hmg_worker = "%' . ($value) . '%"';
                }
                elseif ( strpos($filterName , 'status') !== false && !empty($value)) {
                    
                    $filter_by .= ($filter_by ? ' AND ' : ''). ' os.status = "' . ($value) . '"';
                }
                
                

                elseif ($value) {
                    //$filter_by .= ($filter_by ? ' AND ' : '')
                    //.'`'.$default_table.'`.`'.mysql_real_escape_string($filterName).'` = "'.mysql_real_escape_string($value).'"';
                }
            }
            $filter_by .= ($filter_by && $filter_by_date ? ' AND ('.$filter_by_date.')' : ($filter_by_date ? $filter_by_date : ''));
        }


        return array(
            'filter_by'     => $filter_by,
            'join_clause'   => $join_clause,
            'join_clause_2' => $join_clause_2,
            'group_clause'  => $group_clause,
        );
    }

   
    public function getFollowupsCounts()
    {
        $filtersSql = $this->getFiltersSql();
        $total = 0;
        $rows = array();

        $sql = 'SELECT families.id FROM family_follow_up LEFT JOIN families ON family_follow_up.family_id = families.id ' .$filtersSql['join_clause'].$filtersSql['join_clause_2'].' WHERE follow_up_date != "0000-00-00"  AND done = "1"  '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').' group by family_follow_up.family_id UNION ALL SELECT families.id FROM child_follow_up LEFT JOIN children ON child_follow_up.child_id = children.id LEFT JOIN families ON children.parent_id = families.id '.$filtersSql['join_clause'].$filtersSql['join_clause_2'].' WHERE follow_up_date != "0000-00-00" AND parent_id != 0 AND families.id IS NOT null AND  done = "1" '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'  group by families.id';

        $rs = mysql_query($sql) or die(mysql_error().$sql);
        
        
        $rows[] = array(
            "name" => "Famillies Without Follow-up",
            "cnt"    => mysql_num_rows($rs)
        );

        $total += mysql_num_rows($rs);

        $sql = 'SELECT families.id FROM family_follow_up LEFT JOIN families ON family_follow_up.family_id = families.id '.$filtersSql['join_clause'].$filtersSql['join_clause_2'].'WHERE follow_up_date != "0000-00-00"  AND done = "0"  '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').' group by family_follow_up.family_id UNION ALL SELECT families.id FROM child_follow_up LEFT JOIN children ON child_follow_up.child_id = children.id LEFT JOIN families ON children.parent_id = families.id '.$filtersSql['join_clause'].$filtersSql['join_clause_2'].' WHERE follow_up_date != "0000-00-00" AND parent_id != 0 AND families.id IS NOT null AND  done = "0" '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'  group by families.id ';

        $rs = mysql_query($sql) or die(mysql_error().$sql);
        
        
        $rows[] = array(
            "name" => "Famillies With Follow-up",
            "cnt"    => mysql_num_rows($rs)
        );

        $total += mysql_num_rows($rs);

        
        
        if (count($rows)) {
            $totals = array('cnt'=>$total);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getStatusCounts()
    {
        $filtersSql = $this->getFiltersSql();
        $sql = '
        SELECT * FROM
        (
            SELECT families.status, count(*) cnt FROM `families`'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE 1 '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            GROUP BY status WITH ROLLUP
        ) t
        ORDER BY
            IF(status != "" && status IS NOT NULL, "1", IF(status IS NOT NULL, "2", "3")) ASC,
            Length(status) ASC, status ASC';
        //echo $sql;die;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['status'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }
    
    public function getOrgStatusCounts()
    {
        $filtersSql = $this->getFiltersSqlOrg();
        
                    
         $sql = '
        
        SELECT * FROM
        (
            SELECT os.status , count(*) cnt FROM `organization_sites` os 
            LEFT JOIN organizations ON os.organization_id=organizations.id 
            LEFT JOIN settings orgn ON orgn.id=organizations.organization_name_id 
             
            LEFT JOIN settings sites ON sites.id=os.organization_site_id 
            LEFT JOIN settings statuse ON os.status = `statuse`.id'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE 1 '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            GROUP BY os.status WITH ROLLUP
        ) t
        ORDER BY
            IF(status != "" && status IS NOT NULL, "1", IF(status IS NOT NULL, "2", "3")) ASC,
            Length(status) ASC, status ASC, cnt ASC';
        //echo $sql;die;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['status'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }
        
        return $rows;
    }
    
    public function getEventTypeCounts()
    {
        $filtersSql = $this->getFiltersSqlEvent();
        
                    
        $sql = '
        
        SELECT * FROM
        (
            SELECT event_type_id as status, count(*) cnt FROM events 
            left join`organization_sites` os on events.organization_sites_id=os.id
            LEFT JOIN organizations ON os.organization_id=organizations.id 
            LEFT JOIN settings orgn ON orgn.id=organizations.organization_name_id 
            
            LEFT JOIN settings sites ON sites.id=os.organization_site_id'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE 1 '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            GROUP BY event_type_id WITH ROLLUP
        ) t
        ORDER BY
            IF(status != "" && status IS NOT NULL, "1", IF(status IS NOT NULL, "2", "3")) ASC,
            Length(status) ASC, status ASC, cnt ASC';
        //echo $sql;die;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['status'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }
        
        return $rows;
    }
    
    public function getEventOutreachTypeCounts()
    {
        $filtersSql = $this->getFiltersSqlEvent();
        
                    
        $sql = '
        
        SELECT * FROM
        (
            SELECT outreach_type_id as status, count(*) cnt FROM events 
            left join`organization_sites` os on events.organization_sites_id=os.id
            LEFT JOIN organizations ON os.organization_id=organizations.id 
            LEFT JOIN settings orgn ON orgn.id=organizations.organization_name_id 
             
            LEFT JOIN settings sites ON sites.id=os.organization_site_id'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE 1 '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            GROUP BY outreach_type_id WITH ROLLUP
        ) t
        ORDER BY
            IF(status != "" && status IS NOT NULL, "1", IF(status IS NOT NULL, "2", "3")) ASC,
            Length(status) ASC, status ASC, cnt ASC';
        //echo $sql;die;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['status'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }
        
        return $rows;
    }
    
    public function getEventDayCounts()
    {
        $filtersSql = $this->getFiltersSqlEvent();
        
                    
        $sql = '
        
        SELECT * FROM
        (
            SELECT time_of_day as status, count(*) cnt FROM events 
            left join`organization_sites` os on events.organization_sites_id=os.id
            LEFT JOIN organizations ON os.organization_id=organizations.id 
            LEFT JOIN settings orgn ON orgn.id=organizations.organization_name_id 
             
            LEFT JOIN settings sites ON sites.id=os.organization_site_id'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE 1 '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            GROUP BY time_of_day WITH ROLLUP
        ) t
        ORDER BY
            IF(status != "" && status IS NOT NULL, "1", IF(status IS NOT NULL, "2", "3")) ASC,
            Length(status) ASC, status ASC, cnt ASC';
        //echo $sql;die;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['status'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }
        
        return $rows;
    }
    
    public function getEventCountyCounts()
    {
        $filtersSql = $this->getFiltersSqlEvent();
        
                    
        $sql = '
        
        SELECT * FROM
        (
            SELECT event_county_id as status, count(*) cnt FROM events 
            left join`organization_sites` os on events.organization_sites_id=os.id
            LEFT JOIN organizations ON os.organization_id=organizations.id 
            LEFT JOIN settings orgn ON orgn.id=organizations.organization_name_id 
             
            LEFT JOIN settings sites ON sites.id=os.organization_site_id'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE 1 '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            GROUP BY event_county_id WITH ROLLUP
        ) t
        ORDER BY
            IF(status != "" && status IS NOT NULL, "1", IF(status IS NOT NULL, "2", "3")) ASC,
            Length(status) ASC, status ASC, cnt ASC';
        //echo $sql;die;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['status'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }
        
        return $rows;
    }
    
    public function getOrgTypeCounts()
    {
        $filtersSql = $this->getFiltersSqlOrg();
        
                    
        $sql = '
        
        SELECT * FROM
        (
            SELECT organization_type_id as status, count(*) cnt FROM `organization_sites` os 
            LEFT JOIN organizations ON os.organization_id=organizations.id 
            LEFT JOIN settings orgn ON orgn.id=organizations.organization_name_id 
             
            LEFT JOIN settings sites ON sites.id=os.organization_site_id 
            LEFT JOIN settings statuse ON organization_type_id = `statuse`.id'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE 1 '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            GROUP BY organization_type_id WITH ROLLUP
        ) t
        ORDER BY  IF(status != "" && status IS NOT NULL, "1", IF(status IS NOT NULL, "2", "3")) ASC,
            Length(status) ASC, status ASC, cnt ASC';
        //echo $sql;die;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['status'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }
        
        return $rows;
    }
    
    public function getOrgModeCounts()
    {
        $filtersSql = $this->getFiltersSqlOrg();
        
                    
        $sql = '
        
        SELECT * FROM
        (
            SELECT mode_of_contact_id as status, count(*) cnt FROM `organization_sites` os 
            LEFT JOIN organizations ON os.organization_id=organizations.id 
            LEFT JOIN settings orgn ON orgn.id=organizations.organization_name_id 
            
            LEFT JOIN settings sites ON sites.id=os.organization_site_id 
            LEFT JOIN settings statuse ON mode_of_contact_id = `statuse`.id'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE 1 '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            GROUP BY mode_of_contact_id WITH ROLLUP
        ) t
        ORDER BY  IF(status != "" && status IS NOT NULL, "1", IF(status IS NOT NULL, "2", "3")) ASC,
            Length(status) ASC, status ASC, cnt ASC';
        //echo $sql;die;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['status'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }
        
        return $rows;
    }
    
    public function getOrgPartCounts()
    {
        $filtersSql = $this->getFiltersSqlOrg();
        
                    
        $sql = '
        
        SELECT * FROM
        (
            SELECT partnership_level_id as status, count(*) cnt FROM `organization_sites` os 
            LEFT JOIN organizations ON os.organization_id=organizations.id 
            LEFT JOIN settings orgn ON orgn.id=organizations.organization_name_id 
            
            LEFT JOIN settings sites ON sites.id=os.organization_site_id'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE 1 '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            GROUP BY partnership_level_id WITH ROLLUP
        ) t
        ORDER BY  IF(status != "" && status IS NOT NULL, "1", IF(status IS NOT NULL, "2", "3")) ASC,
            Length(status) ASC, status ASC, cnt ASC';
        //echo $sql;die;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['status'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }
        
        return $rows;
    }
    
    
    
    public function getOrgMouCounts()
    {
        $filtersSql = $this->getFiltersSqlOrg();
        
                    
         $sql = '
        
        SELECT * FROM
        (
            SELECT mou as status, count(*) cnt FROM `organization_sites` os 
            LEFT JOIN organizations ON os.organization_id=organizations.id 
            LEFT JOIN settings orgn ON orgn.id=organizations.organization_name_id 
            
            LEFT JOIN settings sites ON sites.id=os.organization_site_id'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE 1 '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            GROUP BY mou WITH ROLLUP
        ) t
        ORDER BY  IF(status != "" && status IS NOT NULL, "1", IF(status IS NOT NULL, "2", "3")) ASC,
            Length(status) ASC, status ASC, cnt ASC';
        //echo $sql;die;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['status'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }
        
        return $rows;
    }
    
    public function getOrgResCounts()
    {
        $filtersSql = $this->getFiltersSqlOrg();
        
                    
        $sql = '
        
        SELECT * FROM
        (
            SELECT resource_database_id as status, count(*) cnt FROM `organization_sites` os 
            LEFT JOIN organizations ON os.organization_id=organizations.id 
            LEFT JOIN settings orgn ON orgn.id=organizations.organization_name_id 
            
            LEFT JOIN settings sites ON sites.id=os.organization_site_id'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE 1 '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            GROUP BY resource_database_id WITH ROLLUP
        ) t
        ORDER BY  IF(status != "" && status IS NOT NULL, "1", IF(status IS NOT NULL, "2", "3")) ASC,
            Length(status) ASC, status ASC, cnt ASC';
        //echo $sql;die;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['status'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }
        
        return $rows;
    }
    
//---------------------------------------------------------
    public function getOrganizationDrill($arg1,$condition='',$column,$start)
    {
        $filtersSql = $this->getFiltersSqlOrg();
        
        $pos = false;
        
        
        $sql = 'SELECT organizations.*, orgn.name as organization_name, `os`.`organization_site_id` as organization_site_id, 
            sites.name as site, os.id as organization_sites_id, os.status,outy.name as partnership_level,rc.name as region,ty.name as organization_type FROM `organization_sites` os 
            LEFT JOIN organizations ON os.organization_id=organizations.id 
            LEFT JOIN settings orgn ON orgn.id=organizations.organization_name_id 
            
            LEFT JOIN settings sites ON sites.id=os.organization_site_id 
            LEFT JOIN settings statuse ON os.status = `statuse`.id
            LEFT JOIN settings outy ON organizations.partnership_level_id = outy.id 
            LEFT JOIN settings rc ON organizations.region_id = rc.id 
            LEFT JOIN settings ty ON organizations.organization_type_id = ty.id '
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE 1 '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
             ';

            
                if(strtolower($condition) != 'totals'){
                    if( !empty( $condition ) )
                        $sql.=' AND '.$column.'="'.$condition.'" ';
                    else
                        $sql.=' AND ( '.$column.' is null or '.$column.' = "0" ) ';
                }

                
           
            $sql .= " group by os.id ";
            $orderString = explode(' ',trim($this->_order_by));
            if(!empty($this->_order_by) && in_array($orderString[2],array('organization_name','site','organization_type','city','primary_phone','partnership_level','region')) ) {
                $sql .= $this->_order_by;
            } else {
                $sql.='';
            }
            if($arg1 == 1)
                $sql .= ' limit '.$start.',50';
            
       
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }        
        
        return $rows;
   }   
    public function getEventsDrill($arg1,$condition='',$column,$start)
    {
        $filtersSql = $this->getFiltersSqlOrg();
        
        $pos = false;
        
        
         $sql = 'SELECT events.*, orgn.name as organization_name, `os`.`organization_site_id` as organization_site_id, 
            sites.name as site, os.id as organization_sites_id, os.status,outy.name as outreach_type,ty.name as event_type,users.hmg_worker as hmgworker FROM events 
            left join `organization_sites` os  on events.organization_sites_id=os.id
            LEFT JOIN organizations ON os.organization_id=organizations.id 
            LEFT JOIN settings orgn ON orgn.id=organizations.organization_name_id            
            LEFT JOIN settings sites ON sites.id=os.organization_site_id 
            LEFT JOIN settings outy ON events.outreach_type_id = outy.id 
            LEFT JOIN settings ty ON events.event_type_id = ty.id 
            LEFT JOIN users ON events.hmg_worker = users.id 
            
            '
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE 1 '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
             ';

            
                if(strtolower($condition) != 'totals'){
                    $sql.=' AND '.$column.'="'.$condition.'" ';
                }

                
            
            $sql .= "  ";
            $orderString = explode(' ',trim($this->_order_by));
            
            if(!empty($this->_order_by) && in_array($orderString[2],array('event_name','organization_name','site','outreach_type','event_type','hmgworker')) ) {
                $sql .= $this->_order_by;
                
            } else {
                $sql.='';
            }
            if($arg1 == 1)
                $sql .= ' limit '.$start.',50';
            
       
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }        
        
        return $rows;
   } 
    public function getfamilyDrill($arg1,$condition='',$start)
    {
        $filtersSql = $this->getFiltersSql();
        $filtersSql['join_clause_2'] .= " JOIN users u ON u.id = families.hmg_worker ";
        $pos = false;
        if(!empty($filtersSql['filter_by']))
            $pos = strpos($filtersSql['filter_by'], 'status');
        $temp=0;
        if($arg1==1)
        {
            $temp=1;
        $sql = 
            'SELECT families.*,u.hmg_worker,language.name as `language` FROM `families` '
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
    
            left join settings language on families.language_id=language.id Where 1 ';

            if(!empty($condition) && $pos === false )
            {
                if(strtolower($condition) != 'totals')
                    $sql.=' AND families.status="'.$condition.'" ';

                $sql.=($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '');
            } 
            else
            {
                $sql.=($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '');

            }
            if(!empty($this->_order_by)) {
                $sql .= $this->_order_by;
            } else {
                $sql.='
            ORDER BY
                IF(families.status != "" && families.status IS NOT NULL, "1", IF(families.status IS NOT NULL, "2", "3")) ASC,
                Length(families.status) ASC, families.status ASC, first_name_1 asc ';
            }
            $sql .= ' limit '.$start.',50';
            //echo $sql."<br>";
       
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        
        }

        if($arg1==2)
        {
            $temp=2;
        $sql = 
        'SELECT count(*) as `total_records` FROM `families` '
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
             left join settings on families.language_id=settings.id Where 1 '; 
            if(!empty($condition) && $pos === false ) 
            {
                if(strtolower($condition) != 'totals')
                    $sql.=' AND families.status="'.$condition.'" ';

                $sql.=($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '');
            } 
            else
            {
                $sql.=($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '');

            }
           $sql.='
        ORDER BY
            IF(families.status != "" && families.status IS NOT NULL, "1", IF(families.status IS NOT NULL, "2", "3")) ASC,
            Length(families.status) ASC, families.status ASC limit '.$start.',50';
        
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows = $row['total_records'];
        }
        }
        
        if($arg1==3)
        {
            $temp=3;
       $sql = 
        'SELECT families.*,settings.name as `language_name` FROM `families`'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            left join settings on families.language_id=settings.id Where 1 '; 
            if(!empty($condition) && $pos === false )
            {
                if(strtolower($condition) != 'totals')
                    $sql.=' AND families.status="'.$condition.'" ';

                $sql.=($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '');
            } 
            else
            {
                $sql.=($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '');

            }
           $sql.='
        ORDER BY
            IF(families.status != "" && families.status IS NOT NULL, "1", IF(families.status IS NOT NULL, "2", "3")) ASC,
            Length(families.status) ASC, families.status ASC';
     
         
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        
        }
        }
        return $rows;
   }   
    
//-----------------------------------------------------------------------
    public function getRecurringFamilyCounts()
    {
        $filtersSql = $this->getFiltersSql();
        $sql = '
        SELECT name, cnt
        FROM
        (
            (
                SELECT "Recurring" name, count(*) cnt
                FROM
                (
                    SELECT count(*) cnt FROM `families`
                    JOIN startend ON startend.parent_id = families.id
                    '.$filtersSql['join_clause_2'].'
                    WHERE 1 '
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
                    GROUP BY families.id
                    HAVING cnt >= 2
                ) c2
            )
            UNION  ALL
            (
                SELECT "Total" name, count(*) cnt FROM `families`'
                .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
                WHERE 1 '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
                GROUP BY 1
            )
        ) a';
        //echo $sql;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getStatusByWorkerCounts()
    {
        $filtersSql = $this->getFiltersSql();



        $sql =
            '
            SELECT
                t0.hmg_worker hmg_worker,
                IF(t1.Active IS NULL, 0, t1.Active) Active,
                IF(t2.Inactive IS NULL, 0, t2.Inactive) Inactive,
                IF(t3.Inquiry IS NULL, 0, t3.Inquiry) "Open Inquiry",
                IF(t4.Inquiry IS NULL, 0, t4.Inquiry) "Closed Inquiry"
            FROM
            (
                ( SELECT
                    `u`.`hmg_worker`,
                    count(`families`.`status`) "Total Status"
                  FROM families
                   JOIN users u ON u.id = families.hmg_worker  GROUP BY u.hmg_worker
                  ) t0
                LEFT JOIN
                ( SELECT
                    `u`.`hmg_worker`,
                    count(`families`.`status`) Active
                  FROM families
                  JOIN users  u ON u.id = families.hmg_worker  '
                    .$filtersSql['join_clause'].$filtersSql['join_clause_2']
                    .' WHERE `families`.`status` = "Active"'
                    .' AND `u`.`status` = "1"'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .' GROUP BY `u`.`hmg_worker`
                ) t1 on  t1.hmg_worker = t0.hmg_worker
                LEFT JOIN
                ( SELECT
                    `u`.`hmg_worker`,
                    count(`families`.`status`) Inactive
                   FROM families
                   JOIN users  u ON u.id = families.hmg_worker '
                    .$filtersSql['join_clause'].$filtersSql['join_clause_2']
                    .' WHERE `families`.`status` = "Inactive"'
                    .' AND `u`.`status` = "1"'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .' GROUP BY `families`.`hmg_worker`
                ) t2 ON t2.hmg_worker = t0.hmg_worker
                LEFT JOIN
                ( SELECT DISTINCT
                    `u`.`hmg_worker`,
                    count(`families`.`status`) Inquiry
                    FROM families
                    JOIN users  u ON u.id = families.hmg_worker '
                    .
                    (!strstr($filtersSql['join_clause'], 'startend') ?
                        'LEFT JOIN startend ON startend.parent_id = families.id'
                        : ''
                    )
                    .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
                    WHERE `families`.`status` = "Open Inquiry" AND startend.end_date = "0000-00-00"
                    AND `u`.`status` = "1"'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .' GROUP BY `families`.`hmg_worker`
                ) t3 ON t3.hmg_worker = t0.hmg_worker
                LEFT JOIN
                ( SELECT DISTINCT
                    `u`.`hmg_worker`,
                    count(`families`.`status`) Inquiry
                    FROM families
                    JOIN users  u ON u.id = families.hmg_worker '
                    .
                    (!strstr($filtersSql['join_clause'], 'startend') ?
                        'LEFT JOIN startend ON startend.parent_id = families.id'
                        : ''
                    )
                    .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
                    WHERE `families`.`status` = "Closed Inquiry" AND startend.end_date < "'.date('Y-m-d').'"
                    AND `u`.`status` = "1"'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .' GROUP BY `families`.`hmg_worker`
                ) t4 ON t4.hmg_worker = t0.hmg_worker
            )
            WHERE t1.Active > 0 OR t2.Inactive > 0 OR t3.Inquiry > 0 OR t4.Inquiry > 0
            ORDER BY IF(t0.hmg_worker != "", "1", "2") ASC, t0.hmg_worker';
        //echo $sql;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        $inquiryOpenTotals = 0;
        $inquiryClosedTotals = 0;
        $activeTotals = 0;
        $inactiveTotals = 0;
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $row['total'] = $row['Active'] + $row['Inactive'] + $row['Open Inquiry'] +  $row['Closed Inquiry'];
            $rows[] = $row;
            $activeTotals        += $row['Active'];
            $inactiveTotals      += $row['Inactive'];
            $inquiryOpenTotals   += $row['Open Inquiry'];
            $inquiryClosedTotals += $row['Closed Inquiry'];
        }
        if (count($rows)) {
            $grandTotal = $activeTotals + $inactiveTotals + $inquiryOpenTotals + $inquiryClosedTotals;
            $totals = array(
                'hmg_worker' => 'Total',
                'Active' => $activeTotals,
                'Inactive' => $inactiveTotals,
                'Open Inquiry' => $inquiryOpenTotals,
                'Closed Inquiry' => $inquiryClosedTotals,
                'total' => $grandTotal,
                'percent' => '100',
            );
            array_push($rows, $totals);
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['total'] / $grandTotal) * 100, 2);
            }
        }

        return $rows;
    }

    public function getWorkerCCLevelCounts()
    {
        $filtersSql = $this->getFiltersSql();
         $sql =
            '
            SELECT
                a1.hmg_worker hmg_worker,
                IF(t1.Level1 IS NULL, 0, t1.Level1) Level1,
                IF(t2.Level2 IS NULL, 0, t2.Level2) Level2,
                IF(t3.Level3 IS NULL, 0, t3.Level3) Level3
            FROM
            (
                ( SELECT
                    `u`.`hmg_worker`
                  FROM families 
                  LEFT JOIN users u ON u.id=families.hmg_worker '
                    .(!empty($filtersSql['join_clause_users']) ? $filtersSql['join_clause_users'] : '').
					$filtersSql['join_clause'].$filtersSql['join_clause_2']
                    .' WHERE `families`.`cc_level` IN ("Level 1", "Level 2", "Level 3")'
                    .' AND `u`.`status` = "1"'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .' GROUP BY `u`.`hmg_worker`
                ) a1
                LEFT JOIN
                ( SELECT
                    `u`.`hmg_worker`,
                    count(`families`.`cc_level`) Level1
                  FROM families 
                  LEFT JOIN users u ON u.id=families.hmg_worker '
                    .(!empty($filtersSql['join_clause_users']) ? $filtersSql['join_clause_users'] : '')
					.$filtersSql['join_clause'].$filtersSql['join_clause_2']
                    .' WHERE `families`.`cc_level` = "Level 1"'
                    .' AND `u`.`status` = "1"'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .' GROUP BY `u`.`hmg_worker`
                ) t1 ON t1.hmg_worker = a1.hmg_worker
                LEFT JOIN
                ( SELECT
                    `u`.`hmg_worker`,
                    count(`families`.`cc_level`) Level2
                   FROM families 
                   LEFT JOIN users u  ON u.id=families.hmg_worker '
                    .(!empty($filtersSql['join_clause_users']) ? $filtersSql['join_clause_users'] : '')
					.$filtersSql['join_clause'].$filtersSql['join_clause_2']
                    .' WHERE `families`.`cc_level` = "Level 2"'
                    .' AND `u`.`status` = "1"'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .' GROUP BY `u`.`hmg_worker`
                ) t2 ON a1.hmg_worker = t2.hmg_worker
                LEFT JOIN
                ( SELECT
                    `u`.`hmg_worker`,
                    count(`families`.`cc_level`) Level3
                    FROM families 
                    LEFT JOIN users u ON u.id=families.hmg_worker '
                    .(!empty($filtersSql['join_clause_users']) ? $filtersSql['join_clause_users'] : '')
					.$filtersSql['join_clause'].$filtersSql['join_clause_2']
                    .' WHERE `families`.`cc_level` = "Level 3"'
                    .' AND `u`.`status` = "1"'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .' GROUP BY `u`.`hmg_worker`
                ) t3 ON a1.hmg_worker = t3.hmg_worker
            )
            ORDER BY IF(a1.hmg_worker != "", "1", "2") ASC, a1.hmg_worker';
            
        //echo $sql;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        $level1Totals = 0;
        $level2Totals = 0;
        $level3Totals = 0;
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $row['total'] = $row['Level1'] + $row['Level2'] + $row['Level3'];
            $rows[] = $row;
            $level1Totals += $row['Level1'];
            $level2Totals += $row['Level2'];
            $level3Totals += $row['Level3'];
        }
        if (count($rows)) {
            $grandTotal = $level1Totals + $level2Totals + $level3Totals;
            $totals = array(
                'hmg_worker' => 'Total',
                'Level1' => $level1Totals,
                'Level2' => $level2Totals,
                'Level3' => $level3Totals,
                'total' => $grandTotal,
                'percent' => '100',
            );
            array_push($rows, $totals);
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['total'] / $grandTotal) * 100, 2);
            }
        }

        return $rows;
    }

    public function getTasksBYWorkerCounts()
    {
        $filtersSql = $this->getFiltersSql('follow_up_date');
$sql = '
        SELECT
            a1.hmg_worker hmg_worker,
            if(t1.reason_count IS NULL, 0, t1.reason_count) "Connected",
            if(t2.reason_count IS NULL, 0, t2.reason_count) "Give Screening Results",
            if(t3.reason_count IS NULL, 0, t3.reason_count) "Relay Information",
            if(t4.reason_count IS NULL, 0, t4.reason_count) "Research",
            if(t5.reason_count IS NULL, 0, t5.reason_count) "Update Family Information",
            if(t6.reason_count IS NULL, 0, t6.reason_count) "Verify Receipt of Screening",
            if(t7.reason_count IS NULL, 0, t7.reason_count)  "Other",
            ifnull(t1.reason_count, 0) + ifnull(t2.reason_count, 0) +  ifnull(t3.reason_count, 0)
             + ifnull(t4.reason_count, 0) + ifnull(t5.reason_count, 0) + ifnull(t6.reason_count, 0)
             + ifnull(t7.reason_count, 0) Total
        FROM
        (
            ( SELECT
                `u`.`hmg_worker`
              FROM families
              LEFT JOIN users u  ON u.id=families.hmg_worker
              WHERE `u`.`status` = "1"
              GROUP BY `u`.`hmg_worker`
            ) a1

            LEFT JOIN
            (
                SELECT hmg_worker, follow_up_task, count(*) reason_count
                FROM
                (
                    SELECT u.hmg_worker, ft.name follow_up_task
                    FROM family_follow_up
                    JOIN families ON family_follow_up.family_id = families.id
                    LEFT JOIN settings ft ON ft.id = family_follow_up.follow_up_task_id
					LEFT JOIN users u ON u.id=family_follow_up.hmg_worker '
					
                    .(!empty($filtersSql['join_clause_users']) ? $filtersSql['join_clause_users'] : '')
					.$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
                    WHERE done = "0" AND ft.name IN ("Connected?", "Connected")'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .'
                    UNION ALL
                    SELECT u.hmg_worker, ft.name follow_up_task
                    FROM child_follow_up
                    JOIN children ON child_follow_up.child_id = children.id
                    JOIN families ON children.parent_id = families.id
					JOIN users u ON u.id=child_follow_up.hmg_worker 
					
                    '.(!empty($filtersSql['join_clause_users']) ? $filtersSql['join_clause_users'] : '')
					.$filtersSql['join_clause_2'].'
                    LEFT JOIN settings ft ON ft.id = child_follow_up.follow_up_task_id
                    WHERE done = "0" AND ft.name IN ("Connected?", "Connected")'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .'
                ) sq1 group by sq1.hmg_worker
            ) t1 ON t1.hmg_worker = a1.hmg_worker

            LEFT JOIN
            (
                SELECT hmg_worker, follow_up_task, count(*) reason_count
                FROM
                (
                    SELECT u.hmg_worker, ft.name follow_up_task
                    FROM family_follow_up
                    JOIN families ON family_follow_up.family_id = families.id
                    LEFT JOIN settings ft ON ft.id = family_follow_up.follow_up_task_id
					LEFT JOIN users u ON u.id=family_follow_up.hmg_worker '
					
                    .(!empty($filtersSql['join_clause_users']) ? $filtersSql['join_clause_users'] : '')
					.$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
                    WHERE done = "0" AND ft.name IN ("Give Screening Results")'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .'
                    UNION ALL
                    SELECT u.hmg_worker, ft.name follow_up_task
                    FROM child_follow_up
                    JOIN children ON child_follow_up.child_id = children.id
                    JOIN families ON children.parent_id = families.id
					JOIN users u ON u.id=child_follow_up.hmg_worker '
					.(!empty($filtersSql['join_clause_users']) ? $filtersSql['join_clause_users'] : '')
					.$filtersSql['join_clause_2'].'
                    LEFT JOIN settings ft ON ft.id = child_follow_up.follow_up_task_id
                    WHERE done = "0" AND ft.name IN ("Give Screening Results")'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .'
                ) sq2 group by sq2.hmg_worker

            ) t2 ON t2.hmg_worker = a1.hmg_worker

            LEFT JOIN
            (
                SELECT hmg_worker, follow_up_task, count(*) reason_count
                FROM
                (
                    SELECT u.hmg_worker, ft.name follow_up_task
                    FROM family_follow_up
                    JOIN families ON family_follow_up.family_id = families.id
                    LEFT JOIN settings ft ON ft.id = family_follow_up.follow_up_task_id
					LEFT JOIN users u ON u.id=family_follow_up.hmg_worker '
                    .(!empty($filtersSql['join_clause_users']) ? $filtersSql['join_clause_users'] : '')
					.$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
                    WHERE done = "0" AND ft.name IN ("Relay Information")'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .'
                    UNION ALL
                    SELECT u.hmg_worker, ft.name follow_up_task
                    FROM child_follow_up
                    JOIN children ON child_follow_up.child_id = children.id
                    JOIN families ON children.parent_id = families.id
                    LEFT JOIN users u ON u.id=child_follow_up.hmg_worker'.(!empty($filtersSql['join_clause_users']) ? $filtersSql['join_clause_users'] : '')
					.$filtersSql['join_clause_2'].'
                    LEFT JOIN settings ft ON ft.id = child_follow_up.follow_up_task_id
                    WHERE done = "0" AND ft.name IN ("Relay Information")'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .'
                ) sq3 group by sq3.hmg_worker

            ) t3 ON t3.hmg_worker = a1.hmg_worker

            LEFT JOIN
            (
                SELECT hmg_worker, follow_up_task, count(*) reason_count
                FROM
                (
                    SELECT u.hmg_worker, ft.name follow_up_task
                    FROM family_follow_up
                    JOIN families ON family_follow_up.family_id = families.id
                    LEFT JOIN settings ft ON ft.id = family_follow_up.follow_up_task_id
					LEFT JOIN users u ON u.id=family_follow_up.hmg_worker'
                    .(!empty($filtersSql['join_clause_users']) ? $filtersSql['join_clause_users'] : '')
					.$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
                    WHERE done = "0" AND ft.name IN ("Research")'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .'
                    UNION ALL
                    SELECT u.hmg_worker, ft.name follow_up_task
                    FROM child_follow_up
                    JOIN children ON child_follow_up.child_id = children.id
                    JOIN families ON children.parent_id = families.id
                    JOIN users u  ON u.id=child_follow_up.hmg_worker'.(!empty($filtersSql['join_clause_users']) ? $filtersSql['join_clause_users'] : '').
					$filtersSql['join_clause_2'].'
                    LEFT JOIN settings ft ON ft.id = child_follow_up.follow_up_task_id
                    WHERE done = "0" AND ft.name IN ("Research")'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .'
                ) sq4 group by sq4.hmg_worker

            ) t4 ON t4.hmg_worker = a1.hmg_worker

            LEFT JOIN
            (
                SELECT hmg_worker, follow_up_task, count(*) reason_count
                FROM
                (
                    SELECT u.hmg_worker, ft.name follow_up_task
                    FROM family_follow_up
                    JOIN families ON family_follow_up.family_id = families.id
                    LEFT JOIN settings ft ON ft.id = family_follow_up.follow_up_task_id
					LEFT JOIN users u ON u.id=family_follow_up.hmg_worker'
                    .(!empty($filtersSql['join_clause_users']) ? $filtersSql['join_clause_users'] : '')
					.$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
                    WHERE done = "0" AND ft.name IN ("Update Family Information")'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .'
                    UNION ALL
                    SELECT u.hmg_worker, ft.name follow_up_task
                    FROM child_follow_up
                    JOIN children ON child_follow_up.child_id = children.id
                    JOIN families ON children.parent_id = families.id
					JOIN users u ON u.id=child_follow_up.hmg_worker'.(!empty($filtersSql['join_clause_users']) ? $filtersSql['join_clause_users'] : '').
					$filtersSql['join_clause_2'].'
                    LEFT JOIN settings ft ON ft.id = child_follow_up.follow_up_task_id
                    WHERE done = "0" AND ft.name IN ("Update Family Information")'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .'
                ) sq5 group by sq5.hmg_worker

            ) t5 ON t5.hmg_worker = a1.hmg_worker

            LEFT JOIN
            (
                SELECT hmg_worker, follow_up_task, count(*) reason_count
                FROM
                (
                    SELECT u.hmg_worker, ft.name follow_up_task
                    FROM family_follow_up
                    JOIN families ON family_follow_up.family_id = families.id
                    LEFT JOIN settings ft ON ft.id = family_follow_up.follow_up_task_id
					LEFT JOIN users u  ON u.id=family_follow_up.hmg_worker'
                    .(!empty($filtersSql['join_clause_users']) ? $filtersSql['join_clause_users'] : '').
					$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
                    WHERE done = "0" AND ft.name IN ("Verify Receipt of Screening")'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .'
                    UNION ALL
                    SELECT u.hmg_worker, ft.name follow_up_task
                    FROM child_follow_up
                    JOIN children ON child_follow_up.child_id = children.id
                    JOIN families ON children.parent_id = families.id
					JOIN users u ON u.id=child_follow_up.hmg_worker
					'.(!empty($filtersSql['join_clause_users']) ? $filtersSql['join_clause_users'] : '').
					$filtersSql['join_clause_2'].'
                    LEFT JOIN settings ft ON ft.id = child_follow_up.follow_up_task_id
                    WHERE done = "0" AND ft.name IN ("Verify Receipt of Screening")'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .'
                ) sq6 group by sq6.hmg_worker

            ) t6 ON t6.hmg_worker = a1.hmg_worker

            LEFT JOIN
            (
                SELECT hmg_worker, follow_up_task, count(*) reason_count
                FROM
                (
                    SELECT u.hmg_worker, ft.name follow_up_task
                    FROM family_follow_up
                    JOIN families ON family_follow_up.family_id = families.id
                    LEFT JOIN settings ft ON ft.id = family_follow_up.follow_up_task_id
					LEFT JOIN users u  ON u.id=family_follow_up.hmg_worker'
                    .(!empty($filtersSql['join_clause_users']) ? $filtersSql['join_clause_users'] : '').
					$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
                    WHERE done = "0" AND ft.name NOT IN ("Connected?", "Give Screening Results", "Relay Information", "Research", "Update Family Information", "Verify Receipt of Screening")'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .'
                    UNION ALL
                    SELECT u.hmg_worker, ft.name follow_up_task
                    FROM child_follow_up
                    JOIN children ON child_follow_up.child_id = children.id
                    JOIN families ON children.parent_id = families.id
                    JOIN users u  ON u.id=child_follow_up.hmg_worker
					'.(!empty($filtersSql['join_clause_users']) ? $filtersSql['join_clause_users'] : '').
					$filtersSql['join_clause_2'].'
                    LEFT JOIN settings ft ON ft.id = child_follow_up.follow_up_task_id
                    WHERE done = "0" AND ft.name NOT IN ("Connected?", "Give Screening Results", "Relay Information", "Research", "Update Family Information", "Verify Receipt of Screening")'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .'
                ) sq7 group by sq7.hmg_worker

            ) t7 ON t7.hmg_worker = a1.hmg_worker
        )

        ';
        
        // echo '<pre>';
        // echo $sql;
        // echo '</pre>';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        $connectTotals = 0;
        $giveTotals = 0;
        $relayTotals = 0;
        $researchTotals = 0;
        $familyUpdateTotals = 0;
        $verifyTotals = 0;
        $otherTotals = 0;
        $totalTotals = 0;
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
            $connectTotals += $row['Connected'];
            $giveTotals += $row['Give Screening Results'];
            $relayTotals += $row['Relay Information'];
            $researchTotals += $row['Research'];
            $familyUpdateTotals += $row['Update Family Information'];
            $verifyTotals += $row['Verify Receipt of Screening'];
            $otherTotals += $row['Other'];
            $totalTotals += $row['Total'];
        }
        if (count($rows)) {
            $totals = array(
                'hmg_worker' => 'Total',
                'Connected' => $connectTotals,
                'Give Screening Results' => $giveTotals,
                'Relay Information' => $relayTotals,
                'Research' => $researchTotals,
                'Update Family Information' => $familyUpdateTotals,
                'Verify Receipt of Screening' => $verifyTotals,
                'Other' => $otherTotals,
                'Total' => $totalTotals,
            );
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getFollowUpByWorkerCounts()
    {
        $filtersSql = $this->getFiltersSql();
        $sql =
            '
            SELECT
                t1.hmg_worker hmg_worker,
                SUM(IF(t1.Active IS NULL, 0, t1.Active)) Active,
                IF(t2.InActive IS NULL, 0, t2.InActive) Inactive,
                IF(t3.Inquiry IS NULL, 0, t3.Inquiry) Inquiry
            FROM
            (SELECT hmg_worker, SUM(Active) Active FROM
                (SELECT
                    ff1.hmg_worker,
                    count(`families`.`status`) Active
                FROM family_follow_up ff1
                LEFT JOIN families ON ff1.family_id = families.id
                JOIN users ON users.hmg_worker = `families`.`hmg_worker`'
                .$filtersSql['join_clause']
                .' WHERE `families`.`status` = "Active" '
                .' AND `users`.`status` = "1"'
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                .' GROUP BY `families`.`hmg_worker`
                UNION
                SELECT
                    cff1.hmg_worker,
                    count(`families`.`status`) Active
                FROM child_follow_up cff1
                LEFT JOIN children c1 ON cff1.child_id = c1.id
                LEFT JOIN families ON c1.parent_id = families.id
                JOIN users ON users.hmg_worker = `families`.`hmg_worker`'
                .$filtersSql['join_clause']
                .' WHERE `families`.`status` = "Active" '
                .' AND `users`.`status` = "1"'
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                .' GROUP BY `families`.`hmg_worker`
                ) s1 GROUP BY hmg_worker
            ) t1
            LEFT JOIN
            (SELECT hmg_worker, SUM(Inactive) Inactive FROM
                (SELECT
                        ff2.hmg_worker,
                        count(`families`.`status`) Inactive
                    FROM family_follow_up ff2
                    LEFT JOIN families ON ff2.family_id = families.id
                    JOIN users ON users.hmg_worker = `families`.`hmg_worker`'
                    .$filtersSql['join_clause']
                    .' WHERE `families`.`status` = "Inactive" '
                    .' AND `users`.`status` = "1"'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .' GROUP BY `families`.`hmg_worker`
                    UNION
                    SELECT
                        cff2.hmg_worker,
                        count(`families`.`status`) Inactive
                    FROM child_follow_up cff2
                    LEFT JOIN children c2 ON cff2.child_id = c2.id
                    LEFT JOIN families ON c2.parent_id = families.id
                    JOIN users ON users.hmg_worker = `families`.`hmg_worker`'
                    .$filtersSql['join_clause']
                    .' WHERE `families`.`status` = "Inactive" '
                    .' AND `users`.`status` = "1"'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .' GROUP BY `families`.`hmg_worker`
                ) s2 GROUP BY hmg_worker
            ) t2 ON t1.hmg_worker = t2.hmg_worker
            LEFT JOIN
            (SELECT hmg_worker, SUM(Inquiry) Inquiry FROM
                (SELECT
                        ff3.hmg_worker,
                        count(`families`.`status`) Inquiry
                    FROM family_follow_up ff3
                    LEFT JOIN families ON ff3.family_id = families.id
                    JOIN users ON users.hmg_worker = `families`.`hmg_worker`'
                    .$filtersSql['join_clause']
                    .' WHERE `families`.`status` = "Inquiry" '
                    .' AND `users`.`status` = "1"'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .' GROUP BY `families`.`hmg_worker`
                    UNION
                    SELECT
                        cff3.hmg_worker,
                        count(`families`.`status`) Inquiry
                    FROM child_follow_up cff3
                    LEFT JOIN children c3 ON cff3.child_id = c3.id
                    LEFT JOIN families ON c3.parent_id = families.id
                    JOIN users ON users.hmg_worker = `families`.`hmg_worker`'
                    .$filtersSql['join_clause']
                    .' WHERE `families`.`status` = "Inquiry" '
                    .' AND `users`.`status` = "1"'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .' GROUP BY `families`.`hmg_worker`
                ) s3 GROUP BY hmg_worker
            ) t3 ON t1.hmg_worker = t3.hmg_worker
            GROUP BY t1.hmg_worker
            ORDER BY IF(t1.hmg_worker != "", "1", "2") ASC, t1.hmg_worker';
        //echo $sql;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        $inquiryTotals = 0;
        $activeTotals = 0;
        $inactiveTotals = 0;
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $row['total'] = $row['Active'] + $row['Inactive'] + $row['Inquiry'];
            $rows[] = $row;
            $activeTotals += $row['Active'];
            $inactiveTotals += $row['Inactive'];
            $inquiryTotals += $row['Inquiry'];
        }
        if (count($rows)) {
            $grandTotal = $activeTotals + $inactiveTotals + $inquiryTotals;
            $totals = array(
                'hmg_worker' => 'Total',
                'Active' => $activeTotals,
                'Inactive' => $inactiveTotals,
                'Inquiry' => $inquiryTotals,
                'total' => $grandTotal,
                'percent' => '100',
            );
            array_push($rows, $totals);
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['total'] / $grandTotal) * 100, 2);
            }
        }

        return $rows;
    }

    public function getReferralCounts()
    {
        $filtersSql = $this->getFiltersSql('referral_date');
        $sql = 'SELECT outcomes `name`,referred_to_type, count(*) cnt
            FROM
            (
                SELECT outcomes,referred_to_type FROM child_referrals cr
                LEFT JOIN children c ON cr.child_id = c.id
                LEFT JOIN families ON c.parent_id = families.id
                '.$filtersSql['join_clause_2'].'
                WHERE 1 '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                .'UNION ALL
                SELECT outcomes,referred_to_type FROM family_referrals fr
                LEFT JOIN families ON fr.family_id = families.id
                '.$filtersSql['join_clause_2'].'
                WHERE 1 '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
                UNION ALL 
                SELECT outcomes ,referred_to_type FROM contact_referrals fr
                LEFT JOIN contacts ON fr.contact_id = contacts.id
                LEFT JOIN family_provider fp ON fp.contact_id = contacts.id 
                LEFT JOIN families ON fp.family_id = families.id 
                '.$filtersSql['join_clause_3'].'
                WHERE 1 '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            ) a
            GROUP BY outcomes,referred_to_type
            ORDER BY IF(outcomes != "", "1", "2") ASC, outcomes,referred_to_type';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        //echo $sql;die;
        $rows = array();
        $total = 0;
        $grandTotal = 0;
        $osgrandTotal = 0;
        $infograndTotal = 0;
        $separateArray = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            if( !isset( $separateArray[$row['name']] ) ){ 
                $rows[] = $row;
                $separateArray[$row['name']] = array();                
            }
            $grandTotal += $row['cnt'];
            if( $row['referred_to_type'] == 'os'){
                $osgrandTotal += $row['cnt'];
                $separateArray[$row['name']]['os'] = $row['cnt'];
            }
            else{
                $infograndTotal += $row['cnt'];
                $separateArray[$row['name']]['info'] = $row['cnt'];
            } 
            
        }

        
        if (count($rows)) {
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            $totals['cnt'] = $grandTotal;
            $totals['os_cnt'] = $osgrandTotal;
            $totals['info_cnt'] = $infograndTotal;
            $setting = new Setting();             
            foreach ($rows as $key => $value) {
                $rows[$key]['name'] = $setting->getSettingById($value['name']); //191016
                $value['cnt'] = (isset($separateArray[$value['name']]['os'])?$separateArray[$value['name']]['os']:0) + (isset($separateArray[$value['name']]['info'])?$separateArray[$value['name']]['info']:0);

                $rows[$key]['cnt'] = $value['cnt'];
                $rows[$key]['os_cnt'] = (isset($separateArray[$value['name']]['os'])?$separateArray[$value['name']]['os']:0);
                $rows[$key]['info_cnt'] = (isset($separateArray[$value['name']]['info'])?$separateArray[$value['name']]['info']:0);

                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getFamilyReferralCounts()
    {
        $filtersSql = $this->getFiltersSql('referral_date');
        $sql = '
            SELECT outcomes `name`,referred_to_type, count(*) cnt
            FROM family_referrals fr
            LEFT JOIN families ON fr.family_id = families.id
            '.$filtersSql['join_clause_2'].'
            WHERE 1 '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
            .' GROUP BY outcomes
            ORDER BY IF(outcomes != "", "1", "2") ASC, outcomes,referred_to_type';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        $total = 0;
       $grandTotal = 0;
        $osgrandTotal = 0;
        $infograndTotal = 0;
        $separateArray = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            if( !isset( $separateArray[$row['name']] ) ){ 
                $rows[] = $row;
                $separateArray[$row['name']] = array();                
            }
            $grandTotal += $row['cnt'];
            if( $row['referred_to_type'] == 'os'){
                $osgrandTotal += $row['cnt'];
                $separateArray[$row['name']]['os'] = $row['cnt'];
            }
            else{
                $infograndTotal += $row['cnt'];
                $separateArray[$row['name']]['info'] = $row['cnt'];
            } 
            
        }

        
        if (count($rows)) {
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            $totals['cnt'] = $grandTotal;
            $totals['os_cnt'] = $osgrandTotal;
            $totals['info_cnt'] = $infograndTotal;
            $setting = new Setting();             
            foreach ($rows as $key => $value) {
                $rows[$key]['name'] = $setting->getSettingById($value['name']); //191016
                $value['cnt'] = (isset($separateArray[$value['name']]['os'])?$separateArray[$value['name']]['os']:0) + (isset($separateArray[$value['name']]['info'])?$separateArray[$value['name']]['info']:0);

                $rows[$key]['cnt'] = $value['cnt'];
                $rows[$key]['os_cnt'] = (isset($separateArray[$value['name']]['os'])?$separateArray[$value['name']]['os']:0);
                $rows[$key]['info_cnt'] = (isset($separateArray[$value['name']]['info'])?$separateArray[$value['name']]['info']:0);

                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getProviderReferralCounts()
    {
        $filtersSql = $this->getFiltersSql('referral_date');
         $sql = '
            SELECT outcomes `name`,referred_to_type, count(*) cnt
            FROM contact_referrals fr
            LEFT JOIN contacts ON fr.contact_id = contacts.id             
                LEFT JOIN family_provider fp ON fp.contact_id = contacts.id 
                LEFT JOIN families  ON fp.family_id = families.id 
            '.$filtersSql['join_clause_3'].'
            WHERE 1 '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
            .' GROUP BY outcomes,referred_to_type 
            ORDER BY IF(outcomes != "", "1", "2") ASC, outcomes,referred_to_type';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        $total = 0;
        $grandTotal = 0;
        $osgrandTotal = 0;
        $infograndTotal = 0;
        $separateArray = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            if( !isset( $separateArray[$row['name']] ) ){ 
                $rows[] = $row;
                $separateArray[$row['name']] = array();                
            }
            $grandTotal += $row['cnt'];
            if( $row['referred_to_type'] == 'os'){
                $osgrandTotal += $row['cnt'];
                $separateArray[$row['name']]['os'] = $row['cnt'];
            }
            else{
                $infograndTotal += $row['cnt'];
                $separateArray[$row['name']]['info'] = $row['cnt'];
            } 
            
        }

        
        if (count($rows)) {
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            $totals['cnt'] = $grandTotal;
            $totals['os_cnt'] = $osgrandTotal;
            $totals['info_cnt'] = $infograndTotal;
            $setting = new Setting();             
            foreach ($rows as $key => $value) {
                $rows[$key]['name'] = $setting->getSettingById($value['name']); //191016
                $value['cnt'] = (isset($separateArray[$value['name']]['os'])?$separateArray[$value['name']]['os']:0) + (isset($separateArray[$value['name']]['info'])?$separateArray[$value['name']]['info']:0);

                $rows[$key]['cnt'] = $value['cnt'];
                $rows[$key]['os_cnt'] = (isset($separateArray[$value['name']]['os'])?$separateArray[$value['name']]['os']:0);
                $rows[$key]['info_cnt'] = (isset($separateArray[$value['name']]['info'])?$separateArray[$value['name']]['info']:0);

                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getChildReferralCounts($basedOnScreening = false)
    {
        $filtersSql = $this->getFiltersSql('referral_date');
        $sql = '
            SELECT outcomes `name`,referred_to_type, count(*) cnt
            FROM child_referrals cr
            LEFT JOIN children c ON cr.child_id = c.id
            LEFT JOIN families ON c.parent_id = families.id
            '.$filtersSql['join_clause_2'].'
            WHERE 1 '
            .($basedOnScreening ? ' AND based_screening="1"' : '')
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
            .' GROUP BY outcomes
            ORDER BY IF(outcomes != "", "1", "2") ASC, outcomes,referred_to_type';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        $total = 0;
        $grandTotal = 0;
        $osgrandTotal = 0;
        $infograndTotal = 0;
        $separateArray = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            if( !isset( $separateArray[$row['name']] ) ){ 
                $rows[] = $row;
                $separateArray[$row['name']] = array();                
            }
            $grandTotal += $row['cnt'];
            if( $row['referred_to_type'] == 'os'){
                $osgrandTotal += $row['cnt'];
                $separateArray[$row['name']]['os'] = $row['cnt'];
            }
            else{
                $infograndTotal += $row['cnt'];
                $separateArray[$row['name']]['info'] = $row['cnt'];
            } 
            
        }

        
        if (count($rows)) {
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            $totals['cnt'] = $grandTotal;
            $totals['os_cnt'] = $osgrandTotal;
            $totals['info_cnt'] = $infograndTotal;
            $setting = new Setting();             
            foreach ($rows as $key => $value) {
                $rows[$key]['name'] = $setting->getSettingById($value['name']); //191016
                $value['cnt'] = (isset($separateArray[$value['name']]['os'])?$separateArray[$value['name']]['os']:0) + (isset($separateArray[$value['name']]['info'])?$separateArray[$value['name']]['info']:0);

                $rows[$key]['cnt'] = $value['cnt'];
                $rows[$key]['os_cnt'] = (isset($separateArray[$value['name']]['os'])?$separateArray[$value['name']]['os']:0);
                $rows[$key]['info_cnt'] = (isset($separateArray[$value['name']]['info'])?$separateArray[$value['name']]['info']:0);

                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getOutcomesCounts()
    {
        $filtersSql = $this->getFiltersSql();
        $rows = array();

        return $rows;
    }

    public function getAgencyReferrals()
    {
        $filtersSql = $this->getFiltersSql();
        $rows = array();

        return $rows;
    }

    public function getReferralIssueCounts()
    {
        
        $filtersSql = $this->getFiltersSql('referral_date');
        $sql = 'SELECT a.issue_id `name`,referred_to_type, count(*) cnt
            FROM
            (
                SELECT cr.issue_id,referred_to_type FROM child_referrals cr
                LEFT JOIN children c ON cr.child_id = c.id
                LEFT JOIN families ON c.parent_id = families.id
                '.$filtersSql['join_clause_2'].'
                WHERE 1 '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                .'UNION ALL
                SELECT fr.issue_id,referred_to_type FROM family_referrals fr
                LEFT JOIN families ON fr.family_id = families.id
                '.$filtersSql['join_clause_2'].'
                WHERE 1 '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
                UNION ALL 
                SELECT cr.issue_id ,referred_to_type FROM contact_referrals cr
                LEFT JOIN contacts ON cr.contact_id = contacts.id 
                LEFT JOIN family_provider fp ON fp.contact_id = contacts.id 
                LEFT JOIN families  ON fp.family_id = families.id 
                '.$filtersSql['join_clause_3'].'
                WHERE 1 '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            ) a
            GROUP BY issue_id,referred_to_type
            ORDER BY IF(issue_id != "", "1", "2") ASC, issue_id,referred_to_type';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        //echo $sql;die;
        $rows = array();
        $total = 0;
        $grandTotal = 0;
        $osgrandTotal = 0;
        $infograndTotal = 0;
        $separateArray = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            if( !isset( $separateArray[$row['name']] ) ){ 
                $rows[] = $row;
                $separateArray[$row['name']] = array();                
            }
            $grandTotal += $row['cnt'];
            if( $row['referred_to_type'] == 'os'){
                $osgrandTotal += $row['cnt'];
                $separateArray[$row['name']]['os'] = $row['cnt'];
            }
            else{
                $infograndTotal += $row['cnt'];
                $separateArray[$row['name']]['info'] = $row['cnt'];
            } 
            
        }

        
        if (count($rows)) {
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            $totals['cnt'] = $grandTotal;
            $totals['os_cnt'] = $osgrandTotal;
            $totals['info_cnt'] = $infograndTotal;
            $setting = new Setting();             
            foreach ($rows as $key => $value) {
                $rows[$key]['name'] = $setting->getSettingById($value['name']); //191016
                $value['cnt'] = (isset($separateArray[$value['name']]['os'])?$separateArray[$value['name']]['os']:0) + (isset($separateArray[$value['name']]['info'])?$separateArray[$value['name']]['info']:0);

                $rows[$key]['cnt'] = $value['cnt'];
                $rows[$key]['os_cnt'] = (isset($separateArray[$value['name']]['os'])?$separateArray[$value['name']]['os']:0);
                $rows[$key]['info_cnt'] = (isset($separateArray[$value['name']]['info'])?$separateArray[$value['name']]['info']:0);

                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getReferralServiceCounts()//1122,1129
    {
        $filtersSql = $this->getFiltersSql('referral_date');
        $sql = '
            SELECT `name`, count(*) cnt
            FROM
            (
                SELECT IF(ns.national_service IS NOT NULL, ns.national_service, settings.name) name FROM child_referrals cr
                LEFT JOIN children c ON cr.child_id = c.id
                LEFT JOIN families ON c.parent_id = families.id
                LEFT JOIN settings on cr.service_id = settings.id and settings.type = "referred_to_service"
                LEFT JOIN service_national_service ns ON settings.name = ns.service'.$filtersSql['join_clause_2'].'
                WHERE 1 '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                .' UNION ALL
                SELECT IF(ns.national_service IS NOT NULL, ns.national_service, settings.name) name FROM family_referrals fr
                LEFT JOIN families ON fr.family_id = families.id
                LEFT JOIN settings on fr.service_id = settings.id and settings.type = "referred_to_service"
                LEFT JOIN service_national_service ns ON settings.name = ns.service'.$filtersSql['join_clause_2'].'
                WHERE 1 '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            ) a
            GROUP BY `name`
            ORDER BY IF(`name` != "", "1", "2") ASC, `name`';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        $total = 0;
        $grandTotal = 0;
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $grandTotal += $row['cnt'];
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            $totals['cnt'] = $grandTotal;
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getGapCounts()
    {
        $filtersSql = $this->getFiltersSql('referral_date');
        $sql = '
            SELECT `name`, count(*) cnt
            FROM
            (
                SELECT settings.name FROM child_referrals cr
                LEFT JOIN children c ON cr.child_id = c.id
                LEFT JOIN families ON c.parent_id = families.id
                LEFT JOIN settings on cr.gap = settings.id and settings.type = "gaps"
                '.$filtersSql['join_clause_2'].'
                WHERE 1 AND outcomes NOT IN (1991, 1988) '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                .' UNION ALL
                SELECT settings.name FROM family_referrals fr
                LEFT JOIN families ON fr.family_id = families.id
                LEFT JOIN settings on fr.gap = settings.id and settings.type = "gaps"
                '.$filtersSql['join_clause_2'].'
                WHERE 1 AND outcomes NOT IN (1991, 1988) '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            ) a
            GROUP BY `name`
            ORDER BY IF(`name` != "", "1", "2") ASC, `name`';

        /* Query before chagining text to setting IDs 191016
        $sql = '
            SELECT `name`, count(*) cnt
            FROM
            (
                SELECT settings.name FROM child_referrals cr
                LEFT JOIN children c ON cr.child_id = c.id
                LEFT JOIN families ON c.parent_id = families.id
                LEFT JOIN settings on cr.gap = settings.id and settings.type = "gaps"
                WHERE 1 AND outcomes NOT IN ("Information Received", "Connected") '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                .' UNION ALL
                SELECT settings.name FROM family_referrals fr
                LEFT JOIN families ON fr.family_id = families.id
                LEFT JOIN settings on fr.gap = settings.id and settings.type = "gaps"
                WHERE 1 AND outcomes NOT IN ("Information Received", "Connected") '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            ) a
            GROUP BY `name`
            ORDER BY IF(`name` != "", "1", "2") ASC, `name`';
            */
        //echo $sql;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        $total = 0;
        $grandTotal = 0;
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $grandTotal += $row['cnt'];
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            $totals['cnt'] = $grandTotal;
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getBarrierCounts()
    {
        $filtersSql = $this->getFiltersSql('referral_date');
        $sql = '
            SELECT `name`, count(*) cnt
            FROM
            (
                SELECT settings.name FROM child_referrals cr
                LEFT JOIN children c ON cr.child_id = c.id
                LEFT JOIN families ON c.parent_id = families.id
                LEFT JOIN settings on cr.barrier = settings.id and settings.type = "barriers"
                '.$filtersSql['join_clause_2'].'
                WHERE 1 AND outcomes NOT IN (1991, 1988) '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                .' UNION ALL
                SELECT settings.name FROM family_referrals fr
                LEFT JOIN families ON fr.family_id = families.id
                LEFT JOIN settings on fr.barrier = settings.id and settings.type = "barriers"
                '.$filtersSql['join_clause_2'].'
                WHERE 1 AND outcomes NOT IN (1991, 1988) '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            ) a
            GROUP BY `name`
            ORDER BY IF(`name` != "", "1", "2") ASC, `name`';
        /* Query before chagining text to setting IDs 191016
        $sql = '
            SELECT `name`, count(*) cnt
            FROM
            (
                SELECT settings.name FROM child_referrals cr
                LEFT JOIN children c ON cr.child_id = c.id
                LEFT JOIN families ON c.parent_id = families.id
                LEFT JOIN settings on cr.barrier = settings.id and settings.type = "barriers"
                WHERE 1 AND outcomes NOT IN ("Information Received", "Connected") '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                .' UNION ALL
                SELECT settings.name FROM family_referrals fr
                LEFT JOIN families ON fr.family_id = families.id
                LEFT JOIN settings on fr.barrier = settings.id and settings.type = "barriers"
                WHERE 1 AND outcomes NOT IN ("Information Received", "Connected") '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            ) a
            GROUP BY `name`
            ORDER BY IF(`name` != "", "1", "2") ASC, `name`';
            */
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        $total = 0;
        $grandTotal = 0;
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $grandTotal += $row['cnt'];
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            $totals['cnt'] = $grandTotal;
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getReasonForClosingCounts()
    {
        $filtersSql = $this->getFiltersSql('referral_date');
        $sql = '
            SELECT `name`, count(*) cnt FROM
            (
                SELECT DISTINCT id, `name`
                FROM
                (
                    SELECT families.id, settings.name FROM child_referrals cr
                    JOIN children c ON cr.child_id = c.id
                    JOIN families ON c.parent_id = families.id
                    JOIN startend se on families.id = se.parent_id
                    JOIN settings on se.reason = settings.name and settings.type = "file_closed_reason"
                    '.$filtersSql['join_clause_2'].'
                    WHERE 1 '
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                    .' UNION ALL
                    SELECT families.id, settings.name FROM family_referrals fr
                    JOIN families ON fr.family_id = families.id
                    JOIN startend fse on fr.family_id = fse.parent_id
                    JOIN settings on fse.reason = settings.name and settings.type = "file_closed_reason"
                    '.$filtersSql['join_clause_2'].'
                    WHERE 1 '
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
                ) a
                ORDER BY IF(`name` != "", "1", "2") ASC, `name`
            ) b
            GROUP BY `name`
            ORDER BY IF(`name` != "", "1", "2") ASC, `name`';
        //echo $sql;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        $total = 0;
        $grandTotal = 0;
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $grandTotal += $row['cnt'];
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            $totals['cnt'] = $grandTotal;
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getChildStatusCounts()//1350
    {   
        $filtersSql = $this->getFiltersSql();
 
        $sql = '
        SELECT * FROM
        (
            SELECT families.status, count(*) cnt FROM `children`'.'
            LEFT JOIN families on parent_id = families.id'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE families.id is not null'
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            GROUP BY families.status WITH ROLLUP
        ) t
        ORDER BY
            IF(status != "" && status IS NOT NULL, "1", IF(status IS NOT NULL, "2", "3")) ASC,
            Length(status) ASC, status ASC';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['status'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getRecurringChildCounts()
    {
        $filtersSql = $this->getFiltersSql();
        $sql = '
            SELECT * FROM
            (
                SELECT
                    "Recurring" name, count(*) cnt
                FROM
                    (
                    SELECT
                        families.id, count(*) cnt
                    FROM `families`
                    JOIN startend ON startend.parent_id = families.id
                    '.$filtersSql['join_clause_2'].'
                    WHERE 1'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
                    GROUP BY families.id
                    HAVING cnt >= 2
                    ) f1
                    JOIN children ON f1.id = children.parent_id
                    UNION ALL
                    SELECT
                        "Total" name, count(*) cnt
                    FROM
                        `children`
                    LEFT JOIN families on parent_id = families.id'
                    .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
                    WHERE families.id is not null'
                    .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
                    GROUP BY 1
            ) a';
            //echo $sql; exit;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getChildIssueCounts()//1436
    {
        $filtersSql = $this->getFiltersSql();

        $sql = 'SELECT * FROM
        (
        SELECT issue.name name, count(*) cnt
        FROM children c
        LEFT JOIN families families on c.parent_id = families.id
        LEFT JOIN settings issue ON c.issue_id = issue.id and issue.type = "child_issues"'
        .$filtersSql['join_clause'].$filtersSql['join_clause_2'].
        ' WHERE families.id is NOT NULL'
        .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
        .' GROUP BY c.`issue_id` WITH ROLLUP
        ) t';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            usort($rows, array('Hmg\Models\Counts', 'cmp'));
            $first = array_shift($rows);
            array_push($rows, $first);
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public static function cmp($a, $b)
    {
        return strcmp($a['name'], $b['name']);
    }

    public function getChildAgeCounts()//1478
    {
        $filtersSql = $this->getFiltersSql();
        $sql = 'SELECT * FROM
        (
        SELECT IF(
            FLOOR(DATEDIFF(CURDATE(), birth_date) / 365) >= 9,
                "9+", CAST(FLOOR(DATEDIFF(CURDATE(), birth_date) / 365) as CHAR))
                AS name, count(*) cnt
        FROM children
        LEFT JOIN families on parent_id = families.id'
        .$filtersSql['join_clause'].$filtersSql['join_clause_2'].
        ' WHERE families.id is NOT NULL'
        .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
        .' GROUP BY IF(
                FLOOR(DATEDIFF(CURDATE(), birth_date) / 365) >= 9,
                    "9+",
                    CAST(FLOOR(DATEDIFF(CURDATE(), birth_date) / 365) AS CHAR)
                ) WITH ROLLUP
        ) t
        ORDER BY
        IF(`name` != "" && `name` IS NOT NULL, "1", IF(`name` IS NOT NULL, "2", "3")) ASC,
        `name` ASC, cnt asc';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getChildAgeCountsAtStart()
    {
        $filtersSql = $this->getFiltersSql();
        $sql = '
        SELECT * FROM
        (
            SELECT
                IF(FLOOR(DATEDIFF(startend.start_date, birth_date) / 365.25) >= 9, "9+",
                    IF(FLOOR(DATEDIFF(startend.start_date, birth_due_date) / 365.25) < 0, "-Prenatal",
                        IF(FLOOR(DATEDIFF(startend.start_date, birth_date) / 365.25) < 1, "0-12 months",
                            IF(CAST(FLOOR(DATEDIFF(startend.start_date, birth_date) / 365.25) as CHAR),  CAST(FLOOR(DATEDIFF(startend.start_date, birth_date) / 365.25) as CHAR), "Uncoded")
                        )
                    )
                ) AS name,
                count(*) cnt
            FROM
                children
            JOIN families ON parent_id = families.id
            LEFT JOIN (
                SELECT parent_id, min(start_date) start_date
                FROM startend
                GROUP BY parent_id
            ) startend ON startend.parent_id = families.id
            '.$filtersSql['join_clause_2'].'
            WHERE 1 '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
                GROUP BY name WITH ROLLUP
        ) t
        WHERE 1
        ORDER BY
            IF(`name` != "" && `name` IS NOT NULL, "1", IF(`name` IS NOT NULL, "2", "3")) ASC,
            `name` ASC';
        //echo $sql;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getChildCountyCounts()//1573
    {
        $filtersSql = $this->getFiltersSql();
        $sql = '
        SELECT
            co.name `name`, count(*) cnt
        FROM
            children c
        JOIN
            families ON c.parent_id = families.id
        JOIN
            county_zipcodes cz ON families.zip = cz.zip_code
        JOIN
            settings co ON (cz.county_id = co.id AND co.type = "county")
            /*counties co ON cz.id = co.id*/'
        .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
        WHERE 1'
        .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
        .' GROUP BY families.county WITH ROLLUP';

        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = $nrows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if(!empty($rows)) {
            $count = count($rows);
            foreach($rows as $k=>$r) {
                if($k > $count-2)
                    break;

                $is_found = false;
                if(!empty($nrows)) {
                    foreach($nrows as $key=>$nrow) {
                        $is_found = false;
                        if(isset($nrow['name']) && $r['name'] == $nrow['name']){
                            $nrows[$key] = array(
                                'name' => $r['name'], 
                                'cnt' => $nrow['cnt'] + $r['cnt']
                            );
                            $is_found = true;
                            break;
                        }
                    }
                    if(!$is_found)
                        $nrows[] = $r;
                    
                } else {
                    $nrows[] = $r;
                }
            }
        }
        $outOfCounty_cnt = $this->getChildOutOfCountyCounts(true);//27-10
        if(empty($outOfCounty_cnt)) {
            $outOfCounty_cnt = 0;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $rows = $nrows;
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            
            $outOfCounty['name'] = 'Out of State';
            $outOfCounty['cnt']  = $outOfCounty_cnt;
            $totals['cnt'] = $totals['cnt'] + $outOfCounty_cnt;
            $outOfCounty['percent'] = round(($outOfCounty_cnt / $totals['cnt']) * 100, 2);
            array_push($rows, $outOfCounty);
            array_push($rows, $totals);
        } else {
            $totals = array();
            if(!empty($outOfCounty_cnt)) {
                $totals['name'] = 'Totals';
                $totals['cnt']  = $outOfCounty_cnt;
                $totals['percent'] = '100';

                $outOfCounty['name'] = 'Out of State';
                $outOfCounty['cnt']  = $outOfCounty_cnt;
                $outOfCounty['percent'] = round(($outOfCounty_cnt / $totals['cnt']) * 100, 2);
                $rows = array($outOfCounty, $totals);
            }
        }

        return $rows;
    }

    public function getSuccessfulReferralCounts()//1657
    {
        //fetch referral_outcomes with Connected, Information Received
        $sql_ref = 'Select GROUP_CONCAT(id) as ids from settings where type="referral_outcomes" and LOWER(name) IN ("connected", "information received")';
        $rs_ref  = mysql_query($sql_ref) or die(mysql_error().$sql_ref);
        $row_ref = mysql_fetch_array($rs_ref, MYSQL_ASSOC);
        $referral_outcomes = (isset($row_ref['ids']) && !empty($row_ref['ids']))
                ? $row_ref['ids'] : '1988, 1991';
        $filtersSql = $this->getFiltersSql();
        $sql = '
        SELECT CONCAT(IF(cnt1 > 9, "10+", CAST(cnt1 AS CHAR)), " referrals") `name`, count(cnt1) cnt
        FROM
        (
            SELECT count(*) cnt1
            FROM child_referrals cr
            JOIN children c ON c.id = cr.child_id
            JOIN families on c.parent_id = families.id'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].
            ' WHERE outcomes IN (1988, 1991) '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            GROUP BY child_id
            HAVING cnt1 >= 1
        ) t
        GROUP BY IF(cnt1 >  9, "10+", cnt1)
        ORDER BY cnt1 ASC';
        /* Query before chagining text to setting IDs 191016
        $sql = '
        SELECT CONCAT(IF(cnt1 > 9, "10+", CAST(cnt1 AS CHAR)), " referrals") `name`, count(cnt1) cnt
        FROM
        (
            SELECT count(*) cnt1
            FROM child_referrals cr
            JOIN children c ON c.id = cr.child_id
            JOIN families on c.parent_id = families.id'
            .$filtersSql['join_clause'].
            ' WHERE outcomes IN ("Connected","Information Received") '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            GROUP BY child_id
            HAVING cnt1 >= 1
        ) t
        GROUP BY IF(cnt1 >  9, "10+", cnt1)
        ORDER BY cnt1 ASC';
        */
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        $totals = array('name' => 'Totals', 'cnt' => 0, 'percent' => '100');
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
            $totals['cnt'] += $row['cnt'];
        }
        if (count($rows)) {
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getChildReferralOutcomeCounts()//1708
    {
        $filtersSql = $this->getFiltersSql();
        $sql = '
        SELECT outcomes name, count(*) cnt
        FROM child_referrals cr
        JOIN children c ON c.id = cr.child_id
        JOIN families on c.parent_id = families.id'
        .$filtersSql['join_clause'].$filtersSql['join_clause_2'].
        ' WHERE 1 '
        .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
        GROUP BY outcomes
        ORDER BY
            IF(`name` != "" && `name` IS NOT NULL, "1", IF(`name` IS NOT NULL, "2", "3")) ASC,
            `name` ASC';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        $totals = array('name' => 'Totals', 'cnt' => 0, 'percent' => '100');
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
            $totals['cnt'] += $row['cnt'];
        }
        if (count($rows)) {
            $setting = new Setting(); //191016
            foreach ($rows as $key => $value) {
                $rows[$key]['name'] = $setting->getSettingById($value['name']); //191016
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getWhoCalledCounts()//1742
    {
        $filtersSql = $this->getFiltersSql();
        $sql = 'SELECT * FROM
        (
        SELECT who_called_id, `name`, count(*) cnt
        FROM families
        LEFT JOIN settings on who_called_id = settings.id and type = "who_called"'
        .$filtersSql['join_clause'].$filtersSql['join_clause_2'].
        ' WHERE 1 '
        .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
        .' GROUP BY `name` WITH ROLLUP
        ) t
        ORDER BY
        IF(`name` != "" && `name` IS NOT NULL, "1", IF(`name` IS NOT NULL, "2", "3")) ASC,
        `name` ASC, cnt asc';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getRaceCounts()//1776
    {
        $filtersSql = $this->getFiltersSql();
        $sql = 'SELECT * FROM
        (
        SELECT race_id, `name`, count(*) cnt
        FROM families
        LEFT JOIN settings on race_id = settings.id'
        .$filtersSql['join_clause'].$filtersSql['join_clause_2'].
        ' WHERE 1 '
        .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
        .' GROUP BY `name` WITH ROLLUP
        ) t
        ORDER BY
        IF(`name` != "" && `name` IS NOT NULL, "1", IF(`name` IS NOT NULL, "2", "3")) ASC,
        `name` ASC, cnt asc';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getEthnicityCounts()//1810
    {
        $filtersSql = $this->getFiltersSql();
        $sql = 'SELECT * FROM
        (
        SELECT ethnicity_id, `name`, count(*) cnt
        FROM families
        LEFT JOIN settings on ethnicity_id = settings.id'
        .$filtersSql['join_clause'].$filtersSql['join_clause_2'].
        ' WHERE 1 '
        .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
        .' GROUP BY `name` WITH ROLLUP
        ) t
        ORDER BY
        IF(`name` != "" && `name` IS NOT NULL, "1", IF(`name` IS NOT NULL, "2", "3")) ASC,
        `name` ASC, cnt asc';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getCallReasonCounts()//1844,1853
    {
        $filtersSql = $this->getFiltersSql();

        $sql = '
         SELECT name, cnt from (
            SELECT  `name`, count(*) cnt
            FROM families
            LEFT JOIN settings ON call_reason_id = settings.id and type = "call_reason"'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE 1 '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            GROUP BY settings.id
            ORDER BY if(`name` IS NOT NULL AND `name` != "", 1, IF(`name` IS NOT NULL, 2, 3)) ASC, `name` ASC
        ) t
        UNION
        SELECT  "Total" `name`, count(*) cnt
        FROM families '
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
        WHERE 1 '
        .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
        GROUP BY 1';
        //echo $sql;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getHowHeardCounts()
    {
        $filtersSql = $this->getFiltersSql();
        $sql = 'SELECT * FROM
        (
        SELECT family_heard_id, `name`, count(*) cnt
        FROM families
        LEFT JOIN settings on family_heard_id = settings.id and type = "how_heard_category"'
        .$filtersSql['join_clause'].$filtersSql['join_clause_2'].
        ' WHERE 1 '
        .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
        .' GROUP BY `name` WITH ROLLUP
        ) t
        ORDER BY
        IF(`name` != "" && `name` IS NOT NULL, "1", IF(`name` IS NOT NULL, "2", "3")) ASC,
        `name` ASC, cnt asc';
        


        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

public function getHowHeardCountsDrill($name = null, $count = false, $start = 0, $is_page = true)
    {
        $filtersSql = $this->getFiltersSql();
        $filtersSql['join_clause_2'] .= " JOIN users u ON u.id = families.hmg_worker ";        

        $sql = '';

        if($count) {
            $sql .= ' SELECT count(*) as cnt FROM ';
        } else {
            $sql .= ' SELECT t.*, settings.name as language FROM ';
        }
        
        $sql .= "
        (
            SELECT
                family_heard_id,
                families.first_name_1,
                families.last_name_1,
                families.language_id,
                families.city,
                families.status,
                u.hmg_worker,
                families.id
            FROM
                families        
                LEFT JOIN settings ON family_heard_id = settings.id ".
                $filtersSql['join_clause'].$filtersSql['join_clause_2'];
                if(strtolower($name) == "uncoded")
                {
                    $sql.=" Where (settings.name is null OR settings.name = '') ";
                    $sql.=($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : ' ');
                } elseif($name=="Totals") {
                    $sql.=($filtersSql['filter_by'] ? ' Where '.$filtersSql['filter_by'] : ' ');
                }
                else
                {
                    $sql.=" Where settings.type='how_heard_category' and settings.name='".$name ."'";
                    $sql.=($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : ' ');
                } 

                 $sql.="
        ) t 
        LEFT JOIN settings ON language_id = settings.id";

        if($count) {   $sql.='  '; } 
        else {  
            if(!empty($this->_order_by)) {
                $sql .= $this->_order_by;
            } else 
                $sql.=' order by first_name_1'; 
        }
        
        if(!$count && $is_page)
        {
            $sql.="  limit ".$start.", 50 ";
        }
       
        $rows = array();
        $rs = mysql_query( $sql ) or die( mysql_error().$sql );

        if( $count ){

            $row = mysql_fetch_array( $rs, MYSQL_ASSOC );
            return $row['cnt'];
        }
        else{
                 
            while( $row = mysql_fetch_array( $rs, MYSQL_ASSOC ) ) {
                $rows[] = $row;
            }
            return $rows;
       }

    }

public function getCallReasonDrill($name = null, $count = false, $start = 0, $is_page = true)
    {
        $filtersSql = $this->getFiltersSql();
        $filtersSql['join_clause_2'] .= " JOIN users u ON u.id = families.hmg_worker ";
        $sql = '';

        if($count) {
            $sql .= ' SELECT count(*) as cnt FROM ';
        } else {
            $sql .= ' SELECT t.*, settings.name as language FROM ';
        }
        
        $sql .= "
        (
            SELECT
                family_heard_id,
                families.first_name_1,
                families.last_name_1,
                families.language_id,
                families.city,
                families.status,
                u.hmg_worker,
                families.id
            FROM
                families        
                 LEFT JOIN settings ON call_reason_id = settings.id ".
                $filtersSql['join_clause'].$filtersSql['join_clause_2'];
                if(strtolower($name) == "uncoded")
                {
                    $sql.=" Where (settings.name is null OR settings.name = '')";
                    $sql.=($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : ' ');
                } elseif($name=="Totals") {
                    $sql.=($filtersSql['filter_by'] ? ' Where '.$filtersSql['filter_by'] : ' ');
                }
                else {
                    $sql.=" Where settings.name='".$name ."'";
                    $sql.=($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : ' ');
                } 
                $sql.="
        ) t 
        LEFT JOIN settings ON language_id = settings.id 
        ";

        if($count) {   $sql.='  '; } 
        else {  
            if(!empty($this->_order_by)) {
                $sql .= $this->_order_by;
            } else 
                $sql.=' order by first_name_1 asc'; 
        }

        if(!$count && $is_page) {  $sql.=" limit ".$start.", 50"; };

        
        $rows = array();
        $rs = mysql_query( $sql ) or die( mysql_error().$sql );

        if( $count ){

            $row = mysql_fetch_array( $rs, MYSQL_ASSOC );
            return $row['cnt'];
        }
        else{
                 
            while( $row = mysql_fetch_array( $rs, MYSQL_ASSOC ) ) {
                $rows[] = $row;
            }
            return $rows;
       }

    }



    public function getChildCounts()
    {
        $filtersSql = $this->getFiltersSql();
        $sql = 'SELECT * FROM
        (
        SELECT IF(
            FLOOR(DATEDIFF(CURDATE(), birth_date) / 365) >= 9,
                "9+", CAST(FLOOR(DATEDIFF(CURDATE(), birth_date) / 365) as CHAR))
                AS name, count(*) cnt
        FROM children
        LEFT JOIN families on parent_id = families.id'
        .$filtersSql['join_clause'].
        ' WHERE families.id is NOT NULL'
        .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
        .' GROUP BY IF(
                FLOOR(DATEDIFF(CURDATE(), birth_date) / 365) >= 9,
                    "9+",
                    CAST(FLOOR(DATEDIFF(CURDATE(), birth_date) / 365) AS CHAR)
                ) WITH ROLLUP
        ) t
        ORDER BY
        IF(`name` != "" && `name` IS NOT NULL, "1", IF(`name` IS NOT NULL, "2", "3")) ASC,
        `name` ASC, cnt asc';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }
    /** updated on 020816 to include uncoded data **/
    public function getScreeningCounts($uncoded = false)
    {
        $filtersSql = $this->getFiltersSql('date_sent');
        $uncoded_query = '';
        if($uncoded) {
            $uncoded_query = 'UNION ALL
        (SELECT
            "Uncoded" AS `name`,
            COUNT(*) cnt
        FROM
            child_developmental_screenings
            JOIN children ON children.id = child_developmental_screenings.child_id
            JOIN families ON families.id = children.parent_id
            '.$filtersSql['join_clause_2'].'
        WHERE
            (`type` = "ASQ:SE" && score NOT IN ("White", "Grey", "Black") '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').') OR (`type` = "ASQ-3" && score NOT IN ("White", "Grey", "Black") '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').' ))';
        }
        $sql = '
        (SELECT
            "ASQ-3 White" AS `name`,
            SUM(score = "White") cnt
        FROM
            child_developmental_screenings
            JOIN children ON children.id = child_developmental_screenings.child_id
            JOIN families ON families.id = children.parent_id
            '.$filtersSql['join_clause_2'].'
        WHERE
            `type`  = "ASQ-3"'
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
            .')
        UNION ALL
        (SELECT
            "ASQ-3 Grey" AS `name`,
            SUM(score = "Grey") cnt
        FROM
            child_developmental_screenings
            JOIN children ON children.id = child_developmental_screenings.child_id
            JOIN families ON families.id = children.parent_id
            '.$filtersSql['join_clause_2'].'
        WHERE
            `type`  = "ASQ-3"'
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
            .')
        UNION ALL
        (SELECT
            "ASQ-3 Black" AS `name`,
            SUM(score = "Black") cnt
        FROM
            child_developmental_screenings
            JOIN children ON children.id = child_developmental_screenings.child_id
            JOIN families ON families.id = children.parent_id
            '.$filtersSql['join_clause_2'].'
        WHERE
            `type`  = "ASQ-3"'
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
            .')
        UNION ALL
        (SELECT
            "ASQ:SE" AS `name`,
            SUM(`type` = "ASQ:SE") cnt
        FROM
            child_developmental_screenings
            JOIN children ON children.id = child_developmental_screenings.child_id
            JOIN families ON families.id = children.parent_id
            '.$filtersSql['join_clause_2'].'
        WHERE
            `type`  = "ASQ:SE"'
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
            .')
        '.$uncoded_query.'
        UNION ALL
        (SELECT
            "TOTAL" AS `name`,
            COUNT(*) cnt
        FROM
            child_developmental_screenings
            JOIN children ON children.id = child_developmental_screenings.child_id
            JOIN families ON families.id = children.parent_id
            '.$filtersSql['join_clause_2'].'
        WHERE
            (`type` = "ASQ:SE" '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').') OR (`type` = "ASQ-3" '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').' ))
        ';
        //score IN ("White", "Grey", "Black")'
        //echo $sql;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        //echo "<pre>";print_r($rows);
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            $total_all = 0;
            foreach ($rows as $key => $value) {
                if (!empty($totals['cnt'])) {
                    $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
                } else {
                    $rows[$key]['percent'] = 0;
                }
                $total_all += $value['cnt'];
            }
            $totals['cnt'] = $total_all;
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getAsqFrequencyCounts()
    {
        $filtersSql = $this->getFiltersSql('date_sent');
        $sql = 'SELECT  if(cnt = 1, "1 ASQ", if(cnt = 2, "2 ASQs", if(cnt >=3 and cnt <= 4, "3-4 ASQs", "5+ ASQs"))) name, count(cnt) cnt  FROM
            (
                SELECT type, count(*) cnt FROM `child_developmental_screenings`
                JOIN children ON children.id = child_developmental_screenings.child_id
                JOIN families ON families.id = children.parent_id'
                .$filtersSql['join_clause_2']
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                .' WHERE type = "ASQ-3"
                GROUP BY child_id, type
            ) asq_counts GROUP BY name
            WITH ROLLUP';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getAsqSeCounts()
    {
        $filtersSql = $this->getFiltersSql('date_sent');
        $sql = '
        SELECT if(asq_month = "", "Uncoded", CONCAT(asq_month, " months")) name, cnt  FROM
        (
            SELECT asq_month, count(*) cnt FROM `child_developmental_screenings`
            JOIN children ON children.id = child_developmental_screenings.child_id
            JOIN families ON families.id = children.parent_id'
            .$filtersSql['join_clause_2']
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            WHERE type = "ASQ:SE"
            GROUP BY asq_month
            WITH ROLLUP
        ) asq_counts
        ORDER BY IF(asq_month IS NULL, 3, IF(asq_month = "", 2, 1)), LENGTH(asq_month), asq_month';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getAsq3Counts()
    {
        $filtersSql = $this->getFiltersSql('date_sent');
        $sql = '
        SELECT if(asq_month = "", "Uncoded", CONCAT(asq_month, " months")) name, cnt  FROM
        (
            SELECT asq_month, count(*) cnt FROM `child_developmental_screenings`
            JOIN children ON children.id = child_developmental_screenings.child_id
            JOIN families ON families.id = children.parent_id'
            .$filtersSql['join_clause_2']
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            WHERE type = "ASQ-3"
            GROUP BY asq_month
            WITH ROLLUP
        ) asq_counts
        ORDER BY IF(asq_month IS NULL, 3, IF(asq_month = "", 2, 1)), LENGTH(asq_month), asq_month';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getAsq3DomainCounts()
    {
        $filtersSql = $this->getFiltersSql('date_sent');
        $sql = '
        (SELECT
            "Communication" AS `domain`,
            SUM(communication = "White") White,
            SUM(communication = "Grey") Grey,
            SUM(communication = "Black") Black
        FROM
            child_developmental_screenings
            JOIN children ON children.id = child_developmental_screenings.child_id
            JOIN families ON families.id = children.parent_id'
            .$filtersSql['join_clause_2']
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
            .'
        WHERE
            communication  != "")
        UNION ALL
        (SELECT
            "Fine Motor" AS `domain`,
            SUM(fine_motor = "White") White,
            SUM(fine_motor = "Grey") Grey,
            SUM(fine_motor = "Black") Black
        FROM
            child_developmental_screenings
            JOIN children ON children.id = child_developmental_screenings.child_id
            JOIN families ON families.id = children.parent_id'
            .$filtersSql['join_clause_2']
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
            .'
        WHERE
            fine_motor  != "")
        UNION ALL
        (SELECT
            "Gross Motor" AS `domain`,
            SUM(gross_motor = "White") White,
            SUM(gross_motor = "Grey") Grey,
            SUM(gross_motor = "Black") Black
        FROM
            child_developmental_screenings
            JOIN children ON children.id = child_developmental_screenings.child_id
            JOIN families ON families.id = children.parent_id'
            .$filtersSql['join_clause_2']
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
            .'
        WHERE
            gross_motor  != "")
        UNION ALL
        (SELECT
            "Problem Solving" AS `domain`,
            SUM(problem_solving = "White") White,
            SUM(problem_solving = "Grey") Grey,
            SUM(problem_solving = "Black") Black
        FROM
            child_developmental_screenings
            JOIN children ON children.id = child_developmental_screenings.child_id
            JOIN families ON families.id = children.parent_id'
            .$filtersSql['join_clause_2']
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
            .'
        WHERE
            problem_solving  != "")
        UNION ALL
        (SELECT
            "Personal Social" AS `domain`,
            SUM(personal_social = "White") White,
            SUM(personal_social = "Grey") Grey,
            SUM(personal_social = "Black") Black
        FROM
            child_developmental_screenings
            JOIN children ON children.id = child_developmental_screenings.child_id
            JOIN families ON families.id = children.parent_id'
            .$filtersSql['join_clause_2']
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
            .'
        WHERE
            personal_social  != "")
        ';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function getFamilyScreeningCounts($uncoded = false)
    {
       $filtersSql = $this->getFiltersSql('date_sent');
          $sql = 'SELECT   types as name, sum(cnt) cnt  FROM
            (
                SELECT settings.name as types, count(*) cnt FROM family_screenings
                left JOIN families ON families.id = family_screenings.family_id 
                left join settings on settings.id=family_screenings.type '
                .$filtersSql['join_clause_2']
                .' WHERE 1 '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                .' 
                GROUP BY family_screenings.type
            ) asq_counts GROUP BY name
            WITH ROLLUP';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getFamilyAsqFrequencyCounts()
    {
        $filtersSql = $this->getFiltersSql('date_sent');
        $sql = 'SELECT  if(cnt = 1, "1 ", if(cnt = 2, "2 ", if(cnt >=3 and cnt <= 4, "3-4 ", "5+ "))) name, count(cnt) cnt  FROM
            (
                SELECT settings.name as types, count(*) cnt FROM family_screenings
                left JOIN families ON families.id = family_screenings.family_id 
                left join settings on settings.id=family_screenings.type '
                .$filtersSql['join_clause_2']
                .' WHERE 1 '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                .' 
                GROUP BY family_id
            ) asq_counts GROUP BY name
            WITH ROLLUP';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getFamilyAsqSeCounts()
    {
        $filtersSql = $this->getFiltersSql('date_sent');
        $sql = 'SELECT   types as name, sum(cnt) cnt  FROM
            (
                SELECT settings.name as types, count(*) cnt FROM family_screenings
                left JOIN families ON families.id = family_screenings.family_id
                left join settings on settings.id=family_screenings.type_interval '
                .$filtersSql['join_clause_2']
                .' WHERE 1 '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                .' 
                GROUP BY type_interval
            ) asq_counts GROUP BY name
            WITH ROLLUP';
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

   
    public function getVolunteerStatusCounts($sqlOnly = false)
    {
        $filtersSql = $this->getFiltersSql('date_field', 'volunteers');
        $sql = 'SELECT * FROM
                    (
                        SELECT status, count(*) cnt FROM `volunteers`'
                        .$filtersSql['join_clause'].
                        ' WHERE 1 '
                        .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                        .' GROUP BY status WITH ROLLUP
                    ) t
                    ORDER BY
                        IF(status != "" && status IS NOT NULL, "1", "2") ASC';
        if ($sqlOnly) {
            return $sql;
        }
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['status'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getVolunteerSpecialtyCounts($sqlOnly = false)
    {
        $filtersSql = $this->getFiltersSql('date_field', 'volunteers');
        $sql = 'SELECT name, SUM(cnt) cnt FROM (
            SELECT "Family Events" name, SUM(IF(family_event != "0", 1, 0)) cnt FROM `volunteers` WHERE 1 GROUP BY (1=1)
            UNION ALL
            SELECT "Data Entry" name, SUM(IF(data_entry != "0", 1, 0)) cnt FROM `volunteers` WHERE 1 GROUP BY (1=1)
            UNION ALL
            SELECT "Care Coordination" name, SUM(IF(care_coordination != "0", 1, 0)) cnt FROM `volunteers` WHERE 1 GROUP BY (1=1)
            UNION ALL
            SELECT "Special Projects" name, SUM(IF(special_projects != "0", 1, 0)) cnt FROM `volunteers` WHERE 1 GROUP BY (1=1)
            UNION ALL
            SELECT "Parent Mentor" name, SUM(IF(parent_mentor != "0", 1, 0)) cnt FROM `volunteers` WHERE 1 GROUP BY (1=1)
            UNION ALL
            SELECT "Eagle Scout" name, SUM(IF(eagle_scout != "0", 1, 0)) cnt FROM `volunteers` WHERE 1 GROUP BY (1=1)
        ) t
        GROUP BY name with rollup';
        if ($sqlOnly) {
            return $sql;
        }
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['status'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getVolunteerHourCounts($sqlOnly = false)
    {
        $filtersSql = $this->getFiltersSql('date_field', 'volunteers');
        $sql = 'SELECT * FROM
                    (
                        SELECT type name, SUM(hours) cnt FROM `volunteering`'
                        .' INNER JOIN volunteers ON volunteering.volunteer_id = volunteers.id'
                        .$filtersSql['join_clause'].
                        ' WHERE 1 '
                        .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
                        .' GROUP BY (name) WITH ROLLUP
                    ) t
                    ORDER BY
                        IF(name != "" && name IS NOT NULL, "1", "2") ASC, cnt DESC';
        if ($sqlOnly) {
            return $sql;
        }
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getProviderFaxCounts()
    {
        $filtersSql = $this->getFiltersSql();
        $sql = '
        SELECT * FROM
        (
            SELECT
                IF(fpr.fax_permission = "1",
                    "Providers Listed with<br />Permission to Fax",
                    IF(fpr.fax_permission IS NOT NULL,
                        "Providers Listed with<br />No Permission to Fax",
                        "No Provider Listed/No<br />Permission to Fax"
                    )
                ) name, count(*) cnt
            FROM
                families
                    LEFT JOIN
                family_provider fpr ON fpr.family_id = families.id'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].
            ' WHERE 1 '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            GROUP BY fpr.fax_permission
            WITH ROLLUP
        ) t

        ORDER BY IF(name = "1", 1, IF(name IS NOT NULL, 2, 3)), cnt';

        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getProviderRoleCounts()
    {
        $filtersSql = $this->getFiltersSql();
        $sql = '
        SELECT * FROM
        (
            SELECT s.name, count(*) cnt FROM providers p
            LEFT JOIN settings s ON s.id = p.role_id
            GROUP BY s.name
            WITH ROLLUP
        ) t
        ORDER BY IF(name IS NOT NULL, 1, 2), name ASC, cnt';

        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getProviderHowHeardCounts()
    {
        $filtersSql = $this->getFiltersSql();
        $sql = '
        SELECT * FROM
        (
            SELECT family_heard_id, `name`, count(*) cnt
            FROM families
            LEFT JOIN settings on family_heard_id = settings.id'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].
            /** comment temp to fetch all records 0816 **/
            /*' WHERE 1 AND settings.id IN (2, 3, 4, 5, 17, 24, 25, 27) '.
            ($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')*/
            ($filtersSql['filter_by'] ? ' WHERE '.$filtersSql['filter_by'] : '')
            .' GROUP BY `name` WITH ROLLUP
        ) t
        ORDER BY
        IF(`name` != "" && `name` IS NOT NULL, "1", IF(`name` IS NOT NULL, "2", "3")) ASC,
        `name` ASC, cnt asc';
        
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals = array_pop($rows);
            $totals['name'] = 'Totals';
            $totals['percent'] = '100';
            foreach ($rows as $key => $value) {
                if(empty($value['cnt']))
                    continue;
                $rows[$key]['percent'] = round(($value['cnt'] / $totals['cnt']) * 100, 2);
            }
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getProviderMostReferralCounts()
    {
        $filtersSql = $this->getFiltersSql();
        $sql = '
        SELECT * FROM
        (
            SELECT
                CONCAT_WS(" ", p.first_name, p.last_name) name, p.employer, count(*) cnt
            FROM
                family_provider fpr
                JOIN providers p ON fpr.provider_id = p.id
                JOIN families ON families.id = fpr.family_id'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].
            ' WHERE 1 AND fpr.provider_id != 370'
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
            GROUP BY fpr.provider_id
            ORDER BY cnt DESC
        ) t
        LIMIT 30';
        //echo $sql;die;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        $totalsCnt = 0;
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $totalsCnt += $row['cnt'];
            $rows[] = $row;
        }
        if (count($rows)) {
            $totals['name'] = 'Totals';
            $totals['employer'] = '';
            $totals['cnt'] = $totalsCnt;
            array_push($rows, $totals);
        }

        return $rows;
    }

    public function getChildDetails($status = null, $count = false, $start = 0, $is_page = true) {
        $filtersSql = $this->getFiltersSql();
        $filtersSql['join_clause_2'] .= " JOIN users u ON u.id = families.hmg_worker ";
        if($count) {
            $sql = '
        SELECT count(*) as count FROM';
        } else {
            $sql = '
            SELECT * FROM';
        }
        $sql .= '
        (
            SELECT families.status, children.id, children.first, children.last, children.gender FROM `children`'.'
            LEFT JOIN families on parent_id = families.id'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE families.id is not null'
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').'
        ) t ';
        $pos = strpos($filtersSql['filter_by'], 'status');
        if((!isset($filtersSql['filter_by']) || ($pos === false)) && !empty($status)) {
            if(strtolower($status) != 'totals')
                $sql .= ' WHERE LOWER(status)="'.strtolower($status).'" ';
        }

        if(!$count && !empty($this->_order_by) 
                && (trim($this->_order_by) != 'ORDER BY first_name_1 asc' && trim($this->_order_by) != 'ORDER BY first_name_1 desc')
                && (trim($this->_order_by) != 'ORDER BY hmg_worker asc' && trim($this->_order_by) != 'ORDER BY hmg_worker desc')
                && (trim($this->_order_by) != 'ORDER BY age asc' && trim($this->_order_by) != 'ORDER BY age desc')
                && (trim($this->_order_by) != 'ORDER BY birthdate asc' && trim($this->_order_by) != 'ORDER BY birthdate desc')
        ) {
            $sql .= $this->_order_by;
        }
        if(!$count && $is_page) {
            $sql .= ' LIMIT '.$start.', 50';
        }
        //echo $sql;
        $rs  = mysql_query($sql) or die(mysql_error().$sql);
        $rows = array();
        if($count) {
            $rs_c = mysql_fetch_assoc($rs);
            return $rs_c['count'];
        }
      
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $sql2 = "SELECT c.*, CONCAT_WS(' ', f.first_name_1, f.last_name_1) fname, 
                    u.hmg_worker, f.status as fstatus from children c 
                    LEFT JOIN families f ON f.id=c.parent_id 
                    INNER JOIN users u ON u.id = f.hmg_worker WHERE c.id='".$row['id']."'";

            $rs2  = mysql_query($sql2) or die(mysql_error().$sql2);
            $rows2 = array();
            while ($row2 = mysql_fetch_array($rs2, MYSQL_ASSOC)) {
                $rows[] = $row2;
            }
        }
        //echo "<pre>";print_r($rows);die;
        if(trim($this->_order_by) == 'ORDER BY first_name_1 asc') {
            $this->aasort($rows, "fname");
        } elseif (trim($this->_order_by) == 'ORDER BY first_name_1 desc') {
            $this->aasort($rows, "fname", 'desc');
        }
        if(trim($this->_order_by) == 'ORDER BY hmg_worker asc') {
            $this->aasort($rows, "hmg_worker");
        } elseif (trim($this->_order_by) == 'ORDER BY hmg_worker desc') {
            $this->aasort($rows, "hmg_worker", 'desc');
        }
        if(trim($this->_order_by) == 'ORDER BY age asc') {
            $this->aasort($rows, "birth_date");
        } elseif (trim($this->_order_by) == 'ORDER BY age desc') {
            $this->aasort($rows, "birth_date", 'desc');
        }
        if(trim($this->_order_by) == 'ORDER BY birthdate asc') {
            $this->aasort($rows, "birth_date");
        } elseif (trim($this->_order_by) == 'ORDER BY birthdate desc') {
            $this->aasort($rows, "birth_date", 'desc');
        }
        
        return $rows;
    }
    /**
     *  Sort arrays
     */
    function aasort (&$array, $key, $type = 'asc') {
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
    
    public function getChildIssueCountsDetail($issues = null, $count = false, $start = 0, $is_page = true)
    {
        $filtersSql = $this->getFiltersSql();
        $filtersSql['join_clause_2'] .= " JOIN users u ON u.id = families.hmg_worker ";
        if($count) {
            $sql = '
        SELECT count(*) as count FROM';
        } else {
            $sql = '
            SELECT * FROM';
        }
        $sql .= '
        (
        SELECT issue.name name, c.id, c.first, c.last, c.gender, families.status
        FROM children c
        LEFT JOIN families families on c.parent_id = families.id
        LEFT JOIN settings issue ON c.issue_id = issue.id and issue.type = "child_issues"'
        .$filtersSql['join_clause'].$filtersSql['join_clause_2'].
        ' WHERE families.id is NOT NULL ';
        if(strtolower($issues) == 'uncoded')
            $sql .= ' AND issue.name is NULL ';

        $sql .= ($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
        .' ) t';

        if(!empty($issues) && strtolower($issues) != 'totals' && strtolower($issues) != 'uncoded') {
            $sql .= ' WHERE LOWER(name)="'.strtolower($issues).'" ';
        }
        if(!$count && !empty($this->_order_by) 
                && (trim($this->_order_by) != 'ORDER BY first_name_1 asc' && trim($this->_order_by) != 'ORDER BY first_name_1 desc')
                && (trim($this->_order_by) != 'ORDER BY hmg_worker asc' && trim($this->_order_by) != 'ORDER BY hmg_worker desc')
                && (trim($this->_order_by) != 'ORDER BY age asc' && trim($this->_order_by) != 'ORDER BY age desc')
                && (trim($this->_order_by) != 'ORDER BY birthdate asc' && trim($this->_order_by) != 'ORDER BY birthdate desc')
        ) {
            $sql .= $this->_order_by;
        }
        if(!$count && $is_page) {
            $sql .= ' LIMIT '.$start.', 50';
        }

        $rs = mysql_query($sql) or die(mysql_error().$sql);
        if($count) {
            $rs_c = mysql_fetch_assoc($rs);
            return $rs_c['count'];
        }

        $rows = array();
    
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $sql2 = "SELECT c.*, CONCAT_WS(' ', f.first_name_1, f.last_name_1) fname, 
                    u.hmg_worker, f.status as fstatus from children c 
                    LEFT JOIN families f ON f.id=c.parent_id 
                    INNER JOIN users u ON u.id = f.hmg_worker WHERE c.id='".$row['id']."'";

            $rs2  = mysql_query($sql2) or die(mysql_error().$sql2);
            $rows2 = array();
            while ($row2 = mysql_fetch_array($rs2, MYSQL_ASSOC)) {
                $rows[] = $row2;
            }
        }
        if(trim($this->_order_by) == 'ORDER BY first_name_1 asc') {
            $this->aasort($rows, "fname");
        } elseif (trim($this->_order_by) == 'ORDER BY first_name_1 desc') {
            $this->aasort($rows, "fname", 'desc');
        }
        if(trim($this->_order_by) == 'ORDER BY hmg_worker asc') {
            $this->aasort($rows, "hmg_worker");
        } elseif (trim($this->_order_by) == 'ORDER BY hmg_worker desc') {
            $this->aasort($rows, "hmg_worker", 'desc');
        }
        if(trim($this->_order_by) == 'ORDER BY age asc') {
            $this->aasort($rows, "birth_date");
        } elseif (trim($this->_order_by) == 'ORDER BY age desc') {
            $this->aasort($rows, "birth_date", 'desc');
        }
        if(trim($this->_order_by) == 'ORDER BY birthdate asc') {
            $this->aasort($rows, "birth_date");
        } elseif (trim($this->_order_by) == 'ORDER BY birthdate desc') {
            $this->aasort($rows, "birth_date", 'desc');
        }
        return $rows;
    }
    public function getChildCountyCountsDetail($county = null, $countR = false, $start = 0, $is_page = true)
    {
        $filtersSql = $this->getFiltersSql();

        $filtersSql['join_clause_2'] .= " JOIN users u ON u.id = families.hmg_worker ";
        if(strtolower($county) == 'out of state') {
            $sql = '
            SELECT
                families.county `name`, count(*) cnt, GROUP_CONCAT(c.id) ids
            FROM
                families 
            JOIN
                children c ON c.parent_id = families.id '
                .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE LOWER(families.county) = "'.strtolower($county).'" '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '');
        } else {
            $sql = '
            SELECT
                co.name `name`, count(*) cnt, GROUP_CONCAT(c.id) ids
            FROM
                children c
            JOIN
                families ON c.parent_id = families.id
            JOIN
                county_zipcodes cz ON families.zip = cz.zip_code
            JOIN
                settings co ON (cz.county_id = co.id AND co.type = "county")
                /*counties co ON cz.id = co.id*/'
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE 1'
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '')
            .' GROUP BY families.county WITH ROLLUP';
        }
//echo $sql;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        $rows = $nrows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        $ids = '';
        if(strtolower($county) == 'totals') {
            $sql_o = '
            SELECT
                families.county `name`, count(*) cnt, GROUP_CONCAT(c.id) ids
            FROM
                families 
            JOIN
                children c ON c.parent_id = families.id '
                .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE LOWER(families.county) = "out of state" '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '');
            $rs_o = mysql_query($sql_o) or die(mysql_error().$sql_o);
            $rows_o = array();
            while ($row_o = mysql_fetch_array($rs_o, MYSQL_ASSOC)) {
                $rows_o[] = $row_o;
            }
            //echo "<pre>";print_r($rows);print_r($rows_o);die;
            if(!empty($rows_o))
                array_unshift($rows, $rows_o[0]);

            $nrows = $rows;
        }
        else if(!empty($rows)) {
            $count = count($rows);
            foreach($rows as $k=>$r) {
                if(empty($r['ids']))
                    continue;
                if(strtolower($county) != 'out of state') {
                    if($k > $count-2)
                        break;
                }

                $is_found = false;

                if($r['name'] != $county)
                    continue;

                if(!empty($nrows)) {
                    
                    foreach($nrows as $key=>$nrow) {
                        $is_found = false;
                        if(isset($nrow['name']) && $r['name'] == $nrow['name']){
                            //echo $ids;
                            if(empty($ids))
                                $ids = $r['ids'];
                            else
                                $ids = $nrow['ids'].','.$r['ids'];
//echo "<br>";
                            //echo $ids;die;
                            $nrows[$key] = array(
                                'name' => $r['name'], 
                                'cnt' => $nrow['cnt'] + $r['cnt'],
                                'ids' => $ids
                            );
                            $is_found = true;
                            break;
                        }
                    }
                    if(!$is_found)
                        $nrows[] = $r;
                    
                } else {
                    $ids = '';
                    if(empty($ids))
                        $ids = $r['ids'];
                    else
                        $ids = $ids.','.$r['ids'];
                    $nrows[] = $r;
                }
            }
        }
       // echo "<pre>";print_r($rows);print_r($nrows);die;
        $totals = array_pop($nrows);
        if(isset($rows_o) && !empty($rows_o)) {
            $totals['cnt'] = $totals['cnt'] + $rows_o[0]['cnt'];
        }
        array_push($nrows, $totals);
        if($countR) {
            if(strtolower($county) != 'totals' && isset($nrows[0]['cnt']))
                return $nrows[0]['cnt'];
            else {
                return isset($totals['cnt']) ? $totals['cnt'] : 0;
            }
        }
        
        $rows_r = array();
        $ids = '';
        /*echo "<pre>";print_r($nrows);
        print_r(count(explode(',', $nrows[0]['ids'])));
        die;*/
        foreach( $nrows as $row ) {
            $ids .= $row['ids'];
        }
        $ids = explode(',', $ids);
            //echo "<pre>";print_r($ids);print_r($nrows);die;
            if(!$countR && $is_page) {
                $ids = array_slice($ids, $start, 50);
            }
            //echo 'count: '.count($ids);
            foreach( $ids as $id ) {
                $sql2 = "SELECT c.*, CONCAT_WS(' ', f.first_name_1, f.last_name_1) fname, 
                        u.hmg_worker, f.status as fstatus from children c 
                        LEFT JOIN families f ON f.id=c.parent_id 
                        INNER JOIN users u ON u.id = f.hmg_worker WHERE c.id='".$id."'";

                $rs2  = mysql_query($sql2) or die(mysql_error().$sql2);
                $rows2 = array();
                while ($row2 = mysql_fetch_array($rs2, MYSQL_ASSOC)) {
                    $rows_r[] = $row2;
                }
            }
        
        //echo "<pre>";print_r($rows_r);die;
        return $rows_r;
    }

    /*public function getChildCountyCountsDetail($county = null, $count = false, $start = 0, $is_page = true)
    {
        $filtersSql = $this->getFiltersSql();
        if($count) {
            $sql = '
        SELECT count(*) as count FROM';
        } else {
            $sql = '
            SELECT  c.id FROM';
        }
        $sql .= '
            children c
        JOIN
            families ON c.parent_id = families.id
        JOIN
            county_zipcodes cz ON families.zip = cz.zip_code
        JOIN
            counties co ON cz.id = co.id'
        .$filtersSql['join_clause'];
        
        $sql .= ($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '');
        if(!empty($county)) {
            if(isset($filtersSql['filter_by']) && !empty($filtersSql['filter_by'])) {
                $sql .= ' WHERE ';
            } else {
                $sql .= ' AND ';
            }

            $sql .= ' LOWER(families.county)="'.strtolower($county).'" ';
        }
        //.' GROUP BY families.county WITH ROLLUP ';
        
        if(!$count && $is_page) {
            $sql .= ' LIMIT '.$start.', 50';
        }
        echo $sql;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        if($count) {
            $rs_c = mysql_fetch_assoc($rs);
            return $rs_c['count'];
        }

        $rows = array();
    
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $sql2 = "SELECT c.*, CONCAT_WS(' ', f.first_name_1, f.last_name_1) fname, 
                    f.hmg_worker, f.status as fstatus from children c 
                    LEFT JOIN families f ON f.id=c.parent_id WHERE c.id='".$row['id']."'";

            $rs2  = mysql_query($sql2) or die(mysql_error().$sql2);
            $rows2 = array();
            while ($row2 = mysql_fetch_array($rs2, MYSQL_ASSOC)) {
                $rows[] = $row2;
            }
        }
        return $rows;
    }*/

    public function getScreeningCountsDetail($screenings = null, $count = false, $start = 0, $is_page = true)
    {
        if(!in_array($screenings, array('ASQ SE', 'ASQ:SE', 'Uncoded'))) {
            $screenings = str_replace('-3', '', $screenings);
            $screenings = explode(' ', $screenings);
        }
//echo "<pre>";print_r($screenings);die;
        $filtersSql = $this->getFiltersSql('date_sent');
        if($count) {
            $sql = '
            SELECT count(*) as count FROM';
        } else {
            $sql = 'SELECT
            cds.child_id as id
            FROM';
        }
        $sql .= '
            child_developmental_screenings cds
            JOIN children ON children.id = cds.child_id
            JOIN families ON families.id = children.parent_id '
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'];
        if(!in_array($screenings, array('ASQ SE', 'ASQ:SE', 'Uncoded'))) {
            if(strtolower($screenings[0]) != 'totals') {
                if(isset($screenings[0])) {
                    if($screenings[0] == 'ASQ SE') {
                        $screenings[0] = 'ASQ:SE';
                    }
                    if($screenings[0] == 'ASQ') {
                        $screenings[0] = 'ASQ-3';
                    }
                    $sql .= ' WHERE LOWER(type)="'.strtolower($screenings[0]).'" ';
                }
                if(isset($screenings[1])) {
                    $sql .= ' AND LOWER(score)="'.strtolower($screenings[1]).'" ';
                }

                $sql .= ($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '');

            } else {
                $sql .= ' WHERE (`type` = "ASQ:SE" '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').') OR (`type` = "ASQ-3" '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').' )
        ';
            }
        } else {
            if($screenings == 'Uncoded') {
                $sql .= ' WHERE (`type` = "ASQ:SE" && score NOT IN ("White", "Grey", "Black") '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').') OR (`type` = "ASQ-3" && score NOT IN ("White", "Grey", "Black") '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').' )';
            } else {
                if(strtolower($screenings[0]) != 'totals') {
                    if($screenings == 'ASQ SE') {
                        $screenings = 'ASQ:SE';
                    }
                    if($screenings == 'ASQ') {
                        $screenings = 'ASQ-3';
                    }
                    $sql .= ' WHERE LOWER(type)="'.strtolower($screenings).'" ';
                    $sql .= ($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '');
                }
                else {
                    $sql .= ' WHERE (`type` = "ASQ:SE" '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').') OR (`type` = "ASQ-3" '
                .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '').' )
        ';
                }
            }
        }

        if(!$count && !empty($this->_order_by) 
                && (trim($this->_order_by) != 'ORDER BY first_name_1 asc' && trim($this->_order_by) != 'ORDER BY first_name_1 desc')
                && (trim($this->_order_by) != 'ORDER BY hmg_worker asc' && trim($this->_order_by) != 'ORDER BY hmg_worker desc')
                && (trim($this->_order_by) != 'ORDER BY age asc' && trim($this->_order_by) != 'ORDER BY age desc')
                && (trim($this->_order_by) != 'ORDER BY birthdate asc' && trim($this->_order_by) != 'ORDER BY birthdate desc')
                && (trim($this->_order_by) != 'ORDER BY screening_type asc' && trim($this->_order_by) != 'ORDER BY screening_type desc')
                && (trim($this->_order_by) != 'ORDER BY screening_interval asc' && trim($this->_order_by) != 'ORDER BY screening_interval desc')
                && (trim($this->_order_by) != 'ORDER BY screening_score asc' && trim($this->_order_by) != 'ORDER BY screening_score desc')
        ) {
            $sql .= $this->_order_by;
        }

        if(!$count && $is_page) {
            $sql .= ' LIMIT '.$start.', 50';
        }
        //echo $sql;die;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        if($count) {
            $rs_c = mysql_fetch_assoc($rs);
            return $rs_c['count'];
        }
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $sql2 = "SELECT c.*, CONCAT_WS(' ', f.first_name_1, f.last_name_1) fname, 
                    u.hmg_worker, f.status as fstatus, cds.score, cds.type, cds.asq_month 
                    from child_developmental_screenings cds
                    LEFT JOIN children c ON c.id=cds.child_id 
                    LEFT JOIN families f ON f.id=c.parent_id 
                    INNER JOIN users u ON u.id = f.hmg_worker WHERE c.id='".$row['id']."' 
                    GROUP BY cds.child_id ";
            
            $rs2  = mysql_query($sql2) or die(mysql_error().$sql2);
            $rows2 = array();
            while ($row2 = mysql_fetch_array($rs2, MYSQL_ASSOC)) {
                $rows[] = $row2;
            }
        }
//echo "<pre>";print_r($rows);echo "</pre>";//die;
        if(trim($this->_order_by) == 'ORDER BY first_name_1 asc') {
            $this->aasort($rows, "fname");
        } elseif (trim($this->_order_by) == 'ORDER BY first_name_1 desc') {
            $this->aasort($rows, "fname", 'desc');
        }
        if(trim($this->_order_by) == 'ORDER BY hmg_worker asc') {
            $this->aasort($rows, "hmg_worker");
        } elseif (trim($this->_order_by) == 'ORDER BY hmg_worker desc') {
            $this->aasort($rows, "hmg_worker", 'desc');
        }
        if(trim($this->_order_by) == 'ORDER BY age asc') {
            $this->aasort($rows, "birth_date");
        } elseif (trim($this->_order_by) == 'ORDER BY age desc') {
            $this->aasort($rows, "birth_date", 'desc');
        }
        if(trim($this->_order_by) == 'ORDER BY birthdate asc') {
            $this->aasort($rows, "birth_date");
        } elseif (trim($this->_order_by) == 'ORDER BY birthdate desc') {
            $this->aasort($rows, "birth_date", 'desc');
        } 
        if (trim($this->_order_by) == 'ORDER BY screening_type asc') {
            $this->aasort($rows, "type");
        } elseif (trim($this->_order_by) == 'ORDER BY screening_type desc') {
            $this->aasort($rows, "type", 'desc');
        } 
        if (trim($this->_order_by) == 'ORDER BY screening_interval asc') {
            $this->aasort($rows, "asq_month");
        } elseif (trim($this->_order_by) == 'ORDER BY screening_interval desc') {
            $this->aasort($rows, "asq_month", 'desc');
        } 
        if (trim($this->_order_by) == 'ORDER BY screening_score asc') {
            $this->aasort($rows, "score");
        } elseif (trim($this->_order_by) == 'ORDER BY screening_score desc') {
            $this->aasort($rows, "score", 'desc');
        }
        if(empty($this->_order_by)) {
            $this->aasort($rows, "first");
        }
        
        return $rows;
    }

    /**
     *  Fetch families and child records who have selected 
     *  'Out of State' county as an option
     */
    public function getChildOutOfCountyCounts($count = false) {//3214
        $filtersSql = $this->getFiltersSql();
        if($count) {
            $sql = 'SELECT count(*) count';
        } else {
            $sql = 'SELECT *';
        }
        $sql .= ' FROM families JOIN children c ON c.parent_id = families.id '
            .$filtersSql['join_clause'].$filtersSql['join_clause_2'].'
            WHERE LOWER(families.county) = "out of state" '
            .($filtersSql['filter_by'] ? ' AND '.$filtersSql['filter_by'] : '');
        //echo $sql;
        $rs = mysql_query($sql) or die(mysql_error().$sql);
        if($count) {
            $rs_c = mysql_fetch_assoc($rs);
            //echo "<pre>";print_r($rs_c);die;
            return $rs_c['count'];
        }
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

}
