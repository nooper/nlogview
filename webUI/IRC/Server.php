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
		echo "<table><tr><td><b>Servers</b> :: " . $this->servername . " :: ";
		echo "<a href=" . $this->mypath . "&action=showchannels>Channels</a>";
		echo "</td></tr><tr><td>";
	}

	public function printFooter() {
		echo "</td></tr></table>";
		parent::printFooter();
	}

	private function getServerName() {
		$sql = "SELECT name FROM nlogview_servers WHERE serverid = ";
		$sql .= $this->quote( $this->serverid, 'integer' );
		$q = $this->query( $sql );
		$row = $q->fetchrow();
		return $row[0];
	}

	public function getChannels() {
		$sql = "SELECT channelid, name FROM nlogview_channels WHERE serverid = ";
		$sql .= $this->quote( $this->serverid, 'integer' );
		$q = $this->query( $sql );
		$channels = array();
		while( $row = $q->fetchrow() ) {
			$channels[] = array( 'id' => $row[0],
					'name' => $row[1]
				);
		}
		return $channels;
	}
}
