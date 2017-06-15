<?php

namespace Hmg\Controllers;

use Hmg\Models\Families;
use Hmg\Models\Family;
use Hmg\Models\City;
use Hmg\Models\Child;
use Hmg\Models\ChildDevelopmentalScreenings;
use Hmg\Models\ScreeningAttachments;
use Hmg\Models\ChildPriorResources;
use Hmg\Models\FamilyReferrals;
use Hmg\Models\FamilyFollowUps;
use Hmg\Models\ChildReferrals;
use Hmg\Models\ChildFollowUps;
use Hmg\Models\Setting;
use Hmg\Models\ChildNotes;
use Hmg\Models\Letter;
use Hmg\Models\FamilyProvider;
use Hmg\Models\RegionCounties;
use Hmg\Models\User;
use Hmg\Helpers\SessionHelper as FilterHelper;

class ChildController
{
    public function __construct()
    {

        $families = new Families();
        $family = new Family();
        $city = new City();

        $pos = (isset($_REQUEST['pos']) ? $_REQUEST['pos'] : '');
        $child_id = (isset($_REQUEST['child_id']) ? $_REQUEST['child_id'] : '');

        if (is_numeric($pos)) {
            $families->set('_search', null);
            if (isset($_SESSION['families']['filters'])) {
                $childFilters = $_SESSION['families']['filters'];
                if ($_SESSION['list-type'] != 'families-list' && isset($childFilters['quick'])) {
                    unset($childFilters['quick']);
                }
                $families->set('_filters', $childFilters);
            }
            $families->set('_start', $_REQUEST['pos']);
            if (isset($_SESSION['family-sorts'])) {
                $families->set('_sorts', $_SESSION['family-sorts']);
            }
            if ($child_id) {
                $child = new Child();
                $child->setById($child_id);
                $family->setById($child->child["parent_id"]);
            } else {
                $families->set('_limit', 1);
                $data = $families->getList();
                $family->setById($data[0]['id']);
            }
        } else if (is_numeric($child_id)) {
            $child = new Child();
            $child->setById($child_id);
            $family->setById($child->child["parent_id"]);
            // If we don't have a family record position then try and look it up
            if (!$pos && $child_id) {
                $pos = $families->getFamilyPositionById($child->child["parent_id"]);
                $clearFilters = 1;
            }
        }
        //save child Notes
        if (isset($_REQUEST['save']) && isset($_REQUEST['json'])) {
            $data = json_decode($_REQUEST['json']);
            $newNote = (array) $data;

            $child = new Child();
            $child->saveNotes($newNote);
            header('Content-Type: aplication/json');
            echo json_encode(array('status' => true, 'msg' => 'updated'));die;
        }
        //delete child Notes
        if (isset($_REQUEST['delete_note']) && isset($_REQUEST['json'])) {
            $data = json_decode($_REQUEST['json']);
            $newNote = (array) $data;

            $child = new Child();
            $child->deleteNotes($newNote);
            header('Content-Type: aplication/json');
            echo json_encode(array('status' => true, 'msg' => 'deleted'));die;
        }

        // Check family county.
        // If this is a region user and county doesn't match allowed categories
        // Then redirect to family list.
        if (!empty($_SESSION['user']['region_id'])) {
            $regionCounties = new RegionCounties($_SESSION['user']['region_id']);
            $countiesList = $regionCounties->getList();
            $countyNames = [];
            foreach ($countiesList as $result) {
                $countyNames[] = $result['county'];
            }
            if (!in_array($family->family['county'], $countyNames)) {
                header('Location: /index.php?action=families');
            }
        }

        if ($child_id && isset($family) && isset($_REQUEST['letter_id'])) {
            $this->displayLetter($family, $child_id, $_REQUEST['letter_id']);
        } else if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'pdf') {
            $setting = new Setting();
            $user = new User();
            $user->setByHmgWorker($family->family['hmg_worker']);

            $pdf = new \PdfChildInfo('P', 'pt');
            $pdf->setLogoImage($GLOBALS['pdfLogo']);
            $pdf->setCreatedBy($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']);
            $pdf->setHmgWorker($user->user['first_name']);
            $pdf->setFooterText($GLOBALS['footerText']);
            $pdf->AliasNbPages();
            $pdf->SetTextColor(70, 70, 70);
            $pdf->SetAutoPageBreak(1, 70);
            $pdf->AddPage();

            $family->family['full_name'] = $family->family['last_name_1'] . ($family->family['first_name_1'] ? ', ' . $family->family['first_name_1'] : '');
			
			
		
		$family->family['full_address'] = $family->family['address'] .
                ($family->family['city'] ? ', ' . $family->family['city'] : '') .
                ' ' . $family->family['state'] . ' ' . $family->family['zip'];
            $child->child['full_name'] = $child->child['last'] . ($child->child['first'] ? ', ' . $child->child['first'] : '');
            $familyInfo = array(
                'parentName'    => $family->family['full_name'],
                'relationship'  => $setting->getValue($family->family['relationship_1_id']),
                'full_address'  => $family->family['full_address'],
                'county'        => $family->family['county'],
                'primary_phone' => $family->family['primary_phone'],
                'email'         => $family->family['email'],
                'childName'     => $child->child['full_name'],
                'gender'        => $child->child['gender']
            );

            $pdf->FamilyInformation($familyInfo);

            $providerInfo = array(
                'provider'    => $family->family['full_name'],
                'employer'  => $setting->getValue($family->family['relationship_1_id'])
            );
            $familyProvider = new FamilyProvider($family->family['id']);
            $providers = $familyProvider->getList();
            $providerInfo = array(
                'provider' => null,
                'employer' => null
            );
            //print_r($providers);
            if (is_array($providers) && count($providers)) {
                $provider = array_shift($providers);
                $providere = explode('-', $provider['name']);
                $provider['full_name'] = $providere[0];
                $providerInfo = array(
                    'provider' => $provider['full_name'],
                    'employer' => (isset($providere['1']) ? $providere['1'] : '')
                );
            }

            $pdf->ProviderInformation($providerInfo);

            $screeningInfo = array();
            $ds = new ChildDevelopmentalScreenings($child_id);
            $developmentalScreenings = $ds->getList();
            if (is_array($developmentalScreenings)) {
                $settingOb = new Setting(); //201016
                foreach ($developmentalScreenings as $screening) {
                    $screeningInfo[] = array(
                        'Screening Type'     => $screening['type'], //261016 revert back
                        'Month'              => $screening['asq_month'],
                        'Date Completed'     => ($screening['date_sent_formatted'] != '00/00/00' ? $screening['date_sent_formatted'] : ''),
                        'Faxed to Doctor'    => ($screening['date_sent_provider_formatted'] != '00/00/00' ? $screening['date_sent_provider_formatted'] : ''),
                        'Score'              => $screening['score']
                    );
                }
            }

            $screeningColumns = array(
                'Screening Type'     => 'Screening Type',
                'Month'              => 'Month',
                'Date Completed'     => 'Date Completed',
                'Faxed to Doctor'    => 'Faxed to Doctor',
                'Score'              => 'Score'
            );

            $pdf->ScreeningInformation($screeningColumns, $screeningInfo);

            $referralInfo = array();
            $childReferrals = new ChildReferrals($child_id);
            $referrals = $childReferrals->getList();

            if (is_array($referrals)) {
                foreach ($referrals as $referral) {
                    $notes = trim($referral['notes']);
                    $notes = str_replace("\r\n", "\n", $notes);
                    $notes = str_replace("\n", " ", $notes);
                    $referred_to_id = $referral['referred_to_id'];
                    if(!empty($referral['referred_to_id'])) {
                        //get referral name from organization
                        $sql = 'Select * from organization_sites os 
                            JOIN organizations o ON o.id=os.organization_id
                            Where os.id="'.$referral['referred_to_id'].'"';
                        $rs = mysql_query($sql);
                        $row = mysql_fetch_array($rs, MYSQL_ASSOC);
                        if(!empty($row)) {
                            $referred_to_id = !empty($row['organization_site_id'])
                                ? $row['organization_site_id'] : $row['organization_name_id'];
                        }
                    }
                    $referralInfo[] = array(
                        'Referral Name' => $setting->getValue($referred_to_id),
                        'Service'       => $setting->getValue($referral['service_id']),
                        'Ref. Date'     => ($referral['referral_date_formatted'] != '00/00/00' ? $referral['referral_date_formatted'] : ''),
                        'Outcome'       => $setting->getSettingById($referral['outcomes'])
                    );
                }
            }

            $referralColumns = array(
                'Referral Name' => 'Referral Name',
                'Service'       => 'Service',
                'Ref. Date'     => 'Ref. Date',
                'Outcome'       => 'Outcome'
            );

            $prop = array(
                'HeaderColor'=>array(205, 205, 205),
                'color1'=>array(255,255,255),
                'color2'=>array(255, 255, 255),
                'width' => '180',
                'padding'=>2,
                'align' => 'C'
            );
            //echo "<pre>";print_r($referralInfo);die;
            $pdf->ResourceInformation($referralColumns, $referralInfo, $prop);
            $pdf->Output();

        } else if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'fax') {
            $faxProvider = array(
                'first_name' => '',
                'last_name' => ''
            );
            if (isset($_REQUEST['provider_id']) && is_numeric($_REQUEST['provider_id'])) {
                $familyProvider = new FamilyProvider($family->family['id'], $_REQUEST['provider_id']);
                $faxProvider = $familyProvider->getRecord();
            } else {
                $familyProvider = new FamilyProvider($family->family['id']);
                $providerList = $familyProvider->getList();
                foreach ($providerList as $provider) {
                    if ($provider['fax_permission']) {
                        $faxProvider = $provider;
                        break;
                    }
                }
            }

            $faxProvider['full_name'] = $faxProvider['last'] . ($faxProvider['first'] ? ', ' . $faxProvider['first'] : '');
            $providerInfo = array(
                'name' => $faxProvider['full_name'],
                'fax' => (isset($faxProvider['fax']) ? $faxProvider['fax'] : '')
            );

            $user = new User();
            $user->setByHmgWorker($family->family['hmg_worker']);
            $careCoordinater = $user->user['first_name'].' '.$user->user['last_name'];

            $pdf = new $GLOBALS['faxCoverSheetClass']('P', 'pt');
            $pdf->setLogoImage($GLOBALS['faxLogo']);
            
            if(isset($GLOBALS['faxSheetFooterText']) && !empty($GLOBALS['faxSheetFooterText'])) {
                $pdf->setFooterText($GLOBALS['faxSheetFooterText']);
            } else {
                $pdf->setFooterText('148 North 100 West | P.O. Box 135 | Provo, UT 84604 | 801.691.5322 | 801.374.2591 | www.helpmegrowutah.org');
            }
            if(isset($GLOBALS['faxSheetFooterText2']) && !empty($GLOBALS['faxSheetFooterText2'])) {
                $pdf->SetFooterTextNew($GLOBALS['faxSheetFooterText2']);
            }
            
            $pdf->AliasNbPages();
            $pdf->SetTextColor(70, 70, 70);
            $pdf->AddPage();
            $pdf->PageBody($providerInfo, $careCoordinater);
            $pdf->Output();

        } else if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'cert') {
            $parentName = ucwords(strtolower($family->family['first_name_1'] . ($family->family['last_name_1'] ? ' ' . $family->family['last_name_1'] : '')));
            $childName = ucwords(strtolower($child->child['first'] . ($child->child['last'] ? ' ' . $child->child['last'] : '')));

            $pdf = new $GLOBALS['PdfCertificate']('L', 'pt');
			
			
			
            $pdf->setLineColors($GLOBALS['certificate']);
            $pdf->setLogoImage($GLOBALS['certificateLogo']);
            $pdf->AliasNbPages();
            $pdf->AddPage();
            $pdf->PageBody($parentName, $childName);
            $pdf->Output();

        } else if (isset($family)) {
            $this->displayFamilyChild($family, $child_id, $pos);

        } else {
            header("Location: index.php?action=families");
        }
    }

    public function displayLetter($family, $child_id, $letter_id)
    {
        $l = new Letter();
        $l->setById($letter_id);
        $letterContent = $l->letter;
        $child = $family->family['children'][$child_id];
        $letterContent = str_replace('{{firstName}}', $family->family['first_name_1'], $letterContent['body']);
        $letterContent = str_replace('{{lastName}}', $family->family['last_name_1'], $letterContent);
        $letterContent = str_replace('{{childFirstName}}', $child['first'], $letterContent);
        $letterContent = str_replace('{{childLastName}}', $child['last'], $letterContent);
        $letterContent = str_replace('{{childBirthDate}}', $child['birth_date_formatted'], $letterContent);
        $letterContent = str_replace('{{date}}', date('F d, Y'), $letterContent);
        $backgroundImage = '';
        if ($GLOBALS['letterBackground']) {
            $backgroundImage = '<img class="source-image" src="/images/' . $GLOBALS['letterBackground'] . '" />';
        }

        $html = file_get_contents(__DIR__ . '/../' . VIEW_PATH . '/child-letter-alabama.phtml'); //LETTER_TEMPLATE
        $html = str_replace('<BACKGROUND_IMAGE>', $backgroundImage, $html);
        $html = str_replace('<LETTER_CONTENT>', utf8_encode($letterContent), $html);
        $html = str_replace('<USER_FIRST_NAME>', $_SESSION['user']['first_name'], $html);
        $html = str_replace('<USER_LAST_NAME>', $_SESSION['user']['last_name'], $html);
        $html = str_replace('<USER_EMAIL>', $_SESSION['user']['email'], $html);
        $html = str_replace('<FOOTER_TEXT>', str_replace(' | ', '<br>', $GLOBALS['footerText']), $html);
        echo $html;
    }

    public function displayFamilyChild($family, $child_id, $pos, $message = null)
    {
        $setting = new Setting();

        $data = $family->getAll();

        $referredTo         = new Setting('referred_to');
        $issues             = new Setting('issues');
        $service            = new Setting('service');
        $followUpTask       = new Setting('follow_up_task');
        $gaps               = new Setting('gaps');
        $barriers           = new Setting('barriers');
        $referral_outcome   = new Setting('referral_outcomes');

        $screening_type        = new Setting('screening_type');
        $screening_interval    = new Setting('screening_interval', true);
        $score                 = new Setting('score');
        
        $user = new User();
        $data['hmg_worker'] =  $user->getById($data['hmg_worker']);

        $notesObj = new ChildNotes($child_id);
        $notes = $notesObj->getList();



        $familyReferral = new FamilyReferrals($family->family['id']);

        $childReferrals = new ChildReferrals($child_id);
        $referrals = $childReferrals->getList();
       
        $familyFollowUp = new FamilyFollowUps($family->family['id']);

        $childFollowUps = new ChildFollowUps($child_id);
        $followUps = $childFollowUps->getList();
       


        $ds = new ChildDevelopmentalScreenings($child_id);
        $developmentalScreenings = $ds->getList();
        if (is_array($developmentalScreenings)) {
            foreach ($developmentalScreenings as $key => $value) {
                $above = array();
                $monitoring = array();
                $below = array();

                if (isset($value['communication']) && $value['communication'] == 'White') {
                    $above[] = 'Communication';
                } else if (isset($value['communication']) && $value['communication'] == 'Grey') {
                    $monitoring[] = 'Communication';
                } else if (isset($value['communication']) && $value['communication'] == 'Black') {
                    $below[] = 'Communication';
                }

                if (isset($value['gross_motor']) && $value['gross_motor'] == 'White') {
                    $above[] = 'Gross Motor';
                } else if (isset($value['gross_motor']) && $value['gross_motor'] == 'Grey') {
                    $monitoring[] = 'Gross Motor';
                } else if (isset($value['gross_motor']) && $value['gross_motor'] == 'Black') {
                    $below[] = 'Gross Motor';
                }

                if (isset($value['fine_motor']) && $value['fine_motor'] == 'White') {
                    $above[] = 'Fine Motor';
                } else if (isset($value['fine_motor']) && $value['fine_motor'] == 'Grey') {
                    $monitoring[] = 'Fine Motor';
                } else if (isset($value['fine_motor']) && $value['fine_motor'] == 'Black') {
                    $below[] = 'Fine Motor';
                }

                if (isset($value['problem_solving']) && $value['problem_solving'] == 'White') {
                    $above[] = 'Problem Solving';
                } else if (isset($value['problem_solving']) && $value['problem_solving'] == 'Grey') {
                    $monitoring[] = 'Problem Solving';
                } else if (isset($value['problem_solving']) && $value['problem_solving'] == 'Black') {
                    $below[] = 'Problem Solving';
                }

                if (isset($value['personal_social']) && $value['personal_social'] == 'White') {
                    $above[] = 'Personal Social';
                } else if (isset($value['personal_social']) && $value['personal_social'] == 'Grey') {
                    $monitoring[] = 'Personal Social';
                } else if (isset($value['personal_social']) && $value['personal_social'] == 'Black') {
                    $below[] = 'Personal Social';
                }
                $developmentalScreenings[$key]['above'] = implode(', ', $above);
                $developmentalScreenings[$key]['monitoring'] = implode(', ', $monitoring);
                $developmentalScreenings[$key]['below'] = implode(', ', $below);

                $attachmentList = array();
                $attachments = new ScreeningAttachments($value['id']);
                $attachmentList = $attachments->getList();
                $developmentalScreenings[$key]['attachments'] = $attachmentList;
            }
        }


        $childPriorResources = new ChildPriorResources($child_id);
        $resources = $childPriorResources->getList();

        // Get full list of child service types 171016
        $childServices  = new Setting('child_services');
        
        // Get the primary providers
        $familyProvider = new FamilyProvider($family->family['id']);
        $providers = $familyProvider->getList();

        // Filter out providers that have permision to fax
        $faxProviders = array();
        foreach ($providers as $provider) {
            if (isset($provider['fax_permission']) && $provider['fax_permission']) {
                $faxProviders[] = $provider;
            }
        }
        //print_r( $faxProviders );

        $field = '';
        $sort = '';
        if (isset($_SESSION['family-sorts'])) {
            $field = key($_SESSION['family-sorts']);
            $sort = $_SESSION['family-sorts'][$field];
        }

        include(VIEW_PATH . '/adminnav.phtml');

        $referralType = 'Child';
        $followUpType = 'Child';
        $setting = new Setting(); //191016
        ob_start();
        include(VIEW_PATH . '/family-referrals.phtml');
        $referral_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/family-follow-ups.phtml');
        $followup_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/child-resources.phtml');
        $resourceHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        $settingOb = new Setting(); //201016
        include(VIEW_PATH . '/child-developmental-screenings.phtml');
        $developmentalScreeningsHtml = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/child.phtml');
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
}
