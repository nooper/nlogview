<?php
require('../nlogview.php');
require('../Parsers/irssi.php');


class IRC extends nLogView
{
	private $html;
	private $childhtml;
	protected $db;

	public function getContent()
	{
		$myhtml = '
			<table>
			<tr>
			<td>
			<a href="?action=search">Search</a>
			| <a href="?action=showservers">Servers</a>
			| <a href="?action=shownicks">Nicknames</a>
			| <a href="?action=showusers">Users</a>
			| <a href="?action=showhosts">Hosts</a> 
			| <a href="?action=showircusers">IRC Users</a> 
			| <a href="?action=showlogs">Logs</a>
			<tr><td>
			'
			. $this->childhtml .
			'
			</tr></td>
			</td>
			</tr>
			</table>
			';

		parent::addChildContent($myhtml);
		return parent::getContent();
	}

	public function addChildContent($value)
	{
		$this->childhtml .= $value;
	}

	public function getLogs()
	{
		$logdata = array();
		$q = $this->db->query('SELECT * FROM nlogview_logs');
		while($row = $q->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$logdata[] = array(
				'name' => $row['name'],
				'source' => $row['source'],
				'timestamp' => $row['submittime']
			);
		}
		return $logdata;
	}

	public function getNicks()
	{
		$nickdata = array();
		$q = $this->db->query("SELECT * FROM nlogview_nicks");
		while($row = $q->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$nickdata[] = array(
				'id' => $row['nickid'],
				'name' => $row['name']
			);
		}
		return $nickdata;
	}

	public function getUsers()
	{
		$userdata = array();
		$q = $this->db->query("SELECT * from nlogview_users");
		while($row = $q->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$userdata[] = array(
				'id' => $row['userid'],
				'name' => $row['name']
			);
		}
		return $userdata;
	}

	public function getHosts()
	{
		$hostdata = array();
		$q = $this->db->query("SELECT * from nlogview_hosts");
		while($row = $q->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$hostdata[] = array(
				'id' => $row['hostid'],
				'name' => $row['name']
			);
		}
		return $hostdata;
	}

	public function getIRCUsers()
	{
		$ray = array();
		$sql = "select i.ircuserid, nick.nickid, nick.name as nickname, user.userid, user.name as username, host.hostid, host.name as hostname " .
			"from nlogview_ircusers i " .
				"inner join nlogview_nicks nick ON i.nickid = nick.nickid " .
				"inner join nlogview_users user on i.userid = user.userid " .
				"inner join nlogview_hosts host on i.hostid = host.hostid ";
		$q = $this->db->query($sql);
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }
		while($row = $q->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$ray[] = array(
				'ircuserid' => $row['ircuserid'],
				'nickid' => $row['nickid'],
				'nickname' => $row['nickname'],
				'userid' => $row['userid'],
				'username' => $row['username'],
				'hostid' => $row['hostid'],
				'hostname' => $row['hostname']
			);
		}
		return $ray;
	}

	public function getServers()
	{
		$serverdata = array();
		$q = $this->db->query('SELECT * FROM nlogview_servers');
		while($row = $q->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$serverdata[] = array(
				'name' => $row['name'],
				'address' => $row['address'],
				'id' => $row['serverid']
			);
		}
		return $serverdata;
	}

	public function addServer($name, $address)
	{
		$q = $this->db->query('INSERT INTO nlogview_servers (name, address) values (?,?)', array($name, $address));
	}

	public function filterByID($nickid, $userid, $hostid)
	{ // returns array of ircuserids with ircuser data
		$sql = "SELECT n.name nickname, u.name username, h.name hostname, i.ircuserid, i.nickid, i.userid, i.hostid, count(a.activityid) c ";
		$sql .= "FROM nlogview_ircusers i ";
		$sql .= "INNER JOIN nlogview_nicks n ON i.nickid = n.nickid ";
		$sql .= "INNER JOIN nlogview_users u ON i.userid = u.userid ";
		$sql .= "INNER JOIN nlogview_hosts h ON i.hostid = h.hostid ";
		$sql .= "INNER JOIN nlogview_activity a ON i.ircuserid = a.ircuserid ";
		if($nickid > 0)
		{
			$sql .= " WHERE i.nickid = $nickid ";
		}
		if($userid > 0)
		{
			$sql .= " WHERE i.userid = $userid ";
		}
		if($hostid > 0)
		{
			$sql .= " WHERE i.hostid = $hostid ";
		}
		$sql .= "GROUP BY i.ircuserid ";
		$sql .= "ORDER BY count(a.activityid) DESC";

		$q = $this->db->query($sql);
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }
		$retval = array();
		while($row = $q->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$retval[] = array(
				'ircuserid' => $row['ircuserid'],
				'nickid' => $row['nickid'],
				'userid' => $row['userid'],
				'hostid' => $row['hostid'],
				'nickname' => $row['nickname'],
				'username' => $row['username'],
				'hostname' => $row['hostname'],
				'count' => $row['c']
			);
		}
		return $retval;
	}

	public function readLogFile($path, $realname, $type, $name, $serverid)
	{
		//add generic channel name and get ID
		//when channel name is found, update name
		//add to log table


		if($type == 'irssi')
		{
			$parser = new irssiparser;
			$parser->addInput($path, $realname, $name);
			$parser->writeToDB( $this->db, $serverid );
		}
	}

	public function getActivityMap( $userids, $cellheight = 10, $cellwidth = 1, $celltime = 120, $cellsperrow = 0, $logbase = 2 ) {

		if ( $cellsperrow == 0 ) {
			$cellsperrow = 86400 / $celltime;
		}
		$rowtime = $cellsperrow * $celltime;

		/* default row time is 1 day */
		/* default sample time per column is 2 minutes */

		$sql = "select $celltime * round(unix_timestamp(min(activitytime))/$celltime), $celltime * round(unix_timestamp(max(activitytime))/$celltime) ";
		$sql .= "from nlogview_activity ";
		$sql .= "where ircuserid in ($userids)";
		$q = $this->db->query($sql);
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }
		$row = $q->fetchrow();
		$unix_begin_time = $row[0];
		$unix_end_time = $row[1];
		$unix_interval_time = $row[1] - $row[0];
		$rowcount = ceil($unix_interval_time / $rowtime);

		$image = imagecreate( $cellsperrow * $cellwidth, $rowcount * $cellheight );
		$blue = imagecolorallocate($image, 0, 0, 255);
		$white = imagecolorallocate($image, 255, 255, 255);

		$sql = "select round(log($logbase,count(activityid)))+1, $celltime * round(unix_timestamp(activitytime) / $celltime) ";
		$sql .= "from nlogview_activity ";
		$sql .= "where ircuserid in ($userids) ";
		$sql .= "group by round(unix_timestamp(activitytime)/$celltime) ";
		$sql .= "order by round(unix_timestamp(activitytime)/$celltime)";
		$q = $this->db->query($sql);
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }
		while($row = $q->fetchrow()){
			$index = ($row[1] - $unix_begin_time) / $celltime;
			$x = fmod($index, $cellsperrow);
			$y = floor($index / $cellsperrow);
			$rc = imagefilledrectangle( $image, $x, ($y*$cellheight)-$row[0], $x, ($y*$cellheight)+$row[0] , $white );
		}

		return $image;
	}

	public function getHistogram ( $userids, $interval = 3600 ) {
		$sql = "select c, count(c) from ";
		$sql .= "( select count(activityid) c, $interval * round(unix_timestamp(activitytime) / $interval) time ";
		$sql .= "from nlogview_activity ";
		$sql .= "where ircuserid in ( $userids ) ";
		$sql .= "group by round(unix_timestamp(activitytime) / $interval) ) o ";
		$sql .= "group by c ";
		$sql .= "order by c";
		$q = $this->db->query($sql);
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }
		while($row = $q->fetchrow()){
			$lines[$row[0]] = $row[1];
			$total += $row[1];
			$last = $row[0];
		}

		$image = imagecreate( $last * 10, 110 );
		$white = imagecolorallocate($image, 255, 255, 255);
		$red = imagecolorallocate($image, 255, 0, 0);
		$black = imagecolorallocate($image, 0, 0, 0);

		for( $x = 1; $x <= $last; $x++ ) {
			if( isset($lines[$x]) )
				$lines[$x] = round( ($lines[$x] / $total) * 100);
			else
				$lines[$x] = 0;
			$rc = imagefilledrectangle( $image, ($x * 10) - 10, 110, $x * 10, 110 - $lines[$x], $red );
		}

		return $image;
	}
}

?>
