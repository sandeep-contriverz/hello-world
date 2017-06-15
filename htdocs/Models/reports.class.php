<?php

namespace Hmg\Models;

class Reports 
{
    private $_table = 'Report';
    private $_sorts = array('Name' => 'ASC');
    private $_filters = null;
    private $_start = 0;
    private $_limit = 20;
    private $_mysql_error = null;
	private $cities;
	private $workshopCodes;
	private $programs;
	private $cityCodes;

    public function __construct()
    {
		// populate possibility arrays		
    }

    public function set($key, $value)
    {
        $this->$key = $value;
    }

    public function get($key)
    {
        return $this->$key;
    }

    private function buildQuery($addLimit = true, $getNextRecord = false, $currentId = null)
    {
        $order_by = '';
        $filter_by = '';
        $having = '';
        if (is_array($this->_sorts)) {
            $order_by = 'ORDER BY ';
            $concat = false;
            foreach ($this->_sorts as $field => $dir) {
                $order_by .= ($concat ? ', ' : '') . mysql_real_escape_string($field) . ' ' . mysql_real_escape_string($dir);
                $concat = true;
            }
        }
		$sql = 'SELECT Report.*,ReportCategory.Name as Category from Report left join ReportCategory on Report.ReportCategoryID = ReportCategory.ReportCategoryID '
                . ($having ? $having : '')
                . $order_by;
                //echo $sql;
        $rs = mysql_query($sql);
        if ($addLimit) {
            //echo 'Start: ' . $this->_start . ' Limit: ' . $this->_limit;
            $sql .= (is_numeric($this->_start) && $this->_limit ? ' LIMIT ' . $this->_start . ', ' . $this->_limit : ($this->_limit ? ' LIMIT 0, 20' : ''));
        }
        //echo $sql;
        return $sql;
    }

    public function getList()
    {
        $sql = $this->buildQuery();
        $rs = mysql_query( $sql ) or die(mysql_error() . $sql);
        if ($rs) {
            while ( $row = mysql_fetch_array( $rs, MYSQL_ASSOC ) ) {			
				
                $rows[] = $row;
            }
            if ( isset( $rows ) ) {
                return $rows;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getCount()
    {
        $sql = $this->buildQuery(false);
        $rs = mysql_query($sql) or die(mysql_error() . $sql);
        return mysql_num_rows($rs);
    }
	public function createCode($programID = null, $workshopID = null, $workshopNum = null, $numKids = null, $city = null, $status = null) {	
		$code = substr(date('Y'), -1);
		if ($status == "Accepted") { 
			$programKey = array_search($programID, $this->programs);
			if ($programKey === false)
				$code .= "o";
			else
				$code .= $programKey;
		} else if ($status == "Denied")
			$code .= "d";
		else
			$code .= "p";
			
		$workshopKey = array_search($workshopID, $this->workshopCodes);
		if ($workshopKey === false)
			$code .= "*";
		else
			$code .= $workshopKey;
			
		if (empty($workshopNum))
			$code .= "-*-";
		else
			$code .= "-".$workshopNum."-";
			
		if (empty($numKids))
			$code .= "0";
		else
			$code .= $numKids;
			
		$cityKey = array_search($city, $this->cityCodes);
		if ($cityKey === false)
			$code .= "*";
		else
			$code .= $cityKey;
			
		return strtoupper($code);
	}
	
	public function createCodeFromApplicant($applicantID, $workshopID, $workshopNum, $numKids, $city) {
		$year = parent::getSettingCurrentyear();
		$currentYearApplication = mysql_fetch_array(mysql_query("select * from Application where ApplicantID=".$applicantID." and Year='".$year."'"));
		$numKids = mysql_num_rows(mysql_query("select * from Child where ApplicantID=".$applicantID.""));
		
		if ($currentYearApplication != null) {
			return $this->createCode($currentYearApplication['ProgramID'], $workshopID, $workshopNum, $numKids, $city, $currentYearApplication['Status']);
		}
	}
	
	public function parseCode($code) {
		$code = strtolower($code);
		
		$parsedCode = array(
			'program' => "All",
			'status' => "All",
			'workshopID' => "All",
			'workshopNumber' => "All",
			'kids' => "All",
			'city' => "All"
		);
		
//		print_r($code);
//		print_r($this->programs);
//		print_r($this->workshopCodes);
//		die('end');

		
		$foundCity = false;
		// see if the last 2 characters in the code are a city
		/*$possibleCityCode = substr($code, -2);
		$foundCity = false;
		if (array_key_exists($possibleCityCode, $this->cityCodes)) {
			$parsedCode['city'] = $this->cityCodes[$possibleCityCode];
			$foundCity = true;
			$code = substr($code, 0, strlen($code)-2); // take the city out of the code
		}
		*/
		$foundKids = false;
		$foundWorkshopNumber = false;
		$foundWorkshop = false;
		$foundProgram = false;
		if ($foundCity) { // check for kids before the city
			$matches = array();
			if (substr($code, -1) == "*") {
				$code = substr($code, 0, strlen($code)-1); // take the kids out of the code
				$foundKids = true;
			} else if (preg_match("/\d+$/", $code, $matches)) {
				if (count($matches) == 1) {
					$parsedCode["kids"] = $matches[0];
					$code = substr($code, 0, strlen($code)-strlen($matches[0])); // take the kids out of the code
					$foundKids = true;
				}
			}
			
			if ($foundKids) { // check for a worshop number before the kids
				// take a trailing dash out of the code, if there is one
				if (substr($code, -1) == "-")
					$code = substr($code, 0, strlen($code)-1);
				$matches = array();
				if (substr($code, -1) == "*") {
					$code = substr($code, 0, strlen($code)-1); // take the workshop number out of the code
					$foundWorkshopNumber = true;
				} else if (preg_match("/\d+$/", $code, $matches)) {
					if (count($matches) == 1) {
						$parsedCode["workshopNumber"] = $matches[0];
						$code = substr($code, 0, strlen($code)-strlen($matches[0])); // take the workshop number out of the code
						$foundWorkshopNumber = true;
					}
				}
				
				if ($foundWorkshopNumber) {
					// take a trailing dash out of the code, if there is one
					if (substr($code, -1) == "-")
						$code = substr($code, 0, strlen($code)-1);
					$matches = array();
					if (substr($code, -1) == "*") {
						$code = substr($code, 0, strlen($code)-1); // take the workshop number out of the code
						$foundWorkshop = true;
					} else if (preg_match("/\w$/", $code, $matches)) {
						if (count($matches) == 1) {
							$potentialCode = $matches[0];
							if (array_key_exists($potentialCode, $this->workshopCodes)) {
								$parsedCode["workshopID"] = $this->workshopCodes[$potentialCode];
								$code = substr($code, 0, strlen($code)-strlen($potentialCode)); // take the workshop out of the code
								$foundWorkshop = true;
							}
						}
					}
					
					if ($foundWorkshop) {
						if (substr($code, -1) == "*") {
							$code = substr($code, 0, strlen($code)-1); // take the workshop out of the code
							$foundProgram = true;
						} else if (substr($code, -1) == "p") { // pending
							$parsedCode['status'] = "Pending";
						} else if (substr($code, -1) == "d") { // denied
							$parsedCode['status'] = "Denied";
						} else if (preg_match("/\w$/", $code, $matches)) {
							if (count($matches) == 1) {
								$potentialCode = $matches[0];
								if (array_key_exists($potentialCode, $this->programs)) {
									$parsedCode["program"] = $this->programs[$potentialCode];
									$foundProgram = true;
								}
							}
						}
					}
				}
			}
		} else {
			// check for a program at the beginning of the code
			$potentialCode = substr($code, 0, 1);
			if ($potentialCode == "*") {
				$foundProgram = true;
				$code = substr($code, 1);
			} else if (substr($code, 0, 1) == "p") { // pending
				$parsedCode['status'] = "Pending";
			} else if (substr($code, 0, 1) == "d") { // denied
				$parsedCode['status'] = "Denied";
				$code = substr($code, 1);
				$foundProgram = true;
			} else if (array_key_exists($potentialCode, $this->programs)) {
				$parsedCode["program"] = $this->programs[$potentialCode];
				$code = substr($code, 1);
				$foundProgram = true;
			}
			

			// check for a workshop ID (one or two character code)
			if ($foundProgram) {
				$potentialCode = substr($code, 0, 1);
				$potentialTwoChar = substr($code, 0, 2);
				if ($potentialCode == "*") {
					$foundWorkshop = true;
					$code = substr($code, 1);
				} else if (array_key_exists($potentialTwoChar, $this->workshopCodes)) {
					$parsedCode["workshopID"] = $this->workshopCodes[$potentialTwoChar];
					$code = substr($code, 2);
					$foundWorkshop = true;
				} else if (array_key_exists($potentialCode, $this->workshopCodes)) {
					$parsedCode["workshopID"] = $this->workshopCodes[$potentialCode];
					$code = substr($code, 1);
					$foundWorkshop = true;
				}
			}
			
			
			// check for a workshop number
			if ($foundWorkshop) {
				// take a starting dash out of the code, if there is one
				if (substr($code, 0, 1) == "-")
					$code = substr($code, 1);
				$matches = array();
				if (substr($code, 0, 1) == "*") {
					$code = substr($code, 1); // take the workshop number out of the code
					$foundKids = true;
				} else if (preg_match("/^\d+/", $code, $matches)) {
					if (count($matches) == 1) {
						$parsedCode["workshopNumber"] = $matches[0];
						$code = substr($code, strlen($matches[0])); // take the workshop number out of the code
						$foundWorkshopNumber = true;
					}
				}
			}
			
			// check for kids
			if ($foundWorkshopNumber) {
				// take a starting dash out of the code, if there is one
				if (substr($code, 0, 1) == "-")
					$code = substr($code, 1);
				$matches = array();
				if (substr($code, 0, 1) == "*") {
					$code = substr($code, 1); // take the kids out of the code
					$foundWorkshopNumber = true;
				} else if (preg_match("/^\d+/", $code, $matches)) {
					if (count($matches) == 1) {
						$parsedCode["kids"] = $matches[0];
						$code = substr($code, strlen($matches[0])); // take the kids out of the code
						$foundKids = true;
					}
				}
			}
		}
		
		$possibleCityCode = substr($code, -2);
		$foundCity = false;
		if (array_key_exists($possibleCityCode, $this->cityCodes)) {
			$parsedCode['city'] = $this->cityCodes[$possibleCityCode];
			$foundCity = true;
			$code = substr($code, 0, strlen($code)-2); // take the city out of the code
		}
		
		return $parsedCode;
		
	}
	
	public function getApplicantById($app_id){
	
		if(is_numeric($app_id)){
			$sql= 'SELECT * FROM Applicant INNER JOIN ApplicantLanguage on ApplicantLanguage.ApplicantID=Applicant.ApplicantID 
			INNER JOIN `Language` on Language.LanguageID=`ApplicantLanguage`.`LanguageID` 
			WHERE Applicant.ApplicantID="'. mysql_real_escape_string($app_id). '"';			
			
			$rs = mysql_query($sql);
			if (mysql_num_rows($rs)) {
			return mysql_fetch_array($rs, MYSQL_ASSOC);
			}
		}
	}
	public function ApplicantChild($app_id){
		$res='';	
		$sql='SELECT * FROM `Child` 
		WHERE ApplicantID="'.mysql_real_escape_string($app_id).'"
		ORDER BY `Child`.`ApplicantID` DESC';
		$query = mysql_query($sql);
		if($query){
			while($result=mysql_fetch_array($query)){
				$res[] = $result;
			}
			return $res;			
		}else{
			return false;
		}
	}
	
	public function AllReportCategory(){
		$res='';	
		$sql='SELECT * FROM `ReportCategory` ';
		$query = mysql_query($sql);
		if($query){
			while($result=mysql_fetch_array($query)){
				$res[] = $result;
			}
			return $res;			
		}else{
			return false;
		}
	}
	
	public function AllReport(){
		$res='';	
		$sql='SELECT * FROM `Report` order by name asc';
		$query = mysql_query($sql);
		if($query){
			while($result=mysql_fetch_array($query)){
				$res[] = $result;
			}
			return $res;			
		}else{
			return false;
		}
	}

	public function saveReportData($data,$reportID){
		if(empty($data))
			return false;
        if(empty($reportID))
			return false;
		
		$sql = "Update $this->_table set Template='".trim($data)."' where ReportID=".$reportID;
		$query = mysql_query($sql);
		if($query){
			return true;
		}
		return false;
	}
	public function reportById($rpID){
		$sql="SELECT * FROM `Report` WHERE ReportID='".mysql_real_escape_string($rpID)."'";	
		return mysql_fetch_array(mysql_query($sql,MYSQL_ASSOC));
	}
	
	/*public function autocomplete($type){
	
	switch(@$type) {
		case APPLICANTS:
		case APPLICANT_GRID:
		case ANGEL_TREE_LABELS:
			$search = explode(' ', $_REQUEST['individual'], 2);
			
			$c = new Criteria();
			
			if (count($search) == 2) {
				$cton1 = $c->getNewCriterion(ApplicantPeer::FIRSTNAME, '%'.$search[0].'%', Criteria::LIKE);
				$cton1->addAnd($c->getNewCriterion(ApplicantPeer::LASTNAME, '%'.$search[1].'%', Criteria::LIKE));
				
				$cton2 = $c->getNewCriterion(ApplicantPeer::FIRSTNAME, '%'.$search[1].'%', Criteria::LIKE);
				$cton2->addAnd($c->getNewCriterion(ApplicantPeer::LASTNAME, '%'.$search[0].'%', Criteria::LIKE));
				
				$cton1->addOr($cton2);
			} else {
				$cton1 = $c->getNewCriterion(ApplicantPeer::FIRSTNAME, '%'.$search[0].'%', Criteria::LIKE);
				$cton1->addOr($c->getNewCriterion(ApplicantPeer::LASTNAME, '%'.$search[0].'%', Criteria::LIKE));	
			}
			
			
		
			// add to Criteria
			$c->add($cton1);
			
			$c->addAscendingOrderByColumn(ApplicantPeer::LASTNAME);
			$c->setLimit(10);
			
			$applicants = ApplicantPeer::doSelect($c);
			
			foreach($applicants as $applicant){
				echo "<li>".$applicant->getLastName() . ', ' . $applicant->getFirstName() . "</li>";
			}
		break;
		case ReportCategory::SPONSORS:
		case ReportCategory::SPONSOR_GRID:
			$c = new Criteria();
			$cton1 = $c->getNewCriterion(SponsorPeer::FIRSTNAME, '%'.$_REQUEST['individual'].'%', Criteria::LIKE);
			$cton2 = $c->getNewCriterion(SponsorPeer::LASTNAME, '%'.$_REQUEST['individual'].'%', Criteria::LIKE);
			
			// combine them
			$cton1->addOr($cton2);
		
			// add to Criteria
			$c->add($cton1);
			
			$c->addAscendingOrderByColumn(SponsorPeer::LASTNAME);
			$c->setLimit(10);
			
			$applicants = SponsorPeer::doSelect($c);
			
			foreach($applicants as $applicant){
				echo "<li>".$applicant->getLastName() . ', ' . $applicant->getFirstName() . "</li>";
			}
		break;
		case ReportCategory::GOLDEN_ANGELS:
		case ReportCategory::GOLDEN_ANGEL_GRID:
		case ReportCategory::GOLDEN_ANGEL_LABELS:
			$c = new Criteria();
		
			// add to Criteria
			$c->add(GoldenAngelAgencyPeer::NAME, '%'.$_REQUEST['individual'].'%', Criteria::LIKE);
			
			$c->addAscendingOrderByColumn(GoldenAngelAgencyPeer::NAME);
			$c->setLimit(10);
			
			$applicants = GoldenAngelAgencyPeer::doSelect($c);
			
			foreach($applicants as $applicant){
				echo "<li>".$applicant->getName() . "\n";
			}
		break;
		case ReportCategory::VOLUNTEERS:
		case ReportCategory::VOLUNTEER_GRID:
			$c = new Criteria();
			$cton1 = $c->getNewCriterion(VolunteerPeer::FIRSTNAME, '%'.$_REQUEST['individual'].'%', Criteria::LIKE);
			$cton2 = $c->getNewCriterion(VolunteerPeer::LASTNAME, '%'.$_REQUEST['individual'].'%', Criteria::LIKE);
			
			// combine them
			$cton1->addOr($cton2);
		
			// add to Criteria
			$c->add($cton1);
			
			$c->addAscendingOrderByColumn(VolunteerPeer::LASTNAME);
			$c->setLimit(10);
			
			$applicants = VolunteerPeer::doSelect($c);
			
			foreach($applicants as $applicant){
				echo "<li>".$applicant->getLastName() . ', ' . $applicant->getFirstName() . "</li>";
			}
		break;
	}
	echo "</ul>";
	}*/
	
	
	

}
