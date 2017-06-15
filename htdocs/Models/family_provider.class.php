<?php

namespace Hmg\Models;

class FamilyProvider
{
    private $_table = 'family_provider';
    private $_family_id = null;
    private $_provider_id = null;
    private $_org_id = null;
    private $_contact_id = null; 
    private $_fax_permission = 0;
    private $_primary = 0;
    public $message = null;

    public function __construct($family_id, $provider_id = null, $fax_permission = 0, $primary = 0, $send_follow_up = array())
    {
        if (is_numeric($family_id)) {
            $this->_family_id = $family_id;
        }
        if (is_numeric($provider_id)) {
            $this->_provider_id = $provider_id;
        }elseif($provider_id && !is_numeric($provider_id))
            list($this->_org_id , $this->_contact_id) = explode('-',$provider_id);

            
        
        if (is_numeric($fax_permission)) {
            $this->_fax_permission = $fax_permission;
        }
        if (is_numeric($primary)) {
            $this->_primary = $primary;
        }
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


    public function set($key, $value)
    {
        $this->$key = $value;
    }

    public function get($key)
    {
        return $this->$key;
    }

    public function getList()
    {
        /* $sql = 'SELECT providers.*, settings.name as role, fax_permission, `primary` FROM `' . $this->_table . '`' */
        /*      . ' JOIN providers ON `' . $this->_table . '`.provider_id = providers.id LEFT JOIN settings ON providers.role_id = settings.id' */
        /*      . ' WHERE family_id = "' . $this->_family_id . '" ORDER BY `primary` DESC, first_name, last_name'; */
                $sql = "SELECT p.*, CONCAT(first, ' ', last, ' - ', s.name,':',ste.name) name FROM `" . mysql_real_escape_string($this->_table) . '` p'
                .' LEFT JOIN organization_sites os ON os.organization_site_id = p.organization_site_id '
                .'LEFT JOIN organizations o ON o.id=os.organization_id 
                   LEFT JOIN settings s ON o.organization_name_id = s.id  
                  LEFT JOIN settings ste ON os.organization_site_id=ste.id
                  LEFT JOIN contacts c ON c.organization_sites_id = os.id'.
                    ' WHERE p.family_id = '.$this->_family_id;
$sql = "SELECT 
	CONCAT(os.id, '-', c.id) id, 
	CONCAT(
		c.first, ' ', c.last, ' - ', s.name, if(os.organization_site_id=0,'',concat(':',ste.name))
	) name,fax_permission,contact_id as provider_id
FROM 
	family_provider left join 
	organization_sites os on os.id = family_provider.organization_site_id     
	LEFT JOIN organizations o ON o.id = os.organization_id 
	LEFT JOIN settings s ON o.organization_name_id = s.id 
	LEFT JOIN settings ste ON os.organization_site_id = ste.id
	LEFT JOIN contacts c ON c.id = family_provider.contact_id 
	WHERE 
    family_provider.family_id = '$this->_family_id' ";
                

        $rs = mysql_query($sql) or die(mysql_error() . ' ' . $sql);
        if ($rs) {
            $rows = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $rows[] = $row;
            }
            
            return $rows;
        }
    }

        public function getList2()
    {
        /* $sql = 'SELECT providers.*, settings.name as role, fax_permission, `primary` FROM `' . $this->_table . '`' */
        /*      . ' JOIN providers ON `' . $this->_table . '`.provider_id = providers.id LEFT JOIN settings ON providers.role_id = settings.id' */
        /*      . ' WHERE family_id = "' . $this->_family_id . '" ORDER BY `primary` DESC, first_name, last_name'; */

$sql = "SELECT  family_provider.id  , 
		first , last , title , email ,o.fax, fax_permission ,family_provider.primary,send_follow_up , date_permission_granted , date_permission_revoked, permission_type,
            s.name as org ,ste.name as site
FROM 
	family_provider left join 
	organization_sites os on os.id = family_provider.organization_site_id
	LEFT JOIN organizations o ON o.id = os.organization_id 
	LEFT JOIN settings s ON o.organization_name_id = s.id 
	LEFT JOIN settings ste ON os.organization_site_id = ste.id 
	LEFT JOIN contacts c ON c.id = family_provider.contact_id 
	WHERE 
    family_provider.family_id = '$this->_family_id'
    
    order by family_provider.primary DESC";
                

        $rs = mysql_query($sql) or die(mysql_error() . ' ' . $sql);
        if ($rs) {
            $rows = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $rows[] = $row;
            }
            return $rows;
        }
    }

    public function getRecord()
    {
        $sql = 'SELECT contacts.*,title as role, fax_permission, `primary` FROM `' . $this->_table . '`'
             . ' JOIN contacts ON `' . $this->_table . '`.contact_id = contacts.id  WHERE family_id = "' . $this->_family_id . '" AND contact_id = "' . $this->_provider_id . '"';
        
        $sql = "SELECT  c.*,title as role,o.fax, fax_permission ,`primary` 
FROM 
	family_provider left join 
	organization_sites os on os.id = family_provider.organization_site_id
	LEFT JOIN organizations o ON o.id = os.organization_id 
	LEFT JOIN settings s ON o.organization_name_id = s.id 
	LEFT JOIN settings ste ON os.organization_site_id = ste.id 
	LEFT JOIN contacts c ON c.id = family_provider.contact_id 
	WHERE 
    family_provider.family_id = '$this->_family_id' and 
    family_provider.contact_id = '$this->_provider_id' 
    
    order by family_provider.primary DESC";
        
        $rs = mysql_query($sql) or die(mysql_error() . ' ' . $sql);
        if ($rs) {
            $row = mysql_fetch_array($rs, MYSQL_ASSOC);

            return $row;
        }
    }

    public function save()
    {
        if ($this->_family_id && $this->_provider_id) {
            $sql = 'INSERT IGNORE INTO `' . mysql_real_escape_string($this->_table) . '`
                    SET `family_id` = "' . mysql_real_escape_string($this->_family_id) . '",
                     `provider_id` = "' . mysql_real_escape_string($this->_provider_id) . '"';
            $rs = mysql_query($sql);
            return mysql_affected_rows();
        }
        elseif($this->_family_id && $this->_org_id && $this->_contact_id)


        {
            $sql = 'INSERT IGNORE INTO `' . mysql_real_escape_string($this->_table) . '`
                    SET `family_id` = "' . mysql_real_escape_string($this->_family_id) . '",
                     `organization_site_id` = "' . mysql_real_escape_string($this->_org_id) . '"
                     ,`contact_id` = "' . mysql_real_escape_string($this->_contact_id) .'"';
            $rs = mysql_query($sql) or die($sql);
            return mysql_affected_rows();

        }              

    }
            
            
    public function  update($data )
    {   unset($data['update']);
          unset($data['family_id']);
          unset($data['provider_id']);
          unset($data['action']);
          if($data['date_permission_revoked'])
              $data['date_permission_revoked']=date("y-m-d",strtotime($data['date_permission_revoked']));
else
$data['date_permission_revoked'] = '0000-00-00';
          if($data['date_permission_granted'])
              $data['date_permission_granted'] =  date("y-m-d",strtotime($data['date_permission_granted']));
              else 
     $data['date_permission_granted'] = '0000-00-00';
        $sql = 'UPDATE family_provider SET ';

        $sql .=  'fax_permission='.$data['fax_permission'].',send_follow_up='.$data['send_follow_up'].',date_permission_granted="'.$data['date_permission_granted'].'",date_permission_revoked="'.$data['date_permission_revoked'].'"'.',permission_type="'.$data['permission_type'].'"';

                 $sql.= '   WHERE `family_id` = "' . mysql_real_escape_string($this->_family_id) . '"
                     AND `id` = "' . mysql_real_escape_string($this->_provider_id) . '"';
                 $rs = mysql_query($sql) or die($sql);
//                die($sql);

            return mysql_affected_rows();
            

    }
        

    public function updateKey($key)
    {
        if ($this->_family_id && $this->_provider_id) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '`
                    SET `' . mysql_real_escape_string(trim($key, '_')) . '` = "' . mysql_real_escape_string($this->$key) . '"
                    WHERE `family_id` = "' . mysql_real_escape_string($this->_family_id) . '"
                    AND `id` = "' . mysql_real_escape_string($this->_provider_id) . '"';
            $rs = mysql_query($sql);
            return mysql_affected_rows();
        }
    }

    public function delete()
    {
        if ($this->_provider_id) {
            $sql = "DELETE FROM family_provider WHERE id = '$this->_provider_id'";
            $rs = mysql_query($sql);
            if ($rs) {
                return true;
            } else {
                return false;
            }
        }
    }
}
