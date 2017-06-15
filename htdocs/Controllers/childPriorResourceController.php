<?php

namespace Hmg\Controllers;

use Hmg\Models\ChildPriorResources;
use Hmg\Models\ChildPriorResource;
use Hmg\Models\Setting;

class ChildPriorResourceController
{
    public function __construct()
    {

        if (isset($_REQUEST['save']) && isset($_REQUEST['json'])) {
            // We need to escape new line characters.
            $json = preg_replace('/\r?\n/', '\\\n', $_REQUEST['json']);
            $data = json_decode($json);

            $data->date_enrolled = date('Y-m-d', strtotime($data->date_enrolled));

            $newResource = (array) $data;

            $resource = new ChildPriorResource();
            $resource->setResource($newResource);
            $saved = $resource->save();

            $resources = array();
            if ($resource->resource['child_id']) {
                $resourceObj = new ChildPriorResources($resource->resource['child_id']);
                $resources = $resourceObj->getList();

                $this->displayResources($resources);
            }
        } else if (isset($_REQUEST['delete']) && isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            $resource = new ChildPriorResource();
            $resource->setById($_REQUEST['id']);
            $resource->delete();
            $resourceObj = new ChildPriorResources($resource->resource['child_id']);
            $resources = $resourceObj->getList();

            $this->displayResources($resources);
        } else if (isset($_REQUEST['get-form']) && is_numeric($_REQUEST['id'])) {
            $resource = new ChildPriorResource();
            $resource->setById($_REQUEST['id']);

            $this->displayResourceForm($resource->resource);
        } else if (isset($_REQUEST['get-view']) && is_numeric($_REQUEST['id'])) {
            $resource = new ChildPriorResource();
            $resource->setById($_REQUEST['id']);

            $this->displayResourceView($resource->resource);
        }
    }

    public function displayResources($resources)
    {
        $setting = new Setting(); //181016
        ob_start();
        // Get full list of child service types 171016
        $childServices  = new Setting('child_services');
        include(VIEW_PATH . '/child-resources.phtml');
        $resource_content = ob_get_contents();
        ob_end_clean();

        print $resource_content;
    }

    public function displayResourceForm($resource)
    {
        // Get full list of child service types 171016
        $childServices  = new Setting('child_services');

        ob_start();
        include(VIEW_PATH . '/child-resources-edit.phtml');
        $resource_content = ob_get_contents();
        ob_end_clean();

        print $resource_content;
    }

    public function displayResourceView($resource)
    {
        $setting = new Setting(); //181016
        // Get full list of child service types 171016
        $childServices  = new Setting('child_services');
        ob_start();
        include(VIEW_PATH . '/child-resources-view.phtml');
        $resource_content = ob_get_contents();
        ob_end_clean();

        print $resource_content;
    }

    public function displayResourcesJson($list)
    {
        if (is_array($list)) {
            $json = json_encode($list);
            header('Content-Type: application/json');
            echo $json;
        }
    }
}
