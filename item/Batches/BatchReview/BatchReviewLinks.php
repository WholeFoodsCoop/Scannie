<?php
$id = $_GET['id'];
if ($id) {
    $curID = "?id=$id";
} else {
    $curID = '';
}
$curRet = '
<div class="container">
  <ul class="nav nav-tabs" style="boder:1px dotted black">
    <li class="nav-tabs '.get_active_tab($curPage,'BatchReviewPage.php').'">
        <a href="BatchReviewPage.php'.$curID.'">Non-UNFI Review</a></li>
    <li class="nav-tabs '.get_active_tab($curPage,'BatchReviewPageUNFI.php').'">
        <a href="BatchReviewPageUNFI.php'.$curID.'">UNFI Reivew</a></li>
    <li class="nav-tabs '.get_active_tab($curPage,'BatchReviewPageMilk.php').'">
        <a href="BatchReviewPageMilk.php'.$curID.'">UNFI-MILK Review</a></li>
    <li class="nav-tabs '.get_active_tab($curPage,'BatchSaleReviewPage.php').'">
        <a href="BatchSaleReviewPage.php'.$curID.'">Sales Batch Review</a></li>
  </ul>
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

