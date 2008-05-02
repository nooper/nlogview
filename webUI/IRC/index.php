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

$thispage->printFooter();

?>
