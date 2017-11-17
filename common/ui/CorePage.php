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

    protected $onload_commands = array();
    protected $scripts = array();
    protected $start_timestamp = NULL;
    protected $must_authenticate = false;
    protected $current_user = false;
    protected $auth_classes = array();

    public function help_content() {
        return $this->description;
    }

    function __construct() {
        $this->start_timestamp = microtime(true);
        $auth_default = NULL;
        $css_content = FALSE;
    }

    private function runPage($class)
    {
        if(!class_exists('scanLib')) {
            include(dirname(dirname(__FILE__)).'/lib/scanLib.php');
        }
        $obj = new $class();
        $obj->draw_page($class);
    }

    private function draw_page($class)
    {
        $this->preflight();
        print $this->header($class);
        if (!class_exists('MenuClass')) {
            include(dirname(__FILE__).'/MenuClass.php');
        }
        include(dirname(__FILE__).'/../../config.php');
        print "<br />";

        if ($this->ui === TRUE) {
            print '
                <div class="container" style="width:95%;">
                    <div style="font-size:28px;margin-bottom:5px;"  class="primaryColor hidden-sm hidden-xs">
                        <img src="/scancoord/common/src/img/scanner.png" style="width:75px;heigh:75px;float:left">
                        <a href="/scancoord">Scannie</a> | an extension of
                        <a href="http://'.$SCANHOST.'/IS4C/fannie">CORE-POS</a>
                        <!-- this span is for detecting bootstrap screensize -->
                            <span class="device-xs visible-xs"></span>
                            <div style="font-size:20px;" class="secondaryColor" data-toggle="collapse" data-target=".navbar-default" onclick="smartToggle();">
                                IT COREY maintenance &amp; reporting <span style="color: grey;">|</span>  <span style="color: #8c7b70;">'.$this->title.'</span>
                            </div>
                        <!-- for testing only
                            $prev = '.$_SESSION["prevUrl"].'<br />
                            $cur = '.$_SESSION["curUrl"].'
                        -->
                    </div>
                </div>
            ';
            print '
                <div class="container" style="width:95%;">
                    <div style="font-size:14px;margin-bottom:5px;" class="primaryColor hidden-lg hidden-md">
                        <a href="/scancoord">Scannie</a> | an extension of
                        <a href="http://'.$SCANHOST.'/IS4C/fannie">CORE-POS</a>
                        <!-- this span is for detecting bootstrap screensize -->
                            <span class="device-xs visible-xs"></span>
                            <div style="font-size:14px;" class="secondaryColor" data-toggle="collapse" data-target=".navbar-default" onclick="smartToggle();">
                                ITCM&amp;R <span style="color: grey;">|</span>  <span style="color: #8c7b70;">'.$this->title.'</span>
                            </div>
                        <!-- for testing only
                            $prev = '.$_SESSION["prevUrl"].'<br />
                            $cur = '.$_SESSION["curUrl"].'
                        -->
                    </div>
                </div>
            ';
            //print '<div class="container" id="border" style="width:95%;padding:5px">';
            print '<div class="container" id="" style="min-height: 850px;width:95%;border:1px solid #f5ebd0; padding:5px; background-color: white; border-radius: 5px;">';
            print menu::nav_menu();
        }
        print $this->body_content();
        print $this->get_help_content();
        print $this->quick_lookups();
        print '</div></div></div>';
        print $this->footer();
        print $this->writeJS();
        
        $this->recordPath();
    }

    private function recordPath()
    {
        if ($_SESSION['prevUrl'] = $_SESSION['curUrl']) {}
        $noRecord = array(
            '/scancoord/item/PercentCalc.php?iframe=true',
            '/scancoord/item/MarginCalcNew.php?iframe=true'
        );
        if (!in_array($_SERVER[REQUEST_URI] ,$noRecord)) {
            $_SESSION['curUrl'] = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        }
    }

    private function get_help_content()
    {
        return '
            <div id="help" class="modal">
                <div class="vertical-alignment-helper draggable">
                    <div class="modal-dialog vertical-align-center" role="document">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h3 class="modal-title" style="color: #8c7b70">'.$this->title.'</h3>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                style="position: absolute; top:20; right: 20">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            '.$this->help_content().'
                          </div>
                        </div>
                    </div>
                </div>
            </div>
        ';
    }

    private function quick_lookups()
    {
        return '
            <div id="quick_lookups" class="modal" >
                <div class="vertical-alignment-helper draggable">
                    <div class="modal-dialog vertical-align-center" role="document">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h3 class="modal-title" style="color: #8c7b70">Quick Lookups</h3>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                style="position: absolute; top:20; right: 20">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            '.$this->quick_lookups_content().'
                          </div>
                        </div>
                    </div>
                </div>
            </div>
        ';
    }

    private function quick_lookups_content()
    {
        include(dirname(__FILE__).'/../../config.php');
        $ret = '';
        $ret .= '

        ';
        $subBtn = '&nbsp;<button type="submit" class="btn btn-info btn-xs" href=""><span class="go-icon">&nbsp;</span></a>';
        $TrackChange = 'http://'.$SCANHOST.'/scancoord/item/TrackChangeNew.php';
        $ItemEditor = 'http://'.$SCANHOST.'/fannie/item/ItemEditorPage.php';
        $batch = 'http://'.$SCANHOST.'/IS4C/fannie/batches/newbatch/EditBatchPage.php';
        $LastSold = 'http://'.$SCANHOST.'/scancoord/item/last_sold_check.php';
        $ItemBatchHistory = 'http://'.$SCANHOST.'/IS4C/fannie/reports/ItemBatches/ItemBatchesReport.php';
        $SalesBatchPercent = 'http://'.$SCANHOST.'scancoord/item/Batches/CheckBatchPercent.php';
        
        $ret .= '<div align="center">';

        $ret .= '
            <form class="form-inline" method="get"   action="'.$TrackChange.'" target="_blank">
                <div class="input-group">
                    <span class="input-group-addon alert-info" style="width: 100px; "><a href="'.$TrackChange.'">Track Change</a></span>
                    <input type="text" class="form-control" id="trackchange" name="upc" placeholder="enter upc" style="width: 200px; " autofocus>
                    '.$subBtn.'
                </div>
            </form>
        ';

        $ret .= '
            <form class="form-inline" method="get"   action="'.$LastSold.'" target="_blank">
                <div class="input-group">
                    <span class="input-group-addon alert-info" style="width: 100px; "><a href="'.$LastSold.'">Last Sold</a></span>
                    <input type="text" class="form-control" id="lastsold" name="upc" placeholder="enter upc" style="width: 200px; ">
                    '.$subBtn.'
                </div>
                <input type="hidden" name="id" value="1">
            </form>
        ';

        $ret .= '
            <form class="form-inline" method="get"   action="'.$ItemEditor.'" target="_blank">
                <div class="input-group">
                    <span class="input-group-addon alert-warning" style="width: 100px; "><a href="'.$ItemEditor.'">Item Editor</a></span>
                    <input type="text" class="form-control" id="itemeditor" name="searchupc" placeholder="enter upc" style="width: 200px; ">
                    '.$subBtn.'
                </div>
                <input type="hidden" name="ntype" value="UPC">
                <input type="hidden" name="searchBtn" value="">
            </form>
        ';

        $ret .= '
            <form class="form-inline" method="get"   action="'.$batch.'" target="_blank">
                <div class="input-group">
                    <span class="input-group-addon alert-warning" style="width: 100px; "><a href="'.$batch.'">Sales Batches</a></span>
                    <input type="text" class="form-control" id="itemeditor" name="id" placeholder="enter batch ID" style="width: 200px; ">
                    '.$subBtn.'
                </div>
                <input type="hidden" name="ntype" value="UPC">
                <input type="hidden" name="searchBtn" value="">
            </form>
        ';

        $ret .= '
            <form class="form-inline" method="get"   action="'.$ItemBatchHistory.'" target="_blank">
                <div class="input-group">
                    <span class="input-group-addon alert-info" style="width: 100px; "><a href="'.$ItemBatchHistory.'">Item Batch H</a></span>
                    <input type="text" class="form-control" id="itembatchhistory" name="upc" placeholder="enter upc" style="width: 200px; ">
                    <input type="hidden" name="includePC" value="on">
                    '.$subBtn.'
                </div>
                <input type="hidden" name="id" value="1">
            </form>
        ';


        $ret .= '
            <form class="form-inline" method="get"   action="'.$SalesBatchPercent.'" target="_blank">
                <div class="input-group">
                    <span class="input-group-addon alert-info" style="width: 100px; "><a href="'.$SalesBatchPercent.'">Sales Batch %</a></span>
                    <input type="text" class="form-control" id="salesbatchpercent" name="batchID" placeholder="enter batch id" style="width: 200px; ">
                    '.$subBtn.'
                </div>
            </form>
        ';

        $ret .= '</div>';

        return $ret;
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

    private function preflight()
    {
        /* oldkey php -v too old to use session_status() ? 
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        */
        include(dirname(__FILE__).'/../../config.php');

        if ($this->must_authenticate == TRUE) {
            $userPrivilege = $_SESSION['user_type'];
            if ($userPrivilege != 1) {
                header("Location: http://{$SCANHOST}/scancoord/admin/login.php");

            }
        }
    }

    private function jsRedirect()
    {
        $prevUrl = $_SESSION['prevUrl'];        
        $onloadCommand = 'window.location.replace( "'.$prevUrl.'" );';
        
        return $this->addOnloadCommand($onloadCommand);
    }

    private function header($class)
    {
        include(dirname(__FILE__).'/../../config.php');
        $ret = '';
        $ret .= $this->preprocess();
        $ret .= '
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/scancoord/common/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/scancoord/common/css/Scannie_css.css?load=true">
    <link rel="stylesheet" href="/scancoord/common/javascript/tablesorter/css/theme.blue.css">
    <script src="/scancoord/common/bootstrap/jquery.min.js"></script>
    <script src="/scancoord/common/bootstrap/bootstrap.min.js"></script>
    <!-- <script src="/scancoord/common/javascript/jquery-ui-1.12.1/jquery-ui.min.js"></script>
        <script src="/scancoord/common/javascript/jquery-ui-1.12.1/external/jquery/jquery.js"></script>
        <link rel="stylesheet" href="/scancoord/common/javascript/jquery-ui-1.12.1/jquery-ui.min.css"> -->
    <link rel="stylesheet" href="/scancoord/common/javascript/jquery-ui/jquery-ui.css">
    <!--<script src="/scancoord/common/javascript/jquery-ui/jquery-1.12.4.js"></script>-->
    <script src="/scancoord/common/javascript/jquery-ui/jquery-ui.js"></script> 

    <title>' . $this->title . '</title>
<style>';
        $ret .= $this->cssContent();
        $ret .= '
</style>';
        $this->addScript("http://{$SCANHOST}/scancoord/common/javascript/scannie.js");
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
        include(dirname(__FILE__).'/../../config.php');
        $ret ='';
        $user = $_SESSION['user_name'];
        if (empty($user)) {
            $user = 'Generic User';
            $logVerb = 'Login';
            $link = "<a href='http://{$SCANHOST}/scancoord/admin/login.php'>[{$logVerb}]</a><br />";
        } else {
            $logVerb = 'Logout';
            $link = "<a href='http://{$SCANHOST}/scancoord/admin/logout.php'>[{$logVerb}]</a><br />";
        }
        $ret .= '
            <div class="container" id="" style="width:96%;">
        ';
        $ret .= '
            You are logged in as <strong>'.$user.'</strong>.
            '.$link.'
            Current version: 0.0.1-dev<br />
            <a href="http://'.$SCANHOST.'/scancoord/testing/SiteMap.php">Site Map</a><br />
            <br />
       ';
        $ret .= '
            </div></body></html>
        ';
        return $ret;
    }
    
    protected function add_script($file_url,$type="text/javascript")
    {
        $this->addScript($file_url, $type);
    }
    
    protected function addScript($file_url, $type='text/javascript')
    {
        $this->scripts[$file_url] = $type;
    }
    
    protected function add_onload_command($str)
    {
        $this->onload_commands[] = $str;    
    }

    protected function addOnloadCommand($str)
    {
        $this->add_onload_command($str);
    }
    
    protected function writeJS()
    {
        foreach($this->scripts as $s_url => $s_type) {
            printf('<script type="%s" src="%s"></script>',
                $s_type, $s_url);
            echo "\n";
        }

        $js_content = $this->javascriptContent();
        if (!empty($js_content) || !empty($this->onload_commands)) {
            echo '<script type="text/javascript">';
            echo $js_content;
            echo "\n\$(document).ready(function(){\n";
            echo array_reduce($this->onload_commands, function($carry, $oc) { return $carry . $oc . "\n"; }, '');
            echo "});\n";
            echo '</script>';
        }
    }
    
    protected function javascript_content()
    {
    }

    protected function javascriptContent()
    {
        return $this->javascript_content();
    }
    
    /**
      Define any CSS needed
      @return A CSS string
    */
    protected function css_content()
    {
    }

    protected function cssContent()
    {
        return $this->css_content();
    }
    
    protected function preprocess()
    {
    }

}
