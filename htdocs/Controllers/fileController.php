<?php

namespace Hmg\Controllers;

use Hmg\Models\Attachment;

class FileController
{

    public function __construct()
    {

        if ($_FILES) {
            if ($_FILES['file']['name']) {
                $attachmentModel = new Attachment();
                $attachmentModel->setAttachment(array(
                    'family_id'    => $_POST['id'],
                    'attachment_name' => $_FILES['file']['name']
                ));
                $attachmentModel->save();
                $attachment = $attachmentModel->getAll();
                $filename = $attachment['family_id'] . '-' . $attachment['id'] . '-' . $attachment['attachment_name'];
              $path = $_SERVER['DOCUMENT_ROOT'] . '/../attachments/';
                move_uploaded_file($_FILES['file']['tmp_name'], $path . $filename);
				
				
            }
            $this->displayUploadForm($_POST['id'], 'family', 'true');
        } elseif (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            $this->displayUploadForm($_REQUEST['id'], $_REQUEST['type']);

        }
    }

    public function displayUploadForm($id = null, $type = 'family', $showFiles = 'false')
    {
        ob_start();
        include(VIEW_PATH . '/file.phtml');
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }
}
