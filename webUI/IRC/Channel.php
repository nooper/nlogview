<?php

require('Server.php');

class Channel extends Server {

	private $channelid;
	private $serverid;
	private $mypath;
	private $channelname;

	public function __construct( $channelid ) {
		$this->channelid = $channelid;
		$sql = "SELECT serverid, name FROM irc_channels WHERE channelid = ";
		$sql .= $this->quote( $channelid, 'integer' );
		$q = $this->query( $sql );
		$row = $q->fetchrow();
		$this->serverid = $row[0];
		$this->channelname = $row[1];
		parent::__construct( $this->serverid );
		$this->mypath = $this->basepath . "IRC/showchannel.php?channelid=$channelid";
	}

	public function printHeader() {
		parent::printHeader();
		echo "<table><tr><td><b>Channels</b> :: " . $this->channelname;
		echo " :: <a href=\"?channelid=" . $this->channelid . "&action=showmap\">Activity Map</a> | ";
		echo "<a href=\"?channelid=" . $this->channelid . "&action=showlogs\">Logs</a>";
		echo "</td></tr><tr><td>";
	}

	public function printFooter() {
		echo "</td></tr></table>";
		parent::printFooter();
	}

	public function getDetailmap( ) {
		$wherecondition = " WHERE channelid = $this->channelid ";
		$image = $this->getActivityMap( $wherecondition, 3 );
		return $image;
	}

	public function getLogs() {
		$logdata = array();
		$q = $this->query("select distinct l.* from irc_logs l inner join irc_activity a on l.logid = a.logid and a.channelid = $this->channelid");
		while($row = $q->fetchRow(MDB2_FETCHMODE_ASSOC))
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

}

?>
