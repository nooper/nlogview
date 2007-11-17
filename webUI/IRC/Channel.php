<?php

require('Server.php');

class Channel extends Server {

	private $channelid;
	private $serverid;
	private $mypath;
	private $channelname;

	public function __construct( $channelid ) {
		$this->channelid = $channelid;
		$q = $this->query("SELECT serverid, name FROM nlogview_channels WHERE channelid = ?", array($channelid));
		$row = $q->fetchrow();
		$this->serverid = $row[0];
		$this->channelname = $row[1];
		parent::__construct( $this->serverid );
		$this->mypath = $this->basepath . "IRC/showchannel.php?channelid=$channelid";
	}

	public function printHeader() {
		parent::printHeader();
		echo "<table><tr><td><b>Channels</b> :: $this->channelname :: ";
		echo "</td></tr><tr><td>";
	}

	public function printFooter() {
		echo "</td></tr></table>";
		parent::printFooter();
	}


}

?>
