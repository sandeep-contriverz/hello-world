<?php

namespace Hmg\Models;

class Notes
{
    private $_table = 'notes';
    private $_sorts = array('modified' => 'DESC');
    private $_start = 0;
    private $_limit = 0;
    private $_fkey = 'family_id';
    private $_fkeyValue = 0;
    private $_mysql_error = null;

    public function __construct($fkeyValue = null)
    {
        if ($fkeyValue) {
            $this->_fkeyValue = $fkeyValue;
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
        $sql = 'SELECT notes.*, DATE_FORMAT(modified, "%m/%d/%Y") formatted_date, u.hmg_worker FROM `' . mysql_real_escape_string($this->_table) . '`
				LEFT JOIN `users` u ON `' . mysql_real_escape_string($this->_table) . '`.hmg_worker = u.id
                WHERE `' . mysql_real_escape_string($this->_fkey) . '` = "' . mysql_real_escape_string($this->_fkeyValue) . '" ' . $order_by . ($this->_start ? 'LIMIT ' . $this->_start . ', ' . ($this->_limit ? $this_limit : '20') : '');
                //echo $sql;
        $rs = mysql_query($sql);
        if ($rs) {
            $rows = '';
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $rows[] = $row;
            }
            return $rows;
        } else {
            return false;
        }
    }
}
