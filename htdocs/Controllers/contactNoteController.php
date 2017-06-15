<?php

namespace Hmg\Controllers;

use Hmg\Models\ContactNotes;
use Hmg\Models\ContactNote;


class ContactNoteController
{
    public function __construct()
    {
        if (isset($_REQUEST['save']) && isset($_REQUEST['json'])) {
            $json = $_REQUEST['json'];
            if (is_array($json)) {
                $newNote = $json;
            } else {
                $data = json_decode($_REQUEST['json']);
                $newNote = (array) $data;
            }

            $note = new ContactNote();
            $note->setNote($newNote);
            $saved = $note->save();

            $notes = array();
            if ($note->note["contact_id"]) {
                $notesObj = new ContactNotes($note->note["contact_id"]);
                $notes = $notesObj->getList();
            }

            $jsonEncodedNotes = json_encode($notes);
            header('Content-Type: aplication/json');
            echo $jsonEncodedNotes;
        } else if (isset($_REQUEST['delete']) && isset($_REQUEST['id'])) {
            $note = new ContactNote();
            $note->setById($_REQUEST['id']);
            $note->delete();
        }
    }
}
