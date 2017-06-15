<?php

namespace Hmg\Models;

class Child
{
    private $_table = 'children';
    public $child = array();
    public $message = null;

    public function __construct()
    {
    }

    public function setChild($child)
    {
        // always add new data
        $this->child= array();
        if (is_array($child)) {
            foreach ($child as $key => $value) {
                $this->child[$key] = $value;
            }
        }
    }

    public function setById($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT * FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($id) . '"';
            $rs = mysql_query($sql);
            if (mysql_num_rows($rs)) {
                $this->setChild(mysql_fetch_array($rs, MYSQL_ASSOC));
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
}
