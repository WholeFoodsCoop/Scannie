<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op.

    This file is a part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file LICENSE along with CORE-POS; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
class menu
{
    public function nav_menu()
    {
        include('../../config.php');
        $ret = '';

        $ret .= '
            <style>
            span.hd.grey {
                background-color: grey;
            }
            span.hd.green {
                background-color: green;
                color: white;
                font-size: 12px;
                padding: 2px;
                font-family: consolas;
                border-radius: 2px;

            }
            span.hd.blue {
                background-color: lightblue;
            }
            a.menuNav {
                width: 160px;
            }
            a.menu-opt {
                color: red;
                //background-color: #e8e8e8;
                border: 1px solid white;
                border-radius: 2px;
            }

            .tinyInput {
                height: 20px;
                width: 125px;
                font-size: 10px;
                border: 1px solid lightgrey;
                border-radius: 2px;
            }

            .tooltip1 { position: relative; }
            .tooltip1 a span { display: none; color: #FFFFFF; }
            .tooltip1 a:hover span { display: block; position: absolute; width: 200px; background: #aaa url(images/horses200x50.jpg); height: 50px; left: 100px; top: -10px; color: #FFFFFF; padding: 0 5px; }

            iframe.menu {
                opacity: 0.95;
            }

            </style>
        ';

        $ret .= '
            <div align="center">
                <a class="hidden-md hidden-lg hidden-sm" href="http://192.168.1.2/scancoord/testing/SiteMap.php">
                    Site Map
                </a>
            </div>
        ';

        $ret .=  '
<div class="container-fluid"  align="center" style="height:80px;width:1000px; ">

    <div class="navbar navbar-default collapse in hidden-xs hidden-print" style="background-color:white;border:none">
        <ul class="nav navbar-nav">
            <li class="dropdown"><a  class="dropdown-toggle menuNav" data-toggle="dropdown" data-target="#" href="#">Item<span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">
                    <li><a class="menu-opt" href="http://192.168.1.2/scancoord/item/last_sold_check.php">Last Sold</a></li>
                    <li><a class="menu-opt" href="http://192.168.1.2/scancoord/item/TrackChangeNew.php">Track Change</a></li>
                    <li><a class="menu-opt" href="" onclick="popitup(\'http://192.168.1.2/scancoord/item/MarginCalcNew.php\')">Margin Calc</a></li>
                    <li><a class="menu-opt" href="" onclick="popitup(\'http://192.168.1.2/scancoord/item/PercentCalc.php\')">Percent Calc</a></li>
                <li class="divider"></li><!-- divider with no header -->
                <li class="dropdown-header">Scanning</li>
                    <li><a class="menu-opt" href="http://192.168.1.2/scancoord/item/AuditScanner.php">Audit Scanner</a></li>
                    <li><a class="menu-opt" href="http://192.168.1.2/scancoord/item/AuditScannerReport.php">Audit Scan Report</a></li>
            </ul></li>

            <li class="dropdown"><a  class="dropdown-toggle menuNav" data-toggle="dropdown" data-target="#" href="#">Batches<span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">
                <li class="dropdown-header">Basics</li>
                    <li><a class="menu-opt" href="http://192.168.1.2/scancoord/item/coopBasicsScanPage.php">Basics Scan</a></li>
                <li class="divider"></li><!-- divider with no header -->
                <li class="dropdown-header">UNFI Sales Change</li>
                    <li class="test"><a class="test" href="http://192.168.1.2/scancoord/item/SalesChange/SalesChangeIndex.php"> Batch Check </a></li>
                    <li><a class="menu-opt" href="http://192.168.1.2/scancoord/item/SalesChange/CoopDealsReview.php">Quality Assurance</a></li>
                    <li><a class="menu-opt" href="http://192.168.1.2/scancoord/item/Batches/unfiBreakdowns.php">Breakdowns</a></li>
                    <li><a class="menu-opt" href="http://192.168.1.2/scancoord/item/SignInfoHelper.php">Sign Info</a></li>
                    <li><a class="menu-opt" href="http://192.168.1.2/scancoord/item/Batches/prodBatchHistory.php">Item Batch History</a></li>
                    <li><a class="menu-opt" href="http://192.168.1.2/scancoord/item/Batches/CoopDealsSearchPage.php">Coop+Deals File</a></li>
                <li class="divider"></li><!-- divider with no header -->
                <li class="dropdown-header">Price Changes</li>
                    <li><a class="menu-opt" href="http://192.168.1.2/scancoord/item/Batches/batchForceCheck.php">Batch Force Check</a></li>
                    <li><a class="menu-opt" href="http://192.168.1.2/scancoord/item/Batches/BatchReview/">Batch Review</a></li>
                    <li><a class="menu-opt" href="http://192.168.1.2/scancoord/item/Batches/CheckBatchPercent.php">Sales Batch %</a></li>
            </ul>
            </li>

            <li class="dropdown"><a  class="dropdown-toggle menuNav" data-toggle="dropdown" data-target="#" href="#">Data<span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">
                <li class="dropdown-header">Discrepancy Tasks</li>
                    <li><a class="menu-opt" href="http://192.168.1.2/scancoord/dataScanning/zeroPriceCheck.php">Bad Price Scan</a></li>
                    <li><a class="menu-opt" href="http://192.168.1.2/scancoord/dataScanning/ExceptionSaleItemTracker.php">Exception Sale Items</a></li>
                    <li><a class="menu-opt" href="http://192.168.1.2/scancoord/dataScanning/MultiStoreDiscrepTable.php">Multi-Store Prod Discrep</a></li>
                    <li><a class="menu-opt" href="http://192.168.1.2/scancoord/dataScanning/CashlessCheckPage.php">Cashess Trans. Check</a></li>
                    <li><a class="menu-opt" href="http://192.168.1.2/scancoord/misc/ProdUserChangeReport.php">Prod User Change</a></li>
                </ul>
            </li>

            <li class="dropdown"><a class="menuNav"  href=""
                data-toggle="modal" data-target="#help">Help<span class=""></span></a>

           <!-- <li><a class="menuNav"  href="" data-toggle="modal" data-target="#quick_lookups">QLU<span class=""></span></a></li> -->

            <li class="dropdown" style="margin-top: 15px;"><input class="tinyInput menuNav" id="searchbar" name="search" placeholder="search scannie"/></a></li>

            <li style="padding: 10px;"></li>

            <li><span><img class="menuIcon"
                src="http://192.168.1.2/scancoord/common/src/img/calc.png"
                onClick="calcView(\'marginCalc\');"></span>

                <span ><img class="menuIcon"
                src="http://192.168.1.2/scancoord/common/src/img/percentCalc.png"
                onClick="calcView(\'percentCalc\');" ></span>
            </li>

            <li></li>
    </div>
</div>
        ';

        $ret .= '<div id="search-resp"></div>';

        $ret .= self::calciframes();

        return $ret;
    }

    private function calciframes()
    {
        $ret = '';
        $ret .= '<iframe class="fixedCalc menu collapse" id="marginCalc" frameBorder="0"
            src="http://192.168.1.2/scancoord/item/MarginCalcNew.php?iframe=true"></iframe>';
        $ret .= '<iframe class="fixedCalc menu collapse" id="percentCalc" frameBorder="0"
            src="http://192.168.1.2/scancoord/item/PercentCalc.php?iframe=true"></iframe>';
        $ret .= '<img class="backToTop collapse" id="backToTop" src="http://192.168.1.2/scancoord/common/src/img/upArrow.png" />';
        return $ret;
    }

}
?>

<script language="javascript" type="text/javascript">
function popitup(url) {
	newwindow=window.open(url,'name','height=300,width=300');
	if (window.focus) {newwindow.focus()}
	return false;
}

$(document).ready( function () {
        $('#searchbar').keypress( function () {
            var text = $("#searchbar").val();
            if (text.length) {
                //alert(text);
                getSearchResults(text);
            } else {
                $('#search-resp').html('')
            }
        });
        backToTop();
});

function getSearchResults(search)
{
    $.ajax({
        url: '../common/ui/searchbar.php',
        //dataType: 'POST',
        data: 'search='+search,
        success: function(response)
        {
            $('#search-resp').html(response);
        }
    });
}

function calcView(name)
{
    if ( $('#'+name).is(":visible") ) {
        $('#'+name).hide();
    } else {
        $('#'+name).show();
    }
}

function backToTop()
{
    $(window).scroll(function () {
        var scrollTop = $(this).scrollTop();
        //alert(scrollTop);
        if (scrollTop != 0) {
            $('#backToTop').show();
        } else {
            $('#backToTop').hide();
        }

        $('.background1, .background2').each(function() {
            var topDistance = $(this).offset().top;

            if ( (topDistance+100) < scrollTop ) {
                alert( $(this).text() + ' was scrolled to the top' );
            }
        });

        if ($(window).scrollTop() > $('body').height() / 2) {
            $('#backToTop').show();
        }
    });

    $('#backToTop').click(function(){
        $("html, body").animate({ scrollTop: 0 }, "fast");
    });
}
</script>



