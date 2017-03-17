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

include('../../../config.php');
if (!class_exists('ScancoordDispatch')) {
    include($SCANROOT.'/common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/common/sqlconnect/SQLManager.php');
}

class BatchReviewPage extends scancoordDispatch 
{
    
    protected $title = 'Batch Review';
    protected $description = '[Batch Review] Review price change batch data to 
        ensure accuracy.';
    protected $ui = TRUE;
    
    public function body_content() 
    {
        
        include('../../../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', $SCANDB, $SCANUSER, $SCANPASS);
        $curPage = basename($_SERVER['PHP_SELF']);
        
        $id = $_GET['id'];
        $ret = '';
        $ret .= '<div class="container">';
        include('BatchReviewLinks.php');
        $ret .= $this->form_content($id);
        
        $ret .= '<a href="http://key/git/fannie/batches/newbatch/EditBatchPage.php?id=' 
            . $id . '" target="_blank"><span class="text-primary">Open Batch Page</span></a>';
        $nextBatch = $_SERVER['PHP_SELF'] . '?id=' . ($id + 1);
        $prevBatch = $_SERVER['PHP_SELF'] . '?id=' . ($id - 1);
        $ret .= '&nbsp;<a class="btn" href="' . $prevBatch .'"><img src="../../../common/src/img/back.png" style="width:10px;height:10px">&nbsp;Prev Batch</a>';
        $ret .= '&nbsp;<a class="btn" href="' . $nextBatch .'">Next Batch&nbsp;<img src="../../../common/src/img/go.png" style="width:10px;height:10px"></a><br><br>';

        if ($id) {
            $query = $dbc->prepare('
                SELECT 
                    bl.upc,
                    p.description,
                    p.department AS pdept,
                    d.dept_name,
                    p.cost,
                    bl.salePrice AS price,
                    vd.margin AS unfiMarg,
                    vd.posDeptID,
                    vd.name AS vendorDeptName,
                    vd.deptID as unfiDeptId,
                    d.margin,
                    v.discountRate
                FROM batchList as bl
                    LEFT JOIN products AS p ON p.upc = bl.upc
                    LEFT JOIN departments AS d ON d.dept_no = p.department
                    LEFT JOIN vendorDepartments AS vd 
                        ON vd.vendorID = p.default_vendor_id 
                            AND vd.posDeptID = p.department
                    LEFT JOIN vendors AS v ON v.vendorID = p.default_vendor_id
                WHERE bl.batchID = ' . $id . '
                GROUP BY p.upc
                ;');
            $result = $dbc->execute($query);
            $ret .= '
                <div class="panel panel-default"><table class="table table-striped table-condensed small">
                    <th>UPC</th>
                    <th>Description</th>
                    <th>POS Dept.</th>
                    <th>Cost</th>
                    <th>Price</th>
                    <th>New Marg.</th>
                    <th>Desired Marg.</th>
                    <th>Diff.</th>
            ';
            while ($row = $dbc->fetch_row($result)) {
                $newMargin = ($row['price'] - $row['cost']) / $row['price'];
                if ($row['discountRate']) {
                    $newMargin = ( ( ($row['price'] * .10) + $row['price'] ) - $row['cost']) / ( ($row['price'] * .10) + $row['price'] );
                }
                $newMargin  = sprintf('%0.2f', $newMargin);
                
                $upc = '<a href="http://key/git/fannie/item/ItemEditorPage.php?searchupc=' . $row['upc'] . '" target="_blank">' . $row['upc'] . '</a>';
                $diff = $newMargin - $row['margin'];
                $diff = sprintf('%0.2f', $diff);
                $discountRate = $row['discountRate'];
                
                $ret .= '<tr><td>' . $upc . '</td>';
                $ret .= '<td>' . $row['description'] . '</td>';
                $ret .= '<td>' . $row['pdept'] . ' - ' . $row['dept_name'] . '</td>';
                $ret .= '<td>' . $row['cost'] . '</td>';
                $ret .= '<td><span class="text-warning">' . $row['price'] . '</span></td>';
                $ret .= '<td>' . $newMargin . '</td>';
                $ret .= '<td>' . $row['margin'] . '</td>';
                
                if ($diff < -0.08 | $diff > 0.08) {
                    $ret .= '<td><span style="color: tomato;">' . $diff . '</span></td>';
                } else {
                    $ret .= '<td>' . $diff . '</td>';
                }
                
            }
            if (mysql_errno() > 0) {
                $ret .= "<div class='alert alert-danger' align='center'>" . mysql_errno() . ": " . mysql_error(). "</div><br>";
            }
            $ret .= '</table></div>';

        }
        
        return $ret;
        
    }
    
    public function form_content($id)
    {
        $ret = '';
        $ret .= '<h4>Batch Review Page</h4>';
        
        if ($id) $ret .= ' Batch ID # ' . $id;
        
        $ret .= '
            <form method="get" class="form-inline">
                <input type="text" class="form-control" name="id" placeholder="Enter Batch  ID" autofocus>
				<button class="btn btn-default" type="submit">Submit</button>
            </form>
        ';
        
        return $ret;
    }
    
    public function help_content()
    {
        return '
            <ul>
            <li><b>Non-UNFI Review</b> Review non-UNFI vendors.</li>
            <li><b>UNFI Review</b> Review UNFI batches</li>
            <li><b>UNFI-MILK Review</b> Has not been updated in a long time, use with discretion.</li>
            </ul>
            <ul><label>Things to pay attention to </label>
            <div style="border: 1px solid lightgrey;"></div>
            <li><b>UNFI Batches</b> Make sure that POS Dept. matches UNFI Category. If 
                these categories do not match, check that the margin POS is using is correct.</li>
            <li><b>Diff</b> Diff. is the difference between what the new actual margin will be 
                and the desired margin. If this number is off by more than 0.05, there 
                is likely an issue with the new SRP.</li>

            </ul>';
    }
    
}

scancoordDispatch::conditionalExec();

