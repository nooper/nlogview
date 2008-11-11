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
	global $thispage, $channelid;
	$logdata = $thispage->getLogs();
	echo "<br/><table border=1><tr><th>Name</th><th>Source</th><th>Timestamp</th></tr>";
	foreach($logdata as $rowdata)
	{
		echo  "<tr><td><a href='showlog.php?action=getdetail&logid=" . $rowdata['logid'] . "'>" .  $rowdata['name'] . "</a></td><td>" . $rowdata['source'] . "</td><td>" . $rowdata['timestamp'] . "</td></tr>";
	}
	echo "</table>";
	break;

}

$thispage->printFooter();

?>

