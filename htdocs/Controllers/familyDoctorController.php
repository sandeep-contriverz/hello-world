<?php

namespace Hmg\Controllers;

use Hmg\Models\FamilyDoctor;

class FamilyDoctorController
{
    public function __construct()
    {
        if (isset($_REQUEST['save']) && is_numeric($_REQUEST['family_id']) && is_numeric($_REQUEST['doctor_id'])) {
            $familyDoctor = new FamilyDoctor($_REQUEST['family_id'], $_REQUEST['doctor_id']);
            $saved = $familyDoctor->save();
            $this->displayActionResult('save', $saved);
        } else if (isset($_REQUEST['delete']) && is_numeric($_REQUEST['family_id']) && is_numeric($_REQUEST['doctor_id'])) {
            $familyDoctor = new FamilyDoctor($_REQUEST['family_id'], $_REQUEST['doctor_id']);
            $deleted = $familyDoctor->delete();
            $this->displayActionResult('deleted', $deleted);
        } else if (is_numeric($_REQUEST['family_id'])) {
            $familyDoctor = new FamilyDoctor($_REQUEST['family_id']);
            $doctors = $familyDoctor->getList();
            $this->displayDoctorsJson($doctors);
        } else {
            //header("Location: index.php");
        }
    }

    public function displayDoctorsJson($doctors)
    {
        $json = json_encode($doctors);
        header("Content-type: application/json");
        echo $json;
    }

    public function displayActionResult($type, $result)
    {
        $json = '{ "' . $type . '" : "' . ($result ? 'true' : 'false') . '" }';
        header("Content-type: application/json");
        echo $json;
    }
}
