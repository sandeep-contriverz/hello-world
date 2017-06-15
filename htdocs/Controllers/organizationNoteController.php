<?php

namespace Hmg\Controllers;

use Hmg\Models\OrganizationNotes;
use Hmg\Models\OrganizationNote;

class OrganizationNoteController
{
    public function __construct()
    {

        if (isset($_REQUEST['save']) && isset($_REQUEST['json'])) {
            $json = $_REQUEST['json'];
            if (is_array($json)) {
                $newOrganizationNote = $json;
            } else {
                $data = json_decode($_REQUEST['json']);
                $newOrganizationNote = (array) $data;
            }

            $note = new OrganizationNote();
            $note->setNote($newOrganizationNote);
            $saved = $note->save();

            $notes = array();
            if ($note->note["organization_sites_id"]) {
                $notesObj = new OrganizationNotes($note->note["organization_sites_id"]);
                $notes = $notesObj->getList();
            }

            $jsonEncodedOrganizationOrganizationNotes = json_encode($notes);
            header('Content-Type: aplication/json');
            echo $jsonEncodedOrganizationOrganizationNotes;
        } else if (isset($_REQUEST['delete']) && isset($_REQUEST['id'])) {
            $note = new OrganizationNote();
            $note->setById($_REQUEST['id']);
            $note->delete();
        }
    }
}
