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

    public function css_content()
    {
return <<<HTML
.green {
    color: green;
}
td.min {
    min-width: 75px;
}
td.space {
    min-width: 25px;
    text-align: center;
}
input {
    height: 20px;
}
.panel-noborder {
    border: 0;
    box-shadow: none;
}
h5 {
    color: slategrey;
}
HTML;
    }

    public function body_content()
    {
        $ret = '';
        include(__DIR__.'/../config.php');
        $dbc = scanLib::getConObj();

        if (!class_exists(last_sold_check)) {
            include('last_sold_check.php');
        }
        $data = last_sold_check::getDates();
        $storename = array(1=>'Hillside',2=>'Denfeld');
        $lastSold = '<h5>Product Last Sold</h5><table class="table table-small table-condensed"><tbody><tr>';
        foreach ($data as $k => $v) {
            $pipe = ($k == 1) ? " | " : "";
            if (is_numeric($k)) {
                $lastSold .= "<td class='min'>$storename[$k]</td><td>$v</td><td class='space'>$pipe</td>";
            }
        }
        $lastSold .= '</tr></tbody></table>';

        $col1 =  self::form_content();
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
        $upc = $_GET['upc'];
        if($upc = scanLib::upcParse($upc)) {
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
                    . $upc . "&ntype=UPC&searchBtn=' target='_blank'>{$upc}</a><br/>";
            $col1 .=  "<div>" . $upcLink . " <b>" . $desc[max(array_keys($desc))] . "</b></div>";
            $col1 .=  "
              <a value='back' onClick='history.go(-1);return false;'>BACK</a>
			  <span class='pipe'>&nbsp;|&nbsp;</span>
              <a href='http://$SCANROOT_DIR/item/last_sold_check.php?paste_list=1'>LAST SOLD PAGE</a>
                <br>";
            $col1 .= scanLib::getDbcError($dbc);

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
                    $table .=  "<td class='cost'>" . $cost[$i] . "</td>";
                    $table .=  "<td>" . $dept[$i] . "</td>";
                    $table .=  "<td>" . $switch[$tax[$i]] . "</td>";
                    $table .=  "<td>" . $switch[$fs[$i]] . "</td>";
                    $table .=  "<td>" . $switch[$scale[$i]] . "</td>";
                    $table .=  "<td>" . $switch[$wic[$i]] . "</td>";
                    $table .=  "<td>" . $store_id[$i] . "</td>";
                    $table .=  "<td>" . $switch[$inUse[$i]] . "</td>";
                    $table .=  "<td class='modified'>" . $modified[$i] . "</td> ";
                    if ($realName[$i] == NULL) {
                        $table .=  "<td><i>unknown / scheduled change " . $uid[$i] . "</i></tr>";
                    } else {
                        $table .=  "<td>" . $realName[$i] . "</tr> ";
                    }
                }

            }
            $table .=  "</tbody></table>";
            $ret .= $table;
            $ret .=  "</div>";    // <- panel / container
        }

        $pData = last_sold_check::getPurchase($upc,$dbc);

        $this->addScript('../common/javascript/jquery.floatThead.min.js');
        $this->addOnloadCommand("\$('.table-float').floatThead();\n");

        return <<<HTML
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2">
            {$col1}
            {$lastSold}
        </div>
        <div class="col-md-3">
            {$pData[0]}
        </div>
        <div class="col-md-7">
            <div class="panel panel-noborder table-responsive">
                {$pData[1]}
            </div>
            <div id="costs">
                <label>OldCost</label>: <span id="oldCost"></span> | 
                <label>NewCost</label>: <span id="newCost"></span> | 
                <label>Change</label>: <span id="diffCost"></span> | 
                <label>On</label>: <span id="dateCost"></span>
            </div>
        </div>
    </div>
</div>
{$ret}
HTML;
    }

    private function form_content()
    {
        return <<<HTML
<form class ="form-inline"  method="get" >
    <div class="form-group">
        <div class="input-group">
            <input type="text" class="form-control" name="upc" placeholder="enter plu to track" autofocus>
            <div class="input-group-addon">
                <input type="submit" class="btn btn-xs" value="go">
            </div>
        </div>
    </div>
    <div class="form-group">
    </div>
</form>
HTML;
    }

    public function javascript_content()
    {
        return <<<HTML
var newCost = 0;
var oldCost = 0;
var end = 0; 
var date = '';
$(function() {
    $('tr').find('td').each(function() {
        if ( $(this).hasClass('cost') && end == 0 ) {
            var temp = $(this).text();
            if (newCost == 0 && temp != 0) {
                newCost = temp;
                date = $(this).closest('tr').find('td.modified').text();
            }
            if (temp != newCost && temp != 0) oldCost = temp;
            temp = 0;
            if (newCost != 0 && oldCost != 0) end = 1;
        }
    });
    $('#oldCost').text(oldCost);
    $('#newCost').text(newCost);
    var diff = newCost - oldCost;
    var ori = '';
    if (diff > 0) {
        ori = "+";   
    }
    $('#diffCost').text(ori+diff.toFixed(2));
    $('#dateCost').text(date.substr(0,10));
    var d = new Date();
    var dd = d.getDate();
    var mm = d.getMonth()+1;
    var yyyy = d.getFullYear();
    var today = yyyy+"-"+mm+"-"+dd;
     if ( $('#dateCost').text() == today ) {
         $('#dateCost').addClass('green').append(' *today*');
     }
});
HTML;
    }

}
ScancoordDispatch::conditionalExec();
