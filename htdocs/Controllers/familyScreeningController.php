<?php

namespace Hmg\Controllers;

use Hmg\Models\FamilyScreenings;
use Hmg\Models\Family;
use Hmg\Models\FamilyProvider;
use Hmg\Models\FamilyScreening;
use Hmg\Models\FamilyScreeningAttachments;
use Hmg\Models\FamilyScreeningAttachment;
use Hmg\Models\Setting;
use Hmg\Models\User;

class FamilyScreeningController
{
    public function __construct()
    {
        $setting = new Setting();
        if( isset($_REQUEST['pdf']) && $_REQUEST['pdf'] ){

            $setting    = new Setting();
            $user       = new User();
            $family     = new Family();
            $dsModel    = new FamilyScreening();

            $dsModel->setById($_REQUEST['screening_id']);

            $screeningData = $dsModel->getAll();
            
            $family->setById($_REQUEST["parent_id"]);

            $user->setByHmgWorker($family->family['hmg_worker']);

            $pdf = new \PdfFamilyScreeningInfo('P', 'pt');
            $pdf->setLogoImage($GLOBALS['pdfLogo']);
            $pdf->setCreatedBy($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']);
            $pdf->setHmgWorker($user->user['first_name']);
            $pdf->setFooterText($GLOBALS['footerText']);
            $pdf->AliasNbPages();
            $pdf->SetTextColor(70, 70, 70);
            $pdf->SetAutoPageBreak(1, 70);
            $pdf->AddPage();

            $family->family['full_name'] = $screeningData['completed_by'];
        
            $family->family['full_address'] = $family->family['address'] .
                ($family->family['city'] ? ', ' . $family->family['city'] : '') .
                ' ' . $family->family['state'] . ' ' . $family->family['zip'];
            
            $familyInfo = array(
                'parentName'    => $family->family['full_name'],
                'full_address'  => $family->family['full_address'],
                'care'          => $user->user['first_name'].' '.$user->user['last_name'],
            );

            $pdf->FamilyInformation($familyInfo);
            $pdf->ScreeningInformation($screeningData,$setting);
            $pdf->FollowInformation($screeningData,$setting);

            $pdf->Output();
            die();

        }
        if (isset($_REQUEST['save']) && $_REQUEST['save'] && isset($_REQUEST['developmentScreening'])
            && is_numeric($_REQUEST['developmentScreening']['family_id'])) {
            $data = $_REQUEST['developmentScreening'];

            $data['date_sent'] = ($data['date_sent'] ? date('Y-m-d', strtotime($data['date_sent'])) : "0000-00-00");
            $data['date_sent_provider'] = ($data['date_sent_provider'] ? date('Y-m-d', strtotime($data['date_sent_provider'])) : "0000-00-00");

            $newDevelopmentalScreening = (array) $data;

            $dsObj = new FamilyScreening();
            $dsObj->setDevelopmentalScreening($newDevelopmentalScreening);
            $saved = $dsObj->save();
            header('Content-Type: application/json');
            echo json_encode($dsObj->developmentalScreening);

        } elseif (isset($_REQUEST['delete']) && $_REQUEST['delete'] && isset($_REQUEST['developmentScreening'])
            && is_numeric($_REQUEST['developmentScreening']['family_id'])) {
            $data = $_REQUEST['developmentScreening'];

            $data['date_sent'] = ($data['date_sent'] ? date('Y-m-d', strtotime($data['date_sent'])) : "0000-00-00");
            $data['date_sent_provider'] = ($data['date_sent_provider'] ? date('Y-m-d', strtotime($data['date_sent_provider'])) : "0000-00-00");

            $newDevelopmentalScreening = (array) $data;

            $dsObj = new FamilyScreening();
            $dsObj->setDevelopmentalScreening($newDevelopmentalScreening);

            // delete all the attachments.
            $screeningAttachments = new FamilyScreeningAttachments($dsObj->developmentalScreening["id"]);
            $attachmentList = $screeningAttachments->getList();
            $attachmentObj = new FamilyScreeningAttachment();

            if (is_array($attachmentList)) {
                foreach ($attachmentList as $attachment) {
                    $attachmentObj->setById($attachment['id']);
                    $filename = $_SERVER['DOCUMENT_ROOT'] . '/../attachments/f' . $attachment['screening_id'] . '-a' . $attachment['id'] . '-' . $attachment['attachment_name'];
                    $attachmentObj->deleteFile($filename);
                    $attachmentObj->delete();
                }
            }

            // delete the screening
            $saved = $dsObj->delete();
            header('Content-Type: application/json');
            echo json_encode($dsObj->developmentalScreening);
        } elseif (isset($_REQUEST['get-list']) && isset($_REQUEST['developmentScreening']['family_id']) && is_numeric($_REQUEST['developmentScreening']['family_id'])) {
            $dsObj = new FamilyScreenings($_REQUEST['developmentScreening']['family_id']);
            $developmentalScreenings = $dsObj->getList();
            $this->displayDevelopmentalScreenings($developmentalScreenings);
        } elseif (isset($_REQUEST['get-attachments'])
            && isset($_REQUEST['id'])
            && is_numeric($_REQUEST['id'])
        ) {
            $this->displayDevelopmentalScreeningsAttachments(
                $_REQUEST['id']
            );
        } elseif (isset($_REQUEST['get-form']) && isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            $dsModel = new FamilyScreening();
            $dsModel->setById($_REQUEST['id']);
            $this->displayDevelopmentalScreeningsForm($dsModel->getAll());
        } elseif (isset($_REQUEST['get-form']) && isset($_REQUEST['family_id']) && is_numeric($_REQUEST['family_id'])) {
            $this->displayNewDevelopmentalScreeningsForm($_REQUEST['family_id']);
        } elseif (isset($_REQUEST['delete']) && isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            // delete routines
        }
    }

    public function displayDevelopmentalScreenings($developmentalScreenings)
    {

        if (is_array($developmentalScreenings)) {
            foreach ($developmentalScreenings as $key => $value) {
                $above = array();
                $monitoring = array();
                $below = array();

                

                $attachmentList = array();
                $attachments = new FamilyScreeningAttachments($value['id']);
                $attachmentList = $attachments->getList();
                $developmentalScreenings[$key]['attachments'] = $attachmentList;
            }
        }

        ob_start();
        $settingOb = new Setting(); //201016
        include(VIEW_PATH . '/family-screenings.phtml');
        $developmental_screenings_content = ob_get_contents();
        ob_end_clean();

        print $developmental_screenings_content;
    }

    public function displayNewDevelopmentalScreeningsForm($family_id)
    {
        $screening_type        = new Setting('screening_type_family');
        $screening_interval    = new Setting('screening_interval_family', true);
        $score                 = new Setting('score');
        $harm_rating           = new Setting('harm_rating');
        $family = new Family();
        $family->setById($family_id);
        $attachment_content = ''; 

        ob_start();
        include(VIEW_PATH . '/family-screening-form.phtml');
        $developmental_screenings_content = ob_get_contents();
        ob_end_clean();

        print $developmental_screenings_content;
    }

    public function displayDevelopmentalScreeningsForm($developmentalScreening)
    {
        $screening_type        = new Setting('screening_type_family');
        $screening_interval    = new Setting('screening_interval_family', true);
        $score                 = new Setting('score');
        $harm_rating                 = new Setting('harm_rating');
        $family_id = $developmentalScreening['family_id'];

        $attachments = new FamilyScreeningAttachments($developmentalScreening['id']);
        $attachmentList = $attachments->getList();
        
        $family = new Family();
        $family->setById($family_id);
        
        ob_start();
        include(VIEW_PATH . '/screening-attachments.phtml');
        $attachment_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/family-screening-form.phtml');
        $developmental_screenings_content = ob_get_contents();
        ob_end_clean();

        print $developmental_screenings_content;
    }

    public function displayDevelopmentalScreeningsAttachments($screeningId)
    {
        $attachments = new FamilyScreeningAttachments($screeningId);
        $attachmentList = $attachments->getList();

        ob_start();
        include(VIEW_PATH . '/screening-attachments.phtml');
        $attachment_content = ob_get_contents();
        ob_end_clean();

        print $attachment_content;
    }
}
