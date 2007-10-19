<?php

include('IRC.php');

$mapper = new IRC;
if($_GET['type'] == 'activity') {
	$im = $mapper->getActivityMap($_GET['ids']);
}
else {
	$im = $mapper->getHistogram($_GET['ids']);
}
header('Content-type: image/gif');
imagegif($im);
imagedestroy($im);

?>
