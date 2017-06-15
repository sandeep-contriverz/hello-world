<?php

namespace Hmg\Controllers;

use Hmg\Models\ChildDevelopmentalScreenings;
use Hmg\Models\ChildDevelopmentalScreening;
use Hmg\Models\ScreeningAttachments;
use Hmg\Models\ScreeningAttachment;
use Hmg\Models\Setting;

class ChildDevelopmentalScreeningController
{
    public function __construct()
    {
        if (isset($_REQUEST['save']) && $_REQUEST['save'] && isset($_REQUEST['developmentScreening'])
            && is_numeric($_REQUEST['developmentScreening']['child_id'])) {
            $data = $_REQUEST['developmentScreening'];

            $data['date_sent'] = ($data['date_sent'] ? date('Y-m-d', strtotime($data['date_sent'])) : "0000-00-00");
            $data['date_sent_provider'] = ($data['date_sent_provider'] ? date('Y-m-d', strtotime($data['date_sent_provider'])) : "0000-00-00");

            $newDevelopmentalScreening = (array) $data;

            $dsObj = new ChildDevelopmentalScreening();
            $dsObj->setDevelopmentalScreening($newDevelopmentalScreening);
            $saved = $dsObj->save();
            header('Content-Type: application/json');
            echo json_encode($dsObj->developmentalScreening);

        } elseif (isset($_REQUEST['delete']) && $_REQUEST['delete'] && isset($_REQUEST['developmentScreening'])
            && is_numeric($_REQUEST['developmentScreening']['child_id'])) {
            $data = $_REQUEST['developmentScreening'];

            $data['date_sent'] = ($data['date_sent'] ? date('Y-m-d', strtotime($data['date_sent'])) : "0000-00-00");
            $data['date_sent_provider'] = ($data['date_sent_provider'] ? date('Y-m-d', strtotime($data['date_sent_provider'])) : "0000-00-00");

            $newDevelopmentalScreening = (array) $data;

            $dsObj = new ChildDevelopmentalScreening();
            $dsObj->setDevelopmentalScreening($newDevelopmentalScreening);

            // delete all the attachments.
            $screeningAttachments = new ScreeningAttachments($dsObj->developmentalScreening["id"]);
            $attachmentList = $screeningAttachments->getList();
            $attachmentObj = new ScreeningAttachment();

            if (is_array($attachmentList)) {
                foreach ($attachmentList as $attachment) {
                    $attachmentObj->setById($attachment['id']);
                    $filename = $_SERVER['DOCUMENT_ROOT'] . '/../attachments/s' . $attachment['screening_id'] . '-a' . $attachment['id'] . '-' . $attachment['attachment_name'];
                    $attachmentObj->deleteFile($filename);
                    $attachmentObj->delete();
                }
            }

            // delete the screening
            $saved = $dsObj->delete();
            header('Content-Type: application/json');
            echo json_encode($dsObj->developmentalScreening);
        } elseif (isset($_REQUEST['get-list']) && isset($_REQUEST['developmentScreening']['child_id']) && is_numeric($_REQUEST['developmentScreening']['child_id'])) {
            $dsObj = new ChildDevelopmentalScreenings($_REQUEST['developmentScreening']['child_id']);
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
            $dsModel = new ChildDevelopmentalScreening();
            $dsModel->setById($_REQUEST['id']);
            $this->displayDevelopmentalScreeningsForm($dsModel->getAll());
        } elseif (isset($_REQUEST['get-form']) && isset($_REQUEST['child_id']) && is_numeric($_REQUEST['child_id'])) {
            $this->displayNewDevelopmentalScreeningsForm($_REQUEST['child_id']);
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

                if (isset($value['communication']) && $value['communication'] == 'White') {
                    $above[] = 'Communication';
                } elseif (isset($value['communication']) && $value['communication'] == 'Grey') {
                    $monitoring[] = 'Communication';
                } elseif (isset($value['communication']) && $value['communication'] == 'Black') {
                    $below[] = 'Communication';
                }

                if (isset($value['fine_motor']) && $value['fine_motor'] == 'White') {
                    $above[] = 'Fine Motor';
                } elseif (isset($value['fine_motor']) && $value['fine_motor'] == 'Grey') {
                    $monitoring[] = 'Fine Motor';
                } elseif (isset($value['fine_motor']) && $value['fine_motor'] == 'Black') {
                    $below[] = 'Fine Motor';
                }

                if (isset($value['gross_motor']) && $value['gross_motor'] == 'White') {
                    $above[] = 'Gross Motor';
                } elseif (isset($value['gross_motor']) && $value['gross_motor'] == 'Grey') {
                    $monitoring[] = 'Gross Motor';
                } elseif (isset($value['gross_motor']) && $value['gross_motor'] == 'Black') {
                    $below[] = 'Gross Motor';
                }

                if (isset($value['problem_solving']) && $value['problem_solving'] == 'White') {
                    $above[] = 'Problem Solving';
                } elseif (isset($value['problem_solving']) && $value['problem_solving'] == 'Grey') {
                    $monitoring[] = 'Problem Solving';
                } elseif (isset($value['problem_solving']) && $value['problem_solving'] == 'Black') {
                    $below[] = 'Problem Solving';
                }

                if (isset($value['personal_social']) && $value['personal_social'] == 'White') {
                    $above[] = 'Personal Social';
                } elseif (isset($value['personal_social']) && $value['personal_social'] == 'Grey') {
                    $monitoring[] = 'Personal Social';
                } elseif (isset($value['personal_social']) && $value['personal_social'] == 'Black') {
                    $below[] = 'Personal Social';
                }
                $developmentalScreenings[$key]['above'] = implode(', ', $above);
                $developmentalScreenings[$key]['monitoring'] = implode(', ', $monitoring);
                $developmentalScreenings[$key]['below'] = implode(', ', $below);

                $attachmentList = array();
                $attachments = new ScreeningAttachments($value['id']);
                $attachmentList = $attachments->getList();
                $developmentalScreenings[$key]['attachments'] = $attachmentList;
            }
        }

        ob_start();
        $settingOb = new Setting(); //201016
        include(VIEW_PATH . '/child-developmental-screenings.phtml');
        $developmental_screenings_content = ob_get_contents();
        ob_end_clean();

        print $developmental_screenings_content;
    }

    public function displayNewDevelopmentalScreeningsForm($child_id)
    {
        $screening_type        = new Setting('screening_type');
        $screening_interval    = new Setting('screening_interval', true);
        $score                 = new Setting('score');

        $attachment_content = '';

        ob_start();
        include(VIEW_PATH . '/child-developmental-screening-form.phtml');
        $developmental_screenings_content = ob_get_contents();
        ob_end_clean();

        print $developmental_screenings_content;
    }

    public function displayDevelopmentalScreeningsForm($developmentalScreening)
    {
        $screening_type        = new Setting('screening_type');
        $screening_interval    = new Setting('screening_interval', true);
        $score                 = new Setting('score');

        $child_id = $developmentalScreening['child_id'];

        $attachments = new ScreeningAttachments($developmentalScreening['id']);
        $attachmentList = $attachments->getList();
        
        ob_start();
        include(VIEW_PATH . '/screening-attachments.phtml');
        $attachment_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/child-developmental-screening-form.phtml');
        $developmental_screenings_content = ob_get_contents();
        ob_end_clean();

        print $developmental_screenings_content;
    }

    public function displayDevelopmentalScreeningsAttachments($screeningId)
    {
        $attachments = new ScreeningAttachments($screeningId);
        $attachmentList = $attachments->getList();

        ob_start();
        include(VIEW_PATH . '/screening-attachments.phtml');
        $attachment_content = ob_get_contents();
        ob_end_clean();

        print $attachment_content;
    }
}
