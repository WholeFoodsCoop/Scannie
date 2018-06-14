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

include(__DIR__.'/../config.php');
if (!class_exists('ScancoordDispatch')) {
    include(__DIR__.'/../common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../common/sqlconnect/SQLManager.php');
}

class zeroPriceCheck extends ScancoordDispatch
{

    protected $title = "Bad Price Scan";
    protected $description = "[Bad Price Scan] Scan for in-use items with bad prices.";
    protected $ui = TRUE;

    public function body_content()
    {
        $ret = '';
        include(__DIR__.'/../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        include(__DIR__.'/../common/lib/PriceRounder.php');
        $rounder = new PriceRounder();
        if($_GET['upc']) {
            $_GET['upc'] = trim($_GET['upc']);
            $upc = str_pad($_GET['upc'], 13, 0, STR_PAD_LEFT);
        }

        $item = array ( array() );
        $query = $dbc->prepare("SELECT
                p.upc,
                p.normal_price,
                p.brand,
                p.description,
                p.store_id,
                p.last_sold,
                p.cost,
                m.super_name
            FROM products AS p
                RIGHT JOIN MasterSuperDepts AS m ON p.department = m.dept_ID
            WHERE inUse=1
                AND m.superID != 0
                AND m.superID != 3
                AND m.superID != 6
                AND m.superID != 7
                AND (normal_price = 0 OR normal_price > 99.99)
                AND last_sold is not NULL
                AND upc <> 0001440035017
                AND upc <> 0085068400634
                AND upc <> 0000000000114
                AND upc <> 0000000001092
                AND upc <> 0000000001108
                AND upc <> 0065801012014
                AND wicable = 0
                    OR (
                        m.superID != 0
                        AND m.superID != 3
                        AND m.superID != 6
                        AND m.superID != 7
                        AND normal_price < cost
                        AND inUse = 1
                        AND normal_price > 0
                        AND upc <> 0000000003361
                        AND upc <> 0000000001138
                        AND upc <> 0000000000114
                        AND p.price_rule_id = 0
                        AND wicable = 0
                    )
            GROUP BY upc
        ");
        $result = $dbc->execute($query);
        $count = $dbc->numRows($result);
        if ($count == 0) {
            echo '<div class="alert alert-success" align="center">
                No badly priced items discovered.</div>';
        } else {
            echo  '<div class="alert alert-danger" align="center">
                '.$count.' products with bad prices discovered.<br></div>';

            $ret .=  '<table class="table table-condensed table-striped">';
            $headers = array('upc','description','brand','super Dept.','price','cost','store','&Delta;');
            $ret .= '<thead>';
            foreach ($headers as $header) {
                $ret .= '<th>'.$header.'</th>';
            }
            $ret .= '</thead>';

            while ($row = $dbc->fetchRow($result)) {
                $ret .=  '<tr><td><a href="http://'.$FANNIEROOT_DIR.'/item/ItemEditorPage.php?searchupc=' . $row['upc'] . '" target="_blank">' . $row['upc'] . '</a>';
                $ret .=  '<td>' . $row['description'];
                $ret .=  '<td>' . $row['brand'];
                $ret .=  '<td>' . $row['super_name'];
                $ret .=  '<td style="color: red; ">' . $row['normal_price'];
                $ret .=  '<td style="color: grey; ">' . $row['cost'];
                $ret .=  '<td>'.$row['store_id'] . '</b>';
                $ret .=  '<td> <a href="http://'.$SCANROOT_DIR.'/item/TrackChangeNew.php?upc=' . $row['upc'] . '" target="_blank">See Change</a>';
            }
            echo scanLib::getDbcError($dbc);
            $ret .=  '</table>';
        }

        return $ret;
    }

    public function help_content()
    {
        return
        '
            <p>Scans products to locate prices that may be bad.</p>
            <label>Scans for products that meet the following criteria</label>
            <ul>
                <li>price equlas zero</li>
                <li>price > 99.99</li>
                <li>price < cost</li>
            </ul>
            <label>Excludes</label>
            <ul>
                <li>Produce Super Department.</li>
                <li>Deli Super Department.</li>
                <li>Misc. Super Department.</li>
                <li>Brand Super Department.</li>
                <li>Wicable Products</li>
                <li>Products with a Price Rule ID (variably priced items)</li>
                <li>Products that are NOT in use.</li>
            </ul>
        ';

    }

}
ScancoordDispatch::conditionalExec();
