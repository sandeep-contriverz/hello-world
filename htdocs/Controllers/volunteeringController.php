<?php

namespace Hmg\Controllers;

use Hmg\Models\Volunteering;

class VolunteeringController
{
    public function __construct()
    {

        $volunteering = new Volunteering();

        if (isset($_REQUEST['save'])) {
            $data['volunteer_id'] = isset($_REQUEST['volunteerId']) ? $_REQUEST['volunteerId'] : 0;
            $data['date'] = isset($_REQUEST['date']) ? $_REQUEST['date'] : '';
            $data['hours'] = isset($_REQUEST['hours']) ? $_REQUEST['hours'] : '';
            $data['type'] = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';
            $data['id']   = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
            $volunteering->setVolunteering($data);

            $saved = 'false';
        
            if (empty($volunteering->volunteering['date'])
             || !preg_match('/^-?(?:\d+|\d*\.\d+)$/', $volunteering->volunteering['hours'])
             || !$volunteering->volunteering['type']
             || !$volunteering->volunteering['volunteer_id']
            ) {
                $volunteering->message = 'Missing or Invalid Required Field! <br />Required fields are date (2013-01-01), hours (1.25), and type!';
            } else {
                $saved = $volunteering->save();
                if ($saved) {
                    $volunteering->message = 'Information was saved successfully.';
                } else {
                    $volunteering->message = 'Failed to update or there were no changes to the record.';
                }
            }
            header('Content-type: application/json');
            echo '{"saved":"' . $saved . '","message":"' . $volunteering->message . '"}';

        }
        if (isset($_REQUEST['volunteer_id'])) {
            $volunteering->getAllByVolunteerId();
            header('Content-type: application/json');
            echo json_encode($volunteering->hours);
        }
    }
}
