<?php
header('Cache-Control: no-cache');
header('Pragma: no-cache');
error_reporting(E_ALL);
//echo "['test1','test2','test3','test4','ietsanders']";die;
require('../vendor/autoload.php');
include_once "../config/default.php";
$db = new Hmg\Controllers\DbController($host, $user, $pass, $database);
$db->connect();

	//ob_start();	
	//echo "<pre>";print_r($_REQUEST);die;
	//$_REQUEST['type'] = 10;
	$output = array();
	switch(@$_REQUEST['type']) {
		case APPLICANTS:
		case APPLICANT_GRID:
		case ANGEL_TREE_LABELS:
			$search = explode(' ', $_REQUEST['term'], 2);
			
			if (count($search) == 2) {
				
			$sql="SELECT * FROM `Applicant` WHERE (`FirstName` LIKE '%".$search[0]."%' AND `LastName` LIKE '%".$search[1]."%') OR (`FirstName` LIKE '%".$search[1]."%' AND `LastName` LIKE '%".$search[0]."%')";			
			} else {
				
			$sql="SELECT * FROM `Applicant` WHERE (`FirstName` LIKE '%".$search[0]."%') OR (`LastName` LIKE '%".$search[0]."%')";
					
			}
			$sql.=" ORDER by `LastName` ASC limit 10";
			$result = mysql_query($sql);
			//echo $sql;die;
			while($applicant = mysql_fetch_array($result, MYSQL_ASSOC)){
				$output[] = array( 'value' => $applicant['ApplicantID'], 'label' => $applicant['LastName'] . ' ' . $applicant['FirstName']);
			}
		break;
		case SPONSORS:
		case SPONSOR_GRID:
			
			$sql="SELECT * FROM `Sponsor` WHERE (`FirstName` LIKE '%".$_REQUEST['term']."%') OR (`LastName` LIKE '%".$_REQUEST['term']."%')";	
			
			$sql.=" ORDER by `LastName` ASC limit 10";
			$result = mysql_query($sql);
			while($applicant = mysql_fetch_array($result, MYSQL_ASSOC)){
				$output[] = array( 'value' => $applicant['LastName'] . ', ' . $applicant['FirstName'], 'label' => $applicant['LastName'] . ', ' . $applicant['FirstName']);
			}
		break;
		case GOLDEN_ANGELS:
		case GOLDEN_ANGEL_GRID:
		case GOLDEN_ANGEL_LABELS:
			
			$sql="SELECT * FROM `GoldenAngelAgency` WHERE (`Name` LIKE '%".$_REQUEST['term']."%')";
			
			$sql.=" ORDER by `Name` ASC limit 10";
			//echo $sql;die;
			$result = mysql_query($sql);
			while($applicant = mysql_fetch_array($result, MYSQL_ASSOC)){
				$output[] = array( 'value' => $applicant['GoldenAngelAgencyID'], 'label' => $applicant['Name']);
			}
		break;
		case VOLUNTEERS:
		case VOLUNTEER_GRID:
			
			$sql="SELECT * FROM `Volunteer` WHERE (`FirstName` LIKE '%".$_REQUEST['term']."%') OR (`LastName` LIKE '%".$_REQUEST['term']."%')";	
			
			$sql.=" ORDER by `LastName` ASC limit 10";
			$result = mysql_query($sql);
			while($applicant = mysql_fetch_array($result, MYSQL_ASSOC)){
				$output[] = array( 'value' => $applicant['LastName'] . ', ' . $applicant['FirstName'], 'label' => $applicant['LastName'] . ', ' . $applicant['FirstName']);
			}
		break;
	}
	
	echo json_encode($output);die;
