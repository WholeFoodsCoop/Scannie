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

class generic_dScan_page extends ScancoordDispatch
{
    
    protected $title = "none";
    protected $description = "[none] blank.";
    protected $ui = TRUE;
    protected $readme = "This is a generic page for running and drawing a query to table.
        By default, every column returned by query will be drawn in table, in the order 
        selected.";
    
    public function body_content()
    {           
        $ret = '';
        include('../config.php');
        include('../common/lib/PriceRounder.php');
        $rounder = new PriceRounder();
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        
        if($_GET['upc']) {
            $_GET['upc'] = trim($_GET['upc']);
            $upc = str_pad($_GET['upc'], 13, 0, STR_PAD_LEFT);
        }
         
        /*
        $query = $dbc->prepare("");
        $result = $dbc->execute($query);
        while ($row = $dbc->fetch_row($result)) {
        }
        if ($dbc->error()) $ret .=  $dbc->error();
        */
         
        $query = $dbc->prepare("select upc, brand, description from products limit 25");
        $result = $dbc->execute($query);
        $data = array();
        $headers = array();
        $i = 0;
        while ($row = $dbc->fetch_row($result)) {
            foreach ($row as $k => $v) {
                if(!is_numeric($k)) {
                    $data[$i][$k] =  $v;
                    $headers[$k] = $k;
                }
            }
            $i++;
        }
        
        /*  Add a column
        $i = 0;
        foreach ($data as $k => $array) { 
            $newColumnName = 'column_name';
            $data[$i][$newColumnName] = 'data_to_put_into_column';
            $headers[$newColumnName] = $newColumnName;
            $i++;
        }
        */
        
        /*  Add a flags
        $i = 0;
        $flags = array();
        foreach ($data as $k => $array) { 
            if ('condition') {
                $flags['flag_type'][] = $i;
            }
            $i++;
        }
        */
        
        $ret .=  '<div class="panel panel-default"><table class="table table-striped">';
        $ret .=  '<thead>';
        foreach ($headers as $v) {
            $ret .=  '<th>' . $v . '</th>';
        }
        $ret .=  '</thead>';
        $prevKey = '1';
        $ret .=  '<tr>';
        foreach ($data as $k => $array) { 
            foreach ($array as $kb => $v) {
                $ret .=  '<td> ' . $v . '</td>'; 
            }
            if($prevKey != $k) {
                /*  highlight Flagged rows
                if (in_array(($k+1),$flags['flag_name'])) {
                    $ret .=  '</tr><tr class="" style="background-color:tomato;color:white">';
                } else {
                    $ret .=  '</tr><tr>';
                }
                */
                /*  rows w/ no flags */
                $ret .=  '</tr><tr>';
            } 
            $prevKey = $k;
        }
        $ret .=  '</table></div>';
        
        /*
        $upcLink = "<a href='http://key/git/fannie/item/ItemEditorPage.php?searchupc=" 
                . $upc . "&ntype=UPC&searchBtn=' target='_blank'>{$upc}</a>";
        */
        if ($dbc->error()) $ret .=  $dbc->error();
        
        return $ret;
    }
    
    private function form_content()
    {
        return '
            <div class="text-center container">
                <form class ="form-inline"  method="get" > 
                    <br>
                    <div class="form-group">    
                        <input type="text" class="form-control" name="upc" placeholder="enter plu to track" autofocus>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-default" value="submit">
                    </div>
                </form>
            </div>
        ';
    }
    
}

ScancoordDispatch::conditionalExec();

 
