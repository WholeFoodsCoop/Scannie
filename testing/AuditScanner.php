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

class AuditScanner extends ScancoordDispatch
{
    
    protected $title = "Audit Scanner";
    protected $description = "[Audit Scanner] Scan page designed solely for shelf 
        tag auditing.";
    protected $ui = FALSE;
    protected $add_css_content = TRUE;
    
    public function body_content()
    {           
        $ret = '';
        include('../config.php');
        include('../common/lib/scanLib.php');
        include('../common/lib/PriceRounder.php');
        $rounder = new PriceRounder();
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        $storeID = scanLib::getStoreID();
        $upc = str_pad($_POST['upc'], 13, 0, STR_PAD_LEFT);
        
        $ret .= '<div align="center"><h4>AUDIE: THE AUDIT SCANNER</h4></div>';
        $ret .= $this->form_content();
        
        $args = array($storeID,$upc);
        $query = $dbc->prepare("
            SELECT
                p.cost, 
                p.normal_price,  
                p.description,
                p.brand,
                p.default_vendor_id,
                v.vendorName,
                p.department,
                d.dept_name
            FROM products AS p
                LEFT JOIN departments AS d ON p.department=d.dept_no
                LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
            WHERE store_id = ?
                AND upc = ?
            LIMIT 1
        ");
        $result = $dbc->execute($query,$args);
        while ($row = $dbc->fetchRow($result)) {
            $cost = $row['cost'];
            $price = $row['normal_price'];
            $desc = $row['description'];
            $brand = $row['brand'];
            $vendor = '<span class="vid">VID#'.$row['default_vendor_id'].' </span>'.$row['vendorName'];
            $dept = $row['department'].' '.$row['dept_name'];
        }
        if ($dbc->error()) echo $dbc->error();
        
        $margin = ($price - $cost) / $price;
        $rSrp = $cost / (1 - $margin);
        $srp = $rounder->round($rSrp);
        $sMargin = ($srp - $cost ) / $srp;
     
        $ret .= '
            <div align="center">
                <div class="container">
                    <div class="row">
                        <div class="col-xs-4 info" > 
                            <div style="float: left; color: grey">cost</div><br />'.$cost.'
                        </div> 
                        <div class="col-xs-4 info" > 
                            <div style="float: left; color: grey">price</div><br />'.$price.'
                        </div> 
                        <div class="col-xs-4 info" >
                            <div style="float: left; color: grey">margin</div><br />'.sprintf('%0.2f%%',$margin*100).'
                        </div> 
                    </div>
                    <div class="row">
                        <div class="col-xs-4 info" > 
                            <div style="float: left; color: grey"> rSrp </div><br />'.$rSrp.'
                        </div> 
                        <div class="col-xs-4 info" >
                            <div style="float: left; color: grey">Srp</div><br />'.$srp.'
                        </div> 
                        <div class="col-xs-4 info" >
                            <div style="float: left; color: grey">newMarg</div><br />'.sprintf('%0.2f%%',$sMargin).'
                        </div> 
                    </div>
                    <br />
                    <div class="row">
                        <div class="col-xs-12 info" > '.$desc.' </div> 
                    </div>
                    <div class="row">
                        <div class="col-xs-12 info" > '.$brand.' </div> 
                    </div>
                    <div class="row">
                        <div class="col-xs-12 info" > '.$vendor.' </div> 
                    </div>
                    <div class="row">
                        <div class="col-xs-12 info" > '.$dept.' </div> 
                    </div>
                    <div class="container">
                    <br />
                    <div class="row">
                        <div class="col-xs-4  clear btn btn-warning" > <br />Queue <br />To Print <br /><br /></div> 
                        <div class="col-xs-4  clear btn btn-danger" > <br />Notate <br /><br /><br /></div> 
                        <div class="col-xs-4  clear btn btn-success" > <br />Next <br /><br /><br /></div> 
                    </div>
                    <br /><br />
                    <div class="row">
                        <div class="col-xs-12 clear btn btn-surprise" >Exit</div> 
                    </div>
                </div>
            </div>
        ';
        
        $ret .= '<br /><br /><br /><br /><br /><br />';
        
        return $ret;
    }
    
    private function form_content($dbc) 
    {
        
        $upc = str_pad($_POST['upc'], 13, 0, STR_PAD_LEFT);
        $ret .= '';
        $ret .= '
            <div align="center">
                <form method="post" class="form-inline">
                    <input style="text-align: center; width: 140px" class="form-control input-sm" name="upc" value="'.$upc.'">
                </form>
            </div>
        ';
        
        return $ret;
        
    }
    
    public function css_content() 
    {
        return '
            .info {
                opacity: 0.9;
                //background: linear-gradient(#7c7c7c,#272822);
                background: rgba(39, 40, 34, .05);
                border-radius: 2px;
                padding: 5px;
            }
            body {
                background-image: url(\'../common/src/img/lbgrad.png\');
            }
            .vid {
                color: grey;
            }
            .clear {
                opacity: 0.8;
            }
        ';
    }
    
}

ScancoordDispatch::conditionalExec();

 
