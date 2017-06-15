<?php

namespace Hmg\Models;

class ScreeningAttachments
{
    private $_table = 'child_screening_attachments';
    private $_sorts = array('attachment_name' => 'ASC');
    private $_type = null;
    private $_start = 0;
    private $_limit = 0;
    private $_fkey = 'screening_id';
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
            $order_by = ' ORDER BY ';
            $concat = false;
            foreach ($this->_sorts as $field => $dir) {
                $order_by .= ($concat ? ', ' : '') . mysql_real_escape_string($field) . ' ' . mysql_real_escape_string($dir);
                $concat = true;
            }
        }
        $sql = 'SELECT *, IF(LENGTH(attachment_name) > 20,  CONCAT(LEFT(attachment_name, 20), "..."), attachment_name) attachment_shortname
                FROM `' . mysql_real_escape_string($this->_table) . '`
                WHERE `' . mysql_real_escape_string($this->_fkey) . '` = "' . mysql_real_escape_string($this->_fkeyValue) . '"'
                . ($this->_type ? ' AND `type` = "' . mysql_real_escape_string($this->_type) . '"' : '')
                . $order_by
                . ($this->_start ? 'LIMIT ' . $this->_start . ', ' . ($this->_limit ? $this_limit : '20') : '');
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
