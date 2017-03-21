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
    
    private function js()
    {
        ob_start();
        ?>
            <script type="text/javascript">
            $(document).ready(function() {
                $('.rowz').click(function() {
                    if ( $(this).hasClass('click-highlight') ) {
                        $(this).removeClass('click-highlight');
                    } else {
                        $(this).addClass('click-highlight'); 
                    }
                });
            });
            </script>
        <?php
        return ob_get_clean();
    }

    public function body_content()
    {
        $ret = '';
        $ret .= $this->js();
        include('../../config.php');
        $dbc = new SQLManager($SCANHOST, 'pdo_mysql', 'woodshed_no_replicate', $SCANUSER, $SCANPASS);
        $ret .= $this->form_content();

        if (isset($_GET['brand'])) {
            $brand = str_replace("'","\'",$_GET['brand']);
        }
        if (isset($_GET['description'])) {
            $description = $_GET['description'];
        }

        if ($month = $_GET['month']) {
            $ret .= 'Month Selected: <strong>' . $month . '</strong>';

            $ret .= $this->form_ext_content($dbc,$month);

            $query = $dbc->prepare("
                SELECT
                    upc,
                    flyerPeriod,
                    department,
                    sku,
                    brand,
                    description,
                    packSize,
                    srp,
                    lineNotes,
  					promoDiscount
                FROM CoopDeals".$month."
                ORDER BY upc ASC
            ;");
            $queryBrand = $dbc->prepare("
                SELECT
                    upc,
                    flyerPeriod,
                    department,
                    sku,
                    brand,
                    description,
                    packSize,
                    srp,
                    lineNotes,
					promoDiscount
                FROM CoopDeals".$month."
                    WHERE brand = '".$brand."'
                ORDER BY upc ASC
            ");
            $queryDesc = $dbc->prepare("
                SELECT
                    upc,
                    flyerPeriod,
                    department,
                    sku,
                    brand,
                    description,
                    packSize,
                    srp,
                    lineNotes,
					promoDiscount
                FROM CoopDeals".$month."
					WHERE description like '% ? %'
                ORDER BY upc ASC
            ");

			if (isset($_GET['brand'])) {
				$result = $dbc->execute($queryBrand);
			} elseif (isset($_GET['description'])) {
				$result = $dbc->execute($queryDesc,$description);
			} else {
	            $result = $dbc->execute($query);
			}
            $data = array();
            while ($row = $dbc->fetch_row($result)) {
                $upc = $row['upc'];
                $data[$upc]['period'] = $row['flyerPeriod'];
                $data[$upc]['dept'] = $row['department'];
                $data[$upc]['sku'] = $row['sku'];
                $data[$upc]['brand'] = $row['brand'];
                $data[$upc]['desc'] = $row['description'];
                $data[$upc]['size'] = $row['packSize'];
                $data[$upc]['price'] = $row['srp'];
                $data[$upc]['lineNotes'] = $row['lineNotes'];
				$data[$upc]['promoDiscount'] = $row['promoDiscount'].'% OFF';
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
					<th>Description</th>
					<th>Size</th>
					<th>Sale Price</th>
					<th>Line Notes</th>
					<th>PromoDisc</th>
				</thead>
			';
            foreach ($data as $upc => $row) {
                $ret .= '<tr class="rowz">';
                $ret .= '<td>' . $upc . '</td>';
                foreach ($row as $k => $v) {
                    $ret .= '<td>' . $v . '</td>';
                }
                $ret .= '</tr>';
            }
            $ret .= '</table></div>';
        }

        return $ret;
    }

    private function form_content()
    {

        return '
            <form class ="form-inline"  method="get" >
                <select name="month" class="form-control">
                    <option value="">Select A Month</option>
                    <option value="Jan">January</option>
                    <option value="Feb">February</option>
                    <option value="Mar">March</option>
                    <option value="Apr">April</option>
                    <option value="May">May</option>
                    <option value="June">June</option>
                    <option value="July">July</option>
                    <option value="Aug">August</option>
                    <option value="Sep">September</option>
                    <option value="Oct">October</option>
                    <option value="Nov">November</option>
                    <option value="Dec">December</option>
                </select>&nbsp;
                <button class="btn btn-default">Submit</button><br>
            </form>
        ';

    }

    private function form_ext_content($dbc,$month)
    {
        $prep = $dbc->prepare("SELECT brand FROM CoopDeals".$month." GROUP BY brand");
        $res = $dbc->execute($prep);
        $brans = array();
        while ($row = $dbc->fetch_row($res)) {
            $brands[] = $row['brand'];
        }

        //foreach ($brands as $value) echo $value . '<br>';

        $ret = '';
        $ret .= '
        <br><br>
        <div style="width:500px">
                <form class="form-inline" method="get">
                    &nbsp;<div class="input-group">
                        <span class="input-group-addon">Brand</span>
                        <select class="form-control" name="brand">
                            <option value=""></option>';

        foreach ($brands as $brand) $ret .= '<option value="'.$brand.'">'.$brand.'</option>';

        $ret .= '
                        </select>
                    </div>

					<input type="hidden" name="month" value="'.$month.'">
                    <br><br>&nbsp;<button class="btn btn-default btn-sm">Narrow Search</button> &nbsp;&nbsp;
          			<a class="btn btn-default btn-sm" href="http://key/scancoord/item/Batches/CoopDealsSearchPage.php?month='.$month.'">Clear Search</a>
                </form>
        </div>
        ';

        return $ret;
    }

}
ScancoordDispatch::conditionalExec();