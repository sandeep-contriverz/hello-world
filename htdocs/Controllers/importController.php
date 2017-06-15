<?php

namespace Hmg\Controllers;

use Hmg\Models\Organizatione;
use Hmg\Models\Organizations;
use Hmg\Models\Event;
use Hmg\Models\OrganizationFollowUp;
use Hmg\Models\ContactFollowUp;
use Hmg\Models\Zip;
use Hmg\Models\User;

class ImportController
{

    public function __construct()
    {
        
        ini_set("display_errors",1);
        error_reporting(1);
       if(isset($_REQUEST['orgs']))
            $this->migrate_org_data();
        elseif(isset($_REQUEST['referrals']))
            $this->migrate_referrals();
        elseif(isset($_REQUEST['providers']))
            $this->migrate_providers();
        elseif(isset($_REQUEST['hmg_worker']) && isset($_REQUEST['table']))
            $this->migrate_hmgWorker_ids($_REQUEST['table']);
        elseif(isset($_REQUEST['notes'])) {
            $this->migrate_notes($_REQUEST['table']);
        }elseif(isset($_REQUEST['howheard'])) {
            $this->migrate_how_heard();
        }elseif(isset($_REQUEST['dupevents'])) {
            $this->migrate_duplicate_events();
        }elseif(isset($_REQUEST['dupfamilyproviders'])) {
            $this->migrate_duplicate_family_providers();
        }
        elseif(isset($_REQUEST['final'])) {
            $this->finalCheckup();
        }
        
    }
    

    /* fetch all data related with Organization, contacts, events, followups 
     *  from temporary (named with "import_*") tables and save to original tables 
     *
     *  $row_org will contains Organization record if exists
     *  $row_site will contains Site record record if exists (only when $row_org is not empty)
     */
    function migrate_org_data() {
        $sql  = "Select * from import_clinics  order by ClinicID asc";
        $rs   = mysql_query($sql) or die($sql);
        $rows = array();
        if ($rs) {
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {                
                
                $row_org     = $row_site = array();
                echo $site_name   = trim($row['Site']);
                echo $name        = trim($row['ClinicName']);
                
                $sql_info  = 'Select * from settings where name="'.$name.'" and type="info_referall"';
                $rs_info   = mysql_query( $sql_info );
                    
                if( !mysql_num_rows( $rs_info ) ){
                    
                
                    $site_id     = 0;  
                    $org_name_id = 0;  
                    $org_id      = 0;  
                    $organization_sites_id = 0;
                    $org_id_array           = array(0);
                    $org_exists = 0;
                    $site_new = 0;


                    $status    = trim($row['Status']);
                    $status_id = 0;
                    //check organization_status in settings
                    $row_status = array();
                    if(!empty($status)) {
                        $check_status = 'Select * from settings where LOWER(name)="'.strtolower(trim($status)).'" 
                            AND type="organization_status"';
                        $rs_status    = mysql_query($check_status) or die($check_status);
                        $row_status   = mysql_fetch_array($rs_status, MYSQL_ASSOC);
                    }
                    if(!empty($row_status)) {
                        $status_id = $row_status['id'];
                    } elseif(!empty($status)) {
                        //new record
                        $sql_up  = "Insert into settings set name='".$status."', type='organization_status', disabled='0'";
                        $update  = mysql_query($sql_up) or die($sql_up);
                        $status_id = mysql_insert_id();
                    }
                    
                    $check_org = 'Select * from settings where name="'.$name.'" AND type="referred_to"';
                    $rs_org    = mysql_query($check_org);
                    $row_org   = mysql_fetch_array($rs_org, MYSQL_ASSOC);
                    
                    if(!empty($row_org)) {
                        $org_name_id  = $row_org['id'];
                    } else{
                        if(!empty($name)) { 
                            $sql_up  = 'Insert into settings set name="'.$name.'", type="referred_to", disabled="0"';
                            $update = mysql_query($sql_up) or die($sql_up);
                            $org_name_id = mysql_insert_id();                            
                        }
                    }
                    
                    $check_org_main = 'Select * from organizations where organization_name_id="'.$org_name_id.'" ';
                    $rs_org_main    = mysql_query($check_org_main);
                    $row_org_main   = mysql_fetch_array($rs_org_main, MYSQL_ASSOC);
                    if(empty($row_org_main)) { 
                       //insert new organization record to database
                        $sql = "Insert into organizations set organization_name_id=".$org_name_id.", 
                            address='".$row['Address']."', city='".$row['City']."', 
                            state='".strtoupper($row['State'])."', zip='".$row['Zip']."', fax='".$row['FaxPhone']."'";
                        mysql_query($sql) or die($sql);
                        $org_id = mysql_insert_id();
                        $org_exists = 0;
                    } else {
                        $org_id_array[] = $row_org_main['id'];
                        while( $row_org_main   = mysql_fetch_array($rs_org_main, MYSQL_ASSOC) )
                            $org_id_array[] = $row_org_main['id'];
                        $org_exists = 1;
                    }
                        
                    if(empty($site_name)) {
                        $check_org = 'Select id, 
                            organization_site_id,organization_id
                        from organization_sites
                        Where 
                            organization_id in ('.implode(',',$org_id_array).')
                            AND organization_site_id="0"';

                        $rs_org    = mysql_query($check_org) or die($check_org);                        
                        $row_org = mysql_fetch_array($rs_org, MYSQL_ASSOC);

                        //For Empty site name add a blank site entry with organisation
                        if(!empty($row_org)) {
                            $site_id  = $row_org['id'];
                            $site_new = 0;
                            $org_id = $row_org['organization_id'];
                        } else {
                            if( $org_exists ){
                                $sql = "Insert into organizations set organization_name_id=".$org_name_id.", 
                            address='".$row['Address']."', city='".$row['City']."', 
                            state='".strtoupper($row['State'])."', zip='".$row['Zip']."', fax='".$row['FaxPhone']."'";
                                mysql_query($sql) or die($sql);
                                $org_id = mysql_insert_id();
                            }
                            $sql_up  = 'Insert into organization_sites set status="'.$status_id.'",organization_id="'.$org_id.'", organization_site_id="0" ';
                            $update  = mysql_query($sql_up) or die($sql_up);
                            $site_id = mysql_insert_id();  
                            $site_new = 0;
                        }

                    } else {
                        $check_org = 'Select * from settings where name="'.$site_name.'" AND type="organization_site"';
                            $rs_org    = mysql_query($check_org);
                            $row_org   = mysql_fetch_array($rs_org, MYSQL_ASSOC);

                            if(!empty($row_org)) {
                                $site_id  = $row_org['id'];
                                
                            } else{
                                $sql_up  = 'Insert into settings set name="'.$site_name.'", type="organization_site", disabled="0"';
                                $update  = mysql_query($sql_up) or die($sql_up);
                                $site_id = mysql_insert_id();
                            }
                            
                            $check_org = 'Select id, 
                                organization_site_id,organization_id 
                            from organization_sites
                            Where 
                                organization_id in ('.implode(',',$org_id_array).')
                                AND organization_site_id="'.$site_id.'"';
                            
                            $rs_org    = mysql_query($check_org) or die($check_org);                        
                            $row_org = mysql_fetch_array($rs_org, MYSQL_ASSOC);
                            if(!empty($row_org)) {
                                $site_id  = $row_org['id'];
                                $org_id = $row_org['organization_id'];
                            } else {
                                if( $org_exists ){
                                   $sql = "Insert into organizations set organization_name_id=".$org_name_id.", address='".$row['Address']."', city='".$row['City']."', 
                            state='".strtoupper($row['State'])."', zip='".$row['Zip']."', fax='".$row['FaxPhone']."'";
                                    mysql_query($sql) or die($sql);
                                    $org_id = mysql_insert_id();
                                }
                                $sql_up  = 'Insert into organization_sites set status="'.$status_id.'",organization_id="'.$org_id.'", organization_site_id="'.$site_id.'" ';
                                $update  = mysql_query($sql_up) or die($sql_up);
                                $site_id = mysql_insert_id();                                
                            }
                    } 
                    
                echo $org_id;
                    
                    
                
                $type    = trim($row['TypeofAgency']);
                $type_id = 0;
                //check organization_type in settings
                if(!empty($type)) {
                    if(strtolower($type) == 'business') {
                        $type = 'Community Agency';
                    } elseif(strtolower($type) == 'government') {
                        $type = 'Community Agency';
                    } elseif(strtolower($type) == 'healthcare provider') {
                        $type = 'Health Care';
                    } elseif(strtolower($type) == 'library') {
                        $type = 'Community Agency';
                    } elseif(strtolower($type) == 'religious entity') {
                        $type = 'Faith Based Organization';
                    } elseif(strtolower($type) == 'school') {
                        $type = 'Early Care and Education Provider';
                    } elseif(strtolower($type) == 'service provider') {
                        $type = 'Community Agency';
                    }
                    $check_type = 'Select * from settings where LOWER(name)="'.strtolower($type).'" 
                        AND type="organization_type"';
                    $rs_type    = mysql_query($check_type) or die($check_type);
                    $row_type   = mysql_fetch_array($rs_type, MYSQL_ASSOC);
                }  
                if(!empty($row_type)) {
                    $type_id = $row_type['id'];
                }elseif(!empty($type)) { 
                    //add new record
                    $sql_up  = "Insert into settings set name='".$type."', type='organization_type', disabled='0'";
                    $update  = mysql_query($sql_up) or die($sql_up);
                    $type_id = mysql_insert_id();
                }
                
                $contact_address = $contact_city = $contact_state = $contact_zip = '';
                $contact_county = $contact_fax = $contact_phone = $contacts_notes = '';
                //fetch contacts data
                $sql    = "Select * from import_contacts 
                    Where ClinicID='".$row['ClinicID']."' order by ContactID asc";
                echo "<br>";
                $rs_con = mysql_query($sql) or die($sql);
                $contacts_data = array();
                $contact_type  = 0;
                if ($rs_con) {
                    echo "Inserting new contact <br>";
                    while ($contact = mysql_fetch_array($rs_con, MYSQL_ASSOC)) {
                        if(!empty($contact['Key']) && $contact['Key']=='TRUE')
                            $contact_type = 4790;
                        elseif(!empty($contact['Potential']) && $contact['Potential']=='TRUE')
                            $contact_type = 4789;
                        elseif(!empty($contact['Inactive']) && $contact['Inactive']=='TRUE')
                            $contact_type = 4788;
                        else
                            $contact_type = 4787;

                        $contact_follow_ups = array();
                        //fetch follow-ups for contact and insert
                        $sql_cfl = "Select * from import_contact_followup 
                            Where ContactID='".$contact['ContactID']."' order by id asc";
                        $rs_cfl  = mysql_query($sql_cfl) or die($sql_cfl);
                        $cfollowup_data = array();
                        if ($rs_cfl) {
                            echo "Inserting new contact followups <br>";
                            while ($row_cfl = mysql_fetch_array($rs_cfl, MYSQL_ASSOC)) {
                                $this->insert_hmgWorker($row_cfl['HMGContact']);
                                //fetch followup task id
                                $task = $row_cfl['ActionRequired'];
                                $task_id = 0;
                                //check region in settings
                                if(!empty($task)) {
                                    $check_task = 'Select * from settings where LOWER(name)="'.strtolower(trim($task)).'" 
                                        AND type="outreach_follow_up_task"';
                                    $rs_task    = mysql_query($check_task) or die($check_task);
                                    $row_task   = mysql_fetch_array($rs_task, MYSQL_ASSOC);
                                }
                                if(!empty($row_task)) {
                                    $task_id = $row_task['id'];
                                } elseif(!empty($task)) { //add new region
                                    $sql_up  = "Insert into settings set name='".$task."', type='outreach_follow_up_task', disabled='0'";
                                    $update  = mysql_query($sql_up) or die($sql_up);
                                    $task_id = mysql_insert_id();
                                }
                                $ReferralDate = '0000-00-00';
                                if( !empty($row_cfl['TodayDate']) ){
                                    $str = explode('/',$row_cfl['TodayDate']);
                                    $date = $str[2].'-'.$str[0].'-'.$str[1];
                                    $ReferralDate = date('Y-m-d', strtotime($date));
                                }
                                $DueDate = '0000-00-00';
                                if( !empty($row_cfl['DueDate']) ){
                                    $str = explode('/',$row_cfl['DueDate']);
                                    $date = $str[2].'-'.$str[0].'-'.$str[1];
                                    $DueDate = date('Y-m-d', strtotime($date));
                                }
                                $cfollowup_data = array(
                                    //'contact_id' => $contact['ContactID'],
                                    'hmg_worker' => $this->insert_hmgWorker($row_cfl['HMGContact']),
                                    'referral_date' => $ReferralDate,
                                    'referred_to_id' => 0,
                                    'service_id' => 0,
                                    'asq_month'  => '',
                                    'notes' => $row_cfl['Notes'],
                                    'follow_up_task_id' => $task_id,
                                    'follow_up_date' => $DueDate,
                                    'done'   => $row_cfl['Complete']=='TRUE'?1:0,
                                    'result' => '',
                                );
                                $contact_follow_ups[] = $cfollowup_data;
                                //insert follow-up data to table
                                /*$cfollowUpObj = new ContactFollowUp($cfollowup_data);
                                $cfollowUpObj->save();*/
                            }
                        }
                        $contact_phone  = $contact['DirectPhone'];
                        $contacts_notes = $contact['Notes'];
                        
                        $check_org_main1 = 'Select * from contacts where organization_sites_id="'.$site_id.'" and first="'.$contact['ContactFName'].'" and last="'.$contact['ContactLName'].'" ';
                        $rs_org_main1    = mysql_query($check_org_main1);
                        $row_org_main1   = mysql_fetch_array($rs_org_main1, MYSQL_ASSOC);
                        if(empty($row_org_main1)) { 
                            $contacts_data[] = array(
                                //'organization_id' => $org_id,
                                //'organization_sites_id' => $site_id,
                                'hmg_worker' => $this->insert_hmgWorker($contact['HMGContact']),
                                'first'  => $contact['ContactFName'],
                                'last'   => $contact['ContactLName'],
                                'title'  => $contact['Title'],
                                'gender' => $contact['Gender'],
                                'email'  => $contact['Email'],
                                'office_phone' => '',
                                'cell_phone'   => $contact['DirectPhone'],
                                'type_of_contact_id' => $contact_type,
                                'notes' => $contact['Notes'],
                                'contact_follow_ups' => $contact_follow_ups,
                                'is_import' => 1,
                            );
                        } else {
                            
                            $contacts_data[] = array(
                                //'organization_id' => $org_id,
                                //'organization_sites_id' => $site_id,
                                'hmg_worker' => $this->insert_hmgWorker($contact['HMGContact']),
                                'first'  => $contact['ContactFName'],
                                'last'   => $contact['ContactLName'],
                                'title'  => $contact['Title'],
                                'gender' => $contact['Gender'],
                                'email'  => $contact['Email'],
                                'office_phone' => '',
                                'cell_phone'   => $contact['DirectPhone'],
                                'type_of_contact_id' => $contact_type,
                                'notes' => $contact['Notes'],
                                'contact_follow_ups' => $contact_follow_ups,
                                'is_import' => 1,
                                'id'=> $row_org_main1['id']
                            );
                        }
                    }
                }
                //echo "<pre>";print_r($row);
                //fetch county from city OR zip code
                $zip = new Zip();
                if(!empty($row['Zip'])) {
                    $zip_data = $zip->getCountyByZip($row['Zip']);
                } elseif(!empty($row['City'])) {
                    $zip_data = $zip->getCountyByCity($row['City']);
                }
                $county = (isset($zip_data['county']) && !empty($zip_data['county']))
                            ? $zip_data['county'] : '';
                $this->insert_hmgWorker($row['HMGWorker']);

                
                $region    = $row['HMGRegion'];
                $region_id = 0;
                $row_region = array();
                //check region in settings
                if(!empty($region)) {
                    $check_region = 'Select * from settings where LOWER(name)="'.strtolower(trim($region)).'" 
                        AND type="region"';
                    $rs_region    = mysql_query($check_region) or die($check_region);
                    $row_region   = mysql_fetch_array($rs_region, MYSQL_ASSOC);
                }
                if(!empty($row_region)) {
                    $region_id = $row_region['id'];
                } elseif(!empty($region)) { //add new region
                    $sql_up  = "Insert into settings set name='".$region."', type='region', disabled='0'";
                    $update  = mysql_query($sql_up) or die($sql_up);
                    $region_id = mysql_insert_id();
                }
                $mode_of_contact    = trim($row['LeadSource']);
                $mode_of_contact_id = 0;
                $row_moc = array();
                //check mode_of_contact in settings
                if(!empty($mode_of_contact)) {
                    $check_moc = 'Select * from settings where LOWER(name)="'.strtolower($mode_of_contact).'" 
                        AND type="mode_of_contact"';
                    $rs_moc  = mysql_query($check_moc) or die($check_moc);
                    $row_moc = mysql_fetch_array($rs_moc, MYSQL_ASSOC);
                }
                if(!empty($row_moc)) {
                    $mode_of_contact_id = $row_moc['id'];
                } elseif(!empty($mode_of_contact)) { //add new region
                    $sql_up  = "Insert into settings set name='".$mode_of_contact."', type='mode_of_contact', disabled='0'";
                    $update  = mysql_query($sql_up) or die($sql_up);
                    $mode_of_contact_id = mysql_insert_id();
                }
                $service_area = array();

                $resource_database    = trim($row['ServicePoint']);
                $resource_database_id = 0;
                $row_resd = array();
                //check resource_database in settings
                if(!empty($resource_database)) {
                    $check_resd = 'Select * from settings where LOWER(name)="'.strtolower($resource_database).'" 
                        AND type="resource_database"';
                    $rs_resd  = mysql_query($check_resd) or die($check_resd);
                    $row_resd = mysql_fetch_array($rs_resd, MYSQL_ASSOC);
                }
                if(!empty($row_resd)) {
                    $resource_database_id = $row_resd['id'];
                } elseif(!empty($resource_database)) { //add new resource_database
                    $sql_up  = "Insert into settings set name='".$resource_database."', type='resource_database', disabled='0'";
                    $update  = mysql_query($sql_up) or die($sql_up);
                    $resource_database_id = mysql_insert_id();
                }
                $partnership_level    = trim($row['Partnership']);
                $partnership_level_id = 0;
                $row_partn = array();
                //check partnership_level in settings
                if(!empty($partnership_level)) {
                    $check_partn = 'Select * from settings where LOWER(name)="'.strtolower($partnership_level).'" AND type="partnership_level"';
                    $rs_partn  = mysql_query($check_partn) or die($check_partn);
                    $row_partn = mysql_fetch_array($rs_partn, MYSQL_ASSOC);
                }
                if(!empty($row_partn)) {
                    $partnership_level_id = $row_partn['id'];
                } elseif(!empty($partnership_level)) { //add new partnership_level
                    $sql_up  = "Insert into settings set name='".$partnership_level."', type='partnership_level', disabled='0'";
                    $update  = mysql_query($sql_up) or die($sql_up);
                    $partnership_level_id = mysql_insert_id();
                }


                $service_area = array();
                
                //fetch service area
                $sql_serv = "Select * from import_service_area Where ClinicID='".$row['ClinicID']."'";
                $rs_serv = mysql_query($sql_serv) or die($sql_serv);
                
                if ($rs_serv) {
                    while ($service = mysql_fetch_array($rs_serv, MYSQL_ASSOC)) {
                        //fetch county name
                        $sql_county = 'Select * from import_counties Where CountyID="'.$service['CountyID'].'"';
                        
                        $rs_county  = mysql_query($sql_county) or die($sql_county);
                        $county_data = mysql_fetch_array($rs_county, MYSQL_ASSOC);
                        if(!empty($county_data)) {
                            //fetch county id from settings table
                            $county_name = trim($county_data['County']);
                            $row_county = array();
                            $check_county = 'Select * from settings where LOWER(name)="'.strtolower($county_name).'" 
                                    AND type="county"';
                            
                            $rs_county    = mysql_query($check_county) or die($check_county);
                            $row_county   = mysql_fetch_array($rs_county, MYSQL_ASSOC);
                            if(!empty($row_county)) {
                                $service_area[] = $row_county['id'];
                            } elseif(!empty($county_name)) {
                                //new record
                                $sql_up  = "Insert into settings set name='".$county_name."', type='county', disabled='0'";                                
                                $update  = mysql_query($sql_up) or die($sql_up);
                                $service_area[] = mysql_insert_id();
                            }
                        }
                    }
                }
                
                $StartDate = '0000-00-00';
                if( !empty($row['StartDate']) ){
                    $str = explode('/',$row['StartDate']);
                    $date = $str[2].'-'.$str[1].'-'.$str[0];
                    $StartDate= date('Y-m-d', strtotime($row['StartDate']));
                }
                $EndDate = '0000-00-00';
                if( !empty($row['EndDate']) ){
                    $str = explode('/',$row['EndDate']);
                    $date = $str[2].'-'.$str[1].'-'.$str[0];
                    $EndDate = date('Y-m-d', strtotime($row['EndDate']));
                }
                $DateLastSigned = '0000-00-00';
                if( !empty($row['DateLastSigned']) ){
                    $str = explode('/',$row['DateLastSigned']);
                    $date = $str[2].'-'.$str[0].'-'.$str[1];
                    $DateLastSigned = date('Y-m-d', strtotime($row['DateLastSigned']));
                }
                
                $website= '';
                $data_web = explode('#',$row['Website']);
                foreach( $data_web as $dweb){
                    if( !empty( $dweb ) ){
                        $website = $dweb;
                        break;
                    }
                }
                $sqlwer = mysql_query("select service_id from service where referred_to_id in (select organization_sites.id from organization_sites left join organizations on organization_sites.organization_id=organizations.id where organization_name_id='".$org_name_id."') group by service_id");
                $serviceTerms = array();
                while( $rsER = mysql_fetch_array($sqlwer) ){
                    $serviceTerms[] = $rsER['service_id'];
                }
                    
                //save records
                $data = array(
                    'id'             => $org_id,
                    'organization_name_id' => $org_name_id,
                    'site'           => $site_id,
                    'organization_type_id' => $type_id,
                    //'email'       => $row['Email'],
                    'address'        => !empty($row['Address']) 
                                ? $row['Address'] : $contact_address,
                    'city'           => !empty($row['City']) 
                                ? $row['City'] : $contact_city,
                    'state'          => !empty($row['State']) 
                                ? strtoupper($row['State']) : strtoupper($contact_state),
                    'zip'            => !empty($row['Zip']) 
                                ? $row['Zip'] : $contact_zip,
                    'county'         => $county,
                    'fax'            => $row['FaxPhone'],
                    'primary_phone'  => $row['DayPhone'],
                    //'service_terms'     => str_replace(';', ',', trim($row['ServiceTerms'])),
                    'service_terms'  => $serviceTerms ,
                    'hmg_worker'     => $this->insert_hmgWorker($row['HMGWorker']),
                    'start_date'     => $StartDate,
                    'end_date'       => $EndDate,
                    'status'         => $status_id,
                    'success_story'  => ($row['PotentialSuccessStory']?'Yes':'No'),
                    'website'        => $website,
                    'region_id'      => $region_id,
                    'service_area'   => $service_area,
                    'mode_of_contact_id' => $mode_of_contact_id,
                    'mou'            => ($row['MOU']?'Yes':'No'),
                    'date_last_signed' => $DateLastSigned,
                    'note'           => !empty($row['Notes']) 
                                ? $row['Notes'] : '',
                    'success_story_notes' => $row['SuccessStoryNotes'],
                    'children'       => $contacts_data,
                    'is_import'      => 1,
                    'resource_database_id' => $resource_database_id,
                    'database_id'    => isset($row['SPID']) ? $row['SPID'] : 0,
                    'partnership_level_id' => $partnership_level_id,
                    'partnership_notes'    => isset($row['PartnershipNotes']) 
                            ? $row['PartnershipNotes'] : '',
                );
                
                //set organization data here to save
                $organization = new Organizatione();
                $organization->setOrganization($data);
                $saved_id = $organization->save();
                echo '$saved ORG : '.$saved_id ."<br>";

                //fetch all follow-ups and events
                $sql_fl = "Select * from import_org_followup Where ClinicID='".$row['ClinicID']."' order by FollowUpID asc";
                $rs_fl  = mysql_query($sql_fl) or die($sql_fl);
                $followup_data = array();
                if ($rs_fl) {
                    echo "Inserting new followup <br>";
                    while ($row_fl = mysql_fetch_array($rs_fl, MYSQL_ASSOC)) {
                        $this->insert_hmgWorker($row_fl['HMGContact']);
                        $TodayDate = '0000-00-00';
                        if( !empty($row_fl['TodayDate']) ){
                            $str = explode('/',$row_fl['TodayDate']);
                            $date = $str[2].'-'.$str[1].'-'.$str[0];
                            $TodayDate = date('Y-m-d', strtotime($row_fl['TodayDate']));
                        }
                        $DueDate = '0000-00-00';
                        if( !empty($row_fl['DueDate']) ){
                            $str = explode('/',$row_fl['DueDate']);
                            $date = $str[2].'-'.$str[1].'-'.$str[0];
                            $DueDate = date('Y-m-d', strtotime($row_fl['DueDate']));
                        }
                        $followup_data = array(
                            'organization_sites_id' => $saved_id,
                            'hmg_worker' => $this->insert_hmgWorker($row_fl['HMGContact']),
                            'referral_date' => $TodayDate,
                            'referred_to_id' => 0,
                            'service_id' => 0,
                            'notes' => $row_fl['Followupitem'],
                            'follow_up_task_id' => 0,
                            'follow_up_date' => $DueDate,
                            'done'   => $row_fl['Complete']=='TRUE'?1:0,
                            'result' => '',
                        );
                        //insert follow-up data to table
                        $followUpObj = new OrganizationFollowUp($followup_data);
                        $followUpObj->save();
                    }
                }

                $sql_evt = "Select * from import_events Where ClinicID='".$row['ClinicID']."' order by EventID asc";
                $rs_evt  = mysql_query($sql_evt) or die($sql_evt);
                $event_data = array();
                if ($rs_evt) {
                    while ($row_evt = mysql_fetch_array($rs_evt, MYSQL_ASSOC)) {
                        $event_type = trim($row_evt['EventType']);
                        $outreach_type = trim($row_evt['OutreachType']);
                        $event_type_id = 0;
                        $outreach_type_id = 0;
                        //check event_type in settings
                        if(!empty($event_type)) {
                            $check_type = 'Select * from settings where name="'.$event_type.'" 
                                AND type="event_type"';
                            $rs_type    = mysql_query($check_type) or die($check_type);
                            $row_type   = mysql_fetch_array($rs_type, MYSQL_ASSOC);
                        }
                        if(!empty($row_type)) {
                            $event_type_id = $row_type['id'];
                        }
                        //check outreach_type in settings
                        if(!empty($outreach_type)) {
                            $check_type = 'Select * from settings where name="'.$outreach_type.'" 
                                AND type="outreach_type"';
                            $rs_type    = mysql_query($check_type) or die($check_type);
                            $row_type   = mysql_fetch_array($rs_type, MYSQL_ASSOC);
                        }
                        if(!empty($row_type)) {
                            $outreach_type_id = $row_type['id'];
                        }
                        //fetch county by zipcode
                        $county_id  = 0;
                        $sql_county = 'Select * from county_zipcodes
                            Where zip_code="'.$row_evt['EventZip'].'" limit 1';
                        $rs_county  = mysql_query($sql_county) or die($sql_county);
                        $row_county = mysql_fetch_array($rs_county, MYSQL_ASSOC);
                        if(!empty($row_county) && !empty($row_county['county_id'])) {
                            $county_id = $row_county['county_id'];
                        }
                        //check for contact name, if not exists create new contact
                        $contact_id = 0;
                        $contact_name  = explode(' ', trim($row_evt['EventContact']));
                        $contact_fname = isset($contact_name[0]) ? $contact_name[0] : '';
                        $contact_lname = isset($contact_name[1]) ? $contact_name[1] : '';
                        /*$sql_cont   = 'Select * from contacts Where LOWER(first)="'.$contact_fname.'"
                            AND LOWER(last)="'.$contact_lname.'" limit 1';
                        $rs_cont    = mysql_query($sql_cont) or die($sql_cont);
                        $row_cont   = mysql_fetch_array($rs_cont, MYSQL_ASSOC);*/
                        /** As per client : Just add in all the contacts for that organization-site. 
                            We will go back later and clean these up as needed. 
                            (there may be some duplicates)
                        **/
                        $row_cont = array();
                        if(!empty($row_cont)) {
                            $contact_id = $row_cont['id'];
                        } else { //insert new record
                            /* No need to add contacts from events 291216
                            if(!empty($contact_fname) || !empty($contact_lname)) {
                                $insert = 'Insert into contacts set organization_id="'.$org_id.'",
                                     organization_sites_id="'.$saved_id.'", first="'.$contact_fname.'", 
                                     last="'.$contact_lname.'"';
                                mysql_query($insert) or die($insert);
                                $contact_id = mysql_insert_id();
                            }
                            */
                        }

                        /*$time_of_day = trim($row_evt['TimeofDay']);
                        if($time_of_day == 'Late Morning' || $time_of_day == 'Early Morning')
                            $time_of_day_id = 3172;
                        elseif($time_of_day == 'Late Afternoon' || $time_of_day == 'Early Afternoon')
                            $time_of_day_id = 3173;
                        elseif($time_of_day == 'Evening')
                            $time_of_day_id = 3174;
                        */
                        $time_of_day = array();
                        if(trim($row_evt['EarlyMorning']) == 'TRUE' || trim($row_evt['LateMorning']) == 'TRUE') {
                            $time_of_day[] = 4802;
                        }
                        
                        if(trim($row_evt['EarlyAfternoon']) == 'TRUE' || trim($row_evt['LateAfternoon']) == 'TRUE') {
                            $time_of_day[] = 4801;
                        }
                        if(trim($row_evt['Evening']) == 'TRUE') {
                            $time_of_day[] = 4800;
                        }
                        //$time_of_day = !empty($time_of_day) ? implode(',', $time_of_day);

                        $hmg_worker_id = $this->insert_hmgWorker($row_evt['HMGContact']);
                        $event_notes = trim($row_evt['Population']).' ; '.
                                trim($row_evt['WorkedWell']).' ; '.
                                trim($row_evt['Problematic']);
                        $event_date = '0000-00-00';
                        if( !empty($row_evt['EventDate']) ){
                            $str = explode('/',$row_evt['EventDate']);
                            $date = $str[2].'-'.$str[0].'-'.$str[1];
                            $event_date = date('Y-m-d', strtotime($row_evt['EventDate']));
                        }
                        $event_data = array(
                            'organization_sites_id' => $saved_id,
                            'event_name'       => $row_evt['EventName'],
                            'event_type_id'    => $event_type_id,
                            'outreach_type_id' => $outreach_type_id,
                            'event_date'       => $event_date,
                            'event_zipcode_id' => $row_evt['EventZip'],
                            'event_county_id'  => $county_id,
                            'event_contact_id' => $contact_id,
                            'hmg_worker'       => $hmg_worker_id,
                            'event_venue'      => $row_evt['EventVenue'],
                            'no_of_people'     => $row_evt['NumberofPeople'],
                            'no_of_families'   => $row_evt['NumberofFamilies'],
                            'event_duration'   => $row_evt['Duration'],
                            'no_of_staff'      => $row_evt['NoofStaff'],
                            'no_of_volunteers' => $row_evt['NoofVolunteers'],
                            'no_of_asq_completed' => $row_evt['ASQCompleted'],
                            'enrollment_forms'    => $row_evt['Enrollment'],
                            'pitches'          => $row_evt['Pitches'],
                            'quality_of_interactions' => $row_evt['Quality'],
                            'time_of_day'      => array_unique($time_of_day),
                            'attachments'      => '',
                            'event_notes'      => $event_notes,
                        );
                        echo "Inserting new event <br>";
                        //insert event data
                        if(!empty($saved_id) && !empty($row_evt['EventName'])) {
                            $eventObj = new Event();
                            $eventObj->addEvent($event_data);
                        }
                    }
                }

                echo "<hr>";
                }
            } //ends while loop of clinics
        }
        die('done');
    }


    function updateOrg() {
        $sql = 'Select * from organizations';
        $rs = mysql_query($sql);
        if ($rs) {
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                if(empty($row['organization_site_id']))
                    continue;
                $sql_note = 'INSERT INTO `organization_sites` SET organization_id='.$row["id"].', organization_site_id="'.mysql_real_escape_string($row['organization_site_id']).'"';
                mysql_query($sql_note) or die($sql);
            }
        }
    }

    /*
     *  Here you can migrate referrals data from HMG V2.2
     *  migrate referrals
     *  $org_name_id : Organization name id from settings table
     *  $site_id : Organization site id from settings table
     *  $organization_sites_id : Primary key of $organization_sites table
     */
    function migrate_referrals() {
        /** migration referrals starts 061216 **/
        echo "<pre>";
        $sql  = "Select * from settings Where type='referred_to'";
        $rs   = mysql_query($sql);
        $rows = array();
        
        if ($rs) {
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                
                if(empty($row['name']))
                    continue;
                
                echo "#############################################################################################################################"."</br>";
                
                $name                   = '';
                $site_name              = '';
                $site_id                = 0;
                $organization_sites_id  = 0;
                $org_name_id            = $row['id'];
                $explode                = explode(':', $row['name']);
                $org_id_array           = array(0);
                echo "Referral Name - ".$row['name']."</br>";
                
                if(!empty($explode[0]))
                    $name = trim($explode[0]);
                
                if(isset($explode[1]) && !empty($explode[1]))
                    $site_name = trim($explode[1]);
                
                //Check if organisation name present in informational referrals
                $sql_info  = 'Select * from settings where name="'.$row['name'].'" and type="info_referall"';
                $rs_info   = mysql_query( $sql_info );
                    
                if( mysql_num_rows( $rs_info ) ){
                    
                    echo "skipped due to info referral"."</br>";
                    $row_info = mysql_fetch_array($rs_info,MYSQL_ASSOC);
                    $organization_sites_id = $row_info['id'];
                    $ref_type = 'info';
                }  
                else{
                    
                    
                        
                    $check_org = 'Select * from settings where name="'.$name.'" AND type="referred_to"';
                    $rs_org    = mysql_query($check_org);
                    $row_org   = mysql_fetch_array($rs_org, MYSQL_ASSOC);

                    if(!empty($row_org)) {
                        $org_name_id  = $row_org['id'];
                        if( $row['id'] != $row_org['id']){
                            echo $sql_up = 'delete from settings Where id="'.$row['id'].'"';
                            $update = mysql_query($sql_up) or die(mysql_error());

                            $sql_up = "Update service set referred_to_id='".$org_name_id."' Where referred_to_id='".$row['id']."'";
                            $update = mysql_query($sql_up);

                            $sql_upd = 'Update family_referrals set referred_to_id="'.$org_name_id.'" 
                                Where referred_to_id="'.$row['id'].'"';
                            mysql_query($sql_upd) or die(mysql_error());
                            
                            $sql_upd = 'Update contact_referrals set referred_to_id="'.$org_name_id.'" 
                                Where referred_to_id="'.$row['id'].'"';
                            mysql_query($sql_upd) or die(mysql_error());
                            
                            $sql_upd = 'Update child_referrals set referred_to_id="'.$org_name_id.'" 
                                Where referred_to_id="'.$row['id'].'"';
                            mysql_query($sql_upd) or die(mysql_error());
                            
                            $sql_upd = 'Update organization_follow_up set referred_to_id="'.$org_name_id.'" 
                                Where referred_to_id="'.$row['id'].'"';
                            mysql_query($sql_upd) or die(mysql_error());
                            
                            $sql_upd = 'Update family_follow_up set referred_to_id="'.$org_name_id.'" 
                                Where referred_to_id="'.$row['id'].'"';
                            mysql_query($sql_upd) or die(mysql_error());
                            
                            $sql_upd = 'Update contact_follow_up set referred_to_id="'.$org_name_id.'" 
                                Where referred_to_id="'.$row['id'].'"';
                            mysql_query($sql_upd) or die($sql_upd);
                            
                            $sql_upd = 'Update child_follow_up set referred_to_id="'.$org_name_id.'" 
                                Where referred_to_id="'.$row['id'].'"';
                            mysql_query($sql_upd) or die($sql_upd);
                        }

                    } else{
                        echo $sql_up = 'Update settings set name="'.$name.'" Where id="'.$row['id'].'"';
                        $update = mysql_query($sql_up) or die($sql_up);
                    }
                    
                    //Check for organisation name
                    $check_org_main = 'Select * from organizations where organization_name_id="'.$org_name_id.'" ';
                    $rs_org_main    = mysql_query($check_org_main);
                    $row_org_main   = mysql_fetch_array($rs_org_main, MYSQL_ASSOC);
                    if(empty($row_org_main)) { 
                       //insert new organization record to database
                        echo $sql = "Insert into organizations set organization_name_id=".$org_name_id."";
                        mysql_query($sql) or die($sql);
                        $org_id = mysql_insert_id();
                        $org_exists = 0;
                    } else {
                       $org_id_array[] = $row_org_main['id'];
                        while( $row_org_main   = mysql_fetch_array($rs_org_main, MYSQL_ASSOC) )
                            $org_id_array[] = $row_org_main['id'];
                        $org_exists = 1;
                    }

                    
                
                    
                    if(empty($site_name)) {
                        $check_org = 'Select id, 
                            organization_site_id,organization_id 
                        from organization_sites
                        Where 
                            organization_id in ('.implode(',',$org_id_array).')
                            AND organization_site_id="0"';
                        
                        $rs_org    = mysql_query($check_org) or die($check_org);                        
                        $row_org = mysql_fetch_array($rs_org, MYSQL_ASSOC);
                        
                        //For Empty site name add a blank site entry with organisation
                        if(!empty($row_org)) {
                            $site_id  = $row_org['id'];
                             $org_id = $row_org['organization_id'];
                        } else{
                            if( $org_exists ){
                            $sql = "Insert into organizations set organization_name_id=".$org_name_id.", 
                            address='".$row['address']."', city='".$row['city']."', 
                            state='".strtoupper($row['state'])."', zip='".$row['zip']."', fax='".$row['fax']."'";
                            mysql_query($sql) or die($sql);
                            $org_id = mysql_insert_id();
                        }
                            echo $sql_up  = 'Insert into organization_sites set organization_id="'.$org_id.'", organization_site_id="0" ';
                            $update  = mysql_query($sql_up) or die($sql_up);
                            $site_id = mysql_insert_id();                                 
                        }
                        
                    } else {
                        $check_org = 'Select * from settings where name="'.$site_name.'" AND type="organization_site"';
                        $rs_org    = mysql_query($check_org);
                        $row_org   = mysql_fetch_array($rs_org, MYSQL_ASSOC);

                        if(!empty($row_org)) {
                            $site_id  = $row_org['id'];
                            
                        } else{
                            echo $sql_up  = 'Insert into settings set name="'.$site_name.'", type="organization_site", disabled="0"';
                            $update  = mysql_query($sql_up) or die($sql_up);
                            $site_id = mysql_insert_id();
                        }
                        
                        $check_org = 'Select id, 
                            organization_site_id,organization_id 
                        from organization_sites
                        Where 
                            organization_id in ('.implode(',',$org_id_array).') 
                            AND organization_site_id="'.$site_id.'"';
                        
                        $rs_org    = mysql_query($check_org) or die($check_org);                        
                        $row_org = mysql_fetch_array($rs_org, MYSQL_ASSOC);
                        if(!empty($row_org)) {
                            $site_id  = $row_org['id'];
                             $org_id = $row_org['organization_id'];
                        } else {
                            if( $org_exists ){
                                $sql = "Insert into organizations set organization_name_id=".$org_name_id.", 
                                address='".$row['address']."', city='".$row['city']."', 
                                state='".strtoupper($row['state'])."', zip='".$row['zip']."', fax='".$row['fax']."'";
                                mysql_query($sql) or die($sql);
                                $org_id = mysql_insert_id();
                            }
                            echo $sql_up  = 'Insert into organization_sites set organization_id="'.$org_id.'", organization_site_id="'.$site_id.'" ';
                            $update  = mysql_query($sql_up) or die($sql_up);
                            $site_id = mysql_insert_id();                                
                        }
                    } 
                
                    //check if service exists then update service table records                    
                    $ref_type = 'os';
                    $organization_sites_id = $site_id;
                    
                    if(!empty($organization_sites_id)) {
                        $sql_up = "Update service set organization_name_id='".$org_name_id."',referred_to_id='".$organization_sites_id."' 
                            Where referred_to_id='".$org_name_id."'";
                        $update = mysql_query($sql_up);
                    }
                }
                
                
                
                $sql_upd = 'Update family_referrals set referred_to_type="'.$ref_type.'",referred_to_id="'.$organization_sites_id.'" 
                    Where referred_to_id="'.$org_name_id.'"';
                mysql_query($sql_upd) or die($sql_upd);
                
                $sql_upd = 'Update contact_referrals set referred_to_type="'.$ref_type.'",referred_to_id="'.$organization_sites_id.'" 
                    Where referred_to_id="'.$org_name_id.'"';
                mysql_query($sql_upd) or die($sql_upd);
                
                $sql_upd = 'Update child_referrals set referred_to_type="'.$ref_type.'",referred_to_id="'.$organization_sites_id.'" 
                    Where referred_to_id="'.$org_name_id.'"';
                mysql_query($sql_upd) or die($sql_upd);
                
                $sql_upd = 'Update organization_follow_up set referred_to_type="'.$ref_type.'", referred_to_id="'.$organization_sites_id.'" 
                    Where referred_to_id="'.$org_name_id.'"';
                mysql_query($sql_upd) or die($sql_upd);
                
                $sql_upd = 'Update family_follow_up set referred_to_type="'.$ref_type.'",referred_to_id="'.$organization_sites_id.'" 
                    Where referred_to_id="'.$org_name_id.'"';
                mysql_query($sql_upd) or die($sql_upd);
                
                $sql_upd = 'Update contact_follow_up set referred_to_type="'.$ref_type.'",referred_to_id="'.$organization_sites_id.'" 
                    Where referred_to_id="'.$org_name_id.'"';
                mysql_query($sql_upd) or die($sql_upd);
                
                $sql_upd = 'Update child_follow_up set referred_to_type="'.$ref_type.'",referred_to_id="'.$organization_sites_id.'" 
                    Where referred_to_id="'.$org_name_id.'"';
                mysql_query($sql_upd) or die($sql_upd);
            
            }
        }
        //echo "<pre>";print_r($rows);
        die('done');

    }

    /*
     *  Here you can migrate providers data from HMG V2.2
     *  migrate providers
     */
    function migrate_providers() {
        $sql  = "Select * from providers order by id asc";
        $rs   = mysql_query($sql) or die($sql);
        $rows = array();
        if ($rs) {
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                
                if(empty($row['employer']))
                    continue;
                
                $sql_info  = 'Select * from settings where name="'.$row['employer'].'" and type="info_referall"';
                $rs_info   = mysql_query( $sql_info );
                    
                if( !mysql_num_rows( $rs_info ) ){
                    $name                   = '';   
                    $site_name              = '';
                    $site_id                = 0;
                    $org_name_id            = 0;
                    $org_id                 = 0;
                    $organization_sites_id  = 0;
                    $org_id_array           = array(0);
                    //use the employer name for the Organisations
                    $explode   = explode(':', $row['employer']);
                    
                    if(!empty($explode[0]))
                        $name = trim($explode[0]);
                    if(isset($explode[1]) && !empty($explode[1]))
                        $site_name = trim($explode[1]);                    
                    
                    $check_org = 'Select * from settings where name="'.$name.'" AND type="referred_to"';
                    $rs_org    = mysql_query($check_org);
                    $row_org   = mysql_fetch_array($rs_org, MYSQL_ASSOC);
                    
                    if(!empty($row_org)) {
                        $org_name_id  = $row_org['id'];
                    } else{
                        if(!empty($name)) { 
                            $sql_up  = 'Insert into settings set name="'.$name.'", type="referred_to", disabled="0"';
                            $update = mysql_query($sql_up) or die($sql_up);
                            $org_name_id = mysql_insert_id();                            
                        }
                    }
                    
                    $check_org_main = 'Select * from organizations where organization_name_id="'.$org_name_id.'" ';
                    $rs_org_main    = mysql_query($check_org_main);
                    $row_org_main   = mysql_fetch_array($rs_org_main, MYSQL_ASSOC);
                    if(empty($row_org_main)) { 
                       //insert new organization record to database
                        $sql = "Insert into organizations set organization_name_id=".$org_name_id.", 
                            address='".$row['address']."', city='".$row['city']."', 
                            state='".strtoupper($row['state'])."', zip='".$row['zip']."', fax='".$row['fax']."'";
                        mysql_query($sql) or die($sql);
                        $org_id = mysql_insert_id();
                        $org_exists = 0;
                    } else {
                        
                        $org_id_array[] = $row_org_main['id'];
                        while( $row_org_main   = mysql_fetch_array($rs_org_main, MYSQL_ASSOC) )
                            $org_id_array[] = $row_org_main['id'];
                        $org_exists = 1;
                    }
                    
                    $row_org = array();
                    
                    if(!empty($org_name_id)) {
                        
                        if(empty($site_name)) {
                            $check_org = 'Select id, 
                                organization_site_id,organization_id 
                            from organization_sites
                            Where 
                                organization_id in ('.implode(',',$org_id_array).') 
                                AND organization_site_id="0"';
                            
                            $rs_org    = mysql_query($check_org) or die($check_org);                        
                            $row_org = mysql_fetch_array($rs_org, MYSQL_ASSOC);
                            
                            //For Empty site name add a blank site entry with organisation
                            if(!empty($row_org)) {
                                $site_id  = $row_org['id'];
                                 $org_id = $row_org['organization_id'];
                            } else{
                                if( $org_exists ){
                                    $sql = "Insert into organizations set organization_name_id=".$org_name_id.", 
                                    address='".$row['address']."', city='".$row['city']."', 
                                    state='".strtoupper($row['state'])."', zip='".$row['zip']."', fax='".$row['fax']."'";
                                    mysql_query($sql) or die($sql);
                                    $org_id = mysql_insert_id();
                                }
                                $sql_up  = 'Insert into organization_sites set organization_id="'.$org_id.'", organization_site_id="0" ';
                                $update  = mysql_query($sql_up) or die($sql_up);
                                $site_id = mysql_insert_id();                                 
                            }
                            
                        } else {
                            $check_org = 'Select * from settings where name="'.$site_name.'" AND type="organization_site"';
                            $rs_org    = mysql_query($check_org);
                            $row_org   = mysql_fetch_array($rs_org, MYSQL_ASSOC);

                            if(!empty($row_org)) {
                                $site_id  = $row_org['id'];
                                
                            } else{
                                $sql_up  = 'Insert into settings set name="'.$site_name.'", type="organization_site", disabled="0"';
                                $update  = mysql_query($sql_up) or die($sql_up);
                                $site_id = mysql_insert_id();
                            }
                            
                            $check_org = 'Select id, 
                                organization_site_id,organization_id 
                            from organization_sites
                            Where 
                                organization_id in ('.implode(',',$org_id_array).') 
                                AND organization_site_id="'.$site_id.'"';
                            
                            $rs_org    = mysql_query($check_org) or die($check_org);                        
                            $row_org = mysql_fetch_array($rs_org, MYSQL_ASSOC);
                            if(!empty($row_org)) {
                                $site_id  = $row_org['id'];
                                 $org_id = $row_org['organization_id'];
                            } else {
                                if( $org_exists ){
                                $sql = "Insert into organizations set organization_name_id=".$org_name_id.", 
                                address='".$row['address']."', city='".$row['city']."', 
                                state='".strtoupper($row['state'])."', zip='".$row['zip']."', fax='".$row['fax']."'";
                                mysql_query($sql) or die($sql);
                                $org_id = mysql_insert_id();
                            }
                                $sql_up  = 'Insert into organization_sites set organization_id="'.$org_id.'", organization_site_id="'.$site_id.'" ';
                                $update  = mysql_query($sql_up) or die($sql_up);
                                $site_id = mysql_insert_id();                                
                            }
                        }                        
                        
                        //save contacts
                        $data = array(
                            'org_name_id'           => $org_name_id,
                            'org_id'                => $org_id,
                            'organization_sites_id' => $site_id,
                            'first'                 => $row['first_name'],
                            'last'                  => $row['last_name'],
                            'email'                 => $row['email'],
                            'phone'                 => $row['phone'],
                            'notes'                 => $row['notes'],
                            'title'                 => $row['title'],
                        );
                         
                        $new_id = $this->set_contacts($data);
                        
                        $sql_up  = 'update family_provider set contact_id="'.$new_id.'" ,organization_site_id="'.$site_id.'" where provider_id='.$row['id'];
                        $update  = mysql_query($sql_up) or die($sql_up);
                         
                        if(!empty($record)) {
                            $setFields = '';
                            if(!empty($row['city']))
                                $setFields .= ($setFields ? ', ' : '') . '`city` = "' . $row['city'] . '"';
                            if(!empty($row['state']))
                                $setFields .= ($setFields ? ', ' : '') . '`state` = "' . $row['state'] . '"';
                            if(!empty($row['zip']))
                                $setFields .= ($setFields ? ', ' : '') . '`zip` = "' . $row['zip'] . '"';
                            if(!empty($row['phone']))
                                $setFields .= ($setFields ? ', ' : '') . '`primary_phone` = "' . $row['phone'] . '"';
                            if(!empty($row['address']))
                                $setFields .= ($setFields ? ', ' : '') . '`address` = "' . $row['address'] . '"';
                            if(!empty($row['fax']))
                                $setFields .= ($setFields ? ', ' : '') . '`fax` = "' . $row['fax'] . '"';
                            if(!empty($setFields)) {
                                $sql_in = 'UPDATE `organizations` SET ' . $setFields . '
                                    WHERE `id` = "' .$org_id. '"';
                                mysql_query($sql_in) or die($sql_in);
                            }
                        }
                        
                    }
                }
                
            } //ends while loop of providers
        }
        die('done');
    }

    function set_contacts($data = array()) {
        if(empty($data))
            return false;

        $data['notes'] = str_replace('"', "'", $data['notes']);
        
        $check_org_main = 'Select * from contacts where organization_sites_id="'.$data['organization_sites_id'].'" and first="'.$data['first'].'" and last="'.$data['last'].'" ';
        $rs_org_main    = mysql_query($check_org_main);
        $row_org_main   = mysql_fetch_array($rs_org_main, MYSQL_ASSOC);
        if(empty($row_org_main)) { 
           //insert new organization record to database
            $sql = 'Insert into contacts set organization_id="'.$data['org_id'].'", organization_sites_id="'.$data['organization_sites_id'].'", first="'.$data['first'].'", last="'.$data['last'].'", email="'.$data['email'].'", cell_phone="'.$data['phone'].'", office_phone="", notes="'.$data['notes'].'", title="'.$data['title'].'"';
        
            mysql_query($sql) or die($sql);
            $org_id = mysql_insert_id();
        } else {
            $org_id = $row_org_main['id'];
        }
        
                 
        return $org_id;
    }

    function insert_hmgWorker($hmg_worker = '') {
        if(empty($hmg_worker))
            return false;

        //first check if user already exists
        $sql = 'Select * from users 
            Where LOWER(hmg_worker)="'.strtolower($hmg_worker).'" Limit 1';
        $rs  = mysql_query($sql) or die($sql);
        $record = mysql_fetch_array($rs, MYSQL_ASSOC);
        if(!empty($record)) {
            return $record['id'];
        } else { //add new one
            $password = md5('HMGV32016');
            $sql = 'Insert into users set first_name="'.$hmg_worker.'", last_name="",
                hmg_worker="'.$hmg_worker.'", email="'.$hmg_worker.'@example.com", password="'.$password.'",
                permission="Read Only", status="0", region_id=""';
            $rs  = mysql_query($sql) or die($sql);
            return mysql_insert_id();
        }
        return true;
    }


    /*
     *  Here you can migrate hmg worker data from HMG V2.2
     *  migrate hmg worker ids
     */
    function migrate_hmgWorker_ids($table = '') {
        if(empty($table))
            return false;
        /** migration hmg_worker starts 061216 **/
        $sql  = "Select * from $table";
        $rs   = mysql_query($sql);
        $rows = array();
        if ($rs) {
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                if(empty($row['hmg_worker'])) {
                    //update with hmg_worker = 0
                    $sql_up = "Update $table set hmg_worker_id='0' Where id='".$row['id']."'";
                    $update = mysql_query($sql_up) or die($sql_up);
                    continue;
                }
                $check = $this->check_hmgWorker($row['hmg_worker']);
                if($check) {
                    //update with hmg_worker = 0
                    $sql_up = "Update $table set hmg_worker_id='".$check."' 
                        Where id='".$row['id']."'";
                    $update = mysql_query($sql_up) or die($sql_up);
                } else {
                    $sql_up = "Update $table set hmg_worker_id='0' Where id='".$row['id']."'";
                    $update = mysql_query($sql_up) or die($sql_up);
                }
            }
        }
        //echo "<pre>";print_r($rows);
        die('done');

    }

    function check_hmgWorker($hmg_worker = '') {
        if(empty($hmg_worker))
            return false;

        //first check if user already exists
        $sql = 'Select * from users 
            Where LOWER(hmg_worker)="'.strtolower($hmg_worker).'" Limit 1';
        $rs  = mysql_query($sql) or die($sql);
        $record = mysql_fetch_array($rs, MYSQL_ASSOC);
        if(!empty($record)) {
            return $record['id'];
        }
        return false;        
    }

    function return_hmg_worker($hmg_worker = '') {
        if(empty($hmg_worker))
            return 0;
        if(!is_numeric($hmg_worker)) { //if its string
            return $this->check_hmgWorker($hmg_worker);
        } else { //return it
            return $hmg_worker;
        }
        return 0;
    }
    /**
     *  Migrate notes dates
     */
    function migrate_notes($table='') {
        if(empty($table))
            return false;

        $sql  = "Select * from ".$table."_backup";
        $rs   = mysql_query($sql);
        $rows = array();
        if ($rs) {
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                //update with hmg_worker = 0
                $sql_up = "Update $table set modified='".$row['modified']."' 
                    Where id='".$row['id']."'";
                $update = mysql_query($sql_up) or die($sql_up);
                //echo $sql_up."<br>";
            }
        }
        //echo "<pre>";print_r($rows);
        die('done');
    }
    
    function migrate_how_heard() {
        
        $sql  = "Select * from families";
        $rs   = mysql_query($sql);
        $rows = array();
        if ($rs) {
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                
                if( $row['family_heard_id'] == 2 || $row['family_heard_id'] == 27){
                    $sql_up = "Update families set how_heard_details_id='3190', family_heard_id='4813' Where id='".$row['id']."'";
                    $update = mysql_query($sql_up) or die($sql_up);
                }
                if( $row['family_heard_id'] == 3){
                    $sql_up = "Update families set how_heard_details_id='3191', family_heard_id='4813' Where id='".$row['id']."'";
                    $update = mysql_query($sql_up) or die($sql_up);
                }
                if( $row['family_heard_id'] == 4 || $row['family_heard_id'] == 26 || $row['family_heard_id'] == 30 || $row['family_heard_id'] == 32 || $row['family_heard_id'] == 33 || $row['family_heard_id'] == 35 || $row['family_heard_id'] == 34){
                    $sql_up = "Update families set how_heard_details_id='3192', family_heard_id='4813' Where id='".$row['id']."'";
                    $update = mysql_query($sql_up) or die($sql_up);
                }
                if( $row['family_heard_id'] == 15 ){
                    $sql_up = "Update families set how_heard_details_id='4865', family_heard_id='4815' Where id='".$row['id']."'";
                    $update = mysql_query($sql_up) or die($sql_up);
                }
                if( $row['family_heard_id'] == 16){
                    $sql_up = "Update families set how_heard_details_id='4866', family_heard_id='4815' Where id='".$row['id']."'";
                    $update = mysql_query($sql_up) or die($sql_up);
                }
                if( $row['family_heard_id'] == 2216){
                    $sql_up = "Update families set how_heard_details_id='4864', family_heard_id='4815' Where id='".$row['id']."'";
                    $update = mysql_query($sql_up) or die($sql_up);
                }
                if( $row['family_heard_id'] == 19 || $row['family_heard_id'] == 1932){
                    $sql_up = "Update families set how_heard_details_id='3194', family_heard_id='4813' Where id='".$row['id']."'";
                    $update = mysql_query($sql_up) or die($sql_up);
                }
                //issue
                if( $row['family_heard_id'] == 17 || $row['family_heard_id'] == 1930){
                    $sql_up = "Update families set how_heard_details_id='0', family_heard_id='4817' Where id='".$row['id']."'";
                    $update = mysql_query($sql_up) or die($sql_up);
                }
                
                if( $row['family_heard_id'] == 1933 || $row['family_heard_id'] == 13 || $row['family_heard_id'] == 18 || $row['family_heard_id'] == 1931 || $row['family_heard_id'] == 29){
                    $sql_up = "Update families set how_heard_details_id='0', family_heard_id='4814' Where id='".$row['id']."'";
                    $update = mysql_query($sql_up) or die($sql_up);
                }
                if( $row['family_heard_id'] == 20){
                    $sql_up = "Update families set how_heard_details_id='4871', family_heard_id='4816' Where id='".$row['id']."'";
                    $update = mysql_query($sql_up) or die($sql_up);
                }
                if( $row['family_heard_id'] == 3055){
                    $sql_up = "Update families set how_heard_details_id='0', family_heard_id='4817' Where id='".$row['id']."'";
                    $update = mysql_query($sql_up) or die($sql_up);
                }                
            }
        }
        
        die('done');
    }
    
    function migrate_duplicate_events() {
        
        $sql  = "Select * from events";
        $rs   = mysql_query($sql);
        $rows = array();
        if ($rs) {
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $fdt = "Select * from events where event_id = '".$row['event_id']."' ";
                $rdt = mysql_query($fdt);
                $tdt = mysql_fetch_array($rdt, MYSQL_ASSOC);
                if( !empty( $tdt ) )
                    mysql_query("delete from events where event_date='".$row['event_date']."' and hmg_worker='".$row['hmg_worker']."' and organization_sites_id='".$row['organization_sites_id']."' and event_name='".$row['event_name']."' and event_id <> '".$row['event_id']."' ");
            }
        }
        die('donneeee');
    }
    
    function migrate_duplicate_family_providers() {
        
        $sql  = "Select * from  family_provider";
        $rs   = mysql_query($sql);
        $rows = array();
        if ($rs) {
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)) {
                $fdt = "Select * from family_provider where id = '".$row['id']."' ";
                $rdt = mysql_query($fdt);
                $tdt = mysql_fetch_array($rdt, MYSQL_ASSOC);
                if( !empty( $tdt ) )
                    mysql_query("delete from family_provider where family_id='".$row['family_id']."' and provider_id='".$row['provider_id']."' and organization_site_id='".$row['organization_site_id']."' and contact_id='".$row['contact_id']."' and id <> '".$row['id']."' ");
            }
        }
        die('donneeee');
    }
}
