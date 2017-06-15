<?php

namespace Hmg\Controllers;

use Hmg\Models\Setting;
use Hmg\Models\ChildFollowUps;
use Hmg\Models\ChildFollowUp;
use Hmg\Models\Family;
use Hmg\Models\ReferralServices;

class ChildFollowUpController
{
    public function __construct()
    {

        if (isset($_REQUEST['save']) && isset($_REQUEST['child_id']) && is_numeric($_REQUEST['child_id'])) {
            $followUp = $_REQUEST['followUp'];
            $followUp['child_id'] = $_REQUEST['child_id'];
            $followUp['hmg_worker'] = $_REQUEST['hmg_worker'];
            if (!empty($followUp['follow_up_date'])) {
                $followUp['follow_up_date'] = date('Y-m-d', strtotime($followUp['follow_up_date']));
            } else {
                $followUp['follow_up_date'] = date('Y-m-d');
            }
            $followUp['referral_date'] = date('Y-m-d');
            $childFollowUp = new ChildFollowUp($followUp);
            $saved = $childFollowUp->save();
            $this->displayChildFollowUpList($childFollowUp->followup);
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
            $childFollowUp = new ChildFollowUp($followUp);
            $saved = $childFollowUp->save();
            $this->displayChildFollowUpList($childFollowUp->followup);
        } else if (isset($_REQUEST['delete']) && isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            unset($_REQUEST['action'], $_REQUEST['delete']);
            $childFollowUp = new ChildFollowUp($_REQUEST);
            $childFollowUp->setById($_REQUEST['id']);
            $deleted = $childFollowUp->delete();
            $this->displayChildFollowUpList($childFollowUp->followup);
        } else if (isset($_REQUEST['child_id']) && is_numeric($_REQUEST['child_id']) && isset($_REQUEST['get-view'])) {
            $childFollowUps = new ChildFollowUps($_REQUEST['child_id']);
            $followUpsRows = $childFollowUps->getList();
            $lastFollowUp = array_shift($followUpsRows);
            $this->displayFollowUpView($lastFollowUp);
        } else if (isset($_REQUEST['child_id']) && is_numeric($_REQUEST['child_id']) && isset($_REQUEST['get-list'])) {
            $childFollowUp = new ChildFollowUp($_REQUEST);
            $this->displayChildFollowUpList($childFollowUp->followup);
        } else if (isset($_REQUEST['child_id']) && is_numeric($_REQUEST['child_id'])) {
            $childFollowUps = new ChildFollowUps($_REQUEST['child_id']);
            $followUps = $childFollowUps->getList();
            $this->displayFollowUpsJson($followUps);
        } else if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) && isset($_REQUEST['get-form'])) {
            $childFollowUp = new ChildFollowUp();
            $childFollowUp->setById($_REQUEST['id']);
            $this->displayFollowUpForm($childFollowUp->followup);
        } else if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) && isset($_REQUEST['get-view'])) {
            $childFollowUp = new ChildFollowUp();
            $childFollowUp->setById($_REQUEST['id']);
            $this->displayFollowUpView($childFollowUp->followup);
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

        $setting               = new Setting();
        $referredTo            = new Setting('referred_to');
        $service               = new Setting('service');
        $followUpTask          = new Setting('follow_up_task');
        $follow_up_task_result = new Setting('follow_up_task_result');

        $familyFollowUp = new ChildFollowUps($followUp['child_id']);
        $family = new Family();

        $referralServices = new ReferralServices($followUp['referred_to_id']);
        $referralServices->referredToType = $followUp['referred_to_type'];
        $referralServices->setServices();

        $rowClass = 'odd';
        if (isset($_REQUEST['rowClass'])) {
            $rowClass = $_REQUEST['rowClass'];
        }

        if ($followUp['id']) {
            ob_start();
            include(VIEW_PATH . "/edit-follow-up.phtml");
            $html = ob_get_contents();
            ob_end_clean();
            header('Content-type: text/html');
            echo $html;
        }

    }

    public function displayChildFollowUpList($followUp)
    {

        $setting = new Setting();
        $referredTo = new Setting('referred_to');
        $issues = new Setting('issues');
        $service = new Setting('service');
        $followUpTask = new Setting('follow_up_task');
        $gaps = new Setting('gaps');
        $barriers = new Setting('barriers');

        $familyFollowUp = new ChildFollowUps($followUp['child_id']);
        $followUps = $familyFollowUp->getList();

        $rowclass = 'odd';
        if (isset($_REQUEST['rowclass'])) {
            $rowclass = $_REQUEST['rowclass'];
        }

        $followUpType = 'Child';

        ob_start();
        include(VIEW_PATH . "/family-follow-ups.phtml");
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
        $followUpTask = new Setting('follow_up_task');

        $familyFollowUp = new ChildFollowUps($followUp['child_id']);

        $rowclass = 'odd';
        if (isset($_REQUEST['rowclass'])) {
            $rowclass = $_REQUEST['rowclass'];
        }

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
