<?php
	echo "<pre>";
	date_default_timezone_set('America/Chicago');
	
	include "config.php";
	global $token;
	if(isset($_GET['token']) && !empty($_GET['token'])){
		$token = $_GET['token'];
	}	
	$states = array(
		'Alabama'=>'AL',
		'Alaska'=>'AK',
		'Arizona'=>'AZ',
		'Arkansas'=>'AR',
		'California'=>'CA',
		'Colorado'=>'CO',
		'Connecticut'=>'CT',
		'Delaware'=>'DE',
		'Florida'=>'FL',
		'Georgia'=>'GA',
		'Hawaii'=>'HI',
		'Idaho'=>'ID',
		'Illinois'=>'IL',
		'Indiana'=>'IN',
		'Iowa'=>'IA',
		'Kansas'=>'KS',
		'Kentucky'=>'KY',
		'Louisiana'=>'LA',
		'Maine'=>'ME',
		'Maryland'=>'MD',
		'Massachusetts'=>'MA',
		'Michigan'=>'MI',
		'Minnesota'=>'MN',
		'Mississippi'=>'MS',
		'Missouri'=>'MO',
		'Montana'=>'MT',
		'Nebraska'=>'NE',
		'Nevada'=>'NV',
		'New Hampshire'=>'NH',
		'New Jersey'=>'NJ',
		'New Mexico'=>'NM',
		'New York'=>'NY',
		'North Carolina'=>'NC',
		'North Dakota'=>'ND',
		'Ohio'=>'OH',
		'Oklahoma'=>'OK',
		'Oregon'=>'OR',
		'Pennsylvania'=>'PA',
		'Rhode Island'=>'RI',
		'South Carolina'=>'SC',
		'South Dakota'=>'SD',
		'Tennessee'=>'TN',
		'Texas'=>'TX',
		'Utah'=>'UT',
		'Vermont'=>'VT',
		'Virginia'=>'VA',
		'Washington'=>'WA',
		'West Virginia'=>'WV',
		'Wisconsin'=>'WI',
		'Wyoming'=>'WY'
	);
	if(isset($_GET['region']) && !empty($_GET['region'])){
		echo "<b>".$_GET['region'].'</b>';
	}
	 
	include "functions.php";
	
	$_SESSION['caregivers'] = array();
	$_SESSION['childs']     = array();
	
	/**
	* Checking the date. If passed as parameter then it will take the parameter date else take the lass sync completed date
	*/
	if(isset($_GET['date']) && !empty($_GET['date'])){
		$date = $_GET['date'];
	}
	else{
		$rfile = fopen("time.txt", "r");
		$date = fgets($rfile);
		fclose($rfile);
	}
	
	echo "<p>LAST UPDATED ON ".$date."</p>";
		/**
	* Caregiver Processing to HMG Family database starts here
	*/
	//$result_body_care = str_replace("#DATE#",$date,$result_body_care);
	$message_body = '';
	$counter_records = 0;
	
	//Fetching list of all caregiver profiles since the last date
	$data = get_asq('https://www.asqonline.com/api/caregiver_profiles/?since='.$date);	
	
	if(isset($data['caregiver_profiles']) && is_array($data['caregiver_profiles']))
	{
		$total_pages = $data['total_pages'];
		
		$follow_up_task_id = mysql_fetch_array(mysql_query( "select id from settings where type='follow_up_task' and name='Intake'"));
							
		if(!is_array($follow_up_task_id) || empty($follow_up_task_id)){
			$follow_up_task_id = array('0');
		}
		
		$referred_to = mysql_fetch_array(mysql_query( "select id from settings where type='referred_to' and name='Interoffice'"));
							
		if(!is_array($referred_to) || empty($referred_to)){
			$referred_to = array('0');
		}
		
		$referred_to_service = mysql_fetch_array(mysql_query( "select id from settings where type='referred_to_service' and name='Note'"));
							
		if(!is_array($referred_to_service) || empty($referred_to_service)){
			$referred_to_service = array('0');
		}
							
		for($loop=1;$loop<=$total_pages;$loop++)
		{
			$data = get_asq('https://www.asqonline.com/api/caregiver_profiles/?since='.$date.'&page='.$loop);	
	
			if(isset($data['caregiver_profiles']) && is_array($data['caregiver_profiles']))
			{		 
				foreach($data['caregiver_profiles'] as $item)
				{
					//fetching Caregiver details
					$data_caregiver = get_asq('https://www.asqonline.com/api/caregiver_profiles/'.$item['id']);
					
					if(isset($data_caregiver['caregiver_profile']) && is_array($data_caregiver['caregiver_profile']))
					{
						//assigning the data to a variable
						$user_data = $data_caregiver['caregiver_profile'];
						
						//saving the caregiver details to the session
						$_SESSION['caregivers'][$item['id']] = $data_caregiver['caregiver_profile'];
						
						//checking if caregiver is a new record or not							 			
						if(strtolower($user_data['custom_fields']['New Record']) == 'yes'){
							
							//fetching the relationship and language id from the DB
							$relationship_id = mysql_fetch_array(mysql_query( "select id from settings where type='relationships' and name='".$user_data['relationship_to_child']."'"));
							
							if(!is_array($relationship_id) || empty($relationship_id)){
								$relationship_id = array('0');
							}
							
							//fetching the county code from zipcode
							$county = '';
							$counties = mysql_fetch_array(mysql_query( "SELECT settings.name as county FROM county_zipcodes left join settings on settings.id=county_zipcodes.county_id where county_zipcodes.zip_code='".$user_data['zip']."'"));
							if(!is_array($counties) || empty($counties)){
								$county = addslashes($user_data['county']);
							}
							else{
								$county = addslashes($counties[0]);
							}
							
							$state = ( isset( $states[$user_data['state']] )?$states[$user_data['state']]:'' );
							
							$hmg_users = mysql_fetch_array(mysql_query( "SELECT id FROM users where hmg_worker = 'API User' "));
							if(!is_array($hmg_users) || empty($hmg_users)){
								$hmgs = 0;
							}
							else{
								$hmgs = $hmg_users[0];
							}
									
							//adding the value to the family table
							$query = "INSERT INTO families (hmg_worker,last_name_1,first_name_1,relationship_1_id,address,city,state,zip,county,primary_phone,secondary_phone,email,status,cc_level,success_story) VALUES ('".$hmgs."','".addslashes($user_data['last_name'])."','".addslashes($user_data['first_name'])."',".$relationship_id[0].",'".addslashes($user_data['address1'] .' '.$user_data['address2'])."','".addslashes($user_data['city'])."','".$state."','".$user_data['zip']."','".$county."','".formatPhoneNumber( $user_data['phone'] )."','".formatPhoneNumber( $user_data['alternate_phone'] )."','".$user_data['email']."','Open Inquiry','Level 1','No')";

							$retval = mysql_query( $query, $conn ) or die(mysql_error());
							$user_data['alt_family_id'] = mysql_fetch_array(mysql_query( "select LAST_INSERT_ID()"));
							
							//additional entry in family_follow_up table
							$query = "INSERT INTO family_follow_up (hmg_worker,family_id,referral_date,follow_up_date,referred_to_id,service_id,notes,follow_up_task_id) VALUES ('".$hmgs."','".$user_data['alt_family_id'][0]."','".date('Y-m-d')."','".date('Y-m-d')."','".$referred_to[0]."','".$referred_to_service[0]."','Give ASQ results for child.','".$follow_up_task_id[0]."')"; 
							$retval = mysql_query( $query, $conn ) or die(mysql_error());
							
							//additional entry in startend table
							$query = "INSERT INTO startend (parent_id,start_date,end_date) VALUES ('".$user_data['alt_family_id'][0]."','".date('Y-m-d')."','0000-00-00')"; 
							$retval = mysql_query( $query, $conn ) or die(mysql_error());
							
							//String for printing the result on the browser
							$message_body.="<tr><td>".(++$counter_records)."</td><td>".$item['id']."</td><td>".$user_data['first_name']." ".$user_data['last_name']."</td><td>YES</td><td>Added to database with id ".$user_data['alt_family_id'][0]."</td></tr>";
							
							//array to update the caregiver with ID back to ASQ 					 
							$update_array = array(
								"caregiver_profile" => array(
									'id' => $item['id'],    
									'custom_fields' => array
									(
										'Alt. Family ID' => $user_data['alt_family_id'][0],
										'New Record' => 'false'
									)
								)
							);	
							
							$_SESSION['caregivers'][$item['id']]['custom_fields'] = array
									(
										'Alt. Family ID' => $user_data['alt_family_id'][0],
										'New Record' => 'false'
									);
							
							$data_care = put_asq('https://www.asqonline.com/api/caregiver_profiles/'.$item['id'],$update_array);
						}
						else{
						
							//String for printing the result on the browser
							//$message_body.="<tr><td>".(++$counter_records)."</td><td>".$item['id']."</td><td>".$user_data['first_name']." ".$user_data['last_name']."</td><td>NO</td><td></td></tr>";
						}
						
					}
				}					
			}			
		}		
	}
	else{
		//String for printing the result on the browser - CASE API doesn't return anything
		$message_body.="<tr><td colspan='5'>No Caregiver is updated since the last sync</td></tr>";
	}
		
	//printing the content to the browser
	echo str_replace("#BODYELEMENTS#",$message_body,$result_body_care);
	
	/**
	* Caregiver script ends over here
	*/
	
	//child follow up values
	$follow_up_task_id = mysql_fetch_array(mysql_query( "select id from settings where type='follow_up_task' and name='Give Screening Results'"));
							
	if(!is_array($follow_up_task_id) || empty($follow_up_task_id)){
		$follow_up_task_id = array('0');
	}
	
	$referred_to = mysql_fetch_array(mysql_query( "select id from settings where type='referred_to' and name='ASQ-3'"));
						
	if(!is_array($referred_to) || empty($referred_to)){
		$referred_to = array('0');
	}
	
	$referred_to_se = mysql_fetch_array(mysql_query( "select id from settings where type='referred_to' and name='ASQ:SE'"));
						
	if(!is_array($referred_to_se) || empty($referred_to_se)){
		$referred_to_se = array('0');
	}
	
	/**
	* CHILD Processing to HMG child database starts here
	*/
	//$result_body = str_replace("#DATE#",$date,$result_body);
	$message_body = '';
	$counter_records = 0;
	//Fetching list of all child profiles since the last date
	$data = get_asq('https://www.asqonline.com/api/child_profiles/?since='.$date);	
	
	if(isset($data['child_profiles']) && is_array($data['child_profiles']))
	{
		$total_pages = $data['total_pages'];
		
		for($loop=1;$loop<=$total_pages;$loop++)
		{
			$data = get_asq('https://www.asqonline.com/api/child_profiles/?since='.$date.'&page='.$loop);	
	
			if(isset($data['child_profiles']) && is_array($data['child_profiles']))
			{		 
				foreach($data['child_profiles'] as $item)
				{
					//fetching child details
					$data_child = get_asq('https://www.asqonline.com/api/child_profiles/'.$item['id']);
					
					if(isset($data_child['child_profile']) && is_array($data_child['child_profile']))
					{
						$user_data  = $data_child['child_profile'];
						$_SESSION['childs'][$item['id']] = $data_child['child_profile'];
						$user_links = $data_child['_links'];
						
						//checking if user_record is a new record or not
						if(strtolower($user_data['custom_fields']['New Record']) == 'yes'){
							
							//fetching the caregiver parent id first
							//if(isset($user_links['primary_caregiver_profile']) && !empty($user_links['primary_caregiver_profile'])){
								$list_caregiver = get_asq('https://www.asqonline.com'.$user_links['caregiver_profiles']['href']);
								$parent_id = 0;
								$intake = '';
								foreach($list_caregiver['caregiver_profiles'] as $caregivers){
									if(isset($_SESSION['caregivers'][$caregivers['id']])){
										$intake = 'Do Intake';
										$data_caregiver = array();
										$data_caregiver['caregiver_profile'] = $_SESSION['caregivers'][$caregivers['id']];
									}
									else{
										$intake = '';
										$data_caregiver = get_asq('https://www.asqonline.com/api/caregiver_profiles/'.$caregivers['id']);
									}
									$parent_id = $data_caregiver['caregiver_profile']['custom_fields']['Alt. Family ID'];							
									
									if($parent_id){
										break;
									}									
								}
								
								if($parent_id){	
								
									//adding the value to the child table
									$query ="INSERT INTO children (parent_id,first,last,birth_date,gender,early) VALUES (".$parent_id.",'".addslashes($user_data['first_name'])."','".addslashes($user_data['last_name'])."','".$user_data['dob']."','".substr($user_data['gender'],0,1)."','".$user_data['weeks_premature']."')";

									$retval = mysql_query( $query, $conn ) or die(mysql_error());
									$user_data['alt_family_id'] = mysql_fetch_array(mysql_query( "select LAST_INSERT_ID()"));
									
									$hmg_workers = mysql_fetch_array(mysql_query( "SELECT hmg_worker FROM families where id = '".$parent_id."' "));
									if(!is_array($hmg_workers) || empty($hmg_workers)){
										$hmg_worker = 0;
									}
									else{
										$hmg_worker = $hmg_workers[0];
									}
																	
									//updating the child back to the ASQ					 
									$update_array = array(
										"child_profile" => array(
											'id' => $item['id'],
											'alternate_id'=> $user_data['alt_family_id'][0],    
											'custom_fields' => array
											(										
												'New Record' => 'false'
											)
										)
									);					
									$_SESSION['caregivers'][$item['id']]['alternate_id'] = $user_data['alt_family_id'][0];
									$_SESSION['caregivers'][$item['id']]['custom_fields'] = array
											(										
												'New Record' => 'false'
											);
									$data_care = put_asq('https://www.asqonline.com/api/child_profiles/'.$item['id'],$update_array);
									
										//adding all the screenings of the new child
									$data_scr = get_asq('https://www.asqonline.com'.$user_links['screenings']['href']);
									$childScreensData = '';				
									
									if(isset($data_scr['screenings']) && is_array($data_scr['screenings']))
									{
										$total_pages_scr = $data['total_pages'];
										
										for($loop_scr=1;$loop_scr<=$total_pages_scr;$loop_scr++)
										{
											$data_scr = get_asq('https://www.asqonline.com'.$user_links['screenings']['href']);	
									
											if(isset($data_scr['screenings']) && is_array($data_scr['screenings']))
											{		 
												foreach($data_scr['screenings'] as $item_scr)
												{
													//fetching child details
													$data_child_scr = get_asq('https://www.asqonline.com/api/screenings/'.$item_scr['id']);
													
													if(isset($data_child_scr['screening']) && is_array($data_child_scr['screening']))
													{
														$screen_scr  = $data_child_scr['screening'];
														$child_data_scr = $data_child_scr['screening']['child'];
														$care_data_scr = $data_child_scr['screening']['caregiver'];
														//print_r($data_child_scr);
														//checking if user_record is a new record or not
															
															$ages_scr = explode(' ',$screen_scr['age_interval']);							
															$dupli_query = mysql_query("select * from child_developmental_screenings where asq_screening_id=".$item_scr['id']) or die(mysql_error());
															$dupli_data = mysql_fetch_array($dupli_query);
															//print_r($dupli_data);
															if(empty($dupli_data)){
																//echo "dfdfgd";
																//echo $screen_scr['questionnaire'];
																//case asq-3
																if($screen_scr['questionnaire'] =='ASQ-3'){
																	$overall = $data_child_scr['screening']['responses']['overall'];
																	$notes = '';
																	foreach($overall as $ove){
																		if(!empty($ove['comment'])){
																			$notes.= $ove['comment'].';';
																		}
																	}
																	
																	$score_scr = 'White';																
																	
																	$scoresArray = array(
																		'communication'   => 'asq3com',
																		'gross_motor'     => 'asq3gross',
																		'fine_motor'      => 'asq3fine',
																		'problem_solving' => 'asq3problem',
																		'personal_social' => 'asq3personal'
																	);
																	
																	foreach($scoresArray as $scokey => $scovalue){
																	
																		$scoring = $$scovalue;
																		$var = $scokey.'_scr';
																		if( $screen_scr['scores'][$scokey]['score'] >= $scoring[$ages_scr[0]][1] )
																		{
																			if( 
																				$screen_scr['scores'][$scokey]['score'] < $scoring[$ages_scr[0]][0] 
																				&& $screen_scr['scores'][$scokey]['score'] >= $scoring[$ages_scr[0]][1]
																			)
																			{
																				$$var = 'Grey';
																			}
																			else{
																				$$var = 'White';
																			}
																		}
																		else{
																			$$var = 'Black';
																		}
																	}
																	
																	if($communication_scr == 'Black' || $gross_motor_scr == 'Black' || $fine_motor_scr == 'Black' || $problem_solving_scr == 'Black' || $personal_social_scr == 'Black'){
																		$score_scr = 'Black';
																	}
																	else if($communication_scr == 'Grey' || $gross_motor_scr == 'Grey' || $fine_motor_scr == 'Grey' || $problem_solving_scr == 'Grey' || $personal_social_scr == 'Grey'){
																		$score_scr = 'Grey';
																	}
																	else if($communication_scr == 'White' || $gross_motor_scr == 'White' || $fine_motor_scr == 'White' || $problem_solving_scr == 'White' || $personal_social_scr == 'White'){
																		$score_scr = 'White';
																	}
																	
																	$query_scr ="INSERT INTO child_developmental_screenings (asq_screening_id,child_id,type,asq_month,date_sent,score,communication,fine_motor,gross_motor,problem_solving,personal_social,notes) VALUES (".$item_scr['id'].",".$child_data_scr['alternate_id'].",'ASQ-3','".$ages_scr[0]."','".$screen_scr['completed']."','".$score_scr."','".$communication_scr."','".$fine_motor_scr."','".$gross_motor_scr."','".$problem_solving_scr."','".$personal_social_scr."','".addslashes($notes)."')";
																	$childScreensData.=$item['id']."(ASQ-3)</br>";
																	$ageing_interval = mysql_query( "SELECT id FROM settings where name =  '".$screen_scr['age_interval']."' ") or die(mysql_error());
																	$services = mysql_fetch_array($ageing_interval);
																	if(!is_array($services) || empty($services)){
																		$service = 0;
																	}
																	else{
																		$service = $services[0];
																	}
																	
																	//additional entry in child_follow_up table
																	$query_follow = "INSERT INTO child_follow_up (hmg_worker,child_id,referral_date,follow_up_date,referred_to_id,service_id,notes,follow_up_task_id) VALUES ('".$hmg_worker."','".$user_data['alt_family_id'][0]."','".date('Y-m-d')."','".date('Y-m-d')."','".$referred_to[0]."','".$service."','".$intake."','".$follow_up_task_id[0]."')"; 
																	mysql_query( $query_follow, $conn ) or die(mysql_error());
																	
																}
																//case asq-se2
																if($screen_scr['questionnaire'] =='ASQ:SE-2'){
																	
																	if($screen_scr['score'] >= $asqse2[$ages_scr[0]][0])
																	{
																		if( 
																			$screen_scr['score'] < $asqse2[$ages_scr[0]][1] 
																			&& $screen_scr['score'] >= $asqse2[$ages_scr[0]][0]
																		)
																		{
																			$asq_se_score_scr = 'Grey';
																		}
																		else{
																			$asq_se_score_scr = 'Black';
																		}
																	}
																	else{
																		$asq_se_score_scr = 'White';
																	}
																	
																	$query_scr ="INSERT INTO child_developmental_screenings (asq_screening_id,child_id,type,asq_month,date_sent,score,asq_se_score,notes) VALUES (".$item_scr['id'].",".$child_data_scr['alternate_id'].",'ASQ:SE','".$ages_scr[0]."','".$screen_scr['completed']."','".$asq_se_score_scr."','".$screen_scr['score']."','".addslashes($screen_scr['notes'])."')";
																	$childScreensData.=$item_scr['id']."(ASQ:SE-2)</br>";
																	
																	$services = mysql_fetch_array(mysql_query( "SELECT id FROM settings where name = '".$screen_scr['age_interval']."' "));
																	if(!is_array($services) || empty($services)){
																		$service = 0;
																	}
																	else{
																		$service = $services[0];
																	}
																	
																	//additional entry in child_follow_up table
																	$query_follow = "INSERT INTO child_follow_up (hmg_worker,child_id,referral_date,follow_up_date,referred_to_id,service_id,notes,follow_up_task_id) VALUES ('".$hmg_worker."','".$user_data['alt_family_id'][0]."','".date('Y-m-d')."','".date('Y-m-d')."','".$referred_to_se[0]."','".$service."','".$intake."','".$follow_up_task_id[0]."')"; 
																	mysql_query( $query_follow, $conn ) or die(mysql_error());
																}
																
																//adding the value to the child table							
																$retval_scr = mysql_query( $query_scr, $conn )  or die(mysql_error());
															}
															
														
													}
												}					
											}			
										}		
									}							
									//String for printing the result on the browser
									$message_body.="<tr><td>".(++$counter_records)."</td><td>".$item['id']."</td><td>".$user_data['first_name']." ".$user_data['last_name']."</td><td>YES</td><td>Added to database with id ".$user_data['alt_family_id'][0];
									if(!empty($childScreensData)){
										$message_body.="</br>with following screenings </br>".$childScreensData;
									}
									$message_body.="</td></tr>";
									
								}
								else{
									$message_body.="<tr><td>".(++$counter_records)."</td><td>".$item['id']."</td><td>".$user_data['first_name']." ".$user_data['last_name']."</td><td>YES</td><td>CAREGIVER NOT EXIST IN HMG DB</td></tr>";
								}
							//}
						}
						else{
							//$message_body.="<tr><td>".(++$counter_records)."</td><td>".$item['id']."</td><td>".$user_data['first_name']." ".$user_data['last_name']."</td><td>NO</td><td></td></tr>";
						}
					}
				}					
			}			
		}		
	}
	else{
		//String for printing the result on the browser - CASE API doesn't return anything
		$message_body.="<tr><td colspan='5'>No Childs are updated since the last sync</td></tr>";
	}
		
	echo str_replace("#BODYELEMENTS#",$message_body,$result_body);
	/**
	* Child script ends over here
	*/
	
	
	/**
	* SCREENING Processing to HMG screening database starts here
	*/
	
	$message_body = '';
	$counter_records = 0;
	//Fetching list of all child profiles since the last date
	$data = get_asq('https://www.asqonline.com/api/screenings/?since='.$date);	
	
	if(isset($data['screenings']) && is_array($data['screenings']))
	{
		$total_pages = $data['total_pages'];
		
		for($loop=1;$loop<=$total_pages;$loop++)
		{
			$data = get_asq('https://www.asqonline.com/api/screenings/?since='.$date.'&page='.$loop);	
	
			if(isset($data['screenings']) && is_array($data['screenings']))
			{		 
				foreach($data['screenings'] as $item)
				{
					//fetching child details
					$data_child = get_asq('https://www.asqonline.com/api/screenings/'.$item['id']);
					
					if(isset($data_child['screening']) && is_array($data_child['screening']))
					{
						$screen  = $data_child['screening'];
						
						$child_data = $data_child['screening']['child'];
						$care_data = $data_child['screening']['caregiver'];
						
						//checking if user_record is a new record or not
						if( isset($child_data['alternate_id']) && !empty($child_data['alternate_id']) ){
							
							
							$ages = explode(' ',$screen['age_interval']);							
							
							$dupli_query = mysql_query("select * from child_developmental_screenings where asq_screening_id=".$item['id']);
							$dupli_data = mysql_fetch_array($dupli_query);
							if(empty($dupli_data)){
								
								//fetching the hmg worker from the family
								$hmg = mysql_fetch_array(mysql_query( "SELECT hmg_worker FROM families left join children on families.id=children.parent_id WHERE children.id= '".$child_data['alternate_id']."' "));
								if(!is_array($hmg) || empty($hmg)){
									$hmg_users = mysql_fetch_array(mysql_query( "SELECT id FROM users where hmg_worker = 'API User' "));
									if(!is_array($hmg_users) || empty($hmg_users)){
										$hmgs = 0;
									}
									else{
										$hmgs = $hmg_users[0];
									}									
								}
								else{
									$hmgs = $hmg[0];
								}
							
								//case asq-3
								if($screen['questionnaire'] =='ASQ-3'){
									$overall = $data_child['screening']['responses']['overall'];
									$notes = '';
									foreach($overall as $ove){
										if(!empty($ove['comment'])){
											$notes.= $ove['comment'].';';
										}
									}
									$score = 'White';
									$scoresArray = array(
										'communication'   => 'asq3com',
										'gross_motor'     => 'asq3gross',
										'fine_motor'      => 'asq3fine',
										'problem_solving' => 'asq3problem',
										'personal_social' => 'asq3personal'
									);
									
									foreach($scoresArray as $scokey => $scovalue){
									
										$scoring = $$scovalue;
										if( $screen['scores'][$scokey]['score'] >= $scoring[$ages[0]][1] )
										{
											if( 
												$screen['scores'][$scokey]['score'] < $scoring[$ages[0]][0] 
												&& $screen['scores'][$scokey]['score'] >= $scoring[$ages[0]][1]
											)
											{
												$$scokey = 'Grey';
											}
											else{
												$$scokey = 'White';
											}
										}
										else{
											$$scokey = 'Black';
										}
									}
									
																		
									if($communication == 'Black' || $gross_motor == 'Black' || $fine_motor == 'Black' || $problem_solving == 'Black' || $personal_social == 'Black'){
										$score = 'Black';
									}
									else if($communication == 'Grey' || $gross_motor == 'Grey' || $fine_motor == 'Grey' || $problem_solving == 'Grey' || $personal_social == 'Grey'){
										$score = 'Grey';
									}
									else if($communication == 'White' || $gross_motor == 'White' || $fine_motor == 'White' || $problem_solving == 'White' || $personal_social == 'White'){
										$score = 'White';
									}
									
									$query ="INSERT INTO child_developmental_screenings (asq_screening_id,child_id,type,asq_month,date_sent,score,communication,fine_motor,gross_motor,problem_solving,personal_social,notes) VALUES (".$item['id'].",".$child_data['alternate_id'].",'ASQ-3','".$ages[0]."','".$screen['completed']."','".$score."','".$communication."','".$fine_motor."','".$gross_motor."','".$problem_solving."','".$personal_social."','".addslashes($notes)."')";
									$message_body.="<tr><td>".(++$counter_records)."</td><td>".$child_data['alternate_id']."( ".$child_data['first_name']." ".$child_data['last_name']." )</td><td>".$item['id']."( ".$screen['age_interval']." )</td><td>ASQ:3</td><td>ADDED TO DB</td></tr>";
									
									$services = mysql_fetch_array(mysql_query( "SELECT id FROM settings where name = '".$screen['age_interval']."' "));
									if(!is_array($services) || empty($services)){
										$service = 0;
									}
									else{
										$service = $services[0];
									}
									//additional entry in child_follow_up table
									$query_follow = "INSERT INTO child_follow_up (hmg_worker,child_id,referral_date,follow_up_date,referred_to_id,service_id,notes,follow_up_task_id) VALUES ('".$hmgs."','".$child_data['alternate_id']."','".date('Y-m-d')."','".date('Y-m-d')."','".$referred_to[0]."','".$service."','','".$follow_up_task_id[0]."')"; 
									mysql_query( $query_follow, $conn ) or die(mysql_error());
									
								}
								//case asq-se2
								if($screen['questionnaire'] =='ASQ:SE-2'){
									
									if($screen['score'] >= $asqse2[$ages[0]][0])
									{
										if( 
											$screen['score'] >= $asqse2[$ages[0]][0] 
											&& $screen['score'] < $asqse2[$ages[0]][1]
										)
										{
											$asq_se_score = 'Grey';
										}
										else{
											$asq_se_score = 'Black';
										}
									}
									else{
										$asq_se_score = 'White'; 
									}
									
									$query ="INSERT INTO child_developmental_screenings (asq_screening_id,child_id,type,asq_month,date_sent,score,asq_se_score,notes) VALUES (".$item['id'].",".$child_data['alternate_id'].",'ASQ:SE','".$ages[0]."','".$screen['completed']."','".$asq_se_score."','".$screen['score']."','".addslashes($screen['notes'])."')";
									$message_body.="<tr><td>".(++$counter_records)."</td><td>".$child_data['alternate_id']."( ".$child_data['first_name']." ".$child_data['last_name']." )</td><td>".$item['id']."( ".$screen['age_interval']." )</td><td>ASQ:SE-2</td><td>ADDED TO DB</td></tr>";
									
									$services = mysql_fetch_array(mysql_query( "SELECT id FROM settings where name = '".$screen['age_interval']."' "));
									if(!is_array($services) || empty($services)){
										$service = 0;
									}
									else{
										$service = $services[0];
									}
									
									//additional entry in child_follow_up table
									$query_follow = "INSERT INTO child_follow_up (hmg_worker,child_id,referral_date,follow_up_date,referred_to_id,service_id,notes,follow_up_task_id) VALUES ('".$hmgs."','".$child_data['alternate_id']."','".date('Y-m-d')."','".date('Y-m-d')."','".$referred_to_se[0]."','".$service."','','".$follow_up_task_id[0]."')"; 
									mysql_query( $query_follow, $conn ) or die(mysql_error());
									
								}
																
								//adding the value to the child table							
								$retval = mysql_query( $query, $conn ) or die(mysql_error());
							
							}
							
						}
						else{
						}
					}
				}					
			}			
		}		
	}
	else{
		//String for printing the result on the browser - CASE API doesn't return anything
		$message_body.="<tr><td colspan='5'>No Screenings are updated since the last sync</td></tr>";
	}
		
	echo str_replace("#BODYELEMENTS#",$message_body,$result_body_screening);
	/**
	* Screening script ends over here
	*/
	
	//saving the time sync was last completed
	$wfile = fopen("time.txt", "w");
	$time = date("Y-m-d").'T'.date("H:i:s")."Z";
	//$time = date("Y-m-d");
	fwrite($wfile, $time);
	fclose($wfile);
	
	mysql_close($conn);
	echo "<br/>";
	die('DONE');