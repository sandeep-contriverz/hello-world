<?php

namespace Hmg\Models;

class RegionCounties
{
    protected $regionId = null;
    public $counties = array();

    public function __construct($regionId = null, $counties = array())
    {
        $regionId = trim($regionId);
        if (! is_null($regionId) && is_numeric($regionId)) {
            $this->regionId = $regionId;
        }

        $this->counties = $counties;
    }

    public function set($key, $value)
    {
        $this->$key = $value;
    }

    public function get($key)
    {
        return $this->$key;
    }

    public function setCounties()
    {
        if (isset($this->regionId) && is_numeric($this->regionId)) {
            $sql = '
                SELECT
                    rc.region_id,
                    rc.county_id,
                    r.name region,
                    r.disabled region_disabled,
                    c.name county,
                    c.disabled setting_disabled
                FROM `region_counties` rc
                JOIN settings r ON rc.region_id = r.id
                JOIN settings c ON rc.county_id = c.id
                WHERE rc.region_id = "' . mysql_real_escape_string($this->regionId) . '" ORDER BY region ASC, county ASC';
            $rs = mysql_query($sql) or die($sql . '<br />' . mysql_error());
       
            if ($rs) {
                $counties = array();
                while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                    $counties[] = array(
                        'id'       => $row['county_id'],
                        'name'     => $row['county'],
                        'disabled' => ($row['region_disabled'] ? '1' : '0'),
                        'setting-disabled' => ($row['setting_disabled'] ? '1' : '0')
                    );
                }
                $this->counties = $counties;
            }
        }
    }

    public function getFilteredCounties($selected)
    {
        $filteredCounties = array();
        $sql = '
            SELECT
                c.id county_id,
                c.name county,
                c.disabled setting_disabled,
                r.name region,
                r.disabled region_disabled
            FROM `settings` c
            LEFT JOIN region_counties rc ON c.id = rc.county_id
            LEFT JOIN settings r ON rc.region_id = r.id
            WHERE c.type="county" AND rc.county_id IS NULL
                AND c.disabled != "1"' .
            ($selected ? ' OR rc.county_id = "' . mysql_real_escape_string($selected) . '"' :'') . '
            ORDER BY county ASC, county ASC';
        $rs = mysql_query($sql) or die($sql . '<br />' . mysql_error());
        if ($rs) {
            $counties = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $filteredCounties[] = array(
                    'id'       => $row['county_id'],
                    'name'     => $row['county'],
                    'disabled' => ($row['region_disabled'] ? '1' : '0'),
                    'setting-disabled' => ($row['setting_disabled'] ? '1' : '0')
                );
            }
        }
		
		

        return $filteredCounties;
    }

    public function save()
    {
        $counties = $this->counties;
        if (isset($this->regionId) && is_numeric($this->regionId)) {
            // Ignore disabled ones
            $sql = '
                DELETE rc.*
                FROM `region_counties` rc
                JOIN settings c on rc.county_id = c.id
                WHERE rc.region_id = "' . mysql_real_escape_string($this->regionId) . '" AND c.disabled != "1"';
            $rs = mysql_query($sql) or die($sql);
            if (isset($this->counties) && is_array($this->counties)) {
                foreach ($this->counties as $county) {
                    $sql = '
                        INSERT IGNORE INTO
                            `region_counties`
                        SET
                            region_id = "' . mysql_real_escape_string($this->regionId) .  '",
                            county_id = "' . mysql_real_escape_string($county['id']) . '"';
                    $rs = mysql_query($sql);
                }
            }
            $this->setCounties();
        }
    }

    public function updateCountyDisabled($countyId, $disabled)
    {
        if ($this->regionId) {
            $sql = '
                UPDATE
                    settings
                SET disabled = "' . (!empty($disabled) ? '1' : '0') . '"
                WHERE
                    id = "' . mysql_real_escape_string($countyId) . '"';
            $rs = mysql_query($sql);
            return $rs;
        }
    }

    public function getList($filters = array())
    {
        $sql = '
                SELECT
                    r.id region_id,
                    rc.county_id,
                    r.name region,
                    r.disabled region_disabled,
                    c.name county,
                    c.disabled setting_disabled
                FROM settings r
                LEFT JOIN `region_counties` rc ON r.id = rc.region_id
                LEFT JOIN settings c ON rc.county_id = c.id
                WHERE r.type="region" '
                . ($this->regionId ? ' AND rc.region_id = "' . mysql_real_escape_string($this->regionId) . '"' : '')
                . ' ORDER BY region ASC, county ASC';
        $rs = mysql_query($sql);
        if ($rs) {
            $rows = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $rows[] = $row;
            }
            return $rows;
        } else {
            return false;
        }
    }

    public function getAll()
    {
        $sql = '
                SELECT
                    r.id region_id,
                    rc.county_id,
                    r.name region,
                    r.disabled region_disabled,
                    c.name name,
                    c.disabled setting_disabled
                FROM settings r
                LEFT JOIN `region_counties` rc ON r.id = rc.region_id
                LEFT JOIN settings c ON rc.county_id = c.id
                WHERE r.type="region" '
                . ($this->regionId ? ' AND rc.region_id = "' . mysql_real_escape_string($this->regionId) . '"' : '')
                . ' ORDER BY region ASC, name ASC';

        $rs = mysql_query($sql);
        if ($rs) {
            $rows = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $rows[$row['region']][] = $row;
            }
            return $rows;
        } else {
            return false;
        }
    }

    public function getNamesAndIds($searchString)
    {
        $sql = '
        SELECT
            r.id, r.name
        FROM
            `settings` r
        WHERE
            r.type = "region"
                AND r.name LIKE "%' . mysql_real_escape_string($searchString) . '%"
                AND r.disabled != "1"
        ORDER BY r.name ASC';
        $rs = mysql_query($sql);
        if ($rs) {
            $rows = array();
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $rows[] = array(
                    'id' => $row['id'],
                    'name' => $row['name']
                );
            }
            return $rows;
        } else {
            return false;
        }
    }

    public function displayCountySelect(
        $name,
        $selected,
        $label = '',
        $tabIndex = '',
        $required = false,
        $addtlclasses = null,
        $filtered = false,
        $allowDisableSelect = true
    )  {
        if ($label) {
            $options = '<option value="">' . $label . '</option>';
        } else {
            $options = '';
        }
        $disableSelect = false;
		
        if ($filtered) {
            $counties = $this->getFilteredCounties($selected);
        } else {
            $counties = $this->counties;
        }
        if (is_array($counties)) {
            foreach ($counties as $county) {
                if ($county['id'] === $selected && $county['disabled']) {
                    if ($allowDisableSelect) {
                        $disableSelect = true;
                    }
                }
                $options .= '<option value="' . $county['id'] . '"' . ($selected == $county['id'] ? ' selected="selected"' : '') . '>' . $county['name'] . ($county['disabled'] || $county['setting-disabled'] ? ' (Inactive)' : '') . '</option>';
            }
        }
        $select = '<select id="' . $name . '" class="setting' . ($required ? ' required' : '') . ($disableSelect ? ' setting-input setting-input-disabled' : '') . ($addtlclasses ? ' ' . $addtlclasses : '') . '" name="' . $name . '" tabindex="' . $tabIndex . '"' . ($disableSelect ? ' disabled' : '') . '>' . $options . '</select>';
        return $select;
    }
}
