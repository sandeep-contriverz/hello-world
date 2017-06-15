<?php

namespace Hmg\Controllers;

use Hmg\Models\FamilyReferrals;
use Hmg\Models\FamilyReferral;
use Hmg\Models\FamilyFollowUp;
use Hmg\Models\ReferralServices;
use Hmg\Models\Family;
use Hmg\Models\Setting;
use Hmg\Models\User;

class FamilyReferralController
{
    public function __construct()
    {

        if (isset($_REQUEST['save']) && isset($_REQUEST['family_id']) && is_numeric($_REQUEST['family_id'])) {
            unset($_REQUEST['action'], $_REQUEST['save']);
            $familyReferral = new FamilyReferral($_REQUEST);
            $saved = $familyReferral->save();
            $familyFollowUp = new FamilyFollowUp(array(
                'family_id'         => $familyReferral->referral['family_id'],
                'hmg_worker'        => $familyReferral->referral['hmg_worker'],
                'referred_to_id'    => $familyReferral->referral['referred_to_id'],
                'referred_to_type'  => $familyReferral->referral['referred_to_type'],
                'service_id'        => $familyReferral->referral['service_id'],
                'follow_up_task_id' => '1904',
                'referral_date'     => date('Y-m-d'),
                'follow_up_date'    => date('Y-m-d', strtotime('+1 week')),
                'result'            => ''
            ));
            $familyFollowUp->save();
            $this->displayFamilyReferralList($familyReferral->referral);
        } else if (isset($_REQUEST['save']) && isset($_REQUEST['referral']) && is_array($_REQUEST['referral'])) {
            unset($_REQUEST['action'], $_REQUEST['save']);
            list($id, $referral) = each($_REQUEST['referral']);
            $referral['id'] = $id;
            if (isset($referral['referral_date'])) {
                $referral['referral_date'] = date('Y-m-d', strtotime($referral['referral_date']));
            }
            $familyReferral = new FamilyReferral($referral);
            $saved = $familyReferral->save();
            $this->displayFamilyReferralList($familyReferral->referral);
        } else if (isset($_REQUEST['delete']) && isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            unset($_REQUEST['action'], $_REQUEST['delete']);
            $familyReferral = new FamilyReferral($_REQUEST);
            $familyReferral->setById($_REQUEST['id']);
            $deleted = $familyReferral->delete();
            $this->displayFamilyReferralList($familyReferral->referral);
        } else if (isset($_REQUEST['family_id']) && is_numeric($_REQUEST['family_id'])) {
            $familyReferrals = new FamilyReferrals($_REQUEST['family_id']);
            $referrals = $familyReferrals->getList();
            $this->displayReferralsJson($referrals);
        } else if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) && isset($_REQUEST['get-form'])) {
            $familyReferral = new FamilyReferral();
            $familyReferral->setById($_REQUEST['id']);
            $this->displayReferralForm($familyReferral->referral);
        } else if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) && isset($_REQUEST['get-view'])) {
            $familyReferral = new FamilyReferral();
            $familyReferral->setById($_REQUEST['id']);
            $this->displayReferralView($familyReferral->referral);
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

        $familyReferral = new FamilyReferrals($referral['family_id']);
        $family = new Family();

        $referralServices = new ReferralServices($referral['referred_to_id']);
        $referralServices->referredToType = $referral['referred_to_type'];
        $referralServices->setServices();
        $rowClass = 'odd';
        if (isset($_REQUEST['rowClass'])) {
            $rowClass = $_REQUEST['rowClass'];
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

    public function displayFamilyReferralList($referral)
    {

        $referredTo = new Setting('referred_to');
        $issues = new Setting('issues');
        $service = new Setting('service');
        $gaps = new Setting('gaps');
        $barriers = new Setting('barriers');
        $referral_outcome = new Setting('referral_outcomes');

        $familyReferral = new FamilyReferrals($referral['family_id']);
        $referrals = $familyReferral->getList();

        $rowClass = 'odd';
        if (isset($_REQUEST['rowClass'])) {
            $rowClass = $_REQUEST['rowClass'];
        }

        $referralType = 'Family';
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
        $referral_outcome = new Setting('referral_outcomes');

        $familyReferral = new FamilyReferrals($referral['family_id']);


        $rowClass = 'odd';
        if (isset($_REQUEST['rowClass'])) {
            $rowClass = $_REQUEST['rowClass'];
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
