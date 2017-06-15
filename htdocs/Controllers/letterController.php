<?php

namespace Hmg\Controllers;

use Hmg\Models\Letter;
use Hmg\Models\Child;

class LetterController
{
    public function __construct()
    {
        $l = new Letter();

        $l->setById($_REQUEST['letter_id']);

        $c = new Child();
        $c->setById($_REQUEST['child_id']);

    }
}
