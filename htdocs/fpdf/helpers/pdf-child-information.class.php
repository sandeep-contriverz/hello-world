<?php

class PdfChildInfo extends FPDF
{
    var $ProcessingTable=false;
    var $aCols=array();
    var $TableX;
    var $HeaderColor;
    var $RowColors;
    var $ColorIndex;
    var $logo;
    var $footerText;
    var $familyInfo = array();
    var $hmgWorker = 'Un-assigned';
    var $createdBy = '';

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
            $this->Cell(0,40,'Child Information (cont.)',0,1,'L');

            // Reset Y
            $this->PageSummary();

            $this->FamilyInformation($this->familyInfo);

            //$this->displayTableHeaders();

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
            $this->Cell(0,57.14,'Child Information',0,1,'L');

            $this->PageSummary();
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

    function setCreatedBy($text = '')
    {
        $this->createdBy = $text;
    }

    function SetHmgWorker($text = '')
    {
        $this->hmgWorker = $text;
    }

    function PageSummary(){

        $this->setX(371.42);
        $this->SetFont('Arial','B',9);
        $this->Cell(71.43,20.87,'Report Created:',0,0,'R');
        $this->SetFont('Arial','',9);
        $this->Cell(114.28,20.87,date('F, d, Y'),0,0,'L');
        $this->Ln(10.43);

        $this->setX(371.42);
        $this->SetFont('Arial','B',9);
        $this->Cell(71.43,20.87,'Created By:',0,0,'R');
        $this->SetFont('Arial','',9);
        $this->Cell(280.70,20.87,$this->createdBy,0,0,'L');
        $this->Ln(10.43);

        $this->setX(371.42);
        $this->SetFont('Arial','B',9);
        $this->Cell(71.43,20.87,'HMG Worker:',0,0,'R');
        $this->SetFont('Arial','',9);
        $this->Cell(114.28,20.87,$this->hmgWorker,0,0,'L');
        $this->Ln(10.43);

        // $this->setX(371.42);
        // $this->SetFont('Arial','B',9);
        // $this->Cell(71.43,20.87,'Phone Number:',0,0,'R');
        // $this->SetFont('Arial','',9);
        // $this->Cell(114.28,20.87,'801-691-5322',0,0,'L');
        $this->Ln(51.42);

        $currentY = $this->getY();
        // separator
        $this->Line(20.87, $currentY, 571.42, $currentY);
    }

    function FamilyInformation($info = array()){

        $this->familyInfo = $info; // Store family information to display this again

        // Column 1
        $this->ln(10.42);
        $this->setX(34.28);
        $columnY = $this->getY();
        $this->SetFont('Arial','B',12);
        $this->Cell($this->GetStringWidth('Parent Name: '),20.87,'Parent Name:',0,0,'L');
        $this->SetFont('Arial','',11);
        $this->Cell(114.18,20.87,$info['parentName'],0,0,'L');
        $this->Ln(20);

        $this->setX(34.28);
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Relationship: '),20.87,'Relationship:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(285.71,20.87,$info['relationship'],0,0,'L');
        $this->Ln(20);

        $this->setX(34.28);
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Address: '),20.87,'Address:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(114.18,20.87,$info['full_address'],0,0,'L');
        $this->Ln(20);

        $this->setX(34.28);
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('County: '),20.87,'County:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(114.18,20.87,$info['county'],0,0,'L');
        $this->Ln(20);

        $this->setX(34.28);
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Phone: '),20.87,'Phone:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(114.18,20.87,$info['primary_phone'],0,0,'L');

        // Column 2
        $this->ln(14.28);
        $this->setY($columnY);
        $this->setX(342.85);
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Email: '),20.87,'Email:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(114.18,20.87,$info['email'],0,0,'L');
        $this->Ln(34.28);

        $this->setX(342.85);
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Child Name: '),20.87,'Child Name:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(114.18,20.87,$info['childName'],0,0,'L');
        $this->Ln(20);

        $this->setX(342.85);
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Gender: '),20.87,'Gender:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(114.18,20.87,$info['gender'],0,0,'L');

    }

    function ProviderInformation($info = array()){

        $this->setY($this->getY() + 50);
        // Column 1
        $this->setX(28.57);
        $this->SetFont('Arial','B',10);
        $this->Cell($this->GetStringWidth('Provider Information '),28.57,'Provider Information',0,0,'L');
        $this->Ln(22.85);

        $currentY = $this->getY();
        // separator
        $this->Line(28.57, $currentY, 571.42, $currentY);

        // Column 1
        $this->ln(5);
        $this->setX(34.28);
        $columnY = $this->getY();
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Provider Name: '),28.57,'Provider Name:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(114.28,28.57,$info['provider'],0,0,'L');
        $this->Ln(20);

        $this->setX(34.28);
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Office/Clinic Name: '),28.57,'Office/Clinic Name:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(285.71,28.57,$info['employer'],0,0,'L');
        $this->Ln(28.57);
    }

    function ScreeningInformationHeader($columns, $cWidth, $cont = false){

        $pageY = $this->getY();
        if($pageY + 72 > 770){
            $this->addPage();
        }

        $this->setX(28.57);
        $this->SetFont('Arial','B',10);
        $headline = 'Screening Information ' . ($cont ? ' (cont.)' : '');
        $this->Cell($this->GetStringWidth($headline),28.57,$headline,0,0,'L');
        $this->Ln(22);

        $currentY = $this->getY();
        // separator
        $this->Line(28, $currentY, 571.85, $currentY);
        $this->Ln(22);

        $this->displayHeader($columns, $cWidth);
    }

    function ScreeningInformation($columns = array(), $data = array()){

        $cWidth = array(108.57, 108.57, 108.57, 108.57, 108.57);

        $this->ScreeningInformationHeader($columns, $cWidth);

        // Display Table

        $this->setFillColor(205, 205, 205);
        $this->SetTextColor(70, 70, 70);
        $this->SetFont('Arial', '', 8);
        foreach($data as $row){
            $cellBorder = '1';
            $cellLinePadding = '';
            $cellLineHeight = 11;
            $currentY = $this->getY();
            $currentX = $this->getX();
            $cIndex = 0;
            $notesLength = 0;//$this->GetStringWidth($row['Notes on the Score']);
            $numberLines = 1;
            if($notesLength > 257.14){
                $numberLines = ceil($notesLength / 257.14);
                $cellBorder = "LR";
                //$cellLineHeight = $numberLines * $cellLineHeight;
            }
            // Figure out the next row wil cause a page break
            $pageY = $currentY + ($cellLineHeight * $numberLines);

            if($pageY > 770){

                // Adds line to bottom
                $this->addPage();
                $this->setY(240);

                $this->ScreeningInformationHeader($columns, $cWidth, true);

                $currentY = $this->getY();

                // TODO: Figure out why we get an extra cell when going
                // to a new page.
                $maxY = $currentY;

                $currentY = $this->getY();
                $currentX = $this->getX();
                $cellLineHeight = 11;
            }

            foreach($row as $column => $value){
                $cellAlign = 'C';
                if($cIndex == 5){
                    $cellAlign = 'L';
                }
                $cellWidth = $cWidth[$cIndex];
                if($numberLines > 1){
                    $cellLinePadding = str_repeat("\n ", $numberLines - 1);
                }
                $cellText = $value . ($column != 'Notes on the Score' ? $cellLinePadding : '');
                $textWidth = ($cellText ? $this->GetStringWidth($cellText) : 0);
                $this->MultiCell($cellWidth, $cellLineHeight, $cellText , $cellBorder, $cellAlign, 0);
                // Set up for next cell
                $currentX += $cWidth[$cIndex];
                $currentY = $this->getY() - ($cellLineHeight * $numberLines);
                $this->setXY($currentX, $currentY);
                $cIndex++;
            }
            for($l = 1; $l <= $numberLines; $l++){
                $this->Ln();
            }
            $this->Cell(542.85, 0, '', 'T', 0, 0, 0);
            $this->Ln();
        }

        $this->Ln(4);
    }

    function ResourceInformationHeader($columns, $cWidth, $cont = false){

        $pageY = $this->getY();
        if($pageY + 72 > 770){
            $this->addPage();
        }

        $this->setX(28.57);
        $this->SetFont('Arial','B',10);
        $headline = 'Resources Given ' . ($cont ? ' (cont.)' : '');
        $this->Cell($this->GetStringWidth($headline),28.57,$headline,0,0,'L');
        $this->Ln(22);

        $currentY = $this->getY();
        // separator
        $this->Line(28, $currentY, 571.85, $currentY);
        $this->Ln(22);

        // display header again
        $this->displayHeader($columns, $cWidth);
    }


    function ResourceInformation($columns = array(), $data = array()){

        // Display Table
        //$cWidth = array(114.28, 114.28, 57.14, 57.14, 200);
        $cWidth = array(135.71, 135.71, 135.71, 135.71);

        $this->ResourceInformationHeader($columns, $cWidth);

        $this->setFillColor(205, 205, 205);
        $this->SetTextColor(70, 70, 70);
        $this->SetFont('Arial', '', 8);
        $count = 1;
        foreach($data as $row){
            $cellBorder = '1';
            $cellLinePadding = '';
            $cellLineHeight = 11;
            $currentY = $this->getY();
            $currentX = $this->getX();
            $cIndex = 0;
            $numberLines = array();
            $maxLines = 0;
            $maxY = 0;

            $referral = $this->NbLines($row['Referral Name'], 135.71);
            $service = $this->NbLines($row['Service'], 135.71);
            $outcome = $this->NbLines($row['Outcome'], 135.71);
            $date = $this->NbLines($row['Ref. Date'], 135.71);
            //$notes = $this->NbLines($row['Notes'], 200);

            //$row['Notes'] = $notes['wrapped'];

            $numberLines[] = $referral['lines'];
            $numberLines[] = $service['lines'];
            $numberLines[] = $outcome['lines'];
            $numberLines[] = $date['lines'];
            //$numberLines[] = $notes['lines'];

            //echo '<pre>'; var_dump($numberLines);

            $maxLines = max($numberLines);

            // Figure out the next row wil cause a page break
            $pageY = $currentY + ($cellLineHeight * $maxLines);

            if($pageY > 770){

                // Adds line to bottom
                $this->addPage();
                $this->setY(235);

                $this->ResourceInformationHeader($columns, $cWidth, true);

                $currentY = $this->getY();

                // TODO: Figure out why we get an extra cell when going
                // to a new page.
                $maxY = $currentY;

                $currentY = $this->getY();
                $currentX = $this->getX();
                $cellLineHeight = 11;

            }

            foreach($row as $column => $value){
                $cellLinePadding = '';
                $cellAlign = 'C';
                if($cIndex == 4){
                    $cellAlign = 'L';
                }
                $cellBorder = 'LR';
                $cellWidth = $cWidth[$cIndex];
                if($column == 'Referral Name'){
                    $cellText = $value;
                    if($referral['lines'] < $maxLines){
                        $difference = $maxLines - $referral['lines'];
                        if($difference){
                            $cellText .= str_repeat("\n ", $difference);
                        }
                    }
                } else if($column == 'Service'){
                    $cellText = $value;
                    if($service['lines'] < $maxLines){
                        $difference = $maxLines - $service['lines'];
                        if($difference){
                            $cellText .= str_repeat("\n ", $difference);
                        }
                    }
                } else if($column == 'Outcome'){
                    $cellText = $value;
                    if($outcome['lines'] < $maxLines){
                        $difference = $maxLines - $outcome['lines'];
                        if($difference){
                            $cellText .= str_repeat("\n ", $difference);
                        }
                    }
                } else if($column == 'Notes'){
                    $cellText = $value;
                    if($notes['lines'] < $maxLines || true){
                        $difference = $maxLines - $notes['lines'];
                        if($difference){
                            $cellText .= str_repeat("\n ", $difference);
                        }
                    }
                    //var_dump($cellText);
                } else if($column == 'Ref. Date') {
                    $cellText = $value;
                    if($date['lines'] < $maxLines){
                        $difference = $maxLines - $date['lines'];
                        if($difference){
                            $cellText .= str_repeat("\n ", $difference);
                        }
                    }
                }
                $this->MultiCell($cellWidth, $cellLineHeight, $cellText, $cellBorder, $cellAlign, 0);
                $newY = $this->getY();
                if($newY > $maxY){
                    $maxY = $newY;
                }
                // Set up for next cell
                $currentX += $cWidth[$cIndex];
                $this->setXY($currentX, $currentY);
                $cIndex++;
            }

            $this->setY($maxY);
            // Adds line to bottom
            $this->Cell(542.85, 0, '', 'T', 0, 0, 0);
            $this->Ln();

        }

    }

    // Display Header
    function displayHeader($columns, $cWidth, $type = ''){
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