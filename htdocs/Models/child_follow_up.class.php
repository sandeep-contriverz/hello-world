<?php

namespace Hmg\Models;

class ChildFollowUp
{
    private $_table = 'child_follow_up';
    private $_settingTable = 'settings';
    private $_settingJoins = array(
        //'referred_to',
        'service'
    );

    public $followup = array(
        'id'                       => null,
        'child_id'                 => null,
        'hmg_worker'               => null,
        'referred_to_id'           => null,
        'referred_to'              => null,
        'service_id'               => null,
        'service'                  => null,
        'notes'                    => null,
        'follow_up_task_id'        => null,
        'done'                     => null,
        'result'                   => null,
        'follow_up_date'           => null,
        'follow_up_date_formatted' => null,
        'referred_to_type'        => null

    );
    public $message = null;

    public function __construct($followup = null)
    {
        if ($followup) {
            $this->setFollowUp($followup);
        }
    }

    public function setFollowUp($followup)
    {
        // always add new data
        $this->followup= array();
        if (is_array($followup)) {
            foreach ($followup as $key => $value) {
                $this->followup[$key] = $value;
            }
        }
    }

    public function setById($id)
    {
        $join_selects = '';
        $join_clause = '';
        if (is_array($this->_settingJoins)) {
            foreach ($this->_settingJoins as $key) {
                if ($key) {
                    $join_selects .= ', ' . mysql_real_escape_string($key) . '.name ' . mysql_real_escape_string($key);
                    $join_clause .= ' LEFT JOIN `' . $this->_settingTable . '` ' . mysql_real_escape_string($key)
                         . ' ON ' . mysql_real_escape_string($key) . '_id = ' . mysql_real_escape_string($key) . '.id';
                }
            }
        }
        if (is_numeric($id)) {
           $sql = 'SELECT `' . mysql_real_escape_string($this->_table) . '`.*' . ', DATE_FORMAT(referral_date, "%m/%d/%y") referral_date_formatted, DATE_FORMAT(follow_up_date, "%m/%d/%y") follow_up_date_formatted, `' . mysql_real_escape_string($this->_table). '`.`id` '
            . $join_selects
			. ', referred_to.name organization_name' 
			. ', sn.name site_name'
            . ', referred_to.disabled referred_to_disabled'
            . ', service.disabled service_disabled'
            . ' FROM `' . mysql_real_escape_string($this->_table) . '`'
            . ' LEFT JOIN organization_sites os 
				ON referred_to_id = os.id  
				LEFT JOIN organizations o ON o.id = os.organization_id 
				LEFT JOIN `settings` referred_to ON organization_name_id = referred_to.id 
				LEFT JOIN `settings` referred_to_site ON organization_site_id = referred_to.id 
				LEFT JOIN settings sn on sn.id=os.organization_site_id '
			. $join_clause
            . ' WHERE `' . mysql_real_escape_string($this->_table). '`.`id` = "' . mysql_real_escape_string($id) . '"';
            $rs = mysql_query($sql) or die($sql);
            if (mysql_num_rows($rs)) {
                $this->setFollowUp(mysql_fetch_array($rs, MYSQL_ASSOC));
            }
        }
    }

    public function save()
    {
        $setFields = '';
        foreach ($this->followup as $field => $value) {
            //if ($value) {
                $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
            //}
        }
        if (isset($this->followup["id"]) && $this->followup["id"]) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . '
                    WHERE `id` = "' . mysql_real_escape_string($this->followup['id']) . '"';
            $rs = mysql_query($sql) or die($sql . "<br />" . mysql_error());
        } else {
            $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields;
            $rs = mysql_query($sql) or die($sql . "<br />" . mysql_error());
            $id = mysql_insert_id();
            if ($id) {
                $this->followup['id'] = $id;
            }
        }
        if (mysql_affected_rows()) {
            $this->setById($this->followup['id']);
            return true;
        } else {
            $this->setById($this->followup['id']);
            return false;
        }
    }

    public function delete()
    {
        if (isset($this->followup["id"]) && $this->followup["id"]) {
            $sql = 'DELETE FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($this->followup['id']) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                return true;
            } else {
                return false;
            }
        }
    }
}
