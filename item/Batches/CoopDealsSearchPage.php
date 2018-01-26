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
include('../../config.php');
if (!class_exists('ScancoordDispatch')) {
    include($SCANROOT.'/common/ui/CorePage.php');
}
if (!class_exists('SQLManager')) {
    include_once(dirname(dirname(dirname(__FILE__))) . '/common/sqlconnect/SQLManager.php');
}
class CoopDealsSearchPage extends ScancoordDispatch
{

    protected $title = "Search Coop Deals";
    protected $description = "[Search Coop Deals] Look through Co-op Deals commitmend worksheet.";
    protected $ui = TRUE;
    
    public function javascriptContent()
    {
        return <<<HTML
$(document).ready(function() {
    $('.rowz').click(function() {
        if ( $(this).hasClass('click-highlight') ) {
            $(this).removeClass('click-highlight');
        } else {
            $(this).addClass('click-highlight'); 
        }
    });
});
    $('#dealSet').on('change', function(){
        document.forms['dealSet'].submit();
    });
    $('#brandselect').on('change', function(){
        document.forms['brandform'].submit();
    });
HTML;
    }

    public function body_content()
    {
        $ret = '';
        $dbc = ScanLib::getConObj();
        $ret .= $this->form_content();

        if (isset($_GET['brand'])) {
            $brand = str_replace("'","\'",$_GET['brand']);
        }
        /*
        if (isset($_GET['description'])) {
            $description = $_GET['description'];
        }
        */
        if ($dealSet = $_GET['dealSet']) {
            $ret .= 'Month Selected: <strong>' . $dealSet . '</strong>';

            $ret .= $this->form_ext_content($dbc,$dealSet);

            $args = array();
            $query = $dbc->prepare("
                SELECT
                    c.upc,
                    c.abtpr,
                    p.department,
                    v.sku,
                    p.brand,
                    p.description AS posDesc,
                    p.size,
                    c.price,
                    c.multiplier,
  					c.promoDiscount,
                    p.normal_price,
                    pu.description AS signDesc
                FROM is4c_op.CoopDealsItems AS c
                    LEFT JOIN is4c_op.productUser AS pu ON pu.upc=c.upc
                    LEFT JOIN is4c_op.products AS p ON c.upc=p.upc
                    LEFT JOIN is4c_op.vendorItems AS v ON p.default_vendor_id=v.vendorID 
                        AND c.upc=v.upc
                WHERE c.dealSet = ?
                    AND p.upc IS NOT NULL;
                ORDER BY c.upc ASC
            ;");
            $queryBrand = $dbc->prepare("
                SELECT
                    c.upc,
                    c.abtpr,
                    p.department,
                    v.sku,
                    p.brand,
                    p.description AS posDesc,
                    p.size,
                    c.price,
                    c.multiplier,
  					c.promoDiscount,
                    p.normal_price,
                    pu.description AS signDesc
                FROM is4c_op.CoopDealsItems AS c
                    LEFT JOIN is4c_op.productUser AS pu ON pu.upc=c.upc
                    LEFT JOIN is4c_op.products AS p ON c.upc=p.upc
                    LEFT JOIN is4c_op.vendorItems AS v ON p.default_vendor_id=v.vendorID 
                        AND c.upc=v.upc
                WHERE c.dealSet = ?
                    AND p.brand = ?
                    AND p.upc IS NOT NULL;
                ORDER BY c.upc ASC
            ");

			if (isset($_GET['brand'])) {
                $args[] = $_GET['dealSet'];
                $args[] = $_GET['brand'];
				$result = $dbc->execute($queryBrand,$args);
			} else {
                $args[] = $_GET['dealSet'];
	            $result = $dbc->execute($query,$args);
			}
            $data = array();
            while ($row = $dbc->fetch_row($result)) {
                $upc = $row['upc'];
                $data[$upc]['period'] = $row['abtpr'];
                $data[$upc]['dept'] = $row['department'];
                $data[$upc]['sku'] = $row['sku'];
                $data[$upc]['brand'] = $row['brand'];
                $data[$upc]['desc'] = $row['posDesc'];
                $data[$upc]['desc2'] = $row['signDesc'];
                $data[$upc]['size'] = $row['size'];
                $data[$upc]['price'] = $row['price'];
                $data[$upc]['normal_price'] = $row['normal_price'];
                $data[$upc]['lineNotes'] = $row['multiplier'];
				$data[$upc]['promoDiscount'] = ($row['promoDiscount']*100).'% OFF';
            }
            if ($dbc->error()) echo $dbc->error();
            $ret .= '<div class="panel panel-default">
                <table class="table table-condensed small">';
			$ret .= '
				<thead>
					<th>UPC</th>
					<th>Period</th>
					<th>Department</th>
					<th>SKU</th>
					<th>Brand</th>
					<th>Description 1</th>
					<th>Description 2</th>
					<th>Size</th>
					<th>Sale Price</th>
					<th>Normal Price</th>
					<th>Line Notes</th>
					<th>PromoDisc</th>
				</thead>
			';
            foreach ($data as $upc => $row) {
                $ret .= '<tr class="rowz">';
                $ret .= '<td>' . $upc . '</td>';
                foreach ($row as $k => $v) {
                    $ret .= '<td class="col'.$k.'">' . $v . '</td>';
                }
                $ret .= '</tr>';
            }
            $ret .= '</table></div>';
        }

        return $ret;
    }

    private function form_content()
    {
        $dbc = ScanLib::getConObj();
        $dealSets = "";
        $sets = array();
        $prep = $dbc->prepare("SELECT dealSet FROM is4c_op.CoopDealsItems
            GROUP BY dealSet");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $sets[] = $row['dealSet'];
        }
        foreach ($sets as $set) {
            $dealSets .= "<option value='{$set}'>{$set}</option>";
        }

        return <<<HTML
<form class ="form-inline" name="dealSet" method="get" >
    <select name="dealSet" id="dealSet" class="form-control">
        <option value="">Select A Month</option>
        {$dealSets}
    </select>&nbsp;
    <button class="btn btn-default hidden-lg hidden-md">Submit</button><br>
</form>
HTML;
    }

    private function form_ext_content($dbc,$dealSet)
    {
        $args = array($dealSet);
        $prep = $dbc->prepare("
            SELECT brand FROM is4c_op.CoopDealsItems AS c
                LEFT JOIN is4c_op.products AS p ON c.upc=p.upc
            WHERE dealSet = ? 
            GROUP BY brand");
        $res = $dbc->execute($prep,$args);
        $brans = array();
        while ($row = $dbc->fetch_row($res)) {
            $brands[] = $row['brand'];
        }

        //foreach ($brands as $value) echo $value . '<br>';

        $ret = '';
        $ret .= '
        <br><br>
        <div style="width:500px">
                <form class="form-inline" name="brandform" method="get">
                    &nbsp;<div class="input-group">
                        <span class="input-group-addon">Brand</span>
                        <select class="form-control" name="brand" id="brandselect">
                            <option value=""></option>';

        foreach ($brands as $brand) $ret .= '<option value="'.$brand.'">'.$brand.'</option>';

        $ret .= '
                        </select>
                    </div>

					<input type="hidden" name="dealSet" value="'.$dealSet.'">
                    <br><br>&nbsp;<button class="btn btn-default btn-sm hidden-lg hidden-md">Filter by Brand</button>
          			<a class="btn btn-default btn-sm" href="http://key/scancoord/item/Batches/CoopDealsSearchPage.php?dealSet='.$dealSet.'">Show All Brands</a>
                </form>
        </div>
        ';

        return $ret;
    }

}
ScancoordDispatch::conditionalExec();
