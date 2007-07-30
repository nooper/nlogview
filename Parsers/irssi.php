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
	const ACT_NICK = 5;

	private $db;
	private $lineregex = array();
	private $nick2ircuser = array();
	private $nickid = array();
	private $userid = array();
	private $hostid = array();
	private $ircuserid = array();
	private $logdate;
	private $logid, $serverid, $channelid;

	protected $fmtstrings = array();

	function __construct()
	{
		$t = "(\d{2}:\d{2})"; // time stamp
		$w = "([^ ]+)";
		$this->lineregex["logopen"] = "/^--- Log opened $w $w $w $w $w$/";
		$this->lineregex["logclose"] = "/^--- Log closed/";
		$this->lineregex["join"] = "/^$t -!- $w \[$w@$w\] has joined (#[^ ]+)$/";
		$this->lineregex["quit"] = "/^$t -!- $w $w has quit/";
		$this->lineregex["part"] = "/^$t -!- $w $w has left $w/";
		$this->lineregex["nickchange"] = "/^$t -!- $w is now known as $w$/";
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
		if (DB::isError($q)) { die("SQL Error: " . $q->getMessage( )); }

		if($q->numrows() == 1)
		{
			$row = $q->fetchrow();
			return $row[0];
		}
		elseif($q->numrows() == 0)
		{
			$q = $this->db->query('INSERT INTO nlogview_channels(serverid, name) values(?,?)', array($serverid, $channelname));
			if (DB::isError($q)) { die("SQL Error: " . $q->getMessage( )); }
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
		if (DB::isError($q)) { die("SQL Error: " . $q->getMessage( )); }
		$row = $this->db->query("SELECT max(logid) from nlogview_logs")->fetchrow();
		if (DB::isError($q)) { die("SQL Error: " . $q->getMessage( )); }
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
		if (DB::isError($q)) { die("SQL Error: " . $q->getMessage( )); }
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
		elseif( $q->numrows == 0 )
		{
			$sql = "INSERT INTO $tablename($valuecolname) values($value)";
			$q = $this->db->query($sql);
			if (DB::isError($q)) { die("INSERT error $q->getMessage()" ); }
			return $this->selectInsert($tablename, $idcolname, $valuecolname, $value);
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
			if (DB::isError($q)) { die("SQL Error: " . $q->getMessage( )); }
			if( $q->numrows() == 1 )
			{
				$row = $q->fetchrow();
				$this->ircuserid[$lookup] = $row[0];
				return $row[0];
			}
			else
			{
				$q = $this->db->query("INSERT INTO nlogview_ircusers(nickid, userid, hostid) VALUES(?,?,?)", array($nick, $userid, $hostid));
				if (DB::isError($q)) { die("SQL Error: " . $q->getMessage( )); }
				return $this->getIRCUserID($nickid, $userid, $hostid);
			}
		}
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
			$irccuserid = $this->getIRCUserID( $nickid, $userid, $hostid );
		}
		$this->insertActivity( $ircuserid, $this->ACT_MSG, $time);
	}

	private function event_nickchange( $match )
	{
		$time = $this->strtime($match[1]);
		$oldnick = $match[2];
		$newnick = $match[3];
		$ircuserid = $this->nick2ircuser[$oldnick];
		unset($this->nick2ircuser[$oldnick]);
		$this->nick2ircuser[$newnick] = $ircuserid;
		//$this->insertActivity( $ircuserid, 5, 
	}

	private function event_join($match)
	{
		$time = $this->strtime( $match[1] );
		$nickid = $this->getNickID( $match[2] );
		$userid = $this->getUserID( $match[3] );
		$hostid = $this->getHostID( $match[4] );
		$ircuserid = $this->getIRCUserID( $nickid, $userid, $hostid );
		$this->nick2ircuser[$nick] = $ircuserid;
		$this->insertActivity( $ircuserid, $this->ACT_JOIN, $time );
	}

	private function event_logopen( $match )
	{
		$this->logdate = "$match[3] $match[2] $match[5]";
	}

	private function singleFileToDB( $path, $db, $serverid, $channelid )
	{ // returns channelname
		$this->logid = $this->addLogRecord( $path, $path );
		$filehandle = fopen( $path, "r" );
		while ( !feof( $filehandle ) )
		{
			$line = fgets($filehandle);
			$match = array();
			if( preg_match( $this->lineregex["msg"], $line, $match ) )
			{
				$this->event_msg( $match, $serverid, $channelid, $logid );
			}
			elseif( preg_match( $this->lineregex["nickchange"], $line, $match ) )
			{
			}
			elseif( preg_match( $this->lineregex["join"], $line, $match ) )
			{
				$this->event_join( $match );
			}
			elseif( preg_match( $this->lineregex["quit"], $line, $match ) )
			{
			}
			elseif( preg_match( $this->lineregex["part"], $line, $match ) )
			{
			}
			elseif( preg_match( $this->lineregex["logclose"], $line, $match ) )
			{
			}
			elseif( preg_match( $this->lineregex["logopen"], $line, $match ) )
			{
				$this->event_logopen( $match );
			}
			elseif( preg_match( $this->lineregex["daychange"], $line, $match ) )
			{
			}
			else
			{
				//dunno
			}
		}
		$this->logid = 0;
	}

	public function writeToDB( $db, $serverid )
	{
		$this->db = $db;
		$channelname = "newchannel";
		$channelid = $this->addChannel( $serverid, $channelname );
		foreach($this->inputs as $singlefile)
		{
			$channelname = $this->singleFileToDB( $singlefile, $db, $serverid, $channelid );
			//update channelname here
		}

	}

}

?>
