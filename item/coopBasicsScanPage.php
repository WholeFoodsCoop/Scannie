<!--/*******************************************************************************

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
    
*********************************************************************************/-->
<?php session_start(); ?>
<html>
<head>
  <title> Coop Basics Sign Scan </title>
  <link rel="stylesheet" href="../common/bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="../common/css/darkpages.css">
  <script src="../common/bootstrap/jquery.min.js"></script>
  <script src="../common/bootstrap/bootstrap.min.js"></script>
<style>
</style>
</head>
<body>

<?php
include('../../../../../var/www/html/git/fannie/config.php');
if (!class_exists('FannieAPI')) {
    include('../../../../../var/www/html/git/fannie/classlib2.0/FannieAPI.php');
}
/**
*   @class coopBasicsScanPage  - Hillside
*
*   coopBasicsScanPage specifically checks for Coop Basics 
*   missing signs for Hillside, using the Hillside scancoord 
*   shelftag queue (id=13). 
*   Requires list of Coop Basics UPCs to be uploaded to 
*   woodshed'...SaleChangeQueues which currently must be done 
*   manually.
*
*   Instructions on use:
*   
*   1. Upload Coop Basics Checklist to Generic Uploads (Excel Upload in Office).
*   2. Veriphy that $saleItems is generating a list of items on sale. 
*   3. 
*/
class coopBasicsScanPage
{
    
    protected $title = 'Coop Basics Scan Page';
    
    public function view()
    {        
    
        include('../config.php');
        include('../common/lib/scanLib.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        
        $store_id = scanLib::getStoreID();
        if($store_id == 2) {
            $shelftagid = 26;
            echo '<h4>Coop Basics Review for <strong>Denfeld</strong></h4>';
        } else {
            $shelftagid = 13;
            echo '<h4>Coop Basics Review for <strong>Hillside</strong></h4>';
        }
        
        echo "don't forget to upload Coop Basics checklist to Generic Upload.<br>";
        
        if (isset($_GET['session'])) {
            $session = substr($_GET['session'],0,-1);
            echo $session  . "<br>";
        }
        
        $list = array();
        $queryA = ("
            SELECT upc FROM is4c_op.GenericUpload;
        ");
        $resA = $dbc->query($queryA);
        while ($row = $dbc->fetchRow($resA))  {
            $list[] = $row['upc'];  
        }
        
        if (count($list) < 1) {
            echo '<span class="danger">No scanned items were found.<br>
                Check that barcodes are in the correct queue.<br>
                Check your query.</span>';
        }
        
        $scanned = array();
        $queryB = ('SELECT * FROM shelftags WHERE id = 13');
        $resB = $dbc->query($queryB);
        while ($row = $dbc->fetchRow($resB))  {
            $scanned[] = $row['upc'];
        }
        
        $saleitems = array();
        $queryC = ('select bl.upc from batchList as bl left join batches as b on bl.batchID=b.batchID where CURDATE() between b.startDate and b.endDate group by upc;');
        $resC = $dbc->query($queryC);
        while ($row = $dbc->fetchRow($resC))  {
            $saleitems[] = $row['upc'];
        }
       
       
        
        $missing = array();
        $remove = array();
        foreach ($scanned AS $key => $upc) {
            if (in_array($upc,$list)) {
                //  do nothing
            } else {
                $remove[] = $upc;
            }
        }
        foreach ($list AS $key => $upc) {
            if (in_array($upc,$scanned)) {
                //  do nothing
            } else {
                $missing[] = $upc;
            }
        }
        foreach ($missing as $key => $upc) {
            if (in_array($upc,$saleitems)) {
                $missing[$key] = NULL;
            } else {
                //  do nothing
            }
        }
        $notInUse = array();
        $curM = date('m');
        foreach ($missing as $key => $upc) {
            $query = ("select last_sold from products where upc = {$upc} and store_id = 1");
            $res = $dbc->query($query);
            while ($row = $dbc->fetchRow($res))  {
                if (substr($row['last_sold'],0,4) < 2016) {
                    unset($missing[$key]);
                    
                } elseif (substr($row['last_sold'],5,2) < ($curM-2)) {
                    unset($missing[$key]);
                    $notInUse[] = $upc;
                } else {
                    //  do nothing
                }
            }
        }
        
        foreach ($missing as $key => $upc) {
            if (is_null($upc)) unset($missing[$key]);
        }
        
        echo '<br>Signs that are missing, apparently<br>----------------------------------<br>';
        foreach ($missing as $upc) echo $upc . '<br>';
        echo '<br>Signs that are on the floor that should be taken down. . . apparently<br>---------------------------------------------------------------------<br>';
        foreach ($remove as $upc) echo $upc . '<br>';
        echo '<br>These items should be marked as not in use for your store,
            <br>they have not sold in at least 2 months<br>---------------------------------------------------------<br>';
        echo '<span class="danger">This list has now been made irrelevant by product inUse automation.<br></span>';
        foreach ($notInUse as $upc) echo $upc . '<br>';
        
        
        return false;
    }
    
    private function form_content($dbc)
    {
        
        $sessions = array();
        $query = ('select session, store_id from woodshed_no_replicate.SaleChangeQueues group by session;');
        $res = $dbc->query($query);
        while ($row = $dbc->fetchRow($res))  {
            $sessions[] = $row['session'] . $row['store_id'];
        }
        
        $ret = '';
        $ret .= '
            <form method="get" class="form-inline">
                <select class="invisInput" name="store_id">
                    <option value="1">Hillside</option>
                    <option value="2">Denfeld</option>
                </select>
                
        <input type="submit" class="invisInput" value="Submit"></form>';
        
        return $ret;
    }
    
    
}
coopBasicsScanPage::view();

/*
<select class="invisInput" name="session">
                    <option class="" name="" value="">Select a Session</option>';            
        foreach ($sessions as $key => $session) {
            $ret .= '
                    <option class="" value="' . $session  . '">' . substr($session,0,-1) . '</option>
            ';    
        }
        </select>&nbsp;
*/