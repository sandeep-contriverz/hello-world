<?php

namespace Hmg\Models;

class ChildReferral
{
    private $_table = 'child_referrals';
    private $_settingTable = 'settings';
    private $_settingJoins = array(
        //'referred_to',
        'issue',
        'service'
    );

    public $referral = array(
        'id'                      => null,
        'child_id'                => null,
        'issue_id'                => null,
        'issue'                   => null,
        'referred_to_id'          => null,
        'referred_to'             => null,
        'service_id'              => null,
        'service'                 => null,
        'based_screening'         => null,
        'outcomes'                => null,
        'notes'                   => null,
        'gap'                     => null,
        'barrier'                 => null,
        'hmg_worker'              => null,
        'referral_date'           => null,
        'referral_date_formatted' => null,
        'referred_to_type'        => null

    );
    public $message = null;

    public function __construct($referral = null)
    {
        if ($referral) {
            $this->setReferral($referral);
        }
    }

    public function setReferral($referral)
    {
        // always add new data
        $this->referral= array();
        if (is_array($referral)) {
            foreach ($referral as $key => $value) {
                $this->referral[$key] = $value;
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
            $sql = 'SELECT `' . mysql_real_escape_string($this->_table) . '`.*, DATE_FORMAT(referral_date, "%m/%d/%y") referral_date_formatted, `' . mysql_real_escape_string($this->_table). '`.`id` '
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
                $this->setReferral(mysql_fetch_array($rs, MYSQL_ASSOC));
            }
        }
    }

    public function save()
    {
        $setFields = '';
        foreach ($this->referral as $field => $value) {
            //if ($value) {
                $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
            //}
        }
        if (isset($this->referral["id"]) && $this->referral["id"]) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . (!isset($this->referral['referral_date']) ? ', referral_date = CURDATE()' : '') .
                    ' WHERE `id` = "' . mysql_real_escape_string($this->referral['id']) . '"';
            $rs = mysql_query($sql) or die($sql);
        } else {
            $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . ', referral_date = CURDATE()';
            $rs = mysql_query($sql) or die($sql);
            $id = mysql_insert_id();
            if ($id) {
                $this->referral['id'] = $id;
            }
        }
        if (mysql_affected_rows()) {
            $this->setById($this->referral['id']);
            return true;
        } else {
            $this->setById($this->referral['id']);
            return false;
        }
    }

    public function delete()
    {
        if (isset($this->referral["id"]) && $this->referral["id"]) {
            $sql = 'DELETE FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($this->referral['id']) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                return true;
            } else {
                return false;
            }
        }
    }
}
