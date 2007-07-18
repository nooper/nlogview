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
else
{
	$pagecontent .= "<table><tr><th>Name</th><th>Address</th></tr>";
	$servers = $thispage->getServers();
	foreach($servers as $row)
	{
		$pagecontent .= "<tr><td>" . $row['name'] . "</td><td>" . $row['address'] . "</td></tr>";
	}
	$pagecontent .= "</table>";
}

$thispage->addChildContent($pagecontent);

print $thispage->getContent();

?>
