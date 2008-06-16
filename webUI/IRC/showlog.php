<?php
require 'Log.php';

$logid = $_GET['logid'];
$thispage = new Logs($logid);

$thispage->printHeader();


$logdata = $thispage->getLogData();

echo <<<ENDHTML
	<br>
	<table>
	<tr><td><b>Start time</b></td><td> {$thispage->getMinTime()} </td></tr>
	<tr><td><b>Stop time</b></td><td> {$thispage->getMaxTime()} </td></tr>
	<tr><td><b>Activity Count:</b></td><td> {$thispage->getActivityCount()} lines </td></tr>
	<tr><td><b>Source:</b></td><td> {$logdata['source']} </td></tr>
	</table>

ENDHTML;

$thispage->printFooter();
?>
