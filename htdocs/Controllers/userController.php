<?php

namespace Hmg\Controllers;

use Hmg\Models\User;
use Hmg\Models\UserPasswordHistory;
use Hmg\Models\UserLogs;
use Hmg\Models\Setting;

class UserController
{
    public function __construct()
    {
        $passHistory = new UserPasswordHistory();

        $user = new User();

        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            $user->setById($_REQUEST['id']);
        } else if (isset($_REQUEST['save']) || isset($_REQUEST['delete'])) {
            $user->setUser($_REQUEST['data']);
        }

        if (isset($_REQUEST['save'])) {
            $saved = false;
            // Show a form for adding
            $validEmail    = $user->validateEmail();

            $validPassword['valid'] = true;
            $passwordMatch = false;
            if (!empty($user->user['password'])) {
                $validPassword = $user->validatePassword($user->user['password']);
                $passwordMatch = $passHistory->getPasswordsByUserIdAndPassword($user->user['id'], $user->user['password']);
            }

            if (!$user->user['first_name'] || !$user->user['last_name'] || !$user->user['email'] || (!$user->user['id'] && !$user->user['password'])) {
                $user->message = 'Missing Required Field! <br />Required fields are first name, last name, email, password, and password confirmation for new users.';
            } else if (!$validEmail) {
                $user->message = 'Email address is not unique and is already used by another user.';
            } else if (!$user->user['id'] && ($user->user['password'] != $_REQUEST['password_confirm'])) {
                $user->message = 'Passwords did not match. Please try again!';
            } else if (isset($user->user['password']) && !$validPassword['valid']) {
                $user->message = 'Invalid Password. Please try again!' . "<br />";
                foreach ($validPassword['errors'] as $error) {
                    $user->message .= $error . "<br />";
                }
            } else if (!empty($user->user['password']) && $passwordMatch) {
                $user->message = 'Password has already been used in the past. Please try again!' . "<br />";
            } else {
                // if ($user->user['id'] && !$user->user['password']) {
                //     echo '<pre>'; var_dump($user->user); exit;
                // }
                if ($_SESSION['user']['permission'] !== 'Admin') {
                    unset($user->user['region_id']);
                }
                $saved = $user->save();
                if ($saved) {
                    $user->message = 'Information was saved successfully.';
                    $_SESSION['expiredPassword'] = false;

                    if (!empty($user->user['password'])) {
                        // Save a history of password changes
                        $lastHistory = $passHistory->getLastPasswordByUserId($user->user['id']);
                        $newPassword = md5($user->user['password']);
                        $lastPassword = (isset($lastHistory['password']) ? $lastHistory['password'] : '');
                        if ($lastPassword != $newPassword) {
                            $pass = array(
                                'id' => null,
                                'user_id' => $user->user['id'],
                                'password' => $newPassword
                            );
                            $passHistory->setData($pass);
                            $passHistory->save();
                        }
                    }
                } else {
                    $user->message = 'Failed to update or there were no changes to the record.';
                }
            }
            $this->displayUserForm($user->getAll(), $user->message, $saved);
        } else if (isset($_REQUEST['delete']) && $user->user["id"]) {
            if ($_SESSION['user']['id'] == $user->user["id"]) {
                $user->message = 'Error: You can\'t remove the logged in user account!';
                $this->displayUserForm($user->getAll(), $user->message);
            } else {
                $deleted = $user->delete();
                if ($deleted) {
                    $message = 'User was removed successfully!';
                    header("Location: index.php?action=users&message=" . urlencode($message));
                } else {
                    $user->message = 'System Error: Was not able to remove user!';
                    $this->displayUserForm($user->getAll(), $user->message);
                }
            }
        } else if ((isset($user->user["id"]) && $user->user["id"]) || (isset($_REQUEST['id']) && $_REQUEST['id'] == 'new')) {
            $message = null;
            if (isset($_REQUEST['errors'])) {
                $message = 'Your password is invalid, please update now!<br />';
                if (is_array($_REQUEST['errors'])) {
                    foreach ($_REQUEST['errors'] as $error) {
                        $message .= $error . "<br />";
                    }
                }
            }
            $this->displayUserForm($user->getAll(), $message);
        } else {
            header("Location: index.php?action=users");
        }
    }

    public function displayUserForm($user, $message = null, $saved = null)
    {
        $userLogs = new UserLogs();
        $region   = new Setting('region');

        $logs = array();
        if (isset($user['id'])) {
            $logs = $userLogs->getAllLogsByUserId($user['id']);
        }

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/user-form.phtml');
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
