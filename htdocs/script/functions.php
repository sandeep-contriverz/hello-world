<?php
	
	function formatPhoneNumber($phoneNumber) {
		
		//if( gettype($phoneNumber) == 'integer'){
		$phoneNumber = str_replace('(','',$phoneNumber);
		$phoneNumber = str_replace(')','',$phoneNumber);
		$phoneNumber = str_replace(' ','',$phoneNumber);
		$phoneNumber = str_replace('-','',$phoneNumber);
			if(strlen($phoneNumber) == 10) {
			
				$areaCode = substr($phoneNumber, 0, 3);
				$nextThree = substr($phoneNumber, 3, 3);
				$lastFour = substr($phoneNumber, 6, 4);

				$phoneNumber = ''.$areaCode.'-'.$nextThree.'-'.$lastFour;
			}
			else if(strlen($phoneNumber) == 7) {
			
				$nextThree = substr($phoneNumber, 0, 3);
				$lastFour = substr($phoneNumber, 3, 4);

				$phoneNumber = $nextThree.'-'.$lastFour;
			}
		//}

		return $phoneNumber;
	}

	function get_asq($url){
		global $token;
		$ch = curl_init();  
		
		$data = array('X-Auth-Token:'.$token);
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);		
		curl_setopt($ch, CURLOPT_HTTPHEADER,$data);
		$result = curl_exec ($ch);			
		curl_close ($ch); 		
		return json_decode($result, true);
	}
	
	function put_asq($url, $update_array){
		global $token;
		$time=10;
		$ch = curl_init();                   
		$data = array('X-Auth-Token:'.$token);
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($update_array));		
		curl_setopt($ch, CURLOPT_HTTPHEADER,$data);
		$result = curl_exec($ch);
		curl_close ($ch); 
		
		return json_decode($result, true);		
	}
