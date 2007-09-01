<?php

include('IRC.php');

$thispage = new IRC;
$pagecontent = "";

if(!isset($_GET['action']))
{
	$_GET['action'] = "";
}

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
elseif($_GET['action'] == 'shownicks')
{
	$nickdata = $thispage->getNicks();
	$pagecontent .= "<br/><table border=1>";
	foreach($nickdata as $nickinfo)
	{
		$pagecontent .= "<tr><td><a href=?action=filter&nickid=" . $nickinfo['id'] . ">" .  $nickinfo['name'] . "</a></td></tr>";
	}
	$pagecontent .= "</table>";
}
elseif($_GET['action'] == 'showusers')
{
	$userdata = $thispage->getUsers();
	$pagecontent .= "<br/><table border=1>";
	foreach($userdata as $userinfo)
	{
		$pagecontent .= "<tr><td><a href=?action=filter&userid=" . $userinfo['id'] . ">" . $userinfo['name'] . "</a></td></tr>";
	}
	$pagecontent .= "</table>";
}
elseif($_GET['action'] == 'showhosts')
{
	$hostdata = $thispage->getHosts();
	$pagecontent .= "<br/><table border=1>";
	foreach($hostdata as $hostinfo)
	{
		$pagecontent .= "<tr><td><a href=?action=filter&hostid=" . $hostinfo['id'] . ">" . $hostinfo['name'] . "</a></td></tr>";
	}
	$pagecontent .= "</table>";
}
elseif($_GET['action'] == 'showircusers')
{
	$userray = $thispage->getIRCUsers();
	$pagecontent .= "<br/><table border=1><tr><th>Nick</th><th>User</th><th>Host</th></tr>";
	foreach($userray as $s)
	{
		$pagecontent .= "<tr>";
		$pagecontent .= "<td><a href=?action=filter&nickid=" . $s['nickid'] . ">" . $s['nickname'] . "</a></td>";
		$pagecontent .= "<td><a href=?action=filter&userid=" . $s['userid'] . ">" . $s['username'] . "</a></td>";
		$pagecontent .= "<td><a href=?action=filter&hostid=" . $s['hostid'] . ">" . $s['hostname'] . "</a></td>";
		$pagecontent .= "</tr>";
	}
	$pagecontent .= "</table>";
}
elseif( $_GET['action'] == 'logsubmit' )
{
	if( isset($_FILES['logfileupload']) &&
		($_FILES['logfileupload']['error'] == UPLOAD_ERR_OK) &&
       		isset($_POST['Name'])	)
	{

		$somestr = $thispage->readLogFile( $_FILES['logfileupload']['tmp_name'], 'irssi', $_POST['Name'], $_POST['serverid']);
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
elseif( $_GET['action'] == 'filter' )
{
	$nickid = 0;
	$userid = 0;
	$hostid = 0;

	if(isset($_GET['nickid']))
	{
		$nickid = $_GET['nickid'];
	}

	if(isset($_GET['userid']))
	{
		$userid = $_GET['userid'];
	}

	if(isset($_GET['hostid']))
	{
		$hostid = $_GET['hostid'];
	}

	$matches = $thispage->filterByID($nickid, $userid, $hostid);

	$pagecontent .= "<table border=1><tr><th>Nick</th><th>User</th><th>Host</th><th>Activity Count</th></tr>";

	foreach($matches as $u)
	{
		$pagecontent .= "<tr>";
		$pagecontent .= "<td><a href=?action=filter&nickid=" . $u['nickid'] . ">" . $u['nickname'] . "</a></td>";
		$pagecontent .= "<td><a href=?action=filter&userid=" . $u['userid'] . ">" . $u['username'] . "</a></td>";
		$pagecontent .= "<td><a href=?action=filter&hostid=" . $u['hostid'] . ">" . $u['hostname'] . "</a></td>";
		$pagecontent .= "<td>" . $u['count'] . "</td>";
	}

	$pagecontent .= "</table>";

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
