<?php

namespace Hmg\Models;

class ReferralService
{
    public function __construct($referralId = null)
    {
        if (! is_null($referralId) && is_numeric($referralId)) {
            $this->referralId = $referralId;
        }
    }

    public function getList()
    {
        $sql = 'SELECT * FROM `setting` r
                WHERE type = "referred_to"
                LEFT JOIN referral_service rs ON r.id = rs.referred_to_id
                LEFT JOIN setting s ON rs.service_id = s.id
                ' . (
                        $this->referralId ?
                            ' WHERE r.id = "' . mysql_real_escape_string($this->referralId) . '"'
                            : ' WHERE 1'
                    ) . '
                ORDER BY r.name, s.name';
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
