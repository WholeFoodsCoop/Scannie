<?php
if (!class_exists('Search')) {
    require('Search.php');
}
Class coreNav
{
    public $ln = array(
        'Home' => "__DIR__./../../Home/Home.php",
    );

    public function run()
    {
        $ret = '';
        $menu = new coreNav();
        $ret .= $menu->navBar();
        return $ret;
    }

    public function navBar()
    {
        include(__DIR__.'/../../config.php');
        $helptoggle = <<<JAVASCRIPT
var hidden = $('#help-contents').is(':visible');
if (hidden == false) {
    $('#help-contents').show();
} else {
    $('#help-contents').hide();
}
JAVASCRIPT;

        $DIR = __DIR__;
        $user = null;
        $ud = "";
        if (!empty($_SESSION['user_name'])) {
            $user = $_SESSION['user_name'];
            $ud = '<span class="userSymbol"><b>'.strtoupper(substr($user,0,1)).'</b></span>';
        }
        if (empty($user)) {
            $user = 'Generic User';
            $logVerb = 'Login';
            $link = "<a class='nav-login' href='http://{$MY_ROOTDIR}/auth/login.php'>[{$logVerb}]</a>";
        } else {
            $logVerb = 'Logout';
            $link = "<a class='nav-login' href='http://{$MY_ROOTDIR}/auth/logout.php'>[{$logVerb}]</a>";
        }
        $loginText = '
            <div style="color: #cacaca; margin-left: 25px; margin-top: 5px;" align="center">
                <span style="color:#cacaca">'.$ud.'&nbsp;'.$user.'</span><br/>
            '.$link.' 
            </div>
       ';

        return <<<HTML
<img class="backToTop collapse no-print" id="backToTop" src="http://$MY_ROOTDIR/common/src/img/upArrow.png" />
<!--<nav class="navbar navbar-expand-md navbar-dark bg-dark mynav">-->
<nav class="navbar navbar-expand-md navbar-dark bg-custom mynav no-print">
  <a class="navbar-brand" href="http://{$MY_ROOTDIR}">ScannieV2</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item dropdown active">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            CORE-POS
        </a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
          <a class="dropdown-item" href="http://{$FANNIE_ROOTDIR}">OP.CORE-POS</a>
          <a class="dropdown-item" href="http://{$FANNIE_COREY_ROOT}">corey.CORE-POS</a>
          <a class="dropdown-item" href="http://{$FANNIE_ANDY_ROOT}">andy.CORE-POS</a>
        </div>
      </li>
      <!--
      <li class="nav-item">
        <a class="nav-link" href="#">Link</a>
      </li>
      -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Products 
        </a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
          <a class="dropdown-item" href="http://{$MY_ROOTDIR}/content/Item/CheckScannedDate.php">Check PLU Queues</a>
          <a class="dropdown-item" href="http://{$MY_ROOTDIR}/content/Item/LastSoldDates.php?paste_list=1">Last Sold</a>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Reports 
        </a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
          <div class="nav-item nav-label" align="">Tables</div>
          <a class="dropdown-item" href="http://{$MY_ROOTDIR}/content/Tables/CoopDealsFile.php">Coop Deals File</a>
          <div class="nav-item nav-label" align="">Reports</div>
          <a class="dropdown-item" href="http://{$MY_ROOTDIR}/content/Reports/WeeklySalesByWeek.php">Weekly Sales By Week</a>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Sales & Pricing
        </a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
          <a class="dropdown-item" href="http://{$MY_ROOTDIR}/content/Item/Batches/BatchReview/BatchReviewPage.php">Review Batches</a>
          <a class="dropdown-item" href="http://{$MY_ROOTDIR}/content/Item/Batches/CoopDeals/CoopDealsReview.php">Q.A. & Breakdowns</a>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Scanning
        </a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
          <a class="dropdown-item" href="http://{$MY_ROOTDIR}/content/Home/NewPage.php">Scan Dept. <strong>Dashboard</strong></a>
          <a class="dropdown-item" href="http://{$MY_ROOTDIR}/content/Scanning/BatchCheck/SCS.php"><strong style="color: green">Batch Check</strong> Scanner</a>
          <a class="dropdown-item" href="http://{$MY_ROOTDIR}/content/Scanning/BatchCheck/BatchCheckQueues.php?option=1"><strong style="color: green">Batch Check</strong> Report</a>
          <a class="dropdown-item" href="http://{$MY_ROOTDIR}/content/Scanning/AuditScanner/AuditScanner.php"><strong style="color: #4286f4">Audit</strong> Scanner</a>
          <a class="dropdown-item" href="http://{$MY_ROOTDIR}/content/Scanning/AuditScanner/AuditScannerReport.php"><strong style="color: #4286f4">Audit</strong> Report</a>
          <a class="dropdown-item" href="http://{$MY_ROOTDIR}/content/Scanning/AuditScanner/BasicsScan.php"><strong style="color: purple">Basics</strong> Scan</a>
          <!--
          <div class="dropdown-divider"></div>
          <a class="dropdown-item" href="#"></a>
          -->
        </div>
      </li>
      <li class="nav-item">
        <a class="nav-link" onclick="{$helptoggle}" href="#">Help</a>
      </li>
      <!--
      <li class="nav-item">
        <a class="nav-link disabled" href="#">Disabled</a>
      </li>
      -->
    </ul>
    <div id="nav-search-container">
    <form class="form-inline my-2 my-lg-0">
      <input class="form-control mr-sm-2" type="search" id="nav-search" placeholder="Search" aria-label="Search">
      <div id="search-resp"></div>
    </form>
    </div>
    <div class="login-nav">
        $loginText
    </div>
  </div>
  <div class="toggle-control-center">
  </div>
</nav>
<div class="control-center">
</div>
HTML;
    }

}


















