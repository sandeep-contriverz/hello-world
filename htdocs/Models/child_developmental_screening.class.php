<?php

namespace Hmg\Models;

class ChildDevelopmentalScreening
{
    private $_table = 'child_developmental_screenings';
    public $developmentalScreening = array();
    public $message = null;

    public function __construct()
    {
    }

    public function setDevelopmentalScreening($developmentalScreening)
    {
        // always add new data
        $this->developmentalScreening= array();
        if (is_array($developmentalScreening)) {
            foreach ($developmentalScreening as $key => $value) {
                $this->developmentalScreening[$key] = $value;
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
                $this->setDevelopmentalScreening(mysql_fetch_array($rs, MYSQL_ASSOC));
            }
        }
    }

    public function getChildScreeningsCount($child_id)
    {
        if (is_numeric($child_id)) {
            $sql = 'SELECT count(*) num_screenings FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `child_id` = "' . mysql_real_escape_string($child_id) . '" GROUP BY `child_id`';
            $rs = mysql_query($sql);
            if (mysql_num_rows($rs)) {
                $row = mysql_fetch_array($rs, MYSQL_ASSOC);
                return $row['num_screenings'];
            }
        }
    }

    public function getAll()
    {
        return $this->developmentalScreening;
    }

    public function save()
    {
        $setFields = '';
        foreach ($this->developmentalScreening as $field => $value) {
            //if ($value) {
                $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
            //}
        }
        if ($this->developmentalScreening["id"]) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . '
                    WHERE `id` = "' . mysql_real_escape_string($this->developmentalScreening['id']) . '"';
            $rs = mysql_query($sql) or die($sql);
        } else {
            $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields;
            $rs = mysql_query($sql) or die($sql);
            $id = mysql_insert_id();
            if ($id) {
                $this->developmentalScreening['id'] = $id;
            }
        }
        if (mysql_affected_rows()) {
            $this->setById($this->developmentalScreening['id']);
            return true;
        } else {
            $this->setById($this->developmentalScreening['id']);
            return false;
        }
    }

    public function delete()
    {
        if ($this->developmentalScreening["id"]) {
            $sql = 'DELETE FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($this->developmentalScreening['id']) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                return true;
            } else {
                return false;
            }
        }
    }
}
