<?php

namespace Hmg\Models;

class Volunteering
{
    private $_table = 'volunteering';
    public $volunteering = array(
        'id'            => null,
        'volunteer_id'  => null,
        'date'          => null,
        'hours'         => null,
        'type'          => null,
        'formattedDate' => null
    );
    public $hours = array();
    public $message = null;

    public function __construct()
    {
    }

    public function setVolunteering($volunteering)
    {
        // always add new data
        $this->volunteering= array();
        if (is_array($volunteering)) {
            foreach ($volunteering as $key => $value) {
                $this->volunteering[$key] = $value;
            }
        }
    }

    public function setById($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT * FROM `' . $this->_table . '` WHERE id = "' . mysql_real_escape_string($id) . '"';
            $rs = mysql_query($sql);
            if (mysql_num_rows($rs)) {
                $this->setVolunteering(mysql_fetch_array($rs, MYSQL_ASSOC));
            }
        }
    }

    public function getAllByVolunteerId($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT *, DATE_FORMAT(`date`, "%m/%d/%Y") formattedDate FROM `' . $this->_table . '` WHERE volunteer_id = "' . mysql_real_escape_string($id) . '"
                ORDER BY `date` DESC';
            $rs = mysql_query($sql);
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                array_push($this->hours, $row);
            }
        }
    }

    public function getAll()
    {
        return $this->volunteering;
    }

    public function save()
    {
        $setFields = '';
        foreach ($this->volunteering as $field => $value) {
            //if ($value) {
                $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
            //}
        }
        if (isset($this->volunteering["id"]) && !empty($this->volunteering["id"])) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . '
                    WHERE `id` = "' . mysql_real_escape_string($this->volunteering['id']) . '"';
            $rs = mysql_query($sql) or die($sql);
        } else {
            $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields;
            $rs = mysql_query($sql) or die($sql);
            $id = mysql_insert_id();
            if ($id) {
                $this->volunteering['id'] = $id;
            }
        }
        if (mysql_affected_rows()) {
            $this->setById($this->volunteering['id']);
            return true;
        } else {
            $this->setById($this->volunteering['id']);
            return false;
        }
    }

    public function delete()
    {
        if (isset($this->volunteering["id"])) {
            $sql = 'DELETE FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($this->volunteering['id']) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                return true;
            } else {
                return false;
            }
        }
    }
}
