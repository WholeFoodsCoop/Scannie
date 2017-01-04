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

class BulkUNFICostFix extends ScancoordDispatch
{
    
    protected $title = "Bulk UNFI Cost Fix";
    protected $description = "[Bulk UNFI Cost Fix] Returns the correct cost for 
        unique UNFI products that need to be updated after the upload every 
        price change period.";
    protected $ui = TRUE;
    
    public function body_content()
    {           
        $ret = '';
        include('../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        
        $data = array(
            '0000000000351'=>10,
            '0000000000352'=>10,
            '0000000000353'=>10,
            '0000000000839'=>10,
            '0021114000000'=>10,
            '0021119000000'=>10,
            '0000000001502'=>8.64,
            '0000000001501'=>8.8,
            '0000000001503'=>8.8,
            '0000000000441'=>10.16,
            '0000000000412'=>7.69
        );
        $upcs = array();
        foreach ($data as $k => $v) $upcs[] = $k;
        
        $args = array();
        $in = array();
        list($in,$args) = $dbc->safeInClause($upcs);
        $queryString = 'SELECT upc,cost FROM products WHERE upc IN ('.$in.')';
        $prep = $dbc->prepare($queryString);
        $res = $dbc->execute($prep,$args);
        $costs = array();
        while ($row = $dbc->fetch_row($res)) {
            $costs[$row['upc']] = $row['cost'];
        }
        
        $ret .= '<div class="container">';
        $ret .= '<p style="width: 300px">'.$this->description.'</p>';
        $ret .= '<div style="border: 1px solid lightgrey; width: 300px;">
            <table class="table table-condensed table-striped small" style="width: 100%">';
        $ret .= '<thead><th>upc</th><th>cost</th><th>new cost</th></thead>';
        foreach ($costs as $k => $v) {
            $mod = $data[$k];
            if ($k == '0000000001501') $v = $v / 5;
            $modCost = ($v / $mod);
            $ret .= '<tr>';
            $ret .= '<td>'.$k.'</td><td>'.$v.'</td><td>'.sprintf('%0.2f',$modCost).'</td></tr>';
        }
        $ret .= '</table></div></div>';
        
        return $ret;
    }
    
    private function form_content($dbc)
    {
        
        $vendors = array();
        $prep = $dbc->prepare("SELECT vendorID, vendorName FROM vendors");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetch_row($res)) {
            $vendors[$row['vendorID']] = $row['vendorName'];
        }
        
        $ret = '';
        $ret .= '
            <div class="container">
                <form class ="form-inline"  method="get" > 
                    <br>
                    <div class="form-group">    
                        <select name="vendorID" class="form-control">
        ';
        
        foreach ($vendors as $id => $name) {
            $ret .= '<option value="'.$id.'">'.$name.'</option>';
        }
        
        $ret .= '
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-default btn-sm" value="submit">
                    </div>
                </form>
            </div>
        ';
        
        return $ret;
    }
    
}

ScancoordDispatch::conditionalExec();

 
