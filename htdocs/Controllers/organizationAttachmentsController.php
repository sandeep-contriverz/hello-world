<?php

namespace Hmg\Controllers;

use Hmg\Models\OrganizationAttachments;
use Hmg\Models\OrganizationAttachment;

class OrganizationAttachmentsController
{
    public function __construct()
    {

        if (isset($_REQUEST['attachmentId']) && isset($_REQUEST['download'])) {
            $this->downloadFile($_REQUEST['attachmentId']);

        } elseif (isset($_REQUEST['attachmentId'])) {
            $organization_id = $this->deleteAttachment($_REQUEST['attachmentId']);
            $data = [
                'organization_id' => $organization_id 
            ];
            header('Content-type:application/json');
            print json_encode($data);
        } elseif (isset($_REQUEST['get-attachments'])
            && isset($_REQUEST['id'])
            && is_numeric($_REQUEST['id'])
        ) {
            $this->displayAttachments(
                $_REQUEST['id']
            );
        } else {
            exit;
        }
    }

    public function displayAttachments($organization_id, $type = 'organization')
    {
        
        $attachments = new OrganizationAttachments($organization_id);
        $attachments->set('_type', $type);
        $attachmentList = $attachments->getList();
		
        ob_start();
        include(VIEW_PATH . '/organization-attachments.phtml');
        $attachment_content = ob_get_contents();
        ob_end_clean();

        print $attachment_content;
    }

    public function deleteAttachment($id)
    {

        $attachmentModel = new OrganizationAttachment();
        $attachmentModel->setById($id);
        $attachment = $attachmentModel->getAll();
        $deleted = $attachmentModel->delete();
        if ($deleted) {
            $filename = __DIR__ . '/../../attachments/' . $attachment['organization_sites_id'] . '-' . $attachment['id'] . '-' . $attachment['attachment_name'];
            $attachmentModel->deleteFile($filename);
        }

        return $attachment['organization_sites_id'];
    }

    public function downloadFile($id)
    {

        $attachmentModel = new OrganizationAttachment();
        $attachmentModel->setById($id);
        $attachment = $attachmentModel->getAll();
        $filename = basename(__DIR__ ). '/../../attachments/' . $attachment['organization_sites_id'] . '-' . $attachment['id'] . '-' . $attachment['attachment_name'];
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
