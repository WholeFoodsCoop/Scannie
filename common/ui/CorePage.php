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
    
    public function help_content() { 
        return 'No help content has been created for this page.'; 
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
        print "<br />";
        
        if ($this->ui === TRUE) {
            print '
                <div class="container" style="width:95%; ">
                    <div style="font-size:28px;margin-bottom:5px;" class="primaryColor">
                        <img src="/scancoord/common/src/img/scanner.png" style="width:75px;heigh:75px;float:left">
                        <a href="/scancoord">Scannie</a> | an extension of 
                        <a href="http://192.168.1.2/git/fannie">CORE-POS</a>
                        <!-- this span is for detecting bootstrap screensize -->
                            <span class="device-xs visible-xs"></span>
                            <div style="font-size:20px;" class="secondaryColor" data-toggle="collapse" data-target=".navbar-default" onclick="smartToggle();">
                                IT COREY maintenance &amp; reporting <span style="color: grey;">|</span>  <span style="color: #8c7b70;">'.$this->title.'</span>
                            </div>
                    </div>
                </div>
            ';
            //print '<div class="container" id="border" style="width:95%;padding:5px">';
            print '<div class="container" id="" style="min-height: 850px;width:95%;border:1px solid #f5ebd0; padding:5px; background-color: white">';
            print menu::nav_menu();    
        }
        /*
        print '<a class="menuNav" style="width:160px;" href=""
                data-toggle="modal" data-target="#quick_lookups">Quick Lookups<span class=""></span></a>';
        */
        print $this->body_content();   
        print $this->get_help_content();
        print $this->quick_lookups();
        print '</div></div></div>';
        print $this->footer();
        $this->recordPath();
    }
    
    private function recordPath()
    {
        if ($_SESSION['prevUrl'] = $_SESSION['curUrl']) {}  
        $_SESSION['curUrl'] = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }
    
    private function get_help_content()
    {
        return '
            <div id="help" class="modal fade">
                <div class="modal-dialog" role="document">
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
        ';
    }
    
    private function quick_lookups()
    {
        return '
            <div id="quick_lookups" class="modal fade" >
                <div class="modal-dialog" role="document">
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
        ';
    }
    
    private function quick_lookups_content()
    {
        
        $ret = '';
        $ret .= '
            
        ';
        $subBtn = '&nbsp;<button type="submit" class="btn btn-info btn-xs" href=""><span class="go-icon">&nbsp;</span></a>';
        $TrackChange = 'http://192.168.1.2/scancoord/item/TrackChangeNew.php';
        $ItemEditor = 'http://192.168.1.2/git/fannie/item/ItemEditorPage.php';
        $batch = 'http://192.168.1.2/git/fannie/batches/newbatch/EditBatchPage.php';
        $LastSold = 'http://192.168.1.2/scancoord/item/last_sold_check.php';
        $ItemBatchHistory = 'http://192.168.1.2/scancoord/item/Batches/prodBatchHistory.php';
        $SalesBatchPercent = 'http://192.168.1.2/scancoord/item/Batches/CheckBatchPercent.php';
        
        
        //$ret .= '<h4 align="center">Quick Lookups</h4>';
        $ret .= '<div align="center">';
        
        $ret .= '
            <form class="form-inline" method="get"   action="'.$TrackChange.'">
                <div class="input-group">
                    <span class="input-group-addon alert-info" style="width: 100px; ">Track Change</span>
                    <input type="text" class="form-control" id="trackchange" name="upc" placeholder="enter upc" style="width: 200px; " autofocus>
                    '.$subBtn.'
                </div>
            </form>     
        ';
        
        $ret .= '
            <form class="form-inline" method="get"   action="'.$LastSold.'">
                <div class="input-group">
                    <span class="input-group-addon alert-info" style="width: 100px; ">Last Sold</span>
                    <input type="text" class="form-control" id="lastsold" name="upc" placeholder="enter upc" style="width: 200px; ">
                    '.$subBtn.'
                </div>
                <input type="hidden" name="id" value="1">
            </form>
        ';
        
        $ret .= '
            <form class="form-inline" method="get"   action="'.$ItemEditor.'">
                <div class="input-group">
                    <span class="input-group-addon alert-warning" style="width: 100px; ">Item Editor</span>
                    <input type="text" class="form-control" id="itemeditor" name="searchupc" placeholder="enter upc" style="width: 200px; ">
                    '.$subBtn.'
                </div>
                <input type="hidden" name="ntype" value="UPC">
                <input type="hidden" name="searchBtn" value="">
            </form>     
        ';
        
        $ret .= '
            <form class="form-inline" method="get"   action="'.$batch.'">
                <div class="input-group">
                    <span class="input-group-addon alert-warning" style="width: 100px; ">Sales Batches</span>
                    <input type="text" class="form-control" id="itemeditor" name="id" placeholder="enter batch ID" style="width: 200px; ">
                    '.$subBtn.'
                </div>
                <input type="hidden" name="ntype" value="UPC">
                <input type="hidden" name="searchBtn" value="">
            </form>     
        ';
        
        $ret .= '
            <form class="form-inline" method="get"   action="'.$ItemBatchHistory.'">
                <div class="input-group">
                    <span class="input-group-addon alert-info" style="width: 100px; ">Item Batch H</span>
                    <input type="text" class="form-control" id="itembatchhistory" name="upc" placeholder="enter upc" style="width: 200px; ">
                    '.$subBtn.'
                </div>
                <input type="hidden" name="id" value="1">
            </form>     
        ';
        
        
        $ret .= '
            <form class="form-inline" method="get"   action="'.$SalesBatchPercent.'">
                <div class="input-group">
                    <span class="input-group-addon alert-info" style="width: 100px; ">Sales Batch %</span>
                    <input type="text" class="form-control" id="salesbatchpercent" name="batchID" placeholder="enter batch id" style="width: 200px; ">
                    '.$subBtn.'
                </div>
            </form>     
        '; 
        
        $ret .= '</div>';
        
        return $ret;
    }
    
    public function keypress_js()
    {
        ob_start();
?>
<script type="text/javascript">
function KeyDown(evt){
    switch (evt.keyCode) {
        case 39:  /* Right Arrow */
            break;

        case 37:  /* Left Arrow */
            break;

        case 40:  /* Down Arrow */
            break;

        case 38:  /* Up Arrow */
            break;
            
        case 192:  /* Tilde */
            $('#quick_lookups').modal('toggle');
            //$('#quick_lookups').focus();
            //$('#trackchange').focus();
            break;
    }
}
window.addEventListener('keydown', KeyDown);
</script>
<?php
        return ob_get_clean();
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
    
    private function preflight($class) 
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($this->must_authenticate == TRUE) {
            $userPrivilege = $_SESSION['user_type'];
            if ($userPrivilege != 1) {
                header('Location: http://192.168.1.2/scancoord/admin/login.php');
            }
        }
    }
    
    private function header($class)
    {   
        $ret = '';
        if ($this->use_preprocess == TRUE) {
            $this->preprocess();
        }
        $ret .= '
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/scancoord/common/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/scancoord/common/css/Scannie_css.css?foo=bar">
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
        $ret .= $this->keypress_js();
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
        $user = $_SESSION['user_name'];
        if (empty($user)) {
            $user = 'Generic User';
            $logVerb = 'Login';
            $link = '<a href="http://192.168.1.2/scancoord/admin/login.php">['.$logVerb.']</a><br />';
        } else {
            $logVerb = 'Logout';
            $link = '<a href="http://192.168.1.2/scancoord/admin/logout.php">['.$logVerb.']</a><br />';
        }
        $ret .= '
<div class="container" id="" style="width:96%;">
        ';
        $ret .= '
            You are logged in as <strong>'.$user.'</strong>. 
            '.$link.'
            Current version: 0.0.1-dev<br />
            <a href="http://192.168.1.2/scancoord/testing/SiteMap.php">Site Map</a><br />
            <br />
       ';
        $ret .= '
</div></body></html>
        ';
        return $ret;
    }
    
}
