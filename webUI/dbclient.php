<?php

require_once('DB.php');

class dbclient {
	private $db; 

	private function connect()
	{
		$dbhost="localhost";
		$dbname="nooper";
		$dbuser="nooper";
		$dbpass="nothing";

		$sqlstr = "mysql://$dbuser:$dbpass@$dbhost/$dbname";
		$this->db = DB::connect($sqlstr);
		if (DB::isError($this->db)) { die("$sqlstr :: Can't connect: " . $this->db->getMessage( )); }
	}

	public function query( $sql, $data = array() ) {
		if(is_null($this->db))
			$this->connect();
		$q = $this->db->query($sql, $data);
		if (DB::isError($q)) { 
			die("SQL Error: " . $q->getDebugInfo( )); 
		}
		else {
			return $q;
		}
	}

	public function unbuffered_query( $sql, $data = array() ) {
		if(is_null($this->db))
			$this->connect();
		$q = mysql_unbuffered_query($sql, $this->db);
		if (DB::isError($q)) { 
			die("SQL Error: " . $q->getDebugInfo( )); 
		}
		else {
			return $q;
		}
	}
}
?>
