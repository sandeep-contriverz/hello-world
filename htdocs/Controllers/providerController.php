<?php

namespace Hmg\Controllers;

use Hmg\Models\Provider;
use Hmg\Models\FamilyProvider;
use Hmg\Models\Setting;

class ProviderController
{
    public function __construct()
    {

        $provider = new Provider();
        $cities = new Setting('city');

        if (!empty($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            $provider->setById($_REQUEST['id']);
        } else if (!empty($_REQUEST['save']) || !empty($_REQUEST['delete']) || !empty($_REQUEST['deleteChild'])) {
            $provider->setProvider($_REQUEST['data']);
        }

        if (!empty($_REQUEST['save'])) {
            // Show a form for adding

            if (!$provider->provider['last_name']
                 || !$provider->provider['first_name']
                 || !$provider->provider['employer']
                 || !$provider->provider['phone']
            ) {
                $provider->message = 'Missing Required Field! <br />Required fields are Primary Contact (first name, last name, employer, and phone number)!';
                $this->displayProviderForm($provider, $cities, $provider->message);
            } else {
                $saved = $provider->save();
                if ($saved) {
                    $provider->message = 'Information was saved successfully.';
                    $this->displayProvider($provider);
                } else {
                    $provider->message = 'Failed to update or there were no changes to the record.';
                    $this->displayProviderForm($provider, $cities, $provider->message);
                }
            }

        } else if (!empty($_REQUEST['delete']) && $provider->provider["id"]) {
            $deleteFamilyProvider = $provider->deleteFamilyProvider();
            $deleted              = $provider->delete();
            if ($deleted) {
                $message = 'Provider was removed successfully!';
                header("Location: index.php?action=Providers&message=" . urlencode($message));
            } else {
                $provider->message = 'System Error: Was not able to remove Provider!';
                $this->displayProviderForm($provider, $cities, $provider->message);
            }

        } else if ((isset($_REQUEST['id']) && $_REQUEST['id'] == 'new') || ($provider->provider["id"] && !empty($_REQUEST['edit']))) {
            $this->displayProviderForm($provider, $cities, '');

        } else if (is_numeric($provider->provider["id"]) && isset($_REQUEST['family_id']) && is_numeric($_REQUEST['family_id'])) {
            $this->displayFamilyProviderForm($provider, $cities, $_REQUEST['family_id']);

        } else if (is_numeric($_REQUEST['id'])) {
            $this->displayProvider($provider);

        } else {
            header("Location: index.php?action=providers");
        }
    }

    public function displayProvider($provider, $message = null)
    {

        $providerRole = new Setting('provider_role');

        $data = $provider->getAll();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/provider.phtml');
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

    public function displayProviderForm($provider, $cities, $message = null)
    {
        $providerRole = new Setting('provider_role');

        $data = $provider->getAll();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/provider-form.phtml');
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

    public function displayFamilyProviderForm($provider, $city, $family_id)
    {
        $providerRole = new Setting('provider_role');

        $familyProvider = new FamilyProvider($family_id, $provider->provider['id']);
        $data = $familyProvider->getRecord();

        ob_start();
        include(VIEW_PATH . '/family-provider-edit.phtml');
        $content = ob_get_contents();
        ob_end_clean();

        print $content;
    }
}
