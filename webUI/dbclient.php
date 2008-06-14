<?php
require_once('MDB2.php');

class dbclient {
	public $db;

	public function connect()
	{
		$dbhost="localhost";
		$dbname="nlogview";
		$dbuser="nooper";
		$dbpass="nothing";

		$sqlstr = "mysql://$dbuser:$dbpass@$dbhost/$dbname";
		$this->db =& MDB2::connect($sqlstr);
		if (PEAR::isError($this->db)) { die("$sqlstr :: Can't connect: " . $this->db->getMessage( )); }
	}

	public function query( $sql ) {
		if(is_null($this->db))
			$this->connect();
		$q =& $this->db->query($sql);
		if (PEAR::isError($q)) { 
			die("SQL Error: " . $q->getMessage( )); 
		} else {
			return $q;
		}
	}

	public function exec( $sql ) {
		if(is_null($this->db))
			$this->connect();
		$q =& $this->db->exec($sql);
		if (PEAR::isError($q)) { 
			die("SQL Error: " . $q->getMessage( )); 
		} else {
			return $q;
		}
	}

	public function quote( $var, $type ) {
		if( is_null( $this->db ) ) {
			$this->connect();
		}
		return $this->db->quote( $var, $type );
	}

}
?>
