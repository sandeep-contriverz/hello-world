<?php

namespace Hmg\Models;

class OrganizationSite
{
    private $_table = 'organization_sites';
    public $site = array(
        'id'                   => null,
        'organization_site_id' => null,
        'organization_id'      => null,
    );
    public $message = null;

    public function __construct()
    {

    }

    public function setSite($site)
    {
        if (is_array($site)) {
            foreach ($site as $key => $value) {
                $this->site[$key] = $value;
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
                $this->setSite(mysql_fetch_array($rs, MYSQL_ASSOC));
            }
        }
    }
    public function setByOrgId($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT * FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `organization_id` = "' . mysql_real_escape_string($id) . '"';
            $rs = mysql_query($sql);
            if (mysql_num_rows($rs)) {
                return $rs;
            }
        }
        return false;
    }

    public function getAll()
    {
        return $this->site;
    }

    public function save()
    {
        $setFields = '';
        foreach ($this->site as $field => $value) {
            if ($value) {
                $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
            }
        }
        if ($this->site["id"]) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . ', modified = null
                    WHERE `id` = "' . mysql_real_escape_string($this->site['id']) . '"';
            $rs = mysql_query($sql);
        } else {
            $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . ', modified = null';
            $rs = mysql_query($sql);
            $id = mysql_insert_id();
            if ($id) {
                $this->site['id'] = $id;
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
        if ($this->site["id"]) {
            $sql = 'DELETE FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($this->site['id']) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                return true;
            } else {
                return false;
            }
        }
    }
}
