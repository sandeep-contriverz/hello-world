<?php

namespace Hmg\Controllers;

use Hmg\Models\Volunteer;
use Hmg\Models\Volunteering;
use Hmg\Models\Setting;

class VolunteerController
{
    public function __construct()
    {

        $volunteer = new Volunteer();
        $city = new Setting('city');

        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            $volunteer->setById($_REQUEST['id']);
            $volunteering = new Volunteering();
            $volunteering->getAllByVolunteerId($volunteer->volunteer['id']);
        } else if (isset($_REQUEST['save']) || isset($_REQUEST['delete'])) {
            $volunteer->setVolunteer($_REQUEST['data']);
        }

        if (isset($_REQUEST['save']) && $_REQUEST['save']) {
            // Show a form for adding

            if (!$volunteer->volunteer['last_name']
                 || !$volunteer->volunteer['first_name']
                 || !$volunteer->volunteer['organization']
                 || !$volunteer->volunteer['phone']
            ) {
                $volunteer->message = 'Missing Required Field! <br />Required fields are Primary Contact (first name, last name, employer, and phone number)!';
                $this->displayVolunteerForm($volunteer, $city, $volunteer->message);
            } else {
                $saved = $volunteer->save();
                if ($saved) {
                    $volunteer->message = 'Information was saved successfully.';
                    $volunteering = new Volunteering();
                    $volunteering->getAllByVolunteerId($volunteer->volunteer['id']);
                    $this->displayVolunteer($volunteer, $volunteering);
                } else {
                    $volunteer->message = 'Failed to update or there were no changes to the record.';
                    $this->displayVolunteerForm($volunteer, $city, $volunteer->message);
                }
            }
        } else if (isset($_REQUEST['delete']) && $_REQUEST['delete'] && $volunteer->volunteer["id"]) {
            $deleted = $volunteer->delete();
            if ($deleted) {
                $message = 'Volunteer was removed successfully!';
                header("Location: index.php?action=Volunteers&message=" . urlencode($message));
            } else {
                $volunteer->message = 'System Error: Was not able to remove Volunteer!';
                $this->displayVolunteerForm($volunteer, $city, $volunteer->message);
            }
        } else if (is_numeric($_REQUEST['id']) && isset($_REQUEST['loadHours']) && $_REQUEST['loadHours']) {
            $this->displayVolunteerHours($volunteer, $volunteering);
        } else if (isset($_REQUEST['id']) && $_REQUEST['id'] == 'new' || $volunteer->volunteer["id"] && isset($_REQUEST['edit']) && $_REQUEST['edit']) {
            $this->displayVolunteerForm($volunteer, $city, '');
        } else if (is_numeric($_REQUEST['id'])) {
            $this->displayVolunteer($volunteer, $volunteering);
        } else {
            header("Location: index.php?action=volunteers");
        }
    }

    public function displayVolunteer($volunteer, $volunteering, $message = null)
    {

        $data = $volunteer->getAll();
        $hoursData = $volunteering->hours;

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        $settingOb = new Setting(); //191016
        $volunteering_type = new Setting('volunteering_type'); //211016
        include(VIEW_PATH . '/volunteer.phtml');
        $main_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/admin.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function displayVolunteerHours($volunteer, $volunteering)
    {

        $data = $volunteer->getAll();
        $hoursData = $volunteering->hours;
        $settingOb = new Setting(); //191016
        $volunteering_type = new Setting('volunteering_type'); //211016
        header('Content-Type: text/html');
        include(VIEW_PATH . '/hours.phtml');

    }

    public function displayVolunteerForm($volunteer, $city, $message = null)
    {

        $data = $volunteer->getAll();

        $volunteer_heard = new Setting('volunteer_heard');
        $volunteer_heard = $volunteer_heard->settings;
        //echo "<pre>";print_r($volunteer_heard);die;

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        $settingOb = new Setting(); //211016
        $volunteering_type = new Setting('volunteering_type'); //211016
        include(VIEW_PATH . '/volunteer-form.phtml');
        $main_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/admin.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }
}
