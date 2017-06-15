<?php

namespace Hmg\Models;

class ChildPriorResource
{
    private $_table = 'child_prior_resources';

    public $resource = array(
        'id'                      => null,
        'child_id'                => null,
        'service_type'            => null,
        'date_enrolled'           => null,
        'notes'                   => null,
        'date_enrolled_formatted' => null

    );
    public $message = null;

    public function __construct($resource = null)
    {
        if ($resource) {
            $this->setResource($resource);
        }
    }

    public function setResource($resource)
    {
        // always add new data
        $this->resource= array();
        if (is_array($resource)) {
            foreach ($resource as $key => $value) {
                $this->resource[$key] = $value;
            }
        }
    }

    public function setById($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT *, DATE_FORMAT(date_enrolled, "%m/%d/%y") date_enrolled_formatted'
            . ' FROM `' . mysql_real_escape_string($this->_table) . '`'
            . ' WHERE `' . mysql_real_escape_string($this->_table). '`.`id` = "' . mysql_real_escape_string($id) . '"';
            $rs = mysql_query($sql) or die($sql);
            if (mysql_num_rows($rs)) {
                $this->setResource(mysql_fetch_array($rs, MYSQL_ASSOC));
            }
        }
    }

    public function save()
    {
        $setFields = '';
        foreach ($this->resource as $field => $value) {
            //if ($value) {
                $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
            //}
        }
        if (isset($this->resource["id"]) && $this->resource["id"]) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . '
                    WHERE `id` = "' . mysql_real_escape_string($this->resource['id']) . '"';
            $rs = mysql_query($sql) or die($sql . "<br />" . mysql_error());
        } else {
            $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields;
            $rs = mysql_query($sql) or die($sql . "<br />" . mysql_error());
            $id = mysql_insert_id();
            if ($id) {
                $this->resource['id'] = $id;
            }
        }
        if (mysql_affected_rows()) {
            $this->setById($this->resource['id']);
            return true;
        } else {
            $this->setById($this->resource['id']);
            return false;
        }
    }

    public function delete()
    {
        if (isset($this->resource["id"]) && $this->resource["id"]) {
            $sql = 'DELETE FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($this->resource['id']) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                return true;
            } else {
                return false;
            }
        }
    }
}
