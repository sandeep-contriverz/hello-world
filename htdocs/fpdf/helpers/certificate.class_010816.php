<?php

class PdfCertificate extends FPDF
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
        // horizontal thick line
        $this->SetFillColor(
            $this->lineColors['horz-thick-line-color'][0],
            $this->lineColors['horz-thick-line-color'][1],
            $this->lineColors['horz-thick-line-color'][2]
        );
        $this->Rect(0, 110, 842, 18, 'F');


        // stroke vertical line
        if (isset($this->lineColors['stroke-line-color'])) {
            $this->SetFillColor(
                $this->lineColors['stroke-line-color'][0],
                $this->lineColors['stroke-line-color'][1],
                $this->lineColors['stroke-line-color'][2]
            );
            $this->Rect(49, 0, 52, 595, 'F'); 
        }

        // vertical line
        $this->SetFillColor(
            $this->lineColors['vertical-line-color'][0],
            $this->lineColors['vertical-line-color'][1],
            $this->lineColors['vertical-line-color'][2]
        );
        $this->Rect(50, 0, 50, 595, 'F');

        // horizontal thin line
        $this->SetFillColor(
            $this->lineColors['horz-thin-line-color'][0],
            $this->lineColors['horz-thin-line-color'][1],
            $this->lineColors['horz-thin-line-color'][2]
        );
        $this->Rect(0, 133, 842, 4, 'F');

        $this->SetTextColor(58, 58, 58);
        $this->AddFont('CenturyGothic','','CenturyGothic.php');
        $this->SetFont("CenturyGothic", "", "45");
        $this->Text(135, 70, "World's Best Parent!");
    }

    function PageBody($parentName = '', $childName = ''){

        $this->SetTextColor(58, 58, 58);

        $this->setXY(135, 180);
        $curX = 135;

        $this->SetFont("CenturyGothic", "", "20");
        $this->Text($curX, $this->getY(), "This certificate is awarded to");
        $this->Ln(60);

        $this->SetFont("CenturyGothic", "", "42");
        $this->Text($curX, $this->getY(), $parentName);
        $this->Ln(45);

        $this->SetFont("CenturyGothic", "", "20");
        $this->Text($curX, $this->getY(), "in recognition of completing");
        $this->Ln(30);

        $this->Text($curX, $this->getY(), "the Developmental Screening Program with Help Me Grow for");
        $this->Ln(60);

        $this->SetFont("CenturyGothic", "", "42");
        $this->Text($curX, $this->getY(), $childName);


        $this->setY(-100);
        $this->SetFont("CenturyGothic", "", "14");
        $this->setX($curX);
        $this->Cell(300, 24, "Care Coordinator", 'T', '', 'C');
        $this->setX($curX+325);
        $this->Cell(100, 24, "Date", 'T', '', 'C');

        $this->Image($this->logo, 625, 400, 150);
    }

    // Page footer
    function Footer()
    {

    }
}
?>