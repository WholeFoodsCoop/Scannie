<?php
if (!class_exists('PageLayoutA')) {
    include(dirname(__FILE__).'/../PageLayoutA.php');
}
if (!class_exists('RenderReceiptPage')) {
    include_once('/var/www/html/git/IS4C/fannie/admin/LookupReceipt/RenderReceiptPage.php');
}
/**
 *  @class MultipleReceipts  - open multiple receipts at once
 */
class MultipleReceipts extends PageLayoutA 
{

    public function body_content()
    {
        $ret = '';

        return <<<HTML
<form name="myform" id="myform" style="padding: 25px;">
    <table class="" id="mytable">
        <thead>
            <th>Trans Number <span title="emp_no - register_no - trans_no">(?)</span></th><th>Date <span  title="YYYY-MM-DD">(?)</span></th>
        </thead>
        <tr>
            <td><input type="text" name="transNum[]"/></td>
            <td><input type="text" name="date[]"/></td>
        </tr>
    </table>
    <div class="form-group">
        <a id="addInput" class="btn btn-info btn-sm" href="#"> Add Receipt </a>
    </div>
    <div class="form-group">
        <a class="btn btn-primary btn-sm" id="submit" href="#">Find Receipts</a>
    </div>
</form>
HTML;
    }

    public function javascriptContent()
    {
        return <<<JAVASCRIPT
$(document).ready(function(){
});
$('#addInput').on('click', function(){
    var table = $('#mytable'); 
    var apndstr = '<tr><td><input type="text" name="transNum[]"/></td><td><input type="text" name="date[]"/></td></tr>';
    table.append(apndstr);
});
var trans = {
    'transnum' : [],
    'tdate' : [],
}
var i = 0;
$('#submit').on('click', function(){
    $('input').each(function(){
        var elem = $(this);
        var type = elem.attr('name');
        var val = elem.val();
        if (type == 'transNum[]') {
            trans.transnum[i] = val;
        }
        if (type == 'date[]') {
            trans.tdate[i] = val;
            i++;
        }
    });
    for (i; i>0; i--) {
        transnum = trans.transnum[i-1];
        tdate = trans.tdate[i-1];
        window.open('http://key/git/fannie/admin/LookupReceipt/RenderReceiptPage.php?date='+tdate+'&receipt='+transnum, '_blank');
    }
});
JAVASCRIPT;
    }

    public function cssContent()
    {
    }

}
WebDispatch::conditionalExec();
