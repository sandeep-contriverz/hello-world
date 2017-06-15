<?php

namespace Hmg\Models;

class OrganizationNote
{
    private $_table = 'organization_notes';
    public $note = array(
        'id'              => null,
        'organization_sites_id' => null,
        'hmg_worker'      => null,
        'note'            => null,
        'modified'        => null
    );
    public $message = null;

    public function __construct()
    {

    }

    public function setNote($note)
    {
        if (is_array($note)) {
            foreach ($note as $key => $value) {
                $this->note[$key] = $value;
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
                $this->setNote(mysql_fetch_array($rs, MYSQL_ASSOC));
            }
        }
    }

    public function getAll()
    {
        return $this->note;
    }

    public function save()
    {
        $setFields = '';
        foreach ($this->note as $field => $value) {
            if ($value) {
                $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
            }
        }
        if ($this->note["id"]) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . ', modified = null
                    WHERE `id` = "' . mysql_real_escape_string($this->note['id']) . '"';
            $rs = mysql_query($sql);
        } else {
            $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . ', modified = null';
            $rs = mysql_query($sql);
            $id = mysql_insert_id();
            if ($id) {
                $this->note['id'] = $id;
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
        if ($this->note["id"]) {
            $sql = 'DELETE FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($this->note['id']) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                return true;
            } else {
                return false;
            }
        }
    }
}
