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
    include(__DIR__.'/../../../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../../../common/sqlconnect/SQLManager.php');
}
class BatchSaleReviewPage extends PageLayoutA
{
    
    protected $title = 'Batch Review';
    protected $description = '[Batch Review] Review price change batch data to 
        ensure accuracy.';
    protected $ui = TRUE;
    
    public function body_content() 
    {
        include(__DIR__.'/../../../../config.php');
        $dbc = scanLib::getConObj();
        $curPage = basename($_SERVER['PHP_SELF']);
        
        $id = $_GET['id'];
        $batchname = "";
        if ($id > 0) {
            $a = array($id);
            $p = $dbc->prepare("SELECT batchName FROM batches WHERE batchID = ?");
            $r = $dbc->execute($p, $a);
            $row = $dbc->fetchRow($r);
            $batchname = $row['batchName'];
        }
        $ret = '';
        $ret .= '<div class="container">';
        include('BatchReviewLinks.php');
        $ret .= $this->form_content($id, $batchname);
        
        $ret .= '<a href="http://'.$FANNIEROOT_DIR.'/batches/newbatch/EditBatchPage.php?id=' 
            . $id . '" target="_blank"><span class="text-primary">View Batch</span></a>';
        $nextBatch = $_SERVER['PHP_SELF'] . '?id=' . ($id + 1);
        $prevBatch = $_SERVER['PHP_SELF'] . '?id=' . ($id - 1);
        $ret .= '&nbsp;<a class="btn btn-default btn-sm" href="' . $prevBatch .'">prev</a>';
        $ret .= '&nbsp;<a class="btn btn-default btn-sm" href="' . $nextBatch .'">next</a><br><br>';

        if ($id) {
            $query = $dbc->prepare('
                SELECT 
                    bl.upc,
                    p.description,
                    p.department AS pdept,
                    d.dept_name,
                    p.cost,
                    p.normal_price,
                    bl.salePrice,
                    vd.margin AS unfiMarg,
                    vd.posDeptID,
                    vd.name AS vendorDeptName,
                    vd.deptID as unfiDeptId,
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
                <div class="panel panel-default"><table class="table table-bordered table-condensed small">
                    <th>UPC</th>
                    <th>Description</th>
                    <th>POS Dept.</th>
                    <th>Cost</th>
                    <th>Price</th>
                    <th>Sale Price</th>
                    <th>Discount</th>
            ';
            while ($row = $dbc->fetch_row($result)) {
                $newMargin = ($row['price'] - $row['cost']) / $row['price'];
                if ($row['discountRate']) {
                    $newMargin = ( ( ($row['price'] * .10) + $row['price'] ) - $row['cost']) / ( ($row['price'] * .10) + $row['price'] );
                }
                $newMargin  = sprintf('%0.2f', $newMargin);
                
                $disco = $row['salePrice'] / $row['normal_price'];
                $disco = (1 - $disco) * 100; 


                $upc = '<a href="http://key/git/fannie/item/ItemEditorPage.php?searchupc=' . $row['upc'] . '" target="_blank">' . $row['upc'] . '</a>';
                $diff = $newMargin - $row['margin'];
                $diff = sprintf('%0.2f', $diff);
                $discountRate = $row['discountRate'];
                
                $ret .= '<tr><td>' . $upc . '</td>';
                $ret .= '<td>' . $row['description'] . '</td>';
                $ret .= '<td>' . $row['pdept'] . ' - ' . $row['dept_name'] . '</td>';
                $ret .= '<td>' . $row['cost'] . '</td>';
                $ret .= '<td><span class="text-warning">' . $row['normal_price'] . '</span></td>';
                $ret .= '<td>' . $row['salePrice'] . '</td>';
                $ret .= '<td>' . sprintf('%0.2f%%',$disco) . '</td>';
                
                
            }
            $ret .= '</table></div>';

        }
        
        return $ret;
        
    }
    
    public function form_content($id, $batchname)
    {
        $ret = '';
        $ret .= '<h4>Batch Review Page</h4>';
        
        if ($id) $ret .= ' Batch ID # ' . $id . ' - ' . $batchname;
        
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
WebDispatch::conditionalExec();
