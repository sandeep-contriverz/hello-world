<?php

namespace Hmg\Models;

use Hmg\Models\RegionCounties;
use Hmg\Models\User;


class Family
{
    private $_table = 'families';
    private $_childTable = 'children';
    private $_notesTable = 'notes';
	private $_providerTable = 'family_provider';

    public $family = array(
        'id'                                => null,
        'last_name_1'                       => null,
        'first_name_1'                      => null,
        'relationship_1_id'                 => null,
        'last_name_2'                       => null,
        'first_name_2'                      => null,
        'relationship_2_id'                 => null,
        'language_id'                       => null,
        'address'                           => null,
        'city'                              => null,
        'state'                             => null,
        'zip'                               => null,
        'county'                            => null,
        'primary_phone'                     => null,
        'secondary_phone'                   => null,
        'best_times'                        => null,
        'best_times_start'                  => null,
        'best_times_end'                    => null,
        'best_times_days'                   => null,
        'email'                             => null,
        'contact_phone'                     => null,
        'contact_email'                     => null,
        'contact_text'                      => null,
        'hmg_worker'                        => null,
        'cc_level'                          => null,
        'status'                            => null,
        'inquirer'                          => null,
        'enrollment_filed'                  => null,
        'enrollment_type'                   => null,
        //'date_permission_granted'           => null,
        //'date_permission_granted_formatted' => null,
        'asq_preference'                    => null,
        'attachments'                       => null,
        'notes'                             => null,
        'who_called_id'                     => null,
        'family_heard_id'                   => null,
        'how_heard_details_id'              => null,
        'call_reason_id'                    => null,
        'success_story'                     => 'No',
        'success_story_notes'               => '',
        'race_id'                           => null,
        'ethnicity_id'                      => null,
        'health_insurance'                  => null,
        'health_insurance_notes'            => null,
        'children'                          => null,
		'point_of_entry' 					=> null,
		'ecids_permission' 					=> 'Not Given',
		'ecids_permission_granted' 			=> null,'ecids_permission_granted_formatted'	=> null,
		'ecids_permission_revoked' 				=> null,'ecids_permission_revoked_formatted'	=> null,
		'sharing_info'							=> 'Not Given',
		'sharing_permission_granted'			=> null,
		'sharing_permission_granted_formatted'	=> null,
		'sharing_permission_revoked'			=> null,
		'sharing_permission_revoked_formatted'	=> null,
		'provider'								=> null
    );
    public $message = null;

    public function __construct()
    {
    }

    public function setFamily($family)
    {
		
        // always add new data
        $this->family = array();
        if (is_array($family)) {
			
            foreach ($family as $key => $value) {
				
                $this->family[$key] = $value;
            }
        }
    }

    public function setById($id)
    {
        if (is_numeric($id)) {
            $sql = '
                SELECT
                    *,
                    /*DATE_FORMAT(date_permission_granted, "%m/%d/%Y") date_permission_granted_formatted,*/
					DATE_FORMAT(ecids_permission_granted, "%m/%d/%Y") ecids_permission_granted_formatted,
					DATE_FORMAT(ecids_permission_revoked, "%m/%d/%Y") ecids_permission_revoked_formatted,
					DATE_FORMAT(sharing_permission_granted, "%m/%d/%Y") sharing_permission_granted_formatted,
					DATE_FORMAT(sharing_permission_revoked, "%m/%d/%Y") sharing_permission_revoked_formatted
                FROM `' . mysql_real_escape_string($this->_table) . '`
                WHERE `id` = "' . mysql_real_escape_string($id) . '"';
            $rs = mysql_query($sql) or die($sql);
            if (mysql_num_rows($rs)) {
                $this->setFamily(mysql_fetch_array($rs, MYSQL_ASSOC));
                $this->setChildren($id);
				$this->setProviders($id);

            }
        }
    }
	public function setProviders($id)
    {
        $this->family['provider'] = null;
        if (is_numeric($id)) {
           $sql = '
                SELECT 
                    *, 
                    DATE_FORMAT(date_permission_granted, "%m/%d/%Y") date_permission_granted_formatted,
                    DATE_FORMAT(date_permission_revoked, "%m/%d/%Y") date_permission_revoked_formatted
                FROM `family_provider`
                WHERE `family_id` = "' . mysql_real_escape_string($id) . '" ORDER BY date_permission_granted DESC';
            $rs = mysql_query($sql);
            while ($prov= mysql_fetch_array($rs, MYSQL_ASSOC)) {
				
                if ($prov['date_permission_granted'] == '0000-00-00') {
                    $prov['date_permission_granted']           = null;
                    $prov['date_permission_granted_formatted'] = null;
                }
                if ($prov['date_permission_revoked'] == '0000-00-00') {
                    $prov['date_permission_revoked']           = null;
                    $prov['date_permission_revoked_formatted'] = null;
                }
                $this->family['provider'][$prov['family_id']] = $prov;
            }
        }
    }
    public function setChildren($id)
    {
        $this->family['children'] = null;
        if (is_numeric($id)) {
            $sql = '
                SELECT 
                    *, 
                    DATE_FORMAT(birth_date, "%m/%d/%Y") birth_date_formatted,
                    DATE_FORMAT(birth_due_date, "%m/%d/%Y") birth_due_date_formatted
                FROM `children`
                WHERE `parent_id` = "' . mysql_real_escape_string($id) . '" ORDER BY birth_date DESC';
            $rs = mysql_query($sql);
            while ($child = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                if ($child['birth_date'] == '0000-00-00') {
                    $child['birth_date']           = null;
                    $child['birth_date_formatted'] = null;
                }
                if ($child['birth_due_date'] == '0000-00-00') {
                    $child['birth_due_date']           = null;
                    $child['birth_due_date_formatted'] = null;
                }
                $this->family['children'][$child['id']] = $child;
            }
        }
    }

    public function getAll()
    {
        return $this->family;
    }

    public function save()
    {
        $user = new User();
        $setFields = '';
	
	
        foreach ($this->family as $field => $value) {
			
            if ($field != 'children' && $field != 'provider') {
                if($field == 'hmg_worker'){
                  $value = $user->getByName($value);
				}
                if($field == 'ecids_permission_granted'){
					if(!empty($value)){
					$value = date('Y-m-d', strtotime($value));
					}
					
				}
				if($field == 'ecids_permission_revoked'){
					if(!empty($value)){
					$value = date('Y-m-d', strtotime($value));
					} 
				 }
				if($field == 'sharing_permission_granted'){
					if(!empty($value)){
					$value = date('Y-m-d', strtotime($value));
					}
				 }
				if($field == 'sharing_permission_revoked'){
					if(!empty($value)){
					$value = date('Y-m-d', strtotime($value));
					} 
				 }				 
                $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
            }
        }
        if ($this->family["id"]) {
			
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . ', modified = NOW()
                    WHERE `id` = "' . mysql_real_escape_string($this->family['id']) . '"';
            $rs = mysql_query($sql) or die($sql);
        } else {
            $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . ', modified = NOW()';
            $rs = mysql_query($sql) or die($sql);
            $id = mysql_insert_id();
            if ($id) {
                $this->family['id'] = $id;
            }
        }
		$savedProvider = $this->saveProvider();
        if (mysql_affected_rows()) {
            $savedChildren = $this->saveChildren();
			
            $this->setById($this->family['id']);
            return $sql;
        } else {
            $this->setById($this->family['id']);
            return false;
        }
    }
	public function saveProvider()
    {
		
        if (is_array($this->family["provider"])) {
            $affectedProvider = 0;
			
            foreach ($this->family["provider"] as $prov1) {			
                   if($prov1['date_permission_granted']){
					$prov1['date_permission_granted'] = date('Y-m-d', strtotime($prov1['date_permission_granted'])); 
					}else{
						$prov1['date_permission_granted']='';
					}
					if($prov1['date_permission_revoked']){
					$prov1['date_permission_revoked'] = date('Y-m-d', strtotime($prov1['date_permission_revoked'])); 
					}else{
						$prov1['date_permission_revoked']='';
					}
					
                    $setFields = '`family_id` = "' . mysql_real_escape_string($this->family['id']) . '"';

                    list($prov1['organization_site_id'] ,   $prov1['contact_id']) = explode('-',$prov1['provider_id']);
                    foreach ($prov1 as $field => $value) {
						
											
					
                        if (isset($value)) {
                            $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
                       }
                    }
					
                    /*if (isset($prov1["provider_id"]) && is_numeric($prov1["provider_id"]) && $prov["provider_id"]) {
                        $sql = 'UPDATE `' . mysql_real_escape_string($this->_providerTable) . '` SET ' . $setFields . '
                                WHERE `provider_id` = "' . mysql_real_escape_string($prov1['id']) . '"';
                        $rs = mysql_query($sql) or die($sql);
                    } else {*/
                        $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_providerTable) . '` SET ' . $setFields;

                        $rs = mysql_query($sql) or die($sql);
                        
                    //}
                    if (mysql_affected_rows()) {
                        $affectedProvider++;
                    }
                    
                    
                }
            }
            if ($affectedProvider) {
                return true;
            } else {
                return false;
            }
	
    }
    public function saveChildren()
    {
        if (is_array($this->family["children"])) {
            $affectedChildren = 0;
			;
            foreach ($this->family["children"] as $child) {
                if ($child['first']) {
                    $note = $child['notes'];
                    unset(
                        $child["birth_date_formatted"],
                        $child["birth_due_date_formatted"],
                        $child['notes']
                    );
                    if ($child['birth_date']) {
                        $child['birth_date'] = date('Y-m-d', strtotime($child['birth_date']));
                    } else {
                        $child['birth_date'] = '';
                    }
                    if (!empty($child['birth_due_date'])) {
                        $child['birth_due_date'] = date('Y-m-d', strtotime($child['birth_due_date']));
                    } else {
                        $child['birth_due_date'] = '';
                    }
                    $setFields = '`parent_id` = "' . mysql_real_escape_string($this->family['id']) . '"';
                    foreach ($child as $field => $value) {
                        if (isset($value)) {
                            $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
                        }
                    }
                    if (isset($child["id"]) && is_numeric($child["id"]) && $child["id"]) {
                        $sql = 'UPDATE `' . mysql_real_escape_string($this->_childTable) . '` SET ' . $setFields . '
                                WHERE `id` = "' . mysql_real_escape_string($child['id']) . '"';
                        $rs = mysql_query($sql) or die($sql);
                    } else {
                        $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_childTable) . '` SET ' . $setFields;
                        $rs = mysql_query($sql) or die($sql);
                        $child["id"] = mysql_insert_id();
                    }
                    if (mysql_affected_rows()) {
                        $affectedChildren++;
                    }
                    // Add note if there is one.
                    if ($note && $child['id']) {
                        $sql = 'INSERT INTO child_notes SET child_id = "' . mysql_real_escape_string($child['id']) . '"'
                            . ', hmg_worker = "' . mysql_real_escape_string($_SESSION['user']['id']) . '"'
                            . ', note = "' . mysql_real_escape_string($note) . '", modified = CURDATE()';
                        $rs = mysql_query($sql);
                    }
                }
            }
            if ($affectedChildren) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function delete()
    {
        if ($this->family["id"]) {
            $sql = 'DELETE FROM `' . mysql_real_escape_string($this->_notesTable) . '`
                    WHERE `family_id` = "' . mysql_real_escape_string($this->family['id']) . '"';
            $rs = mysql_query($sql);
            $sql = 'DELETE FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($this->family['id']) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function deleteChildren()
    {
        if (is_array($this->family["children"])) {
            foreach ($this->family["children"] as $child) {
                $deleted = $this->deleteChild($child["id"]);
            }
            return $deleted;
        }
    }

    public function deleteChild($childId)
    {
        if ($this->family["id"]) {
            $sql = 'DELETE c, s, sa, n, fu, r, pr FROM `children` c
                        LEFT JOIN `child_developmental_screenings` s ON c.id = s.child_id
                        LEFT JOIN `child_screening_attachments` sa ON s.id = sa.screening_id
                        LEFT JOIN `child_notes` n ON c.id = n.child_id
                        LEFT JOIN `child_follow_up` fu ON c.id = fu.child_id
                        LEFT JOIN `child_referrals` r ON c.id = r.child_id
                        LEFT JOIN `child_prior_resources` pr ON c.id = pr.child_id
                    WHERE c.id = "' . mysql_real_escape_string($childId) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                $this->setChildren($this->family['id']);
                return true;
            } else {
                return false;
            }
        }
    }

    public function displayEnumSelect($name, $field, $selected, $label = '', $tabIndex = '')
    {
        $sql = 'SHOW COLUMNS FROM `' . $this->_table . '` LIKE  "' . mysql_real_escape_string($field) . '"';
        $rs = mysql_query($sql);
        if ($rs) {
            $row = mysql_fetch_array($rs, MYSQL_ASSOC);
            $values = explode(',', str_replace('enum(', '', rtrim($row['Type'], ')')));
            sort($values);
            if (is_array($values)) {
                if ($label) {
                    $options = '<option value="">' . $label . '</option>';
                } else {
                    $options = '';
                }
                foreach ($values as $key => $value) {
                    $trimmed = trim($value, "'");
                    $options .= '<option value="' . $trimmed . '"' . ($selected == $trimmed ? ' selected="selected"' : '') . '>' . $trimmed . '</option>';
                }
            }
            $select = '<select id="' . $field . '" class="' . $field . '" name="' . $name . '" tabindex="' . $tabIndex . '">' . $options . '</select>';
            return $select;
        }
    }

    public function displayChildEnumSelect($name, $field, $selected, $label = '', $tabIndex = '')
    {
        $sql = 'SHOW COLUMNS FROM `' . $this->_childTable . '` LIKE  "' . mysql_real_escape_string($field) . '"';
        $rs = mysql_query($sql);
        if ($rs) {
            $row = mysql_fetch_array($rs, MYSQL_ASSOC);
            $values = explode(',', str_replace('enum(', '', rtrim($row['Type'], ')')));
            sort($values);
            if (is_array($values)) {
                $options = '<option value="' . $selected . '">' . $selected . '</option>'; // Shows current value in select
                foreach ($values as $key => $value) {
                    $trimmed = trim($value, "'");
                    $options .= '<option value="' . $trimmed . '"' . ($selected == $trimmed ? ' selected="selected"' : '') . '>' . $trimmed . '</option>';
                }
            }
            $select = '<select id="' . $field . '" class="' . $field . '" name="' . $name . '" tabindex="' . $tabIndex . '">' . $options . '</select>';
            return $select;
        }
    }

    public function displayEnumOptions($name, $field, $selected, $tabIndex = '')
    {
        $sql = 'SHOW COLUMNS FROM `' . $this->_table . '` LIKE  "' . mysql_real_escape_string($field) . '"';
        $rs = mysql_query($sql);
        if ($rs) {
            $row = mysql_fetch_array($rs, MYSQL_ASSOC);
            $values = explode(',', str_replace('enum(', '', rtrim($row['Type'], ')')));
            if (is_array($values)) {
                $options = '';
                foreach ($values as $key => $value) {
                    $trimmed = trim($value, "'");
                    $options .= '<input type="radio" name="' . $name . '" value="' . $trimmed . '" class="radio ' . $field . '"'
                    . ($selected == $trimmed ? ' checked="checked"' : '') . ' tabindex="' . $tabIndex . '" /> ' . $trimmed;
                    if ($tabIndex) {
                         $tabIndex++;
                    }
                }
            }
            return $options;
        }
    }

    /* public function displayHmgWorkerSelect($name, $selected, $label = '', $tabIndex = '', $class = '', $showAll = false, $showID = false,$isID=false) */
    /* { */
		
	/* 	$user = new User(); */
	/* 	//echo $selected */
	/* 	$user->setById($selected); */
		
    /*     $sql = 'SELECT id,hmg_worker, status FROM `users` WHERE hmg_worker != ""' . ($showAll ? '' : ' AND status="1"') . ' ORDER BY status desc, hmg_worker asc'; */
    /*     $rs = mysql_query($sql); */
    /*     if ($label) { */
    /*         $options = '<option value="">' . $label . '</option>'; */
    /*     } else { */
    /*         $options = '<option value="' . $selected . '">' . $selected . '</option>'; */
    /*     } */
    /*     $activeWorker = false; */
    /*     while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) { */
    /*         if ($selected === $row['hmg_worker']) { */
    /*             $activeWorker = true; */
    /*         } */
    /*         if($isID) { */
    /*             $activeWorker = true; */
    /*             $options .= '<option value="' . $row['id'] . '"' . ($selected == $row['id'] ? ' selected="selected"' : '') . '>' . $row['hmg_worker'] . '</option>'; */
    /*         }else */
    /*         if($showID) { */
    /*             $options .= '<option value="' . $row['id'] . '">' . $row['hmg_worker'] . (!$row['status'] ? ' (Inactive)' : '') . '</option>'; */
    /*         } else { */
    /*             $options .= '<option value="' . $row['hmg_worker'] . '"' . ($selected == $row['hmg_worker'] ? ' selected="selected"' : '') . '>' . $row['hmg_worker'] . (!$row['status'] ? ' (Inactive)' : '') . '</option>'; */
    /*         } */
    /*     } */
    /*     if ($selected && !$activeWorker) { */
    /*         $options = '<option value="' . $selected . '" selected="selected">' . $selected . ' (Inactive)</option>' . $options; */
    /*     } */
    /*     $select = '<select id="' . $name . '" class="' . ($class ? $class : 'hmg_worker') . '" name="' . $name . '" tabindex="' . $tabIndex . '"' . ($class === 'hide-hmg-worker' ? ' disabled="disabled"' : '') . '>' . $options . '</select>'; */
    /*     return $select; */
    /* } */
    public function displayHmgWorkerSelect($name, $selected, $label = '', $tabIndex = '', $class = '', $showAll = false, $useId = false)
    {
        $user = new User();
        //if(!empty($showAll))
            //$showAll = false;
        //echo $selected
        $user->setById($selected);
        $sql = 'SELECT id, hmg_worker, status FROM `users` WHERE hmg_worker != "" ORDER BY status desc, hmg_worker asc';

        $rs = mysql_query($sql);
        $label = trim($label);
        if (!empty($label)) {
            $options = '<option value="">' . $label . '</option>';
        }
		if (!empty($label) && $label=='all') {
            $options = '<option value="">HMG Worker</option>';
        }
		else {
            $options = '';
            //$options = '<option value="' . $selected . '">' . $selected . '</option>';
        }
        $options = '<option value="">HMG Worker</option>';
        $activeWorker = false;
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            if ($selected === $row['hmg_worker']) {
                $activeWorker = true;
            }
            if($row['id'] != $selected && $row['status'] != 1 && !$showAll)
                continue;
            if($useId) {
                $options .= '<option value="' . $row['id'] . '"' . ($selected == $row['id'] ? ' selected="selected"' : '') . '>' . $row['hmg_worker'] . (!$row['status']  ? ' (Inactive)' : '') . '</option>';
            } else {
                $options .= '<option value="' . $row['hmg_worker'] . '"' . ($selected == $row['hmg_worker'] ? ' selected="selected"' : '') . '>' . $row['hmg_worker'] . (!$row['status'] ? ' (Inactive)' : '') . '</option>';
            }
        }
        /*if ($selected && !$activeWorker) {
            $options = '<option value="' . $selected . '" selected="selected">' . $user->user['hmg_worker'] . ' (Inactive)</option>' . $options;
        }*/
        $select = '<select id="' . $name . '" class="' . ($class ? $class : 'hmg_worker') . '" name="' . $name . '" tabindex="' . $tabIndex . '"' . ($class === 'hide-hmg-worker' ? ' disabled="disabled"' : '') . '>' . $options . '</select>';
        return $select;
    }

    public function displayFieldSelect($name, $field, $selected, $label = '', $tabIndex = '', $multiSelect = false)
    {
        $sql = 'SELECT `' . mysql_real_escape_string($field) . '` FROM `' . $this->_table . '`
            WHERE 1 AND `' . mysql_real_escape_string($field) . '` != ""
            GROUP BY `' . mysql_real_escape_string($field) . '` ORDER BY `' . mysql_real_escape_string($field) . '`';
        $rs = mysql_query($sql);
        if ($label) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '';
        }
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            $options .= '<option value="' . $row[$field] . '"' . (is_array($selected) ? (in_array($row[$field], $selected) ? ' selected' : '') : $selected == $row[$field] ? ' selected="selected"' : '') . '>' . $row[$field] . '</option>';
        }
        $select = '<select' . ($multiSelect ? ' multiple size="11"' : '') . ' id="' . $name . '" class="select" name="' . $name . ($multiSelect ? '[]' : '') . '" tabindex="' . $tabIndex . '">' . $options . '</select>';
        return $select;
    }

    public function ucwordsIgnoreNonAlphabetic($words)
    {
        $words = preg_split('/\s+/', $words);
        foreach ($words as &$word) {
            $word = strtolower($word);
            $wordPieces = preg_split('/([^a-zA-Z]+)/', trim($word), null, PREG_SPLIT_DELIM_CAPTURE);
            if (count($wordPieces) > 1) {
                $wordPieces[2] = ucwords($wordPieces[2]);
                $word = implode('', $wordPieces);
            } else {
                $word = ucwords($wordPieces[0]);
            }
        }
        $ucWords = implode(' ', $words);
        return $ucWords;
    }

    public function isRegionFamily()
    {
        $isRegionFamily = true;

        if (!empty($_SESSION['user']['region_id'])) {
            $regionCounties = new RegionCounties($_SESSION['user']['region_id']);
            $countiesList = $regionCounties->getList();
            $countyNames = [];
            foreach ($countiesList as $result) {
                $countyNames[] = $result['county'];
            }
            if (!in_array($this->family['county'], $countyNames)) {
                $isRegionFamily = false;
            }
        }

        return $isRegionFamily;
    }

    public function inRegion($regionId)
    {
        $inRegion = true;

        if (!empty($regionId)) {
            $regionCounties = new RegionCounties($regionId);
            $countiesList = $regionCounties->getList();
            $countyNames = [];
            foreach ($countiesList as $result) {
                $countyNames[] = $result['county'];
            }
            if (!in_array($this->family['county'], $countyNames)) {
                $inRegion = false;
            }
        }

        return $inRegion;
    }

    /**
     *  Check region permissions for user
     */
    public function checkCountyRegion($data = array())
    {
        $inRegion = true;
        if (!empty($data)) {
            $id = (int) $data['cid'];
            if(empty($id)) {
                //check in settings table
                $sql = "SELECT * from settings WHERE type='county' AND name='".$data['cid']."'";
                $rs = mysql_query($sql);
                if (mysql_num_rows($rs)) {
                    $result = mysql_fetch_array($rs, MYSQL_ASSOC);
                    //echo "<pre>";print_r($result);die;
                    if(!empty($result) && !empty($result['id'])) $id = $result['id'];
                }
            }

            $sql = "SELECT * from region_counties WHERE county_id='".
                mysql_real_escape_string($id)."'";
            $rs = mysql_query($sql);
            if (mysql_num_rows($rs)) {
                $region = mysql_fetch_array($rs, MYSQL_ASSOC);
                if(!empty($region) && !empty($region['region_id'])) {
                    //check user table for permissions
                    $sql_u = "SELECT region_id from users WHERE id='".
                        mysql_real_escape_string($data['uid'])."'";
                    $rs_u = mysql_query($sql_u);
                    if (mysql_num_rows($rs_u)) {
                        $user = mysql_fetch_array($rs_u, MYSQL_ASSOC);
                        //echo "<pre>";print_r($user);die;
                        if(!empty($user) && !empty($user['region_id'])
                            && $user['region_id'] != $region['region_id'] ) {
                            $inRegion = false;
                        }
                    }
                }
            }
        }

        return $inRegion;
    }

    public function saveEcid($family_id ,$granted , $revoked , $reason) {
        
        $sql = "INSERT INTO `ecids_history`( `family_id`, `granted`, `revoked`, `reason`) VALUES ('$family_id' ,'$granted','$revoked','$reason')";

        $rs = mysql_query($sql) ;
                     return mysql_affected_rows();
        
        
        
}
}
