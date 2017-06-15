<?php

namespace Hmg\Controllers;

use Hmg\Models\Organizations;
use Hmg\Models\Organization;
use Hmg\Models\OrganizationNotes;
use Hmg\Models\OrganizationNote;
use Hmg\Models\OrganizationFollowUps;
use Hmg\Models\OrganizationFollowUp;
use Hmg\Models\Setting;
use Hmg\Models\City;
use Hmg\Models\OrganizationStartEnd;
use Hmg\Models\OrganizationAttachment;
use Hmg\Models\OrganizationAttachments;
use Hmg\Models\OrganizationStartEnds;
use Hmg\Models\OrganizationSite;
use Hmg\Models\Contact;
use Hmg\Models\User;


use Hmg\Models\Family;
use Hmg\Models\FamilyReferrals;
use Hmg\Models\FamilyReferral;
use Hmg\Models\FamilyProvider;
use Hmg\Models\StartEnds;
use Hmg\Models\Attachments;
use Hmg\Models\Attachment;


use Hmg\Models\RegionCounties;
use Hmg\Helpers\SessionHelper as FilterHelper;

class OrganizationController
{
    public function __construct()
    {
        $organizations = new Organizations();
        $organization = new Organization();
        $family = new Family();
        $city = new City();
        
        if (isset($_REQUEST['clearFilters'])) {
            unset($_SESSION['filters'], $_SESSION['totalOrganizations'], $_SESSION['organization-sorts'], $_SESSION['organizations']);
        }

        $pos = (isset($_REQUEST['pos']) ? $_REQUEST['pos'] : '');
        //$pos = empty($pos) ? 1 : $pos;

        //ajax call check permissions
        if (isset($_REQUEST['check_permissions'])) {
            $data = json_decode($_REQUEST['json']);
            $data = (array) $data;
            $check = array(
                'cid' => trim($data['cid']),
                'uid' => trim($data['uid']),
            );
            header('Content-Type: aplication/json');
            if($organization->checkCountyRegion($check)) {
                echo json_encode(array('status' => true, 'msg' => 'has permissions'));
            } else {
                echo json_encode(array('status' => false, 'msg' => 'no permissions'));
            }
            die;
        }

        if (is_numeric($pos)) {
            $organizations->set('_search', null);
            if (isset($_SESSION['organizations']['filters'])) {
                $organizations->set('_filters', $_SESSION['organizations']['filters']);
            }
            $organizations->set('_start', $_REQUEST['pos']);
            if (isset($_SESSION['organization-sorts'])) {
                $organizations->set('_sorts', $_SESSION['organization-sorts']);
            }
            $organizations->set('_limit', 1);
            $data = $organizations->getList();
            // If position changes and the lookup doesn't match the id on viewing a organization that was in the list
            // then use the id of that organization for displaying the view/edit page.
            // However, after then move and go to the next record they are going to be gone from the list
            if ((!isset($_REQUEST['next'])
                    && !isset($_REQUEST['prev']))
                    && isset($_REQUEST['id'])
                    && $data[0]['id'] != $_REQUEST['id']
            ) {
                #echo 'Position ID: ' . $data[0]['id'] . ' Actual ID: ' . $_REQUEST['id'];
                $data[0]['id'] = $_REQUEST['id'];
            }

            $organization->setById($data[0]['organization_sites_id']);

        } else if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            $organization->setById($_REQUEST['id']);

            // If we don't have a organization record position then try and look it up
            if (!$pos) {
                $pos = $organizations->getOrganizationPositionById($_REQUEST['id']);
                $clearFilters = 1;
            }
        } else if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'fax') {
            $setting = new Setting();
            $contact = new Contact();
            $contact->setById($_REQUEST['provider_id']);
            $user = new User();
            
            $user_data = $user->setByHmgWorker($organization->organization['hmg_worker']);
            //print_r($_SESSION);
            $pdf = new \PdfAgencyFaxSheet('P', 'pt');
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

            $organization->setById($_REQUEST['org_id']);
            $organization_data = $organization->getAll();
            
            $org_name = $setting->getSettingById($organization->organization['organization_name_id']);    

            $pdf->FamilyInformation($organization->organization,$contact->contact);
           
            $pdf->Output();


        }

        // Check organization county.
        // If this is a region user and county doesn't match allowed categories
        // Then redirect to organization list.
        if (!empty($_SESSION['user']['region'])) {
            if (!$organization->isRegionOrganization()
                && isset($_REQUEST['id'])
                && $_REQUEST['id'] !== 'new'
            ) {
                header('Location: /index.php?action=organizations');
            }
        }
        $org_sites_id = 0;
        //echo "<pre>";print_r($organization);//die;
        if(!empty($organization) && isset($organization->organization_sites_id)) {
           $org_sites_id = $organization->organization_sites_id;
        }
        if (isset($_REQUEST['data']) && isset($_REQUEST['save']) || isset($_REQUEST['delete']) || isset($_REQUEST['deleteChild'])) {
            $data = $_REQUEST['data'];
            //echo "<pre>";print_r($data);print_r($_REQUEST['children']);die;
            if (isset($_REQUEST['children']) && $_REQUEST['children']) {
                $data['children'] = $_REQUEST['children'];
       
                unset($_REQUEST['children']);
            }
            //$data['organization_sites_id'] = $org_sites_id;
            $organization->setOrganization($data);
        }

        //echo "<pre>";print_r($organization);die;

        if (!empty($_REQUEST['save'])) {
            //echo "<pre>";print_r($_REQUEST);die;
            // Show a form for adding
            if (!$organization->organization['organization_name_id']
                 || !$organization->organization['organization_type_id']
               //  || !$organization->organization['relationship_1_id']
                 || !$organization->organization['primary_phone']
                 || !$organization->organization['county']
            ) {
                $organization->message = 'Missing Required Field! <br />Required fields are Organization Name, Type of Organization, Primary Phone and County!';
                $this->displayOrganizationForm($organization, $_REQUEST['pos'], $city, $organization->message, 0, null);
            } else {
                if (!empty($organization->organization['date_permission_granted'])) {
                    $organization->organization['date_permission_granted'] = date('Y-m-d', strtotime($organization->organization['date_permission_granted']));
                }
                if (empty($pos) || !$pos) {
                    
                    
                }
                //echo "<pre>";print_r($organization);die;
                $org_site_id = $organization->save();
                $pos = $organizations->getOrganizationPositionById($org_site_id);
                if ($org_site_id) {
                   
                    
                    // Add start end
                    if (isset($_REQUEST['data']['id']) && !$_REQUEST['data']['id']) {
                        $newStartEnd = array(
                            'parent_id'  => $org_site_id,
                            'start_date' => date('Y-m-d')
                        );
                        $startEnd = new OrganizationStartEnd();
                        $startEnd->setStartEnd($newStartEnd);
                        $startEnd->save();
                    }
                    //$organization->message = 'successfully';
                    // Redirect to avoid issues with clicking the back button
                    header(
                        'Location: index.php?action=organization&id='
                        . $org_site_id
                    );

                } else {
                    // Redirect to avoid issues with clicking the back button
                    header(
                        'Location: index.php?action=organization&id='
                        . $org_site_id
                    );
                    /*$organization->message = 'Failed to update or there were no changes to the record.';
                    $this->displayOrganizationForm($organization, $_REQUEST['pos'], $city, $organization->message, 0, null);
                    */
                }
            }

        } else if (isset($_REQUEST['delete']) && $organization->organization["organization_sites_id"]) {
            $deleteContacts = $organization->deleteContacts();
            
            /** delete entry from organization sites table first **/
            $siteModel = new OrganizationSite();
            $siteModel->setById($organization->organization["organization_sites_id"]);
            $siteModel->delete();
            /** Make sure Organization is not attached to other site **/
            $siteModel = new OrganizationSite();
            $check = $siteModel->setByOrgId($organization->organization["id"]);
            if(empty($check) || !isset($check['id'])) {
                $deleted = $organization->delete();
            }

            $notesModel = new OrganizationNotes($organization->organization["organization_sites_id"]);
            $notes = $notesModel->getList();
            $noteModel = new OrganizationNote();
            if (is_array($notes)) {
                foreach ($notes as $note) {
                    $noteModel->setById($note['id']);
                    $noteModel->delete();
                }
            }
            
            $startEndsModel = new OrganizationStartEnds($organization->organization["organization_sites_id"]);
            $startEnds = $startEndsModel->getList();
            $startEndModel = new OrganizationStartEnd();
            if (is_array($startEnds)) {
                foreach ($startEnds as $startEnd) {
                    $startEndModel->setById($startEnd['id']);
                    $startEndModel->delete();
                }
            }

            $organizationFollowUpsModel = new OrganizationFollowUps($organization->organization["organization_sites_id"]);
            $organizationFollowUps = $organizationFollowUpsModel->getList();
            $organizationFollowUpModel = new OrganizationFollowUp();
            if (is_array($organizationFollowUps)) {
                foreach ($organizationFollowUps as $organizationFollowUp) {
                    $organizationFollowUpModel->setById($organizationFollowUp['id']);
                    $organizationFollowUpModel->delete();
                }
            }

            $attachmentsModel = new OrganizationAttachments($organization->organization["organization_sites_id"]);
            $attachments = $attachmentsModel->getList();
            $attachmentModel = new OrganizationAttachment();
            if (is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    $attachmentModel->setById($attachment['id']);
                    $attachmentModel->delete();
                }
            }
           
        
            if ($deleted) {
                $message = 'Organization was removed successfully!';
                $field = '';
                $sort = '';
                if (isset($_SESSION['organization-sorts'])) {
                    $field = key($_SESSION['organization-sorts']);
                    $sort = $_SESSION['organization-sorts'][$field];
                }
                header("Location: index.php?action=organizations&field=" . $field . "&sort=" . $sort . "&message=" . urlencode($message));
            } else {
                $organization->message = 'System Error: Was not able to remove Organization!';
                $this->displayOrganizationForm($organization, $_REQUEST['pos'], $city, $organization->message);
            }

        } else if (isset($_REQUEST['id']) && ($_REQUEST['id'] == 'new' || $organization->organization["organization_sites_id"]) && isset($_REQUEST['edit'])) {
            $addChild = 0;
            // Trigger for adding a contact form on new organization
            if (! sizeof($organization->organization["contacts"])) {
                $addChild = 1;
            }

            $this->displayOrganizationForm($organization, $pos, $city, '', $addChild, $_REQUEST['id']);

        } else if (isset($_REQUEST['deleteChild'])) {
            if (is_array($_REQUEST['deleteChild'])) {
                list($deleteChildId, $value) = each($_REQUEST['deleteChild']);
                $deletedChild = $organization->deleteContact($deleteChildId);
                if ($deletedChild) {
                    $organization->message = 'Contact was removed successfully!';
                }
            }
            $this->displayOrganizationForm($organization, $pos, $city, $organization->message);

        } else if (isset($organization)) {
            $this->displayOrganization($organization, $pos);

        } else {
            header("Location: index.php?action=organizations");
        }
    }

    public function displayOrganization($organization, $pos, $message = null, $saved = null)
    {
        $filterHelper = new FilterHelper();
        $filters = $filterHelper->getOrganizationFilters();

        $setting = new Setting();

        $data = $organization->getAll();

        $data['organization_name'] = $setting->getSettingById($data['organization_name_id']);
        $data['organization_type'] = !empty($data['organization_type_id']) 
                                    ? $setting->getValue($data['organization_type_id']) : 0;
        $data['region']            = $setting->getValue($data['region_id']);
        $data['partnership_level'] = $setting->getValue($data['partnership_level_id']);
        $data['mode_of_contact']   = $setting->getValue($data['mode_of_contact_id']);
        $data['status']            = $setting->getValue($data['status']);

        $referredTo         = new Setting('referred_to');
        $issues             = new Setting('issues');
        $service            = new Setting('service');
        $followUpTask       = new Setting('outreach_follow_up_task');
        $gaps               = new Setting('gaps');
        $language           = new Setting('language');
        $barriers           = new Setting('barriers');
        $referral_outcome   = new Setting('referral_outcomes');
        $reason_file_closed = new Setting('file_closed_reason');
        
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
 
        $notesObj = new OrganizationNotes($data['organization_sites_id']);
        $notes    = $notesObj->getList();


        $organizationFollowUp = new OrganizationFollowUps($data['organization_sites_id']);
        $followUps = $organizationFollowUp->getList();

        $familyStartEnds = new OrganizationStartEnds($data['id']);
        $startEnds = $familyStartEnds->getList();

        $attachments = new OrganizationAttachments($data['organization_sites_id']);
        $attachmentList = $attachments->getList();

        $field = '';
        $sort  = '';
        if (isset($_SESSION['organization-sorts'])) {
            $field = key($_SESSION['organization-sorts']);
            if ($field) {
                $sort = $_SESSION['organization-sorts'][$field];
            }
        }

        include(VIEW_PATH . '/adminnav.phtml');

        $referralType = 'Organization';
        $followUpType = 'Organization';
        $setting = new Setting(); //191016
     		
		ob_start();
        include(VIEW_PATH . '/organization-follow-ups.phtml');
        $followup_content = ob_get_contents();
        ob_end_clean();
		
        ob_start();
        include(VIEW_PATH . '/organization-start-ends.phtml');
        $start_end_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/organization-attachments.phtml');
        $attachment_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/event.phtml');
        $events = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/organization.phtml');
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

    public function displayOrganizationForm($organization, $pos, $city, $message = null, $addChild = 0, $organizationId = null)
    {
        
        $cities                 = new Setting('city');
        $status                 = new Setting('organization_status');
        $county                 = new Setting('county');
        $region                 = new Setting('region');
        $organization_name      = new Setting('referred_to');
        $type                   = new Setting('organization_type');         
        $county                 = new Setting('county');
        $partnership            = new Setting('partnership_level');
        $site                   = new Setting('referred_to');
        $mode_of_contact        = new Setting('mode_of_contact');
        $type_of_contact        = new Setting('type_of_contact');
        $resource_database      = new Setting('resource_database');
        $service_terms          = new Setting('referred_to_service');
		$zip_code               = new Setting();
		
       $setting = new Setting();
        $field = '';
        $sort = '';
        if (isset($_SESSION['organization-sorts'])) {
            $field = key($_SESSION['organization-sorts']);
            $sort = $_SESSION['organization-sorts'][$field];
        }

        $data = $organization->getAll();
        $data['organization_name'] = ''; //set blank caption for add new form
        // echo "<pre>";print_r($data);die;
        if (isset($data['contacts']) && is_array($data['contacts'])) {
            foreach ($data['contacts'] as $id => $contact) {
                if (!isset($contact['birth_date_formatted']) && !empty($contact['birth_date'])) {
                    $data['contacts'][$id]['birth_date_formatted'] = $contact['birth_date'];
                } else if (! isset($contact['birth_date_formatted'])) {
                    $data['contacts'][$id]['birth_date_formatted'] = null;
                }
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

        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/organization-form.phtml');
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
/*  function aasort (&$array, $key, $type = 'asc') { */
/*        $sorter=array(); */
/*        $ret=array(); */
/*        reset($array); */
/*        foreach ($array as $ii => $va) { */
/*            $sorter[$ii]=$va[$key]; */
/*        } */
/*        if($type == 'desc') { */
/*            arsort($sorter); */
/*        } else { */
/*            asort($sorter); */
/*        } */
/*        foreach ($sorter as $ii => $va) { */
/*            $ret[$ii]=$array[$ii]; */
/*        } */
/*        $array=$ret; */
/* } */
