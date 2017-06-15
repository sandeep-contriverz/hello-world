<?php

namespace Hmg\Models;

class Contact
{
    private $_table = 'contacts';
    public $contact = array();
    public $message = null;

    public function __construct()
    {
    }

    public function setContact($contact)
    {
        // always add new data
        $this->contact= array();
        if (is_array($contact)) {
            foreach ($contact as $key => $value) 
			{
                $this->contact[$key] = $value;
            }
        }
    }

    public function setById($contact_id)
    {
        if (is_numeric($contact_id)) {
            $sql = 'SELECT * FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($contact_id) . '"';
            $rs = mysql_query($sql);
            if (mysql_num_rows($rs)) {
                $this->setContact(mysql_fetch_array($rs, MYSQL_ASSOC));
            }
        }
    }
		
    // save contact note
    public function saveNotes($note)
    {
        //echo "<pre>";print_r($note);die;
        if ($note["id"]) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET notes="'.mysql_real_escape_string($note['note']).'"
                    WHERE `id` = "' . mysql_real_escape_string($note['id']) . '" and organization_sites_id="'.mysql_real_escape_string($note['parent_id']).'"';
            $rs = mysql_query($sql);
        }
        if (mysql_affected_rows()) {
            return true;
        }
        return false;
        
    }
    // delete contact note
    public function deleteNotes($note)
    {
        //echo "<pre>";print_r($note);die;
        if ($note["id"]) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET notes=""
                    WHERE `id` = "' . mysql_real_escape_string($note['id']) . '" and organization_sites_id="'.mysql_real_escape_string($note['parent_id']).'"';
            $rs = mysql_query($sql);
        }
        if (mysql_affected_rows()) {
            return true;
        }
        return false;
        
    }
}
