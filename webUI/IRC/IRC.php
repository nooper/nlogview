<?php
require('../nlogview.php');
require('../Parsers/irssi.php');


class IRC extends nLogView
{
	private $html;
	private $childhtml;
	private $mypath;

	public function printHeader() {
		parent::printHeader();
		$this->mypath = $this->basepath . "IRC/index.php";
		echo <<<EOF
			<table>
			<tr>
			<td>
			<b>IRC</b>
			:: <a href="$this->mypath?action=search">Search</a>
			| <a href="$this->mypath?action=showservers">Servers</a>
			| <a href="$this->basepath/IRC/showlog.php?action=showlogs">Logs</a>
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
		$q = $this->query('SELECT * FROM nlogview_logs ORDER BY submittime DESC');
		while($row = $q->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$logdata[] = array(
				'name' => $row['name'],
				'source' => $row['source'],
				'timestamp' => $row['submittime'],
				'logid' => $row['logid']
			);
		}
		return $logdata;
	}

	public function getServers()
	{
		$serverdata = array();
		$q = $this->query('SELECT * FROM nlogview_servers');
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
		$q = $this->query('INSERT INTO nlogview_servers (name, address) values (?,?)', array($name, $address));
	}

	public function filterByID($nickid = 0, $userid = 0, $hostid = 0)
	{ // returns array of ircuserids with ircuser data
		if( is_numeric($nickid) && is_numeric($userid) && is_numeric($hostid) ) {
			$sql = "SELECT n.name nickname, u.name username, h.name hostname, i.ircuserid, i.nickid, i.userid, i.hostid, count(a.activityid) c ";
			$sql .= "FROM nlogview_ircusers i ";
			$sql .= "INNER JOIN nlogview_nicks n ON i.nickid = n.nickid ";
			$sql .= "INNER JOIN nlogview_idents u ON i.userid = u.userid ";
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
	}

	public function filterByName($nicktype, $usertype, $hosttype, $nickname = '', $username = '', $hostname = '')
	{
		$sql = "SELECT n.name nickname, u.name username, h.name hostname, i.ircuserid, i.nickid, i.userid, i.hostid, count(a.activityid) c ";
		$sql .= "FROM nlogview_ircusers i ";
		$sql .= "INNER JOIN nlogview_nicks n ON i.nickid = n.nickid ";
		$sql .= "INNER JOIN nlogview_idents u ON i.userid = u.userid ";
		$sql .= "INNER JOIN nlogview_hosts h ON i.hostid = h.hostid ";
		$sql .= "INNER JOIN nlogview_activity a ON i.ircuserid = a.ircuserid WHERE ";

		$data = array();

		if( strlen($nickname) > 0 ) {
			if( $nicktype == 'is' ) {
				$filter[] = " n.name = ? ";
			}
			elseif( $nicktype == 'like' ) {
				$filter[] = " n.name like ? ";
			}
			$data[] = $nickname;
		}
		if( strlen($username) > 0 ) {
			if( $usertype == 'is' ) {
				$filter[] = " u.name = ? ";
			}
			elseif( $usertype == 'like' ) {
				$filter[] = " u.name like ? ";
			}
			$data[] = $username;
		}
		if( strlen($hostname) > 0 ) {
			if( $hosttype == 'is' ) {
				$filter[] = " h.name = ? ";
			}
			elseif( $hosttype == 'like' ) {
				$filter[] = " h.name like ? ";
			}
			$data[] = $hostname;
		}
		
		foreach( $filter as $cond ) {
			$sql .= $cond . " AND ";
		}

		$sql = substr( $sql, 0, strlen($sql) - 4 );

		$sql .= "GROUP BY i.ircuserid ";
		$sql .= "ORDER BY count(a.activityid) DESC";

		return $this->filterSQL2Array($sql, $data);
	}

	private function filterSQL2Array( $sql, $data = array() ) {
		$q = $this->query($sql, $data);
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

	public function readLogFile($fullpath, $shortpath, $type, $friendlyname, $serverid)
	{
		//add generic channel name and get ID
		//when channel name is found, update name
		//add to log table


		if($type == 'irssi')
		{
			$parser = new irssiparser;
			$parser->addInput($fullpath, $shortpath, $friendlyname);
			$parser->writeToDB( $serverid );
		}
	}

	private function getMaxFont( $maxheight, $max = 5 ) {
		$cur = $prev = 1;
		for( $cur = $prev; $cur <= $max; $cur++ ) {
			if( imagefontheight($cur) > $maxheight ) {
				return $prev;
			}
			else {
				$prev = $cur;
			}
		}
		return $cur;
	}

	protected function getActivityMap( $wherecondition, $logbase = 2, $cellheight = 11, $cellwidth = 1, $celltime = 120, $cellsperrow = 0 ) {

		if ( $cellsperrow == 0 ) {
			$cellsperrow = 86400 / $celltime; //default to 1 day = 1 row
		}
		$rowtime = $cellsperrow * $celltime;

		/* keep cellheight odd, minimum 9 (lowest font height available) */

		//Get first and last date for image map
		$sql = "select unix_timestamp(min(activitytime)), unix_timestamp(max(activitytime)) ";
		$sql .= "from nlogview_activity ";
		$sql .= $wherecondition;
		$q = $this->query($sql);
		$row = $q->fetchrow();
		$unix_begin_time = gmmktime(0, 0, 0, gmdate('m', $row[0]), gmdate('d', $row[0]), gmdate('Y', $row[0]));
		$unix_end_time = $row[1];
		$rowcount = ceil(($unix_end_time - $unix_begin_time) / $rowtime);
		$imageheight = $rowcount * $cellheight + ($cellheight * 2);

		//get size for date stamps
		$font = $this->getMaxFont( $cellheight );
		$xoffset = imagefontwidth($font) * 12;
		$imagewidth = $xoffset + ($cellsperrow * $cellwidth) + 1;

		//create image, sized according to fetched dates
		$image = imagecreate( $imagewidth, $imageheight );
		$blue = imagecolorallocate($image, 0, 0, 255);
		$white = imagecolorallocate($image, 255, 255, 255);
		$lightblue = imagecolorallocate($image, 50, 50, 255);

		//draw 3 equidistant lines
		$sepwidth = $cellsperrow / 4;
		$lines = 5;
		while( $lines--  )
		{
			imagefilledrectangle ( $image, $xoffset + ($sepwidth * $lines), 0, $xoffset + ($sepwidth * $lines), $imageheight, $lightblue );
		}

		//stamp dates
		$date_y_offset = ( $cellheight - imagefontheight($font) ) / 2;
		$mid = $cellheight / 2;
		$now = $unix_begin_time;
		$firstdate = $now;
		for( $currow = 0; $currow < $rowcount; $currow++) {
			$now = mktime( 0, 0, $currow * $rowtime,
				gmdate("m", $firstdate),
				gmdate("d", $firstdate),
				gmdate("Y", $firstdate)
			);
			imagestring($image, $font, 0, $date_y_offset + ($currow * $cellheight), gmdate("Y-m-d", $now), $white);
		}
		
		$stampstr = "Created " . gmdate("c");
		$stamplen = imagefontwidth($font) * strlen($stampstr);
		imagestring($image, $font, $imagewidth - $stamplen - 1, $imageheight - $cellheight, $stampstr, $white);

		//the actual work
		$sql = "select round(log(?,count(activityid)))+1, ? * round(unix_timestamp(activitytime) / ?) ";
		$sql .= "from nlogview_activity ";
		$sql .= $wherecondition;
		$sql .= "group by round(unix_timestamp(activitytime)/?) ";
		$sql .= "order by round(unix_timestamp(activitytime)/?)";
		$data = array( $logbase, $celltime, $celltime, $celltime, $celltime);
		$q = $this->query($sql, $data);
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

	public function getUserActivityMap( $userids ) {

		// first, ensure input is clean
		$idarray = explode( ",", $userids );
		foreach( $idarray as $tempid ) {
			if ( !is_numeric( $tempid ) ) {
				die("Bad input to IRC::getUserActvitiyMap()");
			}
		}
		$wherecondition = " WHERE ircuserid in ($userids) ";
		$image = $this->getActivityMap( $wherecondition );
		return $image;
	}

	public function getHistogram ( $userids, $interval = 3600 ) {
		$sql = "select c, count(c) from ";
		$sql .= "( select count(activityid) c, ? * round(unix_timestamp(activitytime) / ?) time ";
		$sql .= "from nlogview_activity ";
		$sql .= "where ircuserid in ( ? ) ";
		$sql .= "group by round(unix_timestamp(activitytime) / ?) ) o ";
		$sql .= "group by c ";
		$sql .= "order by c";
		$data = array( $interval, $interval, $userids, $interval );
		$q = $this->query($sql, $data);
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
