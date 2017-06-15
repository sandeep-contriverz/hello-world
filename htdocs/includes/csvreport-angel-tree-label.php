<?php 
ini_set('display_errors', 0);
error_reporting(0);
//echo 21212;
require('../vendor/autoload.php');
include_once "../config/default.php";

include_once('../Models/common.class.php');

$common = new Hmg\Models\Common();

//echo "<pre>";print_r($common);die;

$db = new Hmg\Controllers\DbController($host, $user, $pass, $database);
$db->connect();

header('Cache-Control: no-cache');
header('Pragma: no-cache');

$filter_by = '';
$join_clause = '';

$join_clause .= " JOIN Application ON Application.ApplicantID=Applicant.ApplicantID ";
$join_clause .= " JOIN Child ON Child.ApplicantID=Applicant.ApplicantID ";

set_time_limit(60*10);
//echo "<pre>";print_r($_REQUEST);
if ($_REQUEST['filter_type'] == 0 && !empty($_REQUEST['individual'])) {
    if (is_numeric($_REQUEST['individual'])) {
        $filter_by .= ($filter_by ? ' AND ' : '')
        ." Applicant.ApplicantID = '".$_REQUEST['individual']."' ";
    } elseif (is_array($_REQUEST['individual'])) {
        $filter_by .= ($filter_by ? ' AND (' : '(');
        $i = 0;
        foreach($_REQUEST['individual'] as $id) {
            if($i>0) {
                $filter_by .= " OR ";
            }
            $filter_by .= "Applicant.ApplicantID = '".$_REQUEST['individual']."'";
        }
        $filter_by .= ")";
    } 
} else {
        if (!empty($_POST['city'])) $filter_by .= ($filter_by ? ' AND ' : ''). " City='". $_POST['city']."'";
    if (!empty($_POST['zip'])) $filter_by .= ($filter_by ? ' AND ' : ''). " Zip='". $_POST['zip']."'";
    if (!empty($_POST['AdvSearchLanguage'])) {
        // needs criterion languages
    }
    if (!empty($_POST['program'])) {
        if ($_POST['program'] == 'p') {
            $filter_by .= ($filter_by ? ' AND ' : '')
            ." Application.Status='". $_POST['Pending']."'";
        } elseif ($_POST['program'] == 'd') {
            $filter_by .= ($filter_by ? ' AND ' : '')
            ." Application.Status='". $_POST['Denied']."'";
        } else {
            $filter_by .= ($filter_by ? ' AND ' : '')
            ." Application.ProgramID='". $_POST['program']."'";
        }
    }
    if (!empty($_POST['from_year']) && !empty($_POST['from_day'])) {
        $filter_by .= ($filter_by ? ' AND ' : '')
            ." Application.ApplicationDate >= '". $_POST['from_year'] . '-' . $_POST['from_month'] . '-' . $_POST['from_day']."'";
    }
    if (!empty($_POST['to_year']) && !empty($_POST['to_day'])) {
        $filter_by .= ($filter_by ? ' AND ' : '')
            ." Application.ApplicationDate <= '". $_POST['to_year'] . '-' . $_POST['to_month'] . '-' . $_POST['to_day']."'";
    }
    
    if (!empty($_POST['program_year'])) {
        $filter_by .= ($filter_by ? ' AND ' : '')
            ." Application.Year='". $_POST['program_year']."'";
    }
    
    if (!empty($_POST['program']) || !empty($_POST['program_year']) || (!empty($_POST['from_year']) && !empty($_POST['from_day'])) || (!empty($_POST['to_year']) && !empty($_POST['to_day']))) {
        //$join_clause .= " JOIN Application ON Applicant.ApplicantID=Application.ApplicantID ";
    }
    
    if (!empty($_POST['workshop'])) {
        $filter_by .= ($filter_by ? ' AND ' : '')
            ." Applicant.WorkshopID='". $_POST['workshop']."'";
    }
    if (!empty($_POST['workshop_num'])) {
        $filter_by .= ($filter_by ? ' AND ' : '')
            ." Applicant.WorkshopNumber='". $_POST['workshop_num']."'";
    }

    if (!empty($_POST['atDropoffSite'])) {
        if (!empty($_POST['program_year'])) {
            $join_clause .= " JOIN ApplicationProgramData ON Application.ApplicationID=ApplicationProgramData.ApplicationID ";
            $filter_by .= ($filter_by ? ' AND ' : '')
            ." ApplicationProgramData.Field='ATDropoffSiteID AND ApplicationProgramData.Value='".$_POST['atDropoffSite']."'";
        } else {
            //$join_clause .= " JOIN Application ON Applicant.ApplicantID=Application.ApplicantID ";
            $filter_by .= ($filter_by ? ' AND ' : '')
            ." Applicant.Year='".$common->getSettingCurrentyear()."'";
            $join_clause .= " JOIN ApplicationProgramData ON Application.ApplicationID=ApplicationProgramData.ApplicationID ";
            $filter_by .= ($filter_by ? ' AND ' : '')
            ." ApplicationProgramData.Field='ATDropoffSiteID AND ApplicationProgramData.Value='".$_POST['atDropoffSite']."' AND Application.Year='".$common->getSettingCurrentyear()."'";

        }
    }
    
    if (!empty($_POST['matched'])) {
        $join_clause .= " JOIN ApplicationSponsorMatch ON ApplicationSponsorMatch.ApplicationID=Application.ApplicationID ";
        
        if ($_POST['matched'] == 'm') {
            $filter_by .= ($filter_by ? ' AND ' : '')
                ." ApplicationSponsorMatch.ApplicationSponsorMatchID IS NOT NULL'";
        } else {
            $filter_by .= ($filter_by ? ' AND ' : '')
                ." ApplicationSponsorMatch.ApplicationSponsorMatchID IS NULL'";
        }
    }
}

$sql = "Select * from Applicant ".$join_clause." Where 1 ";

$sql .= ($filter_by ? ' AND ' . $filter_by : '');

$sql .= " Group By Child.ChildID ";

// Here's our temporary limit.
$sql .= " LIMIT 0, 500";
//echo $sql;

$result = mysql_query($sql);
$accounts = array();
while($applicant = mysql_fetch_array($result, MYSQL_ASSOC)) {
    $accounts[] = $applicant;
}
/*
echo '<pre>';
print_r($accounts);
exit;*/

 $filename = 'AngelTreeLabelReport' . date('Y-m-d') . '.csv';
// send response headers to the browser
ob_start();
$csvHeader = array("Code","Name","Gender","Age","Pant Size","Shirt Size","ShoeSize","Favorite Color","Books","Wish");
$fp = fopen('php://output', 'w');
fputcsv($fp, $csvHeader);
foreach($accounts as $child){
	//$applicant = ApplicantPeer::retrieveByPK($child->getApplicantId());
    //$code = $applicantClass->createCode($child['ProgramID'], $child['WorkshopID'], $child['WorkshopNum'], $child['NumberOfKids'], $child['City'], $child['Status']);
    $sql2 = "Select * from Applicant Where ApplicantID='".$child['ApplicantID']."'";
    $result2 = mysql_query($sql2);
    $applicant = mysql_fetch_array($result2, MYSQL_ASSOC);
    //echo "<pre>";print_r($applicant);die;
    $row = array(
	   'code' => $common->getRealByNameApplicant('Code', $applicant),
        'name' => $common->getRealByNameChild('Name', $child),
        'gender' => $common->getRealByNameChild('Gender', $child),
        'age' => $common->getRealByNameChild('Age', $child),
        'pantsize' => $common->getRealByNameChild('PantSize', $child),
        'shirtsize' => $common->getRealByNameChild('ShirtSize', $child),
        'shoesize' => $common->getRealByNameChild('ShoeSize', $child),
        'color' => $common->getRealByNameChild('FavoriteColor', $child),
        'books' => $common->getRealByNameChild('Books', $child),
        'giftideas' => $common->getRealByNameChild('GiftIdeas', $child),
    );
    fputcsv($fp, $row);
}
fclose($fp);
$out = ob_get_contents();
ob_end_clean();
header( 'Content-Type: text/csv' );
header( 'Content-Disposition: attachment;filename='.$filename);
echo trim($out);
