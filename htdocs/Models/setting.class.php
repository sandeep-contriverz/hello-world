<?php

namespace Hmg\Models;
use Hmg\Models\ReferralServices;

class Setting
{
    private $_table = 'settings';
    private $_token_table = 'api_tokens';
    private $_type = null;
   private $_etype = null;
     
    public $settings = array();
    public $message = null;

    public function __construct($type = '', $natsort = false,$extra=null)
    {
        
        $this->_type     = $type;
        $this->_etype  = $extra;
        if ($this->_type === 'best_hours') {
            $this->setBestHoursSettings();
        } if ($this->_type === 'region') {
            $this->setRegionSettings();
        }elseif ($this->_type) {
            $this->setSettings($natsort);
        }
    }

    public function setType($type)
    {
        $this->_type = $type;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function setRegionSettings()
    {

        $join = 'LEFT JOIN api_tokens ON api_tokens.region_id= settings.id';
        $sql = 'SELECT `settings`.id,`settings`.name,`settings`.disabled,`api_tokens`.token
                FROM `settings`'.$join.'
                WHERE ' . ($this->_type ? 'type = "' . mysql_real_escape_string($this->_type) . '" ' : '') . ' ORDER BY name ASC';
        $rs = mysql_query($sql) or die(mysql_query());
        if ($rs) {
            $settings = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $settings[] = array(
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'token' => $row['token'],
                    'disabled' => $row['disabled'],
                );
            }
           $this->settings = $settings;
        }
    }

    public function setSettings($natsort = false)
    {
        
        $join = 'LEFT JOIN api_tokens ON api_tokens.region_id= settings.id';
          $sql = 'SELECT `settings`.id,`settings`.name,`settings`.disabled, `settings`.sub_type, `api_tokens`.`token` FROM `' . $this->_table . '` '.$join.' WHERE `type` = "' . mysql_real_escape_string($this->_type) .'" ORDER BY ' . ($natsort ? ' Length(name) ASC,' : '') . ' name ASC';
        $rs = mysql_query($sql) or die($sql . '<br />' . mysql_error());
        if ($rs) {
            $settings = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                //echo "<pre>";print_r($row);
                $settings[] = array('id' => $row['id'], 'name' => $row['name'], 'disabled' => $row['disabled'], 'sub_type' => $row['sub_type'], 'token' => $row['token']);
            }
            $this->settings = $settings;
        }
    }

    public function setBestHoursSettings()
    {
        $sql = 'SELECT
            *,
            IF(name="Any Time", 0, IF(name REGEXP "AM$", 1, (IF(name REGEXP "PM$", 2, 3)))) custom1,
            IF(name REGEXP "AM$", LPAD(SUBSTRING_INDEX(name, " ", 1), 5, "0"), 1) am,
            IF(name REGEXP "PM$", LPAD(SUBSTRING_INDEX(name, " ", 1), 5, "2"), 1) pm
        FROM
            `settings`
        WHERE
            type = "best_hours"
        ORDER BY custom1, am ASC, pm ASC';
        $rs = mysql_query($sql) or die(mysql_error());
        if ($rs) {
            $settings = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $settings[] = array('id' => $row['id'], 'name' => $row['name'], 'disabled' => $row['disabled']);
            }
            $this->settings = $settings;
        }
    }

    public function getSettingById($id)
    {
        $settingValue = '';
        $sql = 'SELECT * FROM `' . $this->_table . '` WHERE `id` = "' . mysql_real_escape_string($id) . '"';

        $rs = mysql_query($sql) or die($sql . '<br />' . mysql_error());
        if (mysql_num_rows($rs)) {
            $row = mysql_fetch_array($rs, MYSQL_ASSOC);
            $settingValue = $row['name'];
        }
        return $settingValue;
    }

    public function getSettingIdByName($name)
    {
        $settingValue = '';
        $sql = 'SELECT * FROM `' . $this->_table . '` WHERE `name` = "' . mysql_real_escape_string($name) . '"';
        $rs = mysql_query($sql) or die($sql . '<br />' . mysql_error());
        if (mysql_num_rows($rs)) {
            $row = mysql_fetch_array($rs, MYSQL_ASSOC);
            $settingValue = $row['id'];
        }
        return $settingValue;
    }

    public function getNamesAndIds($searchString)
    {
        $join = 'LEFT JOIN api_tokens ON api_tokens.region_id= settings.id';
        $sql = 'SELECT `settings`.id,`settings`.name,`settings`.disabled,`api_tokens`.token
                FROM `settings`'.$join.'
                WHERE ' . ($this->_type ? 'type = "' . mysql_real_escape_string($this->_type) . '" AND ' : '') . ' name LIKE "%' . mysql_real_escape_string($searchString) . '%" ORDER BY name ASC';
        $rs = mysql_query($sql) or die(mysql_query());
        if ($rs) {
            $rows = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $rows[] = array(
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'token' => $row['token'],
                    'disabled' => $row['disabled'],
                );
            }
           //print_r($rows);
            return $rows;
        } else {
            return false;
        }
    }

    public function save($token='', $type='', $org_id='',$media_id='',$typeName='')
    {
        if (is_array($this->settings)) {
            foreach ($this->settings as $id => $name) {
                if ($id) {
                    if ($name) {
                        $sql = 'UPDATE `' . $this->_table . '` SET name = "' . mysql_real_escape_string(trim($name)) . '" WHERE id = "' . mysql_real_escape_string($id) . '"';

                    } else {
						
                        // Delete from the settings table, but also from the service table when type is
                        // referred_to or referred_to_service
                        if ($this->_type === 'referred_to') {
							$sql = '
                            DELETE s.*, rs.*
                            FROM `settings` s
                            LEFT JOIN service rs ON s.id = rs.referred_to_id
                            WHERE s.id = "' . mysql_real_escape_string($id) . '"';
                            //die($sql);
                        } else if ($this->_type === 'referred_to_service') {
                           $sql = '
                            DELETE s.*, rs.*
                            FROM `settings` s
                            LEFT JOIN service rs ON s.id = rs.service_id
                            WHERE s.id = "' . mysql_real_escape_string($id) . '"';
                            //die($sql);
                        } else {
							$sql = 'DELETE FROM `' . $this->_table . '` WHERE id = "' . mysql_real_escape_string($id) . '"';
                            //echo $sql; die();
                        }
                    }
                } else if ($name) {
                    if($type && $type == 'organization_name') {
                        //check if record already exists update it
                        $sql_check = "Select * from settings Where type='".$type."' AND name='".mysql_real_escape_string($name)."'";
                        $rs_check = mysql_query($sql_check);
                        $row = mysql_fetch_array($rs_check, MYSQL_ASSOC);
                        if($row) {
                            $id = $row['id'];
                            $sql = 'UPDATE `' . $this->_table . '` SET name = "' . mysql_real_escape_string(trim($name)) . '" WHERE id = "' . mysql_real_escape_string($id) . '"';
                        } else {
                            $sql = 'INSERT INTO `' . $this->_table . '` SET type = "organization_name", name = "' . mysql_real_escape_string(trim($name)) .  '"';
                        }
                    }
					

					else {
                       $sql = 'INSERT INTO `' . $this->_table . '` SET type = "' . mysql_real_escape_string($this->_type) . '", name = "' . mysql_real_escape_string(trim($name)) .  '"';
						
                    }
					if($type && $type == 'how_heard_category'){
						if($typeName && !empty($typeName)){
						$sql = 'INSERT INTO `' . $this->_table . '` SET parent = "'.$media_id.'", type = "' . mysql_real_escape_string(str_replace(array(' ', '/'), array('_', '_'), strtolower($typeName))) . '", name = "' . mysql_real_escape_string(trim($name)) .  '"';		
						}
						else{
						$sql = 'INSERT INTO `' . $this->_table . '` SET  type = "' . mysql_real_escape_string(trim($type)) . '", name = "' . mysql_real_escape_string(trim($name)) .  '"';		
						}	
					}
					              
                }
                $rs = mysql_query($sql);
                if(!$id)
                {
                    $id = mysql_insert_id();
                    if ($this->_type === 'county') { //add dummy record for county_zipcodes
                        $sql = 'INSERT INTO `county_zipcodes` SET city = "", county_id = "' . mysql_real_escape_string($id) . '", zip_code = "0"';
                        $rs = mysql_query($sql);
                    }
                }
                if(!empty($org_id)) {
                    $sql = '
                        INSERT INTO
                            `service`
                        SET
                            referred_to_site_id = "' . mysql_real_escape_string($id) .  '",
                            service_id = "0",
                            organization_name_id = "' . mysql_real_escape_string($org_id) .  '",
                            disabled = "0"';
                   
                    mysql_query($sql);
                }
                $this->saveToken($id, $token);         
            }
            $this->setSettings();
        }
    }

    public function saveToken($id,$token)
    {
        $sql = mysql_query('SELECT * from '.$this->_token_table.' WHERE region_id = '.$id);
        $numResults = mysql_num_rows($sql);
       
        if($numResults > 0)
        {
            mysql_query('UPDATE `' . $this->_token_table . '` SET token = "' . mysql_real_escape_string(trim($token)) .  '" WHERE region_id = "' . mysql_real_escape_string($id) . '"');
        }
        else{
            if(empty($token))
                return false;
            mysql_query('INSERT INTO `' . $this->_token_table . '` SET region_id = "' . mysql_real_escape_string($id) . '", token = "' . mysql_real_escape_string(trim($token)) .  '"');
        }
        //echo mysql_info();
    }

        
    
    public function displaySelect(
        $name,
        $selected,
        $label = '',
        $tabIndex = '',
        $required = false,
        $addtlclasses = null,
        $useId = true,
        $natsort = false,
        $showDisabled = false,
        $id = ''
    ) {
		
        $sql = '
        SELECT
            *
        FROM `' . $this->_table . '`
        WHERE
            type = "' . mysql_real_escape_string($this->_type) . '"';
        if($this->_etype)
            $sql .= ' or type = "' . mysql_real_escape_string($this->_etype) . '"';
        
        $filtersql = '';
        // Filter out disabled if not showing disabled
        if (!$showDisabled && empty($selected)) {
            $filtersql = ' AND (disabled != "1")';
        }

        // Filter out disabled with the exception of the selected item
        if (!$showDisabled && $selected) {
            $filterSelected = '';
            if (is_array($selected)) 
			{
                $items = array();
                foreach ($selected as $selectedItem) {
                    $items[] = mysql_real_escape_string($selectedItem);
                }
                $filterSelected = "('" . implode("','", $items) . "')";
            }

            if ($filterSelected) {
                if ($useId) {
                    $filtersql = ' AND (disabled != "1" OR id IN ' . $filterSelected . ')';
                } else {
                    $filtersql = ' AND (disabled != "1" OR name IN ' . $filterSelected . ')';
                }
            } else {
                if ($useId) {
                    $filtersql = ' AND (disabled != "1" OR id = "' . mysql_real_escape_string($selected) . '")';
                } else {
                    $filtersql = ' AND (disabled != "1" OR name = "' . mysql_real_escape_string($selected) . '")';
                }
            }
        }

        $sql .= $filtersql;
        $sql .= ' ORDER BY ' . ($natsort ? ' Length(name) ASC,' : '') . ' name ASC';

        $rs = mysql_query($sql);

        if ($label && ! is_array($selected)) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '';
        }

        $key = 'name';
        if ($useId) {
            $key = 'id';
        }
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $options .= '<option value="' . $row[$key] . '"' . ((is_array($selected) && in_array($row[$key], $selected)) || $selected == $row[$key] ? ' selected="selected"' : '') . '>' . $row['name'] . ($row['disabled'] ? ' (Inactive)': '') . '</option>';
        }
        $id = !empty($id) ? $id : $name;
        $select = '<select id="' . $id . '" class="setting' . ($required ? ' required' : '') . ($addtlclasses ? ' ' . $addtlclasses : '') . '" name="' . $name . (is_array($selected) ? '[]' : '') . '"  ' . (is_array($selected) ? ' multiple="mutliple" size="5"' : '') . '>' . $options . '</select>';


        return $select;
    }
	
	public function displayOrganizationBySubTypeSelect(
        $name,
        $selected,
        $label = '',
        $tabIndex = '',
        $required = false,
        $addtlclasses = null,
        $useId = true,
        $natsort = false,
        $showDisabled = false,
        $id = ''
    ) {
		
        $sql = '
        SELECT
            *
        FROM `' . $this->_table . '`
        WHERE
            sub_type = "' . mysql_real_escape_string($this->_type) . '"';

        $filtersql = '';
        // Filter out disabled if not showing disabled
        if (!$showDisabled && empty($selected)) {
            $filtersql = ' AND (disabled != "1")';
        }

        // Filter out disabled with the exception of the selected item
        if (!$showDisabled && $selected) {
            $filterSelected = '';
            if (is_array($selected)) 
			{
                $items = array();
                foreach ($selected as $selectedItem) {
                    $items[] = mysql_real_escape_string($selectedItem);
                }
                $filterSelected = "('" . implode("','", $items) . "')";
            }

            if ($filterSelected) {
                if ($useId) {
                    $filtersql = ' AND (disabled != "1" OR id IN ' . $filterSelected . ')';
                } else {
                    $filtersql = ' AND (disabled != "1" OR name IN ' . $filterSelected . ')';
                }
            } else {
                if ($useId) {
                    $filtersql = ' AND (disabled != "1" OR id = "' . mysql_real_escape_string($selected) . '")';
                } else {
                    $filtersql = ' AND (disabled != "1" OR name = "' . mysql_real_escape_string($selected) . '")';
                }
            }
        }

        $sql .= $filtersql;
        $sql .= ' ORDER BY ' . ($natsort ? ' Length(name) ASC,' : '') . ' name ASC';

        $rs = mysql_query($sql);

        if ($label && ! is_array($selected)) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '';
        }
        $key = 'name';
        if ($useId) {
            $key = 'name';
        }
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $options .= '<option value="' . $row[$key] . '"' . ((is_array($selected) && in_array($row[$key], $selected)) || $selected == $row[$key] ? ' selected="selected"' : '') . '>' . $row['name'] . ($row['disabled'] ? ' (Inactive)': '') . '</option>';
        }
        $id = !empty($id) ? $id : $name;
        $select = '<select id="' . $id . '" class="setting' . ($required ? ' required' : '') . ($addtlclasses ? ' ' . $addtlclasses : '') . '" name="' . $name . (is_array($selected) ? '[]' : '') . '"  ' . (is_array($selected) ? ' multiple="mutliple" size="5"' : '') . '>' . $options . '</select>';
        return $select;
    }

    public function displayBestHoursSelect($name, $selected, $label = '', $tabIndex = '', $required = false, $addtlclasses = null, $useId = true, $natsort = false)
    {
        $sql = '
        SELECT
            *,
            IF(name="Any Time", 0, IF(name REGEXP "AM$", 1, (IF(name REGEXP "PM$", 2, 3)))) custom1,
            IF(name REGEXP "AM$", LPAD(SUBSTRING_INDEX(name, " ", 1), 5, "0"), 1) am,
            IF(name REGEXP "PM$", LPAD(SUBSTRING_INDEX(name, " ", 1), 5, "2"), 1) pm
        FROM
            `settings`
        WHERE
            type = "best_hours"
        ORDER BY custom1, am ASC, pm ASC';
        $rs = mysql_query($sql);
        if ($label && ! is_array($selected)) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '';
        }
        $key = 'name';
        if ($useId) {
            $key = 'id';
        }
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $options .= '<option value="' . $row[$key] . '"' . ((is_array($selected) && in_array($row[$key], $selected)) || $selected == $row[$key] ? ' selected="selected"' : '') . '>' . $row['name'] . '</option>';
        }
        $select = '<select id="' . $name . '" class="setting' . ($required ? ' required' : '') . ($addtlclasses ? ' ' . $addtlclasses : '') . '" style="margin-top: 5px;" name="' . $name . (is_array($selected) ? '[]' : '') . '"  ' . (is_array($selected) ? ' multiple="mutliple" size="5"' : '') . '>' . $options . '</select>';
        return $select;
    }

    public function getValue($id)
    {
        $value = '';
        if ($id) {
            $sql = 'SELECT name FROM `' . $this->_table . '` WHERE id = "' . mysql_real_escape_string($id) . '"';
            $rs = mysql_query($sql) or die(mysql_error());
            if (mysql_affected_rows()) {
                $row = mysql_fetch_array($rs, MYSQL_ASSOC);
                $value = $row['name'];
            }
        }
        return $value;
    }

    public function updateSettingName($id, $name)
    {
        $value = '';
        if ($id && $name) {
            $sql = 'UPDATE `' . $this->_table . '` SET `name` = "' . mysql_real_escape_string(trim($name)) . '" WHERE id = "' . mysql_real_escape_string($id) . '"';
            $rs = mysql_query($sql) or die(mysql_error());
        }
        return mysql_affected_rows();
    }

    public function updateSettingDisabled($id, $value)
    {
        if ($id) {
            $sql = 'UPDATE `' . $this->_table . '` SET `disabled` = "' . mysql_real_escape_string($value ? '1' : '0') . '" WHERE id = "' . mysql_real_escape_string($id) . '"';
            $rs = mysql_query($sql) or die(mysql_error());
        }
        return $rs;
    }

    public function displayHeardDetailsSelect(
        $parent,
        $name,
        $selected,
        $label = '',
        $tabIndex = '',
        $required = false,
		$blank ='',
        $addtlclasses = null,
        $useId = true,
        $natsort = false,
        $showDisabled = false,
        $id = ''
    ) {
        $stype = '';
        if(!empty($parent)) {
           $sql = '
            SELECT
                *
            FROM `' . $this->_table . '`
            WHERE
                id = "' . mysql_real_escape_string($parent) . '" order by name asc';
            $rs_select  = mysql_query($sql);
            $row_select = mysql_fetch_array($rs_select, MYSQL_ASSOC);
            if(!empty($row_select)) {
                $stype = strtolower($row_select['sub_type']);
                $parent = $row_select['id'];
            }
        }
		//echo $stype;
        $sql = '';
        //$stype = 'organization_type';
        if($stype == 'organization_type') {
            //fetch all organization:site names whoes organization_type matches with selected one
            $sql = '
            SELECT os.id, orgn.name as name, `os`.`organization_site_id` as organization_site_id, sites.name as site, os.id as organization_sites_id, os.status, organization_type.name organization_type 
            FROM `organizations` 
            LEFT JOIN settings orgn ON orgn.id=`organizations`.organization_name_id 
            JOIN organization_sites os ON os.organization_id=`organizations`.id 
            LEFT JOIN settings sites ON sites.id=os.organization_site_id 
            LEFT JOIN `settings` organization_type ON organization_type_id = organization_type.id 
            WHERE 1 
            /*AND organization_type_id = "' . mysql_real_escape_string($parent) . '" */
            GROUP BY `os`.id 
            ORDER BY name asc, site asc';
        } elseif($stype == 'event_type') {
            $sql = 'Select * from settings Where type="event_type" and disabled="0" order by name asc';
        } 
		
		elseif(!empty($stype)) {
          $sql = '
            SELECT
                *
            FROM `' . $this->_table . '`
            WHERE
                parent = "'.$parent.'" order by name asc';
        }
        //echo $sql;
        $filtersql = '';
        // Filter out disabled if not showing disabled
        /*if (!$showDisabled && empty($selected)) {
            $filtersql = ' AND (disabled != "1")';
        }

        // Filter out disabled with the exception of the selected item
        if (!$showDisabled && $selected) {
            $filterSelected = '';
            if (is_array($selected)) 
            {
                $items = array();
                foreach ($selected as $selectedItem) {
                    $items[] = mysql_real_escape_string($selectedItem);
                }
                $filterSelected = "('" . implode("','", $items) . "')";
            }

            if ($filterSelected) {
                if ($useId) {
                    $filtersql = ' AND (disabled != "1" OR id IN ' . $filterSelected . ')';
                } else {
                    $filtersql = ' AND (disabled != "1" OR name IN ' . $filterSelected . ')';
                }
            } else {
                if ($useId) {
                    $filtersql = ' AND (disabled != "1" OR id = "' . mysql_real_escape_string($selected) . '")';
                } else {
                    $filtersql = ' AND (disabled != "1" OR name = "' . mysql_real_escape_string($selected) . '")';
                }
            }
        }

        $sql .= $filtersql;
        $sql .= ' ORDER BY ' . ($natsort ? ' Length(name) ASC,' : '') . ' name ASC';
*/  

        if(empty($sql))
            return false;
        $rs = mysql_query($sql);

        if ($label && ! is_array($selected)) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '';
        }
        $options = '<option value="">' . $label . '</option>';
        $key = 'name';
        if ($useId) {
            $key = 'id';
        }
        $key = 'id';
        if(empty($rs))
            return false;
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $row['disabled'] = 0;
            $org_site = !empty($row['name']) ? $row['name'] : '';
            $org_site .= !empty($row['site']) ? ':'.$row['site'] : '';
            $options .= '<option value="' . $row[$key] . '"' . ((is_array($selected) && in_array($row[$key], $selected)) || $selected == $row[$key] ? ' selected="selected"' : '') . '>' . $org_site . ($row['disabled'] ? ' (Inactive)': '') . '</option>';
        }
        $id = !empty($id) ? $id : $name;
        $select = '<select id="' . $id . '" class="setting' . ($required ? ' required' : '') . ($addtlclasses ? ' ' . $addtlclasses : '') . '" name="' . $name . (is_array($selected) ? '[]' : '') . '"  ' . (is_array($selected) ? ' multiple="mutliple" size="5"' : '') . '>' . $options . '</select>';
        return $select;
    }

    public function getHowHearDetails($parent = null, $how_heard_details_id = null) {
        if(empty($how_heard_category_id))
            return false;

        $stype = '';
        if(!empty($parent)) {
            $sql = '
            SELECT
                *
            FROM `' . $this->_table . '`
            WHERE
                id = "' . mysql_real_escape_string($parent) . '"';
            $rs_select  = mysql_query($sql);
            $row_select = mysql_fetch_array($rs_select, MYSQL_ASSOC);
            if(!empty($row_select)) {
                $stype = strtolower($row_select['sub_type']);
            }
        }
        $sql = '';
        //$stype = 'organization_type';
        if($stype == 'organization_type') {
            //fetch all organization:site names whoes organization_type matches with selected one
            $sql = '
            SELECT os.id, orgn.name as name, `os`.`organization_site_id` as organization_site_id, sites.name as site, os.id as organization_sites_id, os.status, organization_type.name organization_type 
            FROM `organizations` 
            LEFT JOIN settings orgn ON orgn.id=`organizations`.organization_name_id 
            JOIN organization_sites os ON os.organization_id=`organizations`.id 
            LEFT JOIN settings sites ON sites.id=os.organization_site_id 
            LEFT JOIN `settings` organization_type ON organization_type_id = organization_type.id 
            WHERE 1 
            AND organization_type_id = "' . mysql_real_escape_string($parent) . '" 
            GROUP BY `os`.id 
            ORDER BY name asc, site asc';
        }elseif($stype == 'event_type') {
            $sql = 'Select * from settings Where type="event_type" and disabled="0"';
        }
		elseif(!empty($stype)) {
            $sql = '
            SELECT
                *
            FROM `' . $this->_table . '`
            WHERE
                parent = "'.$parent.'"';
        }

        $rs = mysql_query($sql);
        if(empty($rs))
            return false;
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $row['disabled'] = 0;
            $org_site = !empty($row['name']) ? $row['name'] : '';
            $org_site .= !empty($row['site']) ? ':'.$row['site'] : '';
            $options .= '<option value="' . $row[$key] . '"' . ((is_array($selected) && in_array($row[$key], $selected)) || $selected == $row[$key] ? ' selected="selected"' : '') . '>' . $org_site . ($row['disabled'] ? ' (Inactive)': '') . '</option>';
        }
    }
	
	 public function DisplayHowHear($family_how_heard_id = null, $how_heard_details_id = null) {
	
        $stype = '';
        if(!empty($family_how_heard_id)) {
			$sql = '
            SELECT
                *
            FROM `' . $this->_table . '`
            WHERE
                id = "' . mysql_real_escape_string($family_how_heard_id) . '" order by name ASC';
            $rs_select  = mysql_query($sql);
            $row_select = mysql_fetch_array($rs_select, MYSQL_ASSOC);
            if(!empty($row_select)) {
                $stype = strtolower($row_select['sub_type']);
            }
			
        }
		
        $sql = '';
        //$stype = 'organization_type';
        if($stype == 'organization_type') {
            //fetch all organization:site names whoes organization_type matches with selected one
          $sql = '
            SELECT os.id, orgn.name as name, `os`.`organization_site_id` as organization_site_id, sites.name as site, os.id as organization_sites_id, os.status, organization_type.name organization_type 
            FROM `organizations` 
            LEFT JOIN settings orgn ON orgn.id=`organizations`.organization_name_id 
            JOIN organization_sites os ON os.organization_id=`organizations`.id 
            LEFT JOIN settings sites ON sites.id=os.organization_site_id 
            LEFT JOIN `settings` organization_type ON organization_type_id = organization_type.id 
            WHERE 1 
			AND os.id = "' . mysql_real_escape_string($how_heard_details_id) . '" 
            GROUP BY `os`.id 
            ORDER BY name asc, site asc';
        }elseif($stype == 'event_type') {
            $sql = 'Select * from settings WHERE type="event_type" AND disabled="0" AND id = "' . mysql_real_escape_string($how_heard_details_id) . '" order by name ASC';
        }
		elseif(!empty($stype)) {
            $sql = '
            SELECT
                *
            FROM `' . $this->_table . '`
            WHERE
                id = "' . mysql_real_escape_string($how_heard_details_id) . '" order by name ASC';
        }
		//echo $sql;
       $rs = mysql_query($sql);
	   
        if(!empty($rs)){
			$row = mysql_fetch_array($rs, MYSQL_ASSOC);
            $org_site = !empty($row['name']) ? $row['name'] : '';
            $org_site .= !empty($row['site']) ? ':'.$row['site'] : '';
		return $org_site;
		}
		else{
			return false;
		}
 
    }

    public function getSubTypes($parentID = null) {
        if(empty($parentID))
            return false;

         $sql = '
            SELECT
                *
            FROM `' . $this->_table . '`
            WHERE
                parent = "'.$parentID.'"';

        $rs = mysql_query($sql);
        if(empty($rs))
            return false;
        $rows = array();
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }
}
