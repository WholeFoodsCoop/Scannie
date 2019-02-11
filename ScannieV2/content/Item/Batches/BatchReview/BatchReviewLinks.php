<?php
$id = $_GET['id'];
if ($id) {
    $curID = "?id=$id";
} else {
    $curID = '';
}
$curRet = '
<div class="container" style="padding-top: 25px; padding-bottom: 25px;">
    <nav class="navbar navbar-expand-lg navbar-transparent bg-transparent">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item '.get_active_tab($curPage,'BatchReviewPage.php').'">
                <a class="nav-link" href="BatchReviewPage.php'.$curID.'">Non-UNFI Review</a></li>
            <li class="nav-item '.get_active_tab($curPage,'BatchReviewPageUNFI.php').'">
                <a class="nav-link" href="BatchReviewPageUNFI.php'.$curID.'">UNFI Reivew</a></li>
            <li class="nav-item '.get_active_tab($curPage,'BatchReviewPageMilk.php').'">
                <a class="nav-link" href="BatchReviewPageMilk.php'.$curID.'">UNFI-MILK Review</a></li>
            <li class="nav-item '.get_active_tab($curPage,'BatchSaleReviewPage.php').'">
                <a class="nav-link" href="BatchSaleReviewPage.php'.$curID.'">Sales Batch Review</a></li>
            <li class="nav-item '.get_active_tab($curPage,'WicReviewPage.php').'">
                <a class="nav-link" href="WicReviewPage.php'.$curID.'">Wic Batch Review</a></li>
        </ul>
    </nav>
</div>
';
echo $curRet;

function get_active_tab($curPage,$reqPage)
{
    if ($curPage === $reqPage) { 
        return 'active';
    } else {
        return '';
    }
}

