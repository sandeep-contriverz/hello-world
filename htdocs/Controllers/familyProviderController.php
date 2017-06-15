<?php

namespace Hmg\Controllers;

use Hmg\Models\Setting;
use Hmg\Models\FamilyProvider;

class FamilyProviderController
{
    public function __construct()
    {

        if (isset($_REQUEST['save']) && is_numeric($_REQUEST['family_id']) && !is_numeric($_REQUEST['provider_id'])) {
            $familyProvider = new FamilyProvider($_REQUEST['family_id'], $_REQUEST['provider_id']);
            $currentProviders = count($familyProvider->getList2());
            $saved = $familyProvider->save();

            // Default to fax permission
            /* $familyProvider->set('_fax_permission', 1); */
            /* $familyProvider->updateKey('_fax_permission'); */
            /* if (! $currentProviders) { */
            /*     $familyProvider->set('_primary', 1); */
            /*     $familyProvider->updateKey('_primary'); */
            /* } */
            
             $this->displayFamilyProviders($_REQUEST['family_id']);
        } else if (isset($_REQUEST['update']) && is_numeric($_REQUEST['family_id'])
            && is_numeric($_REQUEST['provider_id'])  ) {
//            $faxPermission = ($_REQUEST['fax_permission'] ? '1' : '0');
            $familyProvider = new FamilyProvider($_REQUEST['family_id'], $_REQUEST['provider_id']);

            $status= $familyProvider->update($_REQUEST);
            

            $this->displayFamilyProviders($_REQUEST['family_id']);
            
        } else if (is_numeric($_REQUEST['family_id']) && isset($_REQUEST['primary'])) {

            if (is_array($_REQUEST['primary'])) {
                foreach ($_REQUEST['primary'] as $provider_id => $primary) {
                    $familyProvider = new FamilyProvider($_REQUEST['family_id'], $provider_id);
                    $familyProvider->set('_primary', $primary);
                    $familyProvider->updateKey('_primary');
                }
            }
            $this->displayFamilyProviders($_REQUEST['family_id']);
        } else if (isset($_REQUEST['delete']) && is_numeric($_REQUEST['family_id']) && is_numeric($_REQUEST['provider_id'])) {
            $familyProvider = new FamilyProvider($_REQUEST['family_id'], $_REQUEST['provider_id']);
            $deleted = $familyProvider->delete();
            $this->displayFamilyProviders($_REQUEST['family_id']);
        } else if (is_numeric($_REQUEST['family_id']) && !empty($_REQUEST['list'])) {
            $familyProvider = new FamilyProvider($_REQUEST['family_id']);
            $providers = $familyProvider->getList();
            $this->displayFamilyProvidersList($_REQUEST['family_id']);
        } else if (is_numeric($_REQUEST['family_id'])) {
            $familyProvider = new FamilyProvider($_REQUEST['family_id']);
            $providers = $familyProvider->getList();
            $this->displayFamilyProviders($_REQUEST['family_id']);
        } else if (is_numeric($_REQUEST['family_id']) && !empty($_REQUEST['delete'])) {
            $familyProvider = new FamilyProvider($_REQUEST['family_id']);
            $familyProvider->delete();
            $providers = $familyProvider->getList();
            $this->displayFamilyProviders($_REQUEST['family_id']);
        } else {
            //header("Location: index.php");
        }
    }

    public function displayFamilyProviders($family_id)
    {

        $familyProvider = new FamilyProvider($family_id);
        $providerList = $familyProvider->getList2();
        $permisson_fax_type   = new Setting('permission_fax_type');
        $perms = $permisson_fax_type->displaySelect('permission_type','','','',false,null,false );

        ob_start();
        include(VIEW_PATH . '/family-providers.phtml');
        $provider_content = ob_get_contents();
        ob_end_clean();

        print $provider_content;
    }

    public function displayFamilyProvidersList($family_id)
    {

        $familyProvider = new FamilyProvider($family_id);
        $providerList = $familyProvider->getList2();

        ob_start();
        include(VIEW_PATH . '/family-providers-list.phtml');
        $provider_content = ob_get_contents();
        ob_end_clean();

        print $provider_content;
    }
}
