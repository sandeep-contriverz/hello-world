<?php

namespace Hmg\Controllers;

use Hmg\Models\Families;
use Hmg\Models\Family;
use Hmg\Models\Setting;
use Hmg\Models\Child;
use Hmg\Models\ProviderFamily;
use Hmg\Models\Provider;
use Hmg\Models\Organization;
use Hmg\Models\Contact;
use Hmg\Models\StartEnds;
use Hmg\Models\AgencyFamilyReferrals;
use Hmg\Models\AgencyChildReferrals;
use Hmg\Models\IssueFamilyReferrals;
use Hmg\Models\IssueChildReferrals;
use Hmg\Models\ServiceFamilyReferrals;
use Hmg\Models\ServiceChildReferrals;
use Hmg\Models\CountyFamilyReferrals;
use Hmg\Models\CountyChildReferrals;
use Hmg\Models\ZipFamilyReferrals;
use Hmg\Models\ZipChildReferrals;
use Hmg\Models\FamilyReferrals;
use Hmg\Models\ChildDevelopmentalScreening;
use Hmg\Models\ChildDevelopmentalScreenings;
use Hmg\Models\ReferralServices;
use Hmg\Models\ChildReferrals;
use Hmg\Models\SchoolDistrict;
use Hmg\Helpers\SessionHelper as FilterHelper;

class ReportsController
{
    public function __construct()
    {

        if (isset($_GET['filters'])) {
            $_SESSION['report-filters'] = $_GET['filters'];
        }

        $message = '';
        $reportType = (! empty($_GET['type']) ? $_GET['type'] : 'Default');
        $reportSubType = (! empty($_GET['subtype']) ? $_GET['subtype'] : '');
        $statusId     = (! empty($_GET['filters']['status']) ? $_GET['filters']['status'] : '');
        $regionId     = (! empty($_GET['filters']['region_id']) ? $_GET['filters']['region_id'] : '');

        $status = 'Any';
        if ($statusId) {
            $setting = new Setting();
            $status = $setting->getSettingById($statusId);
        } 

        $start_date = (! empty($_GET['filters']['start_date']) ? $_GET['filters']['start_date'] : '');
        $end_date   = (! empty($_GET['filters']['end_date']) ? $_GET['filters']['end_date'] : '');

        if ($start_date && $end_date) {
            $reportDate = $start_date . ' - ' . $end_date;
        } else if ($start_date) {
            $reportDate = $start_date . ' - ' . date('m/d/Y');
        } else if ($end_date) {
            $reportDate = 'thru ' . $end_date;
        } else {
            $reportDate = 'All';
        }

        switch ($reportType) {
            case 'user_defined':
                $message = '';
                switch ($reportSubType) {
                    case "family_grid":
                        if ( !empty($_GET['filters'])) {                    
                    
                            $families = new Families();

                            if (isset($_REQUEST['filters']['city']) && (is_numeric($_REQUEST['filters']['city']) || is_array($_REQUEST['filters']['city']))) {
                                $setting = new Setting();
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
                                $setting = new Setting();
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
                            if( !empty( $recording ) && is_array( $recording ) ){
                            foreach ($recording as $family) {

                                $records[] = array(
                                    'Primary Contact'          => $family['first_name_1'] . ' ' . $family['last_name_1'],
                                    'Lang'            => ucwords(strtolower($family['language'])),
                                    'Reason for call'             => $family['call_reason'],
                                    'City'              => $family['city'],
                                    'Phone'                   => $family['primary_phone'],
                                    'Email' => $family['email'],
                                    'HMG Worker' => $family['hmg_worker']
                                    );
                            }       



                            $familyListColumns = array();
                            if (isset($records[0])) {
                                $familyListColumns = array_keys($records[0]);
                            }

                            $providerInfo = array();

                            if (!empty($_REQUEST['export'])) {
                                $this->exportCSVReport(
                                    'Family Grid',
                                    'List of Families',
                                    $records
                                );
                            }


                            $pdf = new \PdfUserDefinedReport('P', 'pt');
                            $pdf->setLogoImage($GLOBALS['pdfLogo']);
                            $pdf->setFooterText($GLOBALS['footerText']);
                            $pdf->AliasNbPages();
                            $pdf->SetTextColor(70, 70, 70);
                            $pdf->SetAutoPageBreak(1, 110);
                            $pdf->SetPageSummaryData($_SESSION['user']['hmg_worker'], '', $reportDate);
                            $pdf->AddPage();
                            //$pdf->ProviderInformation($providerInfo);
                            $pdf->FamilyList($familyListColumns, $records);
                            $pdf->Output();
                            }
                            else {
                                $message = 'No Family Found.';
                            }

                        }
                        $this->displayUserDefinedReportForm($message);
                        break;
                    default:
                        $this->displayUserDefinedLinks();
                        break;
                
                }
                break;
            case 'outreach':
                if (! empty($_GET['id']) && ! empty($_GET['report'])) {
                    switch ($_GET['report']) {
                        case 'provider-family':
                            $id = explode('-',$_GET['id']);
                            $provider = new Contact();
                            $provider->setById($id[1]);
                            $providerFamily = new ProviderFamily($id[1]);
                            $organization = new Organization();
                            $organization->setById($id[0]);
                            $familyIds = $providerFamily->getList();
                            $records = array();
                            if (is_array($familyIds)) {
                                $family = new Family();
                                foreach ($familyIds as $id) {
                                    $family->setById($id);
                                    $startEnds = new StartEnds($id);
                                    $range = $startEnds->getMaxMinDates();

                                    // Filter families not in region
                                    if (!empty($_SESSION['user']['region_id'])) {
                                        if (! $family->isRegionFamily()) {
                                            continue;
                                        }
                                    }
                                    if (!empty($regionId)) {
                                        if (! $family->inRegion($regionId)) {
                                            continue;
                                        }
                                    }
                                    if ($status != 'Any') {
                                        if ($family->family['status'] != $status) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($start_date) {
                                        if (strtotime($range['formatted_start_date']) < strtotime($start_date)) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($end_date) {
                                        if (strtotime($range['formatted_start_date']) > strtotime($end_date)) {
                                            continue; // Skip this record
                                        }
                                    }

                                    if (is_array($family->family['children'])) {
                                        $screenings = new ChildDevelopmentalScreening();
                                        foreach ($family->family['children'] as $child) {
                                            $referrals = new ChildReferrals($child['id']);
                                            $numScreenings = $screenings->getChildScreeningsCount($child['id']);
                                            $daysInCurrentMonth = date('t');
                                            $d1 = new \DateTime();
                                            $d2 = new \DateTime($child['birth_date']);
                                            $interval = $d1->diff($d2);
                                            $years = $interval->format("%y");
                                            $months = $interval->format("%m");
                                            $days = $interval->format("%d");
                                            $totalMonths = $years * 12 + $months;
                                            $records[] = array(
                                                'Family Status*'          => $family->family['status'],
                                                'Parent Name'            => $family->family['first_name_1'] . ' ' . $family->family['last_name_1'],
                                                'Child Name'             => $child['first'] . ($child['last'] ? ' ' . $child['last'] : ''),
                                                'Child Age'              => ($totalMonths + round($days/$daysInCurrentMonth, 1)) . ' mo ',
                                                'City'                   => $family->family['city'],
                                                'Number of Screenings**' => ($numScreenings ? $numScreenings : '0'),
                                                'Number of Referrals***' => $referrals->getCount()
                                            );
                                        }
                                    }
                                }

                                $totalRecords = count($records);

                                $familyListColumns = array();
                                if (isset($records[0])) {
                                    $familyListColumns = array_keys($records[0]);
                                }

                                $providerInfo = $provider->contact;
                                $orgInfo = $organization->organization;
                                $providerInfo['employer'] = $orgInfo['name'].':'.$orgInfo['site'];
                                $providerInfo['fax'] = $orgInfo['fax'];
                                $providerInfo['name'] = $providerInfo['last']
                                    . ($providerInfo['first'] ? ', ' . $providerInfo['first'] : '');
                                $providerInfo['full_address'] = $orgInfo['address']
                                    . ($orgInfo['city'] ? ', ' . $orgInfo['city'] : '')
                                    . ($orgInfo['state'] ? ', ' . $orgInfo['state'] : '')
                                    . ($orgInfo['zip'] ? ' ' . $orgInfo['zip'] : '');
                                
                                
                                if (!empty($_REQUEST['export'])) {
                                    $this->exportCSVReport(
                                        'Outreach Report',
                                        $providerInfo['name']
                                            . ' - ' . $providerInfo['employer']
                                            . ': List of Families',
                                        $records
                                    );
                                }

                                $pdf = new \PdfProviderFamilies('P', 'pt');
                                $pdf->setLogoImage($GLOBALS['pdfLogo']);
                                $pdf->setFooterText($GLOBALS['footerText']);
                                $pdf->AliasNbPages();
                                $pdf->SetTextColor(70, 70, 70);
                                $pdf->SetAutoPageBreak(1, 110);
                                $pdf->SetPageSummaryData($_SESSION['user']['hmg_worker'], $status, $reportDate);
                                $pdf->AddPage();
                                $pdf->ProviderInformation($providerInfo);
                                $pdf->FamilyList($familyListColumns, $records);
                                $pdf->Output();

                            } else {
                                $message = 'No provider or agency was found.';
                            }

                            break;

                        case 'provider-family-child-id':
                            $settingOb = new Setting(); //201016
                            $provider  = new Provider();
                            $provider->setById($_GET['id']);
                            $providerFamily = new ProviderFamily($_GET['id']);
                            $familyIds = $providerFamily->getClinicList();
                            $records = array();
                            if (is_array($familyIds)) {
                                $family = new Family();
                                foreach ($familyIds as $id) {
                                    $family->setById($id);
                                    $startEnds = new StartEnds($id);
                                    $range = $startEnds->getMaxMinDates();
                                    $dateFilters = [
                                        'scored'   => null,
                                        'referral' => null
                                    ];

                                    // Filter families not in region
                                    if (!empty($_SESSION['user']['region_id'])) {
                                        if (! $family->isRegionFamily()) {
                                            continue;
                                        }
                                    }
                                    if (!empty($regionId)) {
                                        if (! $family->inRegion($regionId)) {
                                            continue;
                                        }
                                    }
                                    if ($status != 'Any') {
                                        if ($family->family['status'] != $status) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($start_date) {
                                        if (strtotime($range['formatted_start_date']) < strtotime($start_date)) {
                                            continue; // Skip this record
                                        }
                                        $dateFilters['scored']['start_date'] = $start_date;
                                        $dateFilters['referral']['start_date'] = $start_date;
                                    }
                                    if ($end_date) {
                                        if (strtotime($range['formatted_start_date']) > strtotime($end_date)) {
                                            continue; // Skip this record
                                        }
                                        $dateFilters['scored']['end_date'] = $end_date;
                                        $dateFilters['referral']['end_date'] = $end_date;
                                    }

                                    if (is_array($family->family['children'])) {
                                        foreach ($family->family['children'] as $child) {
                                            $childReferrals = new ChildReferrals($child['id']);
                                            if (count($dateFilters['referral'])) {
                                                $childReferrals->set(
                                                    '_filters',
                                                    $dateFilters['referral']
                                                );
                                                $childReferrals->set(
                                                    '_filters',
                                                    $dateFilters['referral']
                                                );
                                            }
                                            $referralList = $childReferrals->getList();
                                            $referralDates = [];
                                            $referralNames = [];
                                            $referralOutcomes = [];
                                            if ($referralList) {
                                                $setting = new Setting(); //191016
                                                foreach ($referralList as $referralItem) {
                                                    if ($referralItem['outcomes'] != '1991') {
                                                        $referralDates[] = date(
                                                            'm/d/Y',
                                                            strtotime($referralItem['referral_date'])
                                                        );
                                                        $referred = $referralItem;
                                                        if( $referred['referred_to_type']  == 'info'){
                                                            $name = $setting->getValue($referred['referred_to_id']);
                                                        }
                                                        else{
                                                            $siteSeprate = '';
                                                            if($referred['site_name']){
                                                            $siteSeprate = ': '.$referred['site_name'];
                                                            }
                                                            $name = $referred['organization_name'].$siteSeprate;
                                                        }
                                                        $referralNames[] = $name;
                                                        $referralOutcomes[] = $setting->getSettingById($referralItem['outcomes']);
                                                    }
                                                }
                                            }
                                            //echo "<pre>first ";print_r($referralNames);
                                            //echo "<pre>second ";print_r($referralOutcomes);
                                            $screenings = new ChildDevelopmentalScreenings($child['id']);
                                            $screenings->set('_sort', 'date_scored');
                                            //$screenings->set('_limit', '1');
                                            if (count($dateFilters['scored'])) {
                                                $screenings->set(
                                                    '_filters',
                                                    $dateFilters['scored']
                                                );
                                            }
                                            $childScreenings = $screenings->getList(false);
                                            $asqScore = 'Not Scored';
                                            //echo "<pre>Screenings ";print_r($childScreenings);
                                            /*if ($childScreenings) {
                                                $asqScore = $childScreenings[0]['score'];
                                                $screeningType = $childScreenings[0]['type'];
                                            }*/
                                            $screeningsArray = array();
                                            $asqScoreArray   = array();
                                            $asqMonthArray   = array();
                                            $asqScore        = '';
                                            $screeningType   = '';
                                            $asqMonth        = '';
                                            if(!empty($childScreenings)) {
                                                foreach($childScreenings as $scr) {
                                                    if($scr['child_id'] == $child['id'] ) {
                                                        array_push($screeningsArray, $scr['type']);
                                                        array_push($asqScoreArray, $scr['score']);
                                                        if(!empty($scr['asq_month']))
                                                            array_push($asqMonthArray, trim($scr['asq_month']). ' Month');
                                                    }
                                                }
                                                $asqMonthArray = array_filter($asqMonthArray);
                                                /*$asqScoreArray = !empty($asqScoreArray) 
                                                    ? array_unique($asqScoreArray) : array();*/
                                                $asqScore = implode("\n", $asqScoreArray);
                                                /*$screeningTypeArray = !empty($screeningTypeArray) 
                                                    ? array_unique($screeningTypeArray) : array();*/
                                                $screeningType = implode("\n", $screeningsArray);
                                                $asqMonth = implode("\n", $asqMonthArray);
                                            }

                                            //$screeningType = $childScreenings[0]['type']; //261016 revert back
                                            /*$asqScore = '';
                                            $asqScoreArray = array();
                                            if(!empty($childScreenings)) {
                                                foreach($childScreenings as $childS) {
                                                    array_push($asqScoreArray, $childS['score']);
                                                }
                                                $asqScoreArray = !empty($asqScoreArray) 
                                                    ? array_unique($asqScoreArray) : array();
                                                $asqScore = implode("\n", $asqScoreArray);
                                            }*/
                                            $screeningss = new ChildDevelopmentalScreening($child['id']);
                                            $numScreenings = $screeningss->getChildScreeningsCount($child['id']);
                                            $records[] = array(
                                                'Family Status'      => $family->family['status'],
                                                'Child ID'           => $child['id'],
                                                'Date of Referral'   => implode("\n", $referralDates),
                                                'Referral Name'      => implode("\n", $referralNames),
                                                'Referral Outcome'   => implode("\n", $referralOutcomes),
                                                'Screening Type'     => $screeningType,
                                                'Screening Interval' => $asqMonth,
                                                'Screening Outcome*'     => $asqScore,
                                                'Number of Screenings**' => ($numScreenings ? $numScreenings : '0'),
                                                'Number of Referrals***' => $childReferrals->getCount()
                                            );
                                        }
                                    }
                                }

                                $totalRecords = count($records);
                                //sort records using child id asc
                                usort($records, function($a, $b) {
                                    return $a['Child ID'] - $b['Child ID'];
                                });

                                $familyListColumns = array();
                                if (isset($records[0])) {
                                    $familyListColumns = array_keys($records[0]);
                                }

                                $providerInfo = $provider->provider;
                                $providerInfo['name'] = $providerInfo['last_name']
                                    . ($providerInfo['first_name'] ? ', ' . $providerInfo['first_name'] : '');
                                $providerInfo['full_address'] = $providerInfo['address']
                                    . ($providerInfo['city'] ? ', ' . $providerInfo['city'] : '')
                                    . ($providerInfo['state'] ? ', ' . $providerInfo['state'] : '')
                                    . ($providerInfo['zip'] ? ' ' . $providerInfo['zip'] : '');

                                if (!empty($_REQUEST['export'])) {
                                    $this->exportCSVReport(
                                        'Outreach Report',
                                        $providerInfo['employer'] . ': List of Families',
                                        $records,
                                        null,
                                        true
                                    );
                                }
                                include_once('./fpdf/helpers/outreach-family-child-id.class.php');
                                $pdf = new \PdfProviderFamiliesChildID('P', 'pt');
                                $pdf->setLogoImage($GLOBALS['pdfLogo']);
                                $pdf->setFooterText($GLOBALS['footerText']);
                                $pdf->AliasNbPages();
                                $pdf->SetTextColor(70, 70, 70);
                                $pdf->SetAutoPageBreak(false, 110);
                                $pdf->SetPageSummaryData($_SESSION['user']['hmg_worker'], $status, $reportDate);
                                $pdf->AddPage();
                                $pdf->ProviderInformation($providerInfo);
                                $pdf->FamilyList($familyListColumns, $records);
                                $pdf->Output();
                    
                            } else {
                                $message = 'No provider or agency was found.';
                            }

                            break;

                        case 'provider-family-clinic':
                            $settingOb = new Setting(); //201016
                            $provider  = new Provider();
                            $provider->setById($_GET['id']);
                            $providerFamily = new ProviderFamily($_GET['id']);
                            $familyIds = $providerFamily->getClinicList();
                            $records = array();
                            if (is_array($familyIds)) {
                                $family = new Family();
                                foreach ($familyIds as $id) {
                                    $family->setById($id);
                                    $startEnds = new StartEnds($id);
                                    $range = $startEnds->getMaxMinDates();
                                    $dateFilters = [
                                        'scored'   => null,
                                        'referral' => null
                                    ];

                                    // Filter families not in region
                                    if (!empty($_SESSION['user']['region_id'])) {
                                        if (! $family->isRegionFamily()) {
                                            continue;
                                        }
                                    }
                                    if (!empty($regionId)) {
                                        if (! $family->inRegion($regionId)) {
                                            continue;
                                        }
                                    }
                                    if ($status != 'Any') {
                                        if ($family->family['status'] != $status) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($start_date) {
                                        if (strtotime($range['formatted_start_date']) < strtotime($start_date)) {
                                            continue; // Skip this record
                                        }
                                        $dateFilters['scored']['start_date'] = $start_date;
                                        $dateFilters['referral']['start_date'] = $start_date;
                                    }
                                    if ($end_date) {
                                        if (strtotime($range['formatted_start_date']) > strtotime($end_date)) {
                                            continue; // Skip this record
                                        }
                                        $dateFilters['scored']['end_date'] = $end_date;
                                        $dateFilters['referral']['end_date'] = $end_date;
                                    }

                                    if (is_array($family->family['children'])) {
                                        foreach ($family->family['children'] as $child) {
                                            $childReferrals = new ChildReferrals($child['id']);
                                            if (count($dateFilters['referral'])) {
                                                $childReferrals->set(
                                                    '_filters',
                                                    $dateFilters['referral']
                                                );
                                                $childReferrals->set(
                                                    '_filters',
                                                    $dateFilters['referral']
                                                );
                                            }
                                            $referralList = $childReferrals->getList();
                                            $referralDates = [];
                                            $referralNames = [];
                                            $referralOutcomes = [];
                                            if ($referralList) {
                                                $setting = new Setting(); //191016
                                                foreach ($referralList as $referralItem) {
                                                    if ($referralItem['outcomes'] != '1991') {
                                                        $referralDates[] = date(
                                                            'm/d/Y',
                                                            strtotime($referralItem['referral_date'])
                                                        );
                                                        $referred = $referralItem;
                                                        if( $referred['referred_to_type']  == 'info'){
                                                            $name = $setting->getValue($referred['referred_to_id']);
                                                        }
                                                        else{
                                                            $siteSeprate = '';
                                                            if($referred['site_name']){
                                                            $siteSeprate = ': '.$referred['site_name'];
                                                            }
                                                            $name = $referred['organization_name'].$siteSeprate;
                                                        }
                                                        $referralNames[] = $name;
                                                        $referralOutcomes[] = $setting->getSettingById($referralItem['outcomes']);
                                                    }
                                                }
                                            }
                                            $screenings = new ChildDevelopmentalScreenings($child['id']);
                                            $screenings->set('_sort', 'date_scored');
                                            //$screenings->set('_limit', '1');
                                            if (count($dateFilters['scored'])) {
                                                $screenings->set(
                                                    '_filters',
                                                    $dateFilters['scored']
                                                );
                                            }
                                            $childScreenings = $screenings->getList(true);
                                            //echo "<pre>Screenings ";print_r($childScreenings);
                                            $asqScore = 'Not Scored';
                                            $screeningType = '';
                                            /*if ($childScreenings) {
                                                $asqScore = $childScreenings[0]['score'];
                                                $screeningType = $childScreenings[0]['type']; //261016 revert back
                                            }*/
                                            $screeningsArray = array();
                                            $asqScoreArray   = array();
                                            $asqMonthArray   = array();
                                            $asqScore        = '';
                                            $screeningType   = '';
                                            $asqMonth        = '';
                                            if(!empty($childScreenings)) {
                                                foreach($childScreenings as $scr) {
                                                    if($scr['child_id'] == $child['id'] ) {
                                                        array_push($screeningsArray, $scr['type']);
                                                        array_push($asqScoreArray, $scr['score']);
                                                        if(!empty($scr['asq_month']))
                                                            array_push($asqMonthArray, trim($scr['asq_month']). ' Month');
                                                    }
                                                }
                                                $asqMonthArray = array_filter($asqMonthArray);
                                                /*$asqScoreArray = !empty($asqScoreArray) 
                                                    ? array_unique($asqScoreArray) : array();*/
                                                $asqScore = implode("\n", $asqScoreArray);
                                                /*$screeningTypeArray = !empty($screeningTypeArray) 
                                                    ? array_unique($screeningTypeArray) : array();*/
                                                $screeningType = implode("\n", $screeningsArray);
                                                $asqMonth = implode("\n", $asqMonthArray);
                                            }
                                            $screeningss = new ChildDevelopmentalScreening($child['id']);
                                            $numScreenings = $screeningss->getChildScreeningsCount($child['id']);
                                            $records[] = array(
                                                //'Family Status'      => $family->family['status'],
                                                //'Child ID'           => $child['id'],
                                                'Child Name'         => $child['first']. ($child['last'] ? ' ' . $child['last'] : ''),
                                                'Child DOB'          => date('m/d/Y', strtotime($child['birth_date'])),
                                                'Date of Referral'   => implode("\n", $referralDates),
                                                'Referral Name'      => implode("\n", $referralNames),
                                                'Referral Outcome'   => implode("\n", $referralOutcomes),
                                                'Screening Type'     => $screeningType,
                                                'Screening Interval' => $asqMonth,
                                                'Screening Outcome*'     => $asqScore,
                                                'Number of Screenings**' => ($numScreenings ? $numScreenings : '0'),
                                                'Number of Referrals***' => $childReferrals->getCount()
                                            );
                                            /*
                                            $records[] = array(
                                                'Child Name'         => $child['first']
                                                . ($child['last'] ? ' ' . $child['last'] : ''),
                                                'Child DOB'          => date('m/d/Y', strtotime($child['birth_date'])),
                                                'Date of Referral'   => implode("\n", $referralDates),
                                                'Referral Name'      => implode("\n", $referralNames),
                                                'Referral Outcome'   => implode("\n", $referralOutcomes),
                                                'Screening Type' => $screeningType,
                                                'Screening Outcome*'     => $asqScore,
                                                'Number of Screenings**' => ($numScreenings ? $numScreenings : '0'),
                                                'Number of Referrals***' => $childReferrals->getCount()
                                            );*/
                                        }
                                    }
                                }

                                $totalRecords = count($records);

                                // Sort records by child last name
                                
                                usort($records, function ($item1, $item2) {
                                    $child1 = preg_split('/\s+/', trim($item1['Child Name']));
                                    $child2 = preg_split('/\s+/', trim($item2['Child Name']));

                                    $last1 = '';
                                    if (count($child1) > 2) {
                                        $last1 = array_pop($child1);
                                    } elseif (isset($child1[1])) {
                                        $last1 = $child1[1];
                                    }
                                    $last1 = strtolower($last1);

                                    $last2 = '';
                                    if (count($child2) > 2) {
                                        $last2 = array_pop($child2);
                                    } elseif (isset($child2[1])) {
                                        $last2 = $child2[1];
                                    }
                                    $last2 = strtolower($last2);

                                    if ($last1 == $last2) {
                                        return 0;
                                    }

                                    return ($last1 > $last2) ? 1 : -1;
                                });
                                
                                /*usort($records, function($a, $b) {
                                    return $a['Child ID'] - $b['Child ID'];
                                });*/
                                
                                $familyListColumns = array();
                                if (isset($records[0])) {
                                    $familyListColumns = array_keys($records[0]);
                                }

                                $providerInfo = $provider->provider;
                                $providerInfo['name'] = $providerInfo['last_name']
                                    . ($providerInfo['first_name'] ? ', ' . $providerInfo['first_name'] : '');
                                $providerInfo['full_address'] = $providerInfo['address']
                                    . ($providerInfo['city'] ? ', ' . $providerInfo['city'] : '')
                                    . ($providerInfo['state'] ? ', ' . $providerInfo['state'] : '')
                                    . ($providerInfo['zip'] ? ' ' . $providerInfo['zip'] : '');

                                if (!empty($_REQUEST['export'])) {
                                    $this->exportCSVReport(
                                        'Outreach Report',
                                        $providerInfo['employer'] . ': List of Families',
                                        $records,
                                        null,
                                        true
                                    );
                                }

                                $pdf = new \PdfProviderFamiliesClinic('P', 'pt');
                                $pdf->setLogoImage($GLOBALS['pdfLogo']);
                                $pdf->setFooterText($GLOBALS['footerText']);
                                $pdf->AliasNbPages();
                                $pdf->SetTextColor(70, 70, 70);
                                $pdf->SetAutoPageBreak(false, 110);
                                $pdf->SetPageSummaryData($_SESSION['user']['hmg_worker'], $status, $reportDate);
                                $pdf->AddPage();
                                $pdf->ProviderInformation($providerInfo);
                                $pdf->FamilyList($familyListColumns, $records);
                                $pdf->Output();
                    
                            } else {
                                $message = 'No provider or agency was found.';
                            }

                            break;

                        case 'provider-referrals':
                            $setting = new Setting();
                            $provider = new Provider();
                            $provider->setById($_GET['id']);
                            $providerFamily = new ProviderFamily($_GET['id']);
                            $familyIds = $providerFamily->getList();
                            $records = array();
                            if (is_array($familyIds)) {
                                $family = new Family();
                                $referralCounts = array();
                                foreach ($familyIds as $id) {
                                    $family->setById($id);

                                    // Filter families not in region
                                    if (!empty($_SESSION['user']['region_id'])) {
                                        if (! $family->isRegionFamily()) {
                                            continue;
                                        }
                                    }
                                    if (!empty($regionId)) {
                                        if (! $family->inRegion($regionId)) {
                                            continue;
                                        }
                                    }
                                    if ($status != 'Any') {
                                        if ($family->family['status'] != $status) {
                                            continue; // Skip this record
                                        }
                                    }

                                    // Get all the family referrals
                                    $familyReferrals = new FamilyReferrals($id);
                                    $familyReferralsList = $familyReferrals->getList();
                                    if (is_array($familyReferralsList)) {
                                        foreach ($familyReferralsList as $familyReferral) {
                                            if ($start_date) {
                                                if (strtotime($familyReferral['referral_date_formatted']) < strtotime($start_date)) {
                                                    continue; // Skip this record
                                                }
                                            }
                                            if ($end_date) {
                                                if (strtotime($familyReferral['referral_date_formatted']) > strtotime($end_date)) {
                                                    continue; // Skip this record
                                                }
                                            }

                                            if ($familyReferral['referred_to_id']) {
                                                
                                                $referred = $familyReferral;
                                                if( $referred['referred_to_type']  == 'info'){
                                                    $name = $setting->getValue($referred['referred_to_id']);
                                                }
                                                else{
                                                    $siteSeprate = '';
                                                    if($referred['site_name']){
                                                    $siteSeprate = ': '.$referred['site_name'];
                                                    }
                                                    $name = $referred['organization_name'].$siteSeprate;
                                                }

                                                if ($name == '') {
                                                    $name = 'Other';
                                                }
                                                if (isset($referralCounts[$name])) {
                                                    $referralCounts[$name]++;
                                                } else {
                                                    $referralCounts[$name] = 1;
                                                }
                                            }
                                        }
                                    }
                                    // Loop over referrals. Sum on referral agency
                                    if (is_array($family->family['children'])) {
                                        foreach ($family->family['children'] as $child) {
                                            // Get all the child referrals
                                            $childReferrals = new ChildReferrals($child['id']);
                                            $childReferralsList = $childReferrals->getList();
                                            if (is_array($childReferralsList)) {
                                                foreach ($childReferralsList as $childReferral) {
                                                    if ($start_date) {
                                                        if (strtotime($childReferral['referral_date_formatted']) < strtotime($start_date)) {
                                                            continue; // Skip this record
                                                        }
                                                    }
                                                    if ($end_date) {
                                                        if (strtotime($childReferral['referral_date_formatted']) > strtotime($end_date)) {
                                                            continue; // Skip this record
                                                        }
                                                    }

                                                    if ($childReferral['referred_to_id']) {

                                                        $referred = $childReferral;
                                                        if( $referred['referred_to_type']  == 'info'){
                                                            $name = $setting->getValue($referred['referred_to_id']);
                                                        }
                                                        else{
                                                            $siteSeprate = '';
                                                            if($referred['site_name']){
                                                            $siteSeprate = ': '.$referred['site_name'];
                                                            }
                                                            $name = $referred['organization_name'].$siteSeprate;
                                                        }
                                                        
                                                        if ($name == '') {
                                                            $name = 'Other';
                                                        }
                                                        if (isset($referralCounts[$name])) {
                                                            $referralCounts[$name]++;
                                                        } else {
                                                            $referralCounts[$name] = 1;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                ksort($referralCounts);

                                $totalRecords = count($referralCounts);

                                $columns = array(0 => 'Referral Name', 1 => 'Number of Referrals');

                                $providerInfo = $provider->provider;
                                $providerInfo['name'] = $providerInfo['last_name']
                                    . ($providerInfo['first_name'] ? ', ' . $providerInfo['first_name'] : '');
                                $providerInfo['full_address'] = $providerInfo['address']
                                    . ($providerInfo['city'] ? ', ' . $providerInfo['city'] : '')
                                    . ($providerInfo['state'] ? ', ' . $providerInfo['state'] : '')
                                    . ($providerInfo['zip'] ? ' ' . $providerInfo['zip'] : '');

                                if (!empty($_REQUEST['export'])) {
                                    $records = [];
                                    foreach ($referralCounts as $key => $value) {
                                        $records[] = array(
                                            'Referral Name' => $key,
                                            'Number Of Referrals' => $value
                                        );
                                    }
                                    
                                    $this->exportCSVReport(
                                        'Outreach Report',
                                        $providerInfo['name']
                                            . ' - ' . $providerInfo['employer']
                                            . ': List of Referrals',
                                        $records
                                    );
                                }

                                $pdf=new \PdfProviderReferrals('P', 'pt');
                                $pdf->setLogoImage($GLOBALS['pdfLogo']);
                                $pdf->setFooterText($GLOBALS['footerText']);
                                $pdf->AliasNbPages();
                                $pdf->SetTextColor(70, 70, 70);
                                $pdf->AddPage();
                                $pdf->PageSummary($_SESSION['user']['hmg_worker'], $status, $reportDate);
                                $pdf->ProviderInformation($providerInfo);
                                $pdf->ReferralList($columns, $referralCounts);
                                $pdf->Output();

                            } else {
                                $message = 'No provider or agency was found.';
                            }

                            break;

                        case 'agency-referrals':
                            $id = $_GET['id'];
                            $setting = new Setting();
                            $agency = $setting->getSettingById($id);
                            $referralServices = new ReferralServices($id);
                            $referralServices->setServices();
                            $services = $referralServices->get('services');
                            $agencyFamilyReferrals = new AgencyFamilyReferrals($id);
                            $familyReferrals = $agencyFamilyReferrals->getList();
                            $agencyChildReferrals = new AgencyChildReferrals($id);
                            $childReferrals = $agencyChildReferrals->getList();

                            // Tally outcomes for each referral
                            $records = array();
                            $setting = new Setting(); //191016
                            if (is_array($familyReferrals)) {
                                $family = new Family();
                                foreach ($familyReferrals as $referral) {
                                    $service = $referral['service'] ? $referral['service'] : 'Service Unknown';
                                    $outcome = $referral['outcomes'] ? $setting->getSettingById($referral['outcomes']) : 'Outcome Unknown';

                                    $family->setById($id);

                                    // Filter families not in region
                                    if (!empty($_SESSION['user']['region_id'])) {
                                        if (! $family->isRegionFamily()) {
                                            continue;
                                        }
                                    }
                                    if (!empty($regionId)) {
                                        if (! $family->inRegion($regionId)) {
                                            continue;
                                        }
                                    }
                                    if ($status != 'Any') {
                                        if ($family->family['status'] != $status) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($start_date) {
                                        if (strtotime($referral['referral_date_formatted']) < strtotime($start_date)) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($end_date) {
                                        if (strtotime($referral['referral_date_formatted']) > strtotime($end_date)) {
                                            continue; // Skip this record
                                        }
                                    }

                                    if (! isset($records[$service])) {
                                        $records[$service] = array(
                                            'Service'              => $service,
                                            'Information Received' => 0,
                                            'Connected'            => 0,
                                            'Not Connected'        => 0,
                                            'Outcome Unknown'      => 0,
                                            'Outcome Pending'      => 0,
                                            'Total'                => 0
                                        );
                                    }
                                    if (! isset($records[$service][$outcome])) {
                                        $records[$service][$outcome] = 1;
                                    } else {
                                        $records[$service][$outcome] += 1;
                                    }
                                }
                            }

                            if (is_array($childReferrals)) {
                                $family = new Family();
                                $child  = new Child();
                                $setting = new Setting(); //191016
                                foreach ($childReferrals as $referral) {
                                    $service = $referral['service'] ? $referral['service'] : 'Service Unknown';
                                    $outcome = $referral['outcomes'] ? $setting->getSettingById($referral['outcomes']) : 'Outcome Unknown';

                                    // Look up family information using the child_id
                                    $child->setById($referral['child_id']);
                                    $family->setById($child->child['parent_id']);

                                    // Filter families not in region
                                    if (!empty($_SESSION['user']['region_id'])) {
                                        if (! $family->isRegionFamily()) {
                                            continue;
                                        }
                                    }
                                    if (!empty($regionId)) {
                                        if (! $family->inRegion($regionId)) {
                                            continue;
                                        }
                                    }
                                    if ($status != 'Any') {
                                        if ($family->family['status'] != $status) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($start_date) {
                                        if (strtotime($referral['referral_date_formatted']) < strtotime($start_date)) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($end_date) {
                                        if (strtotime($referral['referral_date_formatted']) > strtotime($end_date)) {
                                            continue; // Skip this record
                                        }
                                    }

                                    if (! isset($records[$service])) {
                                        $records[$service] = array(
                                            'Service'              => $service,
                                            'Information Received' => 0,
                                            'Connected'            => 0,
                                            'Not Connected'        => 0,
                                            'Outcome Unknown'      => 0,
                                            'Outcome Pending'      => 0,
                                            'Total'                => 0
                                        );
                                    }

                                    if (! isset($records[$service][$outcome])) {
                                        $records[$service][$outcome] = 1;
                                    } else {
                                        $records[$service][$outcome] += 1;
                                    }
                                }
                            }

                            asort($records);

                            foreach ($records as $key => &$record) {
                                $record['Total'] = $record['Information Received'] + $record['Connected'] + $record['Not Connected'] + $record['Outcome Unknown'];
                            }

                            $agencyInfo = array('name' => $agency, 'services' => $services);

                            $columns = array(
                                0 => 'Service',
                                1 => 'Information Received',
                                2 => 'Connected',
                                3 => 'Not Connected',
                                4 => 'Outcome Unknown',
                                5 => 'Outcome Pending',
                                6 => 'Total'
                            );

                            if (!empty($_REQUEST['export'])) {
                                $this->exportCSVReport(
                                    'Outreach Report',
                                    $agencyInfo['name'] . ': Referrals and Outcomes',
                                    $records,
                                    array('Service Terms:' => $agencyInfo['services'])
                                );
                            }

                            $pdf = new $GLOBALS['agencyReportClass']('P', 'pt');
                            $pdf->setLogoImage($GLOBALS['pdfLogo']);
                            $pdf->setFooterText($GLOBALS['footerText']);
                            $pdf->AliasNbPages();
                            $pdf->SetTextColor(70, 70, 70);
                            $pdf->SetAutoPageBreak(1, 70);
                            $pdf->SetPageSummaryData($_SESSION['user']['hmg_worker'], $status, $reportDate);
                            $pdf->AddPage();
                            $pdf->AgencyInformation($agencyInfo);
                            $pdf->ReferralList($columns, $records);
                            $pdf->Output();

                            break;

                        default:
                            echo 'Invalid Report Type';
                    }

                } else {
                    if (isset($_GET['report'])) {
                        $message = 'Error: Missing provider or agency information';
                    }
                }
                $this->displayOutreachReportForm($status, $regionId, $start_date, $end_date, $message);
                break;

            case 'referral':
                if (isset($_GET['report'])) {
                    switch ($_GET['report']) {
                        case 'agency-referrals-family':
                            $id = $_GET['id'];

                            $setting = new Setting();
                            $agency = $setting->getSettingById($id);

                            $referralServices = new ReferralServices($id);
                            $referralServices->setServices();
                            $services = $referralServices->get('services');

                            $agencyFamilyReferrals = new AgencyFamilyReferrals($id);
                            $agencyFamilyReferrals->set('_sorts', array('service' => 'ASC', 'outcomes' => 'ASC'));
                            $familyReferrals = $agencyFamilyReferrals->getList();

                            // Tally outcomes for each referral
                            $records = array();
                            if (is_array($familyReferrals)) {
                                $family = new Family();
                                $setting = new Setting(); //191016
                                foreach ($familyReferrals as $referral) {
                                    $service = $referral['service'] ? $referral['service'] : 'Service Unknown';
                                    $outcome = $referral['outcomes'] ? $setting->getSettingById($referral['outcomes']) : 'Outcome Unknown';

                                    $family->setById($referral['family_id']);

                                    // Filter families not in region
                                    if (!empty($_SESSION['user']['region_id'])) {
                                        if (! $family->isRegionFamily()) {
                                            continue;
                                        }
                                    }
                                    if (!empty($regionId)) {
                                        if (! $family->inRegion($regionId)) {
                                            continue;
                                        }
                                    }
                                    if ($status != 'Any') {
                                        if ($family->family['status'] != $status) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($start_date) {
                                        if (strtotime($referral['referral_date_formatted']) < strtotime($start_date)) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($end_date) {
                                        if (strtotime($referral['referral_date_formatted']) > strtotime($end_date)) {
                                            continue; // Skip this record
                                        }
                                    }

                                    $recordKey = $family->family['first_name_1'] . $family->family['last_name_1'] . $referral['family_id'];
                                    $recordKey = strtolower(preg_replace('/\s+/', '', $recordKey));

                                    $records[$recordKey][] = array(
                                        'Family Status' => $family->family['status'],
                                        'Parent Name'   => $family->family['first_name_1'] . ' ' . $family->family['last_name_1'],
                                        'City'          => $family->family['city'],
                                        'Phone'         => $family->family['primary_phone'],
                                        'Service'       => $service,
                                        'Outcome'       => trim($outcome)
                                    );
                                }
                            }

                            //ksort($records);

                            if ($_REQUEST['export']) {
                                $this->exportAngencyReferralsAsCsv($_GET['report'], 'Agency: ' . $agency, $records);
                            }

                            $pdf = new \PdfReferralsAgencyFamily('P', 'pt');
                            $pdf->setLogoImage($GLOBALS['pdfLogo']);
                            $pdf->setFooterText($GLOBALS['footerText']);
                            $pdf->AliasNbPages();
                            $pdf->SetTextColor(70, 70, 70);
                            //$pdf->SetAutoPageBreak(1, 70);
                            $pdf->SetPageSummaryData($_SESSION['user']['hmg_worker'], $status, $reportDate);
                            $pdf->AddPage();
                            $pdf->AgencyInformation(array('name' => $agency, 'services' => $services));
                            $columns = array(
                                'Family Status',
                                'Parent Name',
                                'City',
                                'Phone',
                                'Service',
                                'Outcome'
                            );
                            $pdf->ReferralList($columns, $records);
                            $pdf->Output();

                            break;

                        case 'agency-referrals-child':
                            $id = $_GET['id'];

                            $setting = new Setting();
                            $agency = $setting->getSettingById($id);

                            $referralServices = new ReferralServices($id);
                            $referralServices->setServices();
                            $services = $referralServices->get('services');

                            $agencyChildReferrals = new AgencyChildReferrals($id);
                            $agencyChildReferrals->set('_sorts', array('service' => 'ASC', 'outcomes' => 'ASC'));
                            $childReferrals = $agencyChildReferrals->getList();

                            // Tally outcomes for each referral
                            $records = array();
                            if (is_array($childReferrals)) {
                                $family = new Family();
                                $child  = new Child();
                                $setting = new Setting(); //191016
                                foreach ($childReferrals as $referral) {
                                    $service = $referral['service'] ? $referral['service'] : 'Service Unknown';
                                    $outcome = $referral['outcomes'] ? $setting->getSettingById($referral['outcomes']) : 'Outcome Unknown';

                                    // Look up family information using the child_id
                                    $child->setById($referral['child_id']);
                                    $family->setById($child->child['parent_id']);

                                    // Filter families not in region
                                    if (!empty($_SESSION['user']['region_id'])) {
                                        if (! $family->isRegionFamily()) {
                                            continue;
                                        }
                                    }
                                    if (!empty($regionId)) {
                                        if (! $family->inRegion($regionId)) {
                                            continue;
                                        }
                                    }
                                    if ($status != 'Any') {
                                        if ($family->family['status'] != $status) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($start_date) {
                                        if (strtotime($referral['referral_date_formatted']) < strtotime($start_date)) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($end_date) {
                                        if (strtotime($referral['referral_date_formatted']) > strtotime($end_date)) {
                                            continue; // Skip this record
                                        }
                                    }

                                    $recordKey = $family->family['first_name_1'] . $family->family['last_name_1'] . $child->child['parent_id'];
                                    $recordKey = strtolower(preg_replace('/\s+/', '', $recordKey));

                                    $records[$recordKey][] = array(
                                        'Family Status' => $family->family['status'],
                                        'Parent Name'   => $family->family['first_name_1'] . ' ' . $family->family['last_name_1'],
                                        'Child Name'    => $child->child['first'],
                                        'City'          => $family->family['city'],
                                        'Phone'         => $family->family['primary_phone'],
                                        'Service'       => $service,
                                        'Outcome'       => trim($outcome)
                                    );
                                }
                            }

                            //ksort($records);

                            if ($_REQUEST['export']) {
                                $this->exportAngencyReferralsAsCsv($_GET['report'], 'Agency: ' . $agency, $records);
                            }

                            $pdf = new \PdfReferralsAgencyChild('P', 'pt');
                            $pdf->setLogoImage($GLOBALS['pdfLogo']);
                            $pdf->setFooterText($GLOBALS['footerText']);
                            $pdf->AliasNbPages();
                            $pdf->SetTextColor(70, 70, 70);
                            //$pdf->SetAutoPageBreak(1, 70);
                            $pdf->SetPageSummaryData($_SESSION['user']['hmg_worker'], $status, $reportDate);
                            $pdf->AddPage();
                            $pdf->AgencyInformation(array('name' => $agency, 'services' => $services));
                            $columns = array(
                                'Family Status',
                                'Parent Name',
                                'Child Name',
                                'City',
                                'Phone',
                                'Service',
                                'Outcome'
                            );
                            $pdf->ReferralList($columns, $records);
                            $pdf->Output();

                            break;

                        case 'issue-referrals':
                            $id = $_GET['id'];

                            $setting = new Setting();
                            $issue = $setting->getSettingById($id);

                            $issueFamilyReferrals = new IssueFamilyReferrals($id);
                            $familyReferrals = $issueFamilyReferrals->getList();

                            $issueChildReferrals = new IssueChildReferrals($id);
                            $childReferrals = $issueChildReferrals->getList();

                            // Tally outcomes for each referral
                            $records = array();

                            if (is_array($familyReferrals)) {
                                $family = new Family();
                                $setting = new Setting(); //191016
                                foreach ($familyReferrals as $referral) {
                                    $referred = $referral;
                                    if( $referred['referred_to_type']  == 'info'){
                                        $name = $setting->getValue($referred['referred_to_id']);
                                    }
                                    else{
                                        $siteSeprate = '';
                                        if($referred['site_name']){
                                        $siteSeprate = ': '.$referred['site_name'];
                                        }
                                        $name = $referred['organization_name'].$siteSeprate;
                                    }
                                    
                                    $referral['referred_to'] = $referral['referred_to_id'] ? $name : 'Referral Unknown';
                                    $referral['service'] = $referral['service_id'] ? $setting->getSettingById($referral['service_id']) : 'Service Unknown';
                                    $outcome = $referral['outcomes'] ? $setting->getSettingById($referral['outcomes']) : 'Outcome Unknown';

                                    $family->setById($referral['family_id']);

                                    // Filter families not in region
                                    if (!empty($_SESSION['user']['region_id'])) {
                                        if (! $family->isRegionFamily()) {
                                            continue;
                                        }
                                    }
                                    if (!empty($regionId)) {
                                        if (! $family->inRegion($regionId)) {
                                            continue;
                                        }
                                    }
                                    if ($status != 'Any') {
                                        if ($family->family['status'] != $status) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($start_date) {
                                        if (strtotime($referral['referral_date_formatted']) < strtotime($start_date)) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($end_date) {
                                        if (strtotime($referral['referral_date_formatted']) > strtotime($end_date)) {
                                            continue; // Skip this record
                                        }
                                    }

                                    $referralKey = $referral['referred_to_id'] . '-' . $referral['service_id'] . $outcome;
                                    $referralKey = strtolower(preg_replace('/\s+/', '', $referralKey));

                                    if (isset($records[$referralKey])) {
                                        $records[$referralKey]['Count'] += $referral['totals'];
                                    } else {
                                        $records[$referralKey] = array(
                                            'Referral Name' => $referral['referred_to'],
                                            'Service'       => $referral['service'],
                                            'Outcome'       => trim($outcome),
                                            'Count'         => (int) $referral['totals']
                                        );
                                    }
                                }
                            }

                            if (is_array($childReferrals)) {
                                $family = new Family();
                                $child  = new Child();
                                $setting = new Setting(); //191016
                                foreach ($childReferrals as $referral) {
                                    
                                    $referred = $referral;
                                    if( $referred['referred_to_type']  == 'info'){
                                        $name = $setting->getValue($referred['referred_to_id']);
                                    }
                                    else{
                                        $siteSeprate = '';
                                        if($referred['site_name']){
                                        $siteSeprate = ': '.$referred['site_name'];
                                        }
                                        $name = $referred['organization_name'].$siteSeprate;
                                    }
                                    
                                    $referral['referred_to'] = $referral['referred_to_id'] ? $name : 'Referral Unknown';
                                    $referral['service'] = $referral['service_id'] ? $setting->getSettingById($referral['service_id']) : 'Service Unknown';

                                    $outcome = $referral['outcomes'] ? $setting->getSettingById($referral['outcomes']) : 'Outcome Unknown';

                                    // Look up family informaiton using the child_id
                                    $child->setById($referral['child_id']);
                                    $family->setById($child->child['parent_id']);

                                    // Filter families not in region
                                    if (!empty($_SESSION['user']['region_id'])) {
                                        if (! $family->isRegionFamily()) {
                                            continue;
                                        }
                                    }
                                    if (!empty($regionId)) {
                                        if (! $family->inRegion($regionId)) {
                                            continue;
                                        }
                                    }
                                    if ($status != 'Any') {
                                        if ($family->family['status'] != $status) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($start_date) {
                                        if (strtotime($referral['referral_date_formatted']) < strtotime($start_date)) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($end_date) {
                                        if (strtotime($referral['referral_date_formatted']) > strtotime($end_date)) {
                                            continue; // Skip this record
                                        }
                                    }

                                    $referralKey = $referral['referred_to_id'] . '-' . $referral['service_id'] . $outcome;
                                    $referralKey = strtolower(preg_replace('/\s+/', '', $referralKey));

                                    if (isset($records[$referralKey])) {
                                        $records[$referralKey]['Count'] += $referral['totals'];
                                    } else {
                                        $records[$referralKey] = array(
                                            'Referral Name' => $referral['referred_to'],
                                            'Service'       => $referral['service'],
                                            'Outcome'       => trim($outcome),
                                            'Count'         => (int) $referral['totals']
                                        );
                                    }
                                }
                            }

                            usort($records, array('Hmg\Controllers\reportsController', "compareReferralNameOutcome"));

                            if ($_REQUEST['export']) {
                                $this->exportReferralsAsCsv($_GET['report'], '"Issue: ' . $issue . '"', $records);
                            }

                            $pdf = new \PdfReferralsIssue('P', 'pt');
                            $pdf->setLogoImage($GLOBALS['pdfLogo']);
                            $pdf->setFooterText($GLOBALS['footerText']);
                            $pdf->AliasNbPages();
                            $pdf->SetTextColor(70, 70, 70);
                            //$pdf->SetAutoPageBreak(1, 70);
                            $pdf->SetPageSummaryData($_SESSION['user']['hmg_worker'], $status, $reportDate);
                            $pdf->AddPage();
                            $pdf->IssueInformation(array('name' => $issue, 'services' => null));
                            $columns = array(
                                'Referral Name',
                                'Service',
                                'Outcome',
                                'Count'
                            );
                            $pdf->ReferralList($columns, $records);
                            $pdf->Output();
                            break;
                        case 'service-referrals':
                            $id = $_GET['id'];

                            $setting = new Setting();
                            $service = $setting->getSettingById($id);

                            $serviceFamilyReferrals = new ServiceFamilyReferrals($id);
                            $familyReferrals = $serviceFamilyReferrals->getList();

                            $serviceChildReferrals = new ServiceChildReferrals($id);
                            $childReferrals = $serviceChildReferrals->getList();

                            //Tally outcomes for each referral
                            $records = array();

                            if (is_array($familyReferrals)) {
                                $family = new Family();
                                $setting = new Setting(); //191016
                                foreach ($familyReferrals as $referral) {
                                    
                                    $referred = $referral;
                                    if( $referred['referred_to_type']  == 'info'){
                                        $name = $setting->getValue($referred['referred_to_id']);
                                    }
                                    else{
                                        $siteSeprate = '';
                                        if($referred['site_name']){
                                        $siteSeprate = ': '.$referred['site_name'];
                                        }
                                        $name = $referred['organization_name'].$siteSeprate;
                                    }
                                    
                                    $referral['referred_to'] = $referral['referred_to_id'] ? $name : 'Referral Unknown';
                                    $referral['service'] = $referral['service_id'] ? $setting->getSettingById($referral['service_id']) : 'Service Unknown';

                                    $outcome = $referral['outcomes'] ? $setting->getSettingById($referral['outcomes']) : 'Outcome Unknown';

                                    $family->setById($referral['family_id']);

                                    // Filter families not in region
                                    if (!empty($_SESSION['user']['region_id'])) {
                                        if (! $family->isRegionFamily()) {
                                            continue;
                                        }
                                    }
                                    if (!empty($regionId)) {
                                        if (! $family->inRegion($regionId)) {
                                            continue;
                                        }
                                    }
                                    if ($status != 'Any') {
                                        if ($family->family['status'] != $status) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($start_date) {
                                        if (strtotime($referral['referral_date_formatted']) < strtotime($start_date)) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($end_date) {
                                        if (strtotime($referral['referral_date_formatted']) > strtotime($end_date)) {
                                            continue; // Skip this record
                                        }
                                    }

                                    $referralKey = $referral['referred_to_id'] . '-' . $referral['service_id'] . $outcome;
                                    $referralKey = strtolower(preg_replace('/\s+/', '', $referralKey));

                                    if (isset($records[$referralKey])) {
                                        $records[$referralKey]['Count'] += $referral['totals'];
                                    } else {
                                        $records[$referralKey] = array(
                                            'Referral Name' => $referral['referred_to'],
                                            'Outcome'       => trim($outcome),
                                            'Count'         => (int) $referral['totals']
                                        );
                                    }
                                }
                            }

                            if (is_array($childReferrals)) {
                                $family = new Family();
                                $child  = new Child();
                                $setting = new Setting(); //191016
                                foreach ($childReferrals as $referral) {
                                    $referred = $referral;
                                    if( $referred['referred_to_type']  == 'info'){
                                        $name = $setting->getValue($referred['referred_to_id']);
                                    }
                                    else{
                                        $siteSeprate = '';
                                        if($referred['site_name']){
                                        $siteSeprate = ': '.$referred['site_name'];
                                        }
                                        $name = $referred['organization_name'].$siteSeprate;
                                    }
                                    
                                    $referral['referred_to'] = $referral['referred_to_id'] ? $name : 'Referral Unknown';
                                    $referral['service'] = $referral['service_id'] ? $setting->getSettingById($referral['service_id']) : 'Service Unknown';

                                    $outcome = $referral['outcomes'] ? $setting->getSettingById($referral['outcomes']) : 'Outcome Unknown';

                                    // Look up family informaiton using the child_id
                                    $child->setById($referral['child_id']);

                                    if (!$child->child) {
                                        continue; // Skip this record
                                    }

                                    $family->setById($child->child['parent_id']);

                                    // Filter families not in region
                                    if (!empty($_SESSION['user']['region_id'])) {
                                        if (! $family->isRegionFamily()) {
                                            continue;
                                        }
                                    }
                                    if (!empty($regionId)) {
                                        if (! $family->inRegion($regionId)) {
                                            continue;
                                        }
                                    }
                                    if ($status != 'Any') {
                                        if ($family->family['status'] != $status) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($start_date) {
                                        if (strtotime($referral['referral_date_formatted']) < strtotime($start_date)) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($end_date) {
                                        if (strtotime($referral['referral_date_formatted']) > strtotime($end_date)) {
                                            continue; // Skip this record
                                        }
                                    }

                                    $referralKey = $referral['referred_to_id'] . '-' . $referral['service_id'] . $outcome;
                                    $referralKey = strtolower(preg_replace('/\s+/', '', $referralKey));

                                    if (isset($records[$referralKey])) {
                                        $records[$referralKey]['Count'] += $referral['totals'];
                                    } else {
                                        $records[$referralKey] = array(
                                            'Referral Name' => $referral['referred_to'],
                                            'Outcome'       => trim($outcome),
                                            'Count'         => (int) $referral['totals']
                                        );
                                    }
                                }
                            }

                            usort($records, array('Hmg\Controllers\reportsController', "compareReferralNameOutcome"));

                            if ($_REQUEST['export']) {
                                $this->exportReferralsAsCsv($_GET['report'], '"Service: ' . $service . '"', $records);
                            }

                            $pdf = new \PdfReferralsService('P', 'pt');
                            $pdf->setLogoImage($GLOBALS['pdfLogo']);
                            $pdf->setFooterText($GLOBALS['footerText']);
                            $pdf->AliasNbPages();
                            $pdf->SetTextColor(70, 70, 70);
                            //$pdf->SetAutoPageBreak(1, 70);
                            $pdf->SetPageSummaryData($_SESSION['user']['hmg_worker'], $status, $reportDate);
                            $pdf->AddPage();
                            $pdf->ServiceInformation(array('name' => $service, 'services' => null));
                            $columns = array(
                                'Referral Name',
                                'Outcome',
                                'Count'
                            );
                            $pdf->ReferralList($columns, $records);
                            $pdf->Output();
                            break;

                        case 'zip-referrals':
                            $zipCodes = $_GET['zipcodes'];

                            if (is_array($zipCodes)) {
                                $zipCodesList = implode(', ', $zipCodes);
                            }

                            $zipFamilyReferrals = new ZipFamilyReferrals($zipCodes);
                            $familyReferrals = $zipFamilyReferrals->getList();

                            $zipChildReferrals = new ZipChildReferrals($zipCodes);
                            $childReferrals = $zipChildReferrals->getList();

                            // Tally outcomes for each referral
                            $records = array();

                            if (is_array($familyReferrals)) {
                                $family = new Family();
                                $setting = new Setting(); //191016
                                foreach ($familyReferrals as $referral) {
                                    
                                    $referred = $referral;
                                    if( $referred['referred_to_type']  == 'info'){
                                        $name = $setting->getValue($referred['referred_to_id']);
                                    }
                                    else{
                                        $siteSeprate = '';
                                        if($referred['site_name']){
                                        $siteSeprate = ': '.$referred['site_name'];
                                        }
                                        $name = $referred['organization_name'].$siteSeprate;
                                    }
                                    
                                    $referral['referred_to'] = $referral['referred_to_id'] ? $name : 'Referral Unknown';
                                    $referral['service'] = $referral['service_id'] ? $setting->getSettingById($referral['service_id']) : 'Service Unknown';

                                    $outcome = $referral['outcomes'] ? $setting->getSettingById($referral['outcomes']) : 'Outcome Unknown';

                                    $family->setById($referral['family_id']);

                                    // Filter families not in region
                                    if (!empty($_SESSION['user']['region_id'])) {
                                        if (! $family->isRegionFamily()) {
                                            continue;
                                        }
                                    }
                                    if (!empty($regionId)) {
                                        if (! $family->inRegion($regionId)) {
                                            continue;
                                        }
                                    }
                                    if ($status != 'Any') {
                                        if ($family->family['status'] != $status) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($start_date) {
                                        if (strtotime($referral['referral_date_formatted']) < strtotime($start_date)) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($end_date) {
                                        if (strtotime($referral['referral_date_formatted']) > strtotime($end_date)) {
                                            continue; // Skip this record
                                        }
                                    }

                                    $referralKey = $referral['referred_to_id'] . '-' . $referral['service_id'] . $outcome;
                                    $referralKey = strtolower(preg_replace('/\s+/', '', $referralKey));

                                    if (isset($records[$referralKey])) {
                                        $records[$recordKey][$referralKey]['Count'] += $referral['totals'];
                                    } else {
                                        $records[$referralKey] = array(
                                            'Referral Name' => $referral['referred_to'],
                                            'Service'       => $referral['service'],
                                            'Outcome'       => trim($outcome)
                                        );
                                        if (count($zipCodes) > 1) {
                                            $records[$referralKey]['Zip Codes'] = $referral['zipcodes'];
                                        }
                                        $records[$referralKey]['Count'] = (int) $referral['totals'];
                                    }
                                }
                            }

                            if (is_array($childReferrals)) {
                                $family = new Family();
                                $child  = new Child();
                                $setting = new Setting(); //191016
                                foreach ($childReferrals as $referral) {
                                    $referred = $referral;
                                    if( $referred['referred_to_type']  == 'info'){
                                        $name = $setting->getValue($referred['referred_to_id']);
                                    }
                                    else{
                                        $siteSeprate = '';
                                        if($referred['site_name']){
                                        $siteSeprate = ': '.$referred['site_name'];
                                        }
                                        $name = $referred['organization_name'].$siteSeprate;
                                    }
                                    
                                    $referral['referred_to'] = $referral['referred_to_id'] ? $name : 'Referral Unknown';
                                    $referral['service'] = $referral['service_id'] ? $setting->getSettingById($referral['service_id']) : 'Service Unknown';
                                    $outcome = $referral['outcomes'] ? $setting->getSettingById($referral['outcomes']) : 'Outcome Unknown';

                                    // Look up family informaiton using the child_id
                                    $child->setById($referral['child_id']);
                                    $family->setById($child->child['parent_id']);

                                    // Filter families not in region
                                    if (!empty($_SESSION['user']['region_id'])) {
                                        if (! $family->isRegionFamily()) {
                                            continue;
                                        }
                                    }
                                    if (!empty($regionId)) {
                                        if (! $family->inRegion($regionId)) {
                                            continue;
                                        }
                                    }
                                    if ($status != 'Any') {
                                        if ($family->family['status'] != $status) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($start_date) {
                                        if (strtotime($referral['referral_date_formatted']) < strtotime($start_date)) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($end_date) {
                                        if (strtotime($referral['referral_date_formatted']) > strtotime($end_date)) {
                                            continue; // Skip this record
                                        }
                                    }

                                    $referralKey = $referral['referred_to_id'] . '-' . $referral['service_id'] . $outcome;
                                    $referralKey = strtolower(preg_replace('/\s+/', '', $referralKey));

                                    if (isset($records[$referralKey])) {
                                        $records[$referralKey]['Count'] += $referral['totals'];
                                    } else {
                                        $records[$referralKey] = array(
                                            'Referral Name' => $referral['referred_to'],
                                            'Service'       => $referral['service'],
                                            'Outcome'       => trim($outcome)
                                        );
                                        if (count($zipCodes) > 1) {
                                            $records[$referralKey]['Zip Codes'] = $referral['zipcodes'];
                                        }
                                        $records[$referralKey]['Count'] = (int) $referral['totals'];
                                    }
                                }
                            }

                            usort($records, array('Hmg\Controllers\reportsController', "compareFrequent"));

                            if ($_REQUEST['export']) {
                                $this->exportReferralsAsCsv($_GET['report'], '"Zip Codes: ' . $zipCodesList . '"', $records);
                            }

                            $pdf = new \PdfReferralsZip('P', 'pt');
                            $pdf->setLogoImage($GLOBALS['pdfLogo']);
                            $pdf->setFooterText($GLOBALS['footerText']);
                            $pdf->AliasNbPages();
                            $pdf->SetTextColor(70, 70, 70);
                            //$pdf->SetAutoPageBreak(1, 70);
                            $pdf->SetPageSummaryData($_SESSION['user']['hmg_worker'], $status, $reportDate);
                            $pdf->AddPage();
                            $pdf->ZipInformation(array('name' => $zipCodesList, 'services' => null));
                            $columns = array(
                                'Referral Name',
                                'Service',
                                'Outcome'
                            );
                            if (count($zipCodes) > 1) {
                                array_push($columns, 'Zip Codes');
                            }
                            array_push($columns, 'Count');
                            $pdf->ReferralList($columns, $records);
                            $pdf->Output();
                            break;

                        case 'county-referrals':
                            $counties = $_GET['counties'];

                            $setting = new Setting();
                            if (is_array($counties)) {
                                $countiesList = implode(', ', $counties);
                            }

                            $countyFamilyReferrals = new CountyFamilyReferrals($counties);
                            $familyReferrals = $countyFamilyReferrals->getList();

                            $countyChildReferrals = new CountyChildReferrals($counties);
                            $childReferrals = $countyChildReferrals->getList();

                            // Tally outcomes for each referral
                            $records = array();

                            if (is_array($familyReferrals)) {
                                $family = new Family();
                                $setting = new Setting(); //191016
                                foreach ($familyReferrals as $referral) {
                                    $referred = $referral;
                                    if( $referred['referred_to_type']  == 'info'){
                                        $name = $setting->getValue($referred['referred_to_id']);
                                    }
                                    else{
                                        $siteSeprate = '';
                                        if($referred['site_name']){
                                        $siteSeprate = ': '.$referred['site_name'];
                                        }
                                        $name = $referred['organization_name'].$siteSeprate;
                                    }
                                    
                                    $referral['referred_to'] = $referral['referred_to_id'] ? $name : 'Referral Unknown';
                                    $referral['service'] = $referral['service_id'] ? $setting->getSettingById($referral['service_id']) : 'Service Unknown';
                                    $outcome = $referral['outcomes'] ? $setting->getSettingById($referral['outcomes']) : 'Outcome Unknown';

                                    $family->setById($referral['family_id']);

                                    // Filter families not in region
                                    if (!empty($_SESSION['user']['region_id'])) {
                                        if (! $family->isRegionFamily()) {
                                            continue;
                                        }
                                    }
                                    if (!empty($regionId)) {
                                        if (! $family->inRegion($regionId)) {
                                            continue;
                                        }
                                    }
                                    if ($status != 'Any') {
                                        if ($family->family['status'] != $status) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($start_date) {
                                        if (strtotime($referral['referral_date_formatted']) < strtotime($start_date)) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($end_date) {
                                        if (strtotime($referral['referral_date_formatted']) > strtotime($end_date)) {
                                            continue; // Skip this record
                                        }
                                    }

                                    $referralKey = $referral['referred_to_id'] . '-' . $referral['service_id'] . $outcome;
                                    $referralKey = strtolower(preg_replace('/\s+/', '', $referralKey));

                                    if (isset($records[$referralKey])) {
                                        $records[$referralKey]['Count'] += $referral['totals'];
                                    } else {
                                        $records[$referralKey] = array(
                                            'Referral Name' => $referral['referred_to'],
                                            'Service'       => $referral['service'],
                                            'Outcome'       => trim($outcome)
                                        );
                                        if (count($counties) > 1) {
                                            $records[$referralKey]['Counties'] = $referral['counties'];
                                        }
                                        $records[$referralKey]['Count'] = (int) $referral['totals'];
                                    }
                                }
                            }

                            if (is_array($childReferrals)) {
                                $family = new Family();
                                $child  = new Child();
                                $setting = new Setting(); //191016
                                foreach ($childReferrals as $referral) {
                                    $referred = $referral;
                                    if( $referred['referred_to_type']  == 'info'){
                                        $name = $setting->getValue($referred['referred_to_id']);
                                    }
                                    else{
                                        $siteSeprate = '';
                                        if($referred['site_name']){
                                        $siteSeprate = ': '.$referred['site_name'];
                                        }
                                        $name = $referred['organization_name'].$siteSeprate;
                                    }
                                    
                                    $referral['referred_to'] = $referral['referred_to_id'] ? $name : 'Referral Unknown';
                                    $referral['service'] = $referral['service_id'] ? $setting->getSettingById($referral['service_id']) : 'Service Unknown';
                                    $outcome = $referral['outcomes'] ? $setting->getSettingById($referral['outcomes']) : 'Outcome Unknown';

                                    // Look up family informaiton using the child_id
                                    $child->setById($referral['child_id']);
                                    $family->setById($child->child['parent_id']);

                                    // Filter families not in region
                                    if (!empty($_SESSION['user']['region_id'])) {
                                        if (! $family->isRegionFamily()) {
                                            continue;
                                        }
                                    }
                                    if (!empty($regionId)) {
                                        if (! $family->inRegion($regionId)) {
                                            continue;
                                        }
                                    }
                                    if ($status != 'Any') {
                                        if ($family->family['status'] != $status) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($start_date) {
                                        if (strtotime($referral['referral_date_formatted']) < strtotime($start_date)) {
                                            continue; // Skip this record
                                        }
                                    }
                                    if ($end_date) {
                                        if (strtotime($referral['referral_date_formatted']) > strtotime($end_date)) {
                                            continue; // Skip this record
                                        }
                                    }

                                    $referralKey = $referral['referred_to_id'] . '-' . $referral['service_id'] . $outcome;
                                    $referralKey = strtolower(preg_replace('/\s+/', '', $referralKey));

                                    if (isset($records[$referralKey])) {
                                        $records[$referralKey]['Count'] += $referral['totals'];
                                    } else {
                                        $records[$referralKey] = array(
                                            'Referral Name' => $referral['referred_to'],
                                            'Service'       => $referral['service'],
                                            'Outcome'       => trim($outcome)
                                        );
                                        if (count($counties) > 1) {
                                            $records[$referralKey]['Counties'] = $referral['counties'];
                                        }
                                        $records[$referralKey]['Count'] = (int) $referral['totals'];
                                    }
                                }
                            }

                            usort($records, array('Hmg\Controllers\reportsController', "compareReferralNameOutcome"));

                            if ($_REQUEST['export']) {
                                $this->exportReferralsAsCsv($_GET['report'], '"Counties: ' . $countiesList . '"', $records);
                            }

                            $pdf = new \PdfReferralsCounty('P', 'pt');
                            $pdf->setLogoImage($GLOBALS['pdfLogo']);
                            $pdf->setFooterText($GLOBALS['footerText']);
                            $pdf->AliasNbPages();
                            $pdf->SetTextColor(70, 70, 70);
                            //$pdf->SetAutoPageBreak(1, 70);
                            $pdf->SetPageSummaryData($_SESSION['user']['hmg_worker'], $status, $reportDate);
                            $pdf->AddPage();
                            $pdf->CountyInformation(array('name' => $countiesList, 'services' => null));
                            $columns = array(
                                'Referral Name',
                                'Service',
                                'Outcome'
                            );
                            if (count($counties) > 1) {
                                array_push($columns, 'Counties');
                            }
                            array_push($columns, 'Count');

                            $pdf->ReferralList($columns, $records);
                            $pdf->Output();
                            break;
                        default:
                    }

                } else {
                    if (isset($_GET['report'])) {
                        $message = 'Error: Report type';
                    }
                    $this->displayReferralReportForm($status, $regionId, $start_date, $end_date, $message);
                }
                break;

            case 'annual':
                if (isset($_GET['subtype'])) {
                    $status     = (! empty($_SESSION['report-filters']['status']) ? $_SESSION['report-filters']['status'] : '');
                    $start_date = (! empty($_SESSION['report-filters']['start_date']) ? $_SESSION['report-filters']['start_date'] : '');
                    $end_date   = (! empty($_SESSION['report-filters']['end_date']) ? $_SESSION['report-filters']['end_date'] : '');

                    switch ($_GET['subtype']) {
                        case 'hmg-export':
                            if (empty($_GET['reportKey'])) {
                                $this->displayAnnualHMGReportForm($status, $start_date, $end_date, $message);
                            } else {
                                $this->exportAnnualHMGReportForm($_GET['reportKey'], $status, $start_date, $end_date, $message);
                            }
                            break;
                        case 'common-indicators':
                            //$this->displayAnnualHMGReportForm($status, $start_date, $end_date, $message);
                            break;
                        case 'client-tracker':
                            //$this->displayAnnualHMGReportForm($status, $start_date, $end_date, $message);
                            break;
                        default:
                    }

                } else {
                    if (isset($_GET['report'])) {
                        $message = 'Error: Report type';
                    }
                    $this->displayAnnualReportForm($status, $start_date, $end_date, $message);
                }
                break;
            default:
                $this->displayLinks();
        }
    }

    public function displayAnnualReportForm($status = null, $start_date = null, $end_date = null, $message = null)
    {

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/reports-annual-form.phtml');
        $main_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/admin.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function displayAnnualHMGReportForm($status = null, $start_date = null, $end_date = null, $message = null)
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getReportFilters();

        // Used for displaying family selects
        $family = new Family();
        $status = new Setting('status');
        $county = new Setting('county');
        $region = new Setting('region');

        $schoolDistrict = new SchoolDistrict();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/reports-annual-hmg-form.phtml');
        $main_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/admin.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function exportAnnualHMGReportForm($reportKey, $status = null, $start_date = null, $end_date = null, $message = null)
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getReportFilters();

        $schoolDistrict = new SchoolDistrict();
 
        ob_start();
        include(VIEW_PATH . '/reports-hmg-annual-export.phtml');
        $csv = ob_get_contents();
        ob_end_clean();

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-hmg-export-' . $reportKey . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $csv;
    }

    public function exportAngencyReferralsAsCsv($reportType, $reportHeader, $records = array())
    {
        if (! count($records)) {
            echo 'Nothing to Export! No records found for ' . $reportHeader;
            exit;
        }

        // Grab values for header
        $singleRecord = array_pop($records);
        array_push($records, $singleRecord);

        $columns = array_keys($singleRecord[0]);
        $columnCount = count($columns);
        $paddingCount = $columnCount - 1;

        $blankLine = "\n";

        $export = ucwords(str_replace('-', ' ', $reportType)) . ' Export' . $blankLine . $blankLine;
        $export .= $reportHeader . $blankLine . $blankLine;

        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getReportFilters();

        // Adds filtered by
        $filteredBy = '';
        if (count($filters)) {
            $export .= 'Active Filters:'  . "\n";
        }

        $setting = new Setting();
        foreach ($filters as $filter => $value) {
            // if value is numeric then we need to look up it's name in the settings table
            if (is_numeric($value) && $filter != 'zip') {
                $value = $setting->getValue($value);
            }
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            if ($value) {
                $filteredBy .=
                    ($filteredBy ? ' - ' : '') .
                    ucwords(str_replace('_', ' ', str_replace('_id', ' ', $filter))) .
                    ': ' .
                    $value;
            }
        }
        $export .= $filteredBy . "\n";
        $export .= $blankLine;

        $export .= implode($columns, ',');

        foreach ($records as $record) {
            foreach ($record as $key => &$valueArray) {
                foreach ($valueArray as $key => $value) {
                    $valueArray[$key] = (strpos($value, ',') !== false ? '"' . $value . '"' : $value);
                }
            }
            $export .= ($export ? "\n" : '') . implode($record[0], ',');
        }

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-hmg-export-' . $reportType . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $export;
        exit;
    }

    public function exportReferralsAsCsv($reportType, $reportHeader, $records = array())
    {
        if (! count($records)) {
            echo 'Nothing to Export! No records found for ' . $reportHeader;
            exit;
        }

        // Grab values for header
        $singleRecord = array_pop($records);
        array_push($records, $singleRecord);

        $columns = array_keys($singleRecord);
        $columnCount = count($columns);
        $paddingCount = $columnCount - 1;

        $blankLine = "\n";

        $export = ucwords(str_replace('-', ' ', $reportType)) . ' Export' . $blankLine . $blankLine;
        $export .= $reportHeader . $blankLine . $blankLine;

        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getReportFilters();

        // Adds filtered by
        $filteredBy = '';
        if (count($filters)) {
            $export .= 'Active Filters:'  . "\n";
        }

        $setting = new Setting();
        foreach ($filters as $filter => $value) {
            // if value is numeric then we need to look up it's name in the settings table
            if (is_numeric($value) && $filter != 'zip') {
                $value = $setting->getValue($value);
            }
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            if ($value) {
                $filteredBy .=
                    ($filteredBy ? ' - ' : '') .
                    ucwords(str_replace('_', ' ', str_replace('_id', ' ', $filter))) .
                    ': ' .
                    $value;
            }
        }
        $export .= $filteredBy . "\n";
        $export .= $blankLine;

        $export .= implode($columns, ',');

        foreach ($records as $record) {
            foreach ($record as $key => &$value) {
                $value = (strpos($value, ',') !== false ? '"' . $value . '"' : $value);
            }
            $export .= ($export ? "\n" : '') . implode($record, ',');
        }

        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-hmg-export-' . $reportType . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $export;
        exit;
    }

    public function exportCSVReport(
        $reportType,
        $reportHeader,
        $records,
        $additionalHeader = null,
        $is_count = false
    ) {
        if (! count($records)) {
            echo 'Nothing to Export! No records found for ' . $reportHeader;
            exit;
        }
        $sumReferrals = $sumScreenings = $sumChildren = 0;
        // Add values for header from a row
        // Since we don't know if we'll have $records[0]
        // just pop and element off and then put it back
        $firstRow = array_shift($records);
        $header = array_keys($firstRow);
        array_unshift($records, $firstRow);
        array_unshift($records, $header);

        $blankLine = "\n";

        $export = $reportType . $blankLine . $blankLine;

        if (strpos($reportHeader, ',') !== false) {
            $reportHeader = '"' . $reportHeader . '"';
        }
        
        $export .= $reportHeader;

        if (is_array($additionalHeader)) {
            $key = key($additionalHeader);
            $terms = [];
            foreach ($additionalHeader as $items) {
                foreach ($items as $item) {
                    $terms[] = $item['name'];
                }
            }

            $values = implode("\r\n", $terms);

            $export .= "\n" . $key . ',"' . $values . '"';
        }

        $export .= $blankLine . $blankLine;

        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getReportFilters();

        // Adds filtered by
        $filteredBy = '';
        if (count($filters)) {
            $export .= 'Active Filters:'  . "\n";
        }

        $setting = new Setting();
        foreach ($filters as $filter => $value) {
            // if value is numeric then we need to look up it's name in the settings table
            if (is_numeric($value) && $filter != 'zip') {
                $value = $setting->getValue($value);
            }
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            if ($value) {
                $filteredBy .=
                    ($filteredBy ? ' - ' : '') .
                    ucwords(str_replace('_', ' ', str_replace('_id', ' ', $filter))) .
                    ': ' .
                    $value;
            }
        }
        $export .= $filteredBy . "\n";

        foreach ($records as $record) {
           
            if($is_count) {
                $sumReferrals += isset($record['Number of Referrals***'])
                        ? $record['Number of Referrals***'] : 0;
                unset($record['Number of Referrals***']);
                if(isset($record[9]) && $record[9] == 'Number of Referrals***')
                    unset($record[9]);

                $sumScreenings += isset($record['Number of Screenings**'])
                        ? $record['Number of Screenings**'] : 0;
                $sumChildren += isset($record['Child ID']) ? 1 : 0;
            }
            
            foreach ($record as &$value) {
                $value = str_replace("\n", "\r\n", $value);
                $hasComma = strpos($value, ',') !== false;
                $hasNewLines = strpos($value, PHP_EOL) !== false;
                if ($hasComma || $hasNewLines) {
                    $value = '"' . $value . '"';
                }
            }
            $export .= ($export ? "\n" : '') . implode($record, ',');
        }
        if($is_count) {
            $export .= $blankLine.', Total # Children: '.$sumChildren;
            $export .= ', , Total # Referrals: '.$sumReferrals;
            $export .= ', , , , , Total # Screenings: '.$sumScreenings;
        }
        
        // Send CSV headers
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename='
            . strtolower(getenv('ENVIRONMENT'))
            . '-hmg-export-' . $reportType . date('Ymd') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Load content
        print $export;
        exit;
    }

    public function displayOutreachReportForm($status = null, $regionId = null, $start_date = null, $end_date = null, $message = null)
    {
        $statusSetting = new Setting('status');
        $region = new Setting('region');

        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getReportFilters();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/reports-outreach-form.phtml');
        $main_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/admin.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }
    
    public function displayUserDefinedReportForm ($message )
    {
        $statusSetting = new Setting('status');
        $region = new Setting('region');

        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getReportFilters();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/reports-user-defined-form.phtml');
        $main_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/admin.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function displayReferralReportForm($status = null, $regionId = null, $start_date = null, $end_date = null, $message = null)
    {
        $statusSetting = new Setting('status');
        $region = new Setting('region');

        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getReportFilters();

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/reports-referrals-form.phtml');
        $main_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/admin.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public function displayLinks()
    {
        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/reports.phtml');
        $main_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/admin.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }
    
    public function displayUserDefinedLinks()
    {
        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/displayUserDefinedLinks.phtml');
        $main_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/admin.phtml');
        $viewHtml = ob_get_contents();
        ob_end_clean();

        // Load content into site template
        ob_start();
        include(TEMPLATE_PATH . TEMPLATE);
        $content = ob_get_contents();
        ob_end_clean();
        print $content;
    }

    public static function compareFrequent($a, $b)
    {
        $sort = 0;

        if ($a['Count'] > $b['Count']) {
            $sort = - 1;
        } else if ($a['Count'] < $b['Count']) {
            $sort = 1;
        }

        return $sort;
    }

    public static function compareReferralNameOutcome($a, $b)
    {
        $sort = 0;

        if ($a['Referral Name'] . $a['Outcome'] < $b['Referral Name'] . $b['Outcome']) {
            $sort = - 1;
        } else if ($a['Referral Name'] . $a['Outcome'] > $b['Referral Name'] . $b['Outcome']) {
            $sort = 1;
        }

        return $sort;
    }
}
