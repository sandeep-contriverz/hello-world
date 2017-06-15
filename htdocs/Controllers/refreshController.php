<?php

namespace Hmg\Controllers;

class RefreshController
{
    public function __construct()
    {
        header('Content-Type: aplication/json');
        echo '{"expireTime":"' . strtotime('+20 minute') . '"}';
    }
}
