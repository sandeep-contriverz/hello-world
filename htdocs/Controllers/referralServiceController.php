<?php

namespace Hmg\Controllers;

use Hmg\Models\Setting;
use Hmg\Models\ReferralServices;

class ReferralServiceController
{

    public function __construct()
    {
        //echo "<pre>";print_r($_POST);die;
        if (isset($_REQUEST['manage_referral']) && $_REQUEST['manage_referral']) {
            $referred_to = new Setting('referred_to');
            $this->displayManageReferral($referred_to);
            exit;
        }

        if (isset($_REQUEST['clearFilters'])) {
            unset($_SESSION['referral-services']['filters'], $_REQUEST['filters']);
        }
        if (isset($_REQUEST['filters'])) {
            $_SESSION['referral-services']['filters'] = $_REQUEST['filters'];
        }


        if (isset($_REQUEST['save']) && $_REQUEST['save'] && isset($_REQUEST['json']) && $_REQUEST['json']) {
           
            $data = json_decode($_REQUEST['json']);
            $setting = new Setting();
            $setting->updateSettingName($data->id, $data->name);
            if(isset($data->org_name) && !empty($data->org_name))
            $setting->updateSettingName($data->org_id, $data->org_name);
	       
             $referralId = $data->id;
		
            $newReferralServices = array();
            if (is_array($data->services)) {
                foreach ($data->services as $serviceObj) {
                    $newReferralServices[] = array(
                        'id'       => $serviceObj->id,
                        'name'     => $serviceObj->name,
                        'disabled' => $serviceObj->disabled,
                        'org_id'   => $data->org_id,
                    );
                }
            }
		
            $referralServices = new ReferralServices($referralId, $newReferralServices);
            $saved = $referralServices->save();

            $jsonEncodedServices = json_encode($referralServices->services);
            header('Content-Type: aplication/json');
            echo $jsonEncodedServices;
        }
		/*** Referrals/Services ***/
		 else if (isset($_REQUEST['refsave']) && $_REQUEST['refsave'] && isset($_REQUEST['json']) && $_REQUEST['json']) {
            $data = json_decode($_REQUEST['json']);
			$org_id   = $data->id;
			$siteName = $data->siteName;
            $setting  = new Setting();
            if(isset($data->org_name_id) && !empty($data->org_name_id)){
                $setting->updateSettingName($data->org_name_id, $data->name);
            }
            if(isset($data->siteID) && !empty($data->siteID)){
                $setting->updateSettingName($data->siteID, $data->siteName);
			}
            $referralId = $data->id;  //organization_sites_id
		
            $newReferralServices = array();
            if (is_array($data->services)) {
                foreach ($data->services as $serviceObj) {
                    $newReferralServices[] = array(
                        'id'       => $serviceObj->id,
                        'site_id'  => 0, //no need to store now
                        'name'     => $serviceObj->sr_name,
                        'disabled' => isset($serviceObj->disabled) ? $serviceObj->disabled : 0,
                        'org_id'   => $data->id,
                    );
                }
            }
		    //echo "<pre>";print_r($newReferralServices);die;
            $referralServices = new ReferralServices($referralId, $newReferralServices);
            $saved = $referralServices->save();

            $jsonEncodedServices = json_encode($saved);
            header('Content-Type: aplication/json');
            echo $jsonEncodedServices;
        }
		/*** End***/
		/**** Informational referrals*******/
		
		else if (isset($_REQUEST['infosave']) && $_REQUEST['infosave'] && isset($_REQUEST['json']) && $_REQUEST['json']) {
            $data = json_decode($_REQUEST['json']);
			$org_id   = $data->org_name_id;
			$siteName = $data->name;
            $setting  = new Setting();
            if(isset($data->org_name_id) && !empty($data->org_name_id)){
                $setting->updateSettingName($data->org_name_id, $data->name);
            }
          
            $newReferralServices = array();
			
            if (is_array($data->services)) {
                foreach ($data->services as $serviceObj) {
                    $newReferralServices[] = array(
                        'info_referred_id'       => $serviceObj->id,
                        'service_id'  => $serviceObj->service_id, //no need to store now
                        'name'     => $serviceObj->sr_name,
                        'disabled' => isset($serviceObj->disabled) ? $serviceObj->disabled : 0,
                        'org_id'   => $org_id,
                    );
                }
            }
		   
            $referralServices = new ReferralServices($org_id, $newReferralServices);
            $saved = $referralServices->infosave();

            $jsonEncodedServices = json_encode($saved);
            header('Content-Type: aplication/json');
            echo $jsonEncodedServices;
        }
		else if (isset($_REQUEST['update']) && isset($_REQUEST['json'])) {
            $data = json_decode($_REQUEST['json']);
            $referralServices = new ReferralServices($data->referralId);
            $updated = $referralServices->updateServiceDisabled($data->serviceId, $data->disabled);

            header('Content-Type: aplication/json');
            echo '{ "success" : ' . $updated . ' }';
        } else if (isset($_REQUEST['referralId']) && is_numeric($_REQUEST['referralId'])) {
            $referralServices = new ReferralServices($_REQUEST['referralId']);
			if( isset( $_REQUEST['referredToType'] ) && !empty( $_REQUEST['referredToType'] ) ){
				$referralServices->referredToType = $_REQUEST['referredToType'];
			}
            $referralServices->setServices();
            $jsonEncodedServices = json_encode($referralServices->services);
            header('Content-Type: aplication/json');
            echo $jsonEncodedServices;
        } 
		elseif(isset($_REQUEST['get-select'])){
	      $referralServices = new ReferralServices();
	      $select = $referralServices->displayReferralSelectByD(
                'services[]',
                (isset($_REQUEST['selected']) ? $_REQUEST['selected'] : ''), '',
                $label    = ' ',
                $tabIndex = '',
                $required = false,
                $addtlclasses = null,
                $filtered = true,
                $allowDisableSelect = true
            );
			header('Content-Type: text/html');
            echo $select;
            exit;
        
		}
		
		else if (isset($_REQUEST['print'])) {
            $referralServices = new ReferralServices();
            $filters = array();
            if (isset($_SESSION['referral-services']['filters'])) {
                $filters = $_SESSION['referral-services']['filters'];
            }
            $this->printReferralServices($referralServices->getList($filters));
        } else if (isset($_REQUEST['export'])) {
            $referralServices = new ReferralServices();
            $filters = array();
            if (isset($_SESSION['referral-services']['filters'])) {
                $filters = $_SESSION['referral-services']['filters'];
            }
            $this->exportReferralServices($referralServices->getList($filters));
        } 
        else if (isset($_REQUEST['search']) && $_REQUEST['action']=='referral-org') {
            $referralServices = new ReferralServices();
            $this->displayReferralsNameJson($referralServices->getNamesAndIdsOrg($_REQUEST['search']));
        }
        else if (isset($_REQUEST['search'])) {
            $referralServices = new ReferralServices();
            $this->displayReferralsNameJson($referralServices->getNamesAndIds($_REQUEST['search']));
        } else {
            $referralServices = new ReferralServices();
            $filters = array();
            if (isset($_SESSION['referral-services']['filters'])) {
                $filters = $_SESSION['referral-services']['filters'];
            }
            $this->displayReferralServices($referralServices->getList($filters));
        }

    }

    public function displayReferralServices($referralServices)
    {

        $referred_to         = new Setting('referred_to');
        $referred_to_service = new Setting('referred_to_service');

		
		
        include(VIEW_PATH . '/adminnav.phtml');
        ob_start();
        include(VIEW_PATH . '/referral-services.phtml');
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

    public function printReferralServices($referralServices)
    {

        $referred_to         = new Setting('referred_to');
        $referred_to_service = new Setting('referred_to_service');

        ob_start();
        include(VIEW_PATH . '/referral-services-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportReferralServices($referralServices)
    {

        $referred_to         = new Setting('referred_to');
        $referred_to_service = new Setting('referred_to_service');

        ob_start();
        include(VIEW_PATH . '/referral-services-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-referral-services-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }

    public function displayManageReferral($referrals)
    {
        //Get referral agency names
        $organizations = new Setting('referred_to');
        //echo "<pre>";print_r($organizations);die;
        include(VIEW_PATH . '/adminnav.phtml');
        ob_start();
        include(VIEW_PATH . '/settings-referral-form.phtml');
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

    public function displayReferralsNameJson($list)
    {
        if (is_array($list)) {
            $json = json_encode($list);
            header('Content-Type: application/json');
            echo $json;
        }
        exit;
    }
}
