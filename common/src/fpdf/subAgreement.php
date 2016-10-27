<?php
require('fpdf.php');

class PDF extends FPDF
{

    function Header()
    {
        $this->Image('subAgreement.png',0,0,200);  
    }
    
    function AutoFill($meminfo)
    {
        $this->SetFont('Times','',10);
        
        $this->SetXY(15,94);
        $this->Cell(0,0,$payFull, 0, 1);
      
        $this->SetXY(15,106);
        $this->Cell(0,0,$paySome, 0, 1);
        
        $this->SetXY(125,106);
        $this->Cell(0,0,$payPlus, 0, 1);
        
        $this->SetXY(60,113);
        $this->Cell(0,0,$payBal, 0, 1);
        
        $this->SetXY(135,113);
        $this->Cell(0,0,$payInc, 0, 1);
        
        $this->SetXY(53,134);
        $this->Cell(0,0,$nameOwn, 0, 1);
        
        $this->SetXY(20,153);
        $this->Cell(0,0,$nameOne, 0, 1);
       
        $this->SetXY(75,153);
        $this->Cell(0,0,$nameTwo, 0, 1);
        
        $this->SetXY(135,153);
        $this->Cell(0,0,$nameThree, 0, 1);
        
        $this->SetXY(36,161);
        $this->Cell(0,0,$addy, 0, 1);
       
        $this->SetXY(146,161);
        $this->Cell(0,0,$apt, 0, 1);
        
        $this->SetXY(22,169);
        $this->Cell(0,0,$city, 0, 1);
       
        $this->SetXY(98,170);
        $this->Cell(0,0,$state, 0, 1);
        
        $this->SetXY(149,170);
        $this->Cell(0,0,$zip, 0, 1);
        
        $this->SetXY(25,178);
        $this->Cell(0,0,$phoneMain, 0, 1);
        
        $this->SetXY(104,179);
        $this->Cell(0,0,$phoneAlt, 0, 1);
        
        $this->SetXY(24,186);
        $this->Cell(0,0,$email, 0, 1);
        
        $this->SetXY(169,188);
        $this->Cell(0,0,$mailYes, 0, 1);
        
        $this->SetXY(176,188);
        $this->Cell(0,0,$mailNo, 0, 1);
        
        $this->SetXY(35,208);
        $this->Cell(0,0,$signature, 0, 1);
        
        $this->SetXY(142,209);
        $this->Cell(0,0,$date, 0, 1);
        
        $this->SetXY(40,231);
        $this->Cell(0,0,$card_no, 0, 1);

        $this->SetXY(151,232);
        $this->Cell(0,0,$stock, 0, 1);
    }
    
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();;
$pdf->AutoFill();
$pdf->Output();

