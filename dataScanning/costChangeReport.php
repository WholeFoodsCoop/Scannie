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

class costChangeReport extends ScancoordDispatch
{

    protected $title = "Cost Change Report";
    protected $description = "[Cost Change Report] Find the resent change in cost
        for all products under a select vendor.";
    protected $ui = TRUE;
    protected $readme = 'Select a vendor to review costs and the previous cost, if
        there was a change, for each product.';

    public function body_content()
    {
        $ret = '';
        include('../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);

        $mo = date('m');
        $da = date('d');
        $ye = date('Y');
        $prevmo = $m - 1;

        $vendorID = $_GET['vendorID'];
        $curDate = date('Y-m-d');
        $date = $_GET['date'];

        $prep = $dbc->prepare("
            SELECT
                p.upc,
                p.brand,
                p.description,
                p.cost
            FROM products AS p
            WHERE p.default_vendor_id = ?
            GROUP BY p.upc
            ;
        ");
        $res = $dbc->execute($prep,$vendorID);
        $cost = array();
        $brand = array();
        $description = array();
        $prevCost = array();
        $prevMod = array();
        while ($row = $dbc->fetch_row($res)) {
            foreach ($row as $k => $v) {
                $cost[$row['upc']] = $row['cost'];
                $description[$row['upc']] = $row['description'];
                $brand[$row['upc']] = $row['brand'];
            }
        }

        foreach ($cost as $upc => $curCost) {
            $args = array($upc,$date);
            $prep = $dbc->prepare("
                SELECT
                    u.cost,
                    u.modified
                FROM prodUpdate AS u
                WHERE upc = ?
                    AND modified < ?
                ORDER BY modified DESC
            ");
            $res = $dbc->execute($prep,$args);
            while ($row = $dbc->fetch_row($res)) {
                $prevCost[$upc] = $row['cost'];
                break;
            }
        }

        $ret .= '<div class="row">';
        //  Column 1
        $ret .= '<div class="col-xs-6" style="width:600px">';
        $ret .= $this->form_content($dbc);
        $ret .= '<i>For large vendors load time can take several minutes (up to 5 minutes).</i><br>';
        $ret .= '<div style="border: 1px solid lightgrey; width: 550px; ">';
        $ret .= '<table class="table-condensed table-striped small">';
        $ret .= '<thead>';
        $headers = array('upc','brand','description','prev_cost','cost',' ',' ');
        foreach ($headers as $header) $ret .= '<th>'.$header.'</th>';
        $ret .= '</thead>';

        $count = array('all'=>0,'green'=>0,'yellow'=>0,'orange'=>0,'red'=>0);
        $change = array();
        $countChange = 0;
        foreach ($cost as $upc => $value) {
            $curChange = $cost[$upc]-$prevCost[$upc];

            if ($curChange != 0 && $prevCost[$upc] != 0 && !is_null($prevCost[$upc])) {
                $change[$upc] = $curChange;
            } else {
                $change[$upc] = 0;
            }

            if ($curChange != 0 && $prevCost[$upc] != 0 && !is_null($prevCost[$upc])) {
                $countChange++;
            }
            if ($curChange == 0 || $prevCost[$upc] == 0) {
                $curChange = '--';
            } elseif ($curChange > 0) {
                $curChangeArrow = '<span style="color:orange; width: 10px">&#11014</span>';
                $curChange = round(abs($curChange),2);
            } else {
                $curChangeArrow = '<span style="color:lightblue; width: 10px">&#11015</span>';
                $curChange = round(abs($curChange),2);
            }

            if ($curChange != '--') {
                $ret .= '<tr>';
                $ret .= '<td>'.$upc.'</td>';
                $ret .= '<td>'.$brand[$upc].'</td>';
                $ret .= '<td>'.$description[$upc].'</td>';
                $ret .= '<td>'.$prevCost[$upc].'</td>';
                $ret .= '<td>'.$cost[$upc].'</td><td>'.$curChangeArrow.'</td>';
                $ret .= '<td>'.$curChange.'</td>';
                $ret .= '</tr>';
            }

            $count['all']++;

        }

        $ret .= '</table>';
        $ret .= '</div></div>';

        //  Second Column
        $ret .= '<div class="col-xs-6">';
        $ret .= '<h3>Results</h3>
            '.$gKey.'
            '.$countChange.'/'.$count['all'].' product costs have changed. <br /><br />
        ';

        $avg = 0;
        $i = 0;
        foreach ($change as $upc => $v) {
            if ($change[$upc] != 0) {
                $avg += $v;
                $i++;
            }
        }
        $avg = $avg / $i;
        $ret .= '<span style="background-color: lightgrey;">&Lambda;</span> :';
        if ($avg > 0) {
            $ret .= '<span style="color:orange;">&#11014</span> ';
        } else {
            $ret .= '<span style="color:green ;">&#11015</span> ';
        }
        $ret .= sprintf('$%0.2f',abs($avg)).' <br />';

        $stdev = 0;
        $stdevArg = array();
        foreach ($change as $upc => $value) {
            if ($change[$upc] != 0) {
                $x = $value - $avg;
                $stdevArg[] = pow($x,2);
            }
        }
        $i = 0;
        foreach ($stdevArg as $value) {
            $stdev += $v;
            $i++;
        }
        $stdev = $stdev / $i;
        $stdev = sqrt($stdev);
        $ret .= '<span style="background-color:  lightgrey;">&sigma;</span> : '.sprintf('%0.2f',$stdev).' <br />';
        $ret .= '<span style="color: red">STDEV is not correct. Logic issue requires resolution.</span><br />';

        $ret .= '</div></div></div> <!-- End Second Column -->';

        return $ret;
    }

    private function form_content($dbc)
    {
        $date = date('Y-m-d');
        if (is_null($_GET['date'])) {
            $date = date('Y-m-d');
        } else {
            $date = $_GET['date'];
        }
        $vendors = array();
        $prep = $dbc->prepare("SELECT vendorID, vendorName FROM vendors ORDER BY vendorName");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetch_row($res)) {
            $vendors[$row['vendorID']] = $row['vendorName'];
        }

        $ret = '';
        $ret .= '
            <div class="">
                <form class ="form-inline"  method="get" >
                    <br>
                    <div class="form-group">
                        <select name="vendorID" class="form-control">
        ';

        foreach ($vendors as $id => $name) {
            $ret .= '<option value="'.$id.'"';
            if ($_GET['vendorID'] == $id) $ret .= ' selected ';
            $ret .='>'.$name.'</option>';
        }

        $ret .= '
                        </select>
                    </div>
                    <br /><br />
                    <div class="input-group">
                        <span class="input-group-addon">Updated on:</span>
                        <input type="text" class="form-control" style="width: 120px;"
                            name="date" value="'.$date.'">&nbsp;&nbsp;
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


