<?php

$imagename = $_GET['type'];

switch ($imagename){

case 'activity':
	include('IRC.php');
	$mapper = new IRC;
	$im = $mapper->getUserActivityMap($_GET['ids']);
	break;

case 'histogram':
	include('IRC.php');
	$mapper = new IRC;
	$im = $mapper->getHistogram($_GET['ids']);
	break;

case 'chandetail':
	include('Channel.php');
	$mapper = new Channel($_GET['ids']);;
	$im = $mapper->getDetailmap();
	break;

}

header('Content-type: image/gif');
imagegif($im);
imagedestroy($im);

?>