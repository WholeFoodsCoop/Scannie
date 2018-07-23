<?php
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../../common/sqlconnect/SQLManager.php');
}
class BatchCheckMenu extends PageLayoutA
{
    protected $title = "Batch Check Menu";
    protected $description = "[] ";
    protected $ui = FALSE;

    public function preprocess()
    {
        include(__DIR__.'/../../../config.php');
        $dbc = scanLib::getConObj(); 
        if (FormLib::get('deleteSession', false)) {
            $this->displayFunction = $this->deleteSessionHandler();
            die();
        }
        if (FormLib::get('delete', false)) {
            $this->displayFunction = $this->deleteView();
        } else {
            $this->displayFunction = $this->view();
        }

        return false;
    }

    private function deleteSessionHandler()
    {
        $dbc = scanLib::getConObj();
        $storeID = scanLib::getStoreID();
        $sessionName = FormLib::get('sessionName');
        $args = array($storeID,$sessionName);
        $prep = $dbc->prepare("DELETE FROM woodshed_no_replicate.batchCheckQueues WHERE storeID = ? AND session = ?;");
        $res = $dbc->execute($prep,$args);
        
    }

    private function deleteView()
    {
        $dbc = scanLib::getConObj();
        $storeID = scanLib::getStoreID();
        $sessions = ''; 
        $args = array($storeID);
        $prep = $dbc->prepare("SELECT session, storeID FROM woodshed_no_replicate.batchCheckQueues WHERE storeID = ? GROUP BY session;");
        $res = $dbc->execute($prep,$args);
        while ($row = $dbc->fetchRow($res)) {
            $s = $row['session'];
            $id = $row['storeID'];
            $sessions .= "<div class='delete' id='$s' data-storeID='$id'> 
                <span class='smtext'>Session:</span> $s 
                <span class='smtext'>StoreID:</span> $id
            </div>"; 
                
        }
        return <<<HTML
$sessions
<span class="close">close</span>
HTML;
    }

    private function view()
    {
        $links = array(
            'Audie' => '../../../../item/AuditScanner.php',
            'Batch Check Queues' => 'BatchCheckQueues.php',
            'Chat' => 'BatchCheckChat.php',
            'Scanner' => 'SCS.php',
            'Sign Out' => 'SCS.php?signout=1',
            '*Cleanup*<br>Delete Sessions' => 'BatchCheckMenu.php?delete=1',
            '*Cleanup*<br>Delete Chat' => 'BatchCheckChat.php?delete=1',
        );
        $linksContent = '';
        foreach ($links as $name => $href) {
            $linksContent .= "<h2 class='menuOption'><a class='menuOption' href='$href'>$name</a><h2>";
        }
        return <<<HTML
<div id="menu in">
    <div align="center">
        <h1>Batch Check Menu</h1>
        <br/>
        $linksContent
    </div>
</div>
HTML;
    }

    public function javascriptContent()
    {
        return <<<HTML
$('.menuOption').click(function(){
    //var c = confirm('Are you sure?');
    if (c == true) {
        
    }
});
$('.delete').click(function(){
    var id = $(this).attr('id');
    var storeID = $(this).attr('storeID');
    var c = confirm('Delete '+id+' from Batch Check Queues?');
    if (c == true) {
        $.ajax({
            url: 'BatchCheckMenu.php',
            data: 'deleteSession=1&sessionName='+id+'&storeID='+storeID,
            success: function(resp) {
                window.location.reload(); 
            }
        });
    }
});
$('.close').click(function(){
    window.location.href = 'BatchCheckMenu.php';
});
HTML;
    }

    public function cssContent()
    {
        if (!class_exists('SCS')) {
            include('SCS.php');
        }
        $cssContent = SCS::cssContent(); 
        return <<<HTML
$cssContent
body {
    font-family: Arial, Helvetica, sans-serif;
    background-color: rgba(255,255,255,0.9);
    background: linear-gradient(135deg, #42a7f4, #0a1528);
    background-color: linear-gradient(135deg, #42a7f4, #0a1528);
    background-repeat: no-repeat;
    background-attachment: fixed;
}
@media screen and (min-width: 1200px) {
    .menuOption {
        font-size: 28px;
    }
}
h2.menuOption {
    background: rgba(255,255,255,0.1);
    background-color: rgba(255,255,255,0.1);
    padding: 25px;
}
h1 {
    margin-top: 25px;
}
.delete {
    border: 1px solid #cacaca;
    margin: 1vw;
    padding: 1vw;
    cursor: pointer;
}
.close {
    cursor: pointer;
}
HTML;
    }
}
WebDispatch::conditionalExec();
