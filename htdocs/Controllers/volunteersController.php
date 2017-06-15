<?php

namespace Hmg\Controllers;

use Hmg\Models\Volunteers;
use Hmg\Models\Volunteer;
use Hmg\Models\Setting;

class VolunteersController
{
    public function __construct()
    {
        $volunteers= new Volunteers();
        $filters = '';
        $sorts = array();

        $search = '';
        if (isset($_REQUEST['clearFilters'])) {
            unset($_REQUEST['filters'], $_SESSION['volunteers']['filters'], $_SESSION['totalVolunteers'], $_SESSION['volunteers']);
        }

        if (isset($_REQUEST['filters'])) {
            $_SESSION['volunteers']['search'] = (isset($_REQUEST['search']) ? $_REQUEST['search'] : '');
            $volunteers->set('_search', $_SESSION['volunteers']['search']);
            $_SESSION['volunteers']['filters'] = $_REQUEST['filters'];
            $volunteers->set('_filters', $_REQUEST['filters']);
        } else if (isset($_SESSION['volunteers']['filters'])) {
            $search = (isset($_SESSION['volunteers']['search']) ? $_SESSION['volunteers']['search'] : '');
            $volunteers->set('_search', ($search));
            $volunteers->set('_filters', $_SESSION['volunteers']['filters']);
        }

        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page']) && $_REQUEST['page']) {
            $volunteers->set('_start', ($_REQUEST['page'] - 1) * 50);
        } else {
            $volunteers->set('_start', 0);
        }
        if (isset($_REQUEST['field']) && $_REQUEST['field'] && isset($_REQUEST['sort']) && $_REQUEST['sort']) {
            $sorts[$_REQUEST['field']] = $_REQUEST['sort'];
            $volunteers->set('_sorts', $sorts);
            
           
        }
        $totalVolunteers = $volunteers->getCount();
        $message = isset($_REQUEST['message']) ? $_REQUEST['message'] : '';
        if (isset($_REQUEST['print'])) {
            $volunteers->set('_limit', 0); // remove limits
            $this->printVolunteersList($volunteers->getList(), $totalVolunteers, $page, $filters, $sorts, $message);
        } else if (isset($_REQUEST['export'])) {
            $volunteers->set('_limit', 0); // remove limits
            $this->exportVolunteersList($volunteers->getList(), $totalVolunteers, $page, $filters, $sorts, $message);
        } else {
            $this->displayVolunteersList($volunteers->getList(), $totalVolunteers, $page, $filters, $sorts, $message);
        }
    }

    public function displayVolunteersList($volunteers, $totalVolunteers, $page, $filters, $sorts, $message)
    {
        include(VIEW_PATH . '/adminnav.phtml');
        $volunteer= new Volunteer();


        $filters['status'] = (isset($_SESSION['volunteers']['filters']['status']) ? $_SESSION['volunteers']['filters']['status'] : '');
        $filters['area'] = (isset($_SESSION['volunteers']['filters']['area']) ? $_SESSION['volunteers']['filters']['area'] : '');

        $numVolunteers = count($volunteers);
        $numPages = ceil($totalVolunteers / 50);
        $pageNumber = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord = $firstRecord + $numVolunteers - 1;

        $settingOb = new Setting(); //241016
        $volunteering_type = array();
        $volunteerings = new Setting('volunteering_type'); //241016
        if(!empty($volunteerings)) { //211016
            foreach($volunteerings->settings as $type) {
                $volunteering_type[$type['id']] = $type['name'];
            }
        }

        ob_start();
        include(VIEW_PATH . '/volunteers-list.phtml');
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

    public function printVolunteersList($volunteers, $totalVolunteers, $page, $filters, $sorts, $message)
    {
        include(VIEW_PATH . '/adminnav.phtml');
        $volunteer= new Volunteer();


        $filters['status'] = (isset($_SESSION['volunteers']['filters']['status']) ? $_SESSION['volunteers']['filters']['status'] : '');
        $filters['area'] = (isset($_SESSION['volunteers']['filters']['area']) ? $_SESSION['volunteers']['filters']['area'] : '');

        $numVolunteers = count($volunteers);
        $numPages = ceil($totalVolunteers / 50);
        $pageNumber = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord = $firstRecord + $numVolunteers - 1;

        ob_start();
        include(VIEW_PATH . '/volunteers-list-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportVolunteersList($volunteers, $totalVolunteers, $page, $filters, $sorts, $message)
    {
        include(VIEW_PATH . '/adminnav.phtml');
        $volunteer= new Volunteer();

        $filters['status'] = (isset($_SESSION['volunteers']['filters']['status']) ? $_SESSION['volunteers']['filters']['status'] : '');
        $filters['area'] = (isset($_SESSION['volunteers']['filters']['area']) ? $_SESSION['volunteers']['filters']['area'] : '');

        $numVolunteers = count($volunteers);
        $numPages = ceil($totalVolunteers / 50);
        $pageNumber = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord = $firstRecord + $numVolunteers - 1;
        $settingOb = new Setting(); //241016

        ob_start();
        include(VIEW_PATH . '/volunteers-list-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-volunteers-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }
}
