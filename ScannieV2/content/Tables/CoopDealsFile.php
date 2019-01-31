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
    include(__DIR__.'/../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../common/sqlconnect/SQLManager.php');
}
class CoopDealsFile extends PageLayoutA 
{

    protected $title = "";
    protected $description = "[] .";
    protected $ui = TRUE;

    public function preprocess()
    {
        $this->displayFunction = $this->pageContent();

        return false;
    }

    public function pageContent()
    {
        include(__DIR__.'/../../config.php');
        $ret = '';
        $dbc = scanLib::getConObj();

        $ret = '';
        $dbc = ScanLib::getConObj();
        $form = $this->formContent();

        if (isset($_GET['brand'])) {
            $brand = str_replace("'","\'",$_GET['brand']);
        }
        if ($dealSet = $_GET['dealSet']) {
            $ret .= $this->FormExtContent($dbc,$dealSet);
            $monthSelected = '<div><span class="badge badge-dark">Month Selected: </span> '.$dealSet.'</div>';

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
            $table = "";
            $table .= '<div class="card"><div class="card-body">
                <div class="table-responsive">
                <table class="table table-sm small">';
                $table .= '<thead><th>UPC</th><th>Period</th><th>Department</th><th>SKU</th><th>Brand</th>
                    <th>Description1</th><th>Description2</th><th>Size</th><th>SalePrice</th>
                    <th>NormalPrice</th><th>LineNotes</th><th>PromoDisc</th></thead>';
            foreach ($data as $upc => $row) {
                $table .= '<tr class="rowz">';
                $table .= '<td>' . $upc . '</td>';
                foreach ($row as $k => $v) {
                    $table .= '<td class="col'.$k.'">' . $v . '</td>';
                }
                $table .= '</tr>';
            }
            $table .= '</table></div></div></div>';
        }

        return <<<HTML
<div class="container-fluid" style="margin-top: 25px;">
    <div class="row">
        <div class="col-lg-3">
            $form
        </div>
        <div class="col-lg-3">
            $ret
        </div>
        <div class="col-lg-3">
            $monthSelected
        </div>
        <div class="col-lg-12 col-lf-offset-1">
        $table
        </div>
    </div>
</div>
HTML;
    }

    private function formContent()
    {
        $dbc = ScanLib::getConObj();
        $dealSets = "";
        $dealset = FormLib::get('dealSet');
        $sets = array();
        $prep = $dbc->prepare("SELECT dealSet FROM is4c_op.CoopDealsItems
            GROUP BY dealSet");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
           //$sets[] = date('m', strtotime($row['dealSet']));
           //echo "{$row['dealSet']}<br/>";
           $sets[] = $row['dealSet'];
        }
        sort($sets);
        foreach ($sets as $k => $set) {
            //$obj = DateTime::createFromFormat('!m', $set);
            //$sets[$k] = $obj->format('F');
        }
        foreach ($sets as $set) {
            $sel = ($dealset == $set) ? " selected" : "";
            $dealSets .= "<option value='{$set}' $sel>{$set}</option>";
        }

        return <<<HTML
<form name="dealSet" method="get">
<div class="from-group">
    <select name="dealSet" id="dealSet" class="form-control form-control-sm">
        <option value="">Select A Month</option>
        {$dealSets}
    </select>&nbsp;
</div>
<div class="form-group">
    <button class="btn btn-default mobile-show">Submit</button><br>
</div>
</form>
HTML;
    }

    private function FormExtContent($dbc,$dealSet)
    {
        $selbrand = FormLib::get('brand', 'Filter by Brand');
        $args = array($dealSet);
        $prep = $dbc->prepare("
            SELECT brand FROM is4c_op.CoopDealsItems AS c
                LEFT JOIN is4c_op.products AS p ON c.upc=p.upc
            WHERE dealSet = ? 
            GROUP BY brand");
        $res = $dbc->execute($prep,$args);
        $brans = array();
        while ($row = $dbc->fetch_row($res)) {
            $brands[] = htmlspecialchars($row['brand'], ENT_QUOTES);
        }

        //foreach ($brands as $value) echo $value . '<br>';

        $ret = '';
        $ret .= '<form name="brandform" method="get">
            <div class="form-group">
            <select class="form-control form-control-sm" name="brand" id="brandselect">
                    <option value="">Filter by Brand</option>';

        foreach ($brands as $brand) {
            $sel = ($brand == $selbrand) ? " selected" : "";
            $ret .= "<option value='$brand' $sel>$brand</option>";
        }

        $ret .= '</select></div>
            <input type="hidden" name="dealSet" value="'.$dealSet.'">
            <div class="form-group">
            <a class="btn btn-default btn-sm" href="?dealSet='.$dealSet.'">Show All Brands</a>
            </div>
            </form>
        ';

        return $ret;
    }

    public function javascriptContent()
    {
        return <<<JAVASCRIPT
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
JAVASCRIPT;
    }

    public function cssContent()
    {
        $mobileCss = "";
        if ($this->deviceType == "mobile") {
            $mobileCss = <<<HTML
.mobile-show {
    display: inline-block;
}
HTML;
        }
return <<<HTML
.mobile-show {
    display: none;
}
.click-highlight {
    background: yellow;
    background: linear-gradient(#FFF0E0, #FFF8E0);
}
.card, .card-body {
    padding: 0px;
}
$mobileCss
HTML;
    }

    public function helpContent()
    {
        return <<<HTML
<label>Coop Deals File</label>
<ul>
    <li>
        <strong>Help Content</strong>
        <p>Missing from this page.</p>
    </li>
</ul>    
HTML;
    }

}
WebDispatch::conditionalExec();
