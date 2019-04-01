<?php
$curPage = basename($_SERVER['PHP_SELF']);
$id = $_GET['id'];
if ($id) {
    $curID = "?id=$id";
} else {
    $curID = '';
}
$pages = array(
    "Non-UNFI Review" => "BatchReviewPage.php",
    "UNFI Review" => "BatchReviewPageUNFI.php",
    "UNFI-Milk Review" => "BatchReviewPageMilk.php",
    "Sales Batch Review" => "BatchSaleReviewPage.php",
    "Wic Batch Review" => "WicReviewPage.php",
);
$nav = <<<HTML
<div class="container-fluid no-print" style="padding-top: 25px; padding-bottom: 25px;">
    <nav class="navbar navbar-expand-lg navbar-transparent bg-transparent">
        <ul class="navbar-nav mr-auto">
HTML;
foreach ($pages as $name => $path) {
    $active = ($path == $curPage) ? "btn btn-primary btn-sm" : "";
    $nav .= <<<HTML
            <li class="nav-item">
                <a class="nav-link $active" href="$path$curID">$name</a></li>
HTML;
}
$nav .= <<<HTML
        </ul>
    </nav>
</div>
HTML;

echo $nav;
