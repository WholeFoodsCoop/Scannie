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
class last_sold_check extends PageLayoutA 
{

    protected $title = "Last Sold Check";
    protected $description = "[Last Sold Check] Tracks last sale date, most
        recent purchase order and displays Vendor Item information relavant
        to the matching SKU in relation to the purchase order.";

    private function last_sold_check_list($dbc)
    {
        $ret = "";
        include(__DIR__.'/../../config.php');
        $ret .= '
            <div style="height: 25px;"></div>
            <form method="get">
                <div class="row">
                    <div class="col-lg-2">
                        <textarea class="form-control" name="upcs" rows="5"></textarea>
                    </div>
                    <input type="hidden" name="paste_list" value="1">
                    <div class="col-lg-2">
                        <textarea id="copyarea" class="form-control" rows="5">copy/paste
                        </textarea>   
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-2">
                        <button type="submit" class="sp btn btn-default btn-sm">Submit</button>
                    </div>
                    <div class="col-lg-2">
                        <a href="TrackChangeNew.php" class="sp">Track Change</a>
                    </div>
                </div>
            </form>
        ';

        if ($_GET['upcs']) {
            $upcs = $_GET['upcs'];
            $plus = array();
            $chunks = explode("\r\n", $upcs);
            foreach ($chunks as $key => $str) {
                $plus[] = scanLib::upcPreparse($str);
            }
        }

        $link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $ret .= '
            <div align="right">
                <button class="sp btn btn-default btn-sm" id="hideHill">Hide Hillside</button>
                <button class="sp btn btn-default btn-sm" id="hideDenf">Hide Denfeld</button>
                <button id="hideBlue" class="sp btn btn-info btn-sm">Hide Recent</button>
                <button id="hideRed" class="sp btn btn-danger btn-sm">Hide Unsold</button>
                <button id="showAll" class="sp btn btn-default btn-sm">Show All</button>
            </div><br>
        ';

        $ret .= '<div class="table-responsive"><table class="table table-sm" id="dataTable"
            style="width:900 px;border:2px solid lightgrey"><tbody>';
        $ret .= '
            <th>UPC</th>
            <th>Last Date Sold</th>
            <th>Store ID</th>
            <th>Description</th>
            <th>Brand</th>
        ';
        foreach ($plus as $upc) {
            $args = array($upc);
            $prep = $dbc->prepare("
                    SELECT
                        last_sold, store_id, description, brand
                    FROM products
                    WHERE upc = ?
                    ORDER BY store_id
                ");
            $result = $dbc->execute($prep,$args);
            while ($row = $dbc->fetchRow($result)) {
                $last_sold = $row['last_sold'];
                $dateDiff = scanLib::dateDistance($last_sold);
                $class = ($dateDiff >= 31) ? "danger" : "info";
                if (is_null($last_sold)) {
                    $last_sold = "<span class='text-danger'>no recorded sales</span>";
                } else {
                    $last_sold = '<span class="text-'.$class.'">' . $last_sold = substr($last_sold,0,10) . '</span>';
                }
                $upcLink = '<a href="http://'.$FANNIE_ROOTDIR.'/item/ItemEditorPage.php?searchupc=' . $upc . '" target="_blank">' . $upc . '</a>';
                $ret .= "<tr>";
                $ret .= "<td class='clickToAdd'>" . $upcLink . "</td><td>" . $last_sold . "</td><td class='store_id'>" . $row['store_id'] . "</td><td>" . $row['description'] . "</td><td>" . $row['brand'] . "</td>";
                $ret .= "</tr>";
            }
        }
        $ret .= "</tbody></table></div>";

        return "<div class=\"container-fluid\">$ret</div>";
    }

    public function body_content()
    {
        $ret = '';
        $item = array ( array() );
        include(__DIR__.'/../config.php');
        $dbc = scanlib::getConObj();

        if ($_GET['paste_list']) {
            $ret .= self::last_sold_check_list($dbc);
        }
        $ret .= ($ret == '') ? '<div align="center"><h4 class="alert alert-warning">Data Missing. This page offers &empty; functionality
            when called without data.</h4></div>' : '';
        return $ret;
    }


    public function getDates()
    {
        include(__DIR__.'/../config.php');
        $ret = '';
        $item = array ( array() );
        $dbc = scanlib::getConObj();
		$upc = scanLib::upcPreparse($_GET['upc']);

        $data = array();
        $data['form'] = self::form_content();
        
        if (FormLib::get('paste_list')) {
            $data['list'] = self::last_sold_check_list($dbc);
        }

        $args = array($upc);
        $prep = $dbc->prepare("
            SELECT last_sold, store_id, inUse
            FROM products
            WHERE upc = ?
            ORDER BY store_id
        ");
        if(!FormLib::get('paste_list')){
            $result = $dbc->execute($prep,$args);
            $i = 0;
            while ($row = $dbc->fetchRow($result)) {
                $last_sold = (is_null($row['last_sold'])) 
                    ? "<span class='text-danger'>n/a</span>" : substr($row['last_sold'],0,10);
                $dateDiff = scanLib::dateDistance($row['last_sold']);
                $class = ($dateDiff >= 31) ? "red" : "";
                $inUse = ($row['inUse'] == 1) ? "in-use" : "<i>not in-use</i>";
                $inUse = "<span style='font-size: 14px;'>$inUse</span>";
                $data[$row['store_id']] = "<span class='$class'>$last_sold</span> $inUse";
            }
            // avoid notice about appending to undefined value
            if (!isset($data['error'])) {
                $data['error'] = '';
            }
            $data['error'].= scanLib::getDbcError($dbc);
        }
        return $data;
    }

    public function getPurchase($upc,$dbc)
    {
            if(!class_exists('scanLib')) {
                include(__DIR__.'/../common/scanLib.php;');
            }
            $item = array ( array() );
                $upc = scanLib::upcPreparse($_GET['upc']);
                $args = array($upc,$upc);
                $prep = $dbc->prepare("
                    SELECT *
                    FROM PurchaseOrderItems
                    WHERE internalUPC=?
                        AND receivedDate = (SELECT max(receivedDate) FROM PurchaseOrderItems WHERE internalUPC = ?);
                ");
                $result = $dbc->execute($prep,$args);
                $sku = 0;
                $purchase = '<h5>Most Recent Purchase Order</h5>';
                $purchase .= '<table class="table table-small table-condensed">';
                $purchase .= '<th>SKU</th>';
                $purchase .= '<th>Case Size</th>';
                $purchase .= '<th>Received Date</th>';
                while ($row = $dbc->fetchRow($result)) {
                    $purchase .= '<tr><td>' . $row['sku'] . '</td>';
                    $purchase .= '<td>' . $row['caseSize'] . '</td>';
                    $purchase .= '<td>' . substr($row['receivedDate'], 0, 10) . '</td>';
                    $sku = $row['sku'];
                }
                $purchase .= scanLib::getDbcError($dbc);
                $purchase .= '</table>';

                $args = array($upc);
                $prep = $dbc->prepare("
                    SELECT upc
                    FROM VendorBreakdowns
                    WHERE vendorID = 1
                        AND upc = ?
                ");
                if (!$sku && $result = $dbc->execute($prep,$args)) {
                    $purchase .= '
                        <span class="alert-danger" align="center">
                        No purchase orders were found for this item.
                        </span><br><br>
                    ';
                }

                $args = array($upc);
                $prep = $dbc->prepare("
                    SELECT
                        *
                    FROM vendorItems
                    WHERE upc = ?
                        AND vendorID = 1
                ");
                $result = $dbc->execute($prep,$args);
                $vendorItem = '<h5>Vendor Items</h5>';
                $vendorItem .= '<table class="table table-small table-condensed">';
                $vendorItem .= '<th>SKU</th>';
                $vendorItem .= '<th>Brand</th>';
                $vendorItem .= '<th>Description</th>';
                $vendorItem .= '<th>Size</th>';
                $vendorItem .= '<th>Units</th>';
                $vendorItem .= '<th>Cost</th>';
                $vendorItem .= '<th>Modified</th>';
                while ($row = $dbc->fetchRow($result)) {
                    if ($row['sku'] == $sku) {
                        $vendorItem .= '<tr class="success">';
                    } else {
                        $vendorItem .= '<tr>';
                    }
                    $vendorItem .= '<td><b>' . $row['sku'] . '</b></td>';
                    $vendorItem .= '<td>' . $row['brand'] . '</td>';
                    $vendorItem .= '<td>' . $row['description'] . '</td>';
                    $vendorItem .= '<td>' . $row['size'] . '</td>';
                    $vendorItem .= '<td>' . $row['units'] . '</td>';
                    $vendorItem .= '<td><b>' . $row['cost'] . '</b></td>';
                    $vendorItem .= '<td>' . substr($row['modified'], 0, 10) . '</td>';
                }
                $vendorItem .= scanLib::getDbcError($dbc);
                $vendorItem .= '</table>';
                $ret .= '</div>';

                if ($sku) {
                    $vendorItem.= '
                        <span class="alert-success">&nbsp;&nbsp;&nbsp;&nbsp;</span>
                            Highlighted row is related to the sku most recently purchased.
                        <br><br>
                    ';
                }

            
            $data = array($purchase,$vendorItem);
            return $data; 

        return <<<HTML
{$purchase}{$vendorItem}
HTML;
    }

    private function form_content()
    {
        return '
            <form method="get" class="form-inline">
                <input type="text" name="upc" class="form-control" placeholder="Enter upc" autofocus>
                <input type="hidden" name="id" value=1>
                <input type="submit" value="submit" id="btn-core" class="btn btn-default btn-core">
            </form>
            <form method="get">
                or <button type="submit" class="btn  btn-default btn-xs" " name="paste_list" value="1">Copy/Paste a List of UPCs</button>
            </form>
        ';
    }

    public function javascriptContent()
    {
        return <<<HTML
$(document).ready(function() {
    $('#hideBlue').click(function() {
        $('#dataTable').find('td').each(function() {
            var html = $(this).html();
            if ( html.includes('text-info') ) {
                $(this).closest('tr').hide();
            }
        });
    });
    $('#hideRed').click(function() {
        $('#dataTable').find('td').each(function() {
            var html = $(this).html();
            if ( html.includes('text-danger') ) {
                $(this).closest('tr').hide();
            }
        });
    });
    $('#hideHill').click(function() {
        $('#dataTable').find('td').each(function() {
            var text= $(this).text();
            if ( text == 1 ) {
                $(this).closest('tr').hide();
            }
        });
    });
    $('#hideDenf').click(function() {
        $('#dataTable').find('td').each(function() {
            var text= $(this).text();
            if ( text == 2 ) {
                $(this).closest('tr').hide();
            }
        });
    });
    $('#showAll').click(function() {
        location.reload(true);
    });
});
$('.clickToAdd').click(function(){
    var upc = $(this).text();
    if ($(this).hasClass('selected')) {
        $(this).removeClass('selected');
        $(this).css('background-color','white');
        var text = $('#copyarea').text();
        text = text.replace(upc,'');
        $('#copyarea').text(text);
    } else {
        $(this).addClass('selected');
        $(this).css('background-color','lightblue');
        $('#copyarea').append(upc+'\\n');
    }
});
HTML;
    }

    public function css_content()
    {
        return <<<HTML
.selected {
    background-color: lightblue;
}
.clickToAdd {
    background-color: white;
}
#hideHill {
    //border: 2px solid #95cc93;
}
#hideDenf {
    //border: 2px solid lightblue;
}
.sp {
    margin-top: 2.5px;
    margin-bottom: 2.5px;
}
HTML;
    }

    public function help_content()
    {
        return <<<HTML
<ul>
    <li>Insert a list of UPCs.</li>
    <li>This page will check the last sold date for each upc
        entered, for each location.</li>
    <li>For items that have not sold in 31 days or more - "dates" 
        will show up in red, recently sold items in blue.</li>
    <li>Click next to a UPC to add it to the "copy/paste" area
        at the top of the screen.</li>
</ul>
HTML;
    }

}
WebDispatch::conditionalExec();
