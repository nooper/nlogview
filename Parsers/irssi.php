<?php

require('parser.php');

class irssiparser extends parser
{
	/*
	 *  Only handling single channel input files for now
	 */

	const ACT_MSG = 1;
	const ACT_JOIN = 2;
	const ACT_PART = 3;
	const ACT_QUIT = 4;
	const ACT_NICKFROM = 5;
	const ACT_NICKTO = 6;

	private $db;
	private $lineregex = array();
	private $nick2ircuser = array();
	private $nickid = array();
	private $userid = array();
	private $hostid = array();
	private $ircuserid = array();
	private $stranger = array(); //for nicks without user@host
	private $nick2userhost = array(); //used in event_nickchange
	private $logdate;
	private $logid, $serverid, $channelid;

	protected $fmtstrings = array();

	function __construct()
	{
		$t = "(\d{2}:\d{2})"; // time stamp
		$w = "([^ ]+)"; //word
		$this->lineregex["logopen"] = "/^--- Log opened $w $w $w $w $w$/";
		$this->lineregex["logclose"] = "/^--- Log closed/";
		$this->lineregex["join"] = "/^$t -!- $w \[$w@$w\] has joined (#[^ ]+)$/";
		$this->lineregex["quit"] = "/^$t -!- $w \[$w@$w\] has quit/";
		$this->lineregex["part"] = "/^$t -!- $w \[$w@$w\] has left $w/";
		$this->lineregex["nickchange"] = "/^$t -!- $w is now known as $w\n$/";
		$this->lineregex["msg"] = "/^$t <.$w>/";
		$this->lineregex["daychange"] = "/--- Day changed $w $w $w $w$/";
	}

	public function addInput( $filename, $formatstring = '' )
	{
		$this->fmtstrings[$filename] = $formatstring;
		$this->inputs[] = $filename;
	}

	private function addChannel($serverid, $channelname)
	{
		$q = $this->db->query('SELECT channelid from nlogview_channels where serverid=? and name= ?', array($serverid, $channelname));
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }

		if($q->numrows() == 1)
		{
			$row = $q->fetchrow();
			return $row[0];
		}
		elseif($q->numrows() == 0)
		{
			$q = $this->db->query('INSERT INTO nlogview_channels(serverid, name) values(?,?)', array($serverid, $channelname));
			if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }
			return $this->addChannel($serverid, $channelname);
		}
		else
		{
			return 0;
		}
	}

	private function readIrssiLog($path, $logid, $channelID)
	{
		$havechannel = 0; //channel name is unknown when starting the read
		//message
		//everything else
	}

	private function addLogRecord($name, $path)
	{
		$q = $this->db->query("INSERT INTO nlogview_logs(name, source) values(?,?)", array($name, $path));
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }
		$row = $this->db->query("SELECT max(logid) from nlogview_logs")->fetchrow();
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }
		return $row[0];
	}

	private function strtime( $t )
	{
		$fulltime = strtotime( "$this->logdate $t");
		return strftime('%F %T', $fulltime);
	}

	private function insertActivity($ircuserid, $type, $time)
	{
		$q = $this->db->query('INSERT INTO nlogview_activity( channelid, ircuserid, logid, activitytype, activitytime ) VALUES(?,?,?,?,?)',
			array($this->channelid, $ircuserid, $this->logid, $type, $time));
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo()); }
	}

	private function selectInsert($tablename, $idcolname, $valuecolname, $value)
	{
		$retval = 0;
		$sql = "SELECT $idcolname FROM $tablename WHERE " . $valuecolname . " = ?";
		$q = $this->db->query($sql, array($value));
		if (DB::isError($q)) { die("SELECT error $sql, $valuecolname" ); }
		if( $q->numrows() == 1 )
		{
			$row = $q->fetchrow();
			$retval = $row[0];
		}
		elseif( $q->numrows() == 0 )
		{
			$sql = "INSERT INTO $tablename($valuecolname) values(?)";
			$q = $this->db->query($sql, array($value));
			if (DB::isError($q)) { die("INSERT error $q->getDebugInfo()" ); }
			return $this->selectInsert($tablename, $idcolname, $valuecolname, $value);
		}
		else
		{
			print "NUMROWS: " . $q->numrows() . " for $value in $tablename<br>\n";
		}
		return $retval;
	}

	private function getNickID( $nick )
	{
		$nickid = 0;
		if( array_key_exists($nick, $this->nickid) )
		{
			$nickid = $this->nickid[$nick];
		}
		else
		{
			$nickid = $this->selectInsert("nlogview_nicks", "nickid", "name", $nick);
			$this->nickid[$nick] = $nickid;
		}
		return $nickid;
	}

	private function getUserID( $user )
	{
		$userid = 0;
		if( array_key_exists($user, $this->userid) )
		{
			$userid = $this->userid[$user];
		}
		else
		{
			$userid = $this->selectInsert("nlogview_users", "userid", "name", $user);
			$this->userid[$user] = $userid;
		}
		return $userid;
	}

	private function getHostID( $host )
	{
		$hostid = 0;
		if( array_key_exists($host, $this->hostid) )
		{
			$hostid = $this->hostid[$host];
		}
		else
		{
			$hostid = $this->selectInsert("nlogview_hosts", "hostid", "name", $host);
			$this->hostid[$host] = $hostid;
		}
		return $hostid;
	}

	private function getIRCUserID( $nickid, $userid, $hostid )
	{
		$lookup = "$nickid,$userid,$hostid";
		$ircuserid = 0;
		if( array_key_exists($lookup, $this->ircuserid) )
		{
			return $this->ircuserid[$lookup];
		}
		else
		{
			$sql = "SELECT ircuserid FROM nlogview_ircusers WHERE nickid=$nickid AND userid=$userid AND hostid=$hostid";
			$q = $this->db->query($sql);
			if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }
			if( $q->numrows() == 1 )
			{
				$row = $q->fetchrow();
				$this->ircuserid[$lookup] = $row[0];
				return $row[0];
			}
			else
			{
				$q = $this->db->query("INSERT INTO nlogview_ircusers(nickid, userid, hostid) VALUES(?,?,?)", array($nickid, $userid, $hostid));
				if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }
				return $this->getIRCUserID($nickid, $userid, $hostid);
			}
		}
	}

	private function setUser( $ircuserid, $user )
	{
		$q = $this->db->query("UPDATE nlogview_users SET name=? WHERE userid=(SELECT userid FROM nlogview_ircusers where ircuserid=?)",
			array($user, $ircuserid));
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }
	}

	private function setHost( $ircuserid, $host )
	{
		$q = $this->db->query("UPDATE nlogview_hosts set name=? WHERE hostid=(SELECT hostid from nlogview_ircusers where ircuserid=?)",
			array($host, $ircuserid));
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }
	}

	private function retroFixup( $nick, $user, $host )
	{
		/* called from part or quit
		 * if another ircuser exists with this nick user@host then
		 * 	update activity events from fake ircuserid to actual
		 * 	delete fake ircuserid and associated user and host
		 */
		$nickid = $this->getNickID($nick);
		$newuserid = $this->getUserID($user);
		$newhostid = $this->getHostID($host);
		$olduserhost = explode("@", $this->nick2userhost[$nickid]);
		$olduserid = $olduserhost[0];
		$oldhostid = $olduserhost[1];

		//update activity table when ircuserid already exists
		$sql = "UPDATE nlogview_activity act, nlogview_ircusers bad, nlogview_ircusers good ";
		$sql .= "SET act.ircuserid = good.ircuserid ";
		$sql .= "WHERE act.ircuserid = bad.ircuserid AND bad.nickid = good.nickid AND ";
		$sql .= "bad.userid=? AND bad.hostid=? AND good.userid=? AND good.hostid=?";
		$q = $this->db->query($sql, array($olduserid, $oldhostid, $newuserid, $newhostid));
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }

		//delete rows renedered useless by above update
		$sql = "DELETE FROM nlogview_ircusers bad ";
		$sql .= "USING nlogview_ircusers bad, nlogview_ircusers good ";
		$sql .= "WHERE bad.nickid = good.nickid ";
		$sql .= "AND bad.userid=? and bad.hostid=? ";
		$sql .= "AND good.userid=? and good.hostid=? ";
		$q = $this->db->query($sql, array($olduserid, $oldhostid, $newuserid, $newhostid));
		print "$sql WITH $olduserid, $oldhostid, $newuserid, $newhostid. <br>\n";
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }

		$q = $this->db->query("UPDATE nlogview_ircusers SET userid=?, hostid=? WHERE userid=? AND hostid=?",
			array($newuserid, $newhostid, $olduserid, $oldhostid));
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }

		$q = $this->db->query("DELETE FROM nlogview_users where userid=?", array($olduserid));
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }

		$q = $this->db->query("DELETE FROM nlogview_hosts where hostid=?", array($oldhostid));
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }
	}

	private function event_msg( $match )
	{
		$time = $this->strtime( $match[1] );
		$nick = $match[2];
		$ircuserid = 0;
		if( array_key_exists($nick, $this->nick2ircuser) )
		{
			$ircuserid = $this->nick2ircuser[$nick];
		}
		else
		{
			// I dont know his username and hostname. I didn't see him join.
			// Make one up.
			// If I find it later, I'll update the table
			$nickid = $this->getNickID( $nick );
			$userid = $this->getUserID( uniqid("USER") );
			$hostid = $this->getHostID( uniqid("HOST") );
			$ircuserid = $this->getIRCUserID( $nickid, $userid, $hostid );
			$this->nick2ircuser[$nick] = $ircuserid;
			$this->nick2userhost[$nickid] = "$userid@$hostid";
			$this->stranger[$nick] = 1;
		}
		$this->insertActivity( $ircuserid, irssiparser::ACT_MSG, $time);
	}

	private function event_part( $match )
	{
		$time = $this->strtime( $match[1] );
		$nick = $match[2];
		$user = $match[3];
		$host = $match[4];
		$ircuserid = 0;
		$nickid = 0; $userid = 0; $hostid = 0;
		if( array_key_exists($nick, $this->nick2ircuser) )
		{
			if( array_key_exists($nick, $this->stranger) )
			{
				$this->retroFixup( $nick, $user, $host );
				unset( $this->stranger[$nick] );
			}
			$nickid = $this->getNickID($nick);
			$userid = $this->getUserID($user);
			$hostid = $this->getHostID($host);
			unset( $this->nick2ircuser[$nick] );
			unset( $this->nick2userhost[$nickid] );
		}
		else
		{
			$nickid = $this->getNickID($nick);
			$userid = $this->getUserID($user);
			$hostid = $this->getHostID($host);
		}
		$ircuserid = $this->getIRCUserID($nickid, $userid, $hostid);
		$this->insertActivity( $ircuserid, irssiparser::ACT_PART, $time );
	}

	private function event_quit( $match )
	{
		$time = $this->strtime( $match[1] );
		$nick = $match[2];
		$user = $match[3];
		$host = $match[4];
		$ircuserid = 0;
		$nickid = 0; $userid = 0; $hostid = 0;
		if( array_key_exists($nick, $this->nick2ircuser) )
		{
			if( array_key_exists($nick, $this->stranger) )
			{
				$this->retroFixup( $nick, $user, $host );
				unset( $this->stranger[$nick] );
			}
			$nickid = $this->getNickID($nick);
			$userid = $this->getUserID($user);
			$hostid = $this->getHostID($host);
			unset( $this->nick2ircuser[$nick] );
			unset( $this->nick2userhost[$nickid] );
		}
		else
		{
			$nickid = $this->getNickID($nick);
			$userid = $this->getUserID($user);
			$hostid = $this->getHostID($host);
		}
		$ircuserid = $this->getIRCUserID($nickid, $userid, $hostid);
		$this->insertActivity( $ircuserid, irssiparser::ACT_QUIT, $time );
	}

	private function event_nickchange( $match )
	{
		$time = $this->strtime($match[1]);
		$oldnick = $match[2];
		$oldnickid = $this->getNickID($oldnick);
		$newnick = $match[3];
		$newnickid = $this->getNickID($newnick);
		if( array_key_exists($oldnick, $this->stranger) )
		{
			unset($this->stranger[$oldnick]);
			$this->stranger[$newnick] = 1;
		}
		else
		{
			unset($this->stranger, $newnick); // just in case
		}

		$userid = 0;
		$hostid = 0;
		if( array_key_exists($oldnickid, $this->nick2userhost) )
		{
			$userhost = explode("@", $this->nick2userhost[$oldnickid]);
			$userid = $userhost[0];
			$hostid = $userhost[1];
			unset( $this->nick2userhost[$oldnickid] );
			unset( $this->nick2ircuser[$oldnick] );
		}
		else
		{
			$userid = $this->getUserID( uniqid("USER") );
			$hostid = $this->getHostID( uniqid("HOST") );
		}
		$oldircuserid = $this->getIRCUserID($oldnickid, $userid, $hostid);
		$newircuserid = $this->getIRCUserID($newnickid, $userid, $hostid);
		$this->nick2userhost[$newnickid] = "$userid@$hostid";
		$this->nick2ircuser[$newnick] = $newircuserid;
		$this->insertActivity( $oldircuserid, irssiparser::ACT_NICKFROM, $time );
		$this->insertActivity( $newircuserid, irssiparser::ACT_NICKTO, $time );

	}

	private function event_join($match)
	{
		$time = $this->strtime( $match[1] );
		$nickid = $this->getNickID( $match[2] );
		$userid = $this->getUserID( $match[3] );
		$hostid = $this->getHostID( $match[4] );
		$this->nick2userhost[$nickid] = "$userid@$hostid";
		$ircuserid = $this->getIRCUserID( $nickid, $userid, $hostid );
		$this->nick2ircuser[$match[2]] = $ircuserid;
		$this->insertActivity( $ircuserid, irssiparser::ACT_JOIN, $time );
	}

	private function event_logopen( $match )
	{
		$this->nick2ircuser = array();
		$this->stranger = array();
		$this->nick2userhost = array();
		$this->logdate = "$match[3] $match[2] $match[5]";
	}

	private function event_logclose( $match )
	{
		$this->nick2ircuser = array();
		$this->stranger = array();
		$this->nick2userhost = array();
		$this->logdate = "";
	}

	private function event_daychange( $match )
	{
		$this->logdate = "$match[3] $match[2] $match[4]";
	}

	private function singleFileToDB( $path )
	{ // returns channelname
		$this->logid = $this->addLogRecord( $path, $path );
		$filehandle = fopen( $path, "r" );
		while ( !feof( $filehandle ) )
		{
			set_time_limit(30);
			$line = fgets($filehandle);
			print $line . "<br>\n";
			$match = array();
			if( preg_match( $this->lineregex["msg"], $line, $match ) )
			{
				$this->event_msg( $match );
			}
			elseif( preg_match( $this->lineregex["nickchange"], $line, $match ) )
			{
				$this->event_nickchange( $match );
			}
			elseif( preg_match( $this->lineregex["join"], $line, $match ) )
			{
				$this->event_join( $match );
			}
			elseif( preg_match( $this->lineregex["quit"], $line, $match ) )
			{
				$this->event_quit( $match );
			}
			elseif( preg_match( $this->lineregex["part"], $line, $match ) )
			{
				$this->event_part( $match );
			}
			elseif( preg_match( $this->lineregex["logclose"], $line, $match ) )
			{
				$this->event_logclose( $match );
			}
			elseif( preg_match( $this->lineregex["logopen"], $line, $match ) )
			{
				$this->event_logopen( $match );
			}
			elseif( preg_match( $this->lineregex["daychange"], $line, $match ) )
			{
				$this->event_daychange( $match );
			}
			else
			{
				//dunno
			}
		}
		fclose( $filehandle );
	}

	public function writeToDB( $db, $serverid )
	{
		$this->db = $db;
		//$this->db->query("START TRANSACTION");
		$channelname = "newchannel";
		$this->channelid = $this->addChannel( $serverid, $channelname );
		$this->serverid = $serverid;
		foreach($this->inputs as $singlefile)
		{
			$channelname = $this->singleFileToDB( $singlefile );
			//update channelname here
		}
		//$this->db->query("COMMIT");

	}

}

?>