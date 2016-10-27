<?php
require('fpdf.php');

class PDF extends FPDF
{

    function Header()
    {
        $this->Image('subAgreement.png',0,0,200);  
    }
    
    function AutoText()
    {
        
    }

}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();;

$pdf->SetFont('Times','',10);
//  Payment in full checkbox
$pdf->SetXY(15,94);
$pdf->Cell(0,0,$payFull, 0, 1);
//  Payment of 20 checkbox
$pdf->SetXY(15,106);
$pdf->Cell(0,0,$paySome, 0, 1);
//  Plus payment of 
$pdf->SetXY(125,106);
$pdf->Cell(0,0,$payPlus, 0, 1);
//  Leaving balance of
$pdf->SetXY(60,113);
$pdf->Cell(0,0,$payBal, 0, 1);
//  Payment incremented by
$pdf->SetXY(135,113);
$pdf->Cell(0,0,$payInc, 0, 1);
//  Full Name of Voting Owner
$pdf->SetXY(53,134);
$pdf->Cell(0,0,$nameOwn, 0, 1);
//  Additional Household Owner 1
$pdf->SetXY(20,153);
$pdf->Cell(0,0,$nameOne, 0, 1);
//  " " Owner 2
$pdf->SetXY(75,153);
$pdf->Cell(0,0,$nameTwo, 0, 1);
//  " " Owner 3
$pdf->SetXY(135,153);
$pdf->Cell(0,0,$nameThree, 0, 1);
//  Street Address
$pdf->SetXY(36,161);
$pdf->Cell(0,0,$addy, 0, 1);
//  Apt
$pdf->SetXY(146,161);
$pdf->Cell(0,0,$apt, 0, 1);
//  City
$pdf->SetXY(22,169);
$pdf->Cell(0,0,$city, 0, 1);
//  State
$pdf->SetXY(98,170);
$pdf->Cell(0,0,$state, 0, 1);
//  Zip Code
$pdf->SetXY(149,170);
$pdf->Cell(0,0,$zip, 0, 1);
//  Phone 
$pdf->SetXY(25,178);
$pdf->Cell(0,0,$phoneMain, 0, 1);
//  Alt Phone
$pdf->SetXY(104,179);
$pdf->Cell(0,0,$phoneAlt, 0, 1);
//  Email
$pdf->SetXY(24,186);
$pdf->Cell(0,0,$email, 0, 1);
//  Mailings YES
$pdf->SetXY(169,188);
$pdf->Cell(0,0,$mailYes, 0, 1);
//  Mailings NO
$pdf->SetXY(176,188);
$pdf->Cell(0,0,$mailNo, 0, 1);
//  Signature
$pdf->SetXY(35,208);
$pdf->Cell(0,0,$signature, 0, 1);
//  Date
$pdf->SetXY(142,209);
$pdf->Cell(0,0,$date, 0, 1);
//  Owner Number
$pdf->SetXY(40,231);
$pdf->Cell(0,0,$card_no, 0, 1);
//  Dollar Amount of Stock Purchased
$pdf->SetXY(151,232);
$pdf->Cell(0,0,$stock, 0, 1);

$pdf->Output();

