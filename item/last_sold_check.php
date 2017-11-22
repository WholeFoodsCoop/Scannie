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
class last_sold_check extends scancoordDispatch
{

    protected $title = "Last Sold Check";
    protected $description = "[Last Sold Check] Tracks last sale date, most
        recent purchase order and displays Vendor Item information relavant
        to the matching SKU in relation to the purchase order.";
    protected $ui = TRUE;

    private function last_sold_check_list($dbc)
    {
        $ret = "";
        include(__DIR__.'/../config.php');
        $ret .= '
            <form method="get" class="form-inline">
                <textarea class="form-control" style="width:170px" name="upcs"></textarea>
                <input type="hidden" name="paste_list" value="1">
                <img src="../common/src/img/back.png" height="10px" width="10px">
                <button type="submit" class="btn btn-default btn-xs">Submit</button>
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
                <button class="btn btn-default btn-xs" id="hideHill">Hide Hillside</button>
                <button class="btn btn-default btn-xs" id="hideDenf">Hide Denfeld</button>
                <button id="hideBlue" class="btn btn-info btn-xs">Hide Recent</button>
                <button id="hideRed" class="btn btn-danger btn-xs">Hide Unsold</button>
                <button id="showAll" class="btn btn-default btn-xs">Show All</button>
            </div><br>
        ';

        $ret .= '<table class="table table-condensed small" id="dataTable"
            style="width:900 px;border:2px solid lightgrey"><tbody>';
        $ret .= '
            <th>UPC</th>
            <th>Last Date Sold</th>
            <th>Store ID</th>
            <th>Descriptoin</th>
            <th>Brand</th>
        ';
        $curY = date('Y');
        $curM = date('m');
        $curD = date('d');
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
                if (is_null($last_sold)) {
                    $last_sold = "<span class='text-danger'>no recorded sales</span>";
                } else {
                    $year = substr($last_sold, 0, 4);
                    $month = substr($last_sold, 5, 2);
                    $day = substr($last_sold, 8, 2);

                    if (($year < $curY) or ($month < ($curM - 1)) or ($month < $curM && $day < $curD)) {
                        $last_sold = '<span class="text-danger">' . $last_sold = substr($last_sold,0,10) . '</span>';
                    } else {
                        $last_sold = '<span class="text-info">' . $last_sold = substr($last_sold,0,10) . '</span>';
                    }
                }
                $upcLink = '<a href="http://'.$FANNIEROOT_DIR.'/item/ItemEditorPage.php?searchupc=' . $upc . '" target="_blank">' . $upc . '</a>';
                $ret .= "<tr>";
                $ret .= "<td>" . $upcLink . "</td><td>" . $last_sold . "</td><td class='store_id'>" . $row['store_id'] . "</td><td>" . $row['description'] . "</td><td>" . $row['brand'] . "</td>";
                $ret .= "</tr>";
            }
        }
        $ret .= "</tbody></table>";

        return $ret;
    }

    public function body_content()
    {
        $ret = '';
        $item = array ( array() );
        $curY = date('Y');
        $curM = date('m');
        $curD = date('d');

        include(__DIR__.'/../config.php');
        $dbc = scanlib::getConObj();

        $ret .= '<div class="container"><h4>Item Last Sold Check</h4>';
        $ret .= self::form_content();
        $ret .= '<a value="back" onClick="history.go(-1);return false;">BACK</a>';
		$ret .= "<span class='pipe'>&nbsp;|&nbsp;</span>";
		$upc = scanLib::upcPreparse($_GET['upc']);
        $ret .= '<a href="http://key/scancoord/item/TrackChangeNew.php?upc=' . $upc . '">TRACK CHANGE PAGE</a><br>';


        if ($_GET['paste_list']) {
            $ret .= self::last_sold_check_list($dbc);
        }

        $ret .= '<div class="container">';

        $args = array($upc);
        $prep = $dbc->prepare("
            SELECT last_sold, store_id
            FROM products
            WHERE upc = ?
            ORDER BY store_id
        ");

        if(!$_GET['paste_list']){
            $result = $dbc->execute($prep,$args);
            $ret .= '
                    <div class="row">
                        <div class="panel panel-info" style="max-width: 390px;">
                            <div class="panel-heading"><label style="color:darkslategrey;">This Product Last Sold On</label></div>
                            <br />
            ';
            $i = 0;
            while ($row = $dbc->fetchRow($result)) {
                if ($row['store_id'] == 1) $row['store_id'] = 'Hillside';
                else $row['store_id'] = 'Denfeld';
                $year = substr($row['last_sold'], 0, 4);
                $month = substr($row['last_sold'], 5, 2);
                $day = substr($row['last_sold'], 8, 2);
                $ret .= '
                <div class="container">
                    <div class="row">
                        <div class="col-md-1">' . $row['store_id'] . '</div>
                ';

                if (($year < $curY) or ($month < ($curM - 2))) $ret .= '<div class="col-md-2" style="color:red;" class="red">' . substr($row['last_sold'], 0, 10) . '</div>';
                else $ret .= '<div class="col-md-1">' . substr($row['last_sold'], 0, 10) . '</div>';

                $ret .= '

                    </div><br>
                </div>
                ';
            }
            $ret .= scanLib::getDbcError($dbc);
            $ret .= "</div>";
            $ret .= "</div>";
            $ret .= "</div>";
            //  Purchase Order Scanner
            $item = array ( array() );
            if ($_GET['id'] && isset($_GET['id'])) {
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
                $ret .= '<div class="panel panel-info" style="width: 390px;">';
                $ret .= '
                    <div class="panel-heading">
                        <label style="color:darkslategrey;">Purchase Order Items</label>
                    </div>';
                $ret .= '<table class="table">';
                $ret .= '<th>SKU</th>';
                $ret .= '<th>Case Size</th>';
                $ret .= '<th>Received Date</th>';
                while ($row = $dbc->fetchRow($result)) {
                    $ret .= '<tr><td>' . $row['sku'] . '</td>';
                    $ret .= '<td>' . $row['caseSize'] . '</td>';
                    $ret .= '<td>' . substr($row['receivedDate'], 0, 10) . '</td>';
                    $sku = $row['sku'];
                }
                $ret .= scanLib::getDbcError($dbc);
                $ret .= '</table>';
                $ret .='</div>';

                $args = array($upc);
                $prep = $dbc->prepare("
                    SELECT upc
                    FROM VendorBreakdowns
                    WHERE vendorID = 1
                        AND upc = ?
                ");
                if (!$sku && $result = $dbc->execute($prep,$args)) {
                    $ret .= '
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
                $ret .= '<div class="panel panel-info">';
                $ret .= '
                    <div class="panel-heading">
                        <label style="color:darkslategrey;">Vendor Items</label>
                    </div>';
                $ret .= '<table class="table">';
                $ret .= '<th>UPC</th>';
                $ret .= '<th>SKU</th>';
                $ret .= '<th>Brand</th>';
                $ret .= '<th>Description</th>';
                $ret .= '<th>Size</th>';
                $ret .= '<th>Units</th>';
                $ret .= '<th>Cost</th>';
                $ret .= '<th>Modified</th>';
                while ($row = $dbc->fetchRow($result)) {
                    if ($row['sku'] == $sku) {
                        $ret .= '<tr class="success">';
                    } else {
                        $ret .= '<tr>';
                    }
                    $ret .= '<td><a href="http://key/git/fannie/item/ItemEditorPage.php?searchupc='
                        . $row['upc'] . '" target="_blank">' . $row['upc'] . '</a></td>';
                    $ret .= '<td><b>' . $row['sku'] . '</b></td>';
                    $ret .= '<td>' . $row['brand'] . '</td>';
                    $ret .= '<td>' . $row['description'] . '</td>';
                    $ret .= '<td>' . $row['size'] . '</td>';
                    $ret .= '<td>' . $row['units'] . '</td>';
                    $ret .= '<td><b>' . $row['cost'] . '</b></td>';
                    $ret .= '<td>' . substr($row['modified'], 0, 10) . '</td>';
                }
                $ret .= scanLib::getDbcError($dbc);
                $ret .= '</table>';
                $ret .= '</div>';

                if ($sku) {
                    $ret .= '
                        <span class="alert-success">&nbsp;&nbsp;&nbsp;&nbsp;</span>
                            Highlighted row related to sku associated with the
                            most recent purchase order for this product.
                        <br><br>
                    ';
                }

            } else {
                $ret .= 'This UPC is not recoginzed by Office.';
            }
        }

        return $ret;
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
HTML;
    }

    public function cssContent()
    {
        return <<<HTML
#hideHill {
    border: 2px solid #95cc93;
}
#hideDenf {
    border: 2px solid lightblue;
}
HTML;
    }

}
scancoordDispatch::conditionalExec();
