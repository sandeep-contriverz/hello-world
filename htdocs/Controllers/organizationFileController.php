<?php
namespace Hmg\Controllers;

use Hmg\Models\OrganizationAttachment;

class OrganizationFileController
{

    public function __construct()
    {
        if ($_FILES) {
            if ($_FILES['file']['name']) {
                $attachmentModel = new OrganizationAttachment();
				
                $attachmentModel->setAttachment(array(
                    'organization_sites_id' => $_POST['id'],
                    'attachment_name' => $_FILES['file']['name']
                ));
                $attachmentModel->save();
                $attachment = $attachmentModel->getAll();
                $filename = $attachment['organization_sites_id'] . '-' . $attachment['id'] . '-' . $attachment['attachment_name'];
                
				//$folder_path = basename(__DIR__ );
				
				$path = basename(__DIR__ ). '/../../attachments/';
								
                move_uploaded_file($_FILES['file']['tmp_name'], $path . $filename);
            }
            $this->displayUploadForm($_POST['id'], 'organization', 'true');
        } elseif (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) 
		{
            $this->displayUploadForm($_REQUEST['id'], $_REQUEST['type']);

        }
    }

    public function displayUploadForm($id = null, $type = 'organization', $showFiles = 'false')
    {
        $showFiles = $showFiles;
        ob_start();
        include(VIEW_PATH . '/organization-file.phtml');
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }
}
