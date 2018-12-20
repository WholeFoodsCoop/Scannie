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
    Foundation, Inc., 29 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
class menu
{
    
    public function nav_menu()
    {
        include(__DIR__.'/../../config.php');
        $ret = '';
        $ret .= '<img class="backToTop collapse" id="backToTop" src="http://'.$SCANROOT_DIR.'/common/src/img/upArrow.png" />';
        $calculators = self::calciframes();
        $mobileMenu = self::mobileMenu();

        $user = (!empty($_SESSION['user_name'])) ? $_SESSION['user_name'] : null;
        $ud = '<span class="userSymbol"><b>'.strtoupper(substr($user,0,1)).'</b></span>';
        if (empty($user)) {
            $user = 'Generic User';
            $logVerb = 'Login';
            $link = "<a href='http://{$SCANROOT_DIR}/admin/login.php'>[{$logVerb}]</a>";
        } else {
            $logVerb = 'Logout';
            $link = "<a href='http://{$SCANROOT_DIR}/admin/logout.php'>[{$logVerb}]</a>";
        }
        $loginText = '
            <div style="color: #cacaca; margin-left: 25px; margin-top: 5px;">
                <span style="color:#cacaca">'.$ud.'&nbsp;'.$user.'</span><br/>
            '.$link.' | <a href="http://'.$SCANROOT_DIR.'/testing/SiteMap.php">Site Map</a>
            </div>
       ';

        return <<<HTML
<div class=""  align="center">
    <div class="mainNavBox" align="center">
    <div class="navbar navbar-default hidden-xs hidden-sm hidden-print" id="mainNav">
        <ul class="nav navbar-nav">
            <li> 
                <span class="hidden-sm hidden-xs">
                    <p class="navText">
                        <a class="logo" href="http://{$SCANROOT_DIR}">Scannie</a>
                        <a class="logo" href="http://{$FANNIEROOT_DIR}">CORE-POS</a>
                    </p>
                </span>
                <img src="http://{$SCANROOT_DIR}/common/src/img/wfcLogo_smaller.png" class="logo">
            </li>

            <li class="dropdown">
                <input class="form-control" id="searchbar" name="search" autocomplete="off" placeholder="search scannie"/></a>
                <div id="search-resp"></div>
            </li>

            <li class="dropdown">
                <a  class="dropdown-toggle menuNav" data-toggle="dropdown" data-target="#" href="#">Item<span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">
                    <li><a class="menu-opt" href="http://{$SCANROOT_DIR}/item/TrackChangeNew.php">Track Change</a></li>
                    <li><a class="menu-opt" href="" onclick="popitup('http://{$SCANROOT_DIR}/item/MarginCalcNew.php')">Margin Calc</a></li>
                    <li><a class="menu-opt" href="" onclick="popitup('http://{$SCANROOT_DIR}/item/PercentCalc.php')">Percent Calc</a></li>
                <li class="divider"></li><!-- divider with no header -->
                <li class="dropdown-header">Scanning</li>
                    <li><a class="menu-opt" href="http://{$SCANROOT_DIR}/item/AuditScanner.php">Audit Scanner</a></li>
                    <li><a class="menu-opt" href="http://{$SCANROOT_DIR}/item/AuditScannerReport.php">Audit Scan Report</a></li>
            </ul></li>
            <li class="dropdown"><a  class="dropdown-toggle menuNav" data-toggle="dropdown" data-target="#" href="#">Batches<span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">
                <li class="dropdown-header">Basics</li>
                    <li><a class="menu-opt" href="http://{$SCANROOT_DIR}/item/coopBasicsScanPage.php">Basics Scan</a></li>
                <li class="divider"></li><!-- divider with no header -->
                <li class="dropdown-header">UNFI Sales Change</li>
                    <li class="test"><a href="http://{$SCANROOT_DIR}/item/SalesChange/SalesChangeIndex.php" style="color: green"> Batch Check </a></li>
                    <li><a class="menu-opt" href="http://{$SCANROOT_DIR}/item/SalesChange/CoopDealsReview.php">QA & Breakdowns</a></li>
                    <li><a class="menu-opt" href="http://{$SCANROOT_DIR}/item/Batches/CoopDealsSearchPage.php">Coop+Deals File</a></li>
                <li class="divider"></li><!-- divider with no header -->
                <li class="dropdown-header">Price Changes</li>
                    <li><a class="menu-opt" href="http://{$SCANROOT_DIR}/item/Batches/BatchReview/">Batch Review</a></li>
            </ul>
            </li>
            <li class="dropdown"><a  class="dropdown-toggle menuNav" data-toggle="dropdown" data-target="#" href="#">Data<span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">
                <li class="dropdown-header">Discrepancy Tasks</li>
                    <li><a class="menu-opt" href="http://{$SCANROOT_DIR}/dataScanning/zeroPriceCheck.php">Bad Price Scan</a></li>
                    <li><a class="menu-opt" href="http://{$SCANROOT_DIR}/dataScanning/ExceptionSaleItemTracker.php">Pending Actions</a></li>
                    <li><a class="menu-opt" href="http://{$SCANROOT_DIR}/dataScanning/MultiStoreDiscrepTable.php">Multi-Store Prod Discrep</a></li>
                    <li><a class="menu-opt" href="http://{$SCANROOT_DIR}/dataScanning/CashlessCheckPage.php">Cashess Trans. Check</a></li>
                    <li><a class="menu-opt" href="http://{$SCANROOT_DIR}/misc/ProdUserChangeReport.php">Prod User Change</a></li>
                    <li><a class="menu-opt" href="http://{$SCANROOT_DIR}/dataScanning/specialPriceCheck.php">Special Price Scan</a></li>
                    <li><a class="menu-opt" href="" onclick="popitup('http://{$SCANROOT_DIR}/misc/ipod.php')">handheld</a></li>
                </ul>
            </li>
            <li class="dropdown"><a class="menuNav"  href=""
                data-toggle="modal" data-target="#help">Help<span class=""></span></a>
            <li style="padding: 10px;"></li>
            <li><span><a class="menuIcon"
                src="http://{$SCANROOT_DIR}/common/src/img/calc.png"
                data-toggle="collapse" data-target="#marginCalc">M</a></span>
                <span ><a class="menuIcon"
                src="http://{$SCANROOT_DIR}/common/src/img/percentCalc.png"
                data-toggle="collapse" data-target="#percentCalc">%</a></span>
            </li>
            <li>
               {$loginText} 
            </li>
    </div>
    </div>
</div>
{$ret}
{$calculators}
{$mobileMenu}
HTML;
    }

    private function calciframes()
    {
        include(__DIR__.'/../../config.php');
        return <<<HTML
<div id='marginCalc' class='fixedCalc collapse'><iframe class='fixedCalc menu' id='' frameBorder='0'
    src='http://{$SCANROOT_DIR}/item/MarginCalcNew.php?iframe=true'></iframe>
    <span class='menuCalcCloseBtn' data-toggle='collapse' data-target='#marginCalc'>x</span>
</div>
<div id='percentCalc' class='fixedCalc collapse'><iframe class='fixedCalc menu' id='' frameBorder='0'
    src='http://{$SCANROOT_DIR}/item/PercentCalc.php?iframe=true'></iframe>
    <span class='menuCalcCloseBtn' data-toggle='collapse' data-target='#percentCalc'>x</span>
</div>
HTML;
    }
    
    private function mobileMenu()
    {
        include(__DIR__.'/../../config.php');
        $menuOptions = array(
            "Item" => array(
                "Last Sold" => "http://{$SCANROOT_DIR}/item/last_sold_check.php",
                "Track Change" => "http://{$SCANROOT_DIR}/item/TrackChangeNew.php",
                "Audit Scanner" => "http://{$SCANROOT_DIR}/item/AuditScanner.php",
                "Audit Report"  => "http://{$SCANROOT_DIR}/item/AuditScannerReport.php"
            ),
            "Batches" => array(
                "BatchCheck" => "http://{$SCANROOT_DIR}/item/SalesChange/SalesChangeIndex.php",
                "ItemBatchHistory" => "http://{$SCANROOT_DIR}/item/Batches/prodBatchHistory.php",
                "CoopDealsFile" => "http://{$SCANROOT_DIR}/item/Batches/CoopDealsSearchPage.php",
                "BatchForceCheck" => "http://{$SCANROOT_DIR}/item/Batches/batchForceCheck.php"
            ),
            "Data" => array(
                "BadPriceScan" => "http://{$SCANROOT_DIR}/dataScanning/zeroPriceCheck.php",
                "ExceptionSaleItems" => "http://{$SCANROOT_DIR}/dataScanning/ExceptionSaleItemTracker.php",
                "Multi-StoreDiscreps" => "http://{$SCANROOT_DIR}/dataScanning/MultiStoreDiscrepTable.php"
            )
        );
        $ret = "";
        $ret = "<img src='http://{$SCANROOT_DIR}/common/src/img/menuIcon.png' 
            id='mobileMenuBtn' class='mobileMenuIcon hidden-lg hidden-md hidden-sm' />";
        $ret .= '
            <div id="mobileMenu" class="mobileMenu collapse">
                <ul>';
        foreach ($menuOptions as $k => $v) {
            if (is_array($v)) {
                $ret .= '<li class="mobileHeader" data-toggle="collapse" data-parent="#mobileMenu" data-target="#li'.$k.'">'.$k.'</li>';
                $ret .= '<ul class="collapse mobileColumn" id="li'.$k.'">';
                foreach ($v as $vk => $vv) {
                    $ret .=  '<li class="mobilePage"><a class="aPage" href="'.$vv.'">'.$vk.'</a></span></li>';
                }
                $ret .= '</ul>';
            } else {
                $ret .=  '<li class="mobileHeader">'.$v.'</li>';
            }
        }
        $ret .= '
        <ul class="mobileHeader" href="" data-toggle="modal" data-target="#help">Help</ul>
        <ul class="mobileHeader"><a href="http://'.$SCANROOT_DIR.'/admin/logout.php">Logout</a></ul>
                </ul>
                
                <div align="center"><span style="cursor: pointer; font-size: 18px;" 
                    id="closeMenu"><br />
                    
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                style="position: absolute; bottom: 20;">
                              <span aria-hidden="true">&times;</span>
                            </button>
                    <br /><br /></span></div>
            </div>
        ';
        
        return $ret;
    }
    
}
?>
