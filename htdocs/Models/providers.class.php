<?php

namespace Hmg\Models;

class Providers
{
    private $_table = 'providers';
    private $_sorts = [
        'employer' => 'ASC',
        'last_name' => 'ASC',
        'first_name' => 'ASC'
    ];
    private $_start = 0;
    private $_limit = 50;
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

    public function getNamesAndIds($searchString)
    {
        $sql = 'SELECT id, CONCAT(first_name, ", ", last_name, " - ", employer) name FROM `' . mysql_real_escape_string($this->_table) . '`
                WHERE LOWER(CONCAT_WS(" ", first_name, last_name, employer)) LIKE "%' . mysql_real_escape_string(strtolower($searchString)) . '%" ORDER BY last_name, first_name';
                //echo $sql;
        $rs = mysql_query($sql);
        if ($rs) {
            $rows = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $rows[] = array(
                    'id' => $row['id'],
                    'name' => $row['name']
                );
            }
            return $rows;
        } else {
            return false;
        }
    }

    public function getClinicNamesAndIds($searchString)
    {
        $sql = 'SELECT id, employer AS name FROM `' . mysql_real_escape_string($this->_table) . '`
                WHERE LOWER(employer) LIKE "%'
                    . mysql_real_escape_string(strtolower($searchString))
                    . '%" GROUP BY employer ORDER BY employer';
                //echo $sql;
        $rs = mysql_query($sql);
        if ($rs) {
            $rows = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $rows[] = array(
                    'id' => $row['id'],
                    'name' => $row['name']
                );
            }
            return $rows;
        } else {
            return false;
        }
    }

    private function buildQuery($addLimit = true, $getNextRecord = false, $currentId = null)
    {
        $order_by = '';
        $filter_by = '';
        if (is_array($this->_sorts)) {
            $order_by = 'ORDER BY ';
            $concat = false;
            foreach ($this->_sorts as $field => $dir) {
                if ($field == 'role') {
                    $field = 's.name';
                }
                $order_by .= ($concat ? ', ' : '') . mysql_real_escape_string($field) . ' ' . mysql_real_escape_string($dir);
                $concat = true;
            }
        }
        if (is_array($this->_filters)) {
            foreach ($this->_filters as $filterName => $value) {
                if ($filterName == 'quick') {
                    $filter_by = 'LOWER(CONCAT_WS(" ",first_name, last_name, title, employer, city, phone, email)) LIKE "%' . strtolower(mysql_real_escape_string($value)) . '%"';
                } else {
                    if ($this->_search || $value) {
                        $filter_by .= ($filter_by ? ' AND ' : '')
                         . (strpos($filterName, ',') ? 'CONCAT_WS(" ",'  . $filterName . ')' : mysql_real_escape_string($filterName))
                         . ' LIKE "%' . mysql_real_escape_string(($this->_search ? $this->_search : $value)) . '%"';
                    }
                }
            }
        }
        $sql = 'SELECT p.*, `s`.`name` role FROM `' . mysql_real_escape_string($this->_table) . '` p'
                . ' LEFT JOIN settings s ON p.role_id = s.id'
                . ' WHERE 1 '
                . ($filter_by ? ' AND (' . $filter_by . ')' : '')
                . $order_by;
                //echo $sql;
        $rs = mysql_query($sql);
        if ($addLimit) {
            //echo 'Start: ' . $this->_start . ' Limit: ' . $this->_limit;
            $sql .= (is_numeric($this->_start) && $this->_limit ? ' LIMIT ' . $this->_start . ', ' . $this->_limit : ($this->_limit ? ' LIMIT 0, 50' : ''));
        }
        //echo $sql;
        return $sql;
    }

    private function buildnewQuery($addLimit = true, $getNextRecord = false, $currentId = null)
    {
        $order_by = '';
        $filter_by = '';
        if (is_array($this->_sorts)) {
            $order_by = 'ORDER BY ';
            $concat = false;
            foreach ($this->_sorts as $field => $dir) {
                if ($field == 'role') {
                    $field = 's.name';
                }
                $order_by .= ($concat ? ', ' : '') . mysql_real_escape_string($field) . ' ' . mysql_real_escape_string($dir);
                $concat = true;
            }
        }
        if (is_array($this->_filters)) {
            foreach ($this->_filters as $filterName => $value) {
                if ($filterName == 'quick') {
                    $filter_by = 'LOWER(CONCAT_WS(" ",first, last, title, cell_phone, email)) LIKE "%' . strtolower(mysql_real_escape_string($value)) . '%"';
                } else {
                    if ($this->_search || $value) {
                        $filter_by .= ($filter_by ? ' AND ' : '')
                         . (strpos($filterName, ',') ? 'CONCAT_WS(" ",'  . $filterName . ')' : mysql_real_escape_string($filterName))
                         . ' LIKE "%' . mysql_real_escape_string(($this->_search ? $this->_search : $value)) . '%"';
                    }
                }
            }
        }
        $sql = 'SELECT p.*, `s`.`name` role FROM `' . mysql_real_escape_string($this->_table) . '` p'
                .'LEFT JOIN organization_sites os ON os.organization_site_id = p.organization_site_id '
                .'LEFT JOIN organizations o ON o.id=os.organization_id 
                   LEFT JOIN settings s ON o.organization_name_id = s.id  
                  LEFT JOIN settings ste ON os.organization_site_id=ste.id
                  LEFT JOIN contacts c ON c.organization_sites_id = os.id'
                . ' WHERE 1 '
                . ($filter_by ? ' AND (' . $filter_by . ')' : '')
                . $order_by;
                //echo $sql;
        $rs = mysql_query($sql);
        if ($addLimit) {
            //echo 'Start: ' . $this->_start . ' Limit: ' . $this->_limit;
            $sql .= (is_numeric($this->_start) && $this->_limit ? ' LIMIT ' . $this->_start . ', ' . $this->_limit : ($this->_limit ? ' LIMIT 0, 50' : ''));
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

    public function getOrgContact($searchString) {

        
        $searchString ='%'. mysql_real_escape_string(strtolower($searchString)).'%';
        
          $sql = "SELECT CONCAT(os.id,'-',c.id) id ,CONCAT(c.first, ' ', c.last, ' - ', s.name,if(os.organization_site_id=0,'',concat(':',ste.name))) name 
                  FROM contacts c 
                  LEFT JOIN  organization_sites os ON c.organization_sites_id = os.id
                  LEFT JOIN organizations o ON o.id=os.organization_id
                  LEFT JOIN settings s ON o.organization_name_id = s.id  
                  LEFT JOIN settings ste ON os.organization_site_id=ste.id                  
                  having name LIKE '%$searchString%'  ORDER BY c.last, c.first";
        
                $rs = mysql_query($sql) or    die($sql);
        if ($rs) {
            $rows = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                if($row['name'] != null ) 
                $rows[] = array(
                    'id' => $row['id'],
                     'name' => utf8_encode($row['name']), 
                );
                
            }
            return $rows;
        } else {
            return false;
        }

    }

}
