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
include(__DIR__.'/../config.php');
if (!class_exists('ScancoordDispatch')) {
    include(__DIR__.'/../common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../common/sqlconnect/SQLManager.php');
}
class ipod extends ScancoordDispatch
{
    
    public $ui = false;

    public function body_content()
    {
        include(__DIR__.'/../config.php');
        $ret = '';
        
        $url = "http://'.$SCANROOT_DIR.'/item/AuditScanner.php";
        if ($src = $_GET['changesrc']) {
            $url = $src;
        }

        $ret .= '<div class="ipod-container">';
        $ret .= '<div class="ipod-inner">
            <iframe src="'.$url.'"
                scrolling="no"></iframe>
            </div>';
        $ret .= '<img src="../common/src/img/ipod.png" class="ipod">'; 
        $ret .= '<div class="homeBtn"></div>';
        $ret .= '</div>';
        
        $ret .= '<form id="newurl">
            <input type="text" name="changesrc" id="changesrc"
            class="urlbar" placeholder="enter a new URL" ></form>';
        
        return $ret;
    }
    
    public function css_content()
    {
        return <<<HTML
.ipod {
    height: 600px;
}
.ipod-container {
    width: 290px;
}
.ipod-inner {
    position: absolute;
    top: 77px;
    left: 35px;
    height: 435px;
    width: 245px;
}
iframe {
    zoom: 0.15;
    -moz-transform:scale(0.75);
    -moz-transform-origin: 0 0;
    height: 580px;
    width: 328px;
    overflow-y: hidden;
    overflow-x: hidden;
}
body {
    overflow-y: hidden;
    overflow-x: hidden;
}
.homeBtn {
    border-radius: 100%;
    height: 50px;
    width: 50px;
    position: relative;
    bottom: 75px;
    left: 120px;
}
input {
    width: 100%;
    border: 1px solid orange;
    opacity: 0.9;
    position: absolute;
    bottom: 5px;
    background-color: lightorange;
}
.urlbar {
    
}
HTML;
    }
    
    function javascriptContent()
    {
        return <<<HTML
$(document).ready( function() {
    $('#changesrc').hide();
    homeBtn();
});

function homeBtn() {
    $('.homeBtn').click( function() {
        $('#changesrc').show();
    });
}
HTML;
    }

}
ScancoordDispatch::conditionalExec();
