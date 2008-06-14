<?php

error_reporting(E_ALL);

require_once('dbclient.php');

interface webPage{
	public function printHeader();
	public function printFooter();
}

class nLogView extends dbclient implements webPage {
	private $childhtml;
	private $html;
	private $modules = array();
	protected $basepath;

	public function __construct() {
		$this->setBasePath();
	}

	public function printHeader() {
		$modlist = "";
		$this->addModule('IRC');
		foreach($this->modules as $modname){
			$modlist = $modlist . "<td><a href=" . $this->basepath . $modname . "/index.php>" . $modname . "</a></td>";
		}
		echo <<<EOF
			<html>
			<head>
			<title>nLogView</title>
			<script type="text/javascript" src=$this->basepath/general.js></script>
			</head>
			<body>
			<table border=1 width=100% name=wholepage>
			<tr>
			<td>
			<table name=modules>
			<tr>
			<td><b>nLogView</b> :: </td>
EOF;
		echo $modlist;
		echo <<<EOF
			</tr>
			</table>
			</td>
			</tr>
			<tr>
			<td>
EOF;
	}

	public function printFooter() {
		echo <<<EOF
			</td>
			</tr>
			</table>
			</body>
			</html>
EOF;

	}


	private function addModule($modulename){
		$this->modules[] = $modulename;
	}

	protected function setBasePath() {
		if( strlen($this->basepath) == 0 ) {
			$pos = strpos( $_SERVER['PHP_SELF'], "/nlogview/" );
			$this->basepath = substr( $_SERVER['PHP_SELF'], 0, $pos ) . "/nlogview/";
		}
	}


	public function static_get( $key ) {
		$sql = "SELECT value FROM nlogview_static WHERE keyname = " . $this->quote( $key, 'text' );
		$q = $this->query( $sql );
		$row = $q->fetchrow();
		return $row[0];
	}

	public function static_put( $key, $value ) {
		$sql = "INSERT INTO nlogview_static(keyname, value) VALUES( "
			. $this->quote( $key, 'text' ) . ", " . $this->quote( $value, 'text' ) . ")";
		$this->exec($sql);
	}

	public function static_delete( $key ) {
		$sql = "DELETE FROM nlogview_static WHERE keyname = " . $this->quote( $key, 'text' );
		$this->exec( $sql );
	}

}
