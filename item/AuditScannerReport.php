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
class AuditScannerReport extends ScancoordDispatch
{
    
    protected $title = "Audit Scanner Report";
    protected $description = "[Audit Scanner Report] View data from recent scan job.";
    protected $ui = TRUE;
    protected $add_javascript_content = TRUE;
    protected $must_authenticate = TRUE;
    
    private function clear_scandata_hander($dbc,$storeID,$username) 
    {
        
        $args = array($storeID,$username);
        $query = $dbc->prepare("DELETE FROM woodshed_no_replicate.AuditScanner WHERE store_id = ? AND username = ?");
        $dbc->execute($query,$args);
        
        return '
        <div align="center">
            <div class="alert alert-success">Data Cleared - <a href="AuditScannerReport.php">collapse message</a>
            </div>
        </div>
        ';
        
    }
    
    private function clear_notes_handler($dbc,$storeID,$username)
    {
        $args = array($storeID,$username);
        $query = $dbc->prepare("UPDATE woodshed_no_replicate.AuditScanner SET notes = 'n/a' WHERE store_id = ? AND username = ?");
        $dbc->execute($query,$args);
        
        return '
        <div align="center">
            <div class="alert alert-success">Notes Cleared - <a href="AuditScannerReport.php">collapse message</a>
            </div>
        </div>
        ';
    }
    
    private function update_scandata_handler($dbc,$storeID,$username)
    {
        
        $args = array($storeID,$username);
        $query = $dbc->prepare("SELECT upc FROM woodshed_no_replicate.AuditScanner WHERE store_id = ? AND username = ?");
        $res = $dbc->execute($query,$args);
        $upcs = array();
        while ($row = $dbc->fetchRow($res)) {
            $upcs[] = $row['upc'];
        }
        
        foreach ($upcs as $upc) {
            //$args = array($upc);

            $args = array($upc,$upc,$upc,$upc,$upc,$upc);
            $prep = $dbc->prepare("
                UPDATE woodshed_no_replicate.AuditScanner 
                SET price = (select normal_price from is4c_op.products where upc = ? group by upc),
                    prid = (select price_rule_id from is4c_op.products where upc = ? group by upc),
                    cost= (select cost from is4c_op.products where upc = ? group by upc),
                    description = (select description from is4c_op.products where upc = ? group by upc),
                    brand = (select brand from is4c_op.products where upc = ? group by upc)
                WHERE upc = ?;
            ");
            $dbc->execute($prep,$args);
            
            /*
            $prep = $dbc->prepare("SELECT normal_price,cost,brand,description,price_rule_id FROM products WHERE upc = ? GROUP BY upc");
            $res = $dbc->execute($prep,$upc);
            while ($row = $dbc->fetchRow($res)) {
                $description = $row['description'];
                $price = $row['normal_price'];
                $prid = $row['price_rule_id'];
                $cost = $row['cost'];
                $brand = $row['brand'];
            }
            
            $updateA = array($description,$price,$prid,$cost,$brand,$upc,$username,$store_id);
            $updateP = $dbc->prepare("
                UPDATE woodshed_no_replicate.AuditScanner
                SET description = ?, price = ?, prid = ?, cost = ?, brand = ?
                WHERE upc = ?
                    AND username = ?
                    AND store_id = ?
            ");
            $dbc->execute($updateP,$updateA);
            unset($description);
            unset($price);
            unset($prid);
            unset($cost);
            unset($brand);
            */
        }
        
        if ($er = $dbc->error()) {
            return '
                <div align="center">
                    <div class="alert alert-danger">'.$er.'</div>
                </div>
            ';
        } else {
            return '
                <div align="center">
                    <div class="alert alert-success">
                        Update Successful - 
                        <a href="AuditScannerReport.php">collapse message</a>
                    </div>
                    
                </div>
            ';
        }
        
        return false;
        
    }
    
    private function list_upcs_handler($dbc,$storeID,$username)
    {
        $ret = '';
        
        if ($_POST['upcs']) {
            $upcs = $_POST['upcs'];
            $plus = array();
            $chunks = explode("\r\n", $upcs);
            foreach ($chunks as $key => $str) {
                /*
                if (substr($str,2,1) == '2') {
                    $str = '002' . substr($str,3,4) . '000000';
                }
                */
                $str = scanLib::upcPreparse($str);
                $plus[] = $str;
            }
        }
        
        include('../config.php');
        foreach ($plus as $upc) {
            //$upc = str_pad($upc, 13, 0, STR_PAD_LEFT);
            $args = array($storeID,$upc);
            $query = $dbc->prepare("
                SELECT
                    p.cost,
                    p.normal_price,
                    p.description,
                    p.brand,
                    p.default_vendor_id,
                    p.inUse,
                    p.auto_par,
                    v.vendorName,
                    vi.vendorDept,
                    p.department,
                    d.dept_name,
                    p.price_rule_id,
                    vd.margin AS unfiMarg,
                    d.margin AS deptMarg,
                    pu.description AS signdesc,
                    pu.brand AS signbrand,
                    v.shippingMarkup,
                    v.discountRate
                FROM products AS p
                    LEFT JOIN productUser AS pu ON p.upc = pu.upc
                    LEFT JOIN departments AS d ON p.department=d.dept_no
                    LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
                    LEFT JOIN vendorItems AS vi
                        ON p.upc = vi.upc
                            AND p.default_vendor_id = vi.vendorID
                    LEFT JOIN vendorDepartments AS vd
                        ON vd.vendorID = p.default_vendor_id
                            AND vd.deptID = vi.vendorDept
                WHERE p.store_id = ?
                    AND p.upc = ?
                LIMIT 1
            ");
            $result = $dbc->execute($query,$args);
            while ($row = $dbc->fetchRow($result)) {
                $cost = $row['cost'];
                $price = $row['normal_price'];
                $desc = $row['description'];
                $brand = $row['brand'];
                $vendor = '<span class="vid">id['.$row['default_vendor_id'].'] </span>'.$row['vendorName'];
                $vd = $row['default_vendor_id'].' '.$row['vendorName'];
                $dept = $row['department'].' '.$row['dept_name'];
                $pid = $row['price_rule_id'];
                $unfiMarg = $row['unfiMarg'];
                $deptMarg = $row['deptMarg'];
                $signDesc = $row['signdesc'];
                $signBrand = $row['signbrand'];
                $inUse = $row['inUse'];
                $narrow = $row['narrow'];
                $markup = $row['shippingMarkup'];
                $discount = $row['discountRate'];

                $adjcost = $cost;
                if ($markup > 0) $adjcost += $cost * $markup;
                if ($discount > 0) $adjcost -= $cost * $discount;

                if ($row['default_vendor_id'] == 1) {
                    $dMargin = $row['unfiMarg'];
                } else {
                    $dMargin = $row['deptMarg'];
                }
            }
            if ($dbc->error()) echo $dbc->error();

            $margin = ($price - $adjcost) / $price;
            $rSrp = $adjcost / (1 - $dMargin);
            $rounder = new PriceRounder();
            $srp = $rounder->round($rSrp);
            $sMargin = ($srp - $adjcost ) / $srp;

            $sWarn = 'default';
            if ($srp != $price) {
                if ($srp > $price) {

                } else { //$srp < $price
                    $peroff = $srp / $price;
                    if ($peroff < .05) {
                        $sWarn = '';
                    } elseif ($peroff > .15 && $peroff < .30) {
                        $sWarn = 'warning';
                    } else {
                        $sWarn = 'danger';
                    }
                }
            }
            $passcost = $cost;
            if ($cost != $adjcost) $passcost = $adjcost;
            
            $argsA = array($upc,$username,$storeID);
            $prepA = $dbc->prepare("SELECT * FROM woodshed_no_replicate.AuditScanner WHERE upc = ? AND username = ? AND store_id = ? LIMIT 1");
            $resA = $dbc->execute($prepA,$argsA);
            if ($dbc->numRows($resA) == 0) {
                $args = array(
                    $upc,
                    $brand,
                    $description,
                    $price,
                    $margin,
                    $deptMarg,
                    $dept,
                    $vendor,
                    $rSrp,
                    $srp,
                    $pid,
                    $sWarn,
                    $cost,
                    $storeID,
                    $username
                );
                $prep = $dbc->prepare("
                    INSERT INTO woodshed_no_replicate.AuditScanner
                    (
                        upc, brand, description, price, curMarg, desMarg, dept,
                            vendor, rsrp, srp, prid, flag, cost, store_id,
                            username
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    );
                ");
                $dbc->execute($prep,$args);
                if ($dbc->error()) {
                    $ret .= '<div class="alert alert-danger">' . $dbc->error() . '</div>';
                } else {
                    
                }
            }
        }
        
        return $ret;
    }
    
    public function body_content()
    {
    
        $ret = '';
        
        include('../config.php');
        include('../common/lib/PriceRounder.php');
        
        $rounder = new PriceRounder();
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        $storeID = scanLib::getStoreID(); 
        $username = scanLib::getUser();
        
        $routes = array(
            'cleardata' => 'clear_scandata_hander',
            'update' => 'update_scandata_handler',
            'clearNotes' => 'clear_notes_handler',
            'list_upcs' => 'list_upcs_handler'
        );
        foreach ($routes as $post => $function) {
            if ($_POST[$post]) {
                $ret .= $this->$function($dbc,$storeID,$username);
            }
        }
        
        $ret .= $this->form_content();
        
        //delete me later
        $ret .= '<div id="resp"></div>';
        
        $options = $this->get_notes_options($dbc,$storeID,$username);
        $noteStr = '';
        $noteStr .= '<select id="notes" class="" style="font-size: 10px; font-weight: normal; margin-left: 5px; width: 100px;">';
        $noteStr .= '<option value="viewall">View All</option>';
        foreach ($options as $k => $option) {
            $noteStr .= '<option value="'.$k.'">'.$option.'</option>';
        }
        $noteStr .= '</select>';
        
        $ret .= '
            <table class="table table-bordered table-condensed small" style="width: 500px;"> 
                <tr class="key"><td>Key</td><td>
                </td></tr>
                <tr class="key"><td id="grey-toggle" style="background-color: lightgrey">&nbsp;</td><td>Product Missing Cost</td>
                <td id="blue-toggle" style="background-color: lightblue; width: 30px">&nbsp;</td><td>Price Above Margin</td></tr>
                <tr class="key"><td id="yellow-toggle" style="background-color: #FFF457">&nbsp;</td><td>Price Below Margin (M)</td>
                <td id="red-toggle" style="background-color: tomato; width: 30px; ">&nbsp;</td><td>Price Below Margin (L)</td></tr>
            </table>
        ';
        //$ret .= $btnUpdate;
        $args = array($username,$storeID);
        $query = $dbc->prepare("
        	SELECT upc, brand, description, cost, price, curMarg, desMarg, rsrp, srp, prid, flag, dept, vendor, notes, store_id, username
			FROM woodshed_no_replicate.AuditScanner 
            WHERE username = ? 
                AND store_id = ?
            ORDER BY vendor, dept, brand;
        ");    
        $result = $dbc->execute($query,$args);
        $data = array();
        $headers = array();
        $i = 0;
        //  Define <th> & <td> data
        while ($row = $dbc->fetch_row($result)) {
            foreach ($row as $k => $v) {
                if(!is_numeric($k)) {
                    //  @data[x][header] = row
                    $data[$i][$k] =  $v;
                    //  @headers[header] = header
                    $headers[$k] = $k;
                }
            }
            $i++;
        }
        
        //  Add columns to table
        $i = 0;
        $flags = array();
        foreach ($data as $k => $array) { 
            $srp = $data[$k]['srp'];
            $price = $data[$k]['price'];
            $difference = sprintf("%0.3f",$srp - $price);
            $margin = $data[$k]['curMarg'];
            $dMargin = $data[$k]['desMarg'];
            //  Create flags to change color of <tr>
            $margOff = ($margin / $dMargin);
            if ($margOff > 1.05 && $srp != $price) {
                $flags['info'][] = $i;
            } elseif ($margOff > 0.95) {
            } elseif ($margOff < 0.95 && $margOff > 0.90 
                && $srp != $price
                && $srp >= $price) {
                $flags['warning'][] = $i;
            } elseif ($srp != $price && $srp >= $price) {
                $flags['danger'][] = $i;
            }
            $i++;
        }
        
        $ret .= '
            <style>
                .price {
                    color: darkslategrey;
                    font-weight: bold;
                }
            </style>
        ';
        
        $ret .=  '<div class="panel panel-default">
            <table class="table table-condensed" id="dataTable">';
        $ret .=  '<thead class="key" id="dataTableThead">
            <tr class="key">';
        foreach ($headers as $v) {
            if ($v == 'notes') {
                $ret .=  '<th class="key">' . $v . $noteStr . '</th>';
            } elseif($v == 'store_id') {
                $ret .=  '<th class="key">' . 'store' . '</th>';
            } elseif($v == NULL ) {
                //do nothing
            } else {
                $ret .=  '<th class="key">' . $v . '</th>';
            }
        }
        $ret .=  '<th></th></tr></thead>';
        $prevKey = '1';
        $ret .= '<tbody id="mytable">';
        $ret .=  '<tr class="key" id="firstTr" class="highlight">';
        $upcs = array();
        
        foreach ($data as $k => $array) { 
            foreach ($array as $column_name  => $v) {
                if ($column_name == 'store_id') {
                    $ret .=  '<td class="store_id">' . $v . '</td>'; 
                } elseif ($column_name == 'notes') {
                    if ($v == NULL) {
                        $v = 'n/a';
                    }
                    $ret .=  '<td class="notescell">' . $v . '</td>'; 
                } elseif ($column_name == 'price' || $column_name == 'srp') {
                    $ret .=  '<td class="price">$' . $v . '</td>'; 
                } elseif ($column_name == 'upc') {
                    $upclink = '<a class="upc" href="http://192.168.1.2/git/fannie/item/ItemEditorPage.php?searchupc='.$v.
                        '&ntype=UPC&searchBtn=" target="_blank">'.$v.'</a>';
                    $ret .=  '<td>' . $upclink . '</td>'; 
                    $curUpc = $v;
                } elseif ($column_name == 'curMarg' || $column_name == 'desMarg') {
                    $ret .=  '<td>' . 100*$v . '<span class="smSymb">%</span></td>';
                } elseif ($column_name == 'username') {
                    $ret .= '<td class="username">'.$v.'</td>';
                } else {
                    $ret .=  '<td>' . $v . '</td>'; 
                }
            }
            $ret .= '<td  id="upc'.$curUpc.'"><span class="delete-icon"></span></td>';
            $ret .= '</tr>';
            
            if($prevKey != $k) {
                if ($data[$k+1]['cost'] == 0) {
                    $ret .=  '</tr><tr id="tr'.$curUpc.'" class="highlight grey" style="background-color:lightgrey">';
                } elseif (in_array(($k+1),$flags['danger']) && $data[$k+1]['prid'] == 0) {
                    $ret .=  '</tr><tr id="tr'.$curUpc.'"  class="highlight red" style="background-color:tomato; color:#700404">';
	            } elseif (in_array(($k+1),$flags['warning']) && $data[$k+1]['prid'] == 0) {
                    $ret .=  '</tr><tr id="tr'.$curUpc.'"  class="highlight yellow" style="background-color:#FFF457; color: #635d00">';
                } elseif (in_array(($k+1),$flags['info']) && $data[$k+1]['prid'] == 0) {
                    $ret .=  '</tr><tr id="tr'.$curUpc.'"  class="highlight blue" style="background-color:lightblue; color: #344c57">';
                } else {
                    $ret .=  '</tr><tr id="tr'.$curUpc.'"  class="highlight normal">';
                }
            } 
            $prevKey = $k;
        }
        $ret .=  '</tbody></table></div>';
        if ($dbc->error()) $ret .=  $dbc->error();
        
        $ret .= '
                <style>
                    .grey {
                        background-color:lightgrey;
                    }
                    #grey-toggle:hover {
                        cursor: pointer;
                    }
                    .red {
                        background-color:tomato; 
                        color:#700404
                    }
                    #red-toggle:hover {
                        cursor: pointer;
                    }
                    .yellow {
                        background-color:#FFF457; 
                        color: #635d00
                    }
                    #yellow-toggle:hover {
                        cursor: pointer;
                    }
                    .blue {
                        background-color:lightblue; 
                        color: #344c57
                    }
                    #blue-toggle:hover {
                        cursor: pointer;
                    }
                    .smSymb {
                        font-size: 12px;
                    }
                </style>
            ';
        
        if ($storeID == 1) {
            $OppoID = 2;
        } else {
            $OppoID = 1;
        }
        $ret .= $this->javascript_content($OppoID);
        
        return $ret;
    }
    
    private function get_notes_options($dbc,$storeID,$username)
    {
        $args = array($storeID,$username);
        $query = $dbc->prepare("SELECT notes FROM woodshed_no_replicate.AuditScanner WHERE store_id = ? AND username = ? GROUP BY notes;");    
        $result = $dbc->execute($query,$args);
        $options = array();
        while ($row = $dbc->fetch_row($result)) {
            if ($row['notes'] != '') {
                $options[] = $row['notes'];
            }
        }
        echo $dbc->error();
        return $options;
    }
    
    private function form_content()
    {
        
        $ret = '';
        $ret .= '
            <div style="float: right;">
                <table class="buttonbox key">
                    <thead></thead>
                    <tbody>
                        <tr class="buttonbox key">
                            <form method="post" id="clearNotesForm">
                                <td id="clearNotesInput" class="buttonbox key">Clear Notes</td>
                                <input type="hidden" name="clearNotes" value="1" />
                            </form>
                            <form method="post" id="clearAllForm">
                                <td id="clearAllInput" class="buttonbox">Clear ALL</td>
                                <input type="hidden" name="cleardata" value="1" />
                            </form>
                        
                            <form method="post" id="updateForm">
                                <td id="updateInput" class="buttonbox">Update from POS</td>
                                <input type="hidden" name="update" value="1">
                            </form>
                            <td class="buttonbox" data-toggle="modal" data-target="#upcs_modal">Upload a List</td>
                    </tbody>
                </table>
                
                <a class="text-info" style="width: 132px;" href="AuditScanner.php ">Goto Scanner</a><br />
            </div>
        ';
        
        $ret .= '
            <div id="upcs_modal" class="modal">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h3 class="modal-title" style="color: #8c7b70">Upload a list of UPCs to scan</h3>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                            style="position: absolute; top:20; right: 20">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                      <div class="modal-body">
                        <div align="center">
                            <form method="post" class="form-inline">
                                <input type="hidden" name="list_upcs" value="1">
                                <textarea class="form-control" name="upcs" rows="10" cols="50"></textarea>
                                <button type="submit" class="btn btn-default btn-xs">Submit</button>
                            </form>
                        </div>
                      </div>
                    </div>
                </div>
            </div>
        ';
        
        return $ret;
    }
    
    public function javascript_content($e)
    {
        ob_start();
        ?>
<script type="text/javascript">
    $("tr").each(function() { 
        var op_store = '.$e.';
        var id = $(this).find(\'td.store_id\').text();
        if (id == op_store) {
            $(this).closest(\'tr\').hide();
        }
    });
</script>

<script type="text/javascript">
$("#notes").change( function() {
    var noteKey = $("#notes").val();
    var note = $("#notes").find(":selected").text();
    $("#mytable").each(function() {
        $(this).find("tr").each(function() {
            $(this).show();
        });
    });
    $("#mytable").each(function() {
        $(this).find("tr").each(function() {
        var notecell = $(this).find(".notescell").text();
            if (note != notecell) {
                $(this).closest("tr").hide();
            }
            if (noteKey == "viewall") {
                $(this).show();
            }
            $(".blankrow").show();
        });
    });
});
</script>

<script type="text/javascript">
    $(document).ready( function() {
        fancyButtons();
        //linksToText();
        deleteRow();
    });
    
    function fancyButtons()
    {
        $("#clearNotesInput").click( function () {
            var r = confirm("Pressing OK will clear all notes from this queue.");
            if (r == true) {
                $("#clearNotesForm").submit();
            }
            event.stopPropagation();
        });
        $("#clearAllInput").click( function () {
            var r = confirm("Pressing OK will delete all data from this queue.");
            if (r == true) {
                $("#clearAllForm").submit();
            }
            event.stopPropagation();
        });
        $("#updateInput").click( function () {
            var r = confirm("Pressing OK will update product data from Fannie.");
            if (r == true) {
                $("#updateForm").submit();
            }
            event.stopPropagation();
        });
        
    }

    function linksToText() {
        $('.upc').each( function() {
            $(this).removeAttr('href');
        });
    }
    
    function deleteRow() {
        $('.delete-icon').click( function() {
            var upc = $(this).closest('td').attr('id');
            var store_id = $(this).closest('tr').find('.store_id').text();
            var username = $(this).closest('tr').find('.username').text();
            var rowclicked = $(this).closest('tr').attr('id')   ;
            var r = confirm('Remove '+upc+' from Queue?');
            if (r == true) {
                $.ajax({        
                    url: 'AuditScannerReportAjax.php',
                    type: 'post',
                    data: 'store_id='+store_id+'&upc='+upc+'&username='+username+'&deleteRow=true',
                    success: function(response)
                    {
                        if($('#'+rowclicked).length == 0) {
                            $('#firstTr').hide();
                        } else {
                            $('#'+rowclicked).hide();
                        }
                        $('#resp').html(response);
                    }
                });
            }
            event.stopPropagation();
        });
    }
</script>

<script type="text/javascript">    
    function redrawDataTable()
    {
        $('#dataTable').each(function() {
            $('tr').each(function () {
                $(this).show();
            });
        });   
    }

    $(document).ready(function () {
        $('#red-toggle').click(function () {
            redrawDataTable();
            $('#dataTable').each(function() {
                $('tr').each(function () {
                    if ( !$(this).hasClass('red') && !$(this).hasClass('key') ) {
                        $(this).hide();
                    }
                });
            });       
        });
        $('#yellow-toggle').click(function () {
            redrawDataTable();
            $('#dataTable').each(function() {
                $('tr').each(function () {
                    if ( !$(this).hasClass('yellow') && !$(this).hasClass('key') ) {
                        $(this).hide();
                    }
                });
            });       
        });
        $('#blue-toggle').click(function () {
            redrawDataTable();
            $('#dataTable').each(function() {
                $('tr').each(function () {
                    if ( !$(this).hasClass('blue') && !$(this).hasClass('key') ) {
                        $(this).hide();
                    }
                });
            });       
        });
        $('#grey-toggle').click(function () {
            redrawDataTable();
            $('#dataTable').each(function() {
                $('tr').each(function () {
                    if ( !$(this).hasClass('grey') && !$(this).hasClass('key') ) {
                        $(this).hide();
                    }
                });
            });       
        });
    });
</script>
        <?php
        return ob_get_clean();
    }
    
    
}
ScancoordDispatch::conditionalExec();
