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
    
    function __construct() {}
    
    private function runPage($class)
    {
        $obj = new $class();
        $obj->draw_page();
    }
    
    private function draw_page()
    {
        if (!class_exists('MenuClass')) {
            include(dirname(__FILE__).'/MenuClass.php');
        }
        print "<br />";
        print $this->header();
        
        if ($this->ui === TRUE) {
            print '
                <div class="container" style="width:95%;">
                    <div style="font-size:28px;margin-bottom:5px;" class="primaryColor">
                        <img src="/scancoord/common/src/img/scanner.png" style="width:75px;heigh:75px;float:left">
                        <a href="/scancoord">Scannie</a>
                        <!-- this span is for detecting bootstrap screensize -->
                            <span class="device-xs visible-xs"></span>
                            <div style="font-size:20px;" class="secondaryColor" data-toggle="collapse" data-target=".navbar-default" onclick="smartToggle();">
                                IT COREY maintenance &amp; reporting
                            </div>
                    </div>
                </div>
            ';
            //print '<div class="container" id="border" style="width:95%;padding:5px">';
            print '<div class="container" id="" style="width:95%;border:1px solid lightgrey;padding:border:1px solid lightgrey;padding:5px">';
            print menu::nav_menu();    
        }
        
        print $this->body_content();        
        print '</div>';
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
    
    private function header()
    {   
        $ret = '';
        $ret .= '
<html>
<head>
    <link rel="stylesheet" href="/scancoord/common/bootstrap/bootstrap.min.css">
    <script src="/scancoord/common/bootstrap/jquery.min.js"></script>
    <script src="/scancoord/common/bootstrap/bootstrap.min.js"></script>
    <title>' . $this->title . '</title>
<style>';
        $ret .= self::css_content();
        $ret .= '
</style>
</head>
<body>
        ';
        
        return $ret;
    }
    
    private function footer()
    {
        $ret ='';
        $ret .= '
       ';
        $ret .= '
</body></html>
        ';
        return $ret;
    }
    
    private function css_content()
    {
        
        return '
#border {
    border: 10px solid transparent;
    padding: 15px;
    border-image-repeat: repeat;
    border-image: url(/scancoord/common/src/img/greyborder.png) 25 round;
}
.btn-default {
    background: lightgrey; /* For browsers that do not support gradients */
    //background: -webkit-linear-gradient(white, lightgrey); /* For Safari 5.1 to 6.0 */
    //background: -o-linear-gradient(white, lightgrey); /* For Opera 11.1 to 12.0 */
    //background: -moz-linear-gradient(white, lightgrey); /* For Firefox 3.6 to 15 */
    background: linear-gradient(white, lightgrey); /* Standard syntax */
}
.btn-danger {
    background: lightgrey; /* For browsers that do not support gradients */
    //background: -webkit-linear-gradient(#d99696, #d64f4f); /* For Safari 5.1 to 6.0 */
    //background: -o-linear-gradient(#d99696, #d64f4f); /* For Opera 11.1 to 12.0 */
    //background: -moz-linear-gradient(#d99696, #d64f4f); /* For Firefox 3.6 to 15 */
    background: linear-gradient(#d99696, #d64f4f); /* Standard syntax */
}
.table-striped > tbody > tr:nth-child(2n+1) > td, .table-striped > tbody > tr:nth-child(2n+1) > th {
   background-color: #fff7f0;
}

/*  Cmder Themed
body, table, th, tr, td {
    background-color: #272822;
    color: #cacaca;
    font-family: consolas;
}
.success {
  color: #74aa04;
}
.danger {
  color: #a70334;
}
.warning {
  color: #b6b649;
  background-color: #272822;
}
.info {
  color: #58c2e5;
}
.purple {
  color: #89569c;
}
.primary {
  color: #1a83a6;
}
.panel {
    background-color: #272822;
    color: #cacaca;
}

*/
        ';
    }
    
}