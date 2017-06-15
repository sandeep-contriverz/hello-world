<?php

namespace Hmg\Controllers;

use Hmg\Models\OrganizationStartEnds;
use Hmg\Models\OrganizationStartEnd;

class OrganizationStartEndController
{
    public function __construct()
    {
         $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';
        if (isset($_REQUEST['save']) && $_REQUEST['save'] && isset($_REQUEST['organization_id']) && $_REQUEST['organization_id']) {
            $start_date = null;
            if (isset($_REQUEST['start_date']) && $_REQUEST['start_date']) {
                $start_date = date('Y-m-d', strtotime($_REQUEST['start_date']));
            }
            $end_date = null;
            if (isset($_REQUEST['end_date']) && $_REQUEST['end_date']) {
                $end_date = date('Y-m-d', strtotime($_REQUEST['end_date']));
            }
            $data = array(
                'id'         => (isset($_REQUEST['id']) ? $_REQUEST['id'] : ''),
                'parent_id'  => $_REQUEST['organization_id'],
                'start_date' => $start_date,
                'end_date'     => $end_date,
                'reason'     => (isset($_REQUEST['reason']) ? $_REQUEST['reason'] : '')
            );
            

            $startEnd = new OrganizationStartEnd($type);
            $startEnd->setStartEnd($data);
            $saved = $startEnd->save();

            $this->displayFamilyStartEnds($startEnd->startEnd["parent_id"],$type);
        } else if (isset($_REQUEST['delete']) && $_REQUEST['delete'] && isset($_REQUEST['id']) && $_REQUEST['id']) {
            $startEnd = new OrganizationStartEnd($type);
            $startEnd->setById($_REQUEST['id']);
            $organization_id = $startEnd->startEnd["parent_id"];
            $delete = $startEnd->delete();
            $this->displayFamilyStartEnds($organization_id,$type);
        }
    }

    public function displayFamilyStartEnds($organization_id,$type)
    {

        $familyStartEnds = new OrganizationStartEnds($organization_id,$type);
        $startEnds = $familyStartEnds->getList();
                if($type == 'share')
                    $title = 'Sharing history';

            
        ob_start();
     if($type != 'share')
        include(VIEW_PATH . "/organization-start-ends.phtml");
     else
        include(VIEW_PATH . "/organization-start-ends2.phtml");
        $html = ob_get_contents();
        ob_end_clean();
        header('Content-type: text/html');
        echo $html;
    }
}
