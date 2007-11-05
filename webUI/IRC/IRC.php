<?php
require('../nlogview.php');
require('../Parsers/irssi.php');


class IRC extends nLogView
{
	private $html;
	private $childhtml;
	protected $db;

	public function printHeader() {
		parent::printHeader();
		echo <<<EOF
			<table>
			<tr>
			<td>
			<b>IRC</b>
			:: <a href="?action=search">Search</a>
			| <a href="?action=showservers">Servers</a>
			| <a href="?action=shownicks">Nicknames</a>
			| <a href="?action=showusers">Users</a>
			| <a href="?action=showhosts">Hosts</a> 
			| <a href="?action=showircusers">IRC Users</a> 
			| <a href="?action=showlogs">Logs</a>
			<tr><td>
EOF;

	}

	public function printFooter() {
		echo <<<EOF
			</tr></td>
			</td>
			</tr>
			</table>
EOF;
		parent::printFooter();
	}

	public function getLogs()
	{
		$logdata = array();
		$q = $this->db->query('SELECT * FROM nlogview_logs ORDER BY submittime DESC');
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

	public function filterByID($nickid = 0, $userid = 0, $hostid = 0)
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

		return $this->filterSQL2Array($sql);
	}

	public function filterByName($nicktype, $usertype, $hosttype, $nickname = '', $username = '', $hostname = '')
	{
		$sql = "SELECT n.name nickname, u.name username, h.name hostname, i.ircuserid, i.nickid, i.userid, i.hostid, count(a.activityid) c ";
		$sql .= "FROM nlogview_ircusers i ";
		$sql .= "INNER JOIN nlogview_nicks n ON i.nickid = n.nickid ";
		$sql .= "INNER JOIN nlogview_users u ON i.userid = u.userid ";
		$sql .= "INNER JOIN nlogview_hosts h ON i.hostid = h.hostid ";
		$sql .= "INNER JOIN nlogview_activity a ON i.ircuserid = a.ircuserid WHERE ";

		if( strlen($nickname) > 0 ) {
			if( $nicktype == 'is' )
				$filter[] = " n.name = '$nickname' ";
			elseif( $nicktype == 'like' )
				$filter[] = " n.name like '%$nickname%' ";
		}
		if( strlen($username) > 0 ) {
			if( $usertype == 'is' )
				$filter[] = " u.name = '$username' ";
			elseif( $usertype == 'like' )
				$filter[] = " u.name like '%$username%' ";
		}
		if( strlen($hostname) > 0 ) {
			if( $hosttype == 'is' )
				$filter[] = " h.name = '$hostname' ";
			elseif( $hosttype == 'like' )
				$filter[] = " h.name like '%$hostname%' ";
		}
		
		foreach( $filter as $cond ) {
			$sql .= $cond . " AND ";
		}

		$sql = substr( $sql, 0, strlen($sql) - 4 );

		$sql .= "GROUP BY i.ircuserid ";
		$sql .= "ORDER BY count(a.activityid) DESC";

		return $this->filterSQL2Array($sql);
	}

	private function filterSQL2Array( $sql ) {
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

	private function getMaxFont( $maxheight, $max = 5 ) {
		$cur = $prev = 1;
		for( $cur = $prev; $cur <= max; $cur++ ) {
			if( imagefontheight($cur) > $maxheight ) {
				return $prev;
			}
			else {
				$prev = cur;
			}
		}
		return $cur;
	}

	public function getActivityMap( $userids, $cellheight = 11, $cellwidth = 1, $celltime = 120, $cellsperrow = 0, $logbase = 2 ) {

		if ( $cellsperrow == 0 ) {
			$cellsperrow = 86400 / $celltime; //default to 1 day = 1 row
		}
		$rowtime = $cellsperrow * $celltime;

		/* keep cellheight odd, minimum 9 (lowest font height available) */

		//Get first and last date for image map
		$sql = "select $celltime * round(unix_timestamp(min(activitytime))/$celltime), $celltime * round(unix_timestamp(max(activitytime))/$celltime) ";
		$sql .= "from nlogview_activity ";
		$sql .= "where ircuserid in ($userids)";
		$q = $this->db->query($sql);
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }
		$row = $q->fetchrow();
		$unix_begin_time = mktime(0, 0, 0, date('m', $row[0]), date('d', $row[0]), date('Y', $row[0]));
		$unix_end_time = $row[1];
		$rowcount = ceil(($unix_end_time - $unix_begin_time) / $rowtime);

		//get size for date stamps
		$font = $this->getMaxFont( $cellheight );
		$xoffset = imagefontwidth($font) * 12;

		//create image, sized according to fetched dates
		$image = imagecreate( $xoffset + ($cellsperrow * $cellwidth), $rowcount * $cellheight );
		$blue = imagecolorallocate($image, 0, 0, 255);
		$white = imagecolorallocate($image, 255, 255, 255);

		$sql = "select round(log($logbase,count(activityid)))+1, $celltime * round(unix_timestamp(activitytime) / $celltime) ";
		$sql .= "from nlogview_activity ";
		$sql .= "where ircuserid in ($userids) ";
		$sql .= "group by round(unix_timestamp(activitytime)/$celltime) ";
		$sql .= "order by round(unix_timestamp(activitytime)/$celltime)";
		$q = $this->db->query($sql);
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }
		$mid = $cellheight / 2;
		$now = $unix_begin_time;
		$date_y_offset = ( $cellheight - imagefontheight($font) ) / 2;
		//stamp dates
		for( $currow = 0; $currow < $rowcount; $currow++) {
			imagestring($image, $font, 0, $date_y_offset + ($currow * $cellheight), date("Y-m-d", $now), $white);
			$now += $rowtime;
		}
		while($row = $q->fetchrow()){
			$index = ($row[1] - $unix_begin_time) / $celltime;
			$x = $xoffset + fmod($index, $cellsperrow);
			$x2 = $x * $cellwidth;
			$x1 = $x2 - $cellwidth + 1;
			$y = $mid + (floor($index / $cellsperrow) * $cellheight);
			$y1 = $y - $row[0];
			$y2 = $y + $row[0];
			$rc = imagefilledrectangle( $image, $x1, $y1, $x2, $y2 , $white );
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
