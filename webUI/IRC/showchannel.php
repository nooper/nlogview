<?php

require('Channel.php');

$thispage = new Channel( $_GET['channelid'] );
$thispage->printHeader();
$thispage->printFooter();

?>

