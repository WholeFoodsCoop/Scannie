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
class BatchReviewPageMilk extends PageLayoutA 
{
    protected $title = 'Batch Review';
    protected $description = '[Batch Review] Review price change batch data to 
        ensure accuracy.';
    protected $ui = TRUE;
    
    public function body_content() 
    {
        
        include(__DIR__.'/../../../../config.php');
        include(__DIR__.'/../../../../common/lib/PriceRounder.php');
        $dbc = scanLib::getConObj();
        $curPage = basename($_SERVER['PHP_SELF']);
        $rounder = new PriceRounder();
        
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
        $ret .= '<div class="container-fluid">';
        include('BatchReviewLinks.php');
        $ret .= $this->form_content($id, $batchname);
        
        $ret .= '<a href="http://key/git/fannie/batches/newbatch/EditBatchPage.php?id=' 
            . $id . '" target="_blank"><span class="text-primary no-print">View Batch</span></a>';
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
                    p.normal_price AS price,
                    vd.margin AS unfiMarg,
                    vd.posDeptID,
                    vd.name AS vendorDeptName,
                    vd.deptID as unfiDeptId,
                    d.margin,
                    p.price_rule_id
                FROM batchList as bl
                    LEFT JOIN products AS p ON p.upc = bl.upc
                    LEFT JOIN departments AS d ON d.dept_no = p.department
                    LEFT JOIN vendorDepartments AS vd 
                        ON vd.vendorID = p.default_vendor_id 
                            AND vd.posDeptID = p.department
                WHERE bl.batchID = ' . $id . '
                GROUP BY p.upc
                ;');
            $result = $dbc->execute($query);
            $ret .= '
                <div class="panel panel-default"><table class="table table-bordered table-condensed small">
                    <th>UPC</th>
                    <th>Description</th>
                    <th>POS Dept.</th>
                    <th>Cur Marg</th>
                    <th>New Marg | NM - 25.5%</th>
                    <th>Cost</th>
                    <th>Price</th>
                    <th>SRP</th>
                    <th title="Difference between New Margin and Desired Margin.">Diff.</th>
            ';
            while ($row = $dbc->fetch_row($result)) {
                $upc = '<a href="http://key/git/fannie/item/ItemEditorPage.php?searchupc=' . $row['upc'] . '" target="_blank">' . $row['upc'] . '</a>';
                $margin = .2550;
                $c = $row['cost'];
                $p = $row['price'];
                $srp = $c / (1 - $margin); 
                $srp = $rounder->round($srp);
                $diff = $srp - $p; 

                $curMargin = ($p - $c) / $p;
                $curMargin = sprintf('%0.2f', $curMargin);
                $adj = $margin - $curMargin;
                $adjClassA = ($adj > 0.05 || $adj < -0.05) ? 'red' : 'blue';
                $adjSpanA = '<span class="adj '.$adjClassA.'">'.$adj.'</span>';

                $newMargin = ($srp - $c) / $srp;
                $newMargin  = sprintf('%0.2f', $newMargin);
                $adj = $margin - $newMargin;
                $adjClassB = ($adj > 0.05 || $adj < -0.05) ? 'red' : 'blue';
                $adjSpanB = '<span class="adj '.$adjClassB.'">'.$adj.'</span>';

                $pipe = '<span style="color: lightgrey"> | </span>';
                $curMargin = sprintf('%s %s %s',$curMargin,$pipe,$adjSpanA);
                $newMargin = sprintf('%s %s %s',$newMargin,$pipe,$adjSpanB);
                // hide variable items 
                $tr = ($row['price_rule_id'] != 0) ? "<tr style='display:none'>" : "<tr>";
                
                $ret .= $tr . '<td>' . $upc . '</td>';
                $ret .= '<td>' . $row['description'] . '</td>';
                $ret .= '<td>' . $row['pdept'] . ' - ' . $row['dept_name'] . '</td>';
                $ret .= "<td>$curMargin</td>";
                $ret .= "<td>$newMargin</td>";
                $ret .= '<td>' . $row['cost'] . '</td>';
                $ret .= '<td>' . $row['price'] . '</td>';
                $ret .= '<td class="srp-col">' . $srp. '</td>';
                
                if ($diff < -0.08 | $diff > 0.08) {
                    $ret .= '<td class="redText diff-col">' . $diff . '</td>';
                } else {
                    $ret .= '<td class="diff-col">' . $diff . '</td>';
                }
                
            }
               
            $ret .= '</table></div>';

        }
        
        return $ret;
        
    }
    
    public function form_content($id, $batchname)
    {
        $ret = '';
        $ret .= '<h4>Milk Batch Review Page</h4>';
        
        if ($id) $ret .= ' Batch ID # ' . $id . ' - ' . $batchname;
        
        $ret .= '
            <form method="get" class="form-inline no-print">
                <input type="text" class="form-control" name="id" placeholder="Enter Batch  ID" autofocus>
                    <button class="btn btn-default" type="submit">Submit</button> | 
                    <a class="" href="#" onClick="hideZero(); return false;">Hide No-Change Rows</a>
            </form>
        ';
        
        return $ret;
    }

    protected function javascriptContent()
    {
        return <<<HTML
$(function(){
});

function hideZero()
{
    $('td').each(function(){
        var diffcol= $(this).hasClass('diff-col');
        if (diffcol) {
            var value = $(this).text();
            if (value == 0) {
                $(this).closest('tr').hide();
            }
        }
    });
}
HTML;
    }

    public function css_content()
    {
        return <<<HTML
.blue {
    color: lightblue;
}
HTML;
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
