<?php

namespace Hmg\Controllers;

use Hmg\Models\UserPasswordHistory;
use Hmg\Models\UserLogs;

class LoginController
{

    public function __construct($user = null, $password = null)
    {
        if ($user) {
            $validPassword = $user->validatePassword($password);
            $user->login();
            $userInfo = $user->getAll();
            if ($user->loggedIn) {
                $log = array(
                    'user_id' => $userInfo['id'],
                    'login_date' => date('Y-m-d H:i:s')
                );
                $userLogs = new UserLogs($log);
                $userLogs->save();
                $_SESSION['expireTime'] = strtotime('+20 minutes');
                $_SESSION['user'] = $userInfo;
                if (empty($_SESSION['user']['region_id'])) {
                    unset($_SESSION['user']['region_id']);
                }
                $validPassword = $this->checkExpiredPassword();
                if ($validPassword['valid']) {
                    $this->displayLoggedIn($userInfo);
                } else {
                    $errors = '';
                    foreach ($validPassword['errors'] as $key => $error) {
                        $errors .= '&errors[' . $key . ']=' . $error;
                    }
                    header('Location: index.php?action=user&id=' . $userInfo['id'] . $errors);
                }
            } else {
                $this->displayLogin('Incorrect User Name/Password.');
            }
        }
    }

    protected function checkExpiredPassword()
    {
        $validPassword['valid'] = true;
        $validPassword['errors'][] = null;
        $passHistory = new UserPasswordHistory();
        $lastPassword = $passHistory->getLastPasswordByUserId($_SESSION['user']['id']);
        $_SESSION['expiredPassword'] = false;
        if (isset($lastPassword['create_date'])) {
            if ($lastPassword['create_date'] < date('Y-m-d H:i:s', strtotime('-6 month'))) {
                $_SESSION['expiredPassword'] = true;
                $validPassword['valid'] = false;
                $validPassword['errors'][] = 'Your password is expired because it is more than 6 months old.';
            }
        }

        return $validPassword;
    }

    public function checkLoggedIn()
    {
        $loggedIn = false;

        if (!empty($_SESSION['expireTime'])
                && ($_SESSION['expireTime'] - time() > 0)
        ) {
            $loggedIn = true;
        }

        // If logged in
        if ($loggedIn && $this->hasActionPermission() && ! $_SESSION['expiredPassword']) {
            $_SESSION['expireTime'] = strtotime('+20 minutes');
            return true;
        } else if ($loggedIn && ! $_SESSION['expiredPassword']) {
            $this->displayLoggedIn($_SESSION['user'], '<b>PERMISSION ERROR:</b> You do not have access to this module.');
            exit;
        } else if (! empty($_SESSION['expiredPassword'])) {
            if ($GLOBALS['action'] != 'user') {
                $errors = '&errors[0]=Your password is expired because it is more than 6 months old.';
                header('Location: index.php?action=user&id=' . $_SESSION['user']['id'] . $errors);
                exit;
            }
        } else {
            $this->displayLogin();
            exit();
        }
    }

    public function hasActionPermission()
    {

        $permission = (!empty($_SESSION['user']['permission']) ? $_SESSION['user']['permission'] : 'Read Only');

        switch($permission) {

            case 'Admin':
                $actions = array(
                    '',
                    'login',
                    'logout',
                    'families',
                    'family',
                    'child',
                    'child-referral',
                    'child-follow-up',
                    'child-prior-resource',
                    'child-developmental-screening',
                    'family-screening',
                    'screening-attachment',
                    'family-screening-attachment',
                    'screening-attachments',
                    'family-screening-attachments',
                    'screenings-file',
                    'family-screenings-file',
                    'providers',
                    'provider',
                    'organization-start-end',
                    'family-provider',
                    'family-start-end',
                    'family-start-ends',
                    'family-attachments',
                    'volunteers',
                    'volunteer',
                    'volunteering',
                    'counts',
                    'users',
                    'user',
                    'settings',
                    'setting',
                    'referral-services',
                    'note',
                    'child-note',
                    'letter',
                    'family-referral',
                    'family-follow-up',
                    'follow-ups',
                    'file',
                    'reports',
                    'zip',
                    'refresh-session',
                    'region-counties',
                    'organizations',
                    'organization',
                    'contact',
                    'event',
					'organization-follow-up',
					'organization-follow-ups',
                    'organization-note',
                    'contact-note',
                    'contact-referral',
                    'contact-follow-up',
					'event-file-attachment',
					'organization-attachments',
					'organization-file',
					'organization-event-attachments',
                    'contact-attachments',
                    'import',
                    'report',
                    'referral-org',
                    'city'
                );
                break;

            case 'Edit':
                $actions = array(
                    '',
                    'login',
                    'logout',
                    'families',
                    'family',
                    'child',
                    'child-referral',
                    'child-follow-up',
                    'child-prior-resource',
                    'child-developmental-screening',
                    'family-screening',
                    'screening-attachment',
                    'family-screening-attachment',
                    'screening-attachments',
                    'family-screening-attachments',
                    'screenings-file',
                    'family-screenings-file',
                    'providers',
                    'provider',
                    'organization-start-end',
                    'family-provider',
                    'family-start-end',
                    'family-start-ends',
                    'family-attachments',
                    'volunteers',
                    'volunteer',
                    'volunteering',
                    'counts',
                    'settings',
                    'referral-services',
                    'note',
                    'child-note',
                    'letter',
                    'family-referral',
                    'family-follow-up',
                    'follow-ups',
                    'file',
                    'reports',
                    'zip',
                    'refresh-session',
                    'region-counties',
                    'organizations',
                    'organization',
                    'contact',
                    'event',
                    'organization-follow-up',
                    'organization-follow-ups',
                    'organization-note',
                    'contact-note',
                    'contact-referral',
                    'contact-follow-up',
                    'event-file-attachment',
                    'organization-attachments',
                    'organization-file',
                    'organization-event-attachments',
                    'contact-attachments',
                    'report',
                    'referral-org'					
                );
                break;

            case 'Read Only':
            default:
                $actions = array(
                    '',
                    'login',
                    'logout',
                    'counts',
                    'refresh-session'
                );
        }

        $actionPermission = in_array($GLOBALS['action'], $actions);

        return $actionPermission;

    }

    public function displayLogin($error = null)
    {
        $nav = '';
        ob_start();
        include(VIEW_PATH . '/login.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // ob_start();
        // include(VIEW_PATH . '/threecolumn.phtml');
        // $viewHtml = ob_get_contents();
        // ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . SITE_TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function displayLoggedIn($user, $err = null)
    {
        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/welcome.phtml');
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
