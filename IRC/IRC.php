<?php
include('../nlogview.php');

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
			| Nicknames 
			| Users 
			| Hosts 
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

	public function getServers()
	{
		$serverdata = array();
		$q = $this->db->query('SELECT * FROM nlogview_servers');
		while($row = $q->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$serverdata[] = array(
				'name' => $row['name'],
				'address' => $row['address'],
				'id' => $row['id']
			);
		}
		return $serverdata;
	}

	public function addServer($name, $address)
	{
		$q = $this->db->query('INSERT INTO nlogview_servers (name, address) values (?,?)', array($name, $address));
	}

	private function addDBRow($tablename, $name)
	{
		$q = $this->db->query("SELECT * FROM $tablename WHERE name = ?", $name);
		if($q->numrows() == 1)
		{
			$row = $q->fetchrow();
			return $row[0];
		}
		elseif($q->numrows() == 0)
		{
			$q = $this->db->query("INSERT INTO $tablename(name) values(?)", $name);
			return $this->addDBRow($tablename, $name);
		}
		else
		{
			return 0;
		}

	}

	private function addDBIRCUser($nickid, $userid, $hostid)
	{
		$query = "SELECT ircuserid FROM nlogview_ircusers WHERE nickid=$nickid AND userid=$userid AND hostid=$hostid";
		$q = $this->db->query($query);
		if($q->numrows() == 1)
		{
			$row = $q->fetchrow();
			return $row[0];
		}
		elseif($q->numrows() == 0)
		{
			$query = "INSERT INTO nlogview_ircusers(nickid, userid, hostid) VALUES($nickid, $userid, $hostid)";
			$this->db->query($query);
			return $this->addDBIRCUser($nickid, $userid, $hostid);
		}
		else
		{
			return 0;
		}
	}

	private function getIRCID($nick, $user = 'NULL', $host = 'NULL')
	{
	}

	public function readLogFile($path, $type, $name)
	{
		$retstr = "";
		$userids = array();
		$hostids = array();
		$nickids = array();
		$ircuserids = array();
		$nicktoirc = array();
		if($type == 'irssi')
		{
			$logfile = fopen($path, 'r');
			while(!feof($logfile))
			{
				$line = fgets($logfile);
				if(ereg("^[0-9][0-9]:[0-9][0-9]", $line))
				{
					$words = explode(' ', $line);
					if($words[1] == '-!-')
					{
						if( 
							$words[4] == 'has' &&
							(
								($words[5] == 'joined') ||
								($words[5] == 'left') ||
								($words[5] == 'quit')
							)
						)
						{
							$mask = explode('@', $words[3]);
							$userraw = $mask[0];
							$hostraw = $mask[1];

							$user = str_replace('[', '', $userraw);
							$host = str_replace(']', '', $hostraw);
							$nick = $words[2];

							$ircuserid = $this->getIRCID($nick, $user, $host);
						}


							/*
							 *
							 
							$userid = 0;
							$hostid = 0;
							$nickid = 0;

							if( isset($userids[$user]) )
							{
								$userid = $userids[$user];
							}
							else
							{
								$userid = $this->addDBRow("nlogview_users", $user);
								if($userid != 0)
								{
									$userids[$user] = $userid;
								}
								else
								{
									$retstr .= "ERROR with $user";
								}
							}

							if( isset($hostids[$host]) )
							{
								$hostid = $hostids[$host];
							}
							else
							{
								$hostid = $this->addDBRow("nlogview_hosts", $host);
								if($hostid != 0)
								{
									$hostids[$host] = $hostid;
								}
								else
								{
									$retstr .= "ERROR with $host";
								}
							}

							if( isset($nickids[$nick]) )
							{
								$nickid = $nickids[$nick];
							}
							else
							{
								$nickid = $this->addDBRow("nlogview_nicks", $nick);
								if($nickid != 0)
								{
									$nickids[$nick] = $nickid;
								}
								else
								{
									$retstr .= "ERROR with $nick";
								}
							}

							$ircuserstr = "$nickid,$userid,$hostid";
							$ircuserid = 0;
							if( isset($ircuserids[$ircuserstr]) )
							{
								$ircuserid = $ircuserids[$ircuserstr];
								$nicktoirc[$nick] = $ircuserid;
							}
							else
							{
								$ircuserid = $this->addDBIRCUser($nickid, $userid, $hostid);
								if($ircuserid != 0)
								{
									$ircuserids[$ircuserstr] = $ircuserid;
									$nicktoirc[$nick] = $ircuserid;
								}
								else
								{
									$retstr .= "ERROR with $ircuserstr";
								}
							}
						}
							 */
					}
					else
					{
						ereg("^[0-9][0-9]:[0-9][0-9] <.(.*)>", $line, $regs);
						if(isset($nicktoirc[$regs[1]]))
						{
							$curuser = $nicktoirc[$regs[1]];
							$retstr .= "$regs[1] is $curuser<br>";
						}
						else
						{
							$nickid = $this->addDBRow("nlogview_nicks", $regs[1]);
							$userid = 'NULL';
							$hostid = 'NULL';
							$ircuserstr = "$nickid,$userid,$hostid";
						}
					}
				}
			}
		}
		return $retstr;
	}

}

?>
