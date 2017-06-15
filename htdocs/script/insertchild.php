<?php

	echo "<pre>";
	error_reporting(E_ALL);
	ini_set("display_errors","on");
	include "config.php";
	include "functions.php";	
	
	if(isset($_GET['date']) && !empty($_GET['date'])){
		$date = $_GET['date'];
	}
	
	$result_body = str_replace("#DATE#",$date,$result_body);
	$message_body = '';
	$counter_records = 0;
	//Fetching list of all caregiver profiles since the last date
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
					//fetching Caregiver details
					$data_child = get_asq('https://www.asqonline.com/api/child_profiles/'.$item['id']);
					
					if(isset($data_child['child_profile']) && is_array($data_child['child_profile']))
					{
						$user_data  = $data_child['child_profile'];
						$user_links = $data_child['_links'];
						
						//checking if user_record is a new record or not
						if(strtolower($user_data['custom_fields']['New Record']) == 'yes'){
							
							//fetching the caregiver parent id first
							if(isset($user_links['primary_caregiver_profile']) && !empty($user_links['primary_caregiver_profile'])){
								
								$data_caregiver = get_asq('https://www.asqonline.com'.$user_links['primary_caregiver_profile']['href']);							
								$parent_id = $data_caregiver['caregiver_profile']['custom_fields']['Alt. Family ID'];
								if($parent_id){	
									//adding the value to the family table
									$query ="INSERT INTO children (parent_id,first,last,birth_date,gender,early) VALUES (".$parent_id.",'".$user_data['first_name']."','".$user_data['last_name']."','".$user_data['dob']."','".$user_data['gender']."','".$user_data['weeks_premature']."')";

									$retval = mysql_query( $query, $conn ) or die(mysql_error());
									$user_data['alt_family_id'] = mysql_fetch_array(mysql_query( "select LAST_INSERT_ID()"));
									
									//checking the childs 					 
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
									
										$message_body.="<tr><td>".(++$counter_records)."</td><td>".$item['id']."</td><td>".$user_data['first_name']." ".$user_data['last_name']."</td><td>YES</td><td><td>Added to database with id ".$user_data['alt_family_id'][0]."</td></tr>";
									
									$data_care = put_asq('https://www.asqonline.com/api/child_profiles/'.$item['id'],$update_array);
								}
								else{
									$message_body.="<tr><td>".(++$counter_records)."</td><td>".$item['id']."</td><td>".$user_data['first_name']." ".$user_data['last_name']."</td><td>NO</td><td></td></tr>";
								}
							}
						}
					}
				}					
			}			
		}		
	}
	else{
		echo "unable to fetch child records";
	}
	
	echo str_replace("#BODYELEMENTS#",$message_body,$result_body);
	mysql_close($conn);
	die('');