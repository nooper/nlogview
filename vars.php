<?php

class DBclient {
	private $dbhost="localhost";
	private $dbname="nooper";
	private $dbuser="nooper";
	private $dbpass="nothing";
	protected $db; 
	public function __construct()
	{
		$sqlstr = "mysql://{$GLOBALS['dbuser']}:{$GLOBALS['dbpass']}@{$GLOBALS['dbhost']}/{$GLOBALS['dbname']}";
		$this->db = DB::connect($sqlstr);
		if (DB::isError($this->db)) { die("$sqlstr :: Can't connect: " . $this->db->getMessage( )); }
	}
}
?>
