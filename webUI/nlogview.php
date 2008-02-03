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


}
