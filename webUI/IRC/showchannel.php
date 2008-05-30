<?php

require('Channel.php');

if (!isset($_GET['action'])) {
	$_GET['action'] = "";
}

$channelid = $_GET['channelid'];
$thispage = new Channel( $channelid );
$thispage->printHeader();

switch ( $_GET['action'] ) {
case "showmap":
	echo "<img src='getimage.php?type=chandetail&ids=$channelid'>";
	break;

case "showlogs":
	break;

}

$thispage->printFooter();

?>

