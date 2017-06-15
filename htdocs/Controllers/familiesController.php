<?php

namespace Hmg\Controllers;

use Hmg\Models\Families;
use Hmg\Models\Family;
use Hmg\Models\Setting;
use Hmg\Models\SchoolDistrict;
use Hmg\Helpers\SessionHelper as FilterHelper;

class FamiliesController
{
    public function __construct()
    {
        //$this->migrate_providers();

        $_SESSION['list-type'] = 'families-list';

        $families = new Families();
        $search = '';
        if (isset($_REQUEST['clearFilters'])) {
            unset($_SESSION['filters'], $_SESSION['totalFamilies'], $_SESSION['family-sorts'], $_SESSION['families']);
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
            $_SESSION['families']['search'] = (isset($_REQUEST['search']) ? $_REQUEST['search'] : '');
            $families->set('_search', $_SESSION['families']['search']);
            $_SESSION['families']['filters'] = $_REQUEST['filters'];
            $families->set('_filters', $_REQUEST['filters']);
        } else if (isset($_SESSION['families']['filters'])) {
            $search = (isset($_SESSION['families']['search']) ? $_SESSION['families']['search'] : '');
            $families->set('_search', ($search));
            $families->set('_filters', $_SESSION['families']['filters']);
        }
        $totalFamilies = $families->getCount();
        $_SESSION['totalFamilies'] = $totalFamilies;
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page'])) {
            $families->set('_start', ($_REQUEST['page'] - 1) * 50);
        } else {
            $families->set('_start', 0);
        }
        $sort = '';
        $field = '';
        $sorts = array();
        if (isset($_REQUEST['field']) && $_REQUEST['field'] && isset($_REQUEST['sort']) && $_REQUEST['sort']) {
            $sort = $_REQUEST['sort'];
            $field = $_REQUEST['field'];
            $sorts[$field] = $sort;
            $_SESSION['family-sorts'] = $sorts; // store sorts
            $families->set('_sorts', $sorts);
        }
        $message = isset($_REQUEST['message']) ? $_REQUEST['message'] : '';
        if (isset($_REQUEST['print'])) {
            $families->set('_limit', 0); // remove limits
            $this->printFamiliesList($families->getList(), $totalFamilies, $page, $sorts, $search, $message);
        } else if (isset($_REQUEST['export'])) {
            $families->set('_limit', 0); // remove limits
            $this->exportFamiliesList($families->getList(), $totalFamilies, $page, $sorts, $search, $message);
        } else {
            $this->displayFamiliesList($families->getList(), $totalFamilies, $page, $sorts, $search, $message);
        }
    }

    public function displayFamiliesList($families, $totalFamilies, $page, $sorts, $search, $message)
    {

        include(VIEW_PATH . '/adminnav.phtml');
        $numFamilies = count($families);
        $numPages = ceil($totalFamilies / 50);
        $pageNumber = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord = $firstRecord + $numFamilies - 1;
        // print_r($firstRecord);
        $field = '';
        $sort = '';
        if ($sorts) {
            $field = key($sorts);
            $sort = $sorts[$field];
        }

        $family = new Family();

        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getFamilyFilters();

        $setting       = new Setting();
        $relationships = new Setting('relationships');
        $language      = new Setting('language');
        $familyHeard   = new Setting('how_heard_category');
        $callReason    = new Setting('call_reason');
        $race          = new Setting('race');
        $ethnicity     = new Setting('ethnicity');
        $status        = new Setting('status');


        // include_once(CLASS_PATH . '/school_district.class.php');
        // $schoolDistrict = new SchoolDistrict;

        ob_start();
        include(VIEW_PATH . '/families-list.phtml');
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

    public function printFamiliesList($families, $totalFamilies, $page, $sorts, $search, $message)
    {
        $numFamilies = count($families);
        $numPages = ceil($totalFamilies / 50);
        $pageNumber = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord = $firstRecord + $numFamilies - 1;
        $field = '';
        $sort = '';
        if ($sorts) {
            $field = key($sorts);
            $sort = $sorts[$field];
        }

        include_once(CLASS_PATH . '/family.class.php');
        $family = new Family();

        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getFamilyFilters();

        $setting = new Setting();
        $relationships = new Setting('relationships');
        $language = new Setting('language');
        $familyHeard = new Setting('how_heard_category');
        $callReason = new Setting('call_reason');
        $race = new Setting('race');
        $ethnicity = new Setting('ethnicity');
        
        $reportTitle = 'Help Me Grow List of Families';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/families-list-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportFamiliesList($families, $totalFamilies, $page, $sorts, $search, $message)
    {
        $numFamilies = count($families);
        $numPages = ceil($totalFamilies / 50);
        $pageNumber = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord = $firstRecord + $numFamilies - 1;

        include_once(CLASS_PATH . '/family.class.php');
        $family = new Family();

        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getFamilyFilters();

        $setting = new Setting();
        $relationships = new Setting('relationships');
        $language = new Setting('language');
        $familyHeard = new Setting('how_heard_category');
        $callReason = new Setting('call_reason');
        $race = new Setting('race');
        $ethnicity = new Setting('ethnicity');
        ob_start();
        include(VIEW_PATH . '/families-list-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-families-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }
    
}
