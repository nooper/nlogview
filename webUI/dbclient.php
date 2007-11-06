<?php

require_once('DB.php');

class dbclient {
	private $db; 

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

	public function query( $sql, $data = array() ) {
		$q = $this->db->query($sql, $data);
		if (DB::isError($q)) { 
			die("SQL Error: " . $q->getDebugInfo( )); 
		}
		else {
			return $q;
		}
	}
}
?>
