<?php

include_once(dirname(__FILE__) . '/class.ezpdf.php');
require('../../vendor/autoload.php');
include_once "../../config/default.php";


class Clpdf extends Cezpdf {
	protected $my_x;
	protected $my_y;
	protected $title;
	protected $fontsize;
	protected $font;
	protected $date;
	protected $margins;
	protected $pageWidth;
	protected $pageHeight;
	protected $scale;
	protected $nextLine;
	protected $nextX;
	protected $showHeader;
	protected $numOfColumns;
	public $numOfPages = 0;
	protected $currentColumn = 0;
	
	function __construct($title, $landscape = false, $showHeader = 1, $margins = array(.25,.25,.25,.25), $fontsize = 8, $numOfColumns = 1, $font = false, $date = null) {
		if ($font === false) {
			$font = dirname(__FILE__) . '/fonts/Helvetica.afm';
		}
		ini_set('display_errors', 0);
		error_reporting(0);
		
		parent::__construct('LETTER', ($landscape ? 'landscape' : 'portrait'));
		

		$this->numOfColumns = $numOfColumns;
		
		// CONSTANTS... kinda
		if ($landscape == true) {
			$pageWidth = 792.00;
			$pageHeight = 612.00;
		} else {
			$pageWidth = 612.00;
			$pageHeight = 792.00;
		}
		$builderWidth = 610.0;
		
		if (is_null($date)) $date = time();
		
		$this->title = $title;
		$this->fontsize = $fontsize;
		$this->date = $date;
		$this->font = $font;
		$this->showHeader = $showHeader;
		
		// in inches
		$this->margins = $margins;
		
		// convert to pixels
		foreach($this->margins as $key=>$val){
			$this->margins[$key] = $val * 72;
		}
		
		$this->my_x = $this->margins[3];
		
		$this->pageWidth = $pageWidth - $this->margins[3] - $this->margins[1];
		$this->pageHeight = $pageHeight - $this->margins[0] - $this->margins[2];
		$this->scale = $this->pageWidth / $builderWidth;
		
		$this->nextLine = $this->fontsize;
		
		$this->setFont();
	}
	
	function setFont($font_path = null) {
		if (is_null($font_path)) $this->selectFont($this->font,'differences');
		else $this->selectFont($font_path,'differences');
	}
	
	function addHeader() {
		$this->my_y = $this->pageHeight + $this->margins[2];
		$this->my_x = $this->margins[3];
		$this->nextX = $this->margins[3];
		
		$this->numOfPages++;
		
		if ($this->showHeader == 0) return;		
		
		$this->my_y = $this->ezText($this->title, 14, array('spacing'=>2.0));		
		$this->addText($this->pageWidth - $this->getTextWidth(8, date('D M d, Y h:i a')), $this->my_y, 8,  date('D M d, Y h:i a', $this->date));
		$this->line($this->margins[3], $this->my_y-5, $this->pageWidth, $this->my_y-5);
		$this->my_y -= 20;
	}
	
	function addColumns() {
		$this->my_y = $this->pageHeight + $this->margins[2];
		
		//if ($this->nextX == $this->margins[3]) {
		$this->currentColumn++;
		
		if ($this->currentColumn < $this->numOfColumns) {
			$this->my_x = ($this->pageWidth / $this->numOfColumns * $this->currentColumn) + $this->margins[3] + 10;
			$this->nextX = ($this->pageWidth / $this->numOfColumns * $this->currentColumn) + $this->margins[3] + 10;
		} else {
			$this->currentColumn = 0;
			$this->ezNewPage();
			$this->my_x = $this->margins[3];
			$this->nextX = $this->margins[3];
		}
	}
	
	function text($data, $width = null, $variableWidth = true, $wordWrap = false, $fontSize = null, $bold = false, $italic = false, $fontFamily = null) {
		//$data = trim($data); We don't want to trim whitespace.
		
		if (!empty($bold) && $bold == true) {
			$data = "<b>" . $data . "</b>";
		}
		if (!empty($italic) && $italic == true) {
			$data = "<i>" . $data . "</i>";
		}
		
		if (!is_null($fontSize)) {
			$fontSizeToUse = $fontSize;
		} else {
			$fontSizeToUse = $this->fontsize;
		}
		
		
		if ($fontFamily != null) {
			switch($fontFamily){
				case "Arial":
					$this->setFont('./fonts/Arial.afm');
				break;
				case "Times New Roman":
				case "Times":
					$this->setFont('./fonts/Times-Roman.afm');
				break;
				case "Courier":
					$this->setFont('./fonts/Courier.afm');
				break;
				case "Palantino":
					$this->setFont('./fonts/Palantino.afm');
				break;
				case "Garamond":
					$this->setFont('./fonts/Garamond.afm');
				break;
				case "Bookman":
					$this->setFont('./fonts/Bookman.afm');
				break;
			}
			$this->setFont($fontFamily);
		}
		$tempy = 0;
		
		if ($variableWidth) {
			if (($this->getTextWidth($fontSizeToUse, $data) + $this->my_x) > $this->pageWidth) {
				$tempy = 0;
				$next = $this->addTextWrap($this->my_x, $this->my_y, ($this->pageWidth-$this->my_x), $fontSizeToUse, $data);
				while (!empty($next)) {
					$tempy += 12;
					$next = $this->addTextWrap($this->my_x, $this->my_y-$tempy, ($this->pageWidth-$this->my_x), $fontSizeToUse, $next);	
				}
				$this->adjustNextLine($tempy + $fontSizeToUse);
				$this->my_x += ($width*$this->scale);	
			} else {
				$this->addTextWrap($this->my_x, $this->my_y, $this->getTextWidth($fontSizeToUse, $data) + 8, $fontSizeToUse, $data);
				$this->my_x += ($this->getTextWidth($fontSizeToUse, $data) + 2);
			}			
		} else {
			if ($wordWrap == true) {
				$tempy = 0;
				$next = $this->addTextWrap($this->my_x, $this->my_y, ($width*$this->scale), $fontSizeToUse, $data);
				while (!empty($next)) {
					$tempy += 12;
					$next = $this->addTextWrap($this->my_x, $this->my_y-$tempy, ($width*$this->scale), $fontSizeToUse, $next);	
				}
				$this->adjustNextLine($tempy + $fontSizeToUse);
				$this->my_x += $width;
				//$this->my_x += ($width*$this->scale);
			} else {
				$this->addTextWrap($this->my_x, $this->my_y, ($width*$this->scale), $fontSizeToUse, $data);
				$this->my_x += ($width*$this->scale);
			}
		}
		if ($fontFamily != null) {
			$this->setFont();
		}
		
		$this->adjustNextLine($tempy + $fontSizeToUse + 1);
	}
	
	function addImg($file = null, $offsetX = 0, $offsetY = 0, $useJpegFromFile= FALSE, $jpeg_width = 0, $jpeg_height = 0) {
		if ($file == null) $file = APPROOT . '/htdocs' . SUB4SANTAFOLDER . '/images/topimage.png';
		
		$size = getimagesize($file);		

		$im = @imagecreatefrompng($file);
		
		if (!$im) {
			$im = @imagecreatefromjpeg($file);	
		}
		
                if ($useJpegFromFile) {
                  $this->addJpegFromFile($file, $this->my_x + $offsetX, $this->my_y - $size[1] + $this->fontsize + $offsetY, $jpeg_width, $jpeg_height);
                } else {
                  $this->addImage($im, $this->my_x + $offsetX, $this->my_y - $size[1] + $this->fontsize + $offsetY, $size[0]);
                }
		
		if ($offsetY == 0) {		
			$this->my_x += $size[0];
			$this->adjustNextLine($size[1]);
		}
	}
	
	function addGrid($data, $children, $sortBy = array(), $sortDir = array(), $fontSize= null, $fontFamily = null, $is_sponsor = false, $is_angel = false) {
		if (!is_array($children) || count($children) == 0) return;
		
		if (!is_null($fontSize)) {
			$fontSizeToUse = $fontSize;
		} else {
			$fontSizeToUse = $this->fontsize;
		}
				
		if ($fontFamily != null) {
			switch($fontFamily){
				case "Arial":
					$this->setFont('./fonts/Arial.afm');
				break;
				case "Times New Roman":
				case "Times":
					$this->setFont('./fonts/Times-Roman.afm');
				break;
				case "Courier":
					$this->setFont('./fonts/Courier.afm');
				break;
				case "Palantino":
					$this->setFont('./fonts/Palantino.afm');
				break;
				case "Garamond":
					$this->setFont('./fonts/Garamond.afm');
				break;
				case "Bookman":
					$this->setFont('./fonts/Bookman.afm');
				break;
			}
			$this->setFont($fontFamily);
		}
		
		
        $children = $this->aasort($data, $children, $sortBy);
		
		

		$sortedArray = $children;
		$this->ezSetY($this->my_y);
		$gridData = array( );
		
		$num = 0;				
		$setting = new Hmg\Models\Setting();
        $arraySettings = array( 
            'organization_type_id',
            'partnership_level_id',
            'organization_type_id',
            'mode_of_contact_id',
            'resource_database_id',
            'database_id',
            'region_id',
            'relationship_1_id',
            'relationship_2_id',
            'language_id',
            'asq_preference',
            'who_called_id', 
            'family_heard_id',
            'call_reason_id',
            'race_id',
            'ethnicity_id',
            'point_of_entry',
            'how_heard_category_id',
            'how_heard_details_id',
            'event_type_id',
            'outreach_type_id',
            'event_zipcode_id',
            'event_county_id',
            'event_contact_id'
        );
		foreach($sortedArray as $key=>$child) {
			$num++;
			foreach($data as $dat){
                
                if( in_array($dat->value,$arraySettings) ){
                   $gridData[$key][$dat->name] = $setting->getSettingById($children[$key][$dat->value]);
                }
                else if( $dat->value == 'time_of_day' || $dat->value == 'service_area' || $dat->value == 'service_terms' ){
                    $ert = explode(',',$children[$key][$dat->value]);
                    
                    $sdf = '';
                    foreach( $ert as $er){
                        $sdf .= $setting->getSettingById($er).' & '; 
                    }
                    $gridData[$key][$dat->name] =  substr($sdf,0,-2);
                   
                }
				else if( $dat->value == 'formatted_start_date' || $dat->value == 'formatted_end_date' ){
					if( $children[$key][$dat->value] == '00/00/0000'){
						$gridData[$key][$dat->name] = '';
					}
					else{
						$gridData[$key][$dat->name] = $children[$key][$dat->value];
					}
                }
                else{                        
                    $gridData[$key][$dat->name] = $children[$key][$dat->value];	
                }
				
			}
		}			
	
		
		$this->my_y = $this->ezTable($gridData, null, null, array('fontSize'=>$fontSizeToUse, 'width'=>$this->pageWidth - $this->margins[3] - $this->margins[1])) - 16;
		
		if ($fontFamily != null) {
			$this->setFont();
		}
	}
	
	function customAddGrid($data, $children, $sortBy = array(), $sortDir = array(), $fontSize= null, $fontFamily = null, $is_sponsor = false) {
		if (!is_array($children) || count($children) == 0) return;
		
		if (!is_null($fontSize)) {
			$fontSizeToUse = $fontSize;
		} else {
			$fontSizeToUse = $this->fontsize;
		}
		
		
		if ($fontFamily != null) {
			switch($fontFamily){
				case "Arial":
					$this->setFont('./fonts/Arial.afm');
				break;
				case "Times New Roman":
				case "Times":
					$this->setFont('./fonts/Times-Roman.afm');
				break;
				case "Courier":
					$this->setFont('./fonts/Courier.afm');
				break;
				case "Palantino":
					$this->setFont('./fonts/Palantino.afm');
				break;
				case "Garamond":
					$this->setFont('./fonts/Garamond.afm');
				break;
				case "Bookman":
					$this->setFont('./fonts/Bookman.afm');
				break;
			}
			$this->setFont($fontFamily);
		}
		
		/*if (count($sortBy)) {
			for($i=count($sortBy)-1;$i>=0;$i--) {
				$children = $this->sortData($data[$sortBy[$i]]->value, $sortDir[$i], $children);
			}
		}*/
		if (count($sortBy)) {
			for($i=count($sortBy)-1;$i>=0;$i--) {
				//echo $data[$sortBy[$i]]->value. '===';//die;
				$children = $this->aasort($children, $data[$sortBy[$i]]->value);
			}
		}

		$sortedArray = $children;
		$this->ezSetY($this->my_y);
		$gridData = array();
		
		foreach($sortedArray as $key=>$child) {
			$num++;
			foreach($data as $dat){
				//echo "<pre>";print_r($dat);
				if (isset($dat->value)) {
					if($is_sponsor) {
						$gridData[$key][$dat->name] = trim($common->getRealByNameSponsor($dat->value, $child));//trim($child[$dat->value]);
					} else {
						$gridData[$key][$dat->name] = trim($common->getRealByNameApplicant($dat->value, $child));//trim($child[$dat->value]);
					}
				} elseif (isset($dat->name)) {
					if($is_sponsor) {
						$gridData[$key][$dat->name] = trim($common->getRealByNameSponsor($dat->value, $child));//trim($child[$dat->value]);
					} else {
						$gridData[$key][$dat->name] = trim($common->getRealByNameApplicant($dat->value, $child));//trim($child[$dat->value]);
					}
				}
			}
		}
//		die('temp stop');
		
		$this->my_y = $this->ezTable($gridData, null, null, array('fontSize'=>$fontSizeToUse, 'width'=>$this->pageWidth - $this->margins[3] - $this->margins[1])) - 16;
//		die('temp stop 2');
		
		if ($fontFamily != null) {
			$this->setFont();
		}
	}
	
	function sortData($sortBy, $sortDir, $children) {
	
			$sortedArray = array();
			
			$count = 0;
			while (count($children) > 0) {
				$temp = -1;			
					
				foreach($children as $key=>$val) {
					if ($temp == -1) {
						$temp = $key;
						continue;
					}
					
					if ($sortDir == "ASC") {						
						if (is_numeric(@$children[$temp]->getRealByName($sortBy))) {
							if (@$children[$temp]->getRealByName($sortBy) > @$children[$key]->getRealByName($sortBy)) {
								$temp = $key;
							}
						} else {
							if (strcasecmp(trim(@$children[$temp]->getRealByName($sortBy)), trim(@$children[$key]->getRealByName($sortBy))) > 0) {
								$temp = $key;
							}
						}
					} else {
						if (is_numeric(@$children[$temp]->getRealByName($sortBy))) {
							if (@$children[$temp]->getRealByName($sortBy) < @$children[$key]->getRealByName($sortBy)) {
								$temp = $key;
							}
						} else {
							if (strcasecmp(trim(@$children[$temp]->getRealByName($sortBy)), trim(@$children[$key]->getRealByName($sortBy))) < 0) {
								$temp = $key;
							}
						}
					}
				}
	
				$sortedArray[$count] = $children[$temp];
				
				unset($children[$temp]);
				$count++;
			}
			
			return $sortedArray;
	}
	
	function newLine(){
		if ($this->my_y - $this->nextLine - $this->margins[2] - $this->fontsize < 0) {
			if ($this->numOfColumns == 1) {
				$this->ezNewPage();
				$this->addHeader();			
			} else {
				$this->addColumns();			
			}
		} else {
			$this->my_x = $this->nextX;
			$this->my_y -= $this->nextLine;
			$this->nextLine = $this->fontsize;
		}
	}
	
	function hr($height) {
		$this->filledRectangle(30, $this->my_y, 550, 3);
		$this->my_y -= 3;
	}
	
	function addSpace($height){
		$this->my_x = $this->nextX;
		$this->my_y -= $height;
		$this->nextLine = $this->fontsize;
	}
	
	function adjustNextLine($height){
		if ($this->nextLine < $height) $this->nextLine = $height;
	}
	
	function makeFooter($height) {
		if ($this->my_y > $height) $this->my_y = $height;
	}
	function aasort($data, $arr, $col, $dir = SORT_ASC) {
        if (count($sortBy)) {
			for($i=0;$i<count($sortBy);$i++) {
                $name = 'sort_col'.$i;
                $$name = array();
                foreach ($arr as $key=> $row) {
                    $$name[$key] = ucwords(strtolower(trim($row[$data[$sortBy[$i]]->value])));
                }				
			}
            array_multisort($sort_col0, $dir,$sort_col1, $dir,$sort_col2, $dir, $arr);
	    
		}	    
        return $arr;	    
	}

}
