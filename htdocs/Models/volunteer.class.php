<?php

namespace Hmg\Models;

class Volunteer
{
    private $_table = 'volunteers';
    public $volunteer = array(
        'id' => null,
        'last_name' => null,
        'first_name' => null,
        'address' => null,
        'city' => null,
        'state' => null,
        'zip' => null,
        'status' => null,
        'organization' => null,
        'phone' => null,
        'language' => null,
        'email' => null,
        'how_heard' => null,
        'notes' => null,
        'family_event' => null,
        'data_entry' => null,
        'care_coordination' => null,
        'special_projects' => null,
        'parent_mentor' => null,
        'eagle_scout' => null

    );
    public $message = null;

    public function __construct()
    {
    }

    public function setVolunteer($volunteer)
    {
        // always add new data
        $this->volunteer= array();
        if (is_array($volunteer)) {
            foreach ($volunteer as $key => $value) {
                if(isset($_REQUEST['save']) 
                    && $key == 'volunteering_type'
                    && is_array($value)
                ) {
                    $types = implode(',', $value);
                    $this->volunteer[$key] = $types;
                } else {
                    $this->volunteer[$key] = $value;
                }
            }
        }
    }

    public function setById($id)
    {
        if (is_numeric($id)) {
            $sql = 'SELECT * , concat_ws( ",", if ( family_event = "1", "Family Event", "" ) , if ( data_entry = "1", "Data Entry", "" ) , if ( care_coordination = "1", "Care Coordination", "" ) , if ( special_projects = "1", "Special Projects", "" ) , if ( parent_mentor = "1", "Parent Mentor", "" ) , if ( eagle_scout = "1", "Eagle Scout", "" ) ) as `areas` FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($id) . '"';
            $rs = mysql_query($sql);
            if (mysql_num_rows($rs)) {
                $this->setVolunteer(mysql_fetch_array($rs, MYSQL_ASSOC));
            }
        }
    }

    public function getAll()
    {
        return $this->volunteer;
    }

    public function save()
    {
        $setFields = '';
        foreach ($this->volunteer as $field => $value) {
            //if ($value) {
                $setFields .= ($setFields ? ', ' : '') . '`' . $field . '` = "' . mysql_real_escape_string($value) . '"';
            //}
        }
        if ($this->volunteer["id"]) {
            $sql = 'UPDATE `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . ', modified = NOW()
                    WHERE `id` = "' . mysql_real_escape_string($this->volunteer['id']) . '"';
            $rs = mysql_query($sql) or die($sql);
        } else {
            $sql = 'INSERT INTO `' . mysql_real_escape_string($this->_table) . '` SET ' . $setFields . ', modified = NOW()';
            $rs = mysql_query($sql) or die($sql);
            $id = mysql_insert_id();
            if ($id) {
                $this->volunteer['id'] = $id;
            }
        }
        if (mysql_affected_rows()) {
            $this->setById($this->volunteer['id']);
            return true;
        } else {
            $this->setById($this->volunteer['id']);
            return false;
        }
    }

    public function delete()
    {
        if ($this->volunteer["id"]) {
            $sql = 'DELETE FROM `' . mysql_real_escape_string($this->_table) . '`
                    WHERE `id` = "' . mysql_real_escape_string($this->volunteer['id']) . '"';
            $rs = mysql_query($sql);
            if ($rs) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function displayEnumSelect($name, $field, $selected, $label = '', $tabIndex = '', $disabled=false)
    {
        $sql = 'SHOW COLUMNS FROM `' . $this->_table . '` LIKE  "' . mysql_real_escape_string($field) . '"';
        $rs = mysql_query($sql);
        if ($label) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '';
        }
        if ($rs) {
            $row = mysql_fetch_array($rs, MYSQL_ASSOC);
            $values = explode(',', str_replace('enum(', '', rtrim($row['Type'], ')')));
            sort($values);
            if (is_array($values)) {
                foreach ($values as $key => $value) {
                    $trimmed = trim($value, "'");
                    if ($trimmed) {
                        //check if its disabled then continue loop
                        if($field == 'how_heard' && $disabled) {
                            $sql_check = "SELECT * FROM `settings` WHERE `type`= 'volunteer_heard' 
                                AND `name` = '".$trimmed."'";
                            $rs_check = mysql_query($sql_check);
                            $row_check = mysql_fetch_array($rs_check, MYSQL_ASSOC);
                            //echo "<pre>";print_r($row_check);die;
                            if(!empty($row_check) && !empty($row_check['disabled']))
                                continue;
                        }
                        $options .= '<option value="' . $trimmed . '"' . ($selected == $trimmed ? ' selected="selected"' : '') . '>' . $trimmed . '</option>';
                    }
                }
            }
            $select = '<select id="' . $field . '" class="' . $field . '" name="' . $name . '"' . ($tabIndex ? ' tabindex="' . $tabIndex . '"' : '') . '>' . $options . '</select>';
            return $select;
        }
    }

    public function displayAreaSelect($field = 'areas', $fields = array(), $selected = '', $label = '')
    {
        if ($label) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '';
        }
        if (count($fields)) {
            asort($fields);
            foreach ($fields as $key => $value) {
                $options .= '<option value="' . $key . '"' . ($selected == $key ? ' selected="selected"' : '') . '>' . $value . '</option>';
            }
            $select = '<select id="' . $field . '" class="' . $field . '" name="filters[' . $field . ']">' . $options . '</select>';
            return $select;
        }
    }
}
