<?php

namespace Hmg\Controllers;

use Hmg\Models\FamilyScreeningAttachments;
use Hmg\Models\FamilyScreeningAttachment;

class FamilyScreeningAttachmentsController
{
    public function __construct()
    {

        if (isset($_REQUEST['attachmentId']) && isset($_REQUEST['download'])) {
            $this->downloadFile($_REQUEST['attachmentId']);
        } elseif (isset($_REQUEST['attachmentId'])) {
            $screening_id = $this->deleteAttachment($_REQUEST['attachmentId']);
            $data = [
                'screening_id' => $screening_id
            ];
            header('Content-type:application/json');
            print json_encode($data);
            //$this->displayAttachments($screening_id, 'screening');
        } else {
            exit;
        }
    }

    public function displayAttachments($screening_id, $type)
    {

        $attachments = new FamilyScreeningAttachments($screening_id);
        $attachments->set('_type', $type);
        $attachmentList = $attachments->getList();

        ob_start();
        include(VIEW_PATH . '/family-screening-attachments.phtml');
        $attachment_content = ob_get_contents();
        ob_end_clean();

        print $attachment_content;
    }

    public function deleteAttachment($id)
    {

        $attachmentModel = new FamilyScreeningAttachment();
        $attachmentModel->setById($id);
        $attachment = $attachmentModel->getAll();
        $deleted = $attachmentModel->delete();
        if ($deleted) {
            $filename = $_SERVER['DOCUMENT_ROOT'] . '/../attachments/f' . $attachment['screening_id'] . '-a' . $attachment['id'] . '-' . $attachment['attachment_name'];
            $attachmentModel->deleteFile($filename);
        }

        return $attachment['screening_id'];
    }

    public function downloadFile($id)
    {

        $attachmentModel = new FamilyScreeningAttachment();
        $attachmentModel->setById($id);
        $attachment = $attachmentModel->getAll();
        $filename = $_SERVER['DOCUMENT_ROOT'] . '/../attachments/f' . $attachment['screening_id'] . '-a' . $attachment['id'] . '-' . $attachment['attachment_name'];
        if (file_exists($filename)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $type = finfo_file($finfo, $filename);
            finfo_close($finfo);
            //var_dump($type); exit;
            $content = file_get_contents($filename);
            header('Content-type: ' . ($type ? $type : 'application/octet-stream'));
            //header("Content-Disposition: attachment; filename=".$attachment['attachment_name']);
            readfile($filename);
        } else {
            header('Content-type: text/plain');
            echo "Error: " . $filename . " was not found on the server.";
        }
    }
}
