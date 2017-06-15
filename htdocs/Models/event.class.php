<?php

namespace Hmg\Models;

class Event
{
    private $_table = 'events';
  //  public $event = array();
    public $contact = array();
    public $message = null;
    public $event = array(
        'hmg_worker'       => null,
        'event_date'       => null,
        'event_name'       => null,
        'event_venue'      => null,
        'outreach_type_id' => null,
        'event_type_id'    => null
    );

    public function __construct($fkeyValue = null)
    {
        if ($fkeyValue) {
            $this->_fkeyValue = $fkeyValue;
        }
    }

    public function addEvent($newEvent)
    {
       
        $setFields = '';
        if(is_array($newEvent)){
            foreach ($newEvent as $field=>$value) {

                if ($field != 'action'){
                    if($field == 'time_of_day'){
                        $value = implode(',', $value);
                    }
                    if($field == 'event_date'){
                        $value = date("Y-m-d", strtotime($value));
                    }
                    $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
                }
            }
            $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . ', date_added = NOW(), last_modified = NOW()';
            $rs = mysql_query($sql) or die($sql);
			$last_insert_id = mysql_insert_id();
            return $last_insert_id;
        }
    }
    public function updateEvent($newEvent)
    {
       //echo "<pre>";print_r($newEvent);die;
        $setFields = '';
        if(is_array($newEvent)){
            foreach ($newEvent as $field=>$value) {
                if ($field != 'action'){
                    if($field == 'event_id'){
                        $event_id = $value;
                        continue;
                    } 
                    if($field == 'time_of_day'){
                        $value = implode(',', $value);
                    }
                    if($field == 'event_date'){
                        $value = date("Y-m-d", strtotime($value));
                    }
                    $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
                }
            }
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . ', last_modified = NOW()
                    WHERE `event_id` = "' . mysql_real_escape_string($event_id) . '"';
            $rs = mysql_query($sql) or die($sql);
        }
    }



    public function getAll()
    {
        $this->event['events'] = null;
        $sql = 'SELECT * from `' . mysql_real_escape_string($this->_table) .'`';
        //$sql = 'SELECT * from `' . mysql_real_escape_string($this->_table) .'` ORDER BY event_id ASC ';
        $rs = mysql_query($sql) or die($sql);
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

    public function showEvent(){
        $sql = 'SELECT * from `' . mysql_real_escape_string($this->_table) .'`';
        $rs = mysql_query($sql) or die($sql);
        //echo "<pre>";print_r($rs);die;
    }

    public function setEvent($event){

    }

    public function setContact($contact)
    {
        // always add new data
        $this->contact= array();
        if (is_array($contact)) {
            foreach ($contact as $key => $value) {
                $this->contact[$key] = $value;
            }
        }
    }

    public function setById($id)
    {
        $event='';
        if (is_numeric($id)) {
            $sql = 'SELECT * FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `event_id` = "' . mysql_real_escape_string($id) . '"';
            $rs = mysql_query($sql);
            if (mysql_num_rows($rs)) {
                while ($events = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                   // echo "<pre>";print_r($events);die;
                    //$this->$event = $events;
                   return $events;
                }
                    //$this->setContact(mysql_fetch_array($rs, MYSQL_ASSOC));
            }

               
            
        }
    }
	
	///
	public function setOrganizationById($organization_id)
    {
        $event='';
        if (is_numeric($id)) {
            $sql = 'SELECT * FROM `events`
                    WHERE `organization_sites_id` = "' . mysql_real_escape_string(organization_id) . '"';
            $rs = mysql_query($sql);
            if (mysql_num_rows($rs)) {
                while ($events = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                   // echo "<pre>";print_r($events);die;
                    //$this->$event = $events;
                   return $events;
                }
                    //$this->setContact(mysql_fetch_array($rs, MYSQL_ASSOC));
            }

               
            
        }
    }
	
	
	
	
	
	
    // save child note
    public function saveNotes($note)
    {
        //echo "<pre>";print_r($note);die;
        if ($note["id"]) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET notes="'.mysql_real_escape_string($note['note']).'"
                    WHERE `id` = "' . mysql_real_escape_string($note['id']) . '" and parent_id="'.mysql_real_escape_string($note['parent_id']).'"';
            $rs = mysql_query($sql);
        }
        if (mysql_affected_rows()) {
            return true;
        }
        return false;
        
    }
    // delete child note
    public function deleteNotes($note)
    {
        //echo "<pre>";print_r($note);die;
        if ($note["id"]) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET notes=""
                    WHERE `id` = "' . mysql_real_escape_string($note['id']) . '" and parent_id="'.mysql_real_escape_string($note['parent_id']).'"';
            $rs = mysql_query($sql);
        }
        if (mysql_affected_rows()) {
            return true;
        }
        return false;
        
    }

    /* public function displayContactSelect($name, $selected, $label = '', $tabIndex = '', $class = '', $showAll = false)
    {
        $sql = 'SELECT first, last FROM `contacts` WHERE hmg_worker != ""' . ($showAll ? '' : ' AND status="1"') . ' ORDER BY status desc, hmg_worker asc';
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
            $options .= '<option value="' . $row['hmg_worker'] . '"' . ($selected == $row['hmg_worker'] ? ' selected="selected"' : '') . '>' . $row['hmg_worker'] . (!$row['status'] ? ' (Inactive)' : '') . '</option>';
        }
        if ($selected && !$activeWorker) {
            $options = '<option value="' . $selected . '" selected="selected">' . $selected . ' (Inactive)</option>' . $options;
        }
        $select = '<select id="' . $name . '" class="' . ($class ? $class : 'hmg_worker') . '" name="' . $name . '" tabindex="' . $tabIndex . '"' . ($class === 'hide-hmg-worker' ? ' disabled="disabled"' : '') . '>' . $options . '</select>';
        return $select;
    }*/
}
