<?php

namespace Hmg\Models;

class EcidsGrantedRevoked
{
    private $_table = 'ecids_granted_revoked';
    public $ecidsGrantedRevoked = array(
        'id'            => null,
        'family_id'        => null,
        'granted'    => null,
        'revoked'        => null
    );
    public $message = null;

    public function __construct()
    {

    }

    public function setGrantedRevoked($ecidsGrantedRevoked)
    {
        if (is_array($ecidsGrantedRevoked)) {
            foreach ($ecidsGrantedRevoked as $key => $value) {
                $this->ecidsGrantedRevoked[$key] = $value;
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
                $this->setGrantedRevoked(mysql_fetch_array($rs, MYSQL_ASSOC));
            }
        }
    }

    public function getAll()
    {
        return $this->ecidsGrantedRevoked;
    }

    public function save()
    {
        $setFields = '';
        foreach ($this->ecidsGrantedRevoked as $field => $value) {
            if ($value) {
                $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
            }
        }
        if ($this->ecidsGrantedRevoked["id"]) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields
                    . ' WHERE `id` = "' . mysql_real_escape_string($this->startEnd['id']) . '"';
            $rs = mysql_query($sql);
        } else {
            $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields;
            $rs = mysql_query($sql);
            $id = mysql_insert_id();
            if ($id) {
                $this->ecidsGrantedRevoked['id'] = $id;
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
        if ($this->ecidsGrantedRevoked["id"]) {
            $sql = 'DELETE FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($this->ecidsGrantedRevoked['id']) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                return true;
            } else {
                return false;
            }
        }
    }
}
