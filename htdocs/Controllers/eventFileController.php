<?php

namespace Hmg\Controllers;

use Hmg\Models\EventAttachment;

class EventFileController
{

    public function __construct()
    {

        if ($_FILES) {
			
		  
			if ($_FILES['file']['name']) {
                $attachmentModel = new EventAttachment();
                $attachmentModel->setAttachment(array(
                    'event_id'    => $_POST['id'],
                    'attachment_name' => $_FILES['file']['name']
                ));
                $attachmentModel->save();
                $attachment = $attachmentModel->getAll();
                $filename = $attachment['event_id'] . '-' .
                $attachment['id'] . '-' . $attachment['attachment_name'];
				
				
               
			  $path = basename(__DIR__ ) . '/../../attachments/';
			  
			   
                move_uploaded_file($_FILES['file']['tmp_name'], $path . $filename);
				
				
				
            }
            $this->displayUploadForm($_POST['id']);
        } elseif (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            $this->displayUploadForm($_REQUEST['id'], $_REQUEST['type']);

        }
    }

    public function displayUploadForm($id = null, $type = 'event')
    {
        ob_start();
        include(VIEW_PATH . '/event-file-screening.phtml');
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }
}
