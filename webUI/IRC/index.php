<?php

include('IRC.php');

$thispage = new IRC;
$pagecontent = "";

$thispage->printHeader();

function showLogs($thispage)
{
	$logdata = $thispage->getlogs();
	echo "<br/><table border=1><tr><th>Name</th><th>Source</th><th>Timestamp</th></tr>";
	foreach($logdata as $rowdata)
	{
		echo  "<tr><td><a href='logs.php?logid=" . $rowdata['logid'] . "'>" .  $rowdata['name'] . "</a></td><td>" . $rowdata['source'] . "</td><td>" . $rowdata['timestamp'] . "</td></tr>";
	}
	echo "</table>";


	echo <<<ENDHTML
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
</tr>
</table>
ENDHTML;

}


if(!isset($_GET['action']))
{
	$_GET['action'] = "";
}

if($_GET['action'] == 'showlogs')
{
	showLogs($thispage);
}
elseif($_GET['action'] == 'shownicks')
{
	$nickdata = $thispage->getNicks();
	echo "<br/><table border=1>";
	foreach($nickdata as $nickinfo)
	{
		echo "<tr><td><a href=?action=filter&nickid=" . $nickinfo['id'] . ">" .  $nickinfo['name'] . "</a></td></tr>";
	}
	echo "</table>";
}
elseif($_GET['action'] == 'showusers')
{
	$userdata = $thispage->getUsers();
	echo "<br/><table border=1>";
	foreach($userdata as $userinfo)
	{
		echo "<tr><td><a href=?action=filter&userid=" . $userinfo['id'] . ">" . $userinfo['name'] . "</a></td></tr>";
	}
	echo "</table>";
}
elseif($_GET['action'] == 'showhosts')
{
	$hostdata = $thispage->getHosts();
	echo "<br/><table border=1>";
	foreach($hostdata as $hostinfo)
	{
		echo "<tr><td><a href=?action=filter&hostid=" . $hostinfo['id'] . ">" . $hostinfo['name'] . "</a></td></tr>";
	}
	echo "</table>";
}
elseif($_GET['action'] == 'showircusers')
{
	$userray = $thispage->getIRCUsers();
	echo "<br/><table border=1><tr><th>Nick</th><th>User</th><th>Host</th></tr>";
	foreach($userray as $s)
	{
		echo "<tr>";
		echo "<td><a href=?action=filter&nickid=" . $s['nickid'] . ">" . $s['nickname'] . "</a></td>";
		echo "<td><a href=?action=filter&userid=" . $s['userid'] . ">" . $s['username'] . "</a></td>";
		echo "<td><a href=?action=filter&hostid=" . $s['hostid'] . ">" . $s['hostname'] . "</a></td>";
		echo "</tr>";
	}
	echo "</table>";
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
elseif( $_GET['action'] == 'addserver' )
{
	if (isset($_GET['servername']) && isset($_GET['serveraddr']))
	{
		$thispage->addServer( $_GET['servername'], $_GET['serveraddr'] );
		echo "Added server";
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

	echo "<form method=POST action='?action=genmap'>";

	echo "<table border=1><tr><th></th><th>Nick</th><th>User</th><th>Host</th><th>Activity Count</th></tr>";

	foreach($matches as $u)
	{
		echo "<tr>";
		echo "<td><input type=checkbox name='ircuserid[]' value=" . $u['ircuserid'] . "></td>";
		echo "<td><a href=?action=filter&nickid=" . $u['nickid'] . ">" . $u['nickname'] . "</a></td>";
		echo "<td><a href=?action=filter&userid=" . $u['userid'] . ">" . $u['username'] . "</a></td>";
		echo "<td><a href=?action=filter&hostid=" . $u['hostid'] . ">" . $u['hostname'] . "</a></td>";
		echo "<td>" . $u['count'] . "</td>";
	}

	echo "</table>";
	echo "<select name='maptype'>";
	echo "<option value='activity'>Activity</option>";
	echo "<option value='histogram'>Histogram</option>";
	echo "</select>";

	echo "<input type=submit value='Generate image'></form>";

}
elseif( $_GET['action'] == 'genmap' )
{ // GENERATE USER MAP
	if( isset( $_POST['ircuserid'] ) ) {
		$ids = $_POST['ircuserid'];
		$posted = "";
		foreach($ids as $id){
			$posted .= $id . ",";
		}
		$len = strlen($posted);
		$posted = substr($posted, 0, $len - 1);

		echo "<img src=genmap.php?type=" . $_POST['maptype'] . "&ids=$posted>";
	}
}
elseif( $_GET['action'] == 'showservers' )
{ // SHOW SERVER LIST
	echo "<table border=1><tr><th>Name</th><th>Address</th><th></th></tr>";
	$servers = $thispage->getServers();
	foreach($servers as $row)
	{
		echo "<tr><td>" . $row['name'] . "</td><td>" . $row['address'] . "</td></tr>";
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
elseif( $_GET['action'] == 'search' )
{
	if(!isset($_GET['nickvalue'])) $_GET['nickvalue'] = "";
	if(!isset($_GET['hostvalue'])) $_GET['hostvalue'] = "";
	if(!isset($_GET['uservalue'])) $_GET['uservalue'] = "";
	if(!isset($_GET['nicksearch'])) $_GET['nicksearch'] = "";
	if(!isset($_GET['hostsearch'])) $_GET['hostsearch'] = "";
	if(!isset($_GET['usersearch'])) $_GET['usersearch'] = "";

	echo <<<ENDHTML

	<form method=GET>
	<input type=hidden name=action value=search>
	<table border=0>

	<tr><th>nick</th><th>[</th><th>user</th><th>@</th><th>host</th><th>]</th></tr>
ENDHTML;

	echo "<tr>";
	echo "<td align=middle><select name=nicksearch><option value=is ";
	if($_GET['nicksearch'] == 'is') echo "selected";
	echo ">is</option><option value=like ";
	if($_GET['nicksearch'] == 'like') echo "selected";
	echo ">like</option></td>";
	echo "<td></td>";
	echo "<td align=middle><select name=usersearch ";
	if($_GET['usersearch'] == 'is') echo "selected";
	echo "><option value=is>is</option><option value=like ";
	if($_GET['usersearch'] == 'like') echo "selected";
	echo ">like</option></td>";
	echo "<td></td>";
	echo "<td align=middle><select name=hostsearch><option value=is ";
	if($_GET['hostsearch'] == 'is') echo "selected";
	echo ">is</option><option value=like ";
	if($_GET['hostsearch'] == 'like') echo "selected";
	echo ">like</option></td>";
	echo "<td></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td><input type=textbox name=nickvalue value={$_GET['nickvalue']}></td>";
	echo "<td>[</td>";
	echo "<td><input type=textbox name=uservalue value={$_GET['uservalue']}></td>";
	echo "<td>@</td>";
	echo "<td><input type=texbox name=hostvalue value={$_GET['hostvalue']}></td>";
	echo "<td>]</td>";
	echo "</tr>";

	echo "</table>";


	echo "<input type=submit value=Search>";
	echo "</form>";

	if( ($_GET['nickvalue'] != '') || ($_GET['uservalue'] != '') || ($_GET['hostvalue'] != '') )
	{
		$matches = $thispage->filterByName( $_GET['nicksearch'], $_GET['usersearch'], $_GET['hostsearch'],
			$_GET['nickvalue'], $_GET['uservalue'], $_GET['hostvalue'] );

		echo "<form method=POST action='?action=genmap'>";

		echo "<table border=1><tr><th></th><th>Nick</th><th>User</th><th>Host</th><th>Activity Count</th></tr>";

		foreach($matches as $u)
		{
			echo "<tr>";
			echo "<td><input type=checkbox name='ircuserid[]' value=" . $u['ircuserid'] . "></td>";
			echo "<td><a href=?action=filter&nickid=" . $u['nickid'] . ">" . $u['nickname'] . "</a></td>";
			echo "<td><a href=?action=filter&userid=" . $u['userid'] . ">" . $u['username'] . "</a></td>";
			echo "<td><a href=?action=filter&hostid=" . $u['hostid'] . ">" . $u['hostname'] . "</a></td>";
			echo "<td>" . $u['count'] . "</td>";
		}

		echo "</table>";
		echo "<select name='maptype'>";
		echo "<option value='activity'>Activity</option>";
		echo "<option value='histogram'>Histogram</option>";
		echo "</select>";

		echo "<input type=submit value='Generate image'></form>";
	}

}
else
{
	//$pagecontent .= "What are you doing here?";
}

$thispage->printFooter();

?>
