<?php

namespace Hmg\Controllers;

use Hmg\Models\ContactAttachment;
use Hmg\Models\ContactAttachments;

class ContactFileController
{

    public function __construct()
    {
        //echo "<pre>";print_r($_FILES);die;
        if (isset($_FILES) && !empty($_FILES['file']['name'])) {
            if ($_FILES['file']['name']) {
                $attachmentModel = new ContactAttachment();
                
                $attachmentModel->setAttachment(array(
                    'contact_id'      => $_POST['id'],
                    'attachment_name' => $_FILES['file']['name']
                ));
                $attachmentModel->save();
                $attachment = $attachmentModel->getAll();
                $filename = $attachment['contact_id'] . '-' . $attachment['id'] . '-' . $attachment['attachment_name'];
                
                //$folder_path = basename(__DIR__ );
                
                $path = basename(__DIR__ ). '/../../attachments/';
                                                
                move_uploaded_file($_FILES['file']['tmp_name'], $path . $filename);
            }
            $this->displayUploadForm($_POST['id'], 'contact', 'true');
        } else

        if (isset($_REQUEST['attachmentId']) && isset($_REQUEST['download'])) {
            $this->downloadFile($_REQUEST['attachmentId']);

        } elseif (isset($_REQUEST['attachmentId'])) {
            $contact_id = $this->deleteAttachment($_REQUEST['attachmentId']);
            $data = [
                'contact_id' => $contact_id 
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
        } elseif (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) 
        {
            $this->displayUploadForm($_REQUEST['id'], $_REQUEST['type']);

        } elseif (isset($_FILES) && !empty($_FILES['file']['name'])) {
            if ($_FILES['file']['name']) {
                $attachmentModel = new ContactAttachment();
                
                $attachmentModel->setAttachment(array(
                    'contact_id'      => $_POST['id'],
                    'attachment_name' => $_FILES['file']['name']
                ));
                $attachmentModel->save();
                $attachment = $attachmentModel->getAll();
                $filename = $attachment['contact_id'] . '-' . $attachment['id'] . '-' . $attachment['attachment_name'];
                
                //$folder_path = basename(__DIR__ );
                
                $path = basename(__DIR__ ). '/../../attachments/';
                                                
                move_uploaded_file($_FILES['file']['tmp_name'], $path . $filename);
            }
            $this->displayUploadForm($_POST['id'], 'contact', 'true');
        } else {
            exit;
        }
    }

    public function displayUploadForm($id = null, $type = 'contact', $showFiles = 'false')
    {
        ob_start();
        include(VIEW_PATH . '/contact-file.phtml');
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function displayAttachments($contact_id, $type = 'contact')
    {
        $showFiles   = true;
        $attachments = new ContactAttachments($contact_id);
        $attachments->set('_type', $type);
        $attachmentList = $attachments->getList();
        
        ob_start();
        include(VIEW_PATH . '/contact-attachments.phtml');
        $attachment_content = ob_get_contents();
        ob_end_clean();

        print $attachment_content;
    }

    public function deleteAttachment($id)
    {

        $attachmentModel = new ContactAttachment();
        $attachmentModel->setById($id);
        $attachment = $attachmentModel->getAll();
        $deleted = $attachmentModel->delete();
        if ($deleted) {
            $filename = __DIR__. '/../../attachments/' . $attachment['contact_id'] . '-' . $attachment['id'] . '-' . $attachment['attachment_name'];
            $attachmentModel->deleteFile($filename);
        }

        return $attachment['contact_id'];
    }

    public function downloadFile($id)
    {

        $attachmentModel = new ContactAttachment();
        $attachmentModel->setById($id);
        $attachment = $attachmentModel->getAll();
        $filename = basename(__DIR__ ). '/../../attachments/' . $attachment['contact_id'] . '-' . $attachment['id'] . '-' . $attachment['attachment_name'];
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
