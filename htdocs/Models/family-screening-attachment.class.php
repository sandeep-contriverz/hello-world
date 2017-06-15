<?php

namespace Hmg\Models;

class FamilyScreeningAttachment
{
    
    private $_table = 'family_screening_attachments';
    public $attachment = array(
        'id'              => null,
        'screening_id'    => null,
        'attachment_name' => null,
        'type'            => null
    );
    
    public $message = null;

    public function __construct()
    {

    }

    public function setAttachment($attachment)
    {
        if (is_array($attachment)) {
            foreach ($attachment as $key => $value) {
                $this->attachment[$key] = $value;
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
                $this->setAttachment(mysql_fetch_array($rs, MYSQL_ASSOC));
            }
        }
    }

    public function getAll()
    {
        return $this->attachment;
    }

    public function save()
    {
        $setFields = '';
        foreach ($this->attachment as $field => $value) {
            if ($value) {
                $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
            }
        }
        if ($this->attachment["id"]) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . '
                    WHERE `id` = "' . mysql_real_escape_string($this->attachment['id']) . '"';
            $rs = mysql_query($sql);
        } else {
            $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields;
            $rs = mysql_query($sql);
            $id = mysql_insert_id();
            if ($id) {
                $this->attachment['id'] = $id;
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
        if ($this->attachment["id"]) {
            $sql = 'DELETE FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($this->attachment['id']) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function deleteFile($filename)
    {
        $removed = false;
        if (file_exists($filename)) {
            $removed = unlink($filename);
        }

        return $removed;
    }
}
