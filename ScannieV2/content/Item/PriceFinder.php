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
/*
**  @class PriceFinder
**  Get a list of price changes for an array of UPCs.
*/
class PriceFinder extends PageLayoutA 
{

    protected $title = "";
    protected $description = "[] .";
    protected $ui = false;

    public function cssContent()
    {
return <<<HTML
HTML;
    }

    public function body_content()
    {
        $ret = '';
        include(__DIR__.'/../config.php');

        if ($data = FormLib::get('upcs', false)) {
            $upcs = array();
            $chunks = explode("\r\n", $data);
            foreach ($chunks as $key => $str) {
                $upcs[] = scanLib::upcPreparse($str);
            }
        } else {
            $upcs = array('0000000001008','0000000001009','0000000001010','0000000001011','0000000001012','0000000001138','0000000001296','0063547518235','0069685903149','0071375731861','0081076600001','0081076600002','0085559700412',);
        }

        $dbc = scanLib::getConObj();
        $prices = array();
        $modified = array();
        foreach ($upcs as $upc) {
            unset($prices);
            unset($modified);
            $a = array($upc);
            $p = $dbc->prepare("SELECT pu.price, 
                    DATE(pu.modified) AS modified, 
                    p.brand, p.description, p.normal_price
                FROM prodUpdate AS pu
                INNER JOIN products AS p ON pu.upc=p.upc
                WHERE pu.upc = ?");
            $r = $dbc->execute($p, $a);
            $lastprice = 9999.99;
            while ($row = $dbc->fetchRow($r)) {
                $curprice = $row['price'];
                $curmod = $row['modified'];
                $curdesc = $row['description'];
                $curbrand = $row['brand'];
                $curnp = $row['normal_price'];
                if ($curprice != $lastprice) {
                    $prices[] = $curprice;
                    $modified[] = $curmod;
                }
                $lastprice = $curprice;
            }
            $table = "<label>
                <div>$upc</div>
                <div><b>$curbrand</b></div>
                <div><i>$curdesc</i></div>
                <div><strong>current price: $curnp</strong></div>
                </label>
                <table class='table table-sm table-bordered'>
                <thead><th>Price</th><th>Modified</th></thead><tbody>";
            foreach ($prices as $k => $price) {
                $table .= "<tr>";
                $table .= "<td>$price</td><td>{$modified[$k]}</td>";
                $table .= "</tr>";
            }
            $table .= "</tbody></table>";
            $ret .= $table."<br/>";
        }
        if ($e = $dbc->error()) echo "<div class='alert alert-danger'>$e</div>";

        return <<<HTML
<div class="container">
<div class="row">
<div class="col-md-4">
    <form>
        <label>Submit a list of UPCs to view all prices changes for the corresponding items.</label>
        <div class="form-group">
            <textarea class="form-control" name="upcs"></textarea>
        </div>
        <div class="form-group">
            <button class="btn btn-default" type="submit">Submit</button>
        </div>
    </form>
    $ret
</div>
</div>
</div>
HTML;
    }

    private function form_content()
    {
        return <<<HTML
<form class ="form-inline"  method="get" >
    <div class="form-group">
        <div class="input-group">
            <input type="text" class="form-control" name="upc" placeholder="Enter a PLU" autofocus>
                <input type="submit" class="btn btn-defualt" value="go">
        </div>
    </div>
    <div class="form-group">
    </div>
</form>
HTML;
    }

    public function javascriptContent()
    {
        return <<<HTML
HTML;
    }

}
WebDispatch::conditionalExec();
