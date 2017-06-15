<?php

namespace Hmg\Models;

class Users
{
    private $_table = 'users';
    private $_sorts = array('last_name' => 'ASC', 'first_name' => 'ASC');
    private $_start = 0;
    private $_limit = 50;
    private $_mysql_error = null;

    public function __construct()
    {

    }

    public function set($key, $value)
    {
        $this->$key = $value;
    }

    public function get($key)
    {
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
                WHERE 1 ' . $order_by . ($this->_start ? 'LIMIT ' . $this->_start . ', ' . ($this->_limit ? $this_limit : '50') : '');
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

    public function getCount()
    {
        $sql = 'SELECT * FROM `' . mysql_real_escape_string($this->_table) . '`
                WHERE 1';
        $rs = mysql_query($sql);
        return mysql_num_rows($rs);
    }
}
