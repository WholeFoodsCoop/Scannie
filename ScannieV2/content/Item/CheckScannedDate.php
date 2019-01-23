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
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../common/sqlconnect/SQLManager.php');
}
class CheckScannedDate extends PageLayoutA 
{

    protected $title = "";
    protected $description = "[] ";
    protected $ui = TRUE;

    public function preprocess()
    {
        $this->displayFunction = $this->pageContent();
        //$this->__routes[] = 'get<test>';

        return parent::preprocess();
    }

    public function getTestView()
    {
        return <<<HTML
well, hello there, world!
HTML;
    }

    public function cssContent()
    {
return <<<HTML
HTML;
    }

    public function pageContent()
    {
        $ret = '';
        include(__DIR__.'/../config.php');
        $dbc = scanLib::getConObj();

        $qnames = array();
        $prep = $dbc->prepare("SELECT *
            FROM woodshed_no_replicate.batchCheckQueueNames");
        $result = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($result)) {
            $qnames[$row['queueID']] = $row['queueName'];
        }
            if ($er = $dbc->error()) {
                echo "<div class='alert alert-danger'>$er</div>";
            }

        $upc = FormLib::get('upc');
        $t = "<table class='table'><thead><th>Session</th><th>Timestamp</th><th>Queue</th></thead><tbody>";
        if($upc = scanLib::upcParse($upc)) {
            $args = array($upc);
            $prep = $dbc->prepare("
                SELECT session, timestamp, inQueue, 
                    p.brand, p.description
                FROM woodshed_no_replicate.batchCheckQueues AS b
                    LEFT JOIN products AS p ON p.upc=b.upc
                WHERE b.upc = ?
                    GROUP BY timestamp 
                ");
            $result = $dbc->execute($prep,$args);
            while ($row = $dbc->fetchRow($result)) {
                $prodinfo = "<div align='center'><b>{$row['brand']}</b></div>
                    <div align='center'>{$row['description']}</div>";
                $inQ = $row['inQueue'];
                if ($inQ == 98) {
                    $t .= "<tr class='alert alert-warning'>";
                } else {
                    $t .= "<tr>";
                }
                $t .= "<td>".$row['session']."</td>";
                $t .= "<td>".$row['timestamp']."</td>";
                $t .= "<td>".$qnames[$inQ]."</td>";
                $t .= "</tr>";
            }
            if ($er = $dbc->error()) {
                echo "<div class='alert alert-danger'>$er</div>";
            }
            $table .=  "</tbody></table>";

        }
        $table .=  "</tbody></table>";
        $ret .= $t;

        return <<<HTML
<div class="container-fluid">
    <div>&nbsp;</div>
    {$this->form_content()}
    $prodinfo
    $ret
</div>
HTML;
    }

    private function form_content()
    {
        $upc = FormLib::get('upc');
        return <<<HTML
<form class =""  method="get" >
    <div class="row">
        <div class="col-md-2">
            <div class="form-group">
                <input type="text" class="form-control" name="upc" value="$upc" placeholder="Enter a PLU" autofocus>
            </div>
        </div>
        <div class="col-md-1">
            <div class="form-group">
                <input type="submit" class="btn btn-default" value="Submit">
            </div>
        </div>
        <div class="col-md-9">
            <label>Find all Batch Check Queues for a PLU.</label>
        </div>
    </div>
</form>
HTML;
    }

    public function javascriptContent()
    {
        return <<<HTML
HTML;
    }

}
WebDispatch::conditionalExec();
