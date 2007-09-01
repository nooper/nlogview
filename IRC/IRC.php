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
			<a href="">Servers</a>
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

}

?>
