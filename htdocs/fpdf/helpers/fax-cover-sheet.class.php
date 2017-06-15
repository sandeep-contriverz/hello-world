<?php

class PdfFaxCoverSheet extends FPDF
{
    var $logo;
    var $footerText;

    function SetLogoImage($imgPath = '')
    {
        $this->logo = $imgPath;
    }

    function SetFooterText($text = '')
    {
        $this->footerText = $text;
    }

    // Page header
    function Header()
    {
        $this->AddFont('helvetica','','helvetica.php');
        $this->AddFont('helveticab','B','helveticab.php');
        $this->AddFont('helveticai','I','helveticai.php');

        // Logo
        #$this->Image('images/united-way-squarelogo.png',440,-25,150);
        $this->Image($this->logo,425,14,150);
        $this->setY(100);
        $this->setX(40);
        $this->SetFont('helveticab','B', 28);
        $this->Cell(50,20,'Help Me Grow Utah',0,0,'L');
        #$this->Image('images/fax_cover_sheet_text.png',40,100,250);
    }

    function PageBody($provider = array('name' => null, 'fax' => null), $coordinator = null){


        $this->setY(150);
        $this->setX(40);
        $this->SetFont('helvetica','', 10);
        $this->Cell(50,20,'DATE:',0,0,'L');
        $this->SetFont('helvetica','',10);
        $this->Cell(100,20,date('F d, Y'),0,0,'L');
        $this->Ln(25);

        $this->setX(40);
        $this->SetFont('helvetica','', 10);
        $this->Cell(50,20,'TO:',0,0,'L');
        $this->SetFont('helvetica','',10);
        $this->Cell(280.70,20.87,$provider['name'],0,0,'L');
        $this->Ln(25);

        $this->setX(40);
        $this->SetFont('helvetica','', 10);
        $this->Cell(50,20,'FAX:',0,0,'L');
        $this->SetFont('helvetica','',10);
        $this->Cell(100,20,$provider['fax'],0,0,'L');
        $this->Ln(25);

        $this->setX(40);
        $this->SetFont('helvetica','', 10);
        $this->Cell(50,20,'FROM:',0,0,'L');
        $this->SetFont('helvetica','',10);
        $this->Cell(100,20,'Help Me Grow',0,0,'L');
        $this->Ln(25);

        $this->setX(40);
        $this->SetFont('helvetica','', 10);
        $this->Cell(120,20,'CARE COORDINATOR:',0,0,'L');
        $this->SetFont('helvetica','',10);
        $this->Cell(100,20,$coordinator,0,0,'L');
        $this->Ln(25);

        $this->setX(40);
        $this->SetFont('helvetica','', 10);
        $this->Cell(100,20,'# OF Pages (including cover sheet):',0,0,'L');
        $this->SetFont('helvetica','',10);
        $this->Ln(35);

        $this->SetLineWidth(1.0);
        $this->Line(40, $this->getY(), 540, $this->getY());

        $text1 = 'Help Me Grow is a specialized unit of 2-1-1 that provides parents with reliable';
        $text2 = 'information and referrals to locate community services';

        $this->Ln(20);
        $this->Cell(540, 12, $text1, 0, 1, 'C');
        $this->Cell(540, 12, $text2, 0, 1, 'C');
        $this->Ln(10);

        $this->SetFont('helveticab','B', 11);
        $this->setX(40);
        $this->Cell(20, 20, '', 'B', 'L', 0);
        $this->setY($this->getY() + 6);
        $this->setX(60);
        $this->Cell(100, 20, 'This is a follow-up to a referral you gave us.', 0, 1, 'L');
        $this->Ln(30);

        $text3 = 'One of your patients has requested that you receive the following information';
        $this->SetFont('helveticai','I', 11);
        $this->Text(40, $this->getY(), $text3);
        $this->Ln(15);

        $this->SetFont('helvetica','', 10);

        $this->setX(40);
        $this->Cell(20, 20, '', 'B', 'L', 0);
        $this->setY($this->getY() + 6);
        $this->setX(60);
        $this->Cell(100, 20, 'ASQ Summary Sheet', 0, 1, 'L');
        $this->Ln(15);

        $this->setX(40);
        $this->Cell(20, 20, '', 'B', 'L', 0);
        $this->setY($this->getY() + 6);
        $this->setX(60);
        $this->Cell(100, 20, 'List of referrals given to family', 0, 1, 'L');
        $this->Ln(15);

        $this->setX(40);
        $this->Cell(20, 20, '', 'B', 'L', 0);
        $this->setY($this->getY() + 6);
        $this->setX(60);
        $this->Cell(100, 20, 'Follow-up information on resources', 0, 1, 'L');
        $this->Ln(15);
    }
    // Page footer
    // function Footer()
    // {

    //     // Position at 1.5 cm from bottom
    //     $this->SetY(-51.42);
    //     //$this->Image('images/counts-footer.png',45,$this->getY(),120);
    //     //
    //     $this->Line(20.87, $this->getY(), 571.42, $this->getY());
    //     $this->SetTextColor(100, 100, 100);
    //     // Arial italic 8
    //     $this->SetFont('Arial','',11);
    //     // Page number
    //     $this->Cell(571.42, 20.87, $this->footerText, 0, 2, 'L');
    //     $this->Cell(555,12,'Page '.$this->PageNo().' of '. '{nb}',0,0,'R');
    // }
    // Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-100);


        $this->SetFont('helveticab', 'B', 10);
        $this->setX(10);
        $this->Cell(570, 20, 'To schedule a brief in-service on how Help Me Grow can work for you,', 0, 0, 'C');
        $this->Ln(15);
        $this->setX(20);
        $this->Cell(570, 20, 'call 801-691-5322. Help Me Grow will provide a light lunch.', 0, 0, 'C');
        $this->Ln(25);

        $this->setX(10);
        $this->SetTextColor(100, 100, 100);
        // helvetica italic 8
        $this->SetFont('helvetica','',10);

        $footerPieces = explode(' | ', $this->footerText);
        $website = array_pop($footerPieces);
        $footerText = implode(' | ', $footerPieces);

        // Page number
        $this->Cell(571.42, 20.87, $footerText, 0, 0, 'C');
        $this->Ln(15);

        $this->setX(10);
        $this->SetFont('helveticab','B',10);
        $this->Cell(571.42, 20.87, $website, 0, 1, 'C');
    }
}
?>