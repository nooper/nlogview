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


	function __construct()
	{
		$t = "(\d{2}:\d{2})"; // time stamp
		$w = "([^ ]+)"; //word
		$this->lineregex["logopen"] = "/^--- Log opened $w $w $w $w $w$/";
		$this->lineregex["logclose"] = "/^--- Log closed/";
		$this->lineregex["join"] = "/^$t -!- $w \[$w@$w\] has joined $w\n$/";
		$this->lineregex["quit"] = "/^$t -!- $w \[$w@$w\] has quit/";
		$this->lineregex["part"] = "/^$t -!- $w \[$w@$w\] has left $w/";
		$this->lineregex["nickchange"] = "/^$t -!- $w is now known as $w\n$/";
		$this->lineregex["msg"] = "/^$t <.$w>/";
		$this->lineregex["emote"] = "/^$t  \* $w/";
		$this->lineregex["daychange"] = "/--- Day changed $w $w $w $w$/";
		$this->connect();
	}

	public function addInput( $fullpath, $shortpath, $friendlyname, $formatstring = '' )
	{
		$this->inputs[] = array( 
			'fullpath' => $fullpath,
			'shortpath' => $shortpath,
			'friendlyname' => $friendlyname,
			'fmt' => $formatstring
		);
	}

	private function addChannel($serverid, $channelname)
	{
		$q = $this->query('SELECT channelid from irc_channels where serverid='
			. $this->quote($serverid, 'integer') . ' and name='
			. $this->quote($channelname, 'text'));

		if($q->numrows() == 1)
		{
			$row = $q->fetchrow();
			return $row[0];
		}
		elseif($q->numrows() == 0)
		{
			$q = $this->exec('INSERT INTO irc_channels(serverid, name) values('
				. $this->quote($serverid, 'integer') . ','
				. $this->quote($channelname, 'text') . ')');
			return $this->addChannel($serverid, $channelname);
		}
		else
		{
			return 0;
		}
	}

	private function addLogRecord($friendlyname, $shortpath)
	{
		$q = $this->exec("INSERT INTO irc_logs(name, source) values("
			. $this->quote($friendlyname, 'text') . ','
			. $this->quote($shortpath, 'text') . ')');
		$row = $this->query("SELECT max(logid) from irc_logs")->fetchrow();
		return $row[0];
	}

	private function strtime( $t )
	{
		$fulltime = strtotime( "$this->logdate $t");
		return strftime('%F %T', $fulltime);
	}

	private function insertActivity($ircuserid, $type, $time)
	{
		$sql = 'INSERT INTO irc_activity( channelid, ircuserid, logid, activitytype, activitytime ) VALUES('
			. $this->quote( $this->channelid, 'integer' ) . ','
			. $this->quote( $ircuserid, 'integer' ) . ','
			. $this->quote( $this->logid, 'integer' ) . ','
			. $this->quote( $type, 'integer' ) . ','
			. $this->quote( $time, 'text' ) . ')';
		$q = $this->exec( $sql );
	}

	private function selectInsert($tablename, $idcolname, $valuecolname, $value)
	{
		$retval = 0;
		$sql = "SELECT $idcolname FROM $tablename WHERE " . $valuecolname . " = " . $this->quote($value, 'text');
		$q = $this->query($sql);
		if( $q->numrows() == 1 )
		{
			$row = $q->fetchrow();
			$retval = $row[0];
		}
		elseif( $q->numrows() == 0 )
		{
			$sql = "INSERT INTO $tablename($valuecolname) values(" . $this->quote($value, 'text') . ")";
			$q = $this->exec($sql);
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
			$nickid = $this->selectInsert("irc_nicks", "nickid", "name", $nick);
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
			$userid = $this->selectInsert("irc_idents", "userid", "name", $user);
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
			$hostid = $this->selectInsert("irc_hosts", "hostid", "name", $host);
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
			$sql = "SELECT ircuserid FROM irc_ircusers WHERE nickid=$nickid AND userid=$userid AND hostid=$hostid";
			$q = $this->query($sql);
			if( $q->numrows() == 1 )
			{
				$row = $q->fetchrow();
				$this->ircuserid[$lookup] = $row[0];
				return $row[0];
			}
			else
			{
				$q = $this->exec("INSERT INTO irc_ircusers(nickid, userid, hostid) VALUES($nickid,$userid,$hostid)");
				return $this->getIRCUserID($nickid, $userid, $hostid);
			}
		}
	}

	private function setUser( $ircuserid, $user )
	{
		$q = $this->exec("UPDATE irc_idents SET name="
			. $this->quote($ircuserid, 'integer') 
			. "  WHERE userid=(SELECT userid FROM irc_ircusers where ircuserid="
			. $this->quote($user, 'text') . ")");
	}

	private function setHost( $ircuserid, $host )
	{
		$q = $this->exec("UPDATE irc_hosts set name=$ircuserid WHERE hostid=(SELECT hostid from irc_ircusers where ircuserid="
			. $this->quote($host, 'text') . ")");
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
		$sql = "UPDATE irc_activity act, irc_ircusers bad, irc_ircusers good ";
		$sql .= "SET act.ircuserid = good.ircuserid ";
		$sql .= "WHERE act.ircuserid = bad.ircuserid AND bad.nickid = good.nickid AND ";
		$sql .= "bad.userid=$olduserid AND bad.hostid=$oldhostid AND good.userid=$newuserid AND good.hostid=$newhostid";
		$q = $this->exec($sql);

		//delete rows renedered useless by above update
		$sql = "DELETE FROM irc_ircusers bad ";
		$sql .= "USING irc_ircusers bad, irc_ircusers good ";
		$sql .= "WHERE bad.nickid = good.nickid ";
		$sql .= "AND bad.userid=$olduserid and bad.hostid=$oldhostid ";
		$sql .= "AND good.userid=$newuserid and good.hostid=$newhostid ";
		$q = $this->exec($sql);

		$q = $this->exec("UPDATE irc_ircusers SET userid=$newuserid, hostid=$newhostid WHERE userid=$olduserid AND hostid=$oldhostid");

		$q = $this->exec("DELETE FROM irc_idents where userid=$olduserid");

		$q = $this->exec("DELETE FROM irc_hosts where hostid=$oldhostid");
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
		$channel = $match[5];
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
		return $channel;
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
			unset($this->stranger[$newnick]); // just in case
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
		$channel = $match[5];
		// in a netsplit, irssi doesn't log everyone who quits. So every join should assume user alredy exists in our caches
		unset($this->stranger[$match[2]]);
		$this->nick2userhost[$nickid] = "$userid@$hostid";
		$ircuserid = $this->getIRCUserID( $nickid, $userid, $hostid );
		$this->nick2ircuser[$match[2]] = $ircuserid;
		$this->insertActivity( $ircuserid, irssiparser::ACT_JOIN, $time );
		return $channel;
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

	private function setChannelName( $channelid, $channelname )
	{
		$sql = "SELECT old.channelid ";
		$sql .= "FROM irc_channels old ";
		$sql .= "INNER JOIN irc_channels new ON new.channelid=$channelid AND new.channelid <> old.channelid ";
		$sql .= "WHERE old.name=" . $this->quote($channelname, 'text');
		$q = $this->query($sql);
		if($q->numRows() > 0)
		{
			$row = $q->fetchrow();
			$oldchannelid = $row[0];
			$sql = "UPDATE irc_activity SET channelid = $oldchannelid WHERE channelid = $channelid";
			$q = $this->exec($sql);
			$sql = "DELETE FROM irc_channels WHERE channelid = $channelid";
			$q = $this->exec($sql);
		}
		else
		{
			$sql = "UPDATE irc_channels SET name="
				. $this->quote($channelname, 'text')
				. " WHERE channelid=$channelid";
			$q = $this->exec($sql);
		}
	}

	private function singleFileToDB( $fullpath, $friendlyname, $shortpath )
	{
		$channelname = "";
		$this->channelid = $this->addChannel( $this->serverid, "newchannel" );
		$this->logid = $this->addLogRecord( $friendlyname, $shortpath );
		$filehandle = gzopen( $fullpath, "r" );
		while ( !feof( $filehandle ) )
		{
			set_time_limit(30);
			$line = fgets($filehandle);
			$match = array();
			if( preg_match( $this->lineregex["msg"], $line, $match ) )
			{
				$this->event_msg( $match );
			}
			elseif( preg_match( $this->lineregex["emote"], $line, $match ) )
			{
				$this->event_msg( $match );
			}
			elseif( preg_match( $this->lineregex["nickchange"], $line, $match ) )
			{
				$this->event_nickchange( $match );
			}
			elseif( preg_match( $this->lineregex["join"], $line, $match ) )
			{
				$channelname = $this->event_join( $match );
			}
			elseif( preg_match( $this->lineregex["quit"], $line, $match ) )
			{
				$this->event_quit( $match );
			}
			elseif( preg_match( $this->lineregex["part"], $line, $match ) )
			{
				$channelname = $this->event_part( $match );
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
		gzclose( $filehandle );
		$this->setChannelName( $this->channelid, $channelname );
	}

	public function writeToDB( $serverid )
	{
		$this->db->beginTransaction();
		$this->serverid = $serverid;
		foreach($this->inputs as $singlefile)
		{
			$this->singleFileToDB( $singlefile['fullpath'], $singlefile['friendlyname'], $singlefile['shortpath'] );
		}
		$this->db->commit();
	}
}

?>
