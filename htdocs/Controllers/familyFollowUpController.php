<?php

namespace Hmg\Controllers;

use Hmg\Models\Family;
use Hmg\Models\FamilyFollowUps;
use Hmg\Models\FamilyFollowUp;
use Hmg\Models\Setting;
use Hmg\Models\ReferralServices;

class FamilyFollowUpController
{
    public function __construct()
    {

        if (isset($_REQUEST['save']) && isset($_REQUEST['family_id']) && is_numeric($_REQUEST['family_id'])) {
            $followUp = $_REQUEST['followUp'];
            $followUp['family_id'] = $_REQUEST['family_id'];
            $followUp['hmg_worker'] = $_REQUEST['hmg_worker'];
            if (!empty($followUp['follow_up_date'])) {
                $followUp['follow_up_date'] = date('Y-m-d', strtotime($followUp['follow_up_date']));
            } else {
                $followUp['follow_up_date'] = date('Y-m-d');
            }
            $followUp['referral_date'] = date('Y-m-d');
            $familyFollowUp = new FamilyFollowUp($followUp);
            $saved = $familyFollowUp->save();
            $this->displayFamilyFollowUpList($familyFollowUp->followup);
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
            $familyFollowUp = new FamilyFollowUp($followUp);
            $saved = $familyFollowUp->save();
            $this->displayFamilyFollowUpList($familyFollowUp->followup);
        } else if (isset($_REQUEST['delete']) && isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            unset($_REQUEST['action'], $_REQUEST['delete']);
            $familyFollowUp = new FamilyFollowUp($_REQUEST);
            $familyFollowUp->setById($_REQUEST['id']);
            $deleted = $familyFollowUp->delete();
            $this->displayFamilyFollowUpList($familyFollowUp->followup);
        } else if (isset($_REQUEST['family_id']) && is_numeric($_REQUEST['family_id']) && isset($_REQUEST['get-view'])) {
            $familyFollowUps = new FamilyFollowUps($_REQUEST['family_id']);
            $followUpsRows = $familyFollowUps->getList();
            $lastFollowUp = array_shift($followUpsRows);
            $this->displayFollowUpView($lastFollowUp);
        } else if (isset($_REQUEST['family_id']) && is_numeric($_REQUEST['family_id']) && isset($_REQUEST['get-list'])) {
            $familyFollowUp = new FamilyFollowUp($_REQUEST);
            $this->displayFamilyFollowUpList($familyFollowUp->followup);
        } else if (isset($_REQUEST['family_id']) && is_numeric($_REQUEST['family_id'])) {
            $familyFollowUps = new FamilyFollowUps($_REQUEST['family_id']);
            $followUps = $familyFollowUps->getList();
            $this->displayFollowUpsJson($followUps);
        } else if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) && isset($_REQUEST['get-form'])) {
            $familyFollowUp = new FamilyFollowUp();
            $familyFollowUp->setById($_REQUEST['id']);
            $this->displayFollowUpForm($familyFollowUp->followup);
        } else if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) && isset($_REQUEST['get-view'])) {
            $familyFollowUp = new FamilyFollowUp();
            $familyFollowUp->setById($_REQUEST['id']);
            $this->displayFollowUpView($familyFollowUp->followup);
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

        $familyFollowUp = new FamilyFollowUps($followUp['family_id']);
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

    public function displayFamilyFollowUpList($followUp)
    {

        $setting = new Setting();
        $referredTo = new Setting('referred_to');
        $issues = new Setting('issues');
        $service = new Setting('service');
        $followUpTask = new Setting('follow_up_task');
        $gaps = new Setting('gaps');
        $barriers = new Setting('barriers');

        $familyFollowUp = new FamilyFollowUps($followUp['family_id']);
        $followUps = $familyFollowUp->getList();

        $rowClass = 'odd';
        if (isset($_REQUEST['rowClass'])) {
            $rowClass = $_REQUEST['rowClass'];
        }

        $followUpType = 'Family';

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

        $familyFollowUp = new FamilyFollowUps($followUp['family_id']);

        $rowClass = 'odd';
        if (isset($_REQUEST['rowClass'])) {
            $rowClass = $_REQUEST['rowClass'];
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
