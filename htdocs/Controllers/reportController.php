<?php

namespace Hmg\Controllers;

use Hmg\Models\Reports;

class ReportController
{
    public function __construct()
    {
        $reports= new Reports();
		
        $filters = '';
        $sorts = array();

        $search = '';
        $saveReport = isset($_REQUEST['saveReport']) ? $_REQUEST['saveReport'] : false;
        
        $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
        if (isset($_REQUEST['page']) && $_REQUEST['page']) {
            $reports->set('_start', ($_REQUEST['page'] - 1) * 20);
        } else {
            $reports->set('_start', 0);
        }
        if (isset($_REQUEST['field']) && $_REQUEST['field'] && isset($_REQUEST['sort']) && $_REQUEST['sort']) {
            $sorts[$_REQUEST['field']] = $_REQUEST['sort'];
            $reports->set('_sorts', $sorts);
        }
		
		
		/** Create Report Section **/
		if(isset($_REQUEST['reportID']) && is_numeric($_REQUEST['reportID']) && isset($_REQUEST['do']) && $_REQUEST['do']=='deleteReport'){	
			 if(mysql_query("DELETE FROM `Report` WHERE `Report`.`ReportID` = '".$_REQUEST['reportID']."'")){
				 
				header("location:?action=report");	 
			 }
				 
		}
		
		if(isset($_REQUEST['reportID']) && is_numeric($_REQUEST['reportID']) && isset($_REQUEST['do']) && $_REQUEST['do']=='reGenerate')
		{
			$reportResult = mysql_fetch_array(mysql_query("SELECT * FROM `Report` WHERE ReportID='".$_REQUEST['reportID']."'"));
			
			$this->reportGenerateForm(@$reportResult);
            die();
			
		}
		if(isset($_REQUEST['do']) && $_REQUEST['do']=='createReport')
		{
			$tempReport=''; 
			
			
			
			if (@$_REQUEST['name']) {
                $data['Template'] = '';
				$report = new Reports();				
				if (!empty($_REQUEST['copyFrom'])) {					
					$c=mysql_fetch_array(mysql_query("SELECT * FROM Report WHERE ReportID='".$_REQUEST['copyFrom']."'"));
					$tempReport = $c;
				}
				
				if ($tempReport!=null){
					
					$data['Template']=$tempReport['Template'];
					$data['LayoutType']=$tempReport['LayoutType'];
					$data['ReportCategoryID']=$tempReport['ReportCategoryID'];

					$margins = array(
						(float) $_REQUEST['topMargin'],
						(float) $_REQUEST['rightMargin'],
						(float) $_REQUEST['bottomMargin'],
						(float) $_REQUEST['leftMargin']
					);
					$data['Margins']=implode("|", $margins);
					if (!empty($_REQUEST['showHeader'])) {
						$data['ShowHeader']=1;
					} else {
						$data['ShowHeader']=0;
					}
					
				} else {
					$data['LayoutType']=$_REQUEST['layoutType'];
					$data['ReportCategoryID']=isset($_REQUEST['reportCategoryID']) ? $_REQUEST['reportCategoryID'] : 0;
					$margins = array(
						(float) $_REQUEST['topMargin'],
						(float) $_REQUEST['rightMargin'],
						(float) $_REQUEST['bottomMargin'],
						(float) $_REQUEST['leftMargin']
					);
					$data['Margins']=implode("|", $margins);
					if (!empty($_REQUEST['showHeader'])) {
						$data['ShowHeader']=1;
					} else {
						$data['ShowHeader']=0;
					}
					
				}
				
				$data['Name']=$_REQUEST['name'];
				$data['Created']=date("Y-m-d H:i:s");

				
				$sql ="insert into `Report` SET `Name`='".$data['Name']."',`ReportCategoryID`='".$data['ReportCategoryID']."',`LayoutType`='".$data['LayoutType']."',`Created`='".$data['Created']."',`Template`='".$data['Template']."',`ShowHeader`='".$data['ShowHeader']."',`Margins`='".$data['Margins']."'";
				$query=mysql_query($sql) or die(mysql_error());
				$reportLastId=mysql_insert_id();
				//header("location:?action=report");
				header("location:?action=report&do=reGenerate&reportID=".$reportLastId."");
			}
			die();
		
			
		} else if(isset($_REQUEST['do']) && $_REQUEST['do']=='editReport')
		{
			$tempReport=''; 
			
			
			
			if (@$_REQUEST['name']) {
                $data['Template'] = '';
				$report = new Reports();
			
                $data['LayoutType'] = $_REQUEST['layoutType'];
                $data['ReportCategoryID'] = isset($_REQUEST['reportCategoryID']) ? $_REQUEST['reportCategoryID'] : 0;
                $margins = array(
                    (float) $_REQUEST['topMargin'],
                    (float) $_REQUEST['rightMargin'],
                    (float) $_REQUEST['bottomMargin'],
                    (float) $_REQUEST['leftMargin']
                );
                $data['Margins']=implode("|", $margins);
                if (!empty($_REQUEST['showHeader'])) {
                    $data['ShowHeader'] = 1;
                } else {
                    $data['ShowHeader'] = 0;
                }
								
				$data['Name'] = $_REQUEST['name'];
                
				 $sql = "update `Report` SET Name='".$data['Name']."', `ReportCategoryID`='".$data['ReportCategoryID']."',  `LayoutType`='".$data['LayoutType']."', `ShowHeader`='".$data['ShowHeader']."', `Margins`='".$data['Margins']."' where ReportID=".$_REQUEST['id'];
				$query=mysql_query($sql) or die(mysql_error());
				$reportLastId = $_REQUEST['id'];
				header("location:?action=report&do=reGenerate&reportID=".$reportLastId."");
			}
			die();
		
			
		} else if($saveReport) {
			$this->saveReport($_POST['jsonData'],$_POST['reportID']);
            $reportLastId=mysql_insert_id();
            header("location:?action=report&do=reGenerate&reportID=".$reportLastId."");
            die();
		}
		/******** End Report section**********/
        $totalApplicants = $reports->getCount();
        $message = isset($_REQUEST['message']) ? $_REQUEST['message'] : '';
     
		
        $this->displayReportsList($reports->getList(), $totalApplicants, $page, $filters, $sorts, $message);
       
    }

    public function displayReportsList($reports, $totalApplicants, $page, $filters, $sorts, $message)
    {
        include(VIEW_PATH . '/adminnav.phtml');
		$reports1= new Reports();
		$reportCategories=$reports1->AllReportCategory();		
		$reportsAll=$reports1->AllReport();		
        
        $numApplicants = count($reports);
        $numPages = ceil($totalApplicants / 20);
        $pageNumber = $page;
        $firstRecord = (($pageNumber -1) * 20) + 1;
        $lastRecord = $firstRecord + $numApplicants - 1;
        $field = '';
        if (isset($_REQUEST['field']) && $_REQUEST['field'] && isset($_REQUEST['sort']) && $_REQUEST['sort']) {
            $sort = $_REQUEST['sort'];
            $field = $_REQUEST['field'];
            $sorts[$field] =     $sort;
            $_SESSION['sorts'] = $sorts; // store sorts
            //$users->set('_sorts', $sorts);
        }

        ob_start();
        include(VIEW_PATH . '/report.phtml');
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


    public function reportGenerateForm($report)
    {
    	$reportResult = $report;
        
        $reports1= new Reports();
		$reportCategories=$reports1->AllReportCategory();		
		$reportsAll=$reports1->AllReport();	
        
        include(VIEW_PATH . '/adminnav.phtml');
		
            

		/*** ****/
		// create the array of the different field options available for this report
		
		$familyData = array(
            'id'                        => 'Family ID',
            'last_name_1'               => 'Last Name',
            'first_name_1'              => 'First Name',
            'relationship_1_id'         => 'Relationship',
            'last_name_2'               => 'Last Name 2',
            'first_name_2'              => 'First Name 2',
            'relationship_2_id'         => 'Relationship 2',
            'language_id'               => 'Language',
            'address'                   => 'Address',
            'city'                      => 'City',
            'state'                     => 'State',
            'zip'                       => 'Zip',
            'county'                    => 'County',
            'primary_phone'             => 'Primary Phone',
            'secondary_phone'           => 'Secondary Phone',
            'best_times'                => 'Best Times',
            'best_times_start'          => 'Best Times Start',
            'best_times_end'            => 'Best Times End',
            'best_times_days'           => 'Best Times Days',
            'email'                     => 'Email',
            'contact_phone'             => 'Contact Phone',
            'contact_email'             => 'Contact Email',
            'contact_text'              => 'Contact Text',
            'hmg_worker'                => 'HMG Worker',
            'cc_level'                  => 'CC Level',
            'status'                    => 'Status',
            'inquirer'                  => 'Inquirer',
            'enrollment_filed'          => 'Enrollment Filled',
            'enrollment_type'           => 'Enrollment Type',
            'date_permission_granted'   => 'Date Permission Granted',
            'asq_preference'            => 'ASQ Preference',
            'notes'                     => 'notes',
            'who_called_id'             => 'Who Called', 
            'family_heard_id'           => 'Family Heard',
            'call_reason_id'            => 'Reason For Call',
            'success_story'             => 'Success Story',
            'success_story_notes'       => 'Success Story Notes',
            'race_id'                   => 'Race',
            'ethnicity_id'              => 'Ethnicity',
            'health_insurance'          => 'Health Insurance',
            'health_insurance_notes'    => 'Health Insurance Notes',
            'send_follow_up'            => 'Follow Up', 
            'modified'                  => 'Last Modified',
            'point_of_entry'            => 'Point of Entry',
            'how_heard_category_id'     => 'How Heard Category',
            'how_heard_details_id'      => 'How Heard Details',
            'ecids_permission'          => 'Ecids Permission',
            'ecids_permission_granted'  => 'Ecids Permission Granted',
            'ecids_permission_revoked'  => 'Ecids Permission Revoked',
            'sharing_info'              => 'Sharing Permission',
            'sharing_permission_granted'=> 'Sharing Permission Granted',
            'sharing_permission_revoked'=> 'Sharing Permission Revoked',
            'formatted_start_date'      => 'Start Date',
            'formatted_end_date'        => 'End Date'
            
		);
        
        $orgData = array(
            'id'                        => 'ID',
            'organization_name'         => 'Organization Name',
            'site'                      => 'Site Name',
            'organization_type_id'      => 'Organization Type',
            'address'                   => 'Address',
            'city'                      => 'City',
            'state'                     => 'State',
            'zip'                       => 'Zip',
            'county'                    => 'County',
            'primary_phone'             => 'Primary Phone',
            'fax'                       => 'Fax',
            'mou'                       => 'MOU',
            'partnership_level_id'      => 'Partnership Level',
            'date_last_signed'          => 'Date Last Signed',
            'partnership_notes'         => 'Partnership Notes',
            'website'                   => 'Website',
            'region_id'                 => 'Region',
            'service_area'              => 'Service Area',
            'mode_of_contact_id'        => 'Mode of Contact',
            'resource_database_id'      => 'Resource Database ID',            
            'success_story'             => 'Success Story',
            'success_story_notes'       => 'Success Story Notes',
            'service_terms'             => 'Service Terms',
            'note'                      => 'Note',            
            'hmgworker'                 => 'HMG Worker',
            'start_date'                => 'Start Date',
            'end_date'                  => 'End Date',
            'modified'                  => 'Last Modified'
		);
        
        $eventsData = array(
            'event_id'                  => 'Event ID',
            'organization_name'     => 'Organization Name',
            'event_name'                => 'Event Name',
            'event_type_id'             => 'Event Type',
            'outreach_type_id'          => 'Outreach Type',
            'event_date'                => 'Event Date',
            'event_zipcode_id'          => 'Event Zipcode',
            'event_county_id'           => 'Event County',
            'event_contact_id'          => 'Event Contact',
            'hmgworker'                => 'HMG Worker',
            'event_venue'               => 'Event Venue',
            'no_of_people'              => 'No Of People',
            'no_of_families'            => 'No Of Families',
            'event_duration'            => 'Event Duration',
            'no_of_staff'               => 'No Of Staff',
            'no_of_volunteers'          => 'No of Volunteers',
            'no_of_asq_completed'       => 'No Of ASQ Completed',
            'enrollment_forms'          => 'Enrollment Forms',
            'pitches'                   => 'Pitches',
            'quality_of_interactions'   => 'Quality of Interactions',
            'time_of_day'               => 'Time Of Day',
            'attachments'               => 'Attachments',
            'event_notes'               => 'Event Notes',
            'date_added'                => 'Date Added',
            'last_modified'             => 'Last Modified'
		);

		switch($report['ReportCategoryID']) {
			case 1:				
				$fieldOptions = array(
					"form" => array(
						"Families" => $familyData
					),
					"grid" => array(
						"Families" => $familyData
					)
				);
				break;
            case 2:				
				$fieldOptions = array(
					"form" => array(
						"Organizations" => $orgData
					),
					"grid" => array(
						"Organizations" => $orgData
					)
				);
				break;
            case 3:				
				$fieldOptions = array(
					"form" => array(
						"Events" => $eventsData
					),
					"grid" => array(
						"Events" => $eventsData
					)
				);
				break;
			default:
				$fieldOptions = array(
					"form" => array(),
					"grid" => array()
				);
				break;
		}

		if (!empty($_REQUEST['showBuilder']) || $report['Template'] == "")
			$showBuilder = true;
		else
			$showBuilder = false;

		/*** ***/
		
        ob_start();
        include(VIEW_PATH . '/generate_Report.phtml');
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

	public function saveReport($data,$reportID) {
		$reports= new Reports();
		//save data to table
		if($reports->saveReportData($data,$reportID)) {
			echo json_encode(array( 'status' => true ));
		} else {
			echo json_encode(array( 'status' => false ));
		}
		exit();
	}

}
