<?php

namespace Hmg\Models;

class Settings
{
    private $_table = 'settings';
    private $_sorts = array('type' => 'ASC', 'name' => 'ASC');
    private $_start = 0;
    private $_limit = 20;
    private $_mysql_error = null;

    public function __construct($sorts = null, $start = null, $limit = null)
    {
        if ($sort_fields) {
            $this->_sorts        = $sorts;
        }

        if ($start) {
            $this->_start        = $start;
        }
        if ($limit) {
            $this->_limit        = $limit;
        }
    }

    public function set($key, $value)
    {
        $this->$key = $value;
    }

    public function get($key)
    {
        $key = '_' . $key;
        return $this->$key;
    }

    public function getList()
    {
        if (is_array($this->_sorts)) {
            $order_by = 'ORDER BY ';
            $concat = false;
            foreach ($this->_sorts as $field => $dir) {
                $order_by .= ($concat ? ', ' : '') . mysql_real_escape_string($field) . ' ' . mysql_real_escape_string($dir);
                $concat = true;
            }
        }
        $sql = 'SELECT * FROM `' . mysql_real_escape_string($this->_table) . '`
                WHERE 1 ' . $order_by . ($this->_start ? 'LIMIT ' . $this->_start . ', ' . ($this->_limit ? $this_limit : '20') : '');
                //echo $sql;
        $rs = mysql_query($sql);
        if ($rs) {
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $rows[] = $row;
            }
            return $rows;
        } else {
            return false;
        }
    }
}
