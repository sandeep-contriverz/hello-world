<?php
	
	require('../../vendor/autoload.php');
	include_once "../../config/default.php";


	global $conn,$token,$result_body_care,$result_body,$result_body_screening,$date,$rfile,$wfile;
	session_start();

	$password = $pass;

	$token = $token;
	
	/**
	* connect to database host server
	*/
	$conn = mysql_connect($host,$user,$password); 
	if( !$conn ) {
		die('Could not connect: ' . mysql_error()); 
	}
	
	/** 
	* Choose the database
	*/
	mysql_select_db( $database );
	
	/**
	* Monitoring array for the ASQ:SE-2
	*/
	$asqse2 = array(
		'2'  => array(25,30),
		'6'  => array(30,45),
		'12' => array(40,50),
		'18' => array(50,65),
		'24' => array(50,65),
		'30' => array(65,85),
		'36' => array(75,105),
		'48' => array(70,85),
		'60' => array(70,95)
	);
	
	/**
	* Monitoring array for the ASQ3:Communication
	*/
	$asq3com = array(		
		'2'   => array(35.19,22.77),
		'4'   => array(43.44,34.60),
		'6'   => array(39.27,29.65),
		'8'   => array(42.73,33.06),
		'9'   => array(30.00,13.97),
		'10'  => array(35.52,22.87),
		'12'  => array(30.00,15.64),
		'14'  => array(31.63,17.40),
		'16'  => array(30.45,16.81),
		'18'  => array(30.00,13.06),
		'20'  => array(34.32,20.50),
		'22'  => array(30.00,13.04),
		'24'  => array(38.20,25.17),
		'27'  => array(37.22,24.02),
		'30'  => array(43.56,33.30),
		'33'  => array(37.37,25.36),
		'36'  => array(41.43,30.99),
		'42'  => array(38.54,27.06),
		'48'  => array(41.82,30.72),
		'54'  => array(42.82,31.85),
		'60'  => array(42.80,33.19),
	);
	
	/**
	* Monitoring array for the ASQ3:Gross Motor
	*/
	$asq3gross = array(		
		'2'   => array(48.58,41.84),
		'4'   => array(46.52,38.41),
		'6'   => array(33.95,22.25),
		'8'   => array(41.35,30.61),
		'9'   => array(32.27,17.82),
		'10'  => array(41.54,30.07),
		'12'  => array(35.71,21.49),
		'14'  => array(39.44,25.80),
		'16'  => array(47.11,37.91),
		'18'  => array(46.42,37.38),
		'20'  => array(47.85,39.89),
		'22'  => array(39.11,27.75),
		'24'  => array(46.40,38.07),
		'27'  => array(39.14,28.01),
		'30'  => array(44.84,36.14),
		'33'  => array(44.04,34.80),
		'36'  => array(45.84,36.99),
		'42'  => array(45.15,36.27),
		'48'  => array(42.74,32.78),
		'54'  => array(44.58,35.18),
		'60'  => array(41.72,31.28),
	);
	
	/**
	* Monitoring array for the ASQ3:Fine Motor
	*/
	$asq3fine = array(		
		'2'   => array(39.98,30.16),
		'4'   => array(40.60,29.62),
		'6'   => array(37.04,25.14),
		'8'   => array(47.95,40.15),
		'9'   => array(41.82,31.32),
		'10'  => array(46.36,37.97),
		'12'  => array(43.36,34.50),
		'14'  => array(34.97,23.06),
		'16'  => array(41.97,31.98),
		'18'  => array(43.38,34.32),
		'20'  => array(44.39,36.05),
		'22'  => array(39.09,29.61),
		'24'  => array(43.43,35.16),
		'27'  => array(31.08,18.42),
		'30'  => array(33.02,19.25),
		'33'  => array(27.90,12.28),
		'36'  => array(32.57,18.07),
		'42'  => array(33.68,19.82),
		'48'  => array(30.58,15.81),
		'54'  => array(31.72,17.32),
		'60'  => array(39.05,26.54),
	);
	
	/**
	* Monitoring array for the ASQ3:Problem Solving
	*/
	$asq3problem = array(		
		'2'   => array(36.55,24.62),
		'4'   => array(44.38,34.98),
		'6'   => array(39.06,27.72),
		'8'   => array(45.05,36.17),
		'9'   => array(39.11,28.72),
		'10'  => array(42.35,32.51),
		'12'  => array(38.16,27.32),
		'14'  => array(34.82,22.56),
		'16'  => array(40.95,30.51),
		'18'  => array(35.86,25.74),
		'20'  => array(38.54,28.84),
		'22'  => array(39.16,29.30),
		'24'  => array(39.59,29.78),
		'27'  => array(38.79,27.62),
		'30'  => array(38.63,27.08),
		'33'  => array(38.78,26.92),
		'36'  => array(41.13,30.29),
		'42'  => array(39.82,28.11),
		'48'  => array(42.04,31.30),
		'54'  => array(39.68,28.12),
		'60'  => array(41.29,29.99),
	);
	
		/**
	* Monitoring array for the ASQ3:Personal Staff
	*/
	$asq3personal = array(		
		'2'   => array(42.14,33.71),
		'4'   => array(42.54,33.16),
		'6'   => array(36.83,25.34),
		'8'   => array(44.60,35.84),
		'9'   => array(30.69,18.91),
		'10'  => array(38.37,27.25),
		'12'  => array(33.73,21.73),
		'14'  => array(35.76,23.18),
		'16'  => array(37.22,26.43),
		'18'  => array(37.55,27.19),
		'20'  => array(42.70,33.36),
		'22'  => array(40.31,30.07),
		'24'  => array(41.34,31.54),
		'27'  => array(36.11,25.31),
		'30'  => array(41.94,32.01),
		'33'  => array(39.85,28.96),
		'36'  => array(44.07,35.33),
		'42'  => array(41.25,31.12),
		'48'  => array(38.47,26.60),
		'54'  => array(42.55,32.33),
		'60'  => array(46.96,39.07)
	); 
	
	/**
	* API token Value
	*/
	
	
	
	$result_body_care = "<p>CAREGIVERS RECORDS PROCESSED ARE</p><table border='1'><tr><td>SNO</td><td>ASQ NO</td><td>NAME</td><td>NEW RECORD</td><td>RESULT</td></tr>#BODYELEMENTS#</table>";
	
	$result_body = "<p>CHILD RECORDS PROCESSED ARE</p><table border='1'><tr><td>SNO</td><td>ASQ NO</td><td>NAME</td><td>NEW RECORD</td><td>RESULT</td></tr>#BODYELEMENTS#</table>";
	
	$result_body_screening = "<p>SCREENING RECORDS PROCESSED ARE</p><table border='1'><tr><td>SNO</td><td>CHILD</td><td>ASQ SCREENING</td><td>TYPE</td><td>RESULT</td></tr>#BODYELEMENTS#</table>";
