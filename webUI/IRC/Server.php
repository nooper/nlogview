<?php

require('IRC.php');

class Server extends IRC {

	private $serverid;
	private $servername;
	private $mypath;

	public function __construct( $serverid ) {
		if( !is_numeric($serverid)) die("Bad serverid");
		parent::__construct();
		$this->serverid = $serverid;
		$this->servername = $this->getServerName();
		$this->mypath = $this->basepath . "IRC/showserver.php?serverid=$serverid";
	}

	public function printHeader() {
		parent::printHeader();
		echo "<table><tr><td><b>Servers</b> :: $this->servername :: ";
		echo "<a href=" . $this->mypath . "&action=showchannels>Channels</a>";
		echo "</td></tr><tr><td>";
	}

	public function printFooter() {
		echo "</td></tr></table>";
		parent::printFooter();
	}

	private function getServerName() {
		$q = $this->query("SELECT name FROM nlogview_servers WHERE serverid = ?", $this->serverid);
		$row = $q->fetchrow();
		return $row[0];
	}

	public function getChannels() {
		$q = $this->query("SELECT channelid, name FROM nlogview_channels WHERE serverid = ?", $this->serverid);
		$channels = array();
		while( $row = $q->fetchrow() ) {
			$channels[] = array( 'id' => $row[0],
					'name' => $row[1]
				);
		}
		return $channels;
	}
}
