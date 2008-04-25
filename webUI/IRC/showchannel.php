<?php

require('Channel.php');

$channelid = $_GET['channelid'];
$thispage = new Channel( $channelid );
$thispage->printHeader();

echo "<img src='getimage.php?type=chandetail&ids=$channelid'>";

$thispage->printFooter();

?>

