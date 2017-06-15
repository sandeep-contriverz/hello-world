<?php

class PdfProviderFamiliesChildID extends FPDF
{
    var $ProcessingTable=false;
    var $aCols=array();
    var $TableX;
    var $HeaderColor;
    var $RowColors;
    var $ColorIndex;
    var $logo;
    var $footerText;
    var $loggedInUser;
    var $status;
    var $dateRange;
    var $tableColumns;
    var $provider;

    function pageHeader()
    {
        //Print the table header if necessary
        if($this->ProcessingTable)
            $this->TableHeader();
    }

    // Page header
    function Header()
    {
        $this->AddFont('CenturyGothic','','CenturyGothic.php');
        $this->AddFont('CenturyGothicBold','B','CenturyGothicBold.php');
        $this->AddFont('CenturyGothicItalic','I','CenturyGothicItalic.php');



        if($this->PageNo() > 1){

            $this->setY(0);
            $this->setX(25);
            // Arial bold 15
            $this->SetFont('CenturyGothic','',15);
            $this->Cell(0,40,'Providers: List of Families (cont.)',0,1,'L');

            $this->SetFont('Arial','B',12);
            $this->Cell(114.18,20.87,$this->provider['name'],0,0,'L');

            // Reset Y
            $this->PageTwoSummary();

            $this->displayTableHeaders();

        } else {
            // Logo
            $this->Image($this->logo,10,8.57,114.28);
            // Arial bold 15
            $this->SetFont('CenturyGothic','',25);
            // Set position
            $this->setY(0);
            $this->setX(135.71);
            //$this->Image('images/childInformationHdr.png',44,$this->getY()+2,85);
            //$this->Ln(20);
            // Title
            $this->Cell(0,57.14,'Providers: List of Families',0,1,'L');

            $this->PageOneSummary();
        }
    }

    // Page footer
    function Footer()
    {
        $this->SetY(-100);

        $this->SetFont('Arial', 'B', 9);

        $asq3 = '*ASQ-3 Scoring - Above: No Concern, Monitoring: Close to the cutoff, Below: Concern';
        $this->Cell(571.42, 11, $asq3, 0, 2, 'L');

        
        $asq2 = '*ASQ-SE 2 Scoring - Below: No Concern, Monitoring: Close to the cutoff, Above: Concern';
        $this->Cell(571.42, 11, $asq2, 0, 2, 'L');

        $noScore = '*No Score = No screening recorded';
        $this->Cell(571.42, 11, $noScore, 0, 2, 'L');

        // Position at 1.5 cm from bottom
        $this->SetY(-51.42);
        //$this->Image('images/counts-footer.png',45,$this->getY(),120);
        $this->Line(20.87, $this->getY(), 571.42, $this->getY());
        $this->SetTextColor(100, 100, 100);
        // Arial italic 8
        $this->SetFont('Arial', '', 11);
        // Page number
        $this->Cell(571.42, 20.87, $this->footerText, 0, 2, 'L');
        $this->Cell(555, 12, 'Page '.$this->PageNo().' of '. '{nb}', 0, 0, 'R');

    }

    function SetLogoImage($imgPath = '')
    {
        $this->logo = $imgPath;
    }

    function SetFooterText($text = '')
    {
        $this->footerText = $text;
    }

    function SetPageSummaryData($loggedInUser = '', $status = 'Any', $dateRange = 'All'){
        $this->loggedInUser = $loggedInUser;
        $this->status       = $status;
        $this->dateRange    = $dateRange;


    }

    function PageOneSummary(){

        $this->setX(371.42);
        $this->SetFont('Arial','B',9);
        $this->Cell(71.43,20.87,'Report Created:',0,0,'R');
        $this->SetFont('Arial','',9);
        $this->Cell(114.28,20.87,date('F d, Y'),0,0,'L');
        $this->Ln(15);

        $this->setX(371.42);
        $this->SetFont('Arial','B',9);
        $this->Cell(71.43,20.87,'Created By:',0,0,'R');
        $this->SetFont('Arial','',9);
        $this->Cell(280.70,20.87,$this->loggedInUser,0,0,'L');
        $this->Ln(15);

        $this->setX(371.42);
        $this->SetFont('Arial','B',9);
        $this->Cell(71.43,20.87,'Family Status:',0,0,'R');
        $this->SetFont('Arial','',9);
        $this->Cell(114.28,20.87,$this->status,0,0,'L');
        $this->Ln(15);

        $this->setX(371.42);
        $this->SetFont('Arial','B',9);
        $this->Cell(71.43,20.87,'Date Range:',0,0,'R');
        $this->SetFont('Arial','',9);
        $this->Cell(114.28,20.87,$this->dateRange,0,0,'L');
        $this->Ln(51.42);

        $currentY = $this->getY();
        // separator
        $this->Line(20.87, $currentY, 571.42, $currentY);
    }

    function PageTwoSummary(){

        $this->setY(10);
        $this->setX(371.42);
        $this->SetFont('Arial','B',9);
        $this->Cell(71.43,20.87,'Report Created:',0,0,'R');
        $this->SetFont('Arial','',9);
        $this->Cell(114.28,20.87,date('F d, Y'),0,0,'L');
        $this->Ln(15);

        $this->setX(371.42);
        $this->SetFont('Arial','B',9);
        $this->Cell(71.43,20.87,'Created By:',0,0,'R');
        $this->SetFont('Arial','',9);
        $this->Cell(280.70,20.87,$this->loggedInUser,0,0,'L');
        $this->Ln(15);

        $this->setX(371.42);
        $this->SetFont('Arial','B',9);
        $this->Cell(71.43,20.87,'Family Status:',0,0,'R');
        $this->SetFont('Arial','',9);
        $this->Cell(114.28,20.87,$this->status,0,0,'L');
        $this->Ln(15);

        $this->setX(371.42);
        $this->SetFont('Arial','B',9);
        $this->Cell(71.43,20.87,'Date Range:',0,0,'R');
        $this->SetFont('Arial','',9);
        $this->Cell(114.28,20.87,$this->dateRange,0,0,'L');
        $this->Ln(30.42);

        $currentY = $this->getY();
        // separator
        $this->Line(20.87, $currentY, 571.42, $currentY);
    }

    function ProviderInformation($info = array()){

        $this->provider = $info;

        // Column 1
        $this->Ln(10.42);

        $this->setX(34.28);
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Provider Office/Clinic: '),20.87,'Provider Office/Clinic:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(285.71,20.87,$info['employer'],0,0,'L');
        $this->Ln(20);

        $this->setX(34.28);
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Phone: '),20.87,'Phone:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(100.18,20.87,$info['phone'],0,0,'L');
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Fax: '),20.87,'Fax:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(100.18,20.87,$info['fax'],0,0,'L');
        $this->Ln(20);

        $this->setX(34.28);
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Email: '),20.87,'Email:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(114.18,20.87,$info['email'],0,0,'L');
        $this->Ln(42.25);
    }

    /**
     * Display table headers
     *
     * return array $cWidth Array of column widths.
    */
    function displayTableHeaders(){

        $columns = $this->tableColumns;
        //echo "<pre>";print_r($columns);die;
        $this->Ln(22.85);
        $this->setFillColor(205, 205, 205);
        $this->SetTextColor(70, 70, 70);
        $this->SetFont('Arial', 'B', 9);
        $currentY = $this->getY();
        $currentX = $this->getX();
        //$cWidth = array(80, 52, 52, 150, 90, 55, 55);
        //$cWidth = array(75, 52, 52, 70, 50, 50, 55, 65, 65);
        $cWidth = array(45, 33, 60, 55, 50, 55, 55, 55,65,61);
        $cIndex = 0;
        foreach($columns as $column => $value){
            $cellAlign = 'L';
            $cellWidth = $cWidth[$cIndex];
            $cellText = $value;
            $textWidth = $this->GetStringWidth($cellText);
            $cellLineHeight = 11;
            if($textWidth < $cellWidth){
                $cellLineHeight = $cellLineHeight * 2;
            }
            
            //echo $textWidth.'==='.$cellWidth."<br>";
            //echo $cellLineHeight."<br>";
            $this->MultiCell($cellWidth, $cellLineHeight, $cellText, 1, $cellAlign, 1);
            // Set up for next cell
            $currentX += $cWidth[$cIndex];
            $this->setXY($currentX, $currentY);
            $cIndex++;
        }
        $this->Ln();
        $this->Ln();
        
        return $cWidth;
    }

    function FamilyList($columns = array(), $data = array()){

        $this->tableColumns = $columns;

        $totalRecords  = count($data);
        $sumReferrals  = 0;
        $sumScreenings = 0;

        // Column 1
        $this->setX(28.57);
        $this->SetFont('Arial','B',10);
        $this->Cell($this->GetStringWidth('List of Families Associated with Provider'),28.57,'List of Families Associated with Provider',0,0,'L');
        $this->Ln(22.85);

        $currentY = $this->getY();
        // separator
        $this->Line(28.57, $currentY, 571.85, $currentY);
        if(! count($columns)){
            $this->SetFont('Arial','I',10);
            $this->Cell($this->GetStringWidth('No records to display'),28.57,'No records to display',0,0,'L');
            return;
        }

        $cWidth = $this->displayTableHeaders();
        //echo "<pre>";print_r($cWidth);die;

        $this->setFillColor(205, 205, 205);
        $this->SetTextColor(70, 70, 70);
        $this->SetFont('Arial', '', 8);
        $count = 1;
        foreach ($data as $row) {
            $sumScreenings += $row['Number of Screenings**'];
            $sumReferrals += $row['Number of Referrals***'];

            $cellBorder = '1';
            $cellLinePadding = '';
            $cellLineHeight = 11;
            $currentY = $this->getY();
            $currentX = $this->getX();
            $cIndex = 0;
            $numberLines = array();
            $maxLines = 0;
            $maxY = 0;

            $family_status = $this->NbLines($row['Family Status'], $cWidth[0]);
            $childName = $this->NbLines($row['Child ID'], $cWidth[1]);
            //$childDOB = $this->NbLines($row['Child DOB'], $cWidth[1]);
            $referralName = $this->NbLines($row['Referral Name'], $cWidth[3]);
            $referralNameLines = count(explode("\n", ($row['Referral Name'])));
           
            
            if ($referralNameLines > $referralName['lines']) {
                $referralName['lines'] = $referralNameLines;
            }
            $referralDate = $this->NbLines($row['Date of Referral'], $cWidth[2]);
            $referralDateLines = count(explode("\n", $row['Date of Referral']));
            if ($referralDateLines > $referralDate['lines']) {
                $referralDate['lines'] = $referralDateLines;
            }
            
            $outcomeLines = count(explode("\n", trim($row['Referral Outcome']) ));
            $outcome = $this->NbLines($row['Referral Outcome'], $cWidth[4]);
            if ($outcomeLines > $outcome['lines']) {
                $outcome['lines'] = $outcomeLines;
            }
            
            $asqScreening4 = $this->NbLines($row['Screening Type'], $cWidth[5]);
            $asqScreening1 = $this->NbLines($row['Screening Interval'], $cWidth[6]);
            $asqScreening = $this->NbLines($row['Screening Outcome*'], $cWidth[7]);
            $asqScreening2 = $this->NbLines($row['Number of Screenings**'], $cWidth[8]);
            $asqScreening3 = $this->NbLines($row['Number of Referrals**'], $cWidth[9]);

            $numberLines[] = $childName['lines'];
            $numberLines[] = $family_status['lines'];
            $numberLines[] = $referralDate['lines'];
            $numberLines[] = $referralName['lines'];
            $numberLines[] = $outcome['lines'];
            $numberLines[] = $asqScreening['lines'];

            $numberLines[] = $asqNoScreening['lines'];
            $numberLines[] = $asqNoReferals['lines'];
            
            $numberLines[] = $asqScreening['lines'];
            $numberLines[] = $asqScreening1['lines'];
            $numberLines[] = $asqScreening2['lines'];
            $numberLines[] = $asqScreening3['lines'];
            $numberLines[] = $asqScreening4['lines'];

            $maxLines = max($numberLines);

            // Figure out the next row wil cause a page break
            $pageY = $currentY + ($cellLineHeight * $maxLines);

            if ($pageY > 740) {
                // Adds line to bottom
                $this->addPage();

                //$this->setY(240);

                // $this->ScreeningInformationHeader($columns, $cWidth, true);

                $currentY = $this->getY();

                // TODO: Figure out why we get an extra cell when going
                // to a new page.
                $maxY = $currentY;

                $currentY = $this->getY();
                $currentX = $this->getX();
                $cellLineHeight = 11;
            }

            foreach ($row as $column => $value) {
                $cellLinePadding = '';
                $cellAlign = 'L';
                // if ($cIndex == 4){
                //     $cellAlign = 'L';
                // }
                $cellBorder = 'LR';
                $cellWidth = $cWidth[$cIndex];
                $cellText = $value;
                $difference = 0;

                if ($column == 'Child ID') {
                    if ($childName['lines'] < $maxLines) {
                        $difference = $maxLines - $childName['lines'];
                    }
                } elseif ($column == 'Family Status') {
                    if ($family_status['lines'] < $maxLines) {
                        $difference = $maxLines - $family_status['lines'];
                    }
                } elseif ($column == 'Date of Referral') {
                    if ($referralDateLines < $maxLines) {
                        $difference = $maxLines - $referralDate['lines'];
                    }
                } elseif ($column == 'Referral Name') {
                    if ($referralName['lines'] < $maxLines) {
                        $difference = $maxLines - $referralName['lines'];
                    }
                } elseif ($column == 'Referral Outcome') {
                    if ($outcomeLines < $maxLines) {
                        $difference = $maxLines - $outcome['lines'];
                    }
                } elseif ($column == 'Screening Type') {
                    if ($asqScreening4['lines'] < $maxLines) {
                        $difference = $maxLines - $asqScreening4['lines'];
                    }
                }elseif ($column == 'Screening Interval') {
                    if ($asqScreening1 < $maxLines) {
                        $difference = $maxLines - $asqScreening1['lines'];
                    }
                }elseif ($column == 'Screening Outcome*') {
                    if ($asqScreening['lines'] < $maxLines) {
                        $difference = $maxLines - $asqScreening['lines'];
                    }
                } elseif ($column == 'Number of Screenings**') {
                    if ($asqScreening2['lines'] < $maxLines) {
                        $difference = $maxLines - $asqScreening2['lines'];
                    }
                } elseif ($column == 'Number of Referrals***') {
                    if ($asqScreening3['lines'] < $maxLines) {
                        $difference = $maxLines - $asqScreening3['lines'];
                    }
                }
                if(in_array($cIndex, array(7, 8))){
                    $cellAlign = 'C';
                }
                
                if ($difference) {
                    $cellText .= str_repeat("\n\t", $difference);
                }
                

                $this->MultiCell($cellWidth, $cellLineHeight, $cellText, $cellBorder, $cellAlign, 0);
                $newY = $this->getY();
                if ($newY > $maxY) {
                    $maxY = $newY;
                }
                // Set up for next cell
                $currentX += $cWidth[$cIndex];
                $this->setXY($currentX, $currentY);
                $cIndex++;
            }

            $this->setY($maxY);
            // Adds line to bottom
            $this->Cell(534, 0, '', 'T', 0, 0, 0);
            $this->Ln();

        }
        $currentY = $this->getY();
        $currentX = $this->getX();

       $this->MultiCell('158', $cellLineHeight, 'Total Number of Children: ' . $totalRecords, '1', 'L', 0);
        $currentX += 158;
        $this->setXY($currentX, $currentY);
        $this->MultiCell('250', $cellLineHeight, 'Totals: ', '1', 'R', 0);

        $currentX += 250;
        $this->setXY($currentX, $currentY);
        $this->MultiCell('65', $cellLineHeight, $sumScreenings, '1', 'C', 0);

        $currentX += 65;
        $this->setXY($currentX, $currentY);
        $this->MultiCell('61', $cellLineHeight, $sumReferrals, '1', 'C', 0);

        $this->Ln(4);

    }

    function NbLines($txt, $w)
    {
        //$txt = preg_replace('/\ns+/', ' ', $txt);

        //Computes the number of lines a MultiCell of width w will take
        $cw=&$this->CurrentFont['cw'];
        if($w==0)
        $w=$this->w-$this->rMargin-$this->x;
        $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);
        if($nb>0 and $s[$nb-1]=="\n")
        $nb--;
        $sep=-1;
        $i=0;
        $j=0;
        $l=0;
        $nl=1;
        while($i<$nb){
            $c=$s[$i];
            if($c=="\n"){
                $i++;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
                continue;
            }
            if($c==' ')
            $sep=$i;
            $l+=$cw[$c];
            if($l>$wmax){
                if($sep==-1){
                    if($i==$j)
                    $i++;
                }
                else
                    $i=$sep+1;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
            }
            else
                $i++;
        }
        //echo 'TXT: ' . $txt . ' Lines: ' . $nl . '<br />';
        return array('wrapped' => $txt, 'lines' => $nl);
    }
}
?>