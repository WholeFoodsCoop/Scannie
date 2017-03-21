<?php
$curRet = '
<div class="container">
  <ul class="nav nav-tabs" style="boder:1px dotted black">
    <li class="nav-tabs '.get_active_tab($curPage,'BatchReviewPage.php').'"><a href="BatchReviewPage.php">Non-UNFI Review</a></li>
    <li class="nav-tabs '.get_active_tab($curPage,'BatchReviewPageUNFI.php').'"><a href="BatchReviewPageUNFI.php">UNFI Reivew</a></li>
    <li class="nav-tabs '.get_active_tab($curPage,'BatchReviewPageMilk.php').'"><a href="BatchReviewPageMilk.php">UNFI-MILK Review</a></li>
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

