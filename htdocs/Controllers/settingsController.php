<?php

namespace Hmg\Controllers;

use Hmg\Models\Setting;
use Hmg\Models\SchoolDistrict;
use Hmg\Models\SchoolDistrictZipcodes;
use Hmg\Models\CountyZipcodes;
use Hmg\Models\RegionCounties;
use Hmg\Models\ReferralServices;


class SettingsController
{

    public function __construct()
    {
        $schoolDistrict         = new SchoolDistrict();
        $schoolDistrictZipcodes = new SchoolDistrictZipcodes();
        $countyZipcodes         = new CountyZipcodes();
        $regionCounties         = new RegionCounties();
		
		$referralServices      =  new ReferralServices();
		
        $how_heard             = new Setting('how_heard_category');
        $relationship          = new Setting('relationships');
        $race                  = new Setting('race');
        $child_services        = new Setting('child_services');
        $volunteer_heard       = new Setting('volunteer_heard');
        $provider_role         = new Setting('provider_role');
        $family_permission   =  new Setting('permission');
        $ethnicity             = new Setting('ethnicity');
        $follow_up_task        = new Setting('follow_up_task');
        $call_reason           = new Setting('call_reason');
        $who_called            = new Setting('who_called');
        $language              = new Setting('language');
        $gaps                  = new Setting('gaps');
        $barriers              = new Setting('barriers');
        $issues                = new Setting('issues');
        $follow_up_task_result = new Setting('follow_up_task_result');
        $referral_outcome      = new Setting('referral_outcomes');
        $reason_file_closed    = new Setting('file_closed_reason');
        $city                  = new Setting('city');
        $status                = new Setting('status');
        $cc_level              = new Setting('cc_level');
        $health_insurance      = new Setting('health_insurance');
        $best_hours            = new Setting('best_hours');
        $child_issues          = new Setting('child_issues');
        $asq_preference        = new Setting('asq_preference');
        $permission_fax_type   = new Setting('permission_fax_type');
        $referred_to_service   = new Setting('referred_to_service');
        $screening_type        = new Setting('screening_type');
        $screening_type_family        = new Setting('screening_type_family');
        $screening_interval    = new Setting('screening_interval', true);
        $screening_interval_family    = new Setting('screening_interval_family', true);
        $score                 = new Setting('score');
        $county                = new Setting('county');
        $region                = new Setting('region');
        $volunteering_type     = new Setting('volunteering_type'); //211016
        $region_api            = new Setting('region_api'); //071116 Added for ASQ Sync API

        $event_type            = new Setting('event_type');
        $mode_of_contact       = new Setting('mode_of_contact');
        $organization_status   = new Setting('organization_status');
        $organization_type     = new Setting('organization_type');
        $outreach_type         = new Setting('outreach_type');
        $partnership_level     = new Setting('partnership_level');
        $resource_database     = new Setting('resource_database');
        $time_of_day           = new Setting('time_of_day');
        $type_of_contact       = new Setting('type_of_contact'); // type_of_contact worked as a type of provider from 15/02/2017
        //$region_counties       = new Setting('region_counties');
        $zipcodes              = new Setting('zipcodes');
        $gender                = new Setting('gender');
        $outreach_follow_up_task   = new Setting('outreach_follow_up_task');
        $contact_follow_up_task    = new Setting('contact_follow_up_task');
        $school_districts          = $schoolDistrict->getAll(); //011216
        $school_districts_zipcodes = $schoolDistrictZipcodes->getAll(); //011216
        $county_zipcodes           = $countyZipcodes->getAll(); //011216
        $point_of_entry            = new Setting('point_of_entry');
		
        $organization_name         = new Setting('referred_to');
		//print_r($organization_name);
        //$referred_to         = new Setting('referred_to');

        //$referred_to_service = new Setting('referred_to_service');
		
		$referred_to         = new Setting('referred_to');
		
        $referredtoservice =  new Setting('referred_to_service');
        $harmRating =  new Setting('harm_rating');
		
        $region_counties    = $regionCounties->getAll();
				
		/*$ref_service   = $referralServices ->getReferrals_Services();*/
		$informationl_referrals   = $referralServices ->getInformationReferrals();
		
		$settingLists  = array(
            'Families' => array(
                'Barriers'                 => $barriers,
                'Best Time to Contact'     => $best_hours,
                'Call Reason'              => $call_reason,
                'Caller Ethnicity'         => $ethnicity,
                'Caller Race'              => $race,
                'Care Coordination Level'  => $cc_level,
                'Child Presenting Issue'   => $child_issues,
                'Existing/Prior Resources' => $child_services,
                'Family Status'            => $status,
                'Follow Up Tasks'          => $follow_up_task,
                'Follow Up Task Results'   => $follow_up_task_result,
                'Gaps'                     => $gaps,
                'Health Insurance'         => $health_insurance,
                //'How Family Heard'         => $family_heard,
                'How Heard Category' => $how_heard,
                'Point of Entry'           => $point_of_entry,
                'Permission'               => $family_permission,
                'Reasons for Closing File' => $reason_file_closed,
                'Relationship to Child'    => $relationship,
                
                
                'Screening Interval(Child)'       => $screening_interval,
                'Screening Interval(Family)'       => $screening_interval_family,
                'Screening Score'          => $score,
                'Screening Preference'     => $asq_preference,
                'Screening Type(Child)'           => $screening_type,
                'Screening Type(Family)'           => $screening_type_family,
                'Self Harm Rating'           => $harmRating,
                
                'Who Called'               => $who_called
				
            ),
            'Organizations' => array(
                'Event Type'               => $event_type,
                'Mode of Initial Contact'  => $mode_of_contact,
                'Organization Name'        => $organization_name,
                'Organization Status'      => $organization_status,
                'Organization Type'        => $organization_type,
                'Outreach Follow-up Task'  => $outreach_follow_up_task,
                'Outreach Type'            => $outreach_type,
                'Partnership Level'        => $partnership_level,
                'Provider Role'            => $provider_role,
                'Resource Database'        => $resource_database,
                'Service Terms'            => $referred_to_service,
                'Type of Provider'          => $type_of_contact,
                'Time of Day'              => $time_of_day,
            ),
            'Volunteers'    => array(
                'How Volunteer Heard'      => $volunteer_heard,
                'Volunteering Type'        => $volunteering_type, //211016
            ),
            'General Items'         => array(
                'City'                     => $city,
                'County'                   => $county,
                'County Zipcodes'          => $county_zipcodes,
                'Gender'                   => $gender,
				'Informational Referral'   => $informationl_referrals,
                'Referral Issues'          => $issues,
                'Referral Outcomes'        => $referral_outcome,
                'Regions/Counties'         => $region_counties,
                'Regions for the API'      => $region_api, //071116 Added for ASQ Sync API
                'Regions within the State' => $region,
                'School Districts'         => $school_districts,
                'School Districts Zipcodes' => $school_districts_zipcodes
            ),
						
        );
        /*$settingLists  = array(
            'General Items'         => array(
                'Referrals/Services'        =>  $ref_service
            )
        );*/
        //echo "<pre>";print_r($ref_service);die;
    	//ksort($settingLists);
        //echo "<pre>";print_r($settingLists);die;

        if (isset($_REQUEST['search']) && isset($_REQUEST['type'])) 
		{
            $setting = new Setting($_REQUEST['type']);
            $settingData = $setting->getNamesAndIds($_REQUEST['search']);
            $this->displaySettingsNameJson($settingData);
        } elseif(isset($_REQUEST['manage']) && !empty($_REQUEST['manage'])) {
            if(isset($settingLists[trim($_REQUEST['manage'])]))
				
			    
               $this->displaySettingsManagement($settingLists[trim($_REQUEST['manage'])]);
            else
                $this->displaySettings($settingLists);
        } 
		
		
		
		else {
            $this->displaySettings($settingLists);
        }
    }

    public function displaySettings($settingLists)
    {
		
		
             
        include(VIEW_PATH . '/adminnav.phtml');
        ob_start();
        include(VIEW_PATH . '/settings.phtml');
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

    public function displaySettingsManagement($settingLists)
    {
        $cities = new Setting('city');
		
			
        $settingLists = array(
            trim($_REQUEST['manage']) => $settingLists
        );
//        echo "<pre>";print_r($settingLists);die;
        include(VIEW_PATH . '/adminnav.phtml');
        ob_start();
        include(VIEW_PATH . '/settings-management.phtml');
		
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

    public function displaySettingsNameJson($list)
    {
        if (is_array($list)) {
            $json = json_encode($list);
            header('Content-Type: application/json');
            echo $json;
        }
        exit;
    }
}
