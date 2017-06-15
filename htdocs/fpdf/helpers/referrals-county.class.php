<?php

class PdfReferralsCounty extends FPDF
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
    var $county;

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
            $this->Cell(0,40,'Referrals By County (cont.)',0,1,'L');

            $this->SetFont('Arial','B',12);
            $this->Cell(114.18,20.87,$this->county['name'],0,0,'L');

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
            $this->Cell(0,57.14,'Referrals By County',0,1,'L');

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

    function CountyInformation($info = array()){

        $this->county = $info;

        // Column 1
        $this->ln(5);
        $this->setX(30);
        $columnY = $this->getY();
        $this->SetFont('Arial','B',12);
        $this->Cell($this->GetStringWidth('County: '),20.87,'County:',0,0,'L');
        $this->SetFont('Arial','',11);
        $this->Cell(114.18,20.87,$info['name'],0,0,'L');
        $this->Ln(25);

        $currentY = $this->getY();
        // separator
        $this->Line(20.87, $currentY, 571.85, $currentY);
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

        if (count($columns) == 5) {
            $cWidth = array(125, 125, 120, 50, 50);
        } else {
            $cWidth = array(150, 150, 120, 50);
        }
        $cIndex = 0;

        $this->setX(65);

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

        $totals  = 0;

        if(! count($data)){
            $this->SetFont('Arial','I',10);
            $this->Cell($this->GetStringWidth('No records to display'),28.57,'No records to display',0,0,'L');
            return;
        }

        // Display Table

        $currentY = $this->getY();
        $rightMargin = 65;
        $currentX = $rightMargin;
        $tabX = $currentX;
        $this->setX($tabX);

        $cWidth = $this->displayTableHeaders();

        $this->setFillColor(205, 205, 205);
        $this->SetTextColor(70, 70, 70);
        $this->SetFont('Arial', '', 8);

        foreach ($data as $row) {

            $totals += $row['Count'];
            $this->setX($tabX);

            $cellBorder = 1;
            $cellLinePadding = '';
            $cellLineHeight = 11;
            $currentY = $this->getY();
            $currentX = $this->getX();
            $cIndex = 0;
            $numberLines = array();
            $maxLines = 0;

            if (count($row) == 5) {
                $referral = $this->NbLines($row['Referral Name'], 125);
                $service = $this->NbLines($row['Service'], 125);
                $outcome = $this->NbLines($row['Outcome'], 120);
                $counties = $this->NbLines($row['Counties'], 50);
                $count = $this->NbLines($row['Count'], 50);

                $numberLines[] = $referral['lines'];
                $numberLines[] = $counties['lines'];
                $numberLines[] = $count['lines'];
                $numberLines[] = $service['lines'];
                $numberLines[] = $outcome['lines'];
            } else {
                $referral = $this->NbLines($row['Referral Name'], 150);
                $service = $this->NbLines($row['Service'], 150);
                $outcome = $this->NbLines($row['Outcome'], 120);
                $count = $this->NbLines($row['Count'], 50);

                $numberLines[] = $referral['lines'];
                $numberLines[] = $count['lines'];
                $numberLines[] = $service['lines'];
                $numberLines[] = $outcome['lines'];
            }

            $maxLines = max($numberLines);

            if (($currentY + $maxLines * $cellLineHeight) > 770) {
                $this->addPage();
                $this->setX($rightMargin);
            }


            foreach ($row as $column => $value) {
                $cellAlign = 'C';

                $cellWidth = $cWidth[$cIndex];

                if ($column == 'Referral Name') {
                    $cellText = $value;
                    if ($referral['lines'] < $maxLines) {
                        $difference = $maxLines - $referral['lines'];
                        if ($difference) {
                            $cellText .= str_repeat("\n ", $difference);
                        }
                    }
                } else if ($column == 'Count') {
                    $cellText = $value;
                    if ($count['lines'] < $maxLines) {
                        $difference = $maxLines - $count['lines'];
                        if ($difference) {
                            $cellText .= str_repeat("\n ", $difference);
                        }
                    }
                } else if ($column == 'Service') {
                    $cellText = $value;
                    if ($service['lines'] < $maxLines) {
                        $difference = $maxLines - $service['lines'];
                        if ($difference) {
                            $cellText .= str_repeat("\n ", $difference);
                        }
                    }
                } else if ($column == 'Outcome') {
                    $cellText = $value;
                    if ($outcome['lines'] < $maxLines) {
                        $difference = $maxLines - $outcome['lines'];
                        if ($difference) {
                            $cellText .= str_repeat("\n ", $difference);
                        }
                    }
                } else if ($column == 'Counties') {
                    $cellText = $value;
                    if ($counties['lines'] < $maxLines) {
                        $difference = $maxLines - $counties['lines'];
                        if ($difference) {
                            $cellText .= str_repeat("\n ", $difference);
                        }
                    }
                }
                //d($column, $cellText, $numberLines);
                $this->MultiCell($cellWidth, $cellLineHeight, $cellText, $cellBorder, $cellAlign, 0);
                // Set up for next cell
                // Set up for next cell
                $currentX += $cWidth[$cIndex];
                $currentY = $this->getY() - ($cellLineHeight * $maxLines);
                $this->setXY($currentX, $currentY);
                $cIndex++;
            }

            for ($l = 1; $l <= $maxLines; $l++) {
                $this->Ln();
            }

            $this->setX($rightMargin);
            $this->Cell(470, 0, '', 'T', 0, 0, 0);
        }

        $this->setX($rightMargin);

        $currentY = $this->getY();
        $currentX = $this->getX();

        // Totals
        $this->MultiCell(470, 20, 'Totals: ' . $totals, '3', 'R', 0);
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