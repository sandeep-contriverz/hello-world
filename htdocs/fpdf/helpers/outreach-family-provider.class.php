<?php

class PdfProviderFamilies extends FPDF
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
            $this->Image($this->logo,0,8.57,114.28);
            // Arial bold 15
            $this->SetFont('CenturyGothic','',25);
            // Set position
            $this->setY(0);
            $this->setX(125.71);
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

        $this->SetY(-105);
        $this->Cell(571.42, 20.87, '*Status indicates whether a HMG family file is open (Active) or closed (Inactive)', 0, 0, 'L');
        $this->Ln(10);
        $this->Cell(571.42, 20.87, '**Screenings include ASQ-3 and/or ASQ-SE', 0, 0, 'L');
        $this->Ln(10);
        $this->Cell(571.42, 20.87, '***Referrals are resources given for this specific child', 0, 0, 'L');
        $this->Ln(10);
        $this->Cell(571.42, 20.87, '****Inquiry refers either to a family that could not be reached or a family who\'s question was answered and they did not enroll in HMG.', 0, 0, 'L');

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

    function ProviderInformation($info = array()){

        $this->provider = $info;

        // Column 1
        $this->ln(10.42);
        $this->setX(34.28);
        $columnY = $this->getY();
        $this->SetFont('Arial','B',12);
        $this->Cell($this->GetStringWidth('Provider Name: '),20.87,'Provider Name:',0,0,'L');
        $this->SetFont('Arial','',11);
        $this->Cell(114.18,20.87,$info['name'],0,0,'L');
        $this->Ln(20);

        $this->setX(34.28);
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Provider Office/Clinic: '),20.87,'Provider Office/Clinic:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(285.71,20.87,$info['employer'],0,0,'L');
        $this->Ln(20);

        $this->setX(34.28);
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Work Address: '),20.87,'Work Address:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(114.18,20.87,$info['full_address'],0,0,'L');
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
        $this->Ln(22.85);
        $this->setFillColor(205, 205, 205);
        $this->SetTextColor(70, 70, 70);
        $this->SetFont('Arial', 'B', 9);
        $currentY = $this->getY();
        $currentX = $this->getX();
        $cWidth = array(60, 119, 109, 51.14, 80,  62, 56);
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

        // Display Table
        $this->setFillColor(205, 205, 205);
        $this->SetTextColor(70, 70, 70);
        $this->SetFont('Arial', '', 8);

        foreach($data as $row){

            $sumScreenings += $row['Number of Screenings**'];
            $sumReferrals += $row['Number of Referrals***'];

            $cellBorder = '1';
            $cellLinePadding = '';
            $cellLineHeight = 11;
            $currentY = $this->getY();
            $currentX = $this->getX();
            $cIndex = 0;
            //$notesLength = $this->GetStringWidth($row['Notes on the Score']);
            $numberLines = 1;
            // if($notesLength > 257.14){
            //     $numberLines = ceil($notesLength / 257.14);
            //     $cellBorder = "LR";
            //     //$cellLineHeight = $numberLines * $cellLineHeight;
            // }
            //echo '<pre>'; var_dump($row); exit;
            foreach($row as $column => $value){
                $cellAlign = 'L';
                if(in_array($cIndex, array(3, 5, 6))){
                    $cellAlign = 'C';
                }
                $cellWidth = $cWidth[$cIndex];
                if($numberLines > 1){
                    $cellLinePadding = str_repeat("\n ", $numberLines - 1);
                }
                $cellText = $value ; //. ($column != 'Notes on the Score' ? $cellLinePadding : ''); add line padding to a cell value
                $textWidth = ($cellText ? $this->GetStringWidth($cellText) : 0);
                $this->MultiCell($cellWidth, $cellLineHeight, $cellText , $cellBorder, $cellAlign, 0);
                // Set up for next cell
                $currentX += $cWidth[$cIndex];
                $currentY = $this->getY() - 11;
                $this->setXY($currentX, $currentY);
                $cIndex++;
            }
            for($l = 1; $l <= $numberLines; $l++){
                $this->Ln();
            }
            $this->Cell(537, 0, '', 'T', 0, 0, 0);
            $this->Ln();
        }

        $currentY = $this->getY();
        $currentX = $this->getX();

        // Totals
        $this->MultiCell('278', $cellLineHeight, 'Total Number of Children: ' . $totalRecords, '1', 'R', 0);

        $currentX += 278;
        $this->setXY($currentX, $currentY);
        $this->MultiCell('141.14', $cellLineHeight, 'Totals: ', '1', 'R', 0);

        $currentX += 141.14;
        $this->setXY($currentX, $currentY);
        $this->MultiCell('62', $cellLineHeight, $sumScreenings, '1', 'C', 0);

        $currentX += 62;
        $this->setXY($currentX, $currentY);
        $this->MultiCell('56', $cellLineHeight, $sumReferrals, '1', 'C', 0);

        $this->Ln(4);
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