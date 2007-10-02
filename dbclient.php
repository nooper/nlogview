<?php

require 'DB.php';

class dbclient {
	protected $db; 

	public function __construct()
	{
		$dbhost="";
		$dbname="";
		$dbuser="";
		$dbpass="";

		$sqlstr = "mysql://$dbuser:$dbpass@$dbhost/$dbname";
		$this->db = DB::connect($sqlstr);
		if (DB::isError($this->db)) { die("$sqlstr :: Can't connect: " . $this->db->getMessage( )); }
	}
}
?>
