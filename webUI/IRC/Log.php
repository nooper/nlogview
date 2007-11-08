<?php

require('IRC.php');

class Logs extends IRC {

	protected $logid;
	private $mypath;
	private $logData;

	public function __construct( $logid ) {
		parent::__construct();
		$this->logid = $logid;
		$this->mypath = $this->basepath . "logs.php";
		$this->getLogData();
	}

	public function getLogData() {
		if( count($this->logData) == 0 ) {
			$q = $this->query("SELECT * FROM nlogview_logs WHERE logid=?", array($this->logid));
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
		$logdata = $this->getLogData();
		echo "<table><tr><td><b>Logs</b> :: " . $logdata['name'] . "</td>";
		echo "</tr>";
		echo "<tr><td>";
	}

	public function printFooter() {
		echo "</td></tr></table>";
		parent::printFooter();
	}

	public function getMinTime() {
		$q = $this->query("SELECT min(activitytime) FROM nlogview_activity WHERE logid=?", array($this->logid));
		$row = $q->fetchrow();
		return $row[0];
	}

	public function getMaxTime() {
		$q = $this->query("SELECT max(activitytime) FROM nlogview_activity WHERE logid=?", array($this->logid));
		$row = $q->fetchrow();
		return $row[0];
	}

	public function getActivityCount() {
		$q = $this->query("SELECT count(activityid) FROM nlogview_activity WHERE logid=?", array($this->logid));
		$row = $q->fetchrow();
		return $row[0];
	}

	public function getChannelList() {
		$q = $this->query("SELECT DISTINCT a.channelid, c.name FROM nlogview_activity a
			INNER JOIN nlogview_channels c on a.channelid = c.channelid
			WHERE logid=?", array($this->logid));
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
