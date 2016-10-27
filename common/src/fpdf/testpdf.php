<?php
require('fpdf.php');

class PDF extends FPDF
{

    function Header()
    {
        $this->Image('letterhead.png',0,0,200);
        $this->SetFont('Arial','B',15);
        $this->Ln(25);
        $this->Cell(80);    
        $this->Cell(20,10,'SUBSCRIPTION AGREEMENT',0,1,'C');
        $this->Line($this->GetX()+85,$this->GetY(),$this->GetX()+95,$this->GetY());
    }

    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        // Page number
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();;

$pdf->SetFont('Times','',10);
$pdf->Ln(5);
$pdf->Cell(0,0,'I hereby subscribe and intend to purchase $20.00 of Class A voting stock and $80.00 of Class B equity stock. I understand that this', 0, 1);
$pdf->Ln(5);
$pdf->Cell(0,0,'application is subject to the approval of the Board of Directors and that my membership is subject to the Bylaws of Whole Foods', 'C');
$pdf->Ln(5);
$pdf->Cell(0,0,'Community Co-op, Inc./WFC.', 'C');
$pdf->Ln(10);
$pdf->Cell(0,0,"I have been informed of WFC's ENDS Statement and agree to further it. I agree to comply with and be bound by the terms and");
$pdf->Ln(5);
$pdf->Cell(0,0,"conditions relating to membership contained in the Articles of Incorporation, Bylaws and amendments thereto, and the policies");
$pdf->Ln(5);
$pdf->Cell(0,0,"enacted by the Board of Directors.");

$pdf->SetFont('Times','B',12);
$pdf->Ln(10);
$pdf->Cell(0,0,"Payment Plan Options:");
$pdf->Ln(10);

$pdf->SetFont('Times','',10);
$pdf->Cell(0,0,"____ Payment in full of $100.00 by cash, check or credit/debit card");



$pdf->Ln(5);
$pdf->SetFont('Times','',8);
$pdf->Cell(0,0, "           NOTE: If you had a previous membership in your name, valid equity from that membership may apply toward this equity requirement.");
$pdf->Ln(10);
$pdf->SetFont('Times','',10);
$pdf->Cell(0,0,"____ Payment of at least $20.00 for Class A voting stock plus playment of $ ______________ for Class B equity stock");
$pdf->Ln(5);
$pdf->Cell(0,0,"         leaving a balance of $ ________ payable in any increment by __________________________________");
$pdf->Ln(5);
$pdf->SetFont('Times','',8);
$pdf->Cell(0,0,"           (one year from the date of this Subscription Agreement). NOTE: MATCHING FUNDS to meet the Class B stock obligation may");
$pdf->Ln(5);
$pdf->Cell(0,0,"           be availalbe for Owners who qualify based on household income.");
$pdf->SetFont('Times','',10);

$pdf->SetFont('Times','B',12);
$pdf->Ln(10);
$pdf->Cell(0,0,"Owner Information:");
$pdf->Ln(10);

$pdf->SetFont('Times','',10);
$pdf->Cell(0,0,"Full Name of Voting Owner: _________________________________________________________________________"); 
$pdf->Ln(10);
$pdf->Cell(0,0,"1 ______________________________ 2 ______________________________ 3 ______________________________");
$pdf->Ln(8);
$pdf->CELL(0,0,"Street Address: __________________________________________________ Apt: ____________________________");




$pdf->Output();

