<?php

class PdfMySqlTable extends FPDF
{
    var $ProcessingTable=false;
    var $aCols=array();
    var $TableX;
    var $HeaderColor;
    var $RowColors;
    var $ColorIndex;
    var $logo;
    var $footerText;

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
            $this->Cell(0,40,'Children Referred to an Agency (cont.)',0,1,'L');

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
            $this->Cell(0,57.14,'Children Referred to an Agency',0,1,'L');

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

    function PageSummary(){
        $this->SetFont('Arial','B',9);
        $this->Cell(25,10,'Report Created:',0,0,'R');
        $this->SetFont('Arial','',9);
        $this->Cell(40,10,date('F, d, Y'),0,0,'L');
        $this->SetFont('Arial','B',9);
        $this->Cell(25,10,'Created By:',0,0,'R');
        $this->SetFont('Arial','',9);
        $this->Cell(100,10,'DeborahW',0,0,'L');
        $this->Ln(5);

        $this->SetFont('Arial','B',9);
        $this->Cell(25,10,'Family Status:',0,0,'R');
        $this->SetFont('Arial','',9);
        $this->Cell(40,10,'ALL',0,0,'L');
        $this->Ln(5);

        $this->SetFont('Arial','B',9);
        $this->Cell(25,10,'Start Date:',0,0,'R');
        $this->SetFont('Arial','',9);
        $this->Cell(40,10,date('F, d, Y'),0,0,'L');
        $this->SetFont('Arial','B',9);
        $this->Cell(25,10,'End Date:',0,0,'R');
        $this->SetFont('Arial','',9);
        $this->Cell(100,10,date('F, d, Y'),0,0,'L');
        $this->Ln(5);

        $this->SetFont('Arial','B',9);
        $this->Cell(25,10,'Zip Codes:',0,0,'R');
        $this->SetFont('Arial','',9);
        $this->Cell(40,10,'zip code list',0,0,'L');
        $this->SetFont('Arial','B',9);
        $this->Cell(25,10,'School Districts:',0,0,'R');
        $this->SetFont('Arial','',9);
        $this->Cell(100,10,'None',0,0,'L');
        $this->Ln(5);

        $this->SetFont('Arial','B',9);
        $this->Cell(25,10,'Tables Selected:',0,0,'R');
        $this->SetFont('Arial','',9);
        $this->Write(10, 'table list, table, table, table, table, table');
        $this->Ln(15);
    }

    function TableHeader()
    {
        $this->SetFont('Arial','B',8);
        $this->SetTextColor(255, 255, 255);
        $this->SetX($this->TableX);
        $fill=!empty($this->HeaderColor);
        if($fill)
            $this->SetFillColor($this->HeaderColor[0],$this->HeaderColor[1],$this->HeaderColor[2]);
        foreach($this->aCols as $col)
            $this->Cell($col['w'],6,$col['c'],1,0,'C',$fill);
        $this->Ln();
    }

    function Row($data)
    {
        $this->SetX($this->TableX);
        $this->SetFont('Arial','',8);
        $this->SetTextColor(0, 0, 0);
        $ci=$this->ColorIndex;
        $fill=!empty($this->RowColors[$ci]);
        if($fill)
            $this->SetFillColor($this->RowColors[$ci][0],$this->RowColors[$ci][1],$this->RowColors[$ci][2]);
        //var_dump($data);
        foreach($this->aCols as $col){
            $this->Cell($col['w'],5,$data[$col['f']],1,0,$col['a'],$fill);
        }
        $this->Ln();
        $this->ColorIndex=1-$ci;
    }

    function CalcWidths($width,$align)
    {
        //Compute the widths of the columns
        $TableWidth=0;
        foreach($this->aCols as $i=>$col)
        {
            $w=$col['w'];
            if($w==-1)
                $w=$width/count($this->aCols);
            elseif(substr($w,-1)=='%')
                $w=$w/100*$width;
            $this->aCols[$i]['w']=$w;
            $TableWidth+=$w;
        }
        //Compute the abscissa of the table
        if($align=='C')
            $this->TableX=max(($this->w-$TableWidth)/2,0);
        elseif($align=='R')
            $this->TableX=max($this->w-$this->rMargin-$TableWidth,0);
        else
            $this->TableX=$this->lMargin;
    }

    function AddCol($field=-1,$width=-1,$caption='',$align='L')
    {
        //Add a column to the table
        if($field==-1)
            $field=count($this->aCols);
        $this->aCols[]=array('f'=>$field,'c'=>$caption,'w'=>$width,'a'=>$align);
    }

    function Table($query,$prop=array())
    {
        //Issue query
        $res=mysql_query($query) or die('Error: '.mysql_error()."<BR>Query: $query");
        //Add all columns if none was specified
        if(count($this->aCols)==0)
        {
            $nb=mysql_num_fields($res);
            for($i=0;$i<$nb;$i++)
                $this->AddCol();
        }
        //Retrieve column names when not specified
        foreach($this->aCols as $i=>$col)
        {
            if($col['c']=='')
            {
                if(is_string($col['f']))
                    $this->aCols[$i]['c']=ucfirst($col['f']);
                else
                    $this->aCols[$i]['c']=ucfirst(mysql_field_name($res,$col['f']));
            }
        }
        //Handle properties
        if(!isset($prop['width']))
            $prop['width']=0;
        if($prop['width']==0)
            $prop['width']=$this->w-$this->lMargin-$this->rMargin;
        if(!isset($prop['align']))
            $prop['align']='C';
        if(!isset($prop['padding']))
            $prop['padding']=$this->cMargin;
        $cMargin=$this->cMargin;
        $this->cMargin=$prop['padding'];
        if(!isset($prop['HeaderColor']))
            $prop['HeaderColor']=array();
        $this->HeaderColor=$prop['HeaderColor'];
        if(!isset($prop['color1']))
            $prop['color1']=array();
        if(!isset($prop['color2']))
            $prop['color2']=array();
        $this->RowColors=array($prop['color1'],$prop['color2']);
        //Compute column widths
        $this->CalcWidths($prop['width'],$prop['align']);
        //Print header
        $this->TableHeader();
        //Print rows
        $this->SetFont('Arial','',11);
        $this->ColorIndex=0;
        $this->ProcessingTable=true;
        while($row=mysql_fetch_array($res)){
            if(is_null($row[0])){
                $row[0] = 'Total';
            }
            $this->Row($row);
        }
        $this->ProcessingTable=false;
        $this->cMargin=$cMargin;
        $this->aCols=array();
    }

    function TableFromRows($columns=array(),$rows=array(),$prop=array())
    {
        if(count($columns))
        {
            foreach($columns as $field => $value)
                $this->AddCol($field,-1,$value,'L');
        }
        //Handle properties
        if(!isset($prop['width']))
            $prop['width']=0;
        if($prop['width']==0)
            $prop['width']=$this->w-$this->lMargin-$this->rMargin;
        if(!isset($prop['align']))
            $prop['align']='C';
        if(!isset($prop['padding']))
            $prop['padding']=$this->cMargin;
        $cMargin=$this->cMargin;
        $this->cMargin=$prop['padding'];
        if(!isset($prop['HeaderColor']))
            $prop['HeaderColor']=array();
        $this->HeaderColor=$prop['HeaderColor'];
        if(!isset($prop['color1']))
            $prop['color1']=array();
        if(!isset($prop['color2']))
            $prop['color2']=array();
        $this->RowColors=array($prop['color1'],$prop['color2']);
        //Compute column widths
        $this->CalcWidths($prop['width'],$prop['align']);
        //Print header
        $this->TableHeader();
        //Print rows
        $this->SetFont('Arial','',11);
        $this->ColorIndex=0;
        $this->ProcessingTable=true;
        foreach($rows as $row){
            //var_dump($row);
            $this->Row($row);
        }
        $this->ProcessingTable=false;
        $this->cMargin=$cMargin;
        $this->aCols=array();
    }
}
?>