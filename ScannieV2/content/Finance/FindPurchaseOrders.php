<?php 
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../common/sqlconnect/SQLManager.php');
}

class FindPurchaseOrders extends PageLayoutA 
{
    
    protected $title = "Find Purchase Orders";
    protected $ui = TRUE;

    public function __construct()
    {
    }

    public function preprocess()
    {
        $this->displayFunction = $this->view();

        return false;
    }

    private function formContent()
    {
        $startDate = FormLib::get('startDate');
        $endDate = FormLib::get('endDate');

        return <<<HTML
<form method="post">
    <div class="form-group">
        <label>Start Date <i>* dates must be in YYYY-MM-DD format *</i></label>
        <input type="text" name="startDate" value="$startDate" class="form-control">
    </div>
    <div class="form-group">
        <label>End Date</label>
        <input type="text" name="endDate" value="$endDate" class="form-control">
    </div>
    <div class="form-group">
        <label>UPCS</label>
        <textarea name='upcs' class="form-control"></textarea>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Submit</button>
    </div>
</form>
HTML;
    }

    private function view()
    {
        include(__DIR__.'/../../config.php');
        $dbc = scanLib::getConObj();
        $ret = '';
        $ret .= $this->formContent();
        $upcs = array();
        $u = FormLib::get('upcs');
        $startDate = FormLib::get('startDate')." 00:00:00";
        $endDate = FormLib::get('endDate')." 00:00:00";
        if ($u) {
            $plus = array();
            $chunks = explode("\r\n", $u);
            foreach ($chunks as $key => $str) {
                $upcs[] = scanLib::upcPreparse($str);
            }
        }
        $data = array();
        foreach ($upcs as $upc) {
            $a = array($upc, $startDate, $endDate);
            $p = $dbc->prepare("select * from PurchaseOrderItems AS p 
                LEFT JOIN PurchaseOrder AS po ON p.orderID=po.orderID
                where internalUPC = ? AND receivedDate BETWEEN ? AND ? 
                AND internalUPC <> '0000000000000' 
                ORDER BY receivedDate DESC LIMIT 1;");
            $r = $dbc->execute($p, $a);
            while ($row = $dbc->fetchRow($r)) {
                $data[$upc][] = array($row['receivedDate'], $row['receivedQty'], $row['orderID'], $row['salesCode'], $row['vendorInvoiceID']);
            }
            if ($er = $dbc->error()) echo "<div>$er</div>";
            
        }
        $table = "<table class='table table-bordered table-condensed small'>
            <thead>
                <th>UPC</th>
                <th>Received Date</th> 
                <th>Received Qty.</th>
                <th>Order ID</th>
                <th>Sales Code</th>
                <th>Invoice Number</th>
            </thead><tbody>";
        foreach ($data as $upc => $arr) {
            $table .= "<tr>";
            foreach ($arr as $k => $row) {
                $table .= "<td>$upc</td>";
                $table .= "<td>$row[0]</td>";
                $table .= "<td>$row[1]</td>";
                $table .= "<td>$row[2]</td>";
                $table .= "<td>$row[3]</td>";
                $table .= "<td>$row[4]</td>";
            }
            $table .= "</tr>";
        }
        $table .= '</tbody></table>';

        return <<<HTML
<div class='container'>
    <p><u>
        This report finds the most recent purchase order for 
        a list of UPCS within a given time range. 
    </u></p>
    $ret
    $table
</div>
HTML;
    }

    public function cssContent()
    {
        return <<<HTML
HTML;
    }

}
WebDispatch::conditionalExec();
