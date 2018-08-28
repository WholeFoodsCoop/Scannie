<?php 
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../common/sqlconnect/SQLManager.php');
}

class WeeklySalesByWeek extends PageLayoutA 
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

        return <<<HTML
HTML;
    }

    private function view()
    {
        $dbc = scanLib::getConObj();
        $prep = $dbc->prepare("
            SELECT c.obfWeekID AS weekID,
            SUM(c.actualSales) AS sales,
            MAX(c.transactions) AS trans,
            m.super_name,
            MAX(w.startDate) AS start,
            MAX(w.endDate) AS end
            FROM OpenBookFinancingV2.ObfSalesCache AS c
            INNER JOIN is4c_op.superDeptNames AS m ON c.superID=m.superID
            INNER JOIN OpenBookFinancingV2.ObfWeeks AS w ON c.obfWeekID=w.obfWeekID
            INNER JOIN OpenBookFinancingV2.ObfCategories AS cat ON c.obfCategoryID=cat.obfCategoryID
            WHERE c.obfWeekID between 161 and 214
            AND cat.storeID = 1
            AND m.super_name IN (
                'FROZEN','GEN MERCH','GROCERY','MEAT','REFRIGERATED','WELLNESS',
                'Deli Baked Goods','Deli Cheese/Spec','Deli Prep Foods','PRODUCE'
            )
            GROUP BY c.obfWeekID, m.super_name
        ");
        $res = $dbc->execute($prep);
        $cols = array('weekID', 'sales', 'super_name', 'start', 'end');
        $thead = "";
        foreach ($cols as $col) 
            $thead .= "<th>$col</th>";
        $table = "<div class='table-responsive'>
            <table class='table table-condensed table-bordered small'>
            <thead>$thead</thead>";
        $data = array();
        for ($i=162; $i<215; $i++) {
            $data[$i]['GROCERY'] = 0;
            $data[$i]['PRODUCE'] = 0;
            $data[$i]['DELI'] = 0;
            $data[$i]['WHOLE'] = 0;
        }
        $dates = array();
        $masters = array(
            'GROCERY' => array('FROZEN','GEN MERCH','GROCERY','MEAT','REFRIGERATED','WELLNESS'),
            'DELI' => array('Deli Baked Goods','Deli Cheese/Spec','Deli Prep Foods'),
            'PRODUCE' => array('PRODUCE'),
        );
        while ($row = $dbc->fetchRow($res)) {
            $super_name = $row['super_name'];
            $sales = $row['sales']; 
            $data[$row['weekID']]['WHOLE'] += $row['sales'];
            if (in_array($super_name, $masters['GROCERY'])) {
                $data[$row['weekID']]['GROCERY'] += $row['sales'];
            } elseif (in_array($super_name, $masters['DELI'])) {
                $data[$row['weekID']]['DELI'] += $row['sales'];
            } elseif (in_array($super_name, $masters['PRODUCE'])) {
                $data[$row['weekID']]['PRODUCE'] += $row['sales'];
            }
            $dates[$row['weekID']]['start'] = substr($row['start'], 0, 10);
            $dates[$row['weekID']]['end'] = substr($row['end'], 0, 10);
        }
        foreach ($data as $k => $v) {
            foreach ($v as $masterDept => $sales) {
                $table .= "<tr>";
                $table .= "<td>$k</td>";
                $table .= "<td>$sales</td>";
                $table .= "<td>$masterDept</td>";
                $table .= "<td>{$dates[$k]['start']}</td>";
                $table .= "<td>{$dates[$k]['end']}</td>";
                $table .= "</tr>";
            }
        }

        return <<<HTML
<h4>This report shows Weekly Sales from 2017-07-03 through
    2018-07-01 by Week, by Super Department. </h4>
$table
HTML;
    }

    public function cssContent()
    {
        return <<<HTML
HTML;
    }

}
WebDispatch::conditionalExec();
