<?php

include('IRC.php');

$thispage = new IRC;
$pagecontent = "";

function showLogs($thispage)
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
	return $pagecontent;

}


if(!isset($_GET['action']))
{
	$_GET['action'] = "";
}

if($_GET['action'] == 'showlogs')
{
	$pagecontent .= showLogs($thispage);
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

		$somestr = $thispage->readLogFile( $_FILES['logfileupload']['tmp_name'], $_FILES['logfileupload']['name'], 'irssi', $_POST['Name'], $_POST['serverid']);
		$pagecontent .= $somestr;
		$pagecontent .= showLogs($thispage);
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

	$pagecontent .= "<form method=POST action='?action=genmap'>";

	$pagecontent .= "<table border=1><tr><th></th><th>Nick</th><th>User</th><th>Host</th><th>Activity Count</th></tr>";

	foreach($matches as $u)
	{
		$pagecontent .= "<tr>";
		$pagecontent .= "<td><input type=checkbox name='ircuserid[]' value=" . $u['ircuserid'] . "></td>";
		$pagecontent .= "<td><a href=?action=filter&nickid=" . $u['nickid'] . ">" . $u['nickname'] . "</a></td>";
		$pagecontent .= "<td><a href=?action=filter&userid=" . $u['userid'] . ">" . $u['username'] . "</a></td>";
		$pagecontent .= "<td><a href=?action=filter&hostid=" . $u['hostid'] . ">" . $u['hostname'] . "</a></td>";
		$pagecontent .= "<td>" . $u['count'] . "</td>";
	}

	$pagecontent .= "</table>";
	$pagecontent .= "<select name='maptype'>";
	$pagecontent .= "<option value='activity'>Activity</option>";
	$pagecontent .= "<option value='histogram'>Histogram</option>";
	$pagecontent .= "</select>";

	$pagecontent .= "<input type=submit value='Generate image'></form>";

}
elseif( $_GET['action'] == 'genmap' )
{ // GENERATE USER MAP
	$ids = $_POST['ircuserid'];
	$posted = "";
	foreach($ids as $id){
		$posted .= $id . ",";
	}
	$len = strlen($posted);
	$posted = substr($posted, 0, $len - 1);

	$pagecontent .= "<img src=genmap.php?type=" . $_POST['maptype'] . "&ids=$posted>";
}
elseif( $_GET['action'] == 'showservers' )
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
elseif( $_GET['action'] == 'search' )
{

	$pagecontent .= "<form method=GET>";
	$pagecontent .= "<input type=hidden name=action value=search>";

	$pagecontent .= "<table border=0>";

	$pagecontent .= "<tr>";
	$pagecontent .= "<th>nick</th>";
	$pagecontent .= "<th>[</th>";
	$pagecontent .= "<th>user</th>";
	$pagecontent .= "<th>@</th>";
	$pagecontent .= "<th>host</th>";
	$pagecontent .= "<th>]</th>";
	$pagecontent .= "</tr>";


	$pagecontent .= "<tr>";
	$pagecontent .= "<td align=middle><select name=nicksearch><option value=is ";
	if($_GET['nicksearch'] == 'is') $pagecontent .= "selected";
	$pagecontent .= ">is</option><option value=like ";
	if($_GET['nicksearch'] == 'like') $pagecontent .= "selected";
	$pagecontent .= ">like</option></td>";
	$pagecontent .= "<td></td>";
	$pagecontent .= "<td align=middle><select name=usersearch ";
	if($_GET['usersearch'] == 'is') $pagecontent .= "selected";
	$pagecontent .= "><option value=is>is</option><option value=like ";
	if($_GET['usersearch'] == 'like') $pagecontent .= "selected";
	$pagecontent .= ">like</option></td>";
	$pagecontent .= "<td></td>";
	$pagecontent .= "<td align=middle><select name=hostsearch><option value=is ";
	if($_GET['hostsearch'] == 'is') $pagecontent .= "selected";
	$pagecontent .= ">is</option><option value=like ";
	if($_GET['hostsearch'] == 'like') $pagecontent .= "selected";
	$pagecontent .= ">like</option></td>";
	$pagecontent .= "<td></td>";
	$pagecontent .= "</tr>";

	$pagecontent .= "<tr>";
	$pagecontent .= "<td><input type=textbox name=nickvalue value={$_GET['nickvalue']}></td>";
	$pagecontent .= "<td>[</td>";
	$pagecontent .= "<td><input type=textbox name=uservalue value={$_GET['uservalue']}></td>";
	$pagecontent .= "<td>@</td>";
	$pagecontent .= "<td><input type=texbox name=hostvalue value={$_GET['hostvalue']}></td>";
	$pagecontent .= "<td>]</td>";
	$pagecontent .= "</tr>";

	$pagecontent .= "</table>";


	$pagecontent .= "<input type=submit value=Search>";
	$pagecontent .= "</form>";

	if( ($_GET['nickvalue'] != '') || ($_GET['uservalue'] != '') || ($_GET['hostvalue'] != '') )
	{
		$matches = $thispage->filterByName( $_GET['nicksearch'], $_GET['usersearch'], $_GET['hostsearch'],
			$_GET['nickvalue'], $_GET['uservalue'], $_GET['hostvalue'] );

		$pagecontent .= "<form method=POST action='?action=genmap'>";

		$pagecontent .= "<table border=1><tr><th></th><th>Nick</th><th>User</th><th>Host</th><th>Activity Count</th></tr>";

		foreach($matches as $u)
		{
			$pagecontent .= "<tr>";
			$pagecontent .= "<td><input type=checkbox name='ircuserid[]' value=" . $u['ircuserid'] . "></td>";
			$pagecontent .= "<td><a href=?action=filter&nickid=" . $u['nickid'] . ">" . $u['nickname'] . "</a></td>";
			$pagecontent .= "<td><a href=?action=filter&userid=" . $u['userid'] . ">" . $u['username'] . "</a></td>";
			$pagecontent .= "<td><a href=?action=filter&hostid=" . $u['hostid'] . ">" . $u['hostname'] . "</a></td>";
			$pagecontent .= "<td>" . $u['count'] . "</td>";
		}

		$pagecontent .= "</table>";
		$pagecontent .= "<select name='maptype'>";
		$pagecontent .= "<option value='activity'>Activity</option>";
		$pagecontent .= "<option value='histogram'>Histogram</option>";
		$pagecontent .= "</select>";

		$pagecontent .= "<input type=submit value='Generate image'></form>";
	}

}
else
{
	//$pagecontent .= "What are you doing here?";
}

$thispage->addChildContent($pagecontent);

print $thispage->getContent();

?>
