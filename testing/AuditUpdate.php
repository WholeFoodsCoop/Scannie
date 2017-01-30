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
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(__FILE__)) . '/common/sqlconnect/SQLManager.php');
}

class AuditUpdate {
    
    public function main()
    {
        
        $ret = '';
        include('../config.php');
        include('../common/lib/scanLib.php');
        include('../common/lib/PriceRounder.php');
        include('../common/lib/PriceLib.php');
        $rounder = new PriceRounder();
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        $store_id = $_POST['store_id'];
        $upc = $_POST['upc'];
        
        $this->print_queue_handler($dbc,$upc,$store_id);
        
        return false;

    }
    
    private function print_queue_handler($dbc,$upc,$store_id)
    {
        
        if ($store_id == 1) {
            $shelftagid = 13;
        } else {
            $shelftagid = 26;
        }
        
        $args = array($upc,$store_id);
        $prep = $dbc->prepare("
            SELECT 
                p.upc,
                p.description,
                p.normal_price,
                p.brand,
                v.sku,
                p.size,
                v.units,
                vendors.vendorName
            FROM products AS p 
                LEFT JOIN vendorItems AS v ON p.default_vendor_id = v.vendorID
                LEFT JOIN vendors ON p.default_vendor_id = vendors.vendorName
            WHERE p.upc = ?
                AND p.store_id = ?
        ");
        $res = $dbc->execute($prep,$args);
        while ($row = $dbc->fetchRow($res)) {
            $upc = $row['upc'];
            $desc = $row['description'];
            $price = $row['normal_price'];
            $brand = $row['brand'];
            $sku = $row['sku'];
            $size = $row['size'];
            $units = $row['units'];
            $vendor = $row['vendorName'];
            $pricePerUnit = PriceLib::pricePerUnit($price,$size);
        }
        if ($dbc->error()) echo '<div class="alert alert-warning>' . $dbc->error() . '</div>';
        
        $uArgs = array($shelftagid,$upc,$desc,$price,$brand,$sku,$size,$units,$vendor,$pricePerUnit);
        $uPrep = $dbc->prepare("
            INSERT INTO shelftags (
                id, upc, description, normal_price, brand, sku, size, units, vendor, pricePerUnit
            ) VALUES (
                ? , ? , ? , ? , ? , ? , ? , ? , ? , ?  
            );
        "); 
        $dbc->execute($uPrep,$uArgs);
        if ($dbc->error()) echo '<div class="alert alert-warning>' . $dbc->error() . '</div>';
        
        echo '
            <div class="alert alert-success"> 
                '.$upc.'<br />
                <a href="" onClick="$(\'#ajax-resp\').hide(); return false;" style="float: right; color: darkgreen; font-weight: 300">X</a> <br />
                Sent to Print Queue 
            </div>
        ';
    }
    
} 

$obj = new AuditUpdate;
$obj->main();


  
  
  

 
