<?php

namespace Hmg\Controllers;

use Hmg\Models\ChildReferrals;
use Hmg\Models\ChildReferral;
use Hmg\Models\ChildFollowUp;
use Hmg\Models\ReferralServices;
use Hmg\Models\Family;
use Hmg\Models\Setting;

class ChildReferralController
{
    public function __construct()
    {

        if (isset($_REQUEST['save']) && isset($_REQUEST['child_id']) && is_numeric($_REQUEST['child_id'])) {
            unset($_REQUEST['action'], $_REQUEST['save']);
            $childReferral = new ChildReferral($_REQUEST);
            $saved = $childReferral->save();
            $childFollowUp = new ChildFollowUp(array(
                'child_id'          => $childReferral->referral['child_id'],
                'hmg_worker'        => $childReferral->referral['hmg_worker'],
                'referred_to_id'    => $childReferral->referral['referred_to_id'],
                'referred_to_type'    => $childReferral->referral['referred_to_type'],
                'service_id'        => $childReferral->referral['service_id'],
                'follow_up_task_id' => '1904',
                'referral_date'     => date('Y-m-d'),
                'follow_up_date'    => date('Y-m-d', strtotime('+1 week')),
                'result'            => ''
            ));
            $childFollowUp->save();
            $this->displayChildReferralList($childReferral->referral);
        } else if (isset($_REQUEST['save']) && isset($_REQUEST['referral']) && is_array($_REQUEST['referral'])) {
            unset($_REQUEST['action'], $_REQUEST['save']);
            list($id, $referral) = each($_REQUEST['referral']);
            $referral['id'] = $id;
            if (isset($referral['referral_date'])) {
                $referral['referral_date'] = date('Y-m-d', strtotime($referral['referral_date']));
            }
            $childReferral = new ChildReferral($referral);
            $saved = $childReferral->save();
            $this->displayChildReferralList($childReferral->referral);
        } else if (isset($_REQUEST['delete']) && isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            unset($_REQUEST['action'], $_REQUEST['delete']);
            $childReferral = new ChildReferral($_REQUEST);
            $childReferral->setById($_REQUEST['id']);
            $deleted = $childReferral->delete();
            $this->displayChildReferralList($childReferral->referral);
        } else if (isset($_REQUEST['child_id']) && is_numeric($_REQUEST['child_id'])) {
            $childReferrals = new ChildReferrals($_REQUEST['child_id']);
            $referrals = $childReferrals->getList();
            $this->displayReferralsJson($referrals);
        } else if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) && isset($_REQUEST['get-form'])) {
            $childReferral = new ChildReferral();
            $childReferral->setById($_REQUEST['id']);
            $this->displayReferralForm($childReferral->referral);
        } else if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) && isset($_REQUEST['get-view'])) {
            $childReferral = new ChildReferral();
            $childReferral->setById($_REQUEST['id']);
            $this->displayReferralView($childReferral->referral);
        } else {
            //header("Location: index.php");
        }
    }

    public function displayReferralsJson($referrals)
    {
        $json = json_encode($referrals);
        header("Content-type: application/json");
        echo $json;
    }

    public function displayReferralForm($referral)
    {

        $setting          = new Setting();
        $referredTo       = new Setting('referred_to');
        $issues           = new Setting('issues');
        $service          = new Setting('service');
        $gaps             = new Setting('gaps');
        $barriers         = new Setting('barriers');
        $referral_outcome = new Setting('referral_outcomes');

        $familyReferral = new ChildReferrals($referral['child_id']);
        $family = new Family();

        $referralServices = new ReferralServices($referral['referred_to_id']);
        $referralServices->referredToType = $referral['referred_to_type'];
        $referralServices->setServices();

        $rowclass = 'odd';
        if (isset($_REQUEST['rowclass'])) {
            $rowclass = $_REQUEST['rowclass'];
        }

        if ($referral['id']) {
            ob_start();
            include(VIEW_PATH . "/edit-referral.phtml");
            $html = ob_get_contents();
            ob_end_clean();
            header('Content-type: text/html');
            echo $html;
        }

    }

    public function displayChildReferralList($referral)
    {

        $referredTo = new Setting('referred_to');
        $issues = new Setting('issues');
        $service = new Setting('service');
        $gaps = new Setting('gaps');
        $barriers = new Setting('barriers');
        $referral_outcome = new Setting('referral_outcomes');

        $familyReferral = new ChildReferrals($referral['child_id']);
        $childReferral = new ChildReferrals($referral['child_id']);
        $referrals = $childReferral->getList();

        $rowclass = 'odd';
        if (isset($_REQUEST['rowclass'])) {
            $rowclass = $_REQUEST['rowclass'];
        }

        $referralType = 'Child';
        $setting = new Setting(); //191016
        ob_start();
        include(VIEW_PATH . "/family-referrals.phtml");
        $html = ob_get_contents();
        ob_end_clean();
        header('Content-type: text/html');
        echo $html;
    }

    public function displayReferralView($referral)
    {

        $setting = new Setting();
        $referredTo = new Setting('referred_to');
        $issues = new Setting('issues');
        $service = new Setting('service');
        $gaps = new Setting('gaps');
        $barriers = new Setting('barriers');

        $familyReferral = new ChildReferrals($referral['child_id']);

        $rowclass = 'odd';
        if (isset($_REQUEST['rowclass'])) {
            $rowclass = $_REQUEST['rowclass'];
        }

        ob_start();
        include(VIEW_PATH . "/view-referral.phtml");
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
