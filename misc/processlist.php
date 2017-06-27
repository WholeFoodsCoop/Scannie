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
include('../config.php');
if (!class_exists('ScancoordDispatch')) {
    include($SCANROOT.'/common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(__FILE__)) . '/common/sqlconnect/SQLManager.php');
}
class processlist extends ScancoordDispatch
{

    protected $title = "Processlist";
    protected $description = "[Processlist] View DB Processlist in real time.";
    protected $ui = TRUE;
    protected $must_authenticate = TRUE;

    public function body_content()
    {
        $ret = '';
        $ret .= $this->js();
        $ret .= '
            <h4>Processlist</h4>
            <div id="mydiv" style="border: 2px solid lightgrey"> </div>
            <div style="height: 5px;">&nbsp;</div>
            <h4>Querylog</h4>
            <div id="logview" style="border: 2px solid #d8e2ed; top-margin: 10px;"> </div>
        ';

        return $ret;
    }

    private function js()
    {
        ob_start();
        ?>
<script type="text/javascript">
$(document).ready( function () {
    setInterval('getProcesslist()', 1000);
    setInterval('logProcesslist()', 1000);
});

function getProcesslist()
{
    $.ajax({
        url: 'getProcesslist.php',
        data: 'liveList=true',
        success: function(response) {
            $('#mydiv').html(response);
        }
    });
}

function logProcesslist()
{
    $.ajax({
        url: 'getProcesslist.php',
        data: 'logList=true',
        success: function(response) {
            var log = $('#logview').html();
            $('#logview').html(log + response);
        }
    });
}
</script>
        <?php
        return ob_get_clean();
    }

}
ScancoordDispatch::conditionalExec();