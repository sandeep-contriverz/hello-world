<?php

namespace Hmg\Controllers;

use Hmg\Models\Zip;

class ZipController
{
    public function __construct()
    {

        $zip = new Zip();

        if (! empty($_REQUEST['zip'])) {
            $county = $zip->getCountyByZip($_REQUEST['zip']);

            header('Content-Type: application/json');
            echo json_encode($county);

        } else if (isset($_REQUEST['city'])) {
            $county = $zip->getCountyByCity($_REQUEST['city']);

            header('Content-Type: application/json');
            echo json_encode($county);
        } else if (isset($_REQUEST['search'])) {
            header('Content-Type: application/json');
            echo json_encode(array_keys($zip->zipcodes));
        }
    }
}
