<?php
if (!class_exists('menu')) {
    require('MenuClass.php');
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
        // $navbar = menu::nav_menu();

        return <<<HTML
<nav class="navbar navbar-expand-md navbar-dark bg-dark mynav">
  <a class="navbar-brand" href="http://{$MY_ROOTDIR}">ScannieV2</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item active">
        <a class="nav-link" href="http://{$FANNIE_ROOTDIR}">CORE-POS<span class="sr-only">(current)</span></a>
      </li>
      <!--
      <li class="nav-item">
        <a class="nav-link" href="#">Link</a>
      </li>
      -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Scan
        </a>
        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
          <a class="dropdown-item" href="../Scanning/BatchCheck/SCS.php">Batch Check Scanner</a>
          <a class="dropdown-item" href="../Scanning/BatchCheck/BatchCheckQueues.php?option=1">Batch Check Report</a>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item" href="#"></a>
        </div>
      </li>
      <!--
      <li class="nav-item">
        <a class="nav-link disabled" href="#">Disabled</a>
      </li>
      -->
    </ul>
    <form class="form-inline my-2 my-lg-0">
      <input class="form-control mr-sm-2" type="search" placeholder="Search" aria-label="Search">
    </form>
    <div class="login-nav">
        Login options 
    </div>
  </div>
  <div class="toggle-control-center">
    &nbsp;&nbsp;+&nbsp;
  </div>
</nav>
<div class="control-center">
  This is where scannie alerts will be viewed.
</div>
HTML;
    }

}


















