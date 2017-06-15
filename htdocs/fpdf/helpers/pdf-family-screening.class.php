<?php

class PdfFamilyScreeningInfo extends FPDF
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
            

            // Reset Y
            $this->PageSummary();

            

            //$this->displayTableHeaders();

        } else {
            // Logo
            
            // Arial bold 15
            $this->SetFont('Arial','B',22);
            // Set position
            $this->setY(20);
            $this->setX(170.28);            
            $this->Cell(200,30.12,'Edinburgh Screening Summary',0,1,'L');
            $this->Image($this->logo,34.28,10,114.28);

            $this->PageSummary();
            
        }


    }

    // Page footer
    function Footer()
    {
        $this->setX(20.87); 
        // Position at 1.5 cm from bottom
        $this->SetY(-81.42);
        //$this->Image('images/counts-footer.png',45,$this->getY(),120);
        //
        $this->SetFont('Arial','',8);
        $this->Cell(540.42, 9, 'Help Me Grow Utah utilizes the Edinburgh Postnatal Depression Scale (EPDS) with parents during the perinatal period to check on emotional ', 0, 2, 'C');
        $this->Cell(540.42, 9, 'well-being.We consider this a tool to help connect them to the appropriate information and resources. If you have questions please contact us. ', 0, 2, 'C');
        $this->Line(20.87, $this->getY(), 571.42, $this->getY());
        $this->SetTextColor(100, 100, 100);
        // Arial italic 8
        $this->SetFont('Arial','',11);
        // Page number
        
        $this->Cell(571.42, 20.87, $this->footerText, 0, 2, 'L');
        //$this->Cell(555,12,'Page '.$this->PageNo().' of '. '{nb}',0,0,'R');
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

        $this->Ln(21.42);
        $currentY = $this->getY();
        
    }

    function FamilyInformation($info = array()){

        $this->familyInfo = $info; // Store family information to display this again

        // Column 1
        
        $this->setX(34.28);
        $columnY = $this->getY()+50;
        $this->SetFont('Arial','B',12);
        $this->Cell($this->GetStringWidth('Parent Name: '),20.87,'Family Information',0,0,'L');
        $this->Ln(22.85);

        $this->setX(34.28);
        $columnY = $this->getY();
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Parent Name: '),20.87,'Parent Name:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(114.18,20.87,$info['parentName'],0,0,'L');
        $this->Ln(20);
        

        $this->setX(34.28);
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Address: '),20.87,'Address:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(114.18,20.87,$info['full_address'],0,0,'L');
        $this->Ln(20);

        $this->setX(34.28);
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Care Coordinator: '),20.87,'Care Coordinator:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(114.18,20.87,$info['care'],0,0,'L');
        $this->Ln(20);

        

    }

    function ScreeningInformation($info = array(),$setting){

        $this->setY($this->getY() + 20);
        // Column 1
        $this->setX(34.28);
        $this->SetFont('Arial','B',12);
        $this->Cell($this->GetStringWidth('Screening Information '),28.57,'Screening Information',0,0,'L');
        $this->Ln(22.85);

        $currentY = $this->getY();
        

        // Column 1
        $this->ln(5);
        $this->setX(34.28);
        $columnY = $this->getY();
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Screening Type: '),28.57,'Screening Type:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(114.28,28.57,$setting->getValue($info['type']),0,0,'L');
        $this->Ln(20);
        $this->setX(34.28);
        $columnY = $this->getY();
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Screening Interval: '),28.57,'Screening Interval:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(114.28,28.57,$setting->getValue($info['type_interval']),0,0,'L');
        $this->Ln(20);
        $this->setX(34.28);
        $columnY = $this->getY();
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Date Completed: '),28.57,'Date Completed:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(114.28,28.57, ($info['date_sent'] != '0000-00-00' ? date('m/d/y',strtotime($info['date_sent'])) : '') ,0,0,'L');
        $this->Ln(20);
        $this->setX(34.28);
        $columnY = $this->getY();
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Self Harm Rating: '),28.57,'Self Harm Rating:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(114.28,28.57,$setting->getValue($info['harm_rating']),0,0,'L');
        $this->Ln(20);
        $this->setX(34.28);
        $columnY = $this->getY();
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('Numerical Score: '),28.57,'Numerical Score:',0,0,'L');
        $this->SetFont('Arial','',9);
        $this->Cell(114.28,28.57,$info['ed_score'],0,0,'L');
        $this->Ln(20);

        $this->setY($this->getY() + 20);
        
        $this->SetDrawColor(0,0,0);
        $this->SetFillColor(255,255,255);
        $this->Rect(70,$this->getY(),110,15,'DF');
        $this->SetFillColor(0,0,0);
        $this->Rect(180,$this->getY()-10,2,40,'DF');
        $this->SetFillColor(192,192,192);
        $this->Rect(182,$this->getY(),140,15,'DF');
        $this->SetFillColor(0,0,0);
        $this->Rect(320,$this->getY()-10,2,40,'DF');
        $this->SetFillColor(0,0,0);
        $this->Rect(322,$this->getY(),200,15,'DF');
        $this->SetFillColor(0,0,0);
        $this->Rect(520,$this->getY()-10,2,40,'DF');

        $this->setY($this->getY() + 12);
        $this->setX(90);
        $this->SetFont('Arial','',7);
        $this->Cell($this->GetStringWidth('Little to no concern'),18.57,'Little to no concern',0,0,'L');

        
        $this->setX(220);
        $this->SetFont('Arial','',7);
        $this->Cell($this->GetStringWidth('Some Concern '),18.57,'Some Concern ',0,0,'L');

        
        $this->setX(410);
        $this->SetFont('Arial','',7);
        $this->Cell($this->GetStringWidth('Concern '),18.57,'Concern ',0,0,'L');

        $this->setY($this->getY() + 18);
        $this->setX(175);
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('5'),18.57,'5',0,0,'L');

        
        $this->setX(315);
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('10'),18.57,'10',0,0,'L');

        
        $this->setX(510);
        $this->SetFont('Arial','B',9);
        $this->Cell($this->GetStringWidth('30'),18.57,'30',0,0,'L');
        
        
    }

    function FollowInformation($info = array(),$setting){

        $this->setY($this->getY() + 40);
        // Column 1
        $this->setX(34.28);
        $this->SetFont('Arial','B',12);
        $this->Cell($this->GetStringWidth('Follow-up Plan with Parent '),28.57,'Follow-up Plan with Parent',0,0,'L');
        $this->Ln(22.85);

        $currentY = $this->getY();
        

        // Column 1
        $this->ln(5);
        $this->setX(50);
        $this->SetFont('Arial','',11);
        $this->Image('images/box.jpg',50,$this->getY()+10,10);
        $this->setX(70);
        $columnY = $this->getY();
        $this->SetFont('Arial','',9);
        $this->Cell($this->GetStringWidth('Help Me Grow was unable to contact the parent'),28.57,'Help Me Grow was unable to contact the parent',0,0,'L');
        $this->Ln(20);

        $this->setX(50);
        $this->SetFont('Arial','',11);
        $this->Image('images/box.jpg',50,$this->getY()+10,10);
        $this->setX(70);
        $this->setX(70);
        $columnY = $this->getY();
        $this->SetFont('Arial','',9);
        $this->Cell($this->GetStringWidth('Share results with health care provider'),28.57,'Share results with health care provider',0,0,'L');
        $this->Ln(20);

        $this->setX(50);
        $this->SetFont('Arial','',11);
        $this->Image('images/box.jpg',50,$this->getY()+10,10);
        $this->setX(70);
        $columnY = $this->getY();
        $this->SetFont('Arial','',9);
        $this->Cell($this->GetStringWidth('The parent was given informational resources'),28.57,'The parent was given informational resources',0,0,'L');
        $this->Ln(20);

        $this->setX(50);
        $this->SetFont('Arial','',11);
        $this->Image('images/box.jpg',50,$this->getY()+10,10);
        $this->setX(70);
        $columnY = $this->getY();
        $this->SetFont('Arial','',9);
        $this->Cell($this->GetStringWidth('The parent received a referral:'),28.57,'The parent received a referral:',0,0,'L');
        $this->Ln(20);

        $this->setX(100);
        $columnY = $this->getY();
        $this->SetFont('Arial','',9);
        $this->Cell($this->GetStringWidth('Support Group'),28.57,'Support Group',0,0,'L');
        $this->line( $this->getX()+3,$this->getY()+18.57,$this->getX()+200,$this->getY()+18.57);
        $this->Ln(20);

        $this->setX(100);
        $columnY = $this->getY();
        $this->SetFont('Arial','',9);
        $this->Cell($this->GetStringWidth('Therapy'),28.57,'Therapy',0,0,'L');
        $this->line( $this->getX()+3,$this->getY()+18.57,$this->getX()+225,$this->getY()+18.57);
        $this->Ln(20);

        $this->setX(100);
        $columnY = $this->getY();
        $this->SetFont('Arial','',9);
        $this->Cell($this->GetStringWidth('Other'),28.57,'Other',0,0,'L');
        $this->line( $this->getX()+3,$this->getY()+18.57,$this->getX()+235,$this->getY()+18.57);
        $this->Ln(20);

        $this->setX(50);
        $this->SetFont('Arial','',11);
        $this->Image('images/box.jpg',50,$this->getY()+10,10);
        $this->setX(70);
        $columnY = $this->getY();
        $this->SetFont('Arial','',9);
        $this->Cell($this->GetStringWidth('Help Me Grow Utah will re-screen in'),28.57,'Help Me Grow Utah will re-screen in',0,0,'L');
        $this->line( $this->getX()+6,$this->getY()+16.57,$this->getX()+30,$this->getY()+16.57);
        $this->setX($this->getX()+30);    
        $this->Cell($this->GetStringWidth('weeks'),28.57,'weeks',0,0,'L');

        $this->Ln(20);


        
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