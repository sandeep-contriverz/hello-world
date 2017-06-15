<?php

namespace Hmg\Controllers;

use Hmg\Models\City;
use Hmg\Models\User;



class CityController
{
    public function __construct()
    {
        $city = new City();
        
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page'])) {
            $city->set('_start', ($_REQUEST['page'] - 1) * 50);
        } else {
            $city->set('_start', 0);
        }
        $Allcity = $city->getcities();
        $totalcity = $city->getcount();

        if(!empty($_REQUEST['save'])){
            print_r($_REQUEST);
            exit();
            $this->displaycityForm();
        }
        if(isset($_REQUEST['id']) && $_REQUEST['id']== 'new'){
            $this->displaycityForm($Allcity,$_REQUEST['id']);
        }else{
            $this->displaycity($Allcity,$page,$totalcity);
        }    
        // print_r($city);
         
        
        
    }
    public function displaycity($Allcity,$page,$totalcity){
        $numcitiy = count($Allcity);
        $numPages = ceil($totalcity/50);
        $pageNumber = $page;
        $firstRecord = (($pageNumber -1) * 50) + 1;
        $lastRecord = $firstRecord + $numcitiy - 1;
        // print_r($numPages);
        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/city.phtml');
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

    public function displaycityForm($Allcity,$id){
        include (VIEW_PATH .'/adminnav.phtml');

        ob_start();
        include(VIEW_PATH .'/city_form.phtml');
        $main_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/admin.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(TEMPLATE_PATH . TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print_r($content);

    }

}   