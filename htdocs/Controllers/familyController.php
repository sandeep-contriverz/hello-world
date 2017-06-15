<?php

namespace Hmg\Controllers;

use Hmg\Models\Families;
use Hmg\Models\Family;
use Hmg\Models\City;
use Hmg\Models\FamilyScreenings;
use Hmg\Models\FamilyScreening;
use Hmg\Models\FamilyScreeningAttachments;
use Hmg\Models\FamilyReferrals;
use Hmg\Models\FamilyReferral;
use Hmg\Models\FamilyFollowUps;
use Hmg\Models\FamilyFollowUp;
use Hmg\Models\FamilyProvider;
use Hmg\Models\StartEnds;
use Hmg\Models\StartEnd;
use Hmg\Models\Attachments;
use Hmg\Models\Attachment;
use Hmg\Models\Setting;
use Hmg\Models\Notes;
use Hmg\Models\Note;
use Hmg\Models\RegionCounties;
use Hmg\Models\User;



class FamilyController
{
    public function __construct()
    {
        $families = new Families();
        $family = new Family();
        $city = new City();

        
        if (isset($_REQUEST['clearFilters'])) {
            unset($_SESSION['filters'], $_SESSION['totalFamilies'], $_SESSION['family-sorts'], $_SESSION['families']);
        }

        $pos = (isset($_REQUEST['pos']) ? $_REQUEST['pos'] : '');

        //ajax call check permissions
        if (isset($_REQUEST['check_permissions'])) {
            $data = json_decode($_REQUEST['json']);
            $data = (array) $data;
            $check = array(
                'cid' => trim($data['cid']),
                'uid' => trim($data['uid']),
            );
            header('Content-Type: aplication/json');
            if($family->checkCountyRegion($check)) {
                echo json_encode(array('status' => true, 'msg' => 'has permissions'));
            } else {
                echo json_encode(array('status' => false, 'msg' => 'no permissions'));
            }
            die;
        }
        
        if (is_numeric($pos)) {
            $families->set('_search', null);
            if (isset($_SESSION['families']['filters'])) {
                $families->set('_filters', $_SESSION['families']['filters']);
            }
            $families->set('_start', $_REQUEST['pos']);
            if (isset($_SESSION['family-sorts'])) {
                $families->set('_sorts', $_SESSION['family-sorts']);
            }
            $families->set('_limit', 1);
            $data = $families->getList();
            // If position changes and the lookup doesn't match the id on viewing a family that was in the list
            // then use the id of that family for displaying the view/edit page.
            // However, after then move and go to the next record they are going to be gone from the list
            if ((!isset($_REQUEST['next'])
                    && !isset($_REQUEST['prev']))
                    && isset($_REQUEST['id'])
                    && $data[0]['id'] != $_REQUEST['id']
            ) {
                #echo 'Position ID: ' . $data[0]['id'] . ' Actual ID: ' . $_REQUEST['id'];
                $data[0]['id'] = $_REQUEST['id'];
            }
            
            $family->setById($data[0]['id']);
            
        } else if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            // print_r($_REQUEST['id']);
            $family->setById($_REQUEST['id']);

            // If we don't have a family record position then try and look it up
            if (!$pos) {
                $pos = $families->getFamilyPositionById($_REQUEST['id']);
                $clearFilters = 1;
            }
        }

        // Check family county.
        // If this is a region user and county doesn't match allowed categories
        // Then redirect to family list.
        if (!empty($_SESSION['user']['region_id'])) {
            if (!$family->isRegionFamily()
                && isset($_REQUEST['id'])
                && $_REQUEST['id'] !== 'new'
            ) {
                header('Location: /index.php?action=families');
            }
        }

            
        if (isset($_REQUEST['data']) && isset($_REQUEST['save']) || isset($_REQUEST['delete']) || isset($_REQUEST['deleteChild'])) {
            $data = $_REQUEST['data'];
            if (isset($_REQUEST['children']) && $_REQUEST['children']) {
                $data['children'] = $_REQUEST['children'];
                
               // START Validation for Child Birth Date 
                 //  echo "<pre>";print_r($data['children']);die;  
                $is_birth_valid = true;       
                foreach($data['children'] as $child_data){
                    if($child_data['birth_date']){
                        if(preg_match('/^\d{1,2}\/\d{1,2}\/(?:\d{4}|\d{2})$/',$child_data['birth_date'])){
                            
                           
                            $date_arr = explode("/",$child_data['birth_date']);
                            $month = $date_arr[0];
                            $day = $date_arr[1];
                            $year = $date_arr[2];
                            $check_date = checkdate($month,$day,$year);
                            
                            if($check_date)
                            {
                                 $is_birth_valid = true;
                            }
                            else{
                                 $is_birth_valid = false;
                            }
                            
                            break;
                        }
                        else{
                            $is_birth_valid = false;
                        }
                    }
                }
                if(!$is_birth_valid) {
                    $family->message = 'Child Birth Date is not Valid !';
                    $this->displayFamilyForm($family, $_REQUEST['pos'], $city, $family->message, 0, null);
                    exit();
                }
              
                // END Validation for Child Birth Date 
                unset($_REQUEST['children']);
            }
            if (isset($data['best_times_days'])) {
                $data['best_times_days'] = implode(', ', $data['best_times_days']);
            } else {
                $data['best_times_days'] = '';
            }
			if (isset($_REQUEST['provider']) && $_REQUEST['provider']) {
                $data['provider'] = $_REQUEST['provider'];
			}
            $family->setFamily($data);
        }

        if (!empty($_REQUEST['save'])) {
            // Show a form for adding

            if (!$family->family['last_name_1']
                 || !$family->family['first_name_1']
               //  || !$family->family['relationship_1_id']
                 || !$family->family['primary_phone']
                 || !$family->family['county']
            ) {
                $family->message = 'Missing Required Field! <br />Required fields are Primary Contact (first name, last name, primary phone number, and county)!';
                $this->displayFamilyForm($family, $_REQUEST['pos'], $city, $family->message, 0, null);
            } else {
                if (!empty($family->family['date_permission_granted'])) {
                    $family->family['date_permission_granted'] = date('Y-m-d', strtotime($family->family['date_permission_granted']));
                }
                $saved = $family->save();

                if ($saved) {
                    // Add provider
                    if (!empty($_REQUEST['provider']) && is_numeric($_REQUEST['provider']['id'])) {
                        //echo "<pre>";print_r($_REQUEST['provider']['id']);die;
                        if(is_array($_REQUEST['provider']['id']) 
                            && !empty($_REQUEST['provider']['id'])) {
                            foreach($_REQUEST['provider']['id'] as $pid) {
                            $fax_permission = (isset($_REQUEST['provider']['fax_permission']) 
                                    && $_REQUEST['provider']['fax_permission'] == 'No') ? 0 : 1;
                            $send_follow_up = !empty($_REQUEST['provider']['send_follow_up'])
                                ? $_REQUEST['provider']['send_follow_up'] : 'No'; 
                            $familyProvider = new FamilyProvider(
                                $family->family["id"],
                                $pid,
                                $fax_permission,
                                1,
                                $send_follow_up
                            );
                            $familyProvider->save();
                            $familyProvider->updateKey('_fax_permission');
                            }
                        }
                    }
                    // Add family note
                    if ($_REQUEST['note']) {
                        $newNote = array(
                            'family_id' => $family->family["id"],
                            'hmg_worker' => $_SESSION['user']['id'],
                            'note' => $_REQUEST['note']
                        );
                        $note = new Note();
                        $note->setNote($newNote);
                        $saved = $note->save();
                    }
                    // Add start end
                    if (isset($_REQUEST['data']['id']) && !$_REQUEST['data']['id']) {
                        $newStartEnd = array(
                            'parent_id' => $family->family["id"],
                            'start_date' => date('Y-m-d')
                        );
                        $startEnd = new StartEnd();
                        $startEnd->setStartEnd($newStartEnd);
                        $startEnd->save();
                    }
                    // $family->message = 'Information was saved successfully.';
                    // $this->displayFamily($family, $pos);
                    // Redirect to avoid issues with clicking the back button
                    header(
                        'Location: /index.php?action=family&id='
                        . $family->family["id"]
                        . '&pos=' . $pos
                    );
                } else {
                    $family->message = 'Failed to update or there were no changes to the record.';
                    $this->displayFamilyForm($family, $_REQUEST['pos'], $city, $family->message);
                }
            }

        } else if (isset($_REQUEST['delete']) && $family->family["id"]) {
            $deletedChildren = $family->deleteChildren();
            $deleted = $family->delete();

            $notesModel = new Notes($family->family["id"]);
            $notes = $notesModel->getList();
            $noteModel = new Note();
            if (is_array($notes)) {
                foreach ($notes as $note) {
                    $noteModel->setById($note['id']);
                    $noteModel->delete();
                }
            }

            $startEndsModel = new StartEnds($family->family["id"]);
            $startEnds = $startEndsModel->getList();
            $startEndModel = new StartEnd();
            if (is_array($startEnds)) {
                foreach ($startEnds as $startEnd) {
                    $startEndModel->setById($startEnd['id']);
                    $startEndModel->delete();
                }
            }

            $familyFollowUpsModel = new FamilyFollowUps($family->family["id"]);
            $familyFollowUps = $familyFollowUpsModel->getList();
            $familyFollowUpModel = new FamilyFollowUp();
            if (is_array($familyFollowUps)) {
                foreach ($familyFollowUps as $familyFollowUp) {
                    $familyFollowUpModel->setById($familyFollowUp['id']);
                    $familyFollowUpModel->delete();
                }
            }

            $familyReferralsModel = new FamilyReferrals($family->family["id"]);
            $familyReferrals = $familyReferralsModel->getList();
            $familyReferralModel = new FamilyReferral();
            if (is_array($familyReferrals)) {
                foreach ($familyReferrals as $familyReferral) {
                    $familyReferralModel->setById($familyReferral['id']);
                    $familyReferralModel->delete();
                }
            }

            $attachmentsModel = new Attachments($family->family["id"]);
            $attachments = $attachmentsModel->getList();
            $attachmentModel = new Attachment();
            if (is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    $attachmentModel->setById($attachment['id']);
                    $attachmentModel->delete();
                }
            }

            $familyProvider = new FamilyProvider($family->family["id"]);
            $familyProvider->delete();

            if ($deleted) {
                $message = 'Family was removed successfully!';
                $field = '';
                $sort = '';
                if (isset($_SESSION['family-sorts'])) {
                    $field = key($_SESSION['family-sorts']);
                    $sort = $_SESSION['family-sorts'][$field];
                }
                header("Location: index.php?action=families&field=" . $field . "&sort=" . $sort . "&message=" . urlencode($message));
            } else {
                $family->message = 'System Error: Was not able to remove Family!';
                $this->displayFamilyForm($family, $_REQUEST['pos'], $city, $family->message);
            }

        } else if (isset($_REQUEST['id']) && ($_REQUEST['id'] == 'new' || $family->family["id"]) && isset($_REQUEST['edit'])) {
            $addChild = 0;
            // print_r($_REQUEST);
            // Trigger for adding a child form on new family
            if (! sizeof($family->family["children"])) {
                $addChild = 1;
            }

            $this->displayFamilyForm($family, $pos, $city, '', $addChild, $_REQUEST['id']);

        } else if (isset($_REQUEST['deleteChild'])) {
            if (is_array($_REQUEST['deleteChild'])) {
                list($deleteChildId, $value) = each($_REQUEST['deleteChild']);
                $deletedChild = $family->deleteChild($deleteChildId);
                if ($deletedChild) {
                    $family->message = 'Child was removed successfully!';
                }
            }
            $this->displayFamilyForm($family, $pos, $city, $family->message);

        } else if (isset($family)) {
            $this->displayFamily($family, $pos);

        } else {
            header("Location: index.php?action=families");
        }
    }

    public function displayFamily($family, $pos, $message = null, $saved = null)
    {

        $filters['first_name_1'] = (isset($_SESSION['families']['filters']['first_name_1']) ? $_SESSION['families']['filters']['first_name_1'] : '');
        $filters['last_name_1'] = (isset($_SESSION['families']['filters']['last_name_1']) ? $_SESSION['families']['filters']['last_name_1'] : '');
        $filters['primary_phone'] = (isset($_SESSION['families']['filters']['primary_phone']) ? $_SESSION['families']['filters']['primary_phone'] : '');
        $filters['child_name'] = (isset($_SESSION['families']['filters']['child_name']) ? $_SESSION['families']['filters']['child_name'] : '');
        $filters['child_first_name'] = (isset($_SESSION['families']['filters']['child_first_name']) ? $_SESSION['families']['filters']['child_first_name'] : '');
        $filters['child_last_name'] = (isset($_SESSION['families']['filters']['child_last_name']) ? $_SESSION['families']['filters']['child_last_name'] : '');
        $filters['child_id'] = (isset($_SESSION['families']['filters']['child_id']) ? $_SESSION['families']['filters']['child_id'] : '');
        $filters['family_code'] = (isset($_SESSION['families']['filters']['family_code']) ? $_SESSION['families']['filters']['family_code'] : '');
        $filters['email'] = (isset($_SESSION['families']['filters']['email']) ? $_SESSION['families']['filters']['email'] : '');
        $filters['city'] = (isset($_SESSION['families']['filters']['city']) ? $_SESSION['families']['filters']['city'] : '');
        $filters['county'] = (isset($_SESSION['families']['filters']['county']) ? $_SESSION['families']['filters']['county'] : '');
        $filters['zip'] = (isset($_SESSION['families']['filters']['zip']) ? $_SESSION['families']['filters']['zip'] : '');
        $filters['school_district'] = (isset($_SESSION['families']['filters']['school_district']) ? $_SESSION['families']['filters']['school_district'] : '');
        $filters['age_min'] = (isset($_SESSION['families']['filters']['age_min']) ? $_SESSION['families']['filters']['age_min'] : '');
        $filters['age_max'] = (isset($_SESSION['families']['filters']['age_max']) ? $_SESSION['families']['filters']['age_max'] : '');
        $filters['status'] = (isset($_SESSION['families']['filters']['status']) ? $_SESSION['families']['filters']['status'] : '');
        $filters['start_date'] = (isset($_SESSION['families']['filters']['start_date']) ? $_SESSION['families']['filters']['start_date'] : '');
        $filters['end_date'] = (isset($_SESSION['families']['filters']['end_date']) ? $_SESSION['families']['filters']['end_date'] : '');
        $filters['hmg_worker'] = (isset($_SESSION['families']['filters']['hmg_worker']) ? $_SESSION['families']['filters']['hmg_worker'] : '');
        $filters['language_id'] = (isset($_SESSION['families']['filters']['language_id']) ? $_SESSION['families']['filters']['language_id'] : '');
        $filters['race_id'] = (isset($_SESSION['families']['filters']['race_id']) ? $_SESSION['families']['filters']['race_id'] : '');
        $filters['ethnicity_id'] = (isset($_SESSION['families']['filters']['ethnicity_id']) ? $_SESSION['families']['filters']['ethnicity_id'] : '');
        $filters['family_heard_id'] = (isset($_SESSION['families']['filters']['family_heard_id']) ? $_SESSION['families']['filters']['family_heard_id'] : '');
        $filters['call_reason_id'] = (isset($_SESSION['families']['filters']['call_reason_id']) ? $_SESSION['families']['filters']['call_reason_id'] : '');
        $filters['issue'] = (isset($_SESSION['families']['filters']['issue']) ? $_SESSION['families']['filters']['issue'] : '');
        $filters['cc_level'] = (isset($_SESSION['families']['filters']['cc_level']) ? $_SESSION['families']['filters']['cc_level'] : '');

        $setting = new Setting();

        $data = $family->getAll();
        $user = new User();

        $data['hmg_worker']     = $user->getById($data['hmg_worker']);
       
        $data['relationship_1'] = $setting->getValue($data['relationship_1_id']);
        $data['relationship_2'] = $setting->getValue($data['relationship_2_id']);
        $data['language']       = $setting->getValue($data['language_id']);
        $data['ethnicity']      = $setting->getValue($data['ethnicity_id']);
        $data['race']           = $setting->getValue($data['race_id']);
        $data['call_reason']    = $setting->getValue($data['call_reason_id']);
        $data['family_heard']   = $setting->getValue($data['family_heard_id']);
		
        $data['how_heard_details'] = $setting->DisplayHowHear($data['family_heard_id'],$data['how_heard_details_id']);
        $data['who_called']        = $setting->getValue($data['who_called_id']);
		$data['point_of_entry']    = $setting->getValue($data['point_of_entry']);
        
        $referredTo         = new Setting('referred_to');
        $issues             = new Setting('issues');
        $service            = new Setting('service');
        $followUpTask       = new Setting('follow_up_task');
        $gaps               = new Setting('gaps');
        $barriers           = new Setting('barriers');
        $referral_outcome   = new Setting('referral_outcomes');
        $reason_file_closed = new Setting('file_closed_reason');
        $permisson_fax_type = new Setting('permission_fax_type');
        $perms              = $permisson_fax_type->displaySelect('permission_type','','','',false,null,false );
        $notesObj           = new Notes($data['id']);
        $notes              = $notesObj->getList();
        
        $familyProvider     = new FamilyProvider($data['id']);
        $providerList       = $familyProvider->getList2();
        
        $familyReferral     = new FamilyReferrals($data['id']);
        $referrals          = $familyReferral->getList();

        $familyFollowUp     = new FamilyFollowUps($data['id']);
        $followUps          = $familyFollowUp->getList();

        $familyStartEnds    = new  StartEnds($data['id'],'');
        $startEnds          = $familyStartEnds->getList();
        
        $screening_type        = new Setting('screening_type_family');
        $screening_interval    = new Setting('screening_interval_family', true);
        $score                 = new Setting('score');
        
        $ds = new FamilyScreenings( $data['id'] );
        $developmentalScreenings = $ds->getList();
        if (is_array($developmentalScreenings)) {
            foreach ($developmentalScreenings as $key => $value) {                

                $attachmentList = array();
                $attachments = new FamilyScreeningAttachments($value['id']);
                $attachmentList = $attachments->getList();
                $developmentalScreenings[$key]['attachments'] = $attachmentList;
            }
        }

		/* ECIDS Date*/
		/* $familyGrantedStart = new EcidsGrantedRevokeds($data['id']); */
        /* $ecids_history = $familyGrantedStart->getList(); */


        $attachments = new Attachments($data['id']);
        $attachmentList = $attachments->getList();

        $field = '';
        $sort = '';
        if (isset($_SESSION['family-sorts'])) {
            $field = key($_SESSION['family-sorts']);
            if ($field) {
                $sort = $_SESSION['family-sorts'][$field];
            }
        }
                if(empty($data['ecids_permission']))
                    $data['ecids_permission'] = 'Not Given';
                if(empty($data['sharing_info']))
                    $data['sharing_info'] = 'Not Given';


        include(VIEW_PATH . '/adminnav.phtml');

        $referralType = 'Family';
        $followUpType = 'Family';
        $setting = new Setting(); //191016
        ob_start();
        include(VIEW_PATH . '/family-referrals.phtml');
        $referral_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . "/family-start-ends.phtml");
        $ecids_history = ob_get_contents();
        ob_end_clean();

        $start_end_content = $ecids_history;
        
        /* $familyStartEnds = new  StartEnds($data['id'],'share'); */
        /* $startEnds = $familyStartEnds->getList(); */
        /* ob_start(); */
        /* include(VIEW_PATH . "/family-start-ends2.phtml"); */
        /* $sharing_history = ob_get_contents(); */
        /* ob_end_clean(); */


        ob_start();
        include(VIEW_PATH . '/family-follow-ups.phtml');
        $followup_content = ob_get_contents();
        ob_end_clean();

		/* ob_start(); */
        /* include(VIEW_PATH . '/family-ecids-granted-revoked.phtml'); */
        /* $ecidsGrantedRevokeds = ob_get_contents(); */
        /* ob_end_clean(); */

        ob_start();
        include(VIEW_PATH . '/family-attachments.phtml');
        $attachment_content = ob_get_contents();
        ob_end_clean();
        
        ob_start();
        $settingOb = new Setting(); //201016
        include(VIEW_PATH . '/family-screenings.phtml');
        $developmentalScreeningsHtml = ob_get_contents();
        ob_end_clean();
        
        ob_start();
        include(VIEW_PATH . '/family-providers.phtml');
        $provider_content = ob_get_contents();
        ob_end_clean();

        ob_start();
        include(VIEW_PATH . '/family.phtml');
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

    public function displayFamilyForm($family, $pos, $city, $message = null, $addChild = 0, $familyId = null)
    {

        $family_heard          = new Setting('how_heard_category');
        $relationship          = new Setting('relationships');
        $race                  = new Setting('race');

        $ethnicity             = new Setting('ethnicity');
        $call_reason           = new Setting('call_reason');
        $who_called            = new Setting('who_called');
        $language              = new Setting('language');
        $cities                = new Setting('city');
        $status                = new Setting('status');
        $cc_level              = new Setting('cc_level');
        $health_insurance      = new Setting('health_insurance');
        $best_times            = new Setting('best_times');
        $best_hours            = new Setting('best_hours');
        $child_issues          = new Setting('child_issues');
        $asq_preference        = new Setting('asq_preference');
        $permission_fax_type   = new Setting('permission_fax_type');
        $county                = new Setting('county');
		$point_of_entry		   = new Setting('point_of_entry');

        $how_heard_details     = '';
        if(isset($family->family['family_heard_id']) 
            && !empty($family->family['family_heard_id'])) {
            $settingObj = new Setting();
            //$how_heard_details = $settingObj->getHowHearDetails($family->family['how_heard_category_id'], $family->family['how_heard_details_id']);
            $how_heard_details = $settingObj->displayHeardDetailsSelect(
                $parent = $family->family['family_heard_id'],
                'data[how_heard_details_id]',
                (isset($family->family['how_heard_details_id']) ? $family->family['how_heard_details_id'] : ''), '',
                $label    = ' ',
                $tabIndex = '',
                $required = false,
                $addtlclasses = 'chosen-select',
                true,
                $filtered = true,
                $allowDisableSelect = true,
                'how_heard_details_id'
            );
        }
        
        $field = '';
        $sort  = '';
        if (isset($_SESSION['family-sorts'])) {
            $field = key($_SESSION['family-sorts']);
            $sort  = $_SESSION['family-sorts'][$field];
        }
         $data = $family->getAll();
        $permissions            = new Setting('permission');
        
        if(empty($data['ecids_permission']))
            $data['ecids_permission'] = 'Not Given';
            
        $ecids_options = '<select name="data[ecids_permission]" >';
        foreach ( $permissions->settings as $p ){

                $ecids_options .= '<option value="'.$p['name'].'" '.($data['ecids_permission'] == $p['name']?"selected":'').' >'.$p['name'].' </option>';

        }
        $ecids_options .= '</select>';
        if(empty($data['sharing_info']))
            $data['sharing_info'] = 'Not Given';
        
        $sharing_options = '<select name="data[sharing_info]" >';
        foreach ( $permissions->settings as $p )
            $sharing_options .= '<option value="'.$p['name'].'" '.($data['sharing_info'] == $p['name']?"selected":'') .' >'.$p['name'].' </option>';
        $sharing_options .= '</select>';
        

        $user = new User();
        $data['hmg_worker'] = $user->getById($data['hmg_worker']);

        // echo "<pre>";print_r($data);die;
        if (is_array($data['children'])) {
            foreach ($data['children'] as $id => $child) {
                if (!isset($child['birth_date_formatted']) && !empty($child['birth_date'])) {
                    $data['children'][$id]['birth_date_formatted'] = $child['birth_date'];
                } else if (! isset($child['birth_date_formatted'])) {
                    $data['children'][$id]['birth_date_formatted'] = null;
                }
                // If we are editing a family then don't show child notes in the child notes field
                // Any note added there will be added as a new child note in the system
                if (is_numeric($familyId)) {
                    unset($data['children'][$id]['notes']);
                }
            }
        }
        
        // Get the primary provider
        $familyProvider = new FamilyProvider($data['id']);
        $providers = $familyProvider->getList();
        
        include(VIEW_PATH . '/adminnav.phtml');

        ob_start();
        include(VIEW_PATH . '/family-form.phtml');
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
