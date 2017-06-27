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

class MultiStoreDiscrepTable extends ScancoordDispatch
{

    protected $title = "Multi Store Discrepancies Table";
    protected $description = "[] ";
    protected $ui = TRUE;
    protected $add_css_content = TRUE;

    public function body_content()
    {
        $ret = '';
        include('../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);

        if ($_GET['upcs']) {
            $upcs = $_GET['upcs'];
            $plus = array();
            $chunks = explode("\r\n", $upcs);
            foreach ($chunks as $key => $str) {
                $plus[] = $str;
            }
        }

        $fields = array('description','normal_price','cost','tax','foodstamp','wicable','discount','scale',
            'department','brand','local','price_rule_id',);
        $getDiscreps = false;
        $data = array();
        $tempData = array();
        foreach ($fields as $field) {
            $tempData = $this->getDiscrepancies($dbc,$field);
            foreach ($tempData as $k => $upc) {
                $data[] = $upc;
            }
        }

        $data = array_unique($data);
        $ret .= $this->getProdInfo($dbc,$data,$fields);
        $ret .= $this->js();

        return $ret;
    }

    private function getDiscrepancies($dbc, $field)
    {

        $data = array();
        $diffR = $dbc->query("
            SELECT upc, description
            FROM products
            WHERE inUse = 1
            GROUP BY upc
            HAVING MIN({$field}) <> MAX({$field})
            ORDER BY department
        ");
        $count = $dbc->numRows($diffR);
        $msg = "";
        if ($count > 0 ) {
            while ($row = $dbc->fetchRow($diffR)) {
                $data[] = $row['upc'];
            }
        }

        if ($count > 0) {
            return $data;
        } else {
            return false;
        }
    }

    private function getProdInfo($dbc,$data)
    {

        $ret = '';
        $fields = array(
            'super_name',
            'description',
            'price',
            'cost',
            'dept',
            'tax',
            'fs',
            'wic',
            'scale',
            'forceQty'
        );
        list($inClause,$args) = $dbc->safeInClause($data);
        $queryH = 'SELECT p.*, m.super_name FROM prodUpdate AS p LEFT JOIN MasterSuperDepts AS m ON p.dept=m.dept_id WHERE storeID = 1 AND upc IN ('.$inClause.')';
        $queryD = 'SELECT * FROM prodUpdate WHERE storeID = 2 AND upc IN ('.$inClause.')';
        $itemH = array();
        $itemD = array();

        //  Get Hillside Prod. Info
        $prepH = $dbc->prepare($queryH);
        $resH = $dbc->execute($prepH,$args);
        if ($dbc->error()) $ret .= '<div class="alert alert-danger">'.$dbc->error().'</div>';
        while ($row = $dbc->fetchRow($resH)) {
            foreach ($fields as $field) {
                $itemH[$row['upc']][$field] = $row[$field];
            }
        }

        //  Get Denfeld Prod. Info
        $prepD = $dbc->prepare($queryD);
        $resD = $dbc->execute($prepD,$args);
        if ($dbc->error()) $ret .= '<div class="alert alert-danger">'.$dbc->error().'</div>';
        while ($row = $dbc->fetchRow($resD)) {
            foreach ($fields as $field) {
                $itemD[$row['upc']][$field] = $row[$field];
            }
        }

        $headers = array('Hill Desc','Den Desc','Hill Cost','Den Cost');
        $ret .= '<table class="table table-condensed table-bordered small">';
        $ret .= '<thead><th>upc</th><th>Track</th><th>sup_dept</th>';
        foreach ($fields as $field) {
            if ($field != 'super_name') {
                $ret .= '<th><b>[H]</b>'.$field.'</th><th><b>[D]</b>'.$field.'</th>';
            }
        }

        $ret .= '</thead><tbody>';
        foreach ($itemH as $upc => $row) {
            $ret .= '<tr>';
            $ret .= '<td class="okay"><a href="http://192.168.1.2/git/fannie/item/ItemEditorPage.php?searchupc='.$upc.'"
                target="_blank">' . $upc . '</a></td>
                <td class="okay"><a href="http://key/scancoord/item/TrackChangeNew.php?upc=' . $upc . '" target="_blank">
                    <img src="../common/src/img/q.png" style="height: 15px;">&nbsp;<span class="text-tiny">info</span></a></td>';
            $ret .= '<td class="'.$row['super_name'].'">' . $row['super_name'] . '</td>';
            foreach ($fields as $field) {
                if ($field != 'super_name') {
                    $td = '';
                    if ($row[$field] == $itemD[$upc][$field]) {
                        $td = '<td class="okay">';
                    } else {
                        $td = '<td>';
                    }
                    $ret .= $td;
                    $ret .= $row[$field] . '</td>';

                    $ret .= $td;
                    $ret .= $itemD[$upc][$field] . '</td>';
                }

            }
            $ret .= '</tr>';
        }
        $ret .= '</tbody></table>';

        return $ret;

    }

    public function css_content()
    {
        return '
            td.okay {
                background-color: grey;
            }
            td.produce {
                background-color: #71c98a;
            }
            td.grocery, td.gen,
            td.frozen, td.refrigerated {
                background-color: #ffa72b;
            }
            td.deli {
                background-color: #c674cc;
            }
            th {
                font-weight: normal;
            }
            body {
                //background-image: none;
                //background-color: black;
            }
            a {
                color: lightgrey;
            }
            tbody {
                padding: 100px;
            }
            table {
                background: linear-gradient(#ffdba1, #ffffa1);
            }
            td:hover, tr:hover {
                border: 1px solid purple;
            }
            .row:focus {
                background-color: red;
            }
        ';
    }

    public function js()
    {
        ob_start();?>

        <?php
        return ob_get_clean();
    }

    public function help_content()
    {
        return '
            <p>
                The <b>'.$this->title.'</b> checks POS for discrepancies in several fields for
                all products that are in use at either store.
                <lu>
                    <li>Cells shaded out in grey are product fields that do not have an issue.</li>
                    <li>Cells that are highlighted reflect discrepancies that should be checked/corrected.</lii>
                </lu>
            </p>


        ';
    }


}

ScancoordDispatch::conditionalExec();


