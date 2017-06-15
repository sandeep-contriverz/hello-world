<?php

namespace Hmg\Models;

class UserLogs
{
    private $_table = 'user_logs';
    public $log = array();
    public $message = null;

    public function __construct(array $log = null)
    {
        if (is_array($log)) {
            $this->setLog($log);
        }
    }

    public function setLog($log)
    {
        if (is_array($log)) {
            foreach ($log as $key => $value) {
                $this->log[$key] = $value;
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
                $this->setLog(mysql_fetch_array($rs, MYSQL_ASSOC));
            }
        }
    }

    public function getLog()
    {
        return $this->log;
    }

    public function getAllLogsByUserId($userId)
    {
        if (!is_numeric($userId)) {
            return null;
        }
        $logs = array();
        if (is_numeric($userId)) {
            $sql = 'SELECT * FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `user_id` = "' . mysql_real_escape_string($userId) . '"';
            $rs = mysql_query($sql);
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $logs[$row['id']] = $row;
            }
        }

        return $logs;
    }

    public function getLastLogByUserId($userId)
    {
        if (!is_numeric($userId)) {
            return null;
        }
        $logs = array();
        if (is_numeric($userId)) {
            $sql = 'SELECT * FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `user_id` = "' . mysql_real_escape_string($userId) . '"
                    ORDER BY login_date DESC LIMIT 1';
            $rs = mysql_query($sql);
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $logs[$row['id']] = $row;
            }
        }

        return $logs;
    }

    public function save()
    {
        $setFields = '';
        foreach ($this->log as $field => $value) {
            $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
        }
        if (isset($this->log) && isset($this->log["id"])) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . '
                    WHERE `id` = "' . mysql_real_escape_string($this->log['id']) . '"';
            $rs = mysql_query($sql);
        } else {
            $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields;
            $rs = mysql_query($sql);
            $id = mysql_insert_id();
            if ($id) {
                $this->log['id'] = $id;
            }
        }
        if (mysql_affected_rows()) {
            return true;
        } else {
            return false;
        }
    }
}
