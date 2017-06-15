<?php

class PdfOrgCertificate extends FPDF
{
    protected $logo;
    protected $lineColors;

    function SetLogoImage($imgPath = '')
    {
        $this->logo = $imgPath;
    }

    function SetLineColors($lineColors)
    {
        $this->lineColors = $lineColors;
    }

    // Page header
    function Header()
    {
        $this->Image('images/right_strip.png', 150, 100, 695);
        $this->Image('images/left_strip.png', 0, 0, 165);

        $this->SetTextColor(58, 58, 58);
        $this->AddFont('verdanab','','verdanab.php');
        $this->SetFont("verdanab", "", "40");
        $this->Text(185, 85, "World's Best Organization!");
    }

    function PageBody($parentName = '', $childName = ''){

        $this->SetTextColor(58, 58, 58);

        $this->setXY(135, 200);
        $curX = 175;

        $this->SetFont("Arial", "", "19");
        $this->Text($curX, $this->getY(), "This certificate is awarded to");
        $this->Ln(60);

        $this->SetFont("Arial", "", "42");
        $this->Text($curX, $this->getY(), $parentName);
        $this->Ln(45);

        $this->SetFont("Arial", "", "20");
        $this->Text($curX, $this->getY(), "in recognition of completing the Developmental Screening");
        $this->Ln(35);

        $this->Text($curX, $this->getY(), "Program with Help Me Grow Utah for");
        $this->Ln(60);

        $this->SetFont("Arial", "", "42");
        $this->Text($curX, $this->getY(), $childName);


        $this->setY(-140);
        $this->SetFont("Arial", "", "14");
        $this->setX($curX);
        $this->SetFillColor(0);
        $this->Cell(250, 24, "Care Coordinator", 'T', '', 'L');
        // Line break
        $this->Ln(10);

        $this->setY(-85);
        $this->setX($curX);
        $this->SetFillColor(0);
        $this->Cell(120, 24, "Date", 'T', '', 'L');

        $this->Image($this->logo, 520, 420, 300);
    }

    // Page footer
    function Footer()
    {

    }
}
?>