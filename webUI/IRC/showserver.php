<?php
require('Server.php');
$thispage = new Server($_GET['serverid']);
$thispage->printHeader();

if (!isset($_GET['action'])) {
	$_GET['action'] = "";
}


switch ( $_GET['action'] ) {
case "showchannels" :
	$channels = $thispage->getChannels();
	echo "<table>";
	foreach( $channels as $chan ) {
		echo "<tr><td><a href=showchannel.php?channelid=" . $chan['id'] . ">" . $chan['name'] . "</a></td></tr>";
	}
	break;
default:
	echo "Hello";
}



$thispage->printFooter();
?>
