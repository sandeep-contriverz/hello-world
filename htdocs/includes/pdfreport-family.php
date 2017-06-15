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

$families = new Hmg\Models\Families();

	if (isset($_REQUEST['filters']['city']) && (is_numeric($_REQUEST['filters']['city']) || is_array($_REQUEST['filters']['city']))) {
		$setting = new Hmg\Models\Setting();
		// Search is using city name and not the id coming from advanced form
		if (is_array($_REQUEST['filters']['city'])) {
			$cityNames = array();
			foreach ($_REQUEST['filters']['city'] as $cityId) {
				$cityNames[] = $setting->getSettingById($cityId);
			}
			$_REQUEST['filters']['city'] = $cityNames;
		} else {
			$_REQUEST['filters']['city'] = $setting->getSettingById($_REQUEST['filters']['city']);
		}
	}
	if (isset($_REQUEST['filters']['county']) && (is_numeric($_REQUEST['filters']['county']) || is_array($_REQUEST['filters']['county']))) {
		$setting = new Hmg\Models\Setting();
		// Search is using county name and not the id coming from advanced form
		if (is_array($_REQUEST['filters']['county'])) {
			$countyNames = array();
			foreach ($_REQUEST['filters']['county'] as $countyId) {
				$countyNames[] = $setting->getSettingById($countyId);
			}
			$_REQUEST['filters']['county'] = $countyNames;
		} else {
			$_REQUEST['filters']['county'] = $setting->getSettingById($_REQUEST['filters']['county']);
		}
	}

	if (isset($_REQUEST['data']['how_heard_details_id']) && (is_numeric($_REQUEST['data']['how_heard_details_id']) )) {
		$_REQUEST['filters']['how_heard_details_id'] =  $_REQUEST['data']['how_heard_details_id'];
	}



	$families->set('_filters', $_REQUEST['filters']); 
	$families->set('_start', 0);
	$recording = $families->getList(false,false);

	$totalRecords = count( $recording );
    $accounts = array();
	if( !empty( $recording ) && is_array( $recording ) ){
	foreach ($recording as $family) {

		$records[] = $family;
	}
    }
    
							
$accounts = $records;

$pdf =& new Clpdf($report['Name'], $report['LayoutType'], $report['ShowHeader'], explode('|', $report['Margins']));

$first = true;
	
	if (!$first) {
		$pdf->ezNewPage();
	} else {
		$first = false;
	}
	
	$pdf->addHeader();
	
	foreach($template as $obj) {
		switch ($obj->type) {
			case "pageBreak":
				$pdf->ezNewPage();
			break;
			case "space":
				$pdf->addSpace($obj->data);
			break;
			case "form":
				foreach ($obj->data as $data) {
					switch ($data->type) {
						case "row":
							foreach($data->data as $row){
								switch ($row->type) {
									case "text":
										$val = $row->value;
										
										if (trim($row->value) == '[date]') {
											$val = date("M j, Y");
										}										
										
										if (trim($row->value) == '[footer]') {
											$pdf->makeFooter(175);
											break;
										}
										
										if (trim($row->value) == '[line]') {
												$pdf->hr(10);
										} elseif (trim($row->value) == '[footer]') {
											$pdf->makeFooter(135);
										} elseif (trim($row->value) == '[s4simage]') {
											$pdf->addImg( 'imgs/s4s-report-logo.jpg', 180);
										} else {											
											$pdf->text($val, @$row->width, @$row->variableWidth, @$row->wordWrap, @$row->fontSize, @$row->bold, @$row->italic, @$row->fontFamily);
										}
									break;
									case "field":										
										$pdf->text($common->getRealByNameApplicant($row->value, $account), @$row->width, @$row->variableWidth, @$row->wordWrap, @$row->fontSize, @$row->bold, @$row->italic, @$row->fontFamily);										
									break;
									case "image": 
										$pdf->addImg($row->value);
									break;
								}
							}
							$pdf->newLine();
						break;
					}
				}				
			break;
			case "grid":				
				$pdf->addGrid($obj->data, $accounts, @$obj->sortBy, @$obj->sortByDirection, @$obj->fontSize, @$obj->fontFamily);				
			break;
			default:
				echo "Error: Unexpected data type... Not sure what to do now... XD<br><BR>";
				print_r($obj);
				die();
			break;
		}
	}


if ($first == true) $pdf->ezText("Sorry, No records found for this report");
