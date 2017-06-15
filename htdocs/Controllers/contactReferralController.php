<?php

namespace Hmg\Controllers;

use Hmg\Models\ContactReferrals;
use Hmg\Models\ContactReferral;
use Hmg\Models\ContactFollowUp;
use Hmg\Models\ReferralServices;
use Hmg\Models\Organization;
use Hmg\Models\Setting;
use Hmg\Models\User;

class ContactReferralController
{
    public function __construct()
    {

        if (isset($_REQUEST['save']) && isset($_REQUEST['contact_id']) && is_numeric($_REQUEST['contact_id'])) {
            unset($_REQUEST['action'], $_REQUEST['save']);
            $contactReferral = new ContactReferral($_REQUEST);
            $saved = $contactReferral->save();
            $contactFollowUp = new ContactFollowUp(array(
                'contact_id'        => $contactReferral->referral['contact_id'],
                'hmg_worker'        => $contactReferral->referral['hmg_worker'],
                'referred_to_id'    => $contactReferral->referral['referred_to_id'],
                'referred_to_type'    => $contactReferral->referral['referred_to_type'],
                'service_id'        => $contactReferral->referral['service_id'],
                'follow_up_task_id' => '5992',
                'referral_date'     => date('Y-m-d'),
                'follow_up_date'    => date('Y-m-d', strtotime('+1 week')),
                'result'            => ''
            ));
            $contactFollowUp->save();
            $this->displayContactReferralList($contactReferral->referral);
        } else if (isset($_REQUEST['save']) && isset($_REQUEST['referral']) && is_array($_REQUEST['referral'])) {
            unset($_REQUEST['action'], $_REQUEST['save']);
            list($id, $referral) = each($_REQUEST['referral']);
            $referral['id'] = $id;
            if (isset($referral['referral_date'])) {
                $referral['referral_date'] = date('Y-m-d', strtotime($referral['referral_date']));
            }
            $contactReferral = new ContactReferral($referral);
            $saved = $contactReferral->save();
            $this->displayContactReferralList($contactReferral->referral);
        } else if (isset($_REQUEST['delete']) && isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            unset($_REQUEST['action'], $_REQUEST['delete']);
            $contactReferral = new ContactReferral($_REQUEST);
            $contactReferral->setById($_REQUEST['id']);
            $deleted = $contactReferral->delete();
            $this->displayContactReferralList($contactReferral->referral);
        } else if (isset($_REQUEST['contact_id']) && is_numeric($_REQUEST['contact_id'])) {
            $contactReferrals = new ContactReferrals($_REQUEST['contact_id']);
            $referrals = $contactReferrals->getList();
            $this->displayReferralsJson($referrals);
        } else if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) && isset($_REQUEST['get-form'])) {
            $contactReferral = new ContactReferral();
            $contactReferral->setById($_REQUEST['id']);
            $this->displayReferralForm($contactReferral->referral);
        } else if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) && isset($_REQUEST['get-view'])) {
            $contactReferral = new ContactReferral();
            $contactReferral->setById($_REQUEST['id']);
            $this->displayReferralView($contactReferral->referral);
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

        $organizationReferral = new ContactReferrals($referral['contact_id']);
        $organization = new Organization();

        $referralServices = new ReferralServices($referral['referred_to_id']);
        $referralServices->referredToType = $referral['referred_to_type'];
        $referralServices->setServices();

        $rowClass = 'odd';
        if (isset($_REQUEST['rowClass'])) {
            $rowClass = $_REQUEST['rowClass'];
        }

        if ($referral['id']) {
            ob_start();
            include(VIEW_PATH . "/edit-contact-referral.phtml");
            $html = ob_get_contents();
            ob_end_clean();
            header('Content-type: text/html');
            echo $html;
        }

    }

    public function displayContactReferralList($referral)
    {

        $referredTo = new Setting('referred_to');
        $issues = new Setting('issues');
        $service = new Setting('service');
        $gaps = new Setting('gaps');
        $barriers = new Setting('barriers');
        $referral_outcome = new Setting('referral_outcomes');

        $organizationReferral = new ContactReferrals($referral['contact_id']);
        $contactReferral = new ContactReferrals($referral['contact_id']);
        $referrals = $contactReferral->getList();

        $rowclass = 'odd';
        if (isset($_REQUEST['rowclass'])) {
            $rowclass = $_REQUEST['rowclass'];
        }

        $referralType = 'Provider';//Change "Contact to Provider" Date: 15/02/2017
        $setting = new Setting(); //191016
        ob_start();
        include(VIEW_PATH . "/contact-referrals.phtml");
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

        $organizationReferral = new ContactReferrals($referral['contact_id']);

        $rowClass = 'odd';
        if (isset($_REQUEST['rowClass'])) {
            $rowClass = $_REQUEST['rowClass'];
        }
        $userObj = new User();
        $userObj->setById((int)$referral['hmg_worker']);
        $referral['hmg_worker'] = !empty($userObj->user['hmg_worker']) ? $userObj->user['hmg_worker'] : $referral['hmg_worker'];
        ob_start();
        
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
