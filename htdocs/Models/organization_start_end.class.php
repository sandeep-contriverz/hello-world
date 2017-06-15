<?php

namespace Hmg\Models;

class OrganizationStartEnd
{
    private $_table = 'organization_startend';
    public $startEnd = array(
        'id'            => null,
        'parent_id'        => null,
        'start_date'    => null,
        'end_date'        => null
    );
    public $message = null;

    public function __construct()
    {

    }

    public function setStartEnd($startEnd)
    {
        if (is_array($startEnd)) {
            foreach ($startEnd as $key => $value) {
                $this->startEnd[$key] = $value;
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
                $this->setStartEnd(mysql_fetch_array($rs, MYSQL_ASSOC));
            }
        }
    }

    public function getAll()
    {
        return $this->startEnd;
    }

    public function save()
    {
        $setFields = '';
        foreach ($this->startEnd as $field => $value) {
            //if ($value) {
                $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
            //}
        }
        if ($this->startEnd["id"]) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields
                    . ' WHERE `id` = "' . mysql_real_escape_string($this->startEnd['id']) . '"';
            $rs = mysql_query($sql);
        } else {
            $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields;
            $rs = mysql_query($sql);
            $id = mysql_insert_id();
            if ($id) {
                $this->startEnd['id'] = $id;
            }
        }
        
        if (mysql_affected_rows()) {
            return true;
        } else {
            return false;
        }
    }

    public function delete()
    {
        if ($this->startEnd["id"]) {
            $sql = 'DELETE FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($this->startEnd['id']) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                return true;
            } else {
                return false;
            }
        }
    }
}
