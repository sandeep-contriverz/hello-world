<?php

namespace Hmg\Controllers;

use Hmg\Models\Setting;
use Hmg\Models\RegionCounties;

class RegionCountiesController
{

    public function __construct()
    {
        //echo "<pre>";print_r($_REQUEST);die;
        if (isset($_REQUEST['region-counties']) && $_REQUEST['region-counties']) {
            $regions = new Setting('region');
            $this->displayManageRegions($regions);
            exit;
        }

        if (isset($_REQUEST['save']) && $_REQUEST['save'] && isset($_REQUEST['json']) && $_REQUEST['json']) {
            $data = json_decode($_REQUEST['json']);
            $regionId = $data->id;
            $newRegionCounties = array();
            if (is_array($data->counties)) 
            {
                foreach ($data->counties as $county) {
                    $newRegionCounties[] = array(
                        'id'       => $county->id,
                        'name'     => $county->name
                    );
                }
            }
            $regionCounties = new RegionCounties($regionId, $newRegionCounties);
            $saved = $regionCounties->save();
//echo "<pre>";print_r($regionCounties->counties);die;
            $json = json_encode($regionCounties->counties);
            header('Content-Type: aplication/json');
            echo $json;
        } else if (isset($_REQUEST['get-select'])) {
            $counties = new Setting('county');
            $regionCounties = new RegionCounties(null,$counties);

            // $selected = (isset($_REQUEST['selected']) ? $_REQUEST['selected'] : '');
            // $counties = $regionCounties->getFilteredCounties($selected);
            $select = $regionCounties->displayCountySelect(
                'county[]',
                (isset($_REQUEST['selected']) ? $_REQUEST['selected'] : ''),
                $label = ' ',
                $tabIndex = '',
                $required = false,
                $addtlclasses = null,
                $filtered = true,
                $allowDisableSelect = true
            );
            header('Content-Type: text/html');
            echo $select;
            exit;
        } else {
            $regionCounties = new RegionCounties();
            $this->displayRegionCounties($regionCounties->getList());
        }

    }

    public function displayRegionCounties($regionCounties)
    {

        $region = new Setting('region');
        $county = new Setting('county');

        include(VIEW_PATH . '/adminnav.phtml');
        ob_start();
        include(VIEW_PATH . '/region-counties.phtml');
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
