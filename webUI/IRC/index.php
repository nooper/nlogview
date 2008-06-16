<?php

include('IRC.php');

$thispage = new IRC;

$thispage->printHeader();

if(!isset($_GET['action']))
{
	$_GET['action'] = "";
}

switch( $_GET['action'] ) {
case 'addserver':
	getaddserver();

case 'showservers':
	showservers();
	break;

case 'showlogs':
	showlogs();
	break;

case 'addlog':
	addlog();
	break;

case 'logsubmit':
	logsubmit();
	break;

case 'logsubmitlocal':
	logsubmitlocal();
	break;
}






function getaddserver() {
	global $thispage;
	if (isset($_GET['servername']) && isset($_GET['serveraddr']))
	{
		$thispage->addServer( $_GET['servername'], $_GET['serveraddr'] );
	}
	else
	{
		echo "Bad input";
	}
}



function showservers() {
	global $thispage;
	echo "<table border=1><tr><th>Name</th><th>Address</th><th></th></tr>";
	$servers = $thispage->getServers();
	foreach($servers as $row)
	{
		echo "<tr><td><a href=showserver.php?serverid=" . $row['id'] . ">" . $row['name'] . "</td><td>" . $row['address'] . "</td></tr>";
	}
	echo <<<ENDHTML
<br>
	<tr>
		<form method=GET action="?action=addserver">
		<input type=hidden name=action value=addserver>
		<td><input type=text name=servername></td>
		<td><input type=text name=serveraddr></td>
		<td><input type=submit value="Add"></td>
		</form>
	</tr>
ENDHTML;
	echo "</table>";
}





function showlogs(){
	global $thispage;
	echo "<br><a href=\"?action=addlog\">Add log</a><br>";
	$logdata = $thispage->getLogs();
	echo "<br/><table border=1><tr><th>Name</th><th>Source</th><th>Timestamp</th></tr>";
	foreach($logdata as $rowdata)
	{
		echo  "<tr><td><a href='showlog.php?action=getdetail&logid=" . $rowdata['logid'] . "'>" .  $rowdata['name'] . "</a></td><td>" . $rowdata['source'] . "</td><td>" . $rowdata['timestamp'] . "</td></tr>";
	}
	echo "</table>";
}





function addlog() {
	global $thispage;
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



function logsubmit() {
	global $thispage;
	if( isset($_FILES['logfileupload']) &&
		($_FILES['logfileupload']['error'] == UPLOAD_ERR_OK) &&
       		isset($_POST['Name'])	)
	{

		echo $thispage->readLogFile( $_FILES['logfileupload']['tmp_name'], $_FILES['logfileupload']['name'], 'irssi', $_POST['Name'], $_POST['serverid']);
		showlogs();
	}
	else
	{
		echo "Bad submit";
	}
}


function logsubmitlocal() {
	global $thispage;
	$path = $_POST['fullpath'];
	if( file_exists($path) )
	{
		echo $thispage->readLogFile( $path, $path, 'irssi', $_POST['friendlyname'], $_POST['serverid']);
		showlogs();
	}
	else
	{
		echo "$path: File does not exist";
	}
}


$thispage->printFooter();

?>
