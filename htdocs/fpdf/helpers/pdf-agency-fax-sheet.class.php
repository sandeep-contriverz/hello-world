<?php

class PdfAgencyFaxSheet extends FPDF
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
            $this->Cell(0,40,'Agency Fax Sheet (cont.)',0,1,'L');

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
            $this->Cell(0,57.14,'Agency Fax Sheet',0,1,'L');

            $this->PageSummary();
        }


    }

    // Page footer
    function Footer()
    {
        $this->SetY(-75.42);
        $this->setX(34.28);
        $this->SetFont('Arial','',8);
        $this->Cell(560.42, 9, 'The information contained in this fax may contain confidential information. It is intended only for the use of the person(s) named above. If you', 0, 2, 'L');
        $this->Cell(560.42, 9, 'are not the intended recipient, you are hereby notified that any review, dissemination, distribution, or duplication of this communication is', 0, 2, 'L'); 
        $this->Cell(560.42, 9, 'strictly prohibited. If you are not the intended recipient, please contact the sender by reply email and destroy all copies of the original', 0, 2, 'L');
        $this->Cell(560.42, 9, 'message.', 0, 2, 'L');
        // Position at 1.5 cm from bottom
        $this->Ln(5); 
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

      
        $this->Ln(51.42);

        $currentY = $this->getY();
        // separator
    }

    function FamilyInformation($organization = array(),$provider){
        
        $this->setY($this->getY()+50);
        // Column 1
        $this->ln(10.42);

        //--------------------
        $this->setX(34.28);
        $columnY = $this->getY();
        $this->SetFont('Arial','B',12);
        $this->Cell($this->GetStringWidth('DATE:'),20.87,'DATE:',0,0,'L');
        $this->SetFont('Arial','',11);
        $this->Cell(114.18,20.87,date('F d, Y'),0,0,'L');
        $this->Ln(20); 

        //--------------------

         //--------------------
        $this->setX(34.28);
        $columnY = $this->getY();
        $this->SetFont('Arial','B',12);
        $this->Cell($this->GetStringWidth('TO:'),20.87,'TO:',0,0,'L');
        $this->SetFont('Arial','',11);
        $this->Cell(114.18,20.87,$provider['first'].' '.$provider['last'],0,0,'L');
        $this->Ln(20); 

        //--------------------


        $this->setX(34.28);
        $columnY = $this->getY();
        $this->SetFont('Arial','B',12);
        $this->Cell($this->GetStringWidth('ORGANIZATION:'),20.87,'ORGANIZATION:',0,0,'L');
        $this->SetFont('Arial','',11);
        $this->Cell(114.18,20.87,$organization['name'].(!empty($organization['site'])?':'.$organization['site']:''),0,0,'L');
        $this->Ln(20);       

        $this->setX(34.28);
        $columnY = $this->getY();
        $this->SetFont('Arial','B',12);
        $this->Cell($this->GetStringWidth('FAX: '),20.87,'FAX:',0,0,'L');
        $this->SetFont('Arial','',11);
        $this->Cell(114.18,20.87,$organization['fax'],0,0,'L');
        $this->Ln(20);   
              
        $this->setX(34.28);
        $columnY = $this->getY();
        $this->SetFont('Arial','B',12);
        $this->Cell($this->GetStringWidth('COMMUNITY LIAISON: '),20.87,'COMMUNITY LIAISON:',0,0,'L');
        $this->SetFont('Arial','',11);
        $this->Cell(114.18,20.87,$_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name'],0,0,'L');
        $this->Ln(20);  

        $this->setX(34.28);
        $columnY = $this->getY();
        $this->SetFont('Arial','B',12);
        $this->Cell($this->GetStringWidth('EMAIL:'),20.87,'EMAIL:',0,0,'L');
        $this->SetFont('Arial','',11);
        $this->Cell(114.18,20.87,$_SESSION['user']['email'],0,0,'L');
        $this->Ln(20);  

        $this->setX(34.28);
        $columnY = $this->getY();
        $this->SetFont('Arial','B',12);
        $this->Cell($this->GetStringWidth('# OF PAGES:'),20.87,'# OF PAGES:',0,0,'L');
        $this->SetFont('Arial','',11);
        $this->Cell(114.18,20.87,'',0,0,'L');
        $this->Ln(50);   
              

        $currentY = $this->getY();
        // separator
        $this->Line(34.28, $currentY, 571.42, $currentY);
        $this->Ln(20); 
        $this->setX(34.28);
        $this->SetFont('Arial','',12);
        $this->Cell(540.42, 13, 'Help Me Grow Utah, a specialized branch of 2-1-1, offers parents and providers information,', 0, 2, 'C');
        $this->Cell(540.42, 13, 'community resources and on-going support for child development and family well-being.', 0, 2, 'C');

        $this->Ln(40); 
        $this->setX(34.28);
        $this->SetFont('Arial','',12);
        $this->Cell(540.42, 13, 'Thanks for your partnership! If you have any questions please contact the Community Liaison listed', 0, 2, 'L');
        $this->Cell(540.42, 13, "above. To keep you updated, we'd like to provide the following information:", 0, 2, 'L');
        $this->Ln(30);
        $this->setX(34.028);
        $columnY = $this->getY();
        $this->SetFont('Arial','',11);
        $this->line( $this->getX(),$this->getY()+18.57,$this->getX()+50,$this->getY()+18.57); 
        $this->setX(88.028);  
        $this->Cell($this->GetStringWidth('Referral Report'),28.57,'Referral Report',0,0,'L');
        $this->Ln(30);

        $this->setX(84.028);
        $columnY = $this->getY();
        $this->SetFont('Arial','',11);
        $this->line( $this->getX(),$this->getY()+18.57,$this->getX()+50,$this->getY()+18.57); 
        $this->setX(138.028);  
        $this->Cell($this->GetStringWidth('Referrals from your organization to Help Me Grow'),28.57,'Referrals from your organization to Help Me Grow',0,0,'L');
        $this->Ln(30);

        $this->setX(84.028);
        $columnY = $this->getY();
        $this->SetFont('Arial','',11);
        $this->line( $this->getX(),$this->getY()+18.57,$this->getX()+50,$this->getY()+18.57); 
        $this->setX(138.028);  
        $this->Cell($this->GetStringWidth('Referrals from Help Me Grow to your organization'),28.57,'Referrals from Help Me Grow to your organization',0,0,'L');
        $this->Ln(30);

        $this->setX(34.028);
        $columnY = $this->getY();
        $this->SetFont('Arial','',11);
        $this->line( $this->getX(),$this->getY()+18.57,$this->getX()+50,$this->getY()+18.57); 
        $this->setX(88.028);  
        $this->Cell($this->GetStringWidth('Child/Family Update'),28.57,'Child/Family Update',0,0,'L');
        $this->Ln(30);

        $this->setX(34.028);
        $columnY = $this->getY();
        $this->SetFont('Arial','',11);
        $this->line( $this->getX(),$this->getY()+18.57,$this->getX()+50,$this->getY()+18.57); 
        $this->setX(88.028);  
        $this->Cell($this->GetStringWidth('Other'),28.57,'Other',0,0,'L');
         $this->line( $this->getX()+4,$this->getY()+18.57,$this->getX()+300,$this->getY()+18.57);
        $this->Ln(100);

        

        

        


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