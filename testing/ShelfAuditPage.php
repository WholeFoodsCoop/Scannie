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

class ShelfAuditPage extends ScancoordDispatch
{
    
    protected $title = "Shelf Audit";
    protected $description = "[Shelf Audit] Track info. on shelftags scanned for auditing purposes.";
    protected $ui = TRUE;
    
    public function body_content()
    {           
        $ret = '';
        
        include('../config.php');
        include('../common/lib/PriceRounder.php');
        include('../common/lib/scanLib.php');
        $rounder = new PriceRounder();
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        

        if($_GET['upc']) {
            $_GET['upc'] = trim($_GET['upc']);
            $upc = str_pad($_GET['upc'], 13, 0, STR_PAD_LEFT);
        }

        $store_id = scanLib::getStoreID();
        if($store_id == 2) {
            $queue = 26;
            $ret .= 'Shelftag Queues Data for <strong>Denfeld</strong>';
        } else {
            $queue = 13;
            $ret .= 'Shelftag Queues Data for <strong>Hillside</strong>';
        }
        
        
        $ret .= $this->form_content();
        
        $queryUnfi = $dbc->prepare("
            select 
                s.upc, 
                p.description, 
                s.normal_price, 
                p.cost, 
                round((s.normal_price-p.cost)/s.normal_price,3) as margin, 
                vd.margin AS unfiMarg, 
                p.department, 
                d.dept_name, 
                p.default_vendor_id, 
                round(p.cost / (1 - vd.margin),2) as SRP,
                v.vendorName, 
                p.price_rule_id
            from shelftags as s 
                left join products as p on s.upc=p.upc left join departments as d on p.department=d.dept_no 
                left join vendors as v on p.default_vendor_id=v.vendorID 
                LEFT JOIN vendorItems AS vi ON vi.upc = p.upc AND vi.vendorID = p.default_vendor_id
                    LEFT JOIN vendorDepartments AS vd 
                        ON vd.vendorID = p.default_vendor_id 
                            AND vd.deptID = vi.vendorDept
            where s.id = ?
                and p.default_vendor_id=1
            group by p.upc 
            order by p.department, p.upc;");
            
        $queryNonUNFI = $dbc->prepare("
            select 
                s.upc, 
                p.description, 
                s.normal_price, 
                p.cost, 
                round((s.normal_price-p.cost)/s.normal_price,3) as margin, 
                d.margin AS unfiMarg, 
                p.department, 
                d.dept_name, 
                p.default_vendor_id, 
                round(p.cost / (1 - d.margin),2) as SRP,
                v.vendorName, 
                p.price_rule_id
            from shelftags as s 
                left join products as p on s.upc=p.upc 
                left join departments as d on p.department=d.dept_no 
                left join vendors as v on p.default_vendor_id=v.vendorID 
                LEFT JOIN vendorItems AS vi ON vi.upc = p.upc AND vi.vendorID = p.default_vendor_id
                    LEFT JOIN vendorDepartments AS vd 
                        ON vd.vendorID = p.default_vendor_id 
                            AND vd.deptID = vi.vendorDept
            where s.id = ?
                and p.default_vendor_id<>1
            group by p.upc 
            order by p.department;
        ");
        $result = $dbc->execute($queryUnfi,$queue);
        $data = array();
        $headers = array();
        $i = 0;
        //  Add UNFI items to data
        while ($row = $dbc->fetch_row($result)) {
            foreach ($row as $k => $v) {
                if(!is_numeric($k)) {
                    $data[$i][$k] =  $v;
                    $headers[$k] = $k;
                }
            }
            $i++;
        }
        $result = $dbc->execute($queryNonUNFI,$queue);
        //  Add NON-UNFI items to table
        while ($row = $dbc->fetch_row($result)) {
            foreach ($row as $k => $v) {
                if(!is_numeric($k)) {
                    $data[$i][$k] =  $v;
                    $headers[$k] = $k;
                }
            }
            $i++;
        }
        
        //  Add a column
        $i = 0;
        $flags = array();
        foreach ($data as $k => $array) { 
            $newColumnName = 'priceoffmarg';
            $srp = $data[$k]['SRP'];
            $price = $data[$k]['normal_price'];
            $difference = sprintf("%0.3f",$srp - $price);
            //$data[$i][$newColumnName] = $difference;
            //$headers[$newColumnName] = $newColumnName;
            
            if ($difference > 0.2 && $rounder->round($srp) != $price) $flags['danger'][] = $i;
            
            if (
                ($rounder->round($data[$i]['SRP']) != $data[$i]['normal_price'])
                && abs($data[$i]['normal_price'] - $rounder->round($data[$i]['SRP']) > 0.14) 
            ) {
                //  This is a price redux.
                if ($curDiff = $data[$i]['normal_price'] - $rounder->round($data[$i]['SRP']) > 0) {
                    if ($data[$i]['SRP'] < $rounder->round($data[$i]['SRP'])) {
                        $flags['warning'][] = $i;
                    }
                } 
                //  This is a price increase.
                else {
                    if ($data[$i]['SRP'] > $rounder->round($data[$i]['SRP'])) {
                        $flags['warning'][] = $i;
                    }
                }
            }

            $i++;
        }
        
        $ret .=  '<div class="panel panel-default">
            <table class="table table-striped">';
        $ret .=  '<thead>';
        foreach ($headers as $v) {
            $ret .=  '<th>' . $v . '</th>';
        }
        $ret .=  '</thead>';
        $prevKey = '1';
        $ret .=  '<tr>';
        foreach ($data as $k => $array) { 
            foreach ($array as $kb => $v) {
                //  mod values in table
                if ($kb == 'SRP') {
                    $v = $v . ' | ' . $rounder->round($v);
                    $ret .=  '<td> ' . $v . '</td>'; 
                } else {
                    $ret .=  '<td> ' . $v . '</td>'; 
                }
            }
            if($prevKey != $k) {
                if (in_array(($k+1),$flags['danger'])) {
                    $ret .=  '</tr><tr class="" style="background-color:tomato;color:white">';
	                } elseif (in_array(($k+1),$flags['warning'])) {
                    $ret .=  '</tr><tr class="" style="background-color:#FFF457;">';
                } else {
                    $ret .=  '</tr><tr>';
                }
            } 
            
            $prevKey = $k;
        }
        $ret .=  '</table></div>';
        
        /*
        $upcLink = "<a href='http://key/git/fannie/item/ItemEditorPage.php?searchupc=" 
                . $upc . "&ntype=UPC&searchBtn=' target='_blank'>{$upc}</a>";
        */
        if ($dbc->error()) $ret .=  $dbc->error();
        
        return $ret;
    }
    
    private function form_content()
    {
        return '
                <form class ="form-inline"  method="get" > 
                    <br>
                    <div class="form-group">    
                        <select class="form-control" name="storeID" autofocus>
                            <option value="">Change Store</option>
                            <option value="1">Hillside</option>
                            <option value="2">Denfeld</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-default" value="go">
                    </div>
                </form>
        ';
    }
    
}

ScancoordDispatch::conditionalExec();

 
