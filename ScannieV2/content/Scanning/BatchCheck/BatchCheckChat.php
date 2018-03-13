<?php
if (!class_exists('PageLayoutA')) {
    include(__DIR__.'/../../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(__DIR__.'/../../../common/sqlconnect/SQLManager.php');
}
class BatchCheckChat extends PageLayoutA
{
    protected $title = "Batch Check Chat";
    protected $description = "[] ";
    protected $ui = FALSE;

    public function preprocess()
    {
        include(__DIR__.'/../../../config.php');
        $dbc = scanLib::getConObj(); 
        if (FormLib::get('sendMsg', false)) {
            $this->sendMsgHandler($dbc);
        } elseif (FormLib::get('getNewMsg', false)) {
            $this->getNewMsgHandler($dbc);
            die();
        } elseif (FormLib::get('delete', false)) {
            $this->deleteHandler($dbc);
            echo "<div align='center'>Chat has been deleted.</div></div></div></div></div></div></div></div></div>";
            //die();
        }

        $this->displayFunction = $this->view($dbc);

        return false;
    }

    private function deleteHandler($dbc)
    {
        $prep = $dbc->prepare("DELETE FROM woodshed_no_replicate.batchCheckChat;");
        $res = $dbc->execute($prep);
        $row = $dbc->fetchRow($res);
    }

    private function getNewMsgHandler($dbc)
    {
        $lastMsg = FormLib::get('lastMsg');
        $prep = $dbc->prepare("SELECT max(id) AS maxid FROM woodshed_no_replicate.batchCheckChat;");
        $res = $dbc->execute($prep);
        $row = $dbc->fetchRow($res);
        // if id's don't match, return all messages with id's within the un-viewed range 
        // and update $lastMsg;
        $json = array();
        if ($row['maxid'] != $lastMsg) {
            $ids = array();
            $maxid = $row['maxid'];
            for ($i=$lastMsg+1; $i<$maxid+1; $i++) {
                $ids[] = $i;
            }
            list($inStr,$args) = $dbc->safeInClause($ids);
            $query = "SELECT id, user, text FROM woodshed_no_replicate.batchCheckChat
                WHERE id IN ($inStr);";
            $prep = $dbc->prepare($query);
            $res = $dbc->execute($prep,$args);
            $json['output'] = '';
            while ($row = $dbc->fetchRow($res)) {
                $id = $row['id'];
                $user = $row['user'];
                $text = $row['text'];
                $json['output'] .= "
                    <div id='m$id' class='message newmessage'>
                        <div class='user'>$user</div>
                        <p class='message'>$text</p>
                    </div>
                ";
            }
            $_SESSION['lastMsg'] = $maxid;
            $json['lastMsg'] = $maxid;

            echo json_encode($json);
            return false;
        } else {
            $json['output'] = false;
            $json['lastMsg'] = false;

            echo json_encode($json);
            return false;
        }
    }

    private function sendMsgHandler($dbc)
    {
        $storeID = scanLib::getStoreID();
        $text = FormLib::get('message');
        $stores = array(1=>'Hillside',2=>'Denfeld');
        $args = array($stores[$storeID],$text);
        $prep = $dbc->prepare("INSERT INTO woodshed_no_replicate.batchCheckChat (user,text,time)
            VALUES (?, ?, NOW())");
        $res = $dbc->execute($prep,$args);

        return false;
        
    }

    private function view($dbc)
    {
        $storeID = scanLib::getStoreID();
        $userName = ($u = scanLib::getUser()) ? "$u@" : '';

        $prep = $dbc->prepare("SELECT max(id) AS maxid FROM woodshed_no_replicate.batchCheckChat");
        $res = $dbc->execute($prep);
        $lastMsgId = $dbc->fetchRow($res);
        $lastMsgId = $lastMsgId['maxid'];
        $_SESSION['lastMsg'] = $lastMsgId;

        $prep = $dbc->prepare("SELECT id, user, text FROM woodshed_no_replicate.batchCheckChat");
        $res = $dbc->execute($prep);
        $messages = ''; 
        while ($row = $dbc->fetchRow($res)) {
            $id = $row['id'];
            $store = $row['user'];
            $text = $row['text'];
            $messages .= "
                <div id='m$id' class='message'>
                    <div class='user'>$userName$store</div>
                    <p class='message'>$text</p>
                </div>
            ";
        }

        return <<<HTML

<div id="messageBoard">$messages</div>
<div class="form" id="myform" >
    <form name="chatForm" method="post" action="BatchCheckChat.php#myform">
        <input type="hidden" id="lastMsg" name="lastMsg" value="$lastMsgId">
        <textarea class="form-control" name="message" id="message"></textarea>
        <button type="submit" name="sendMsg" value="1" class="btn btn-default">Send</button>
    </form>
    <a class="btn btn-default" href="SCS.php">Back to Scanner</a>
</div>
HTML;
    }

    public function javascriptContent()
    {
        return <<<HTML
$(function(){
    setInterval('ajaxRequest()',1000);
});
function ajaxRequest()
{
    var lastMsg = $('#lastMsg').val();
    $.ajax({
        type: 'post',
        data: 'getNewMsg=1&lastMsg='+lastMsg,
        dataType: 'json',
        url: 'BatchCheckChat.php',
        success: function(json) {
            var output = json.output;
            var lastMsg = json.lastMsg;
            if (lastMsg) {
                var lastMsg = json.lastMsg;
                $('#lastMsg').val(lastMsg);
            }
            if (output) {
                var oldhtml = $('#messageBoard').html();
                var newhtml = oldhtml + output;
                $('#messageBoard').html(newhtml);
                $('[id]').each(function () {
                    $('[id="' + this.id + '"]:gt(0)').remove();
                });
            }
        }
    });
}
HTML;
    }

    public function cssContent()
    {
        if (!class_exists('SCS')) {
            include('SCS.php');
        }
        $cssContent = SCS::cssContent(); 
        return <<<HTML
.userName {
    color: #cacaca;
}
.newmessage {
    border: 2px solid tomato;
    padding: 5px;
}
#messageBoard {
    padding: 5px;
}
#messageBoard {
    //max-width: 500px;
}
textarea.form-control {
    background: rgba(255,255,255,0.5);
    color: rgba(0,0,0,0.9);
}
.btn-default {
    background: rgba(255,255,255,0.5);
    color: rgba(0,0,0,0.7);
    font-weight: bold;
    margin: 5px;
}
div.message {
    //background: rgba(0,255,155,0.1);
    background: rgba(255,255,255,0.2);
    color: rgba(0,0,0,0.9);
    border-radius: 5px;
}
div.user {
    color: rgba(0,0,0,0.3);
}
p.message {
    padding: 5px;
}
$cssContent
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
