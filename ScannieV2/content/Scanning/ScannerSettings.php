<?php
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../common/sqlconnect/SQLManager.php');
}
class ScannerSettings extends PageLayoutA
{
    protected $title = "Scanner Settings";
    protected $description = "[Scanner Settings] Control Settings.";
    protected $ui = true;

    public function preprocess()
    {
        $this->displayFunction = $this->postView();
        $this->__routes[] = 'post<save>';

        return parent::preprocess();
    }

    public function postSaveHandler()
    {
        $dbc = scanLib::getConObj('SCANALTDB');

        $checked = FormLib::get('checked');
        $checked = ($checked == 'true') ? 1 : 0;
        $a = array($checked, session_id());
        $p = $dbc->prepare("UPDATE ScannieConfig SET scanBeep = ? WHERE session_id = ?;");
        $dbc->execute($p, $a);
        if ($er =  $dbc->error()) echo "<div class=\"alert alert-danger\">$er</div>";

        return false;
    }

    public function postView()
    {
        $dbc = scanLib::getConObj('SCANALTDB');
        $SESSION_ID = session_id();
        $a = array($SESSION_ID);
        $p = $dbc->prepare("SELECT * FROM ScannieConfig WHERE session_id = ?;");
        $r = $dbc->execute($p, $a);
        while ($row = $dbc->fetchRow($r)) {
            $beepOnScan = $row['scanBeep'];
            $beepChecked = ($beepOnScan) ? 'checked' : '';
        };
        return <<<HTML
<div class="container-fluid" style="margin-top: 25px;">
    <form method="post">
        <ul>
            <li>
                <label>Beep After Scan: </label>
                <input type="checkbox" name="scanBeep" value=1 id="toggleBeep" $beepChecked />
            </li>
        </ul>
        <input type="hidden" name="sessionID" id="sessionID" value="$a" />
    </form>
</div>
HTML;
    }

    public function javascriptContent()
    {
        return <<<JAVASCRIPT
$('input[type="checkbox"]').on('change', function(){
    var checked = $(this).prop('checked');
    var sessionID = $('#sessionID');
    $.ajax({
        url: 'ScannerSettings.php',
        data: 'checked='+checked+'&save=1',
        type: 'post',
        dataType: 'text',
        success: function(response)
        {
            alert('success');
        }
    });
});
JAVASCRIPT;
    }
}
WebDispatch::conditionalExec();
