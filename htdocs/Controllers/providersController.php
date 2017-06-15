<?php

namespace Hmg\Controllers;

use Hmg\Models\Providers;
use Hmg\Models\Provider;
use Hmg\Models\Setting;

class ProvidersController
{
    public function __construct()
    {
        $providers = new Providers();
        $filters = '';
        $sorts = array();

        $search = '';
        if (isset($_REQUEST['clearFilters'])) {
            unset($_REQUEST['filters'], $_SESSION['totalProviders'], $_SESSION['providers']);
        }

        if (isset($_REQUEST['filters'])) {
            $_SESSION['providers']['search'] = (isset($_REQUEST['search']) ? $_REQUEST['search'] : '');
            $providers->set('_search', $_SESSION['providers']['search']);
            $_SESSION['providers']['filters'] = $_REQUEST['filters'];
            $providers->set('_filters', $_REQUEST['filters']);
        } else if (isset($_SESSION['providers']['filters'])) {
            $search = (isset($_SESSION['providers']['search']) ? $_SESSION['providers']['search'] : '');
            $providers->set('_search', ($search));
            $providers->set('_filters', $_SESSION['providers']['filters']);
        }

        if (isset($_REQUEST['search'])) {
            if (isset($_REQUEST['searchType']) && $_REQUEST['searchType'] === 'clinic') {
                $this->displayClinicNameJson($providers->getClinicNamesAndIds($_REQUEST['search']));
            } else {
                $this->displayProviderNameJson($providers->getOrgContact($_REQUEST['search']));
            }
        } else {
            $totalProviders = $providers->getCount();
            $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
            if (isset($_REQUEST['page']) && $_REQUEST['page']) {
                $providers->set('_start', ($_REQUEST['page'] - 1) * 50);
            } else {
                $providers->set('_start', 0);
            }
            $sort = '';
            $field = '';
            $sorts = array();
            if (isset($_REQUEST['field']) && $_REQUEST['field'] && isset($_REQUEST['sort']) && $_REQUEST['sort']) {
                $sorts[$_REQUEST['field']] =     $_REQUEST['sort'];
                $providers->set('_sorts', $sorts);
            }
            $message = isset($_REQUEST['message']) ? $_REQUEST['message'] : '';
            if (isset($_REQUEST['print'])) {
                $providers->set('_limit', 0); // remove limits
                $this->printProvidersList($providers->getList(), $totalProviders, $page, $filters, $sorts, $message);
            } else if (isset($_REQUEST['export'])) {
                $providers->set('_limit', 0); // remove limits
                $this->exportProvidersList($providers->getList(), $totalProviders, $page, $filters, $sorts, $message);
            } else {
                $this->displayProvidersList($providers->getList(), $totalProviders, $page, $filters, $sorts, $message);
            }
        }
    }

    public function displayProvidersList($providers, $totalProviders, $page, $filters, $sorts, $message)
    {
        $provider = new Provider();

        $providerRole = new Setting('provider_role');

        include(VIEW_PATH . '/adminnav.phtml');

        $filters['title'] = (isset($_SESSION['providers']['filters']['title']) ? $_SESSION['providers']['filters']['title'] : '');
        $filters['city'] = (isset($_SESSION['providers']['filters']['city']) ? $_SESSION['providers']['filters']['city'] : '');
        $filters['role'] = (isset($_SESSION['providers']['filters']['role']) ? $_SESSION['providers']['filters']['role'] : '');

        $numProviders = count($providers);
        $numPages = ceil($totalProviders / 50);
        $pageNumber = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord = $firstRecord + $numProviders - 1;

        ob_start();
        include(VIEW_PATH . '/providers-list.phtml');
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

    public function displayProviderNameJson($list)
    {

        if (is_array($list)) {
            $json = json_encode($list);
            header('Content-Type: application/json');
            echo $json;
        }
    }

    public function displayClinicNameJson($list)
    {
        
        if (is_array($list)) {
            $json = json_encode($list);
            header('Content-Type: application/json');
            echo $json;
        }
    }

    public function printProvidersList($providers, $totalProviders, $page, $filters, $sorts, $message)
    {
        $provider = new Provider();

        $filters['title'] = (isset($_SESSION['providers']['filters']['title']) ? $_SESSION['providers']['filters']['title'] : '');
        $filters['city'] = (isset($_SESSION['providers']['filters']['city']) ? $_SESSION['providers']['filters']['city'] : '');
        $filters['role'] = (isset($_SESSION['providers']['filters']['role']) ? $_SESSION['providers']['filters']['role'] : '');

        $numProviders = count($providers);
        $numPages = ceil($totalProviders / 50);
        $pageNumber = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord = $firstRecord + $numProviders - 1;

        ob_start();
        include(VIEW_PATH . '/providers-list-print.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . PRINT_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportProvidersList($providers, $totalProviders, $page, $filters, $sorts, $message)
    {
        $provider = new Provider();
        include(VIEW_PATH . '/adminnav.phtml');

        $filters['title'] = (isset($_SESSION['providers']['filters']['title']) ? $_SESSION['providers']['filters']['title'] : '');
        $filters['city'] = (isset($_SESSION['providers']['filters']['city']) ? $_SESSION['providers']['filters']['city'] : '');
        $filters['role'] = (isset($_SESSION['providers']['filters']['role']) ? $_SESSION['providers']['filters']['role'] : '');

        $numProviders = count($providers);
        $numPages = ceil($totalProviders / 50);
        $pageNumber = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord = $firstRecord + $numProviders - 1;

        ob_start();
        include(VIEW_PATH . '/providers-list-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-providers-' . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }
}
