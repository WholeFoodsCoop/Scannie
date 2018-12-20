<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Community Co-op.
    
    This file is a part of Scannie.
    
    Scannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Scannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file LICENSE along with Scannie; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
    
*********************************************************************************/

include('../config.php');
if (!class_exists('ScancoordDispatch')) {
    include($SCANROOT.'/common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(__FILE__)) . '/common/sqlconnect/SQLManager.php');
}

class generic_page extends ScancoordDispatch
{
    
    protected $title = "";
    protected $description = "[] ";
    protected $ui = TRUE;
    
    public function body_content()
    {           
        $ret = '';
        include('../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        
        $card_no = 15952;
        $args = array();
        $query = $dbc->prepare("select * from is4c_trans.dlog_90_view where card_no = 15952");
        $result = $dbc->execute($query,$args);
        $data = array();
        $amount = array();
        $skip = array('Discount','Change','Credit Card','Tax','Coupons','Donations');
        while ($row = $dbc->fetchRow($result)) {
            if (!in_array($row['description'],$skip)) {
                if (!$data[$row['description']]) {
                    $data[$row['description']] = $row['quantity'];
                } else {
                    $data[$row['description']] += $row['quantity'];
                }
                $amount[$row['description']] = $row['total'];
            }
        }
        if ($dbc->error()) echo $dbc->error();

        asort($data,SORT_NUMERIC);
        $ret .= "<h5>Purchases for Owner # $card_no</h4>";
        $ret .= "<table class='table table-condensed small' style='max-width: 500px;'>";
        foreach ($data as $desc => $qty) {
            $ret .= "<tr><td>$desc</td><td>$qty</td><td>$$amount[$desc]</td></tr>";
        }
        $ret .= "</table>";
        
        return $ret;
    }
    
    private function form_content($dbc) 
    {
        
        $ret .= '';
        
        return $ret;
        
    }
    
}

ScancoordDispatch::conditionalExec();

 
