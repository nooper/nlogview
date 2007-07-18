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
				'address' => $row['address']
			);
		}
		return $serverdata;
	}

	public function readLogFile($path, $type, $name)
	{
		$retstr = "";
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
						}
					}
				}
			}
		}
		return $retstr;
	}

}

?>
