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

class ScancoordDispatch 
{
    
    protected $start_timestamp = NULL;
    protected $must_authenticate = false;
    protected $current_user = false;
    protected $auth_classes = array();
    protected $add_css_content = false;
    protected $add_javascript_content = false;
    
    function __construct() {
        $this->start_timestamp = microtime(true);
        $auth_default = NULL;
        $css_content = FALSE;
    }
    
    private function runPage($class)
    {
        $obj = new $class();
        $obj->draw_page($class);
        
    }
    
    private function draw_page($class)
    {
        if (!class_exists('MenuClass')) {
            include(dirname(__FILE__).'/MenuClass.php');
        }
        print "<br />";
        print $this->header($class);
        
        if ($this->ui === TRUE) {
            print '
                <div class="container" style="width:95%; ">
                    <div style="font-size:28px;margin-bottom:5px;" class="primaryColor">
                        <img src="/scancoord/common/src/img/scanner.png" style="width:75px;heigh:75px;float:left">
                        <a href="/scancoord">Scannie</a>
                        <!-- this span is for detecting bootstrap screensize -->
                            <span class="device-xs visible-xs"></span>
                            <div style="font-size:20px;" class="secondaryColor" data-toggle="collapse" data-target=".navbar-default" onclick="smartToggle();">
                                IT COREY maintenance &amp; reporting <span style="color: grey;">|</span>  <span style="color: #8c7b70;">'.$this->title.'</span>
                            </div>
                    </div>
                </div>
            ';
            //print '<div class="container" id="border" style="width:95%;padding:5px">';
            print '<div class="container" id="" style="min-height: 850px;width:95%;border:1px solid lightgrey; padding:5px; background-color: white">';
            print menu::nav_menu();    
        }
        
        print $this->body_content();        
        print '</div></div></div>';
        print $this->footer();
    }
    
    static public function conditionalExec($custom_errors=true)
    {
        $frames = debug_backtrace();
        // conditionalExec() is the only function on the stack
        if (count($frames) == 1) {
            // draw current page
            $page = basename(filter_input(INPUT_SERVER, 'PHP_SELF'));
            $class = substr($page,0,strlen($page)-4);
            if ($class != 'index' && class_exists($class)) {
                self::runPage($class);
            } else {
                trigger_error('Missing class '.$class, E_USER_NOTICE);
            }
            
        }
    }
    
    private function preflight() {}
    
    private function header($class)
    {   
        $ret = '';
        $ret .= '
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/scancoord/common/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/scancoord/common/lib/Scannie_css.css?foo=bar">
    <script src="/scancoord/common/bootstrap/jquery.min.js"></script>
    <script src="/scancoord/common/bootstrap/bootstrap.min.js"></script>
    <title>' . $this->title . '</title>
<style>';
        //$ret .= self::css_content();
        if ($this->add_css_content == TRUE) {
            $ret .= $class::css_content();
        }
        $ret .= '
</style>';
        if ($this->add_javascript_content == TRUE) {
            $ret .= $class::javascript_content();
        }
        $ret .= '
</head>
<body>
        ';
        
        return $ret;
    }
    
    private function footer()
    {
        $ret ='';
        $ret .= '
<div class="container" id="" style="width:96%;">
        ';
        $ret .= '
            You are logged in as <strong>User</strong>. <a href="">[Logout]</a><br />
            Current version: 0.0.1-dev<br />
            <a href="http://key/scancoord/testing/SiteMap.php">Site Map</a><br />
            <br />
       ';
        $ret .= '
</div></body></html>
        ';
        return $ret;
    }
    
    /*
    private function css_content()
    {
        return '';
    }
    */
    
}
