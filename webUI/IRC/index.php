<?php

include('IRC.php');

$thispage = new IRC;

$thispage->printHeader();

if(!isset($_GET['action']))
{
	$_GET['action'] = "";
}

if( $_GET['action'] == 'addserver' )
{
	if (isset($_GET['servername']) && isset($_GET['serveraddr']))
	{
		$thispage->addServer( $_GET['servername'], $_GET['serveraddr'] );
		echo "Added server";
	}
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

		echo "<img src=getimage.php?type=" . $_POST['maptype'] . "&ids=$posted>";
	}
}
elseif( $_GET['action'] == 'showservers' )
{ // SHOW SERVER LIST
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

	<tr><th>nick</th><th>!</th><th>ident</th><th>@</th><th>host</th><th></th></tr>
ENDHTML;

	echo "<tr>";
	echo "<td align=middle><select name=nicksearch><option value=is ";
	if($_GET['nicksearch'] == 'is') echo "selected";
	echo ">is</option><option value=like ";
	if($_GET['nicksearch'] == 'like') echo "selected";
	echo ">like</option></select></td>";
	echo "<td></td>";
	echo "<td align=middle><select name=usersearch ";
	if($_GET['usersearch'] == 'is') echo "selected";
	echo "><option value=is>is</option><option value=like ";
	if($_GET['usersearch'] == 'like') echo "selected";
	echo ">like</option></select></td>";
	echo "<td></td>";
	echo "<td align=middle><select name=hostsearch><option value=is ";
	if($_GET['hostsearch'] == 'is') echo "selected";
	echo ">is</option><option value=like ";
	if($_GET['hostsearch'] == 'like') echo "selected";
	echo ">like</option></select></td>";
	echo "<td></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td><input type=textbox name=nickvalue value={$_GET['nickvalue']}></td>";
	echo "<td>!</td>";
	echo "<td><input type=textbox name=uservalue value={$_GET['uservalue']}></td>";
	echo "<td>@</td>";
	echo "<td><input type=texbox name=hostvalue value={$_GET['hostvalue']}></td>";
	echo "<td></td>";
	echo "</tr>";

	echo "</table>";


	echo "<input type=submit value=Search>";
	echo "</form>";

	if( ($_GET['nickvalue'] != '') || ($_GET['uservalue'] != '') || ($_GET['hostvalue'] != '') )
	{
		$matches = $thispage->filterByName( $_GET['nicksearch'], $_GET['usersearch'], $_GET['hostsearch'],
			$_GET['nickvalue'], $_GET['uservalue'], $_GET['hostvalue'] );

		echo "<form method=POST action='?action=genmap' name='matches'>";

		echo "<table border=1>\n";
		echo "<tr><th><input type=checkbox name=mastercheck onClick='checkAll(document.matches, \"ircuserid[]\", document.matches.mastercheck)'></th>\n";
		echo "<th>Nick</th><th>Ident</th><th>Host</th><th>Activity Count</th></tr>\n";

		foreach($matches as $u)
		{
			echo "<tr>";
			echo "<td><input type=checkbox name='ircuserid[]' value=" . $u['ircuserid'] . "></td>";
			echo "<td><a href=?action=search&nicksearch=is&nickvalue=" . $u['nickname'] . ">" . $u['nickname'] . "</a></td>";
			echo "<td><a href=?action=search&usersearch=is&uservalue=" . $u['username'] . ">" . $u['username'] . "</a></td>";
			echo "<td><a href=?action=search&hostsearch=is&hostvalue=" . $u['hostname'] . ">" . $u['hostname'] . "</a></td>";
			echo "<td>" . $u['count'] . "</td>\n";
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
}

$thispage->printFooter();

?>
