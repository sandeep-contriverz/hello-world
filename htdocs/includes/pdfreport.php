<?php
header('Cache-Control: no-cache');
header('Pragma: no-cache');
error_reporting(E_ALL);
require('../../vendor/autoload.php');
include_once "../../config/default.php";
ini_set('display_errors',1);
error_reporting(1);


$db = new Hmg\Controllers\DbController($host, $user, $pass, $database);
$db->connect();


set_time_limit(0);



require_once ('class.clpdf.php');

$sql = "Select * from Report Where ReportID='".$_REQUEST['reportID']."'";
$result = mysql_query($sql);
$report = mysql_fetch_array($result, MYSQL_ASSOC);
$template = json_decode($report['Template']);

$sql = "Update Report set LastGenerated='".date('Y-m-d H:i:s')."' Where ReportID='".$_REQUEST['reportID']."'";
$result = mysql_query($sql);

switch($report['ReportCategoryID']) {
	case '1':
		include('pdfreport-family.php');
		break;
    case '2':
		include('pdfreport-org.php');
		break;
    case '3':
        
		include('pdfreport-events.php');
		break;
	
}


if (!empty($_REQUEST['manualTitle'])) {		
	$file = $_REQUEST['manualTitle'] . '.pdf';	
} else {	
	$file = $report['Name'] . '.pdf';
}

$file = str_replace(" ", "-", $file);
$file = preg_replace('/[^a-z0-9-.]/i', '', $file);
$options = array('Content-Disposition'=>urlencode($file));


//header('Content-Description: File Transfer');
//header('Content-Type: application/octet-stream');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
//header('Content-Length: ' . filesize($file));
//header('Content-Disposition: attachment; filename='.$file);
header('Content-type: application/pdf');
//$pdf->ezStream();*/

echo $pdf->output();
