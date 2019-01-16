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
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../common/sqlconnect/SQLManager.php');
}
class NewPage extends PageLayoutA 
{

    protected $title = "";
    protected $description = "[] .";
    protected $ui = TRUE;

    public function preprocess()
    {
        $this->displayFunction = $this->pageContent();

        return false;
    }

    public function pageContent()
    {
        include(__DIR__.'/../../config.php');
        $ret = '';
        $dbc = scanLib::getConObj();
        $data = "";

        $reports = array(
            array(
                'handler' => self::getGenericPRIDItems($dbc), 
                'ranges' => array(10, 100, 999),
            ),
            array(
                'handler' => self::getProdMissingCost($dbc), 
                'ranges' => array(10, 100, 999),
            ),
            array(
                'handler' => self::getProdMissingVendor($dbc), 
                'ranges' => array(10, 20, 999),
            ),
            array(
                'handler' => self::getMissingMovementTags($dbc), 
                'ranges' => array(20, 50, 999),
            ),
            array(
                'handler' => self::getVendorList($dbc), 
                'ranges' => array(0, 1, 99),
            ),
            array(
                'handler' => self::getMissingSKU($dbc),
                'ranges' => array(0, 50, 999999),
            ),
            array(
                'handler' => self::getVendorSkuDiscrep($dbc),
                'ranges' => array(0, 1, 9999),
            ),
            array(
                'handler' => self::getProdsMissingLocation($dbc),
                'ranges' => array(0, 100, 99999),
            ),

        );
        $table = "";
        foreach ($reports as $row) {
            $data = $row['handler'];
            $table .= $this->getReportHeader($data, $row['ranges']);
            $table .= self::getTable($data);
        }

        $this->addScript('http://'.$MY_ROOTDIR.'/common/javascript/tablesorter/js/jquery.tablesorter.min.js');
        $this->addScript('http://'.$MY_ROOTDIR.'/common/javascript/tablesorter/js/jquery.metadata.js');
        $this->addOnloadCommand('$(".table").tablesorter();');

        return <<<HTML
<div class="container-fluid">
    <h4>Scanning Department Dashboard</h4>
    $table 
</div>
HTML;
    }

    public function getReportHeader($data, $range)
    {
        $count = number_format(count($data['data']), 0, '.', ',');
        $alert = "";
        if ($count <= $range[0]) {
            $alert = 'alert-success';
        } elseif ($count <= $range[1]) {
            $alert = 'alert-warning';
        } elseif ($count <= $range[2]) {
            $alert = 'alert-danger';
        }
        
        $ret = "";
        $ret .= "<div class='count $alert'>$count</div>";
        $ret .= "<div class='desc'>" . $data['desc'] . "</div>";
        return $ret;
    }
    
    /*
    *   parameters: array data with the following indexes: 'cols', 'data', 'count', 'desc'
    */
    public function getTable($data)
    {
        $tid = substr(md5(microtime()),rand(0,26),5);
        $table = " <button class='btn btn-default btn-collapse' data-target='#table$tid'>view</button><br/>";
        $table .= "<div id='table$tid'><table class='table table-sm table-bordered tablesorter'><thead>";
        foreach ($data['cols'] as $col) {
            $table .= "<th>$col</th>"; 
        }
        $table .= "</thead><tbody>";
        foreach ($data['data'] as $temp) {
            $table .= "<tr>";
            foreach ($data['cols'] as $col) {
                $table .= "<td>{$temp[$col]}</td>";
            }
            $table .= "</tr>";
        }
        $table .= "</tbody></table></div>";
        
        return $table;
    }

    public function empty_template($dbc)
    {
        $desc = "";
        $a = array();
        $p = $dbc->prepare("");
        $r = $dbc->execute($p, $a);
        $cols = array('');
        $data = array();
        while ($row = $dbc->fetchRow($r)) {
            foreach ($cols as $col) $data[$row['upc']][$col] = $row[$col];
        }
        if ($er = $dbc->error()) echo "<div class='alert alert-danger'>$er</div>";

        return array('cols'=>$cols, 'data'=>$data, 'count'=>$count, 
            'desc'=>$desc);
    }

    public function getProdsMissingLocation($dbc)
    {
        $desc = "Products missing physical locations";
        $a = array();
        $p = $dbc->prepare("
            SELECT upc, brand, description, department, store_id
            FROM products AS p
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE upc NOT IN (
                SELECT f.upc
                FROM FloorSectionProductMap AS f
                    INNER JOIN products AS p ON p.upc=f.upc
                    INNER JOIN FloorSections AS s ON f.floorSectionID=s.floorSectionID
                        AND s.storeID=p.store_id
            )
                AND inUse = 1
                AND m.superID IN (1,13,9,4,8,17,5) 
        ");
        $r = $dbc->execute($p, $a);
        $cols = array('upc', 'brand', 'department', 'store_id');
        $data = array();
        while ($row = $dbc->fetchRow($r)) {
            foreach ($cols as $col) $data[$row['upc']][$col] = $row[$col];
        }
        if ($er = $dbc->error()) echo "<div class='alert alert-danger'>$er</div>";

        return array('cols'=>$cols, 'data'=>$data, 'count'=>$count, 
            'desc'=>$desc);
    }

    public function getVendorSkuDiscrep($dbc)
    {
        $desc = "Items with multiple SKUs by Vendor";
        $p = $dbc->prepare("SELECT vendorID FROM vendors
            WHERE vendorID NOT IN (1, 2) ;");
        $r = $dbc->execute($p);
        $vendors = array();
        while ($row = $dbc->fetchRow($r)) {
            $vendors[] = $row['vendorID'];
        }
        $data = array();
        foreach ($vendors as $vid) {
            $a = array($vid, $vid);
            $p = $dbc->prepare("
                SELECT v.sku, v.upc, v.description, v.cost, v.modified, v.vendorID
                FROM vendorItems AS v 
                    INNER JOIN (SELECT * FROM vendorItems WHERE vendorID = ? GROUP BY upc HAVING COUNT(upc)>1) dup ON v.upc = dup.upc WHERE v.vendorID=?
            ");
            $r = $dbc->execute($p,$a);
            $cols = array('upc', 'description', 'modified', 'sku', 'vendorID');
            while ($row = $dbc->fetchRow($r)) {
                foreach ($cols as $col) $data[$row['sku']][$col] = $row[$col];
            }
        }

        return array('cols'=>$cols, 'data'=>$data, 'count'=>$count, 
            'desc'=>$desc);
    }

    public function getMissingSKU($dbc)
    {
        $desc = "Items with recent sales missing SKU";
        $p = $dbc->prepare("
            SELECT p.upc, p.brand, p.description, p.department, p.default_vendor_id AS dvid
            FROM products AS p 
                LEFT JOIN vendorItems AS v ON v.vendorID=p.default_vendor_id
                    AND p.upc=v.upc
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE (v.sku IS NULL OR v.sku=p.upc)
                AND p.inUse = 1
                AND m.superID IN (1,13,9,4,8,17,5) 
                AND p.default_vendor_id NOT IN (0, 1, 2)
                AND p.default_vendor_id > 0 
                AND p.default_vendor_id IS NOT NULL
            GROUP BY p.upc
        ");
        $r = $dbc->execute($p);
        $data = array();
        $cols = array('upc', 'brand', 'description', 'department', 'dvid');
        while ($row = $dbc->fetchRow($r)) {
            foreach ($cols as $col) $data[$row['upc']][$col] = $row[$col];
        }

        return array('cols'=>$cols, 'data'=>$data, 'count'=>$count, 
            'desc'=>$desc);
    }

    public function getVendorList($dbc)
    {
        $desc = "Vendors missing from Vendor Review Schedule";
        $p = $dbc->prepare("SELECT vendorID, vendorName FROM vendors 
            WHERE vendorID NOT IN (SELECT vid AS vendorID FROM vendorReviewSchedule)
            AND vendorID <> -2
            ORDER BY vendorID");
        $r = $dbc->execute($p);
        $data = array();
        $cols = array('vendorID', 'vendorName');
        while ($row = $dbc->fetchRow($r)) {
            foreach ($cols as $col) $data[$row['vendorID']][$col] = $row[$col];
        }

        return array('cols'=>$cols, 'data'=>$data, 'count'=>$count, 
            'desc'=>$desc);
    }

    public function getGenericPRIDItems($dbc)
    {
        $desc = "Products using generic variable pricing rule";
        $data = array();
        $pre = $dbc->prepare("SELECT upc, brand, description, 
            department, default_vendor_id, inUse FROM products WHERE 
            price_rule_id = 1");
        $res = $dbc->execute($pre);
        $count = $dbc->numRows($res);
        $cols = array('upc', 'brand', 'description', 'department',
             'default_vendor_id', 'inUse');
        while ($row = $dbc->fetchRow($res)) {
            foreach ($cols as $col) {
                if ($col == 'inUse' && isset($data[$row['upc']][$col])) {
                    $data[$row['upc']][$col] .= ', '.$row[$col];
                } else {
                    $data[$row['upc']][$col] = $row[$col];
                }
            }
        }
        foreach ($data as $upc => $row) {
            if ($row['inUse'] == '0, 0') $data[$upc]['inUse'] = 'item not in use';
        }

        return array('cols'=>$cols, 'data'=>$data, 'count'=>$count, 
            'desc'=>$desc);
    }

    public function getProdMissingCost($dbc)
    {
        $desc = "Products missing cost";
        $data = array();
        $pre = $dbc->prepare("SELECT upc, brand, description, 
            p.department, default_vendor_id, cost 
            FROM products AS p
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE m.superID IN (1,13,9,4,8,17,5) 
                AND cost = 0 
                AND default_vendor_id > 0
                AND p.inUse = 1
            GROUP BY upc;");
        //$pre = $dbc->prepare("select * from products limit 1");
        $res = $dbc->execute($pre);
        $count = $dbc->numRows($res);
        $cols = array('upc', 'brand', 'description', 'department',
             'default_vendor_id', 'cost');
        while ($row = $dbc->fetchRow($res)) {
            foreach ($cols as $col) $data[$row['upc']][$col] = $row[$col];
        }
        if ($er = $dbc->error()) echo "<div class='alert alert-danger'>$er</div>";

        return array('cols'=>$cols, 'data'=>$data, 'count'=>$count, 
            'desc'=>$desc);

    }

    public function getProdMissingVendor($dbc)
    {
        $desc = "Products not assigned a vendor";
        $data = array();
        $pre = $dbc->prepare("SELECT upc, brand, description, 
            p.department, default_vendor_id, cost 
            FROM products AS p
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE m.superID IN (1,13,9,4,8,17,5) 
                AND default_vendor_id = 0
                AND p.inUse = 1
            GROUP BY upc;");
        //$pre = $dbc->prepare("select * from products limit 1");
        $res = $dbc->execute($pre);
        $count = $dbc->numRows($res);
        $cols = array('upc', 'brand', 'description', 'department',
             'default_vendor_id' );
        while ($row = $dbc->fetchRow($res)) {
            foreach ($cols as $col) $data[$row['upc']][$col] = $row[$col];
        }
        if ($er = $dbc->error()) echo "<div class='alert alert-danger'>$er</div>";

        return array('cols'=>$cols, 'data'=>$data, 'count'=>$count, 
            'desc'=>$desc);

    }

    public function getMissingMovementTags($dbc)
    {
        $desc = "Products missing movement tag rows";
        $data = array();
        $argA = array(1, 2, 1);
        $argB = array(2, 1, 2);
        $pre = $dbc->prepare("
            SELECT upc, brand, description, created, ? AS store_id
            FROM products AS p 
                left join MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE upc not in (select upc from MovementTags where storeID = ?) 
                AND m.superID IN (1, 4, 5, 9, 13, 17) 
                AND p.upc NOT IN ('0000000001330','0000000001341','0000000001342')
                AND store_id = ? 
            GROUP by p.upc;
        ");
        $res = $dbc->execute($pre, $argA);
        $count = $dbc->numRows($res, $argA);
        $cols = array('upc', 'brand', 'description', 'created', 'store_id');
        $i = 0;
        while ($row = $dbc->fetchRow($res)) {
            foreach ($cols as $col) $data[$i][$col] = $row[$col];
            $i++;
        }
        $res = $dbc->execute($pre, $argB);
        $count = $dbc->numRows($res, $argB);
        $cols = array('upc', 'brand', 'description', 'created', 'store_id');
        while ($row = $dbc->fetchRow($res)) {
            foreach ($cols as $col) $data[$i][$col] = $row[$col];
            $i++;
        }

        return array('cols'=>$cols, 'data'=>$data, 'count'=>$count, 
            'desc'=>$desc);
    }

    public function javascriptContent()
    {
        return <<<JAVASCRIPT
$(document).ready(function(){
    $('.btn-collapse').each(function(){
        $(this).trigger('click');
    });
});
$('.btn-collapse').click(function(){
    var target = $(this).attr('data-target');
    $(target).toggle();
    //$(target).show();
});
JAVASCRIPT;
    }

    public function cssContent()
    {
return <<<HTML
.btn-collapse {
    background: rgba(0,0,0,0);
    color: #84B3FF;
    padding: 0px;
}
div.list {
    display: inline-block;
    width: 400px;
}
div.count {
    display: inline-block;
    width: 50px;
    margin-right: 5px;
}
div.desc {
    display: inline-block;
    width: 400px;
}
h4 {
    padding-top: 25px;
    padding-bottom: 25px;
}
HTML;
    }

}
WebDispatch::conditionalExec();
