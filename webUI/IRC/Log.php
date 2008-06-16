<?php

require('IRC.php');

class Logs extends IRC {

	private $mypath;
	private $logData;
	private $logid;

	public function __construct( $logid ) {
		parent::__construct();
		$this->mypath = $this->basepath . "IRC/showlog.php";
		$this->logid = $logid;
			$sql = "SELECT * FROM nlogview_logs WHERE logid = ";
			$sql .= $this->quote($this->logid, 'integer');
			$q = $this->query( $sql );
			$row = $q->fetchrow();
			$this->logData = array(
				'name' => $row[1],
				'source' => $row[2],
				'timestamp' => $row[3]
			);
	}

	public function getLogData() {
		if( count($this->logData) == 0 ) {
			$sql = "SELECT * FROM nlogview_logs WHERE logid = ";
			$sql .= $this->quote($this->logid, 'integer');
			$q = $this->query( $sql );
			$row = $q->fetchrow();
			$this->logData = array(
				'name' => $row[1],
				'source' => $row[2],
				'timestamp' => $row[3]
			);
		}
		return $this->logData; //reference?
	}

	public function printHeader() {
		parent::printHeader();
		echo "<table><tr><td><b>Logs</b> :: ";
		echo $this->logData['name'];
		echo "</td></tr>";
		echo "<tr><td>";
	}

	public function printFooter() {
		echo "</td></tr></table>";
		parent::printFooter();
	}

	public function getMinTime() {
		$q = $this->query("SELECT min(activitytime) FROM nlogview_activity WHERE logid=" . $this->quote($this->logid, 'integer'));
		$row = $q->fetchrow();
		return $row[0];
	}

	public function getMaxTime() {
		$q = $this->query("SELECT max(activitytime) FROM nlogview_activity WHERE logid=" . $this->quote($this->logid, 'integer'));
		$row = $q->fetchrow();
		return $row[0];
	}

	public function getActivityCount() {
		$q = $this->query("SELECT count(activityid) FROM nlogview_activity WHERE logid=" . $this->quote($this->logid, 'integer'));
		$row = $q->fetchrow();
		return $row[0];
	}

	public function getChannelList() {
		$q = $this->query("SELECT DISTINCT a.channelid, c.name FROM nlogview_activity a
			INNER JOIN nlogview_channels c on a.channelid = c.channelid
			WHERE logid=" . $this->quote($this->logid, 'integer'));
		$channels = array();
		while( $row = $q->fetchrow() ) {
			$channels[] = array(
				'id' => $row[0],
				'name' => $row[1]
			);
		}
		return $channels;
	}
}
