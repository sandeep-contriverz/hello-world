<?php

namespace Hmg\Models;
use Hmg\Models\Organization;
class ReferralServices
{
    protected $referralId = array();
    public $services = array();
    public $referredToType = 'os';

    public function __construct($referralId = null, $services = array())
    {
        if (! is_null($referralId) && is_numeric($referralId)) {
            $this->referralId = $referralId;
        }

        $this->services = $services;
    }

    public function set($key, $value)
    {
		
        $this->$key = $value;
		
    }

    public function get($key)
    {
        return $this->$key;
    }

    public function setServices()
    {
        if (isset($this->referralId) && is_numeric($this->referralId)) {
           
			if($this->referredToType == 'os'){
			$sql = '
                SELECT
                    s.id service_id,
                    s.name service,
                    s.disabled setting_disabled,
                    rs.disabled
                FROM `service` rs
                LEFT JOIN settings s ON rs.service_id = s.id
				LEFT JOIN organization_sites os on os.id=rs.referred_to_id
                WHERE rs.referred_to_id  = "' . mysql_real_escape_string($this->referralId) . '" ORDER BY service ASC';
            }
			else{
				$sql = '
                SELECT
                    s.id service_id,
                    s.name service,
                    s.disabled setting_disabled,
                    ir.disabled
                FROM settings s
				LEFT JOIN Informational_Referrals_List ir on s.id=ir.service_id
                WHERE  ir.info_referred_id  = "' . mysql_real_escape_string($this->referralId) . '" ORDER BY service ASC';
			}
			$rs = mysql_query($sql) or die($sql . '<br />' . mysql_error());
			$count=mysql_num_rows($rs);
            if ($count > 0) {
				
                $services = array();
                while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                    $services[] = array(
                        'id'       => $row['service_id'],
                        'name'     => $row['service'],
                        'disabled' => ($row['disabled'] ? '1' : '0'),
                        'setting-disabled' => ($row['setting_disabled'] ? '1' : '0')
                    );
                }
                $this->services = $services;
				
            }
			else{
		
			$sql_ch= "SELECT organizations.organization_name_id
			FROM organization_sites JOIN organizations on organizations.id=organization_sites.organization_id where organization_sites.id='".mysql_real_escape_string($this->referralId)."'";
			
			$rs_check = mysql_query($sql_ch);
			$re_ch=mysql_fetch_array($rs_check, MYSQL_ASSOC);
			$orgNameID = $re_ch['organization_name_id'];
			
			$sql1 = '
					SELECT
					s.id service_id,
						s.name service,
						s.disabled setting_disabled,
						rs.disabled
						
					FROM organizations o
					LEFT JOIN organization_sites os ON os.organization_id=o.id
					LEFT JOIN service rs ON rs.referred_to_id=os.id
					LEFT JOIN settings s ON rs.service_id = s.id
					WHERE o.organization_name_id = "' . mysql_real_escape_string($orgNameID) . '"
					AND (os.organization_site_id=0 OR os.organization_site_id="" OR os.organization_site_id="null") ORDER BY service ASC';
					$rs1 = mysql_query($sql1);
					$services1 = array();
					while ($row1 = mysql_fetch_array($rs1, MYSQL_ASSOC)) {
						$services1[] = array(
							'id'       => $row1['service_id'],
							'name'     => $row1['service'],
							'disabled' => ($row1['disabled'] ? '1' : '0'),
							'setting-disabled' => ($row1['setting_disabled'] ? '1' : '0')
						);
					}


                $this->services = $services1;

                
			}
        }
    }
			

    public function getFilteredServices($selected)
    {
        $filteredServices = array();
        //!ddd($this->services);
	   if (is_array($this->services)) 
	   {
            foreach ($this->services as $service) 
			{
                if ((!$service['disabled'] && !$service['setting-disabled']) || $service['id'] === $selected) 
				{
                    $filteredServices[] = $service;
                }
            }
        }
        
		
        return $filteredServices;
    }
    public function  getiServices ($id)
    {
               $sql1 = "SELECT
                    distinct s.id service_id,
                    s.name service,
                    s.disabled setting_disabled,
                    service.disabled
                FROM Informational_Referrals_List
                LEFT JOIN service ON Informational_Referrals_List.service_id=  service.service_id
                LEFT JOIN settings s ON service.service_id = s.id
                WHERE Informational_Referrals_List.Column_2 = '$id'";
               $rs1 = mysql_query($sql1) or die($sql1);

 
               $services1 = array();
					while ($row1 = mysql_fetch_array($rs1, MYSQL_ASSOC)) {
						$services1[] = array(
							'id'       => $row1['service_id'],
							'name'     => $row1['service'],
							'disabled' => ($row1['disabled'] ? '1' : '0'),
							'setting-disabled' => ($row1['setting_disabled'] ? '1' : '0')
						);
					}
                    return $services1;
    }

        
    public function save()
    {
        $services = $this->services;
        if (isset($this->referralId) && is_numeric($this->referralId)) {
            // Ignore disabled ones
            $sql = '
                DELETE rs.*
                FROM `settings` s
                LEFT JOIN service rs ON s.id = rs.service_id
                WHERE rs.referred_to_id = "' . mysql_real_escape_string($this->referralId) . '" AND s.disabled != "1"';
            //$rs = mysql_query($sql) or die($sql);
            if (isset($this->services) && is_array($this->services)) {
                foreach ($this->services as $service) {
                    $sql = '
                        INSERT INTO
                            `service`
                        SET
                            referred_to_id = "' . mysql_real_escape_string($this->referralId) .  '",
                            service_id = "' . mysql_real_escape_string($service['id']) .  '",
                            
                            disabled = "' . mysql_real_escape_string($service['disabled']) .  '"';
                    $rs = mysql_query($sql);
                }
            }
            $this->setServices();
            return $this->services;
        }
        
    }
	/******* Save informational referral *******/
	public function infosave()
    {
        $services = $this->services;
        if (isset($this->referralId) && is_numeric($this->referralId)) {
            // Ignore disabled ones
            $sql = '
                DELETE 
                FROM `Informational_Referrals_List` 
                WHERE info_referred_id = "' . mysql_real_escape_string($this->referralId) . '"';
            $rs = mysql_query($sql) or die($sql);
            if (isset($this->services) && is_array($this->services)) {
                foreach ($this->services as $service) {
                    $sql = '
                        INSERT INTO
                            `Informational_Referrals_List`
                        SET
                            info_referred_id = "' . mysql_real_escape_string($this->referralId) .  '",
                            service_id = "' . mysql_real_escape_string($service['service_id']) .  '",
                            
                            disabled = "' . mysql_real_escape_string($service['disabled']) .  '"';
                    $rs = mysql_query($sql);
                }
            }
            //$this->setServices();
            return true;
        }
        
    }
	/*****End******/

    public function updateServiceDisabled($serviceId, $disabled){	
        if ($this->referralId) {	
           $sql = '
                UPDATE
                    service
                SET disabled = "' . (!empty($disabled) ? '1' : '0') . '"
                WHERE
                    referred_to_id = "' . $this->referralId . '"
                    AND service_id = "' . mysql_real_escape_string($serviceId) . '"';
            $rs = mysql_query($sql);
            return $rs;
        }
    }

    public function getList($filters = array())
    {
        $sql = 'SELECT
                    r.id organization_name_id,
                    r.name organization_name,
                    r.disabled referral_disabled,
                    s.id service_id,
                    s.name service,
                    s.disabled service_disabled,
                    rs.disabled disabled,
                    rs.referred_to_site_id referred_to_site_id,
					
                    rst.name referred_to_site                    
                FROM `settings` r
                 JOIN service rs ON r.id = rs.referred_to_id
                 JOIN settings s ON rs.service_id = s.id
				 
                 JOIN settings rst ON rs.referred_to_site_id = rst.id
				 
                WHERE r.type = "referred_to"'
                .   (
                        $this->referralId ?
                            ' and r.id = "' . mysql_real_escape_string($this->referralId) . '"'
                            : ''
                    )
                .     (
                        isset($filters['referral']) && $filters['referral'] ?
                            ' and r.name LIKE "%' . mysql_real_escape_string($filters['referral']) . '%"'
                        :
                            ''
                    )
                .     (
                        isset($filters['service']) && $filters['service'] ?
                            ' and s.name LIKE "%' . mysql_real_escape_string($filters['service']) . '%"'
                        :
                            ''
                    )
                .     (
                        isset($filters['exlude_disabled']) && $filters['exlude_disabled'] ?
                            ' and (s.disabled != "1" and rs.disabled != "1")'
                        :
                            ''
                    )
                . ' ORDER BY referred_to ASC, service ASC';
        $rs = mysql_query($sql);
        if ($rs) {
            $rows = $value = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                //$rows[] = $row;
                
                $org_name = 'Other'; 
				$org_id = 0;
                if(empty($org_id))
                    $org_id = $row['organization_name_id'];
                if(!empty($row['organization_name']))
                    $org_name = $row['organization_name'];
               
                $rows[$org_name][] = $row;
            }
            return $rows;
        } else {
            return false;
        }
    }

    public function getNamesAndIdsOrg($searchString)
    {

        $sql = '
            SELECT
                os.id, r.name,sn.name as siteName
            FROM
                organization_sites os
            JOIN organizations o ON o.id=os.organization_id
            JOIN settings r ON r.id=o.organization_name_id
            LEFT JOIN settings sn on sn.id=os.organization_site_id
            WHERE
                r.type = "referred_to"
                AND (r.name LIKE "%' . mysql_real_escape_string($searchString) . '%"
                OR sn.name LIKE "%' . mysql_real_escape_string($searchString) . '%")
                AND r.disabled != "1"
            Group By o.id
            ORDER BY r.name ASC ';
        $rs = mysql_query($sql);

        if ($rs) {
            $rows1 = array();         
                
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $siteName = '';
                if(!empty($row['siteName'])){
                    $siteName = ': '.$row['siteName'];  
                }
                $rows1[] = array(
                    'id' => $row['id'],
                    'name' => $row['name'].$siteName,
                    'type' => "os"
                );
            }
        }
        
        return array_values($rows1) ;        
    }

    public function getNamesAndIds($searchString)
    {
        $sql = '
        SELECT
            os.id, r.name,sn.name as siteName
        FROM
            organization_sites os
		JOIN organizations o ON o.id=os.organization_id
		JOIN settings r ON r.id=o.organization_name_id
		LEFT JOIN settings sn on sn.id=os.organization_site_id
        WHERE
            r.type = "referred_to"
                AND (r.name LIKE "%' . mysql_real_escape_string($searchString) . '%"
				OR sn.name LIKE "%' . mysql_real_escape_string($searchString) . '%")
                AND r.disabled != "1"
		Group By o.id
        ORDER BY r.name ASC';
        $rs = mysql_query($sql);

        if ($rs) {
            $rows1 = array();         
			
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
				$siteName = '';
				if(!empty($row['siteName'])){
					$siteName = ': '.$row['siteName'];	
				}
                $rows1[] = array(
                    'id' => $row['id'],
                    'name' => $row['name'].$siteName,
					'type' => "os"
                );
            }
        }
        $rows2 = array();
		
		$sql = " SELECT 
			info.info_referred_id,s.name
		From 
			Informational_Referrals_List info
		JOIN settings s ON s.id=info.info_referred_id 
		
		where 	 s.type='info_referall' AND s.name 
			like '%".mysql_real_escape_string($searchString)."%'
			
		Group By info.info_referred_id 
		order by s.name ASC ";
        
		$rs = mysql_query($sql);
        if ($rs) {

            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC))
                                   $rows2[] = array(
                                       'id' => $row['info_referred_id'],
                                       'name' => $row['name'],
									   'type' => "info"
                );

        }
        return   array_merge(array_values($rows1),  array_values($rows2)) ;

        
    }
    public function displayServiceSelect(
        $name,
        $selected,
        $label = '',
        $tabIndex = '',
        $required = false,
        $addtlclasses = null,
        $filtered = false,
        $allowDisableSelect = true
    ) {
		
	    //$selected; 
		
        if ($label) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '';
        }
        $disableSelect = false;
		
        if ($filtered) {
            $services = $this->getFilteredServices($selected);
        } else {
			
            $services = $this->services;
        }
	 	
        if (is_array($services)) {
            foreach ($services as $service) {
                if ($service['id'] === $selected && $service['disabled']) 
				{
                    if ($allowDisableSelect) 
					{
                        $disableSelect = true;
                    }
                }
                $options .= '<option value="' . $service['id'] . '"' . ($selected == $service['id'] ? ' selected="selected"' : '') . '>' . $service['name'] . ($service['disabled'] || $service['setting-disabled'] ? ' (Inactive)' : '') . '</option>';
            }
        }
        $select = '<select id="' . $name . '" class="setting' . ($required ? ' required' : '') . ($disableSelect ? ' setting-input setting-input-disabled' : '') . ($addtlclasses ? ' ' . $addtlclasses : '') . '" name="' . $name . '" tabindex="' . $tabIndex . '"' . ($disableSelect ? ' disabled' : '') . '>' . $options . '</select>';
        return $select;
    }
	
		public function getFilteredReferralBYD($selected){
        
		$filteredReferral = array();
		/*$sql = "Select * from service os
			JOIN settings s ON s.id=os.service_id
			Where referred_to_id='307'";*/
		$sql = '
            SELECT
                 st.id, st.name , disabled FROM settings st 
		 
             WHERE st.type="referred_to_service"
			' .
		   
            ($selected ? ' OR st.id = "' . mysql_real_escape_string($selected) . '"' :'') . '
		 
		 ORDER BY st.name';
		
        $rs = mysql_query($sql) or die($sql . '<br />' . mysql_error());
	    
        if ($rs) {
            $services = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $filteredReferral[] = array(
                    'id'       => $row['id'],
                    'name'     => $row['name'],
                    'disabled' => ($row['disabled'] ? '1' : '0')
                   
                );
            }
        }

        return $filteredReferral;
    }
	public function getFilteredReferral($selected)
    {
        $filteredReferral = array();
		
		$sql = '
            SELECT
                 st.id, st.name , disabled FROM settings st 
		 
             WHERE st.type="referred_to_service"
			' .
		   
            ($selected ? ' OR st.id = "' . mysql_real_escape_string($selected) . '"' :'') . '
		 
		 ORDER BY st.name';
		
        $rs = mysql_query($sql) or die($sql . '<br />' . mysql_error());
	    
        if ($rs) {
            $services = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $filteredReferral[] = array(
                    'id'       => $row['id'],
                    'name'     => $row['name'],
                    'disabled' => ($row['disabled'] ? '1' : '0')
                   
                );
            }
        }

        return $filteredReferral;
    }
	
	public function displayReferralSelect(
        $name,
        $selected,
        $label = '',
        $tabIndex = '',
        $required = false,
        $addtlclasses = null,
        $filtered = false,
        $allowDisableSelect = true
    ) {
        if ($label) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '';
        }
       $disableSelect = false;
		
        if ($filtered) {
            $services = $this->getFilteredReferral($selected);
        } else {
            $services = $this->services;
        }
        if (is_array($services)) {
            foreach ($services as $service) {
                if ($service['id'] === $selected && $service['disabled']) {
                    if ($allowDisableSelect) {
                       $disableSelect = true;
                    }
                }
			
                $options .= '<option value="' . $service['id'] . '"' . ($selected == $service['id'] ? ' selected="selected"' : '') . '>' . $service['name'] . ($service['disabled']  ? ' (Inactive)' : '') . '</option>';
            }
        }
        $select = '<select id="' . $name . '" class="setting' . ($required ? ' required' : '') . ($disableSelect ? ' setting-input setting-input-disabled' : '') . ($addtlclasses ? ' ' . $addtlclasses : '') . '" name="' . $name . '" tabindex="' . $tabIndex . '"' . ($disableSelect ? ' disabled' : '') . '>' . $options . '</select>';
        return $select;
			
    }
	
	
	public function displayReferralSelectByD(
        $name,
        $selected,
		$siteID,
        $label = '',
        $tabIndex = '',
        $required = false,
        $addtlclasses = null,
        $filtered = false,
        $allowDisableSelect = true
    ) {

        if ($label) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '';
        }
       $disableSelect = false;
		
        if ($filtered) {
            $services = $this->getFilteredReferralBYD($selected,$siteID);
			//print_r($services);
		
        } else {
            $services = $this->services;
        }

        if (is_array($services)) {
            foreach ($services as $service) {
                if ($service['id'] === $selected && $service['disabled']) {
                    if ($allowDisableSelect) {
                       $disableSelect = true;
                    }
                }
			
                $options .= '<option value="' . $service['id'] . '"' . ($selected == $service['id'] ? ' selected="selected"' : '') . '>' . $service['name'] . ($service['disabled']  ? ' (Inactive)' : '') . '</option>';
            }
        }
        $select = '<select id="' . $name . '" class="setting' . ($required ? ' required' : '') . ($disableSelect ? ' setting-input setting-input-disabled' : '') . ($addtlclasses ? ' ' . $addtlclasses : '') . '" name="' . $name . '" tabindex="' . $tabIndex . '"' . ($disableSelect ? ' disabled' : '') . '>' . $options . '</select>';
        return $select;
			
    }
	
	public function getReferrals_Services1111($filters = array())
    {
        $sql = 'SELECT
                    r.id organization_name_id,
                    r.name organization_name,
                    r.disabled referral_disabled,
                    s.id service_id,
                    s.name service,
                    s.disabled service_disabled,
                    rs.disabled disabled,
                    os.organization_site_id referred_to_site_id,
                    
                    rst.name referred_to_site                    
                FROM organization_sites os
                JOIN organizations o ON o.id=os.organization_id
                LEFT JOIN `settings` r ON r.id=os.organization_id
                LEFT JOIN service rs ON r.id = rs.referred_to_id
                LEFT JOIN settings s ON rs.service_id = s.id
                LEFT JOIN settings rst ON os.organization_site_id = rst.id
                 
                WHERE 1=1 /*r.type = "referred_to"*/'
                .   (
                        $this->referralId ?
                            ' and r.id = "' . mysql_real_escape_string($this->referralId) . '"'
                            : ''
                    )
                .     (
                        isset($filters['referral']) && $filters['referral'] ?
                            ' and r.name LIKE "%' . mysql_real_escape_string($filters['referral']) . '%"'
                        :
                            ''
                    )
                .     (
                        isset($filters['service']) && $filters['service'] ?
                            ' and s.name LIKE "%' . mysql_real_escape_string($filters['service']) . '%"'
                        :
                            ''
                    )
                .     (
                        isset($filters['exlude_disabled']) && $filters['exlude_disabled'] ?
                            ' and (s.disabled != "1" and rs.disabled != "1")'
                        :
                            ''
                    )
                . ' ORDER BY referred_to ASC, service ASC';
        //echo $sql;
        $rs = mysql_query($sql);
        if ($rs) {
            $rows = $value = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                //$rows[] = $row;
                
                $org_name = 'Other'; $org_id = 0;
                if(empty($org_id))
                    $org_id = $row['organization_name_id'];
                if(!empty($row['organization_name']))
                    $org_name = $row['organization_name'];
               
                $rows[$org_name][] = $row;
            }
            return $rows;
        } else {
            return false;
        }
    }
    /* 020117 */
    public function getReferrals_Services()
    {   
        $sql = "Select o.*, os.id org_site_id, os.organization_site_id, s.name 
            From organization_sites os
            JOIN organizations o ON o.id=os.organization_id
            JOIN settings s ON s.id=o.organization_name_id";
       
        $rs = mysql_query($sql);
        if ($rs) {
            $rows = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $rows[$row['name']][] = $row;
            }
            return $rows;
        } else {
            return false;
        }
    }
    
	
	public function getSiteByOrganizationID($organization_id){
		$sql = "Select os.*, os.id org_site_id, s.name 
            From organization_sites os
			JOIN settings s ON s.id=os.organization_site_id
			Where organization_id='".$organization_id."'";
		$rs = mysql_query($sql);
        if ($rs) {
            $rows = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $rows[] = $row;
            }
			return $rows;
        } else {
            return false;
        }
	}
	
	public function getServiceBySiteID($siteID){
		$sql = "Select os.*, s.name from service os
			JOIN settings s ON s.id=os.service_id
			Where referred_to_id='".$siteID."'";
		$rs = mysql_query($sql);

        if ($rs) {
            $rows = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $rows[] = $row;
            }
			return $rows;
        } else {
            return false;
        }
	}

    public function getInformationReferrals() {
	$sql = "Select info.info_referred_id,s.name From settings s  left JOIN  Informational_Referrals_List info ON s.id=info.info_referred_id where s.type='info_referall' order by s.name ASC";
       $rs = mysql_query($sql);
        if ($rs) {
            $services = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) 
                $services[$row['name']][] = $row;
            return $services;
        }else
            return false;
        
    }

    public function getServiceById($id) {

	$sql ="SELECT st.id,st.name,info.disabled FROM `Informational_Referrals_List` info left join settings st on st.id=info.service_id 
       WHERE st.type = 'referred_to_service'
       AND info.info_referred_id='$id'";

	$rs = mysql_query($sql) or die($sql);
        if ($rs) {
            $services = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) 
                $services[] = $row;
            return $services;
        }else
            return false;

        

    }
    public function fetchServices(
        $name,
        $selected,
		$siteID,
        $label = '',
        $tabIndex = '',
        $required = false,
        $addtlclasses = null,
        $filtered = false,
        $allowDisableSelect = true
    ) {

        $sql = "SELECT Column_4 FROM Informational_Referrals_List WHERE id='$selected' LIMIT 1";

        $rs = mysql_query($sql) or die($sql . '<br />' . mysql_error());
       if ($rs) 
           $row = mysql_fetch_array($rs , MYSQL_ASSOC);
       
       $selected = mysql_real_escape_string($row['Column_4']);

        $sql = "SELECT id  FROM settings st WHERE  st.type='referred_to_service' AND st.name='$selected'";

        $rs = mysql_query($sql) or die($sql . '<br />' . mysql_error());
       if ($rs) 
           $row = mysql_fetch_array($rs , MYSQL_ASSOC);

        $selected = $row['id'];

           
        if ($label) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '';
        }
       $disableSelect = false;
		
        if ($filtered) {

            		$sql = '
            SELECT 
                 st.id, st.name , disabled FROM settings st 
             WHERE st.type="referred_to_service"
		 ORDER BY st.name';
		
        $rs = mysql_query($sql) or die($sql . '<br />' . mysql_error());
	    
        if ($rs) {
            $services = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $services[] = array(
                    'id'       => $row['id'],
                    'name'     => $row['name'],
                    'disabled' => ($row['disabled'] ? '1' : '0')
                   
                );
            }

        }
		
        } else {
            $services = $this->services;
        }

        if (is_array($services)) {
            foreach ($services as $service) {
                if ($service['id'] === $selected && $service['disabled']) {
                    if ($allowDisableSelect) {
                       $disableSelect = true;
                    }
                }
			
                $options .= '<option value="' . $service['id'] . '"' . ($selected == $service['id'] ? ' selected="selected"' : '') . '>' . $service['name'] . ($service['disabled']  ? ' (Inactive)' : '') . '</option>';
            }
        }
        $select = '<select id="' . $name . '" class="setting' . ($required ? ' required' : '') . ($disableSelect ? ' setting-input setting-input-disabled' : '') . ($addtlclasses ? ' ' . $addtlclasses : '') . '" name="' . $name . '" tabindex="' . $tabIndex . '"' . ($disableSelect ? ' disabled' : '') . '>' . $options . '</select>';
        return $select;	
    }

    public function saveInfo($name , $services) {
        foreach ( $services as $service ){
            if($service->id != "undefined")    {
         $sql = "UPDATE Informational_Referrals_List SET Column_2='$name' , service_id='$service->service_id' , disabled = '$service->disabled'
                 WHERE id = '$service->id'";


            }
            else
                $sql = "INSERT INTO Informational_Referrals_List VALUES('','$name','$service->service_id','$service->disabled')";
		
         mysql_query($sql) or die($sql . '<br />' . mysql_error());
	    
        }

    }
        
}
    

