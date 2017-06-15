<?php
namespace Hmg\Controllers;

use Hmg\Models\Organizations;
use Hmg\Models\Organization;
use Hmg\Models\OrganizationReferrals;
use Hmg\Models\OrganizationFollowUps;
use Hmg\Models\ContactNotes;
use Hmg\Models\ContactReferrals;
use Hmg\Models\ContactFollowUps;
use Hmg\Models\Event;

use Hmg\Models\City;
use Hmg\Models\Child;
use Hmg\Models\Contact;
use Hmg\Models\Setting;

use Hmg\Models\Letter;
use Hmg\Models\RegionCounties;
use Hmg\Models\User;
use Hmg\Helpers\SessionHelper as FilterHelper;

class ContactController
{
    public function __construct()
    {
        $city     = new City();

        $organizations  = new Organizations();
        $organization   = new Organization();
        $contact        = new Contact();

        $pos = (isset($_REQUEST['pos']) && !empty($_REQUEST['pos']) ? $_REQUEST['pos'] : 0);

        $contact_id = (isset($_REQUEST['contact_id']) ? $_REQUEST['contact_id'] : '');

        if (is_numeric($pos)) {
            $organizations->set('_search', null);
            if (isset($_SESSION['organizations']['filters'])) {
                $childFilters = $_SESSION['organizations']['filters'];
                if ($_SESSION['list-type'] != 'organizations-list' && isset($childFilters['quick'])) {
                    unset($childFilters['quick']);
                }
                $organizations->set('_filters', $childFilters);
            }
            $organizations->set('_start', $pos);
            if (isset($_SESSION['organization-sorts'])) {
                $organizations->set('_sorts', $_SESSION['organization-sorts']);
            }
            if ($contact_id) {
                $contact = new Contact();
                $contact->setById($contact_id);
				$organization->setById($contact->contact['organization_sites_id']);
		    } else {
                $organizations->set('_limit', 1);
                $data = $organizations->getList();
                $organization->setById($data[0]['id']);
            }
        } else if (is_numeric($contact_id)) {
            $contact = new Contact();
            $contact->setById($contact_id);
           // $family->setById($contact->contact["parent_id"]);
            // If we don't have a family record position then try and look it up
            if (empty($pos) && $contact_id) 
			{
                $pos = $organizations->getOrganizationPositionById($_REQUEST['contact_id']);
        		$clearFilters = 1;
		    }
        }
        //save contact Notes
        if (isset($_REQUEST['save']) && isset($_REQUEST['json'])) {
            $data = json_decode($_REQUEST['json']);
            $newNote = (array) $data;

            $contact = new Contact();
            $contact->saveNotes($newNote);
            header('Content-Type: aplication/json');
            echo json_encode(array('status' => true, 'msg' => 'updated'));die;
        }
        //delete contact Notes
        if (isset($_REQUEST['delete_note']) && isset($_REQUEST['json'])) {
            $data = json_decode($_REQUEST['json']);
            $newNote = (array) $data;

            $contact = new Contact();
            $contact->deleteNotes($newNote);
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
            if (!in_array($organization->organization['county'], $countyNames)) {
                header('Location: /index.php?action=families');
            }
        }

        if ($contact_id && isset($organization) && isset($_REQUEST['letter_id'])) {
            $this->displayLetter($organization, $contact_id, $_REQUEST['letter_id']);
        } else if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'pdf') {
            $setting = new Setting();
            $user = new User();
            
          	$user_data = $user->setByHmgWorker($organization->organization['hmg_worker']);
			
            $pdf = new \PdfContactInfo('P', 'pt');
            $pdf->setLogoImage($GLOBALS['pdfLogo']);
            $pdf->setCreatedBy($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']);
            //$pdf->setHmgWorker($user->user['first_name']);
            $pdf->setHmgWorker($_SESSION['user']['hmg_worker']);
            $pdf->setFooterText($GLOBALS['footerText']);
            $pdf->AliasNbPages();
            $pdf->SetTextColor(70, 70, 70);
            $pdf->SetAutoPageBreak(1, 70);
            $pdf->AddPage();
			
			$setting = new Setting();	

            $organization->setById($contact->contact['organization_sites_id']);
		    $organization_data = $organization->getAll();
		    //$organization->organization['organization_name_id'];
		    //echo "<pre>";print_r($organization_data);die;
            $org_name = $setting->getSettingById($organization->organization['organization_name_id']);
			
            $organization->organization['full_address'] = $organization->organization['address'] .
                ($organization->organization['city'] ? ', ' . $organization->organization['city'] : '') .
                ' ' . $organization->organization['state'] . ' ' . $organization->organization['zip'];
           // $child->child['full_name'] = $child->child['last'] . ($child->child['first'] ? ', ' . $child->child['first'] : '');
                $service = '';
                $services = explode(',',$organization->organization['service_area']);
                foreach( $services as $ser){
                    $service .= $setting->getValue($ser).',';
                }

            $familyInfo = array(
                'organizationName'    => $org_name ,
                'site'    => $organization->organization['site'] ,
				'last' =>  $contact->contact['last'],
				'first' => $contact->contact['first'],
                'full_address'  => $organization->organization['address'],
                'service_area'  => $service,
                'primary_phone' => $contact->contact['cell_phone'],
                'email'         => $contact->contact['email'],
				'gender'        => $contact->contact['gender']
                
            );

            $pdf->FamilyInformation($familyInfo);

            $providerInfo = array();

            $pdf->ProviderInformation($providerInfo);

          	$screeningInfo = array();
            //$developmentalScreenings = $ds->getList();
            if (is_array($organization_data)) {
                $settingOb = new Setting(); //201016
				if(empty($organization_data['events']))
                    $organization_data['events'] = array();
                foreach ($organization_data['events'] as $screening) 
				{
					$event_type = $settingOb ->getSettingById($screening['event_type_id']);
					
                    $screeningInfo[] = array(
                        'Screening Type'     => $screening['event_name'], //261016 revert back
                        'Month'              => $event_type,
                        'Date Completed'     => ($screening['event_date'] != '00/00/00' ? $screening['event_date'] : ''),
                        'Faxed to Doctor'    => $screening['event_venue'],
                        'Score'              => $screening['no_of_people'],
                        'Notes' => $screening['event_notes']
                    );
                }
            }

            $screeningColumns    =  array(
                'Event Name'     => 'Event Name',
                'Event Type'     => 'Event Type',
                'Event Date'     => 'Event Date',
                'Venue'          => 'Venue',
                'No Of People'   => 'No Of People',
                'Notes'          => 'Notes',
            );

            //$pdf->EventInformation($screeningColumns, $screeningInfo);

            $referralInfo = array();
            $contactReferrals = new ContactReferrals($contact_id);
            $referrals = $contactReferrals->getList();
			
		   if (is_array($referrals)) {
                foreach ($referrals as $referral) {
                    $service = '';
                    $services = explode(',',$referral['service_id']);
                    foreach( $services as $ser){
                        $service .= $setting->getValue($ser).',';
                    }
                    if( $referral['referred_to_type']  == 'info'){
                        $rn = $setting->getValue($referral['referred_to_id']);
                    }
                    else{
                        $siteSeprate = '';
                        if($referral['site_name']){
                        $siteSeprate = ': '.$referral['site_name'];
                        }
                        $rn = $referral['organization_name'].$siteSeprate;
                    }
                    $referralInfo[] = array(
                        'Referral Name' => $rn,
                        'Service'       => substr($service,0,-1),
                        'Ref. Date'     => ($referral['referral_date_formatted'] != '00/00/00' ? $referral['referral_date_formatted'] : ''),
                        'Outcome'       => $setting->getSettingById($referral['outcomes']), //191016
                        'Notes'         => $referral['notes']
                    );
                }
            }

            $referralColumns = array(
                'Referral Name' => 'Referral Name',
                'Service'       => 'Service',
                'Ref. Date'     => 'Ref. Date',
                'Outcome'       => 'Outcome',
                'Notes'         => 'Notes'
            );

            $prop = array(
                'HeaderColor'=>array(205, 205, 205),
                'color1'=>array(255,255,255),
                'color2'=>array(255, 255, 255),
                'width' => '180',
                'padding'=>2,
                'align' => 'C'
            );
            $pdf->ResourceInformation($referralColumns, $referralInfo, $prop);
            $pdf->Output();

        } else if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'cert') 
		
		  {
            //$parentName = ucwords(strtolower($family->family['first_name_1'] . ($family->family['last_name_1'] ? ' ' . $family->family['last_name_1'] : '')));
            ///$childName = ucwords(strtolower($child->child['first'] . ($child->child['last'] ? ' ' . $child->child['last'] : '')));
           $setting = new Setting();
		   $organization->setById($contact->contact['organization_sites_id']);
		   $org_name = $setting->getSettingById($organization->organization['organization_name_id']);
		   
		   
            $pdf= new $GLOBALS['PdfOrgCertificate']('L', 'pt');
            $pdf->setLineColors($GLOBALS['PdfOrgCertificate']);
            $pdf->setLogoImage($GLOBALS['PdfOrgCertificateLogo']);
			
            $pdf->AliasNbPages();
            $pdf->AddPage();
            $pdf->PageBody($org_name);
            $pdf->Output();

        } else if (isset($organization)) {
            $this->displayOrganizationContact($organization, $contact_id, $pos);

        } else {
            header("Location: index.php?action=families");
        }
    }
/*
    public function displayLetter($family, $contact_id, $letter_id)
    {
        $l = new Letter();
        $l->setById($letter_id);
        $letterContent = $l->letter;
        $child = $family->family['children'][$contact_id];
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
*/
    public function displayOrganizationContact($organization, $contact_id, $pos, $message = null)
    {
        //$contact_id = 0;
		//print_r($organization);
        $setting = new Setting();
	
        $data    = $organization->getAll();

        $referredTo         = new Setting('referred_to');
        $issues             = new Setting('issues');
        $service            = new Setting('service');
        $followUpTask       = new Setting('outreach_follow_up_task');
        $gaps               = new Setting('gaps');
        $barriers           = new Setting('barriers');
        $referral_outcome   = new Setting('referral_outcomes');

        $screening_type     = new Setting('screening_type');
        $screening_interval = new Setting('screening_interval', true);
        $score              = new Setting('score');

        $notesObj = new ContactNotes($contact_id);
        $notes = $notesObj->getList();

        $familyReferral = array();

        $contactReferrals = new ContactReferrals($contact_id);
        $referrals = $contactReferrals->getList();

        $familyFollowUp = array();

        $contactFollowUps = new ContactFollowUps($contact_id);
        $followUps = $contactFollowUps->getList();
        
        // Get full list of child service types 171016
        $childServices  = new Setting('child_services');
        
        // Get the primary providers
        $providers = array();

        // Filter out providers that have permision to fax
        $faxProviders = array();
        foreach ($providers as $provider) {
            if ($provider['fax_permission']) {
                $faxProviders[] = $provider;
            }
        }
        
        $keys = $in = $other = array();
		if(!empty($data['contacts'])){
         foreach ($data['contacts'] as $cnt ) { 
             if ($setting->getValue($cnt['type_of_contact_id']) == 'Key') 
                $keys[] = $cnt;
            elseif($setting->getValue($cnt['type_of_contact_id']) == 'Inactive')
                $in[] = $cnt;
            else
                $other[] = $cnt ;
        }

         usort($keys, function ($a, $b) {
             return strcmp($a['first'].$a['last'], $b['first'].$b['last']);
         });

                  usort($other, function ($a, $b) {
             return strcmp($a['first'].$a['last'], $b['first'].$b['last']);
         });
         usort($in, function ($a, $b) {
             return strcmp($a['first'].$a['last'], $b['first'].$b['last']);
         });

         $tail = array_merge($other , $in);
         $data['contacts'] = array_merge($keys, $tail);
        }

        $field = '';
        $sort = '';
        if (isset($_SESSION['family-sorts'])) {
            $field = key($_SESSION['family-sorts']);
            $sort = $_SESSION['family-sorts'][$field];
        }
//        echo $pos ; die();
        include(VIEW_PATH . '/adminnav.phtml');

        $referralType = 'Provider';//Change "Contact to Provider" Date: 15/02/2017
        $followUpType = 'Provider';//Change "Contact to Provider" Date: 15/02/2017
        $setting = new Setting(); //191016
        ob_start();
        include(VIEW_PATH . '/contact-referrals.phtml');
        $referral_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/organization-follow-ups.phtml');
        $followup_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/contact.phtml');
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
