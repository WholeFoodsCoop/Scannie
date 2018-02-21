<?php
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
        //ideally, navBar will take the sitemap of ScannieV2 and create the 
        //nav based on Directory Hierarchy

        return <<<HTML
HTML;
    }

}


















