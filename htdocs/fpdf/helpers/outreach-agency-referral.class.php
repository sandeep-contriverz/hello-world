<?php

class PdfAgencyReferrals extends FPDF
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
    var $agency;

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
            $this->Cell(0,40,'Agency Information (cont.)',0,1,'L');

            $this->SetFont('Arial','B',12);
            $this->Cell(114.18,20.87,$this->agency['name'],0,0,'L');

            // Reset Y
            $this->PageTwoSummary();

            $this->displayTableHeaders();

        } else {
            // Logo
            $this->Image($this->logo,0,8.57,114.28);
            // Arial bold 15
            $this->SetFont('CenturyGothic','',25);
            // Set position
            $this->setY(0);
            $this->setX(125.71);
            //$this->Image('images/childInformationHdr.png',44,$this->getY()+2,85);
            //$this->Ln(20);
            // Title
            $this->Cell(0,57.14,'Agency Information',0,1,'L');

            $this->PageOneSummary();
        }
    }

    // Page footer
    function Footer()
    {

        // Position at 1.5 cm from bottom
        $this->SetY(-51.42);
        //$this->Image('images/counts-footer.png',45,$this->getY(),120);
        //
        $this->Line(20.87, $this->getY(), 571.42, $this->getY());
        $this->SetTextColor(100, 100, 100);
        // Arial italic 8
        $this->SetFont('Arial','',11);
        // Page number
        $this->Cell(571.42, 20.87, $this->footerText, 0, 2, 'L');
        $this->Cell(555,12,'Page '.$this->PageNo().' of '. '{nb}',0,0,'R');
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

    function AgencyInformation($info = array()){

        $this->agency = $info;

        // Column 1
        $this->ln(10.42);
        $this->setX(150);
        $columnY = $this->getY();
        $this->SetFont('Arial','B',12);
        $this->Cell($this->GetStringWidth('Agency Name: '),20.87,'Agency Name:',0,0,'L');
        $this->SetFont('Arial','',11);
        $this->Cell(114.18,20.87,$info['name'],0,0,'L');
        $this->Ln(20);

        $this->setX(170);
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Service Terms: '),20.87,'Service Terms:',0,0,'L');

        $termsX = $this->getX();

        // Display all service terms
        if(is_array($info['services'])){
            foreach($info['services'] as $service){
                $this->setX($termsX);
                $this->SetFont('Arial','',9);
                $this->Cell(285.71,20.87,$service['name'],0,0,'L');
                $this->Ln(20);
            }
        }
    }
/**
     * Display table headers
     *
     * return array $cWidth Array of column widths.
    */
    function displayTableHeaders(){

        $columns = $this->tableColumns;

        $this->Ln(20);
        $this->setFillColor(205, 205, 205);
        $this->SetTextColor(70, 70, 70);
        $this->SetFont('Arial', 'B', 9);


        $cWidth = array(170, 60, 60, 60, 60, 60, 60);
        $cIndex = 0;

        $this->setX(32);

        $currentY = $this->getY();
        $currentX = $this->getX();

        foreach($columns as $column => $value){
            $cellAlign = 'C';
            $cellWidth = $cWidth[$cIndex];
            $cellText = $value;
            $textWidth = $this->GetStringWidth($cellText);
            $cellLineHeight = 11;
            if($textWidth < $cellWidth){
                $cellLineHeight = $cellLineHeight * 2;
            }
            $this->MultiCell($cellWidth, $cellLineHeight, $cellText, 1, $cellAlign, 1);
            // Set up for next cell
            $currentX += $cWidth[$cIndex];
            $this->setXY($currentX, $currentY);
            $cIndex++;
        }
        $this->Ln();

        return $cWidth;
    }

    function ReferralList($columns = array(), $data = array()){

        $this->tableColumns = $columns;

        $totalRecords  = count($data);
        $resourceTotal = 0;

        // Column 1
        $this->setX(28.57);
        $this->SetFont('Arial','B',10);
        $this->Cell($this->GetStringWidth('Referrals and Outcomes'),28.57,'Referrals and Outcomes',0,0,'L');
        $this->Ln(22.85);

        $currentY = $this->getY();
        // separator
        $this->Line(28.57, $currentY, 571.85, $currentY);
        if(! count($data)){
            $this->SetFont('Arial','I',10);
            $this->Cell($this->GetStringWidth('No records to display'),28.57,'No records to display',0,0,'L');
            return;
        }

        // Display Table

        $currentY = $this->getY();
        $rightMargin = 32;
        $currentX = $rightMargin;
        $tabX = $currentX;
        $this->setX($tabX);

        $cWidth = $this->displayTableHeaders();

        $this->setFillColor(205, 205, 205);
        $this->SetTextColor(70, 70, 70);
        $this->SetFont('Arial', '', 8);

        $totals = array(
            "Information Received" => 0,
            "Connected"            => 0,
            "Not Connected"        => 0,
            "Outcome Unknown"      => 0,
            "Outcome Pending"      => 0,
            "Total"                => 0
        );
        foreach($data as $row){

            $this->setX($tabX);

            $cellBorder = 1;
            $cellLinePadding = '';
            $cellLineHeight = 20;
            $currentY = $this->getY();
            $currentX = $this->getX();
            $cIndex = 0;
            $serviceLength = $this->GetStringWidth($row['Service']);
            $numberLines = 1;
            if($serviceLength > 160){
                $numberLines = ceil($serviceLength / 160);
                $cellBorder = "LR";
                //$cellLineHeight = $numberLines * $cellLineHeight;
            }
            foreach($row as $column => $value){
                if($column != 'Service'){
                    $totals[$column] += $value;
                }
                $cellAlign = 'C';
                if($cIndex == 0){
                    $cellAlign = 'L';
                }
                $cellWidth = $cWidth[$cIndex];
                if($numberLines > 1){
                    $cellLinePadding = str_repeat("\n ", $numberLines - 1);
                    $cellLineHeight = 14;
                } else {
                    $cellLineHeight = 20;
                }
                $cellText = $value . ($column != 'Service' ? $cellLinePadding : '');
                $textWidth = ($cellText ? $this->GetStringWidth($cellText) : 0);
                $this->MultiCell($cellWidth, $cellLineHeight, $cellText , $cellBorder, $cellAlign, 0);
                // Set up for next cell
                // Set up for next cell
                $currentX += $cWidth[$cIndex];
                $currentY = $this->getY() - ($cellLineHeight * $numberLines);
                $this->setXY($currentX, $currentY);
                $cIndex++;
            }

            for($l = 1; $l <= $numberLines; $l++){
                $this->Ln();
            }
        }

        $this->setX($rightMargin);

        $currentY = $this->getY();
        $currentX = $this->getX();

        // Totals
        $this->MultiCell($cWidth[0], $cellLineHeight, 'Totals: ', '1', 'R', 0);

        $currentX += $cWidth[0];
        $this->setXY($currentX , $currentY);
        $this->MultiCell($cWidth[1], $cellLineHeight, $totals["Information Received"], '1', 'C', 0);

        $currentX += $cWidth[1];
        $this->setXY($currentX , $currentY);
        $this->MultiCell($cWidth[2], $cellLineHeight, $totals["Connected"], '1', 'C', 0);

        $currentX += $cWidth[2];
        $this->setXY($currentX , $currentY);
        $this->MultiCell($cWidth[3], $cellLineHeight, $totals["Not Connected"], '1', 'C', 0);

        $currentX += $cWidth[3];
        $this->setXY($currentX , $currentY);
        $this->MultiCell($cWidth[4], $cellLineHeight, $totals["Outcome Unknown"], '1', 'C', 0);

        $currentX += $cWidth[4];
        $this->setXY($currentX , $currentY);
        $this->MultiCell($cWidth[5], $cellLineHeight, $totals["Outcome Pending"], '1', 'C', 0);

        $currentX += $cWidth[5];
        $this->setXY($currentX , $currentY);
        $this->MultiCell($cWidth[6], $cellLineHeight, $totals["Total"], '1', 'C', 0);

        $this->setX($rightMargin);
        $this->Cell(530, 0, '', 'T', 0, 0, 0);

        $this->Ln(4);
    }

    // Display Header
    function displayHeader($columns, $cWidth){
        $this->setFillColor(205, 205, 205);
        $this->SetTextColor(70, 70, 70);
        $this->SetFont('Arial', '', 9);
        $currentY = $this->getY();
        $currentX = $this->getX();
        $cIndex = 0;
        foreach($columns as $column => $value){
            $cellAlign = 'C';
            if($cIndex == 5){
                $cellAlign = 'L';
            }
            $cellWidth = $cWidth[$cIndex];
            $cellText = $value;
            $textWidth = $this->GetStringWidth($cellText);
            $cellLineHeight = 11;
            if($textWidth < $cellWidth){
                $cellLineHeight = $cellLineHeight * 2;
            }
            $this->MultiCell($cellWidth, $cellLineHeight, $cellText, 1, $cellAlign, 1);
            // Set up for next cell
            $currentX += $cWidth[$cIndex];
            $this->setXY($currentX, $currentY);
            $cIndex++;
        }
        $this->Ln();
    }

    function NbLines($txt, $w)
    {
        $txt = preg_replace('/\s+/', ' ', $txt);
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
        return array('wrapped' => $txt, 'lines' => $nl);
    }
}
?>