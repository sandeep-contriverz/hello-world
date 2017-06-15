<?php

namespace Hmg\Controllers;

use Hmg\Models\Counts;
use Hmg\Models\Family;
use Hmg\Models\Organization;
use Hmg\Models\Setting;
use Hmg\Models\SchoolDistrict;
use Hmg\Helpers\SessionHelper as FilterHelper;
use Hmg\Fpdf\Helpers\PdfMySqlTable;

class CountsController
{
    public $issues = '';
    public $county = '';
    public $status = '';
    public $substatus = '';
    public $value = '';
    public $screenings = '';
    public $drillheading = array();
    public $sorts = array();
    public function __construct()
    {
        $counts = new Counts();

        if (isset($_REQUEST['clearFilters'])) {
            unset($_SESSION['count']['filters'], $_REQUEST['filters'], $_SESSION['family-drill-sorts']);
        }
        if (isset($_REQUEST['filters'])) {
            $_SESSION['count']['filters'] = $_REQUEST['filters'];
            $counts->set('_filters', $_REQUEST['filters']);
        } else if (isset($_SESSION['count']['filters'])) {
            $counts->set('_filters', $_SESSION['count']['filters']);
        }
        $print = (isset($_REQUEST['print']) && $_REQUEST['print'] ? '1' : '0');
        $printpdf = (isset($_REQUEST['pdf']) && $_REQUEST['pdf'] ? '1' : '0');
        $export = (isset($_REQUEST['export']) && $_REQUEST['export'] ? '1' : '0');
        $type = (isset($_REQUEST['type']) && $_REQUEST['type'] ? $_REQUEST['type'] : '');
        $this->issues = (isset($_REQUEST['issues']) && $_REQUEST['issues'] ? $_REQUEST['issues'] : '');
        $this->county  = isset($_REQUEST['county']) && $_REQUEST['county'] ? $_REQUEST['county'] : '';
        $this->status  = isset($_REQUEST['status']) ? $_REQUEST['status'] : '';
        $this->substatus  = isset($_REQUEST['substatus']) ? $_REQUEST['substatus'] : '';
        $this->subvalue  = isset($_REQUEST['subvalue']) ? $_REQUEST['subvalue'] : '';
        $this->screenings  = isset($_REQUEST['screenings']) ? $_REQUEST['screenings'] : '';

        if (isset($_REQUEST['field']) && $_REQUEST['field'] && isset($_REQUEST['sort']) && $_REQUEST['sort']) {
            $sort = $_REQUEST['sort'];
            $field = $_REQUEST['field'];
            $sorts[$field] = $sort;
            $_SESSION['family-drill-sorts'] = $sorts; // store sorts
            $this->sorts = $sorts;
            //echo "<pre>";print_r($sorts);die;
        }
        
        //loads new child count view
        if(isset($type) && $type == 'childDrill') {
            if ($type) {
                $function = (
                    $print ? 'print'
                    :
                    (
                        $printpdf ? 'printPDF'
                        :
                        (
                            $export ? 'export' : 'display'
                        )
                    )
                ) . str_replace(' ', '', ucwords($type));
                $this->$function();
            } else {
                $this->displayLinks();
            }

        } 
        elseif(isset($type) && $type == 'screeningsDrill') {
            if ($type) {
                $function = (
                    $print ? 'print'
                    :
                    (
                        $printpdf ? 'printPDF'
                        :
                        (
                            $export ? 'export' : 'display'
                        )
                    )
                ) . str_replace(' ', '', ucwords($type));
                $this->$function();
            } else {
                $this->displayLinks();
            }
        } else {
            if ($type) {
                $function = (
                    $print ? 'print'
                    :
                    (
                        $printpdf ? 'printPDF'
                        :
                        (
                            $export ? 'export' : 'display'
                        )
                    )
                ) . 'Counts' . str_replace(' ', '', ucwords($type));
                $this->$function();
            } else {
                $this->displayLinks();
            }
        }

    }

    public function displayChildDrill()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $drillName  = isset($_REQUEST['drillName']) ? $_REQUEST['drillName'] : '';
        if(!empty($drillName))
            $_SESSION['drillName'] = $drillName;

       $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            (isset($filters['drillheading']) && !empty($filters['drillheading']) ? $filters['drillheading'] : (isset($drillName) ? $drillName : ''))
        );
        $drillHead = array_filter($drillHead);
        //echo "<pre>";print_r($drillHead);echo "</pre>";
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';
        
        $totalChilds = $this->getDrillResults(true);
        $_SESSION['totalChilds'] = $totalChilds;
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page'])) {
            $start = ($_REQUEST['page'] - 1) * 50;
        } else {
            $start = 0;
        }
        $field = $this->status;

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $sort  = '';
        $sorts = $this->sorts;
        if ($sorts) {
            $field = key($sorts);
            $sort = $sorts[$field];
        }

        $childDetails = $this->getDrillResults(false, $start);
        
        $numChilds   = count($childDetails);
        $numPages    = ceil($totalChilds / 50);
        $pageNumber  = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord  = $firstRecord + $numChilds - 1;

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        $search  = (isset($_SESSION['families']['search']) ? $_SESSION['families']['search'] : '');
        $issues  = $this->issues;
        $county  = $this->county;
        $screenings = $this->screenings;
       
        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-child-drill.phtml');
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

    
    public function displayCountsEventsDrill()
    {
        
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts('eventsDrill');
        $counts->set('_filters', $filters);
        $sort  = '';
        
        $sorts = $this->sorts;
        if ($sorts) {
            $field = key($sorts);
            $sort = $sorts[$field];
        }

        $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            //(isset($filters['substatus']) && !empty($filters['substatus']) ? $filters['substatus'] : (isset($this->substatus) ? $this->substatus : '')),
            //(isset($filters['subvalue']) && !empty($filters['subvalue']) ? $filters['subvalue'] : (isset($this->subvalue) ? $this->subvalue : ''))
        );
         
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';        
        $arg="count";
        
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page'])) {
            $start = ($_REQUEST['page'] - 1) * 50;
        } else {
            $start = 0;
        }
        
        $field = $this->subvalue;
        $totalOrganization = $counts->getEventsDrill(2, $this->subvalue, $this->substatus, $start);
        $organizations = $counts->getEventsDrill(1, $this->subvalue, $this->substatus, $start);
        
        $substatus = $this->substatus;
        $totalOrganizations   = count($totalOrganization);
        // print_r($organizations);
        $numPages    = ceil($totalOrganizations / 50);
        $pageNumber  = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord  = $firstRecord + $totalOrganizations - 1;
        
        $search  = (isset($_SESSION['events']['search']) ? $_SESSION['events']['search'] : '');

        $organization  = new Organization();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-events-drill.phtml');
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
    public function printCountsEventsDrill()
    {
        
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts('eventsDrill');
        $counts->set('_filters', $filters);
        $sort  = '';
        
        $sorts = $this->sorts;
        if ($sorts) {
            $field = key($sorts);
            $sort = $sorts[$field];
        }

        $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            //(isset($filters['substatus']) && !empty($filters['substatus']) ? $filters['substatus'] : (isset($this->substatus) ? $this->substatus : '')),
            //(isset($filters['subvalue']) && !empty($filters['subvalue']) ? $filters['subvalue'] : (isset($this->subvalue) ? $this->subvalue : ''))
        );
         
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';        
        $arg="count";
        
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page'])) {
            $start = ($_REQUEST['page'] - 1) * 50;
        } else {
            $start = 0;
        }
        
        $field = $this->subvalue;
        $totalOrganization = $counts->getEventsDrill(2, $this->subvalue, $this->substatus, $start);
        $organizations = $counts->getEventsDrill(2, $this->subvalue, $this->substatus, $start);
        
        $substatus = $this->substatus;
        $totalOrganizations   = count($totalOrganization);
        // print_r($organizations);
        $numPages    = ceil($totalOrganizations / 50);
        $pageNumber  = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord  = $firstRecord + $totalOrganizations - 1;
        
        $search  = (isset($_SESSION['events']['search']) ? $_SESSION['events']['search'] : '');

        $organization  = new Organization();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        $reportTitle  = 'Outreach Events Demographics';
        

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/counts-events-drill-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
        
    }
    
    public function exportCountsEventsDrill()
    {
        
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts('eventsDrill');
        $counts->set('_filters', $filters);
        $sort  = '';
        
        $sorts = $this->sorts;
        if ($sorts) {
            $field = key($sorts);
            $sort = $sorts[$field];
        }

        $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            //(isset($filters['substatus']) && !empty($filters['substatus']) ? $filters['substatus'] : (isset($this->substatus) ? $this->substatus : '')),
            //(isset($filters['subvalue']) && !empty($filters['subvalue']) ? $filters['subvalue'] : (isset($this->subvalue) ? $this->subvalue : ''))
        );
         
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';        
        $arg="count";
        
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page'])) {
            $start = ($_REQUEST['page'] - 1) * 50;
        } else {
            $start = 0;
        }
        
        $field = $this->subvalue;
        $totalOrganization = $counts->getEventsDrill(2, $this->subvalue, $this->substatus, $start);
        $organizations = $counts->getEventsDrill(2, $this->subvalue, $this->substatus, $start);
        
        $substatus = $this->substatus;
        $totalOrganizations   = count($totalOrganization);
        // print_r($organizations);
        $numPages    = ceil($totalOrganizations / 50);
        $pageNumber  = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord  = $firstRecord + $totalOrganizations - 1;
        
        $search  = (isset($_SESSION['events']['search']) ? $_SESSION['events']['search'] : '');

        $organization  = new Organization();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        ob_start();
        include(VIEW_PATH . '/counts-events-drill-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();
        $enc = getenv('ENVIRONMENT');
        $env = (null !== $enc && !empty($enc))?strtolower($enc):'file';

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . $env
            . '-counts-events-drill-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
        
    }
    
    public function displayCountsOrganizationDrill()
    {
        
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts('organizationDrill');
        $counts->set('_filters', $filters);
        $sort  = '';
        
        $sorts = $this->sorts;
        if ($sorts) {
            $field = key($sorts);
            $sort = $sorts[$field];
        }

        $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            //(isset($filters['substatus']) && !empty($filters['substatus']) ? $filters['substatus'] : (isset($this->substatus) ? $this->substatus : '')),
            //(isset($filters['subvalue']) && !empty($filters['subvalue']) ? $filters['subvalue'] : (isset($this->subvalue) ? $this->subvalue : ''))
        );
         
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';        
        $arg="count";
        
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page'])) {
            $start = ($_REQUEST['page'] - 1) * 50;
        } else {
            $start = 0;
        }
        
        $field = $this->subvalue;
        $totalOrganization = $counts->getOrganizationDrill(2, $this->subvalue, $this->substatus, $start);
        $organizations = $counts->getOrganizationDrill(1, $this->subvalue, $this->substatus, $start);
        
        $substatus = $this->substatus;
        $totalOrganizations   = count($totalOrganization);
        // print_r($organizations);
        $numPages    = ceil($totalOrganizations / 50);
        $pageNumber  = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord  = $firstRecord + $totalOrganizations - 1;
        
        $search  = (isset($_SESSION['organizations']['search']) ? $_SESSION['organizations']['search'] : '');

        $organization  = new Organization();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-organization-drill.phtml');
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
    
    public function printCountsOrganizationDrill()
    {
        
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts('organizationDrill');
        $counts->set('_filters', $filters);
        $sort  = '';
        
        $sorts = $this->sorts;
        if ($sorts) {
            $field = key($sorts);
            $sort = $sorts[$field];
        }

        $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            //(isset($filters['substatus']) && !empty($filters['substatus']) ? $filters['substatus'] : (isset($this->substatus) ? $this->substatus : '')),
            //(isset($filters['subvalue']) && !empty($filters['subvalue']) ? $filters['subvalue'] : (isset($this->subvalue) ? $this->subvalue : ''))
        );
         
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';        
        $arg="count";
        
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page'])) {
            $start = ($_REQUEST['page'] - 1) * 50;
        } else {
            $start = 0;
        }
        
        $field = $this->subvalue;
        $totalOrganization = $counts->getOrganizationDrill(2, $this->subvalue, $this->substatus, $start);
        $organizations = $counts->getOrganizationDrill(2, $this->subvalue, $this->substatus, $start);
        
        $substatus = $this->substatus;
        $totalOrganizations   = count($totalOrganization);
        // print_r($organizations);
        $numPages    = ceil($totalOrganizations / 50);
        $pageNumber  = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord  = $firstRecord + $totalOrganizations - 1;
        
        $search  = (isset($_SESSION['organizations']['search']) ? $_SESSION['organizations']['search'] : '');

        $organization  = new Organization();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        $reportTitle  = 'Outreach Events Demographics';
        

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/counts-organization-drill-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
        
    }
    
    public function exportCountsOrganizationDrill()
    {
        
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts('organizationDrill');
        $counts->set('_filters', $filters);
        $sort  = '';
        
        $sorts = $this->sorts;
        if ($sorts) {
            $field = key($sorts);
            $sort = $sorts[$field];
        }

        $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            //(isset($filters['substatus']) && !empty($filters['substatus']) ? $filters['substatus'] : (isset($this->substatus) ? $this->substatus : '')),
            //(isset($filters['subvalue']) && !empty($filters['subvalue']) ? $filters['subvalue'] : (isset($this->subvalue) ? $this->subvalue : ''))
        );
         
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';        
        $arg="count";
        
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page'])) {
            $start = ($_REQUEST['page'] - 1) * 50;
        } else {
            $start = 0;
        }
        
        $field = $this->subvalue;
        $totalOrganization = $counts->getOrganizationDrill(2, $this->subvalue, $this->substatus, $start);
        $organizations = $counts->getOrganizationDrill(2, $this->subvalue, $this->substatus, $start);
        
        $substatus = $this->substatus;
        $totalOrganizations   = count($totalOrganization);
        // print_r($organizations);
        $numPages    = ceil($totalOrganizations / 50);
        $pageNumber  = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord  = $firstRecord + $totalOrganizations - 1;
        
        $search  = (isset($_SESSION['organizations']['search']) ? $_SESSION['organizations']['search'] : '');

        $organization  = new Organization();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        ob_start();
        include(VIEW_PATH . '/counts-organization-drill-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();
        $enc = getenv('ENVIRONMENT');
        $env = (null !== $enc && !empty($enc))?strtolower($enc):'file';

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . $env
            . '-counts-organization-drill-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
        
    }
   
    public function displayCountsfamilyDrill()
    {
        
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts('familyDrill');
        $counts->set('_filters', $filters);
        $sort  = '';
        $sorts = $this->sorts;
        if ($sorts) {
            $field = key($sorts);
            $sort = $sorts[$field];
        }

        $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            (isset($filters['status']) && !empty($filters['status']) ? $filters['status'] : (isset($this->status) ? $this->status : ''))
        );
        //echo "<pre>";print_r($drillHead);echo "</pre>";
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';
        //-----Pagination ----
        $arg="count";
        $totalfamilies = $counts->getfamilyDrill(2,$this->status,0);
        //$totalChilds = $this->getDrillResults(true);
        
        $_SESSION['totalChilds'] = $totalfamilies;
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page'])) {
            $start = ($_REQUEST['page'] - 1) * 50;
        } else {
            $start = 0;
        }
        
        $field = $this->status;
        $familydetails = $counts->getfamilyDrill(1,$this->status,$start);
        
        
        //----------------------------------------------------
        $numChilds   = count($familydetails);
        $numPages    = ceil($totalfamilies / 50);
        $pageNumber  = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord  = $firstRecord + $numChilds - 1;
        
        $search  = (isset($_SESSION['families']['search']) ? $_SESSION['families']['search'] : '');
        //-----------------


        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-family-drill.phtml');
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
public function exportCountsfamilyDrill()
{
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts('familyDrill');
        $counts->set('_filters', $filters);
        
        $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            (isset($filters['status']) && !empty($filters['status']) ? $filters['status'] : (isset($this->status) ? $this->status : ''))
        );
        //echo "<pre>";print_r($drillHead);echo "</pre>";
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';
        
        //-----Pagination ----
        $arg="count";
        $totalfamilies = $counts->getfamilyDrill(3,$this->status,0);
        //echo "<pre>";
        //print_r($totalfamilies); die();
        
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        ob_start();
        include(VIEW_PATH . '/counts-family-drill-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();
        $enc = getenv('ENVIRONMENT');
        $env = (null !== $enc && !empty($enc))?strtolower($enc):'file';

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . $env
            . '-counts-child-drill-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
}
public function printCountsfamilyDrill()
{
    
    $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts('familyDrill');
        $counts->set('_filters', $filters);
        
        $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            (isset($filters['status']) && !empty($filters['status']) ? $filters['status'] : (isset($this->status) ? $this->status : ''))
        );
        //echo "<pre>";print_r($drillHead);echo "</pre>";
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';
        
                
        //-----Pagination ----
        $arg="count";
        $familydetails = $counts->getfamilyDrill(3,$this->status,0);
        
        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();
       
        $reportTitle  = 'Family Demographics';
        $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            (isset($filters['status']) && !empty($filters['status']) ? $filters['status'] : (isset($this->status) ? $this->status : ''))
        );
        //echo "<pre>";print_r($drillHead);echo "</pre>";
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/counts-family-drill-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;

}
//--------------------Call Reason---------------
public function displayCountscallReasonDrill()
    {
        
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();
        $drillName  = isset($_REQUEST['drillName']) ? $_REQUEST['drillName'] : '';
        if(!empty($drillName))
            $_SESSION['drillName'] = $drillName;

        $counts = new Counts();
        $counts->set('_filters', $filters);

        $sort  = '';
        $sorts = $this->sorts;
        if ($sorts) {
            $field = key($sorts);
            $sort = $sorts[$field];
        }
        
        $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            (isset($filters['drillheading']) && !empty($filters['drillheading']) ? $filters['drillheading'] : (isset($drillName) ? $drillName : ''))
        );
        $drillHead = array_filter($drillHead);
        //echo "<pre>";print_r($drillHead);echo "</pre>";
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';

        //-----Pagination ----
        $arg="count";
        
        $totalcalls = $counts->getCallReasonDrill($_SESSION['drillName'],true,0,true);
    
        $_SESSION['totalChilds'] = $totalcalls;
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page'])) {
            $start = ($_REQUEST['page'] - 1) * 50;
        } else {
            $start = 0;
        }
        
        $field = $this->status;
        $calldetails = $counts->getCallReasonDrill($_SESSION['drillName'],false,$start,true);
        
        //----------------------------------------------------
        $numChilds   = count($calldetails);
        $numPages    = ceil( $totalcalls/ 50);
        $pageNumber  = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord  = $firstRecord + $numChilds - 1;
        
        $search  = (isset($_SESSION['families']['search']) ? $_SESSION['families']['search'] : '');
        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-callreason-drill.phtml');
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

public function exportCountsCallReasonDrill()

    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();
        $drillName  = isset($_REQUEST['drillName']) ? $_REQUEST['drillName'] : '';
        if(!empty($drillName))
            $_SESSION['drillName'] = $drillName;

        $counts = new Counts();
        $counts->set('_filters', $filters);
        
        $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            (isset($filters['drillheading']) && !empty($filters['drillheading']) ? $filters['drillheading'] : (isset($drillName) ? $drillName : ''))
        );
        $drillHead = array_filter($drillHead);
        //echo "<pre>";print_r($drillHead);echo "</pre>";
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';
        //-----Pagination ----
                
  
        $field = $this->status;
        $calldetails = $counts->getCallReasonDrill($_SESSION['drillName'],false,0,false);
        
        //----------------------------------------------------
        
        $search  = (isset($_SESSION['families']['search']) ? $_SESSION['families']['search'] : '');
        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        $enc = getenv('ENVIRONMENT');
        $env = (null !== $enc && !empty($enc))?strtolower($enc):'file';

        ob_start();
        include(VIEW_PATH . '/counts-callreason-drill-export.phtml');
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
        
        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . $env
            . '-counts-child-drill-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;

    }

public function printCountsCallReasonDrill()
{

    
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();
        $drillName  = isset($_REQUEST['drillName']) ? $_REQUEST['drillName'] : '';
        if(!empty($drillName))
            $_SESSION['drillName'] = $drillName;

        $counts = new Counts();
        $counts->set('_filters', $filters);
        
       $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            (isset($filters['drillheading']) && !empty($filters['drillheading']) ? $filters['drillheading'] : (isset($drillName) ? $drillName : ''))
        );
        $drillHead = array_filter($drillHead);
        //echo "<pre>";print_r($drillHead);echo "</pre>";
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';
                
        //-----Pagination ----
        $arg="count";
        $calldetails = $counts->getCallReasonDrill($_SESSION['drillName'],false,0,false);
        
        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();
       
        $reportTitle = 'Call Reason Demographics';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/counts-callreason-drill-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;

}
    //--------HOW HEARD FUNCTIONS --------------------------------

    public function displayCountshowheardDrill()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();
        $drillName  = isset($_REQUEST['drillName']) ? $_REQUEST['drillName'] : '';
        if(!empty($drillName))
            $_SESSION['drillName'] = $drillName;

        $counts = new Counts();
        $counts->set('_filters', $filters);

        $sort  = '';
        $sorts = $this->sorts;
        if ($sorts) {
            $field = key($sorts);
            $sort = $sorts[$field];
        }
        
        $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            (isset($filters['status']) && !empty($filters['status']) ? $filters['status'] : ($drillName) ? $drillName : '')
        );
        //echo "<pre>";print_r($drillHead);echo "</pre>";
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';

        //-----Pagination ----
        $arg="count";
        
        $totalheards = $counts->getHowHeardCountsDrill($_SESSION['drillName'],true,0,true);

        $_SESSION['totalChilds'] = $totalheards;
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page'])) {
            $start = ($_REQUEST['page'] - 1) * 50;
        } else {
            $start = 0;
        }
        
        $field = $this->status;
        $hearddetails = $counts->getHowHeardCountsDrill($_SESSION['drillName'],false,0,true);;
        
        //----------------------------------------------------
        $numChilds   = count($hearddetails);
        $numPages    = ceil($totalheards / 50);
        $pageNumber  = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord  = $firstRecord + $numChilds - 1;
        
        $search  = (isset($_SESSION['families']['search']) ? $_SESSION['families']['search'] : '');
        //-----------------
      
        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-heard-drill.phtml');
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
public function exportCountshowheardDrill()

    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $drillName  = isset($_REQUEST['drillName']) ? $_REQUEST['drillName'] : '';
        if(!empty($drillName))
            $_SESSION['drillName'] = $drillName;

        $counts = new Counts();
        $counts->set('_filters', $filters);
        
        $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            (isset($filters['status']) && !empty($filters['status']) ? $filters['status'] : ($drillName) ? $drillName : '')
        );
        //echo "<pre>";print_r($drillHead);echo "</pre>";
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';

        //-----Pagination ----
        $arg="count";
        
        $totalheards = $counts->getHowHeardCountsDrill($_SESSION['drillName'],true,0,false);

            

        $_SESSION['totalChilds'] = $totalheards;
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page'])) {
            $start = ($_REQUEST['page'] - 1) * 50;
        } else {
            $start = 0;
        }
        
        $field = $this->status;
        $hearddetails = $counts->getHowHeardCountsDrill($_SESSION['drillName'],false,0,false);;
        
        //----------------------------------------------------
        $numChilds   = count($hearddetails);
        $numPages    = ceil($totalheards / 50);
        $pageNumber  = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord  = $firstRecord + $numChilds - 1;
        
        $search  = (isset($_SESSION['families']['search']) ? $_SESSION['families']['search'] : '');
        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        $enc = getenv('ENVIRONMENT');
        $env = (null !== $enc && !empty($enc))?strtolower($enc):'file';

        ob_start();
        include(VIEW_PATH . '/counts-heard-drill-export.phtml');
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
        
        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . $env
            . '-counts-child-drill-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;

    }
public function printCountshowheardDrill()

    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $drillName  = isset($_REQUEST['drillName']) ? $_REQUEST['drillName'] : '';
        if(!empty($drillName))
            $_SESSION['drillName'] = $drillName;

        $counts = new Counts();
        $counts->set('_filters', $filters);
        
        $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            (isset($filters['status']) && !empty($filters['status']) ? $filters['status'] : ($drillName) ? $drillName : '')
        );
        //echo "<pre>";print_r($drillHead);echo "</pre>";
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';

        //-----Pagination ----
        $arg="count";
        
        $totalheards = $counts->getHowHeardCountsDrill($_SESSION['drillName'],true,0,true);

        $_SESSION['totalChilds'] = $totalheards;
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page'])) {
            $start = ($_REQUEST['page'] - 1) * 50;
        } else {
            $start = 0;
        }
        
        $field = $this->status;
        $hearddetails = $counts->getHowHeardCountsDrill($_SESSION['drillName'],false,0,false);;
        
        //----------------------------------------------------
        $numChilds   = count($hearddetails);
        $numPages    = ceil($totalheards / 50);
        $pageNumber  = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord  = $firstRecord + $numChilds - 1;
        
        $search  = (isset($_SESSION['families']['search']) ? $_SESSION['families']['search'] : '');
        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        $reportTitle = 'How Heard Demographics';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/counts-heard-drill-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();



        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;

    }

//------------------------------------------------------------------------------------------
    /**
     *  Get records for Drill feature
     *  @params: boolean $count, int $start
     */
    public function getDrillResults($count = false, $start = 0, $is_page = true) {
        $totalChilds  = array();
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();
        $counts  = new Counts();
        $counts->set('_filters', $filters);

        if(!empty($this->status) || !empty($this->county) || !empty($this->screenings)) {
            unset($_SESSION['issues']);
        }
        if(!empty($this->status) || !empty($this->issues) || !empty($this->screenings)) {
            unset($_SESSION['county']);
        } 
        if(!empty($this->status) || !empty($this->county) || !empty($this->issues)) {
            unset($_SESSION['screenings']);
        }
        if(isset($this->issues)) {
            $_SESSION['issues'] = !empty($this->issues) ? $this->issues : 
                (isset($_SESSION['issues']) ? $_SESSION['issues'] : '');
        } 
        if(isset($this->county)) {
            $_SESSION['county'] = !empty($this->county) ? $this->county : 
                (isset($_SESSION['county']) ? $_SESSION['county'] : '');
        }
        
        if(isset($this->screenings)) {
            $_SESSION['screenings'] = !empty($this->screenings) ? $this->screenings : 
                (isset($_SESSION['screenings']) ? $_SESSION['screenings'] : '');
        }
        //echo "<pre>";print_r($_SESSION);die;
        $this->issues  = isset($_SESSION['issues']) && !empty($_SESSION['issues']) 
                    ? $_SESSION['issues'] : $this->issues;
        $this->county  = isset($_SESSION['county']) && !empty($_SESSION['county']) 
                    ? $_SESSION['county'] : $this->county;
        $this->screenings  = isset($_SESSION['screenings']) && !empty($_SESSION['screenings']) 
                    ? $_SESSION['screenings'] : $this->screenings;

        if(isset($this->issues) && !empty($this->issues)) {
            $totalChilds = $counts->getChildIssueCountsDetail($_SESSION['drillName'], $count, $start, $is_page);
        } elseif(isset($this->county) && !empty($this->county)) {
            $totalChilds = $counts->getChildCountyCountsDetail($_SESSION['drillName'], $count, $start, $is_page);
        } elseif(isset($this->screenings) && !empty($this->screenings)) {
            $totalChilds = $counts->getScreeningCountsDetail($_SESSION['drillName'], $count, $start, $is_page);
        } else {
            $totalChilds = $counts->getChildDetails($_SESSION['drillName'], $count, $start, $is_page);
        }
        return $totalChilds;
    }

    public function printChildDrill()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $drillName  = isset($_REQUEST['drillName']) ? $_REQUEST['drillName'] : '';
        if(!empty($drillName))
            $_SESSION['drillName'] = $drillName;

       $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            (isset($filters['drillheading']) && !empty($filters['drillheading']) ? $filters['drillheading'] : (isset($drillName) ? $drillName : ''))
        );
        $drillHead = array_filter($drillHead);
        //echo "<pre>";print_r($drillHead);echo "</pre>";
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';
        
        $field = $this->status;
        $childDetails = $this->getDrillResults(false, 0, false);

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        $reportTitle = 'Child Demographics';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/counts-child-drill-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportChildDrill()
    {
        
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();
        $drillName  = isset($_REQUEST['drillName']) ? $_REQUEST['drillName'] : '';
        if(!empty($drillName))
            $_SESSION['drillName'] = $drillName;

       $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            (isset($filters['drillheading']) && !empty($filters['drillheading']) ? $filters['drillheading'] : (isset($drillName) ? $drillName : ''))
        );
        $drillHead = array_filter($drillHead);
        //echo "<pre>";print_r($drillHead);echo "</pre>";
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';

        $field = $this->status;
        $childDetails = $this->getDrillResults(false, 0, false);

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        ob_start();
        include(VIEW_PATH . '/counts-child-drill-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();
        $enc = getenv('ENVIRONMENT');
        $env = (null !== $enc && !empty($enc))?strtolower($enc):'file';

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . $env
            . '-counts-child-drill-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }

    public function displayScreeningsDrill()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $drillName  = isset($_REQUEST['drillName']) ? $_REQUEST['drillName'] : '';
        if(!empty($drillName))
            $_SESSION['drillName'] = $drillName;

       $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            (isset($filters['drillheading']) && !empty($filters['drillheading']) ? $filters['drillheading'] : (isset($drillName) ? $drillName : ''))
        );
        $drillHead = array_filter($drillHead);
        //echo "<pre>";print_r($drillHead);echo "</pre>";
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';
        
        $totalChilds = $this->getDrillResults(true);
        $_SESSION['totalChilds'] = $totalChilds;
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page'])) {
            $start = ($_REQUEST['page'] - 1) * 50;
        } else {
            $start = 0;
        }
        $field = $this->status;
        $sort  = '';
        $sorts = $this->sorts;
        if ($sorts) {
            $field = key($sorts);
            $sort = $sorts[$field];
        }
        
        $childDetails = $this->getDrillResults(false, $start);

        $numChilds   = count($childDetails);
        $numPages    = ceil($totalChilds / 50);
        $pageNumber  = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord  = $firstRecord + $numChilds - 1;

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        $search  = (isset($_SESSION['families']['search']) ? $_SESSION['families']['search'] : '');
        $issues  = $this->issues;
        $county  = $this->county;
        $screenings = $this->screenings;
       
        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        $settingOb = new Setting(); //201016
        include(VIEW_PATH . '/counts-screening-drill.phtml');
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

    public function printScreeningsDrill()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();
        
        $drillName  = isset($_REQUEST['drillName']) ? $_REQUEST['drillName'] : '';
        if(!empty($drillName))
            $_SESSION['drillName'] = $drillName;

       $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            (isset($filters['drillheading']) && !empty($filters['drillheading']) ? $filters['drillheading'] : (isset($drillName) ? $drillName : ''))
        );
        $drillHead = array_filter($drillHead);
        //echo "<pre>";print_r($drillHead);echo "</pre>";
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';

        $field = $this->status;
        $childDetails = $this->getDrillResults(false, 0, false);

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        $reportTitle = 'Developmental Screening Demographics';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        $settingOb = new Setting(); //201016
        include(VIEW_PATH . '/counts-screening-drill-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportScreeningsDrill()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $drillName  = isset($_REQUEST['drillName']) ? $_REQUEST['drillName'] : '';
        if(!empty($drillName))
            $_SESSION['drillName'] = $drillName;

       $drillHead = array(
            (isset($_REQUEST['drillheading']) ? $_REQUEST['drillheading'] : ''),
            (isset($filters['drillheading']) && !empty($filters['drillheading']) ? $filters['drillheading'] : (isset($drillName) ? $drillName : ''))
        );
        $drillHead = array_filter($drillHead);
        //echo "<pre>";print_r($drillHead);echo "</pre>";
        if(!empty($drillHead) && isset($_REQUEST['drillheading']))
            $_SESSION['drillheading'] = $drillHead;

        $this->drillheading = isset($_SESSION['drillheading']) ? $_SESSION['drillheading'] : '';
       
        $field = $this->status;
        $childDetails = $this->getDrillResults(false, 0, false);

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        ob_start();
        $settingOb = new Setting(); //201016
        include(VIEW_PATH . '/counts-screening-drill-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();
        $enc = getenv('ENVIRONMENT');
        $env = (null !== $enc && !empty($enc))?strtolower($enc):'file';

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . $env
            . '-counts-child-drill-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }



    public function displayCountsProgram()
    {

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-program.phtml');
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
    
    public function displayCountsOrganization()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $statusCounts = $counts->getOrgStatusCounts();
        $typeCounts = $counts->getOrgTypeCounts();
        $partCounts = $counts->getOrgPartCounts();
        
        $mouCounts = $counts->getOrgMouCounts();
        $resCounts = $counts->getOrgResCounts();
        $modeCounts = $counts->getOrgModeCounts();
        
        
        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-organizations.phtml');
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
    
    public function printCountsOrganization()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $statusCounts = $counts->getOrgStatusCounts();
        $typeCounts = $counts->getOrgTypeCounts();
        $partCounts = $counts->getOrgPartCounts();
        
        $mouCounts = $counts->getOrgMouCounts();
        $resCounts = $counts->getOrgResCounts();
        $modeCounts = $counts->getOrgModeCounts();
        
        
        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();
        
        $reportTitle = 'Organizations Demographics';
        
        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/counts-organizations-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

       

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }
    
    public function exportCountsOrganization()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $statusCounts = $counts->getOrgStatusCounts();
        $typeCounts = $counts->getOrgTypeCounts();
        $partCounts = $counts->getOrgPartCounts();
        
        $mouCounts = $counts->getOrgMouCounts();
        $resCounts = $counts->getOrgResCounts();
        $modeCounts = $counts->getOrgModeCounts();
        
        
        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();
        
        $reportTitle = 'Organizations Demographics';
        
        ob_start();
        include(VIEW_PATH . '/counts-organizations-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-counts-organizations-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }
    
    public function displayCountsOutreach_events()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters); 
        
        $typeCounts = $counts->getEventTypeCounts();
        $outreachTypeCounts = $counts->getEventOutreachTypeCounts();
        $dayCounts = $counts->getEventDayCounts();
        $countyCounts = $counts->getEventCountyCounts();
        

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-outreach-events.phtml');
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
    
    public function printCountsOutreach_events()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);
        
        $typeCounts = $counts->getEventTypeCounts();
        $outreachTypeCounts = $counts->getEventOutreachTypeCounts();
        $dayCounts = $counts->getEventDayCounts();
        $countyCounts = $counts->getEventCountyCounts();
        

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        $reportTitle = 'Outreach Events Demographics';
        
        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/counts-outreach-events-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

     

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }
    
    public function exportCountsOutreach_events()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);
        
        $typeCounts = $counts->getEventTypeCounts();
        $outreachTypeCounts = $counts->getEventOutreachTypeCounts();
        $dayCounts = $counts->getEventDayCounts();
        $countyCounts = $counts->getEventCountyCounts();
        

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        $reportTitle = 'Outreach Events Demographics';
        
        ob_start();
        include(VIEW_PATH . '/counts-outreach-events-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-counts-outreach-events-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }
    
    public function displayCountsFamily()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $statusCounts = $counts->getStatusCounts();
        $FollowupsCounts = $counts->getFollowupsCounts();
        $whoCalledCounts = $counts->getWhoCalledCounts();
        $raceCounts = $counts->getRaceCounts();
        $ethnicityCounts = $counts->getEthnicityCounts();
        $callReasonCounts = $counts->getCallReasonCounts();
        $howHeardCounts = $counts->getHowHeardCounts();
        $closeReasonCounts = $counts->getReasonForClosingCounts();
        $recurringFamilyCounts = $counts->getRecurringFamilyCounts();

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-family.phtml');
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

    public function printCountsFamily()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();
        $counts = new Counts('family');
        $counts->set('_filters', $filters);
        $statusCounts = $counts->getStatusCounts();
        $whoCalledCounts = $counts->getWhoCalledCounts();
        $raceCounts = $counts->getRaceCounts();
        $ethnicityCounts = $counts->getEthnicityCounts();
        $callReasonCounts = $counts->getCallReasonCounts();
        $howHeardCounts = $counts->getHowHeardCounts();
        $recurringFamilyCounts = $counts->getRecurringFamilyCounts();
$closeReasonCounts = $counts->getReasonForClosingCounts();
        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();
        
        $reportTitle = 'Family Demographics';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/counts-family-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportCountsFamily()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts('family');
        $counts->set('_filters', $filters);
        $statusCounts = $counts->getStatusCounts();
        $whoCalledCounts = $counts->getWhoCalledCounts();
        $raceCounts = $counts->getRaceCounts();
        $ethnicityCounts = $counts->getEthnicityCounts();
        $callReasonCounts = $counts->getCallReasonCounts();
        $howHeardCounts = $counts->getHowHeardCounts();
        $recurringFamilyCounts = $counts->getRecurringFamilyCounts();
        $closeReasonCounts = $counts->getReasonForClosingCounts();
        // Used for displaying family selects
        $family         = new Family();
        $setting        = new Setting();
        $status         = new Setting('status');
        $county         = new Setting('county');
        $region         = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        ob_start();
        include(VIEW_PATH . '/counts-family-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-counts-families-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }

    public function displayCountsChild()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $childStatusCounts = $counts->getChildStatusCounts();
        $childIssueCounts = $counts->getChildIssueCounts();
        $childAgeCounts = $counts->getChildAgeCounts();
        $childAgeCountsAtStart = $counts->getChildAgeCountsAtStart();
        $recurringChildCounts = $counts->getRecurringChildCounts();
        $childCountyCounts = $counts->getChildCountyCounts();
        $successfulReferralCounts = $counts->getSuccessfulReferralCounts();
        
        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-child.phtml');
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

    public function printCountsChild()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $childStatusCounts = $counts->getChildStatusCounts();
        $childIssueCounts = $counts->getChildIssueCounts();
        $childAgeCounts = $counts->getChildAgeCounts();
        $childAgeCountsAtStart = $counts->getChildAgeCountsAtStart();
        $recurringChildCounts = $counts->getRecurringChildCounts();
        $childCountyCounts = $counts->getChildCountyCounts();
        $successfulReferralCounts = $counts->getSuccessfulReferralCounts();

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        $reportTitle = 'Child Demographics';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/counts-child-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportCountsChild()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $childStatusCounts = $counts->getChildStatusCounts();
        $childIssueCounts = $counts->getChildIssueCounts();
        $childAgeCounts = $counts->getChildAgeCounts();
        $childAgeCountsAtStart = $counts->getChildAgeCountsAtStart();
        $recurringChildCounts = $counts->getRecurringChildCounts();
        $childCountyCounts = $counts->getChildCountyCounts();
        $successfulReferralCounts = $counts->getSuccessfulReferralCounts();

        // Used for displaying family selects
        $family         = new Family();
        $setting        = new Setting();
        $status         = new Setting('status');

        $schoolDistrict = new SchoolDistrict();

        ob_start();
        include(VIEW_PATH . '/counts-child-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-counts-children-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }

    public function displayCountsScreenings()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $screeningsCounts = $counts->getScreeningCounts(true);
        //echo "<pre>";print_r($screeningsCounts);die;
        $asqFrequencyCounts = $counts->getAsqFrequencyCounts();
        $asqSeCounts = $counts->getAsqSeCounts();
        $asq3Counts = $counts->getAsq3Counts();
        $asq3DomainCounts = $counts->getAsq3DomainCounts();

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-screening.phtml');
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

    public function printCountsScreenings()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $screeningsCounts = $counts->getScreeningCounts(true);
        $asqFrequencyCounts = $counts->getAsqFrequencyCounts();
        $asqSeCounts = $counts->getAsqSeCounts();
        $asq3Counts = $counts->getAsq3Counts();
        $asq3DomainCounts = $counts->getAsq3DomainCounts();

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();
        
        $reportTitle = 'Developmental Screenings';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/counts-screening-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportCountsScreenings()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $screeningsCounts = $counts->getScreeningCounts(true);
        $asqFrequencyCounts = $counts->getAsqFrequencyCounts();
        $asqSeCounts = $counts->getAsqSeCounts();
        $asq3Counts = $counts->getAsq3Counts();
        $asq3DomainCounts = $counts->getAsq3DomainCounts();

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        ob_start();
        include(VIEW_PATH . '/counts-screening-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-counts-screening-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }

    public function displayCountsFamilyscreenings()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $screeningsCounts = $counts->getFamilyScreeningCounts(true);        
        $asqFrequencyCounts = $counts->getFamilyAsqFrequencyCounts();        
        $asqSeCounts = $counts->getFamilyAsqSeCounts();

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-family-screening.phtml');
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

    public function printCountsFamilyscreenings()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $screeningsCounts = $counts->getFamilyScreeningCounts(true);        
        $asqFrequencyCounts = $counts->getFamilyAsqFrequencyCounts();
        $asqSeCounts = $counts->getFamilyAsqSeCounts();
        

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();
        
        $reportTitle = 'Family Screenings';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/counts-family-screening-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportCountsFamilyscreenings()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $screeningsCounts = $counts->getFamilyScreeningCounts(true);        
        $asqFrequencyCounts = $counts->getFamilyAsqFrequencyCounts();
        $asqSeCounts = $counts->getFamilyAsqSeCounts();
        

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        ob_start();
        include(VIEW_PATH . '/counts-family-screening-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-counts-family-screening-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }


    public function displayCountsReferrals()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $referralCounts = $counts->getReferralCounts();
        $familyReferralCounts = $counts->getFamilyReferralCounts();
        $childReferralCounts = $counts->getChildReferralCounts();
        $outcomeCounts = $counts->getOutcomesCounts();
        $agencyReferrals = $counts->getAgencyReferrals();
        $referralIssueCounts = $counts->getReferralIssueCounts();
        $gapCounts = $counts->getGapCounts();
        $barrierCounts = $counts->getBarrierCounts();
        $providerReferralCounts = $counts->getProviderReferralCounts();
        

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-referrals.phtml');
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

    public function printCountsReferrals()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $referralCounts = $counts->getReferralCounts();
        $familyReferralCounts = $counts->getFamilyReferralCounts();
        $childReferralCounts = $counts->getChildReferralCounts();
        $outcomeCounts = $counts->getOutcomesCounts();
        $agencyReferrals = $counts->getAgencyReferrals();
        $referralIssueCounts = $counts->getReferralIssueCounts();
        $gapCounts = $counts->getGapCounts();
        $barrierCounts = $counts->getBarrierCounts();
        $providerReferralCounts = $counts->getProviderReferralCounts();

        // Used for displaying family selects
        $family         = new Family();
        $status         = new Setting('status');
        $setting        = new Setting();

        $schoolDistrict = new SchoolDistrict();
        
        $reportTitle = 'Outgoing Referrals';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/counts-referrals-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportCountsReferrals()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $referralCounts = $counts->getReferralCounts();
        $familyReferralCounts = $counts->getFamilyReferralCounts();
        $childReferralCounts = $counts->getChildReferralCounts();
        $outcomeCounts = $counts->getOutcomesCounts();
        $agencyReferrals = $counts->getAgencyReferrals();
        $referralIssueCounts = $counts->getReferralIssueCounts();
        $gapCounts = $counts->getGapCounts();
        $barrierCounts = $counts->getBarrierCounts();
        $providerReferralCounts = $counts->getProviderReferralCounts();

        // Used for displaying family selects
        $family         = new Family();
        $status         = new Setting('status');
        $setting        = new Setting();

        $schoolDistrict = new SchoolDistrict();

        ob_start();
        include(VIEW_PATH . '/counts-referrals-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-counts-referrals-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }

    public function displayCountsAccesspoint()
    {
        $filterHelper = new FilterHelper(); //021116
        $filters = $filterHelper->getCountFilters(); //021116

        $filters['school_district'] = (isset($_SESSION['count']['filters']['school_district']) ? $_SESSION['count']['filters']['school_district'] : '');
        $filters['zip'] = (isset($_SESSION['count']['filters']['zip']) ? $_SESSION['count']['filters']['zip'] : '');
        $filters['status'] = (isset($_SESSION['count']['filters']['status']) ? $_SESSION['count']['filters']['status'] : '');
        $filters['start_date'] = (isset($_SESSION['count']['filters']['start_date']) ? $_SESSION['count']['filters']['start_date'] : '');
        $filters['end_date'] = (isset($_SESSION['count']['filters']['end_date']) ? $_SESSION['count']['filters']['end_date'] : '');

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $statusByWorkerCounts = $counts->getStatusByWorkerCounts();
        //$followUpByWorkerCounts = $counts->getFollowUpByWorkerCounts();
        $workerCCLevelCounts = $counts->getWorkerCCLevelCounts();
        $tasksByWorkerCounts = $counts->getTasksByWorkerCounts();
        //echo '<pre>'; var_dump($tasksByWorkerCounts); exit;

        // Used for displaying family selects
        $family         = new Family();
        $status         = new Setting('status');
        $setting        = new Setting();

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-access-point.phtml');
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

    public function printCountsAccesspoint()
    {
        $filterHelper = new FilterHelper(); //021116
        $filters = $filterHelper->getCountFilters(); //021116

        $filters['school_district'] = (isset($_SESSION['count']['filters']['school_district']) ? $_SESSION['count']['filters']['school_district'] : '');
        $filters['zip'] = (isset($_SESSION['count']['filters']['zip']) ? $_SESSION['count']['filters']['zip'] : '');
        $filters['status'] = (isset($_SESSION['count']['filters']['status']) ? $_SESSION['count']['filters']['status'] : '');
        $filters['start_date'] = (isset($_SESSION['count']['filters']['start_date']) ? $_SESSION['count']['filters']['start_date'] : '');
        $filters['end_date'] = (isset($_SESSION['count']['filters']['end_date']) ? $_SESSION['count']['filters']['end_date'] : '');

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $statusByWorkerCounts = $counts->getStatusByWorkerCounts();
        //$followUpByWorkerCounts = $counts->getFollowUpByWorkerCounts();
        $workerCCLevelCounts = $counts->getWorkerCCLevelCounts();
        $tasksByWorkerCounts = $counts->getTasksByWorkerCounts();
        //echo '<pre>'; var_dump($tasksByWorkerCounts); exit;

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        $reportTitle = 'Central Access Point';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/counts-access-point-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportCountsAccesspoint()
    {
        $filterHelper = new FilterHelper(); //021116
        $filters = $filterHelper->getCountFilters(); //021116

        $filters['school_district'] = (isset($_SESSION['count']['filters']['school_district']) ? $_SESSION['count']['filters']['school_district'] : '');
        $filters['zip'] = (isset($_SESSION['count']['filters']['zip']) ? $_SESSION['count']['filters']['zip'] : '');
        $filters['status'] = (isset($_SESSION['count']['filters']['status']) ? $_SESSION['count']['filters']['status'] : '');
        $filters['start_date'] = (isset($_SESSION['count']['filters']['start_date']) ? $_SESSION['count']['filters']['start_date'] : '');
        $filters['end_date'] = (isset($_SESSION['count']['filters']['end_date']) ? $_SESSION['count']['filters']['end_date'] : '');

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $statusByWorkerCounts = $counts->getStatusByWorkerCounts();
        //$followUpByWorkerCounts = $counts->getFollowUpByWorkerCounts();
        $workerCCLevelCounts = $counts->getWorkerCCLevelCounts();
        $tasksByWorkerCounts = $counts->getTasksByWorkerCounts();
        //echo '<pre>'; var_dump($tasksByWorkerCounts); exit;

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        ob_start();
        include(VIEW_PATH . '/counts-accesspoint-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-counts-central-access-point-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }

    public function displayCountsVolunteer()
    {

        $filters['school_district'] = (isset($_SESSION['count']['filters']['school_district']) ? $_SESSION['count']['filters']['school_district'] : '');
        $filters['zip'] = (isset($_SESSION['count']['filters']['zip']) ? $_SESSION['count']['filters']['zip'] : '');
        $filters['status'] = (isset($_SESSION['count']['filters']['status']) ? $_SESSION['count']['filters']['status'] : '');
        //$filters['region_id'] = (isset($_SESSION['count']['filters']['region_id']) ? $_SESSION['count']['filters']['region_id'] : '');

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $volunteerStatusCounts = $counts->getVolunteerStatusCounts();
        $volunteerSpecialtyCounts = $counts->getVolunteerSpecialtyCounts();
        $volunteerHourCounts = $counts->getVolunteerHourCounts();

        // Used for displaying family selects
        $family         = new Family();
        $status         = new Setting('status');
        $region         = new Setting('region');
        $setting        = new Setting();

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        $settingOb = new Setting(); //241016
        include(VIEW_PATH . '/counts-volunteer.phtml');
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

    public function printCountsVolunteer()
    {

        $filters['school_district'] = (isset($_SESSION['count']['filters']['school_district']) ? $_SESSION['count']['filters']['school_district'] : '');
        $filters['zip'] = (isset($_SESSION['count']['filters']['zip']) ? $_SESSION['count']['filters']['zip'] : '');
        $filters['status'] = (isset($_SESSION['count']['filters']['status']) ? $_SESSION['count']['filters']['status'] : '');

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $volunteerStatusCounts = $counts->getVolunteerStatusCounts();
        $volunteerSpecialtyCounts = $counts->getVolunteerSpecialtyCounts();
        $volunteerHourCounts = $counts->getVolunteerHourCounts();

        // Used for displaying family selects
        $family         = new Family();
        $status         = new Setting('status');
        $setting        = new Setting();

        $schoolDistrict = new SchoolDistrict();
        
        $reportTitle = 'Volunteer Information';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        $settingOb = new Setting(); //241016
        include(VIEW_PATH . '/counts-volunteer-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportCountsVolunteer()
    {

        $filters['school_district'] = (isset($_SESSION['count']['filters']['school_district']) ? $_SESSION['count']['filters']['school_district'] : '');
        $filters['zip'] = (isset($_SESSION['count']['filters']['zip']) ? $_SESSION['count']['filters']['zip'] : '');
        $filters['status'] = (isset($_SESSION['count']['filters']['status']) ? $_SESSION['count']['filters']['status'] : '');

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $volunteerStatusCounts = $counts->getVolunteerStatusCounts();
        $volunteerSpecialtyCounts = $counts->getVolunteerSpecialtyCounts();
        $volunteerHourCounts = $counts->getVolunteerHourCounts();

        // Used for displaying family selects
        $family         = new Family();
        $status         = new Setting('status');
        $setting        = new Setting();

        $schoolDistrict = new SchoolDistrict();

        ob_start();
        $settingOb = new Setting(); //241016
        include(VIEW_PATH . '/counts-volunteer-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-counts-volunteer-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }

    public function printPDFCountsVolunteer()
    {

        $filters['school_district'] = (isset($_SESSION['count']['filters']['school_district']) ? $_SESSION['count']['filters']['school_district'] : '');
        $filters['zip'] = (isset($_SESSION['count']['filters']['zip']) ? $_SESSION['count']['filters']['zip'] : '');
        $filters['status'] = (isset($_SESSION['count']['filters']['status']) ? $_SESSION['count']['filters']['status'] : '');

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $volunteerCounts = $counts->getVolunteerStatusCounts();
        $volunteerSpecialtyCounts = $counts->getVolunteerSpecialtyCounts();
        $volunteerHourCounts = $counts->getVolunteerHourCounts();

        $pdf = new \PdfMySqlTable();
        $pdf->setLogoImage($GLOBALS['pdfLogo']);
        $pdf->setFooterText($GLOBALS['footerText']);
        $prop=array(
            'HeaderColor'=>array(64, 49, 81),
            'color1'=>array(255,255,255),
            'color2'=>array(240, 240, 240),
            'width' => '120',
            'padding'=>2,
            'align' => 'L'
        );
        $pdf->AddPage();
        $pdf->PageSummary();
        $pdf->TableFromRows(array('status' => 'Status', 'cnt' => 'Count', 'percent' => 'Percent'), $volunteerCounts, $prop);
        $pdf->ln();
        $pdf->TableFromRows(array('name' => 'Specialty', 'cnt' => 'Count', 'percent' => 'Percent'), $volunteerSpecialtyCounts, $prop);
        //echo '<pre>'; var_dump($volunteerSpecialtyCounts); exit;
        $pdf->ln();
        $pdf->TableFromRows(array('name' => 'Hours', 'cnt' => 'Count', 'percent' => 'Percent'), $volunteerHourCounts, $prop);
        $pdf->Output();
    }

    public function displayCountsProvider()
    {
        $filterHelper = new FilterHelper(); //021116
        $filters = $filterHelper->getCountFilters(); //021116

        $filters['school_district'] = (isset($_SESSION['count']['filters']['school_district']) ? $_SESSION['count']['filters']['school_district'] : '');
        $filters['zip'] = (isset($_SESSION['count']['filters']['zip']) ? $_SESSION['count']['filters']['zip'] : '');
        $filters['status'] = (isset($_SESSION['count']['filters']['status']) ? $_SESSION['count']['filters']['status'] : '');
        $filters['region_id'] = (isset($_SESSION['count']['filters']['region_id']) ? $_SESSION['count']['filters']['region_id'] : '');
        $filters['start_date'] = (isset($_SESSION['count']['filters']['start_date']) ? $_SESSION['count']['filters']['start_date'] : '');
        $filters['end_date'] = (isset($_SESSION['count']['filters']['end_date']) ? $_SESSION['count']['filters']['end_date'] : '');

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $providerFaxCounts = $counts->getProviderFaxCounts();
        $providerRoleCounts = $counts->getProviderRoleCounts();
        $providerHowHeardCounts = $counts->getProviderHowHeardCounts();
        $providerMostReferralCounts = $counts->getProviderMostReferralCounts();

        //echo "<pre>";print_r($providerHowHeardCounts);die;

        // Used for displaying family selects
        $family         = new Family();
        $status         = new Setting('status');
        $region         = new Setting('region');
        $setting        = new Setting();

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-provider.phtml');
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

    public function printCountsProvider()
    {
        $filterHelper = new FilterHelper(); //021116
        $filters = $filterHelper->getCountFilters(); //021116

        $filters['school_district'] = (isset($_SESSION['count']['filters']['school_district']) ? $_SESSION['count']['filters']['school_district'] : '');
        $filters['zip'] = (isset($_SESSION['count']['filters']['zip']) ? $_SESSION['count']['filters']['zip'] : '');
        $filters['status'] = (isset($_SESSION['count']['filters']['status']) ? $_SESSION['count']['filters']['status'] : '');
        $filters['start_date'] = (isset($_SESSION['count']['filters']['start_date']) ? $_SESSION['count']['filters']['start_date'] : '');
        $filters['end_date'] = (isset($_SESSION['count']['filters']['end_date']) ? $_SESSION['count']['filters']['end_date'] : '');

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $providerFaxCounts = $counts->getProviderFaxCounts();
        $providerRoleCounts = $counts->getProviderRoleCounts();
        $providerHowHeardCounts = $counts->getProviderHowHeardCounts();
        $providerMostReferralCounts = $counts->getProviderMostReferralCounts();

        // Used for displaying family selects
        $family         = new Family();
        $status         = new Setting('status');
        $setting        = new Setting();

        $schoolDistrict = new SchoolDistrict();
        
        $reportTitle = 'Provider Information';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/counts-provider-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportCountsProvider()
    {
        $filterHelper = new FilterHelper(); //021116
        $filters = $filterHelper->getCountFilters(); //021116

        $filters['school_district'] = (isset($_SESSION['count']['filters']['school_district']) ? $_SESSION['count']['filters']['school_district'] : '');
        $filters['zip'] = (isset($_SESSION['count']['filters']['zip']) ? $_SESSION['count']['filters']['zip'] : '');
        $filters['status'] = (isset($_SESSION['count']['filters']['status']) ? $_SESSION['count']['filters']['status'] : '');
        $filters['start_date'] = (isset($_SESSION['count']['filters']['start_date']) ? $_SESSION['count']['filters']['start_date'] : '');
        $filters['end_date'] = (isset($_SESSION['count']['filters']['end_date']) ? $_SESSION['count']['filters']['end_date'] : '');

        $counts = new Counts();
        $counts->set('_filters', $filters);
        $providerFaxCounts = $counts->getProviderFaxCounts();
        $providerRoleCounts = $counts->getProviderRoleCounts();
        $providerHowHeardCounts = $counts->getProviderHowHeardCounts();
        $providerMostReferralCounts = $counts->getProviderMostReferralCounts();

        // Used for displaying family selects
        $family         = new Family();
        $status         = new Setting('status');
        $setting        = new Setting();

        $schoolDistrict = new SchoolDistrict();

        ob_start();
        include(VIEW_PATH . '/counts-provider-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-provider-counts-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }

    public function displayCountsIndicators()
    {
        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-indicators.phtml');
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

    public function displayCountsIndicatorsDemographics()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);

        $childStatusCounts = $counts->getChildStatusCounts();
        $recurringChildCounts = $counts->getRecurringChildCounts();
        $childAgeCountsAtStart = $counts->getChildAgeCountsAtStart();
        $whoCalled = $counts->getWhoCalledCounts();
        $howHeardCounts = $counts->getHowHeardCounts();

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-indicators-demographics.phtml');
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

    public function printCountsIndicatorsDemographics()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);

        $childStatusCounts = $counts->getChildStatusCounts();
        $recurringChildCounts = $counts->getRecurringChildCounts();
        $childAgeCountsAtStart = $counts->getChildAgeCountsAtStart();
        $whoCalled = $counts->getWhoCalledCounts();
        $howHeardCounts = $counts->getHowHeardCounts();

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();
        
        $reportTitle = 'Common Indicators: Help Me Grow Demographics';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/counts-indicators-demographics-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportCountsIndicatorsDemographics()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);

        $childStatusCounts = $counts->getChildStatusCounts();
        $recurringChildCounts = $counts->getRecurringChildCounts();
        $childAgeCountsAtStart = $counts->getChildAgeCountsAtStart();
        $whoCalled = $counts->getWhoCalledCounts();
        $howHeardCounts = $counts->getHowHeardCounts();

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        ob_start();
        include(VIEW_PATH . '/counts-indicators-demographics-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-indicators-demographics-counts-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }

    public function displayCountsIndicatorsNatureIssues()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);

        $childIssueCounts = $counts->getChildIssueCounts();
        $childReferralCounts = $counts->getChildReferralCounts();
        $basedOnScreening = true;
        $childReferralScreeningCounts = $counts->getChildReferralCounts($basedOnScreening);

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-indicators-issues.phtml');
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

    public function printCountsIndicatorsNatureIssues()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);

        $childIssueCounts = $counts->getChildIssueCounts();
        $childReferralCounts = $counts->getChildReferralCounts();
        $basedOnScreening = true;
        $childReferralScreeningCounts = $counts->getChildReferralCounts($basedOnScreening);

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        $reportTitle = 'Nature of Presenting Issues/Concerns';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/counts-indicators-issues-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportCountsIndicatorsNatureIssues()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);

        $childIssueCounts = $counts->getChildIssueCounts();
        $childReferralCounts = $counts->getChildReferralCounts();
        $basedOnScreening = true;
        $childReferralScreeningCounts = $counts->getChildReferralCounts($basedOnScreening);

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        ob_start();
        include(VIEW_PATH . '/counts-indicators-issues-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-indicators-issues-counts-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }

    public function displayCountsIndicatorsProgramReferrals()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);

        $referralServiceCounts = $counts->getReferralServiceCounts();
        $gapCounts = $counts->getGapCounts();
        $barrierCounts = $counts->getBarrierCounts();

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-indicators-program-referrals.phtml');
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

    public function printCountsIndicatorsProgramReferrals()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);

        $referralServiceCounts = $counts->getReferralServiceCounts();
        $gapCounts = $counts->getGapCounts();
        $barrierCounts = $counts->getBarrierCounts();

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();
        
        $reportTitle = 'Common Indicators: Referrals by Help Me Grow to Service Programs';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/counts-indicators-program-referrals-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportCountsIndicatorsProgramReferrals()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);

        $referralServiceCounts = $counts->getReferralServiceCounts();
        $gapCounts = $counts->getGapCounts();
        $barrierCounts = $counts->getBarrierCounts();

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        ob_start();
        include(VIEW_PATH . '/counts-indicators-program-referrals-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-indicators-program-referrals-counts-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }

    public function displayCountsIndicatorsOutcome()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);

        $childReferralOutcomes = $counts->getChildReferralOutcomeCounts();

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts-indicators-outcome.phtml');
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

    public function printCountsIndicatorsOutcome()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);

        $childReferralOutcomes = $counts->getChildReferralOutcomeCounts();

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();
        
        $reportTitle = 'Help Me Grow Outcomes';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/counts-indicators-outcome-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportCountsIndicatorsOutcome()
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getCountFilters();

        $counts = new Counts();
        $counts->set('_filters', $filters);

        $childReferralOutcomes = $counts->getChildReferralOutcomeCounts();

        // Used for displaying family selects
        $family  = new Family();
        $setting = new Setting();
        $status  = new Setting('status');
        $county  = new Setting('county');
        $region  = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        ob_start();
        include(VIEW_PATH . '/counts-indicators-outcome-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-indicators-outcome-counts-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }

    public function displayLinks()
    {
        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/counts.phtml');
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
}
