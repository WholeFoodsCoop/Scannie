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
include(__DIR__.'/../config.php');
if (!class_exists('ScancoordDispatch')) {
    include(__DIR__.'/../common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../common/sqlconnect/SQLManager.php');
}
class TrackChangeNew extends ScancoordDispatch
{

    protected $title = "Track Change";
    protected $description = "[Track Change] Track all changes made to an item in POS/OFFICE.";
    protected $ui = TRUE;

    public function body_content()
    {
        $ret = '';
        include(__DIR__.'/../config.php');
        $dbc = scanLib::getConObj();

        $desc = array();
        $salePrice = array();
        $cost = array();
        $dept = array();
        $tax = array();
        $fs = array();
        $scale = array();
        $modified = array();
        $name = array();
        $realName = array();
        $uid = array();
        $ret .= '<div class="container-fluid">';
        $ret .=  self::form_content();
        $upc = $_GET['upc'];
        if($upc = scanLib::upcParse($upc)) {
            //* Create the table if it doesn't exist */
            $args = array($upc);
            $prep = $dbc->prepare("SELECT pu.description,
                        pu.salePrice,
                        pu.price,
                        pu.cost,
                        pu.dept,
                        pu.tax,
                        pu.fs,
                        pu.scale,
                        pu.modified,
                        u.name,
                        u.real_name,
                        u.uid,
                        pu.upc,
                        pu.wic,
                        pu.storeID,
                        pu.inUse
                    FROM prodUpdate as pu
                    LEFT JOIN Users as u on u.uid=pu.user
                    WHERE pu.upc = ?
                    GROUP BY pu.modified, pu.storeID
                    ORDER BY modified DESC
                    ;");
            $result = $dbc->execute($prep,$args);
            while ($row = $dbc->fetchRow($result)) {
                $desc[] = $row['description'];
                $price[] = $row['price'];
                $salePrice[] = $row['salePrice'];
                $cost[] = $row['cost'];
                $dept[] = $row['dept'];
                $tax[] = $row['tax'];
                $fs[] = $row['fs'];
                $scale[] = $row['scale'];
                $modified[] = $row['modified'];
                $name[] = $row['name'];
                $realName[] = $row['real_name'];
                $uid[] = $row['uid'];
                $wic[] = $row['wic'];
                $store_id[] = $row['storeID'];
                $inUse[] = $row['inUse'];
            }
            $upcLink = "<a href='http://$FANNIEROOT_DIR/item/ItemEditorPage.php?searchupc="
                    . $upc . "&ntype=UPC&searchBtn=' target='_blank'>{$upc}</a>";
            $ret .=  "<div>Changes made to " . $upcLink . " <b>" . $desc[max(array_keys($desc))] . "</b></div>";
            $ret .=  "<div><i>*Changes now being sorted from newest to oldest.*</i></div>
              <a value='back' onClick='history.go(-1);return false;'>BACK</a>
			  <span class='pipe'>&nbsp;|&nbsp;</span>
              <a href='http://$SCANROOT_DIR/item/last_sold_check.php?upc=" . $upc . "+&id=1'>LAST SOLD PAGE</a>
                <br>";
            $ret .= scanLib::getDbcError($dbc);

            $ret .=  "<div class='panel panel-default panelScroll table-responsive'>";
            $ret .= '<span class="scrollRightIcon collapse" id="scrollRight"> </span>';
            $table = '';
            $table .=  "<table class='table' id='mytable'>";
            $table .=  "
                <thead style='position: relative;'>
                <th>Description</th>
                <th>Price</th><th>Sale</th>
                <th>Cost</th>
                <th>Dept</th>
                <th>Tax</th>
                <th>FS</th>
                <th>Scale</th>
                <th>wic</th>
                <th>store</th>
                <th>In Use</th>
                <th>Modified</th>
                <th>Modified By</th>
                </thead>
                <tbody>
            ";
            for ($i=0; $i<count($desc); $i++) {
                if($store_id[$i] == 1) $store_id[$i] = "Hillside";
                if($store_id[$i] == 2) $store_id[$i] = "Denfeld";
            }
            for ($i=0; $i<count($desc); $i++) {
                if ($cost[$i] != $cost[$i-1]
                    || $salePrice[$i] != $salePrice[$i-1]
                    || $cost[$i] != $cost[$i-1]
                    || $tax[$i] != $tax[$i-1]
                    || $fs[$i] != $fs[$i-1]
                    || $scale[$i] != $scale[$i-1]
                    || $desc[$i] != $desc[$i-1]
                    || $price[$i] != $price[$i-1]
                    || $dept[$i] != $dept[$i-1]
                    || $wic[$i] != $wic[$i-1]
                    || $realName[$i] != $realName[$i-1]
                    || $store_id[$i] != $store_id[$i-1]
                    || $inUse[$i] != $inUse[$i-1]
                )
                {
                    if ($store_id[$i] == 'Hillside') {
                        $table .=  "<tr >";
                    } else {
                        $table .=  "<tr class='warning'>";
                    }

                    $switch = array(
                        0=>"<span class=\"alert-danger\" style=\"color: white\">off</span>",
                        1=>"<span class=\"alert-success\"> on </span>"
                    );

                    $table .=  "<td>" . $desc[$i] . "</td>";
                    $table .=  "<td>" . $price[$i] . "</td>";
                    $table .=  "<td>" . $salePrice[$i]  . "</td>";
                    $table .=  "<td>" . $cost[$i] . "</td>";
                    $table .=  "<td>" . $dept[$i] . "</td>";
                    $table .=  "<td>" . $switch[$tax[$i]] . "</td>";
                    $table .=  "<td>" . $switch[$fs[$i]] . "</td>";
                    $table .=  "<td>" . $switch[$scale[$i]] . "</td>";
                    $table .=  "<td>" . $switch[$wic[$i]] . "</td>";
                    $table .=  "<td>" . $store_id[$i] . "</td>";
                    $table .=  "<td>" . $switch[$inUse[$i]] . "</td>";
                    $table .=  "<td>" . $modified[$i] . "</td> ";
                    if ($realName[$i] == NULL) {
                        $table .=  "<td><i>unknown / scheduled change " . $uid[$i] . "</i></tr>";
                    } else {
                        $table .=  "<td>" . $realName[$i] . "</tr> ";
                    }
                }

            }
            $table .=  "</tbody></table>";
            $ret .= $table;
            $ret .=  "</div></div>";    // <- panel / container
        }

        $this->addScript('../common/javascript/jquery.floatThead.min.js');
        $this->addOnloadCommand("\$('.table-float').floatThead();\n");

        return $ret;
    }

    private function form_content()
    {
        return '
            <h4>Product Change Check</h4>
                <form class ="form-inline"  method="get" >
                    <div class="form-group">
                        <input type="text" class="form-control" name="upc" placeholder="enter plu to track" autofocus>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-default" value="submit">
                    </div>
                </form>
        ';
    }

}
ScancoordDispatch::conditionalExec();
