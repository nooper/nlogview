<?php

include('IRC.php');

$thispage = new IRC;
$pagecontent;

if($_GET['action'] == 'showlogs')
{

	$logdata = $thispage->getlogs();
	$pagecontent .= "<br/><table border=1><tr><th>Name</th><th>Source</th><th>Timestamp</th></tr>";
	foreach($logdata as $rowdata)
	{
		$pagecontent .= "<tr><td>" . $rowdata['name'] . "</td><td>" . $rowdata['source'] . "</td><td>" . $rowdata['timestamp'] . "</td></tr>";
	}
	$pagecontent .= "</table>";


	$pagecontent .= <<<EOD
<form method=post action="?action=logsubmit" enctype="multipart/form-data">
	Name: <input type=text name="Name">
	<br/>
	Server: <select name="serverid">	
EOD;
	$serverray = $thispage->getServers();
	foreach($serverray as $server)
	{
		$servername = $server['name'];
		$serverid = $server['id'];
		$pagecontent .= "<option value=$serverid>$servername</option>";
	}

	$pagecontent .= <<<EOD
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
</tr>
</table>
EOD;

}
elseif( $_GET['action'] == 'logsubmit' )
{
	if( isset($_FILES['logfileupload']) &&
		($_FILES['logfileupload']['error'] == UPLOAD_ERR_OK) &&
       		isset($_POST['Name'])	)
	{
		$somestr = $thispage->readLogFile( $_FILES['logfileupload']['tmp_name'], 'irssi', $_POST['name']);
		$pagecontent .= $somestr;
	}
	else
	{
		$pagecontent .= "Bad submit";
	}
}
elseif( $_GET['action'] == 'addserver' )
{
	if (isset($_GET['servername']) && isset($_GET['serveraddr']))
	{
		$thispage->addServer( $_GET['servername'], $_GET['serveraddr'] );
		$pagecontent .= "Added server";
	}
}
else
{ // SHOW SERVER LIST
	$pagecontent .= "<table border=1><tr><th>Name</th><th>Address</th><th></th></tr>";
	$servers = $thispage->getServers();
	foreach($servers as $row)
	{
		$pagecontent .= "<tr><td>" . $row['name'] . "</td><td>" . $row['address'] . "</td></tr>";
	}
	$pagecontent .= <<<ENDHTML
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

	$pagecontent .= "</table>";

		
}

$thispage->addChildContent($pagecontent);

print $thispage->getContent();

?>
