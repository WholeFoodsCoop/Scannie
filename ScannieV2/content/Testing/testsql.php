<?php
include('../../config.php');
if (!class_exists('PageLayoutA')) {
    include(dirname(__FILE__).'/../PageLayoutA.php');
}
if (!class_exists('SQLManager')) {
    include_once(dirname(__FILE__).'/../../../common/sqlconnect/SQLManager.php');
}

class testsql extends PageLayoutA 
{

    public function body_content()
    {
        $ret = '';
        include('../../config.php');

        $type = $_POST['type'];
        $link = $_POST['link'];
        $pw = $_POST['pw'];

        $dbc = new SQLManager($MYHOST, 'pdo_mysql', $MYDB, $MYUSER, $MYPASS);

        if ($type) {
            $args = array($type, $link);
            $prep = $dbc->prepare("INSERT INTO socialmedia (type,link) 
                VALUES (?, ?) ");
            $dbc->execute($prep,$args);
            if ($er = $dbc->error()) {
                echo "<div class='alert alert-danger'>{$er}</div>";
            } else {
                echo "<div class='alert alert-success'>Data Entered</div>";
            }
        }
        $prep = $dbc->prepare("select * from users");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            echo $row[2];
        }
        //id,type,link
        return <<<HTML
<div>
    <form method="post">
        <input name="type" placeholder="type">
        <input name="link" placeholder="link">
        <input name="pw">
        <button type="submit"></button>
    </form>
</div>
HTML;
    }

}
WebDispatch::conditionalExec();
