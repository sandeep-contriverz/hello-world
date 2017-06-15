<?php

namespace Hmg\Controllers;

use Hmg\Models\FollowUps;
use Hmg\Models\Setting;
use Hmg\Models\Family;
use Hmg\Helpers\SessionHelper as FilterHelper;

class FollowUpsController
{
    public function __construct()
    {

        $_SESSION['list-type'] = 'follow-ups-list';

        $followUps = new FollowUps();
        $sort = '';
        $field = '';
        $sorts = array();
        $setting = new Setting();

        if (!empty($_REQUEST['field'])  && !empty($_REQUEST['sort'])) {
            $sort = $_REQUEST['sort'];
            $field = $_REQUEST['field'];
            $sorts[$field] = $sort;
            $_SESSION['follow-up-sorts'] = $sorts; // store sorts
            $followUps->set('_sorts', $sorts);
        }
        $search = '';
        if (isset($_REQUEST['clearFilters'])) {
            unset(
                $_REQUEST['filters'],
                $_SESSION['followUps']['filters'],
                $_SESSION['totalFollowUps'],
                $_SESSION['follow-up-sorts'], $_SESSION['followUps']
            );
        }
        if (isset($_REQUEST['filters'])) {
            $_SESSION['followUps']['search'] = (isset($_REQUEST['search']) ? $_REQUEST['search'] : '');
            $followUps->set('_search', $_SESSION['followUps']['search']);
            $_SESSION['followUps']['filters'] = $_REQUEST['filters'];
            $followUps->set('_filters', $_REQUEST['filters']);
        } else if (isset($_SESSION['followUps']['filters'])) {
            $search = (isset($_SESSION['followUps']['search']) ? $_SESSION['followUps']['search'] : '');
            $followUps->set('_search', ($search));
            $followUps->set('_filters', $_SESSION['followUps']['filters']);
            if (isset($_SESSION['follow-up-sorts'])) {
                $sorts = $_SESSION['follow-up-sorts'];
            }
            $followUps->set('_sorts', $sorts);
        } else {
            $_SESSION['followUps']['filters'] = array(
                'hmg_worker'          => $_SESSION['user']['hmg_worker'],
                'language_id'        => '',
                'status'             => '',
                'follow_up_task'     => '',
                'start_date'         => '',
                'end_date'           => '',
                'done'                 => '0'
            );
            $followUps->set('_filters', $_SESSION['followUps']['filters']);
        }

        $totalFollowUps = $followUps->getCount($setting);

        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page']) && $_REQUEST['page']) {
            $followUps->set('_start', ($_REQUEST['page'] - 1) * 50);
        } else {
            $followUps->set('_start', 0);
        }
        if (isset($_REQUEST['field']) && $_REQUEST['field'] && isset($_REQUEST['sort']) && $_REQUEST['sort']) {
            $sorts[$_REQUEST['field']] =     $_REQUEST['sort'];
            $followUps->set('_sorts', $sorts);
        }

        $message = isset($_REQUEST['message']) ? $_REQUEST['message'] : '';

        $filters = (isset($_SESSION['followUps']['filters']) ? $_SESSION['followUps']['filters'] : array());

        if (isset($_REQUEST['print'])) {
            $followUps->set('_limit', 0); // remove limits
            $this->printFollowUpsList($followUps->getList($setting), $totalFollowUps, $page, $sorts, $message);
        } else {
            $this->displayFollowUpsList($followUps->getList($setting), $totalFollowUps, $page, $sorts, $message);
        }
    }

    public function displayFollowUpsList($followUps, $totalFollowUps, $page, $sorts, $message)
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getFollowUpFilters();
          
        $family = new Family();

        $setting       = new Setting();
        $relationships = new Setting('relationships');
        $language      = new Setting('language');
        $familyHeard   = new Setting('how_heard_category');
        $callReason    = new Setting('call_reason');
        $race          = new Setting('race');
        $status        = new Setting('status');
        $ethnicity     = new Setting('ethnicity');
        $followUpTask  = new Setting('follow_up_task');
		
		

        include(VIEW_PATH . '/adminnav.phtml');

        $numFollowUps = count($followUps);
        $numPages = ceil($totalFollowUps / 50);
        $pageNumber = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord = $firstRecord + $numFollowUps - 1;

        ob_start();
        include(VIEW_PATH . '/follow-ups-list.phtml');
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
        $filters = $filterHelper->getFollowUpFilters();

        $family = new Family();

        $setting       = new Setting();
        $relationships = new Setting('relationships');
        $language      = new Setting('language');
        $familyHeard   = new Setting('how_heard_category');
        $callReason    = new Setting('call_reason');
        $race          = new Setting('race');
        $status        = new Setting('status');
        $ethnicity     = new Setting('ethnicity');
        $followUpTask  = new Setting('follow_up_task');

        $numFollowUps = count($followUps);
        $numPages = ceil($totalFollowUps / 50);
        $pageNumber = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord = $firstRecord + $numFollowUps - 1;
        
        $reportTitle = 'Help Me Grow Utah List of Follow Ups';

        ob_start();
        include(VIEW_PATH . '/print-header.phtml');
        $headerHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/follow-ups-list-print.phtml');
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
