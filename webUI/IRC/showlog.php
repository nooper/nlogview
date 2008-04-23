<?php
require 'Log.php';

$thispage = new Logs();

$thispage->printHeader();

function showLogs($thispage)
{
	$logdata = $thispage->getlogs();
	echo "<br/><table border=1><tr><th>Name</th><th>Source</th><th>Timestamp</th></tr>";
	foreach($logdata as $rowdata)
	{
		echo  "<tr><td><a href='showlog.php?action=getdetail&logid=" . $rowdata['logid'] . "'>" .  $rowdata['name'] . "</a></td><td>" . $rowdata['source'] . "</td><td>" . $rowdata['timestamp'] . "</td></tr>";
	}
	echo "</table>";



}

if($_GET['action'] == 'showlogs')
{
	showLogs($thispage);
}
elseif($_GET['action'] == 'getdetail')
{
	$logid = $_GET['logid'];

	$logdata = $thispage->getLogData($logid);

	echo <<<ENDHTML
	<br>
	<table>
	<tr><td><b>Start time</b></td><td> {$thispage->getMinTime($logid)} </td></tr>
	<tr><td><b>Stop time</b></td><td> {$thispage->getMaxTime($logid)} </td></tr>
	<tr><td><b>Activity Count:</b></td><td> {$thispage->getActivityCount($logid)} lines </td></tr>
	<tr><td><b>Source:</b></td><td> {$logdata['source']} </td></tr>
	</table>

ENDHTML;
}
elseif($_GET['action'] == 'addlog')
{

	echo <<<ENDHTML
<table border=1>
<tr><td>
<center><b>Upload</b></center>
</td>
<td>
<center><b>Local</b></center>
</td></tr>
<tr>
<td>
<form method=post action="?action=logsubmit" enctype="multipart/form-data">
	Name: <input type=text name="Name">
	<br/>
	Server: <select name="serverid">	
ENDHTML;
	$serverray = $thispage->getServers();
	foreach($serverray as $server)
	{
		$servername = $server['name'];
		$serverid = $server['id'];
		echo "<option value=$serverid>$servername</option>";
	}

	echo <<<ENDHTML
	</select><br/>
	Type:
	<select name="sourcetype">
		<option value="irssi">irssi</option>
		<option value="trillian">trillian</option>
	</select>
	<br/>
	<input type="file" name="logfileupload">
	<br/>
	<input type=submit value="Add log">
</form>
</td>

<td>
<form method=post action="?action=logsubmitlocal">
Name: <input type=text name="friendlyname">
<br/>
Full path: <input type=text name=fullpath><br/>
Server: <select name="serverid">	
ENDHTML;
	$serverray = $thispage->getServers();
	foreach($serverray as $server)
	{
		$servername = $server['name'];
		$serverid = $server['id'];
		echo "<option value=$serverid>$servername</option>";
	}
	echo "</select><br/><input type=submit value=\"Add log\"></form></td></tr></table>";
}
elseif( $_GET['action'] == 'logsubmit' )
{
	if( isset($_FILES['logfileupload']) &&
		($_FILES['logfileupload']['error'] == UPLOAD_ERR_OK) &&
       		isset($_POST['Name'])	)
	{

		echo $thispage->readLogFile( $_FILES['logfileupload']['tmp_name'], $_FILES['logfileupload']['name'], 'irssi', $_POST['Name'], $_POST['serverid']);
		showLogs($thispage);
	}
	else
	{
		echo "Bad submit";
	}
}
elseif( $_GET['action'] == 'logsubmitlocal' )
{
	$path = $_POST['fullpath'];
	if( file_exists($path) )
	{
		echo $thispage->readLogFile( $path, $path, 'irssi', $_POST['friendlyname'], $_POST['serverid']);
		showLogs($thispage);
	}
	else
	{
		echo "$path: File does not exist";
	}
}

$thispage->printFooter();
?>
