<?php

namespace Hmg\Models;

use Hmg\Models\RegionCounties;
use Hmg\Models\ContactFollowUp;
use Hmg\Models\OrganizationStartEnd;

class Organizatione
{
    private $_table        = 'organizations';
    private $_contactTable = 'contacts';
    private $_notesTable   = 'organization_notes';
    public  $organization_sites_id = 0;

    public $organization = array(
        'id'                                => null,
        'organization_name_id'              => null,
        'site'                              => null,
        'type'                              => null,
        'address'                           => null,
        'city'                              => null,
        'state'                             => null,
        'zip'                               => null,
        'county'                            => null,
        'primary_phone'                     => null,
        'fax'                               => null,
        'mou'                               => 'No',
        'partnership_level_id'              => null,
        'date_last_signed'                  => null,
        'partnership_notes'                 => null,
        'website'                           => null,
        'region_id'                         => null,        
        //'status'                          => null,
        'service_area'                      => null,
        'mode_of_contact_id'                => null,
        'success_story'                     => 'No',
        'success_story_notes'               => null,
        'service_terms'                     => null,
        'database_id'                       => null,
        'service_terms'                     => null,
        'contacts'                          => null,
        'note'                              => null,
        'hmg_worker'                        => null,
        'organization_sites_id'             => null,
        'setting_site_id'                   => null,

    );
    public $message = null;

    public function __construct()
    {
    }

    public function setOrganization($organization)
    {
        // always add new data
        $this->organization = array();
        if (is_array($organization)) {
            foreach ($organization as $key => $value) {
                $this->organization[$key] = !is_array($value) ? trim($value) : $value;
            }
        }
    }

    public function setById($id)
    {
        if (is_numeric($id)) {
            $sql = '
                SELECT
                    `organizations`.*, `st`.`name`, `os`.`organization_site_id` as site,
                    os.id as organization_sites_id, os.organization_site_id setting_site_id, 
                    os.status, sites.name site,
                    GROUP_CONCAT(service.service_id) service_terms, 
                    DATE_FORMAT(date_last_signed, "%m/%d/%Y") date_last_signed_formatted
                FROM `' . mysql_real_escape_string($this->_table) . '`
                JOIN organization_sites os ON os.organization_id=organizations.id
                LEFT JOIN service ON service.referred_to_id=os.id
                LEFT JOIN settings st ON st.id=organizations.organization_name_id 
                LEFT JOIN settings sites ON sites.id=os.organization_site_id 
                WHERE `os`.`id` = "' . mysql_real_escape_string($id) . '"
                GROUP BY os.id';
            
            $rs = mysql_query($sql);
            if (mysql_num_rows($rs)) {
                $row = mysql_fetch_array($rs, MYSQL_ASSOC);
                //fetch start and end dates
                $sql_fetch = 'SELECT * FROM `organization_startend` 
                    WHERE `parent_id`="'.$row['organization_sites_id'].'"';
                $rs_sql  = mysql_query($sql_fetch);
                $row_sql = mysql_fetch_array($rs_sql, MYSQL_ASSOC);
                if(!empty($row_sql)) {
                    $row['start_date'] = $row_sql['start_date'];
                    $row['end_date']   = $row_sql['end_date'];
                }
                $this->setOrganization($row);
                $this->setContacts($id);
                $this->setEvents($id);
            }
        }
    }

    public function setContacts($id)
    {
        $this->organization['contacts'] = null;
        if (is_numeric($id)) {
            $sql = '
                SELECT 
                    *                    
                FROM `contacts`
                WHERE `organization_sites_id` = "' . mysql_real_escape_string($id) . '" ORDER BY id ASC';
            $rs = mysql_query($sql);
            while ($contact = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                /*if ($child['birth_date'] == '0000-00-00') {
                    $child['birth_date']           = null;
                    $child['birth_date_formatted'] = null;
                }
                if ($child['birth_due_date'] == '0000-00-00') {
                    $child['birth_due_date']           = null;
                    $child['birth_due_date_formatted'] = null;
                }*/
                $this->organization['contacts'][$contact['id']] = $contact;
            }
        }
    }
	
	public function set_organizationby_id($organization_id)
	{
		
		
        if (is_numeric($id)) {
            $sql = '
                SELECT 
                    *                    
                FROM `organizations`
                WHERE `id` = "' . mysql_real_escape_string($organization_id) . '" ORDER BY id ASC';
            $rs = mysql_query($sql) or die($sql);
            $organization = mysql_fetch_array($rs, MYSQL_ASSOC);
              
             $this->organization['organization'][$organization['address']] = $organization;
			 
            }
  	}
	
	public function setEvents($id)
    {
        $this->organization['events'] = null;
        if (is_numeric($id)) {
            $sql = '
                SELECT 
                    events.*, CONCAT(users.last_name, " ", users.first_name) as hmg_worker, users.hmg_worker as hmgworker_user
                FROM `events`
                LEFT JOIN users ON users.id=events.hmg_worker
                WHERE `organization_sites_id` = "' . mysql_real_escape_string($id) . '" 
                ORDER BY event_date DESC';
            $rs = mysql_query($sql) or die($sql);
            while ($event = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                //echo "<pre>";print_r($event);
                $this->organization['events'][$event['event_id']] = $event;
            }
            
        }
    }

    public function getAll()
    {
        //echo "<pre>";print_r($this->organization);die;
        return $this->organization;
    }

    public function save()
    {
        $setFields              = '';
        $is_note                = false;
        $is_import              = false;
        $note_data              = '';
        $status                 = 0;
        $service_terms          = '';
        $start_date = $end_date = '';
        
        
        foreach ($this->organization as $field => $value) {
            if($field == 'service_area') {
                $value = implode(', ', $value);
            }
            if($field == 'service_terms') {
                $value = implode(', ', $value);
            }
            if($field == 'note') {
                $is_note = true;
                $note_data = $value;
            }
            if($field == 'is_import') {
                $is_import = true;
            }
            if($field == 'status') {
                $status = $value;
                continue;
            }
            if($field == 'service_terms') {
                $service_terms = $value;
                continue;
            }
            if($field == 'start_date') {
                $start_date = $value;
                $value = date('Y-m-d', strtotime($value));
            }
            if($field == 'end_date') {
                $end_date = $value;
                $value = date('Y-m-d', strtotime($value));
            }
            if($field == 'organization_sites_id' || $field == 'setting_site_id')
                continue;

            if($field == 'date_last_signed') {
                //if( strtotime($value) )
                if( !empty($value) )
                    $value = date('Y-m-d', strtotime($value));
                else 
                    $value = '0000-00-00';
            }
            if($field == 'site') {
                $this->organization_sites_id = $value;
            }
                
            if ($field != 'children' && $field != 'site' && $field != 'is_import') { //131216
                $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
            }//hmg_worker = "' . mysql_real_escape_string($_SESSION['user']['hmg_worker'])
        }
        /*if(!$is_note) {
            $setFields .= ($setFields ? ', ' : '') . '`note` = "' . mysql_real_escape_string($_REQUEST['note']) . '"';
        }*/
        
        if(isset($_POST['note']) && !empty($_POST['note'])) {
            $is_note   = true;
            $note_data = $_POST['note'];
        }
        if (isset($this->organization["id"]) && !empty($this->organization["id"])) { //131216
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . ', modified = NOW()
                    WHERE `id` = "' . mysql_real_escape_string($this->organization['id']) . '"';
            $rs = mysql_query($sql) or die($sql);
        } else {
            if(!isset($this->organization['hmg_worker'])) { //131216
                $setFields .= ($setFields ? ', ' : '') . '`hmg_worker` = "' . mysql_real_escape_string($_SESSION['user']['id']) . '"'; //set hmg worker
            }
            $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . ', modified = NOW()';
            $rs = mysql_query($sql) or die($sql);
            $id = mysql_insert_id();
            if ($id) {
                $this->organization['id'] = $id;
            }
        }
        
        $affectedRows = mysql_affected_rows();
        //echo $sql;die;
        if (isset($this->organization["id"]) && !empty($this->organization["id"])) {
            //$this->saveSiteInfo($status); //insert site name record
            $savedContacts = $this->saveContacts();
            $this->saveServiceTerms($this->organization_sites_id, $service_terms);
            // Add start end
            if (!empty($this->organization_sites_id) 
                    && (!empty($start_date) || !empty($end_date))
            ) {
                $newStartEnd = array(
                    'parent_id'  => $this->organization_sites_id,
                    'start_date' => !empty($start_date) 
                            ? date('Y-m-d', strtotime($start_date)) : '',
                    'end_date'   => !empty($end_date) 
                            ? date('Y-m-d', strtotime($end_date)) : '',
                );
                $startEnd = new OrganizationStartEnd();
                $startEnd->setStartEnd($newStartEnd);
                $startEnd->save();
            }
            //save notes
            if(!empty($note_data)) {
                $hmg_worker = $this->organization["hmg_worker"];
                if($is_import) {
                    $hmg_worker = '';
                }
                $sql_note = 'INSERT INTO `' . mysql_real_escape_string($this->_notesTable) . '` SET organization_sites_id='.$this->organization_sites_id.', note="'.mysql_real_escape_string($note_data).'", hmg_worker="'.$hmg_worker.'", modified = null';
                mysql_query($sql_note);
            }
        }
        if ($affectedRows) {
            
            $this->setById($this->organization_sites_id);
            if(isset($this->organization['organization_sites_id'])
                && !empty($this->organization['organization_sites_id'])) {
                return $this->organization['organization_sites_id'];
            } else {
                return $this->organization_sites_id;
            }
            //return true;
        } else {
            $this->setById($this->organization_sites_id);
            if(isset($this->organization['organization_sites_id'])
                && !empty($this->organization['organization_sites_id'])) {
                return $this->organization['organization_sites_id'];
            } else {
                return $this->organization_sites_id;
            }
        }
    }

    public function saveServiceTerms($id = 0, $service_terms = '') {
        if(empty($service_terms) || empty($id))
            return false;

        $sql = 'DELETE FROM service
                WHERE referred_to_id = "'.mysql_real_escape_string($id).'"';
        mysql_query($sql);
        $terms = explode(',', $service_terms);
        foreach($terms as $term) {
            $sql = 'INSERT INTO
                    `service`
                SET
                    referred_to_id = "'.mysql_real_escape_string($id).'",
                    service_id = "'.$term.'",
                    disabled = "0"';       
            mysql_query($sql);
        }
    }

    public function saveSiteInfo($status = 0)
    {
        if (isset($this->organization["site"]) 
            /*&& !empty($this->organization["site"])*/
            && isset($this->organization["id"])
            && !empty($this->organization["id"])
        ) {
            $site_id = 0;
            //echo "<pre>";print_r($this->organization);die;
            if(empty($this->organization["site"])) {
                $site_id = 0;
            } elseif(!is_numeric($this->organization["site"])) {
                $check_org = 'Select s.id, s.name, os.id osid, 
                    os.organization_site_id site_id, o.id oid from settings s 
                    JOIN organization_sites os ON os.organization_site_id=s.id 
                        JOIN organizations o ON o.id=os.organization_id
                    where name="'.$this->organization["site"].'" AND type="organization_site" 
                    AND o.id="'.$this->organization['id'].'" Limit 1';
                //echo $check_org;
                $rs_org   = mysql_query($check_org);
                $row_site = mysql_fetch_array($rs_org, MYSQL_ASSOC);
                if(empty($row_site) && empty($this->organization['setting_site_id'])) { //insert new site name entry to settings table
                    $sql_up  = 'Insert into settings set name="'.$this->organization["site"].'", type="organization_site", disabled="0"';
                    $update  = mysql_query($sql_up);
                    $site_id = mysql_insert_id();
                } else {
                    $site_id_update = !empty($this->organization['setting_site_id'])
                            ? $this->organization['setting_site_id'] : $row_site['site_id'];
                    //echo '$site_id_update  : '.$site_id_update ;
                    $sql_up  = 'Update settings set name="'.$this->organization["site"].'", type="organization_site" Where id="'.$site_id_update.'"';

                    $update  = mysql_query($sql_up);
                    $site_id = $site_id_update;
                }
            } else {
                $site_id = $this->organization["site"];
            }
            
            //echo "<pre>";print_r($this->organization);die;
            //check if record already exists 141216
            $check_site = 'Select * from `organization_sites` Where organization_id='.$this->organization["id"].' AND organization_site_id="'.mysql_real_escape_string($site_id).'"';
            $rs_site = mysql_query($check_site);
            $record = mysql_fetch_array($rs_site, MYSQL_ASSOC);
            //echo "<pre>szadsadsad ";print_r($record);
            if(!empty($record) || !empty($this->organization['organization_sites_id'])) {
                $uid = !empty($this->organization['organization_sites_id'])
                        ? $this->organization['organization_sites_id'] : $record['id'];
                //update record
                $sql_site = 'Update `organization_sites` SET organization_id='.$this->organization["id"].', organization_site_id="'.mysql_real_escape_string($site_id).'", status="'.$status.'" Where id="'.$uid.'"';
                //echo $sql_site;die;
                mysql_query($sql_site);
                $this->organization_sites_id = $record['id'];
            } else { //insert
                $sql_site = 'INSERT INTO `organization_sites` SET organization_id='.$this->organization["id"].', organization_site_id="'.mysql_real_escape_string($site_id).'", status="'.$status.'"';
                mysql_query($sql_site);
                $this->organization_sites_id = mysql_insert_id();
            }
            //echo $sql_site;die;
        }
        return true;
    }

    public function saveContacts()
    {
        if (is_array($this->organization["children"]) 
            && isset($this->organization["children"])
            && !empty($this->organization["children"])
        ) {
            $affectedContacts = 0;
            $contact_notes = $hmg_worker = '';
            $contact_follow_ups = array();
            $is_import = false;
            foreach ($this->organization["children"] as $contact) {
                if (!empty($contact['first'])) {
                    /** Created for import purpose starts here **/
                    $contact_follow_ups = $contact['contact_follow_ups'];
                    $contact_notes = $contact['notes'];
                    $hmg_worker = $contact['hmg_worker'];
                    if(isset($contact['is_import'])) {
                        $is_import = true;
                    }
                    /** Created for import purpose ends here **/
                    unset(
                        $contact["birth_date_formatted"],
                        $contact["birth_due_date_formatted"],
                        $contact['contact_follow_ups'],
                        $contact['hmg_worker'],
                        $contact['is_import']
                    );

                    $setFields = '`organization_id` = "' . mysql_real_escape_string($this->organization['id']) . '"';
                    $setFields = '`organization_sites_id` = "' . mysql_real_escape_string($this->organization_sites_id) . '"';
                    foreach ($contact as $field => $value) {
                        if (isset($value)) {
                            $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
                        }
                    }
                    if (isset($contact["id"]) && is_numeric($contact["id"]) && $contact["id"]) {
                        $sql = 'UPDATE `' . mysql_real_escape_string($this->_contactTable) . '` SET ' . $setFields . '
                                WHERE `id` = "' . mysql_real_escape_string($contact['id']) . '"';
                        $rs = mysql_query($sql) or die($sql);
                    } else {
                        $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_contactTable) . '` SET ' . $setFields;
                        $rs = mysql_query($sql) or die($sql);
                        $contact["id"] = mysql_insert_id();
                    }
                    if (mysql_affected_rows()) {
                        $affectedContacts++;
                    }
                    //save notes
                    if(isset($contact_notes) && !empty($contact_notes)) {
                        if($is_import) {
                            $hmg_worker = !empty($contact['hmg_worker']) 
                                ? $contact['hmg_worker'] : '';
                        } else {
                            $hmg_worker = !empty($contact['hmg_worker']) 
                                ? $contact['hmg_worker'] : $_SESSION['user']['id'];
                        }
                        $sql_note = 'INSERT INTO `contact_notes` SET contact_id='.$contact["id"].', note="'.mysql_real_escape_string($contact_notes).'", hmg_worker="'.mysql_real_escape_string($hmg_worker).'", modified = null';
                        mysql_query($sql_note);
                    }
                    
                }
                /** Created for import purpose starts here **/
                //if contact follow up records exists, insert them
                if(!empty($contact_follow_ups) && !empty($contact["id"])) {
                    if(is_array($contact_follow_ups)) {
                        foreach($contact_follow_ups as $cfollowup_data) {
                            $cfollowup_data["contact_id"] = $contact["id"];
                            $cfollowUpObj = new ContactFollowUp($cfollowup_data);
                            $cfollowUpObj->save();
                        }
                    }
                }
                /** Created for import purpose ends here **/
            }
            if ($affectedContacts) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function delete()
    {
        if ($this->organization["id"]) {
            $sql = 'DELETE FROM `' . mysql_real_escape_string($this->_notesTable) . '`
                    WHERE `organization_sites_id` = "' . mysql_real_escape_string($this->organization['id']) . '"';
            $rs = mysql_query($sql);
            $sql = 'DELETE FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($this->organization['id']) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function deleteContacts()
    {
        if (is_array($this->organization["children"])) {
            foreach ($this->organization["children"] as $contact) {
                $deleted = $this->deleteContact($contact["id"]);
            }
            return $deleted;
        }
    }

    public function deleteContact($contactId)
    {
        if ($this->organization["id"]) {
            /*$sql = 'DELETE c, s, sa, n, fu, r, pr FROM `children` c
                        LEFT JOIN `child_developmental_screenings` s ON c.id = s.child_id
                        LEFT JOIN `child_screening_attachments` sa ON s.id = sa.screening_id
                        LEFT JOIN `child_notes` n ON c.id = n.child_id
                        LEFT JOIN `child_follow_up` fu ON c.id = fu.child_id
                        LEFT JOIN `child_referrals` r ON c.id = r.child_id
                        LEFT JOIN `child_prior_resources` pr ON c.id = pr.child_id
                    WHERE c.id = "' . mysql_real_escape_string($childId) . '"';*/
            $sql = 'DELETE c FROM `contacts` c
                WHERE c.id = "' . mysql_real_escape_string($contactId) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                $this->setContacts($this->organization['id']);
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
        $sql = 'SHOW COLUMNS FROM `' . $this->_contactTable . '` LIKE  "' . mysql_real_escape_string($field) . '"';
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

    public function displayHmgWorkerSelect($name, $selected, $label = '', $tabIndex = '', $class = '', $showAll = false, $id = false)
    {
        $sql = 'SELECT id, hmg_worker, status FROM `users` WHERE hmg_worker != ""' . ($showAll ? '' : ' AND status="1"') . ' ORDER BY status desc, hmg_worker asc';
        $rs = mysql_query($sql);
        if ($label) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '<option value="' . $selected . '">' . $selected . '</option>';
        }
        $activeWorker = false;
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            
            if ($selected === $row['hmg_worker']) {
                $activeWorker = true;
            }
            if(isset($id) && $id) {
                if ($selected === $row['id']) {
                    $activeWorker = true;
                }
                $options .= '<option value="' . $row['id'] . '"' . ($selected == $row['id'] ? ' selected="selected"' : '') . '>' . $row['hmg_worker'] . (!$row['status'] ? ' (Inactive)' : '') . '</option>';
            } else {
                $options .= '<option value="' . $row['hmg_worker'] . '"' . ($selected == $row['hmg_worker'] ? ' selected="selected"' : '') . '>' . $row['hmg_worker'] . (!$row['status'] ? ' (Inactive)' : '') . '</option>';
            }
        }
        if ($selected && !$activeWorker) {
            if(isset($id) && $id) {
                $userObj = new User();
                $userObj->setById((int)$selected);
                $hmgW = isset($userObj->user['hmg_worker']) ? $userObj->user['hmg_worker'] : '';
                $options = '<option value="' . $selected . '" selected="selected">' . $hmgW . ' (Inactive)</option>' . $options;
            } else {
                $options = '<option value="' . $selected . '" selected="selected">' . $selected . ' (Inactive)</option>' . $options;
            }
        }
        $select = '<select id="' . $name . '" class="' . ($class ? $class : 'hmg_worker') . '" name="' . $name . '" tabindex="' . $tabIndex . '"' . ($class === 'hide-hmg-worker' ? ' disabled="disabled"' : '') . '>' . $options . '</select>';
        return $select;
    }
	
	// display organzation list
	public function displayOrganization($name,$selected,$label = '', $tabIndex = '', $class = '', $showAll = false)
	{
		
		$sql = "SELECT * FROM `settings` WHERE type = 'referred_to'";
        $rs = mysql_query($sql);
		
		if ($label) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '<option value="' . $selected . '">' . $selected . '</option>';
        }
        $activeOrganization = false;
        while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
            if ($selected === $row['id']) {
                $activeOrganization = true;
            }
			
		
		
            $options .= '<option value="' . $row['id'] . '"' . ($selected == $row['id'] ? ' selected="selected"' : '') . '>' . $row['name']  . '</option>';
        }
        if ($selected && !$activeWorker) {
            $options = '<option value="' . $selected . '" selected="selected">' . $selected . '</option>' . $options;
        }
        $select = '<select id="' . $name . '"  name="' . $name . '" tabindex="' . $tabIndex . '" >' . $options . '</select>';
        
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

    public function isRegionOrganization()
    {
        $isRegionOrganization = true;

        if (!empty($_SESSION['user']['region_id'])) {
            $regionCounties = new RegionCounties($_SESSION['user']['region_id']);
            $countiesList = $regionCounties->getList();
            $countyNames = [];
            foreach ($countiesList as $result) {
                $countyNames[] = $result['county'];
            }
            if (!in_array($this->organization['county'], $countyNames)) {
                $isRegionOrganization = false;
            }
        }

        return $isRegionOrganization;
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
            if (!in_array($this->organization['county'], $countyNames)) {
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
	
	public function getOrganizationByOrgID($orgID)
	{
		
		/*$sql = '
                SELECT
                    GROUP_CONCAT(service.service_id) service_terms
                    
                FROM `' . mysql_real_escape_string($this->_table) . '`
                JOIN organization_sites os ON os.organization_id=organizations.id
                LEFT JOIN service ON service.referred_to_id=os.id

                WHERE organizations.organization_name_id = "' . mysql_real_escape_string($orgID) . '"';*/
				
				$sql='SELECT
					GROUP_CONCAT(s.id) service_terms
						
					FROM organizations o
					JOIN organization_sites os ON os.organization_id=o.id
					LEFT JOIN service rs ON rs.referred_to_id=os.id
					LEFT JOIN settings s ON rs.service_id = s.id
					WHERE o.organization_name_id = "' . mysql_real_escape_string($orgID) . '" 
					AND (os.organization_site_id=0 OR os.organization_site_id="" OR os.organization_site_id="null") ORDER BY service ASC';
				$rs = mysql_query($sql);
				$re=mysql_fetch_array($rs, MYSQL_ASSOC);
				return $re;
	
	}
}
