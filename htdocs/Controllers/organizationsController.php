<?php

namespace Hmg\Controllers;

use Hmg\Models\Organizations;
use Hmg\Models\Organization;
use Hmg\Models\Setting;
use Hmg\Models\SchoolDistrict;
use Hmg\Helpers\SessionHelper as FilterHelper;

class OrganizationsController
{
    public function __construct()
    {

        $_SESSION['list-type'] = 'organizations-list';

        $organizations = new Organizations();
        $search = '';
        if (isset($_REQUEST['clearFilters'])) {
            unset($_SESSION['filters'], $_SESSION['totalOrganizations'], $_SESSION['organization-sorts'], $_SESSION['organizations']);
        }
        if (isset($_REQUEST['filters']) && !isset($_REQUEST['clearFilters'])) {
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
            $_SESSION['organizations']['search'] = (isset($_REQUEST['search']) ? $_REQUEST['search'] : '');
            $organizations->set('_search', $_SESSION['organizations']['search']);
            $_SESSION['organizations']['filters'] = $_REQUEST['filters'];
            $organizations->set('_filters', $_REQUEST['filters']);
            //echo '<pre>'; print_r($organizations->_filters); exit;
        } else if (isset($_SESSION['organizations']['filters'])) {
            $search = (isset($_SESSION['organizations']['search']) ? $_SESSION['organizations']['search'] : '');
            $organizations->set('_search', ($search));
            $organizations->set('_filters', $_SESSION['organizations']['filters']);
        }
        $totalOrganizations = $organizations->getCount();
        $_SESSION['totalOrganizations'] = $totalOrganizations;
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page'])) {
            $organizations->set('_start', ($_REQUEST['page'] - 1) * 50);
        } else {
            $organizations->set('_start', 0);
        }
        $sort = '';
        $field = '';
        $sorts = array();
        if (isset($_REQUEST['field']) && $_REQUEST['field'] && isset($_REQUEST['sort']) && $_REQUEST['sort']) {
            $sort = $_REQUEST['sort'];
            $field = $_REQUEST['field'];
            $sorts[$field] = $sort;
            if($field != 'site')
                $sorts['site'] = 'asc';
            $_SESSION['organization-sorts'] = $sorts; // store sorts
            $organizations->set('_sorts', $sorts);
        }
        $message = isset($_REQUEST['message']) ? $_REQUEST['message'] : '';
        if (isset($_REQUEST['print'])) {
            $organizations->set('_limit', 0); // remove limits
            $this->printOrganizationsList($organizations->getList(), $totalOrganizations, $page, $sorts, $search, $message);
        } else if (isset($_REQUEST['export'])) {
            $organizations->set('_limit', 0); // remove limits
            $this->exportOrganizationsList($organizations->getList(), $totalOrganizations, $page, $sorts, $search, $message);
        } else {
            $this->displayOrganizationsList($organizations->getList(), $totalOrganizations, $page, $sorts, $search, $message);
        }
    }

    public function displayOrganizationsList($organizations, $totalOrganizations, $page, $sorts, $search, $message)
    {

        include(VIEW_PATH . '/adminnav.phtml');

        $numOrganizations = count($organizations);
        $numPages = ceil($totalOrganizations / 50);
        $pageNumber = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord = $firstRecord + $numOrganizations - 1;
        $field = '';
        $sort = '';
        if ($sorts) {
            $field = key($sorts);
            $sort = $sorts[$field];
        }
        include_once(CLASS_PATH . '/organization.class.php');
        $organization = new Organization();

        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getOrganizationFilters();
        //echo "<pre>";print_r($filters);die;
        $setting       = new Setting();
        $relationships = new Setting('relationships');
        $language      = new Setting('language');
        $familyHeard   = new Setting('how_heard_category');
        $callReason    = new Setting('call_reason');
        $race          = new Setting('race');
        $ethnicity     = new Setting('ethnicity');
        $status        = new Setting('organization_status');
        $regions       = new Setting('region');
        $type          = new Setting('organization_type');
		//$subtype       = new Setting('organization_type');
		
        $county        = new Setting('county');
        $partnership   = new Setting('partnership_level');
        $type_of_contact      = new Setting('type_of_contact');
        $resource_database    = new Setting('resource_database');
        $service_terms        = new Setting('referred_to_service');
		//$orgization_name         = new Setting('organization_type');
		

        include_once(CLASS_PATH . '/school_district.class.php');
        $schoolDistrict = new SchoolDistrict;

        ob_start();
        include(VIEW_PATH . '/organizations-list.phtml');
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

    public function printOrganizationsList($organizations, $totalOrganizations, $page, $sorts, $search, $message)
    {
        $numOrganizations = count($organizations);
        $numPages = ceil($totalOrganizations / 50);
        $pageNumber = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord = $firstRecord + $numOrganizations - 1;
        $field = '';
        $sort = '';
        if ($sorts) {
            $field = key($sorts);
            $sort = $sorts[$field];
        }

        include_once(CLASS_PATH . '/organization.class.php');
        $organization = new Organization();

        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getOrganizationFilters();

        $setting = new Setting();
        $relationships = new Setting('relationships');
        $language = new Setting('language');
        $familyHeard = new Setting('how_heard_category');
        $callReason = new Setting('call_reason');
        $race = new Setting('race');
        $ethnicity = new Setting('ethnicity');
        
        $reportTitle = 'Help Me Grow List of Organizations';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/organizations-list-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportOrganizationsList($organizations, $totalOrganizations, $page, $sorts, $search, $message)
    {
        $numOrganizations = count($organizations);
        $numPages = ceil($totalOrganizations / 50);
        $pageNumber = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord = $firstRecord + $numOrganizations - 1;

        include_once(CLASS_PATH . '/organization.class.php');
        $organization = new Organization();
        $organizationed = new Organization();

        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getOrganizationFilters();

        $setting = new Setting();
        $relationships = new Setting('relationships');
        $language = new Setting('language');
        $familyHeard = new Setting('how_heard_category');
        $callReason = new Setting('call_reason');
        $race = new Setting('race');
        $ethnicity = new Setting('ethnicity');
        ob_start();
        include(VIEW_PATH . '/organizations-list-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-organizations-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }
}
