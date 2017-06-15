<?php

namespace Hmg\Controllers;

use Hmg\Models\Organization;
use Hmg\Models\ContactFollowUps;
use Hmg\Models\ContactFollowUp;
use Hmg\Models\Setting;
use Hmg\Models\ReferralServices;
use Hmg\Models\User;

class ContactFollowUpController
{
    public function __construct()
    {

        if (isset($_REQUEST['save']) && isset($_REQUEST['contact_id']) && is_numeric($_REQUEST['contact_id'])) {
            $followUp = $_REQUEST['followUp'];
            $followUp['contact_id'] = $_REQUEST['contact_id'];
            $followUp['hmg_worker'] = $_REQUEST['hmg_worker'];
            if (!empty($followUp['follow_up_date'])) {
                $followUp['follow_up_date'] = date('Y-m-d', strtotime($followUp['follow_up_date']));
            } else {
                $followUp['follow_up_date'] = date('Y-m-d');
            }
            $followUp['referral_date'] = date('Y-m-d');
            $ContactFollowUp = new ContactFollowUp($followUp);

            $saved = $ContactFollowUp->save();
            $this->displayContactFollowUpList($ContactFollowUp->followup);
        } else if (isset($_REQUEST['save']) && isset($_REQUEST['followUp']) && is_array($_REQUEST['followUp'])) {
            unset($_REQUEST['action'], $_REQUEST['save']);
            list($id, $followUp) = each($_REQUEST['followUp']);
            $followUp['id'] = $id;
            if (isset($followUp['referral_date'])) {
                $followUp['referral_date'] = date('Y-m-d', strtotime($followUp['referral_date']));
            } else {
                if (! isset($followUp['done'])) {
                    $followUp['referral_date'] = date('Y-m-d');
                }
            }
            if (!empty($followUp['follow_up_date'])) {
                $followUp['follow_up_date'] = date('Y-m-d', strtotime($followUp['follow_up_date']));
            } else {
                $followUp['follow_up_date'] = date('Y-m-d');
            }
            // Just update the date when we are hitting the done checkbox and checked is true
            if (count($followUp) == 3 && $followUp['done'] !== "1") {
                unset($followUp['follow_up_date']);

            }
            $ContactFollowUp = new ContactFollowUp($followUp);
            $saved = $ContactFollowUp->save();
            $this->displayContactFollowUpList($ContactFollowUp->followup);
        } else if (isset($_REQUEST['delete']) && isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            unset($_REQUEST['action'], $_REQUEST['delete']);
            $ContactFollowUp = new ContactFollowUp($_REQUEST);
            $ContactFollowUp->setById($_REQUEST['id']);
            $deleted = $ContactFollowUp->delete();
            $this->displayContactFollowUpList($ContactFollowUp->followup);
        } else if (isset($_REQUEST['contact_id']) && is_numeric($_REQUEST['contact_id']) && isset($_REQUEST['get-view'])) {
            $ContactFollowUps = new ContactFollowUps($_REQUEST['contact_id']);
            $followUpsRows = $ContactFollowUps->getList();
            $lastFollowUp = array_shift($followUpsRows);
            $this->displayFollowUpView($lastFollowUp);
        } else if (isset($_REQUEST['contact_id']) && is_numeric($_REQUEST['contact_id']) && isset($_REQUEST['get-list'])) {
            $ContactFollowUp = new ContactFollowUp($_REQUEST);
            $this->displayContactFollowUpList($ContactFollowUp->followup);
        } else if (isset($_REQUEST['contact_id']) && is_numeric($_REQUEST['contact_id'])) {
            $ContactFollowUps = new ContactFollowUps($_REQUEST['contact_id']);
            $followUps = $ContactFollowUps->getList();
            $this->displayFollowUpsJson($followUps);
        } else if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) && isset($_REQUEST['get-form'])) {
            $ContactFollowUp = new ContactFollowUp();
            $ContactFollowUp->setById($_REQUEST['id']);
            $this->displayFollowUpForm($ContactFollowUp->followup);
        } else if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) && isset($_REQUEST['get-view'])) {
            $ContactFollowUp = new ContactFollowUp();
            $ContactFollowUp->setById($_REQUEST['id']);
            $this->displayFollowUpView($ContactFollowUp->followup);
        } else {
            //header("Location: index.php");
        }
    }

    public function displayFollowUpsJson($followUps)
    {
        $json = json_encode($followUps);
        header("Content-type: application/json");
        echo $json;
    }

    public function displayFollowUpForm($followUp)
    {
        print_r($followUp);
        $setting               = new Setting();
        $referredTo            = new Setting('referred_to');
        $service               = new Setting('service');
        $followUpTask          = new Setting('outreach_follow_up_task');
        $follow_up_task_result = new Setting('follow_up_task_result');

        $OrganizationFollowUp = new ContactFollowUps($followUp['contact_id']);
        $organization = new Organization();

        $referralServices = new ReferralServices($followUp['referred_to_id']);
        $referralServices->referredToType = $followUp['referred_to_type'];
        $referralServices->setServices();

        $rowclass = 'odd';
        if (isset($_REQUEST['rowclass'])) {
            $rowclass = $_REQUEST['rowclass'];
        }

        if ($followUp['id']) {
            ob_start();
            include(VIEW_PATH . "/edit-organization-follow-up.phtml");
            $html = ob_get_contents();
            ob_end_clean();
            header('Content-type: text/html');
            echo $html;
        }

    }

    public function displayContactFollowUpList($followUp)
    {

        $setting = new Setting();
        $referredTo = new Setting('referred_to');
        $issues = new Setting('issues');
        $service = new Setting('service');
        $followUpTask = new Setting('outreach_follow_up_task');
        $gaps = new Setting('gaps');
        $barriers = new Setting('barriers');

        $OrganizationFollowUp = new ContactFollowUps($followUp['contact_id']);
        $followUps = $OrganizationFollowUp->getList();

        $rowclass = 'odd';
        if (isset($_REQUEST['rowclass'])) {
            $rowclass = $_REQUEST['rowclass'];
        }

        $followUpType = 'Provider';//Change "Contact to Provider" Date: 15/02/2017

        ob_start();
        include(VIEW_PATH . "/organization-follow-ups.phtml");
        $html = ob_get_contents();
        ob_end_clean();
        header('Content-type: text/html');
        echo $html;
    }

    public function displayFollowUpView($followUp)
    {

        $setting = new Setting();
        $referredTo = new Setting('referred_to');
        $service = new Setting('service');
        $followUpTask = new Setting('outreach_follow_up_task');

        $OrganizationFollowUp = new ContactFollowUps($followUp['contact_id']);

        $rowclass = 'odd';
        if (isset($_REQUEST['rowclass'])) {
            $rowclass = $_REQUEST['rowclass'];
        }
        $userObj = new User();
        $userObj->setById((int)$followUp['hmg_worker']);
        $followUp['hmg_worker'] = !empty($userObj->user['hmg_worker']) ? $userObj->user['hmg_worker'] : 'Unknown';
        ob_start();
        include(VIEW_PATH . "/view-follow-up.phtml");
        $html = ob_get_contents();
        ob_end_clean();
        header('Content-type: text/html');
        echo $html;
    }

    public function displayActionResult($type, $result)
    {
        $json = '{ "' . $type . '" : "' . ($result ? 'true' : 'false') . '" }';
        header("Content-type: application/json");
        echo $json;
    }
}
