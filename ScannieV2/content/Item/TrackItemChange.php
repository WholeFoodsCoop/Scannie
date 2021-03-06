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
class TrackItemChange extends PageLayoutA 
{

    protected $title = "Track Change";
    protected $description = "[Track Change] Track all changes made to an item in POS/OFFICE.";
    protected $ui = TRUE;

    public function cssContent()
    {
return <<<HTML
.green {
    color: green;
}
.panel-noborder {
    border: 0;
    box-shadow: none;
}
h5 {
    color: slategrey;
}
.inactive {
    background: white;
}
HTML;
    }

    public function body_content()
    {
        $ret = '';
        include(__DIR__.'/../../config.php');
        $dbc = scanLib::getConObj();

        if (!class_exists(LastSoldDates)) {
            include('LastSoldDates.php');
        }
        $data = LastSoldDates::getDates();
        $storename = array(1=>'Hillside',2=>'Denfeld');
        $lastSold = '<h5>Product Last Sold</h5>
            <table class="table table-sm"><tbody><tr>';
        foreach ($data as $k => $v) {
            $pipe = ($k == 1) ? " | " : "";
            if (is_numeric($k)) {
                $lastSold .= "<td class='min'>{$storename[$k]}</td><td>$v</td><td class='space'>$pipe</td>";
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
        $upc = FormLib::get('upc');
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
            $upcLink = "<div><a href='http://$FANNIE_ROOTDIR/item/ItemEditorPage.php?searchupc="
                . $upc . "&ntype=UPC&searchBtn=' target='_blank'>{$upc}</a></div>";
            $col1 .=  "<div>" . $upcLink . " <b>" . $desc[max(array_keys($desc))] . "</b></div>";
            $col1 .=  "<div><a href='LastSoldDates.php?paste_list=1'>LAST SOLD PAGE</a></div>";
            $col1 .= scanLib::getDbcError($dbc);

            $table = '';
            $table .=  "<div class='table-responsive'><table class='table table-sm small'>";
            $table .=  "
                <thead>
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
                if (array_key_exists(($i-1), $cost)
                    && $cost[$i] != $cost[$i-1]
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
                        $table .=  "<tr id='tr_$i'>";
                    } else {
                        $table .=  "<tr id='tr_$i' class='alert-warning'>";
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
                    $table .=  "<td class=\"storeid\">" . $store_id[$i] . "</td>";
                    $table .=  "<td>" . $switch[$inUse[$i]] . "</td>";
                    $table .=  "<td class='modified'>" . $modified[$i] . "</td> ";
                    if ($realName[$i] == NULL) {
                        $table .=  "<td><i>unknown / scheduled change " . $uid[$i] . "</i></tr>";
                    } else {
                        $table .=  "<td>" . $realName[$i] . "</tr> ";
                    }
                }

            }
            $table .=  "</tbody></table></div>";
            $ret .= $table;
        }

        $pData = LastSoldDates::getPurchase($upc,$dbc);

        return <<<HTML
<div class="container-fluid" style="margin-top: 20px;">
    <div class="row">
        <div class="col-lg-3">
            {$col1}
            {$lastSold}
            <div class='form-group'>
                <button class='btn btn-default active filter'>Hillside</button>
                <button class='btn btn-default active filter'>Denfeld</button>
            </div>
        </div>
        <div class="col-lg-3">
            {$pData[0]}
        </div>
        <div class="col-lg-6">
            {$pData[1]}
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
<form class =""  method="get" >
    <div class="row">
        <div class="col-lg-6">
            <div class="form-group">
                <input type="text" class="form-control" name="upc" placeholder="Enter a PLU" autofocus />
            </div>
        </div>
        <div class="col-lg-2">
            <div class="form-group">
                <div class="form-group">
                    <input type="submit" class="btn btn-defualt" value="submit" />
                </div>
            </div>
        </div>
    </div>
</form>
HTML;
    }

    public function javascriptContent()
    {
        return <<<JAVASCRIPT
var newCost = 0;
var oldCost = 0;
var end = 0; 
var date = '';
var dateID = 0;
$(function() {
    $('tr').find('td').each(function() {
        if ( $(this).hasClass('cost') && end == 0 ) {
            var temp = $(this).text();
            if (newCost == 0 && temp != 0) {
                newCost = temp;
            }
            if (temp != newCost && temp != 0) {
                oldCost = temp;
                dateID = $(this).closest('tr').attr('id');
                dateID = dateID.substr(3);
            }
            temp = 0;
            if (newCost != 0 && oldCost != 0) end = 1;
        }
    });
    var changeID = dateID-1;
    var fullChangeID = '#tr_'+changeID;
    date = $(fullChangeID).closest('tr').find('td.modified').text();
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
$(function(){
    $('.filter').on('click', function(){
        var active = $(this).hasClass('active');
        var store = $(this).text();
        if (active == true) {
            // make store inactive
            $(this).removeClass('active')
                .addClass('inactive');
            $('td').each(function(){
                $(this).closest('tr').show();
            });
            $('td').each(function(){
                if ($(this).hasClass('storeid')) {
                    var hide = $(this).text();
                    if (hide == store) {
                        $(this).closest('tr').hide();
                    }
                }
            });
        } else {
            // make store active
            $(this).removeClass('inactive')
                .addClass('active');
            $('td').each(function(){
                if ($(this).hasClass('storeid')) {
                    var show = $(this).text();
                    if (show == store) {
                        $(this).closest('tr').show();
                    }
                }
            });
        }
    });
});
JAVASCRIPT;
    }

}
WebDispatch::conditionalExec();
