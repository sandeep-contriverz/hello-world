<?php

namespace Hmg\Models;

class ProviderFamily
{
    private $_table = 'family_provider';
    private $_provider_id = null;
    public $message = null;

    public function __construct($provider_id)
    {
        if (is_numeric($provider_id)) {
            $this->_provider_id = $provider_id;
        }
    }

    public function getList()
    {
        $sql = 'SELECT family_id FROM `' . $this->_table . '`'
                . ' JOIN families f ON f.id = family_id '
                . ' WHERE contact_id = "' . $this->_provider_id . '"'
                . ' ORDER BY f.first_name_1, f.last_name_1';
        $rs = mysql_query($sql) or die(mysql_error() . ' ' . $sql);
        if ($rs) {
            $rows = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $rows[] = $row['family_id'];
            }
            return $rows;
        }
    }

    public function getClinicList()
    {
        $sql = 'SELECT employer FROM`' . $this->_table . '`'
            . ' JOIN providers p ON p.id = provider_id'
            . ' WHERE provider_id = "' . $this->_provider_id . '"';
        $rs = mysql_query($sql) or die(mysql_error() . ' ' . $sql);
        $row = mysql_fetch_array($rs);
        $employer = $row['employer'];

        $sql = 'SELECT family_id FROM `' . $this->_table . '`'
                . ' JOIN families f ON f.id = family_id '
                . ' JOIN contacts p on p.id = contact_id'
                . ' WHERE p.id = "' . $this->_provider_id . '"';
        $rs = mysql_query($sql) or die(mysql_error() . ' ' . $sql);
        if ($rs) {
            $rows = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $rows[] = $row['family_id'];
            }
            return $rows;
        }
    }
}
