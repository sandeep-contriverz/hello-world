<?php
namespace Hmg\Controllers;

use Hmg\Models\OrganizationFollowUpss;

use Hmg\Models\Setting;
use Hmg\Models\Organization;
use Hmg\Models\Organizations;

use Hmg\Controllers\OrganizationController;
use Hmg\Helpers\SessionHelper as FilterHelper;

class OrganizationFollowUpsController
{
    public function __construct()
    {

        $_SESSION['list-type'] = 'Organization-follow-ups-list';
		
		$organizations = new Organizations();

        $OrganizationFollowUpss = new OrganizationFollowUpss(0);

		
        $sort    = '';
        $field   = '';
        $sorts   = array();
        $setting = new Setting();

        if (!empty($_REQUEST['field'])  && !empty($_REQUEST['sort'])) {
            $sort  = $_REQUEST['sort'];
            $field = $_REQUEST['field'];
			
            $sorts[$field] = $sort;
            if($field != 'site')
                $sorts['site'] = 'asc';
            
            $_SESSION['org-follow-up-sorts'] = $sorts; // store sorts
            $OrganizationFollowUpss->set('_sorts', $sorts);
            $OrganizationFollowUpss->sorts =  array($field , $sort);
        }
        $search = '';
		
        if (isset($_REQUEST['clearFilters'])) {
            unset(
                $_REQUEST['filters'],
                $_SESSION['OrganizationFollowUpss']['filters'],
                $_SESSION['totalOrgFollowUps'],
                $_SESSION['org-follow-up-sorts'], $_SESSION['OrganizationFollowUpss']
            );
        }
        if (isset($_REQUEST['filters'])) {
			
			if (isset($_REQUEST['filters']['city']) && (is_numeric($_REQUEST['filters']['city']) || is_array($_REQUEST['filters']['city']))) {
                $setting = new Setting();
                // Search is using city name and not the id coming from advanced form
                if (is_array($_REQUEST['filters']['city'])) {
                    $cityNames = array();
                    foreach ($_REQUEST['filters']['city'] as $cityId) {
                        $cityNames[] = $setting->getSettingById($cityId);
                    }
                    $_REQUEST['filters']['city'] = $cityNames;
                } else {
                    $_REQUEST['filters']['city'] = $setting->getSettingById($_REQUEST['filters']['city']);
                }
            }
            if (isset($_REQUEST['filters']['county']) && (is_numeric($_REQUEST['filters']['county']) || is_array($_REQUEST['filters']['county']))) {
                $setting = new Setting();
                // Search is using county name and not the id coming from advanced form
                if (is_array($_REQUEST['filters']['county'])) {
                    $countyNames = array();
                    foreach ($_REQUEST['filters']['county'] as $countyId) {
                        $countyNames[] = $setting->getSettingById($countyId);
                    }
                    $_REQUEST['filters']['county'] = $countyNames;
                } else {
                    $_REQUEST['filters']['county'] = $setting->getSettingById($_REQUEST['filters']['county']);
                }
            }
            $_SESSION['OrganizationFollowUpss']['search'] = (isset($_REQUEST['search']) ? $_REQUEST['search'] : '');
            
			$OrganizationFollowUpss->set('_search', $_SESSION['OrganizationFollowUpss']['search']);
			
            $_SESSION['OrganizationFollowUpss']['filters'] = $_REQUEST['filters'];
			
            $OrganizationFollowUpss->set('_filters', $_REQUEST['filters']);
			
        } else if (isset($_SESSION['OrganizationFollowUpss']['filters'])) {
            $search = (isset($_SESSION['OrganizationFollowUpss']['search']) ? $_SESSION['OrganizationFollowUpss']['search'] : '');
            $OrganizationFollowUpss->set('_search', ($search));
            $OrganizationFollowUpss->set('_filters', $_SESSION['OrganizationFollowUpss']['filters']);
            if (isset($_SESSION['org-follow-up-sorts'])) {
                $sorts = $_SESSION['org-follow-up-sorts'];
                $OrganizationFollowUpss->set('_sorts', $sorts);
            }
            
        } else {
            $_SESSION['OrganizationFollowUpss']['filters'] = array(
                'hmg_worker'         => $_SESSION['user']['hmg_worker'],
                'language_id'        => '',
                'status'             => '',
                'follow_up_task_id'  => '',
                'start_date'         => '',
                'end_date'           => '',
				'date'               => '',
                'done'               => '0'
            );
            $OrganizationFollowUpss->set('_filters', $_SESSION['OrganizationFollowUpss']['filters']);
        }

        $totalOrgFollowUps = $OrganizationFollowUpss->getCount($setting);

        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page']) && $_REQUEST['page']) {
            $OrganizationFollowUpss->set('_start', ($_REQUEST['page'] - 1) * 50);
        } else {
            $OrganizationFollowUpss->set('_start', 0);
        }
        if (isset($_REQUEST['field']) && $_REQUEST['field'] && isset($_REQUEST['sort']) && $_REQUEST['sort']) {
            $sorts[$_REQUEST['field']] =     $_REQUEST['sort'];
            $OrganizationFollowUpss->set('_sorts', $sorts);
        }

        $message = isset($_REQUEST['message']) ? $_REQUEST['message'] : '';

        //$filters = (isset($_SESSION['OrganizationFollowUpss']['filters']) ? $_SESSION['OrganizationFollowUpss']['filters'] : array());

        if (isset($_REQUEST['print'])) {
            $OrganizationFollowUpss->set('_limit', 0); // remove limits
            $this->printFollowUpsList($OrganizationFollowUpss->getList(true), $totalOrgFollowUps, $page, $sorts, $message);
        } else {
            $this->displayFollowUpsList($OrganizationFollowUpss->getList(true), $totalOrgFollowUps, $page, $sorts, $message);
        }
				
    }

    public function displayFollowUpsList($OrganizationFollowUpss, $totalFollowUps, $page, $sorts, $message)
    {
        $totalFollowUps = count($OrganizationFollowUpss);
        $filterHelper = new FilterHelper();
        //$filters = $filterHelper->getFollowUpFilters();
        $filters = $filterHelper->getOrganizationFollowUpssFilters();
          

         //echo "<pre>";print_r($filters);die;
        
        $organization  = new Organization();

        $setting       = new Setting();
        $relationships = new Setting('relationships');
        $language      = new Setting('language');
        $familyHeard   = new Setting('how_heard_category');
        $callReason    = new Setting('call_reason');
        $race          = new Setting('race');
        $status        = new Setting('organization_status');
        $ethnicity     = new Setting('ethnicity');

        
        $followUpTask  = new Setting('outreach_follow_up_task','','contact_follow_up_task');
       


		$organization_name  = new Setting('referred_to');
		$status        = new Setting('organization_status');
        $regions       = new Setting('region');
        $type          = new Setting('organization_type');
        $county        = new Setting('county');
        $partnership   = new Setting('partnership_level');
        $type_of_contact      = new Setting('type_of_contact');
        $resource_database    = new Setting('resource_database');
        $service_terms        = new Setting('referred_to_service');

        

        include(VIEW_PATH . '/adminnav.phtml');
 
        $numFollowUps = count($OrganizationFollowUpss);
        $numPages = ceil($numFollowUps / 50);
        $pageNumber = $page;
        $firstRecord = (($pageNumber -1) * 50) ;
        $lastRecord = $firstRecord + 49;
        if($lastRecord > $numFollowUps - 1)
            $lastRecord = $numFollowUps - 1;

        if($lastRecord == $firstRecord)
            $OrganizationFollowUpss = array($OrganizationFollowUpss[$lastRecord]); 
        else
        $OrganizationFollowUpss = array_slice($OrganizationFollowUpss , $firstRecord , $lastRecord);       
       

        ob_start();
        include(VIEW_PATH . '/organization-follow-ups-list.phtml');
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

    public function printFollowUpsList($followUps, $totalFollowUps, $page, $sorts, $message)
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getOrganizationFollowUpssFilters();
        //echo "<pre>";print_r($OrganizationFollowUpss);die;
        $organization  = new Organization();

        $setting       = new Setting();
        $relationships = new Setting('relationships');
        $language      = new Setting('language');
        $familyHeard   = new Setting('how_heard_category');
        $callReason    = new Setting('call_reason');
        $race          = new Setting('race');
        $status        = new Setting('organization_status');
        $ethnicity     = new Setting('ethnicity');
        $followUpTask  = new Setting('outreach_follow_up_task');
        
        $organization_name = new Setting('referred_to');
        $status            = new Setting('organization_status');
        $regions           = new Setting('region');
        $type              = new Setting('organization_type');
        $county            = new Setting('county');
        $partnership       = new Setting('partnership_level');
        $type_of_contact   = new Setting('type_of_contact');
        $resource_database = new Setting('resource_database');
        $service_terms     = new Setting('referred_to_service');

        $numFollowUps = count($followUps);
        $numPages = ceil($totalFollowUps / 50);
        $pageNumber = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord = $firstRecord + $numFollowUps - 1;


        $reportTitle = 'Help Me Grow Utah List of Organization Follow Ups';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/organization-follow-ups-list-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }
}

