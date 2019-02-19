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
        $d = new DateTime();
        $datetime = $d->format('Y-m-d H:i');

        $reports = array(
            array(
                'handler' => self::getGenericPRIDItems($dbc), 
                'ranges' => array(10, 100, 999),
            ),
            array(
                'handler' => self::getProdMissingCost($dbc), 
                'ranges' => array(100, 200, 999),
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
                'ranges' => array(0, 100, 9999),
            ),
            array(
                'handler' => self::getProdsMissingLocation($dbc),
                'ranges' => array(50, 100, 99999),
            ),
            array(
                'handler' => self::getMissingScaleItems($dbc),
                'ranges' => array(1, 2, 999),
            ),
            array(
                'handler' => self::badPriceCheck($dbc),
                'ranges' => array(1, 2, 999),
            ),
        );

        $muData = $this->multiStoreDiscrepCheck($dbc);
        $multi = $this->getReportHeader(array('desc'=>'Discrepancies between stores', 'data'=>$muData['data']), array(5, 10, 999));
        $multi .= " <button class='btn btn-default btn-collapse' data-target='#tableMulti'>view</button><br/>";
        $multi .= "<div id='tableMulti' class='table-responsive-lg'>";
        $multi .= "<div class='card'><div class='card-body' style='overflow-x: scroll'>";
        $multi .= $muData['table'] . "</div></div></div>";

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
    <div style="margin-top: 20px;"></div>
    <div class="card">
        <div class="card-content">
            <div class="card-body">
                <div class="card-title">
                    <h4>Scanning Department Dashboard <span class="smh4"><strong>Page last updated:</strong> $datetime</span></h4>
                </div>
                $table 
                $multi
            </div>
        </div>
    </div>
    <div style="margin-top: 20px;"></div>
    <div class="card">
        <div class="card-content">
            <div class="card-body">
                <div class="card-title">
                    <legend>Scan Utilities</legend>
                </div>
                <ul>
                    <li>Scan POS for  
                        <a href="#" onclick="
                            $('#specIframe').css('display', 'block'); 
                            $('#specIframe').attr('src', 'http://key/scancoord/dataScanning/specialPriceCheck.php');
                            var h = $('#specIframe').outerHeight();
                            h += parseInt(h, 10);
                            $('#specIframe').css('height', h+'px');
                            this.preventDefault;
                        "
                        >Sale Price Discrepancies</a>
                    </li>
                </ul>
                <div id="iframeContainer" data-test="test">
                    <iframe src="pleasewait.html" id="specIframe" style="width: 100%; height: auto; padding: 25px; border: 1px solid lightgrey; display:none;">
                    </iframe>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;
    }

    public function getReportHeader($data, $range)
    {
        $pcount = number_format(count($data['data']), 0, '.', ',');
        $count = count($data['data']);
        $alert = "";
        if ($count <= $range[0]) {
            $alert = 'alert-success';
        } elseif ($count <= $range[1]) {
            $alert = 'alert-warning';
        } elseif ($count <= $range[2]) {
            $alert = 'alert-danger';
        }
        
        $ret = "";

        $ret .= "<div class='count $alert'>$pcount</div>";
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

    public function multiStoreDiscrepCheck($dbc)
    {
        $desc = "Discrepancies with products between stores";
        $fields = array('description','normal_price','cost','tax','foodstamp','wicable','discount','scale',
            'department','brand','local','price_rule_id',);
        $data = array();
        $tempData = array();
        foreach ($fields as $field) {
            $tempData = $this->getDiscrepancies($dbc,$field);
            foreach ($tempData as $k => $upc) {
                $data[] = $upc;
            }
        }

        $data = array_unique($data);
        $ret .= $this->getProdInfo($dbc,$data,$fields);

        return array('table'=>$ret, 'data'=>$data);
    }

    private function getProdInfo($dbc,$data)
    {
        $ret = '';
        include(__DIR__.'/../../config.php');
        $fields = array(
            'super_name',
            'description',
            'price',
            'cost',
            'dept',
            'tax',
            'fs',
            'wic',
            'scale',
            'forceQty'
        );
        list($inClause,$args) = $dbc->safeInClause($data);
        $queryH = 'SELECT p.*, m.super_name FROM prodUpdate AS p LEFT JOIN MasterSuperDepts AS m ON p.dept=m.dept_id WHERE storeID = 1 AND upc IN ('.$inClause.')';
        $queryD = 'SELECT * FROM prodUpdate WHERE storeID = 2 AND upc IN ('.$inClause.')';
        $itemH = array();
        $itemD = array();

        //  Get Hillside Prod. Info
        $prepH = $dbc->prepare($queryH);
        $resH = $dbc->execute($prepH,$args);
        if ($dbc->error()) $ret .= '<div class="alert alert-danger">'.$dbc->error().'</div>';
        while ($row = $dbc->fetchRow($resH)) {
            foreach ($fields as $field) {
                $itemH[$row['upc']][$field] = $row[$field];
            }
        }

        //  Get Denfeld Prod. Info
        $prepD = $dbc->prepare($queryD);
        $resD = $dbc->execute($prepD,$args);
        if ($dbc->error()) $ret .= '<div class="alert alert-danger">'.$dbc->error().'</div>';
        while ($row = $dbc->fetchRow($resD)) {
            foreach ($fields as $field) {
                $itemD[$row['upc']][$field] = $row[$field];
            }
        }

        $headers = array('Hill Desc','Den Desc','Hill Cost','Den Cost');
        $ret .= '<table class="table small">';
        $ret .= '<thead><tr><th>upc</th><th>chg</th><th>sup_dept</th>';
        foreach ($fields as $field) {
            if ($field != 'super_name') {
                $ret .= '<th><b>[H]</b>'.$field.'</th><th><b>[D]</b>'.$field.'</th>';
            }
        }

        $ret .= '</tr></thead><tbody>';
        foreach ($itemH as $upc => $row) {
            $ret .= '<tr>';
            $ret .= '<td class="okay">
                <a class="text" href="../../../../'.$FANNIE_SERVE_DIR.'item/ItemEditorPage.php?searchupc='.$upc.'" target="_blank">' . $upc . '</a></td>
                    <td class="okay">
                    <a class="text" href="../Item/TrackItemChange.php?upc=' . $upc . '" target="_blank">
                    dx
                </a></td>';
            $ret .= '<td class="'.$row['super_name'].'">' . $row['super_name'] . '</td>';
            foreach ($fields as $field) {
                if ($field != 'super_name') {
                    $td = '';
                    if ($row[$field] == $itemD[$upc][$field]) {
                        $td = '<td class="okay">';
                    } else {
                        $td = '<td class="bad alert alert-warning">';
                    }
                    $ret .= $td;
                    $ret .= $row[$field] . '</td>';

                    $ret .= $td;
                    $ret .= $itemD[$upc][$field] . '</td>';
                }

            }
            $ret .= '</tr>';
        }
        $ret .= '</tbody></table>';

        return $ret;
    }

    private function getDiscrepancies($dbc, $field)
    {

        $data = array();
        $diffR = $dbc->query("
            SELECT upc, description
            FROM products
            WHERE inUse = 1
                AND brand NOT IN (
                    'BOLTHOUSE FARMS', 
                    'BEETOLOGY',
                    'COLUMBIA GORGE',
                    'EVOLUTION FRESH',
                    'WILD POPPY',
                    'SUJA',
                    'HONEYDROP',
                    'SO GOOD SO YOU'
                )
            AND upc NOT IN (0000000001082, 0001136800238, 0001396436579, 0001396436582, 0002409407062, 0002409407091, 0003963200679, 0004122418310, 0004165252829, 0004973309101, 0004973395011, 0007105300001, 0007105300777, 0007215500005, 0007224821323, 0007224825064, 0007707523050, 0008775412005, 0008775412007, 0063172302830, 0063172302832, 0065428700002, 0065628517035, 0073402790126, 0073402790127, 0073402790414, 0078099900094, 0078099900206, 0078264300010, 0078264300020, 0078264300030, 0078506372236, 0082532515644, 0085055100511, 0085055100516, 0085055100517, 0086170500030, 0086170500032)
            AND numflag & (1 << 19) = 0
            GROUP BY upc
            HAVING MIN({$field}) <> MAX({$field})
            ORDER BY department
        ");
        $count = $dbc->numRows($diffR);
        $msg = "";
        if ($count > 0 ) {
            while ($row = $dbc->fetchRow($diffR)) {
                $data[] = $row['upc'];
            }
        }

        if ($count > 0) {
            return $data;
        } else {
            return false;
        }
    }

    public function badPriceCheck($dbc)
    {
        $desc = "Products with bad prices";
        $p = $dbc->prepare("
            SELECT
                p.upc,
                p.normal_price AS price,
                p.brand,
                p.description,
                p.store_id,
                p.last_sold,
                p.cost,
                m.super_name
            FROM products AS p
                RIGHT JOIN MasterSuperDepts AS m ON p.department = m.dept_ID
            WHERE inUse=1
                AND upc NOT IN (0001440035017,0085068400634,0000000000114,0000000001092,0000000001108,0065801012014,0000000003361,0000000001138,0000000000114,0000000001101,0000000001997,0000000005005,0000009999904)
                AND (normal_price = 0 OR normal_price > 99.99 OR normal_price < cost)
                AND last_sold is not NULL
                AND p.price_rule_id = 0
                AND wicable = 0
                AND m.superID IN (1,13,9,4,8,17,5) 
            GROUP BY upc
        "
        );
        $r = $dbc->execute($p);
        $cols = array('upc', 'brand', 'description', 'cost', 'price');
        $data = array();
        while ($row = $dbc->fetchRow($r)) {
            foreach ($cols as $col) $data[$row['upc']][$col] = $row[$col];
        }
        if ($er = $dbc->error()) echo "<div class='alert alert-danger'>$er</div>";

        return array('cols'=>$cols, 'data'=>$data, 'count'=>$count, 
            'desc'=>$desc);
    }

    public function getMissingScaleItems($dbc)
    {
        $desc = "Scale-items set to scale = 0";
        $dontCheck = array(0, 5, 6, 40, 52, 103, 104, 105, 107, 109, 111, 112, 123, 160, 184, 
            194, 195, 234, 237, 245, 247, 248, 250, 256, 265, 324, 549, 550, 666, 759, 799, 800, 
            852, 868, 869, 918, 919, 920, 958, 983, 984, 985, 917, 154, 155, 193, 197, 198, 199,
            211, 228, 189, 190);
        $p = $dbc->prepare("SELECT upc, brand, description, normal_price FROM products WHERE upc < 1000 AND scale = 0 GROUP BY upc;");
        $r = $dbc->execute($p);
        $cols = array('upc', 'brand', 'description', 'normal_price');
        $data = array();
        while ($row = $dbc->fetchRow($r)) {
            if (!in_array($row['upc'], $dontCheck)) {
                foreach ($cols as $col) $data[$row['upc']][$col] = $row[$col];
            }
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
        $desc = "Products with multiple SKUs by Vendor";
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
            /** query to get vendorItemID of items with 2+ skus and one sku matches the UPC. 
            $p = $dbc->prepare("
                SELECT v.sku, v.upc, v.description, v.cost, v.modified, v.vendorID, v.vendorItemID
                FROM vendorItems AS v 
                    LEFT JOIN products AS p ON p.upc=v.upc
                    INNER JOIN (SELECT * FROM vendorItems WHERE vendorID = ? GROUP BY upc HAVING COUNT(upc)>1) dup ON v.upc = dup.upc WHERE v.vendorID=?
                AND v.upc <> 0 
                AND p.upc=v.sku
            ");
            */
            $p = $dbc->prepare("
                SELECT v.sku, v.upc, v.description, v.cost, v.modified, v.vendorID
                FROM vendorItems AS v 
                    INNER JOIN (SELECT * FROM vendorItems WHERE vendorID = ? GROUP BY upc HAVING COUNT(upc)>1) dup ON v.upc = dup.upc WHERE v.vendorID=?
                AND v.upc <> 0 
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
        $desc = "Products with recent sales missing SKU";
        // think about excluding vendorIDs since some vendors don't use SKUs
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
                AND p.department <> 240
            GROUP BY upc;");
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
        $argA = array(1, 1, 1, 1);
        $argB = array(2, 2, 2, 2);
        $pre = $dbc->prepare("
            SELECT upc, brand, description, created, ? AS store_id,
                CONCAT('INSERT INTO MovementTags (upc, storeID, lastPar, modified) VALUES (\"', upc, '\", ',?,', 0.00, \"', NOW(), '\");') 
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
        //$cols = array('upc', 'brand', 'description', 'created', 'store_id');
        $cols = array('sql');
        $i = 0;
        while ($row = $dbc->fetchRow($res)) {
            foreach ($cols as $col) $data[$i]['sql'] = $row[5];
            $i++;
        }
        if ($er = $dbc->error()) echo "<div class='alert alert-danger'>$er</div>";
        $res = $dbc->execute($pre, $argB);
        $count = $dbc->numRows($res, $argB);
        //$cols = array('upc', 'brand', 'description', 'created', 'store_id');
        $cols = array('sql');
        while ($row = $dbc->fetchRow($res)) {
            foreach ($cols as $col) $data[$i]['sql'] = $row[5];
            $i++;
        }
        if ($er = $dbc->error()) echo "<div class='alert alert-danger'>$er</div>";

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
});
JAVASCRIPT;
    }

    public function cssContent()
    {
return <<<HTML
fieldset {
    border: 1px solid lightgrey;
}
.smh4 {
    font-size: 14px;
    padding: 15px;
}
.small {
    //font-size: 12px;
}
.btn-collapse {
    background: rgba(0,0,0,0);
    color: #84B3FF;
    padding: 0px; }
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

    public function helpContent()
    {
        return <<<HTML
<label>Scanning Department Dashboard</label>
<ul>
    <li>
        <strong>Products using generic variable pricing rule</strong>
        <p>Generig "Variable" pricing rules have been deprecated at WFC. All products
            should fall into a specific pricing rule category.</p>
    </li>
    <li>
        <strong>Products missing movement tag rows</strong>
        <p>The data returned in this table is in sql format and is ready to be queries directly 
            into the operational database, which is <i>currently handled manually.</i></p>
    </li>
    <li>
        <strong>Vendors missing from Vendor Review Schedule</strong>
        <p>There is a script in /home/csather/ (newScheduleVendor.sh) that will insert a new 
            vendor into the operational database.</p>
    </li>
    <li>
        <strong>Products with multiple SKUs by Vendor</strong>
        <p>Eeach vendor should only have one SKU for each item. When updating vendors, make 
            sure to remove the irrelevant SKUs before running the Vendor Pricing Batch Page, 
            or there can be some discrepancies which may prohibit some items from showing
            up in the list of desired changes.</p>
    </li>
    <li>
        <strong>Products missing physical locations</strong>
        <p>Use the product location editor <i>list of UPCs Update</i> to update physical 
            product locations.</p> 
    </li>
</ul>    
HTML;
    }

}
WebDispatch::conditionalExec();
