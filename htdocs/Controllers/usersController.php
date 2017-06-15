<?php

namespace Hmg\Controllers;

use Hmg\Models\Users;

class UsersController
{
    public function __construct()
    {

        $message = null;
        $filters = null;
        $sorts   = null;

        $users = new Users();
        $totalUsers = $users->getCount();
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page'])) {
            $users->set('_start', ($_REQUEST['page'] - 1) * 50);
        } else {
            $users->set('_start', 0);
        }
        $sort = '';
        $field = '';
        $sorts = array();
        if (isset($_REQUEST['field']) && $_REQUEST['field'] && isset($_REQUEST['sort']) && $_REQUEST['sort']) {
            $sort = $_REQUEST['sort'];
            $field = $_REQUEST['field'];
            $sorts[$field] =     $sort;
            $_SESSION['sorts'] = $sorts; // store sorts
            $users->set('_sorts', $sorts);
        }
        if (isset($_REQUEST['message'])) {
            $message = $_REQUEST['message'];
        }

        $this->displayUserList($users->getList(), $totalUsers, $page, $filters, $sorts, $message);
    }

    public function displayUserList($users, $totalUsers, $page, $filters, $sorts, $message)
    {
        include(VIEW_PATH . '/adminnav.phtml');

        $numUsers = count($users);
        $numPages = ceil($totalUsers / 50);
        $pageNumber = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord = $firstRecord + $numUsers - 1;
        $field = '';
        $sort = '';
        if ($sorts) {
            $field = key($sorts);
            $sort = $sorts[$field];
        }

        ob_start();
        include(VIEW_PATH . '/user-list.phtml');
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
