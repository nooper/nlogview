<?php
require 'dbclient.php';

interface webPage{
	public function printHeader();
	public function printFooter();
}

class nLogView extends dbclient implements webPage {
	private $childhtml;
	private $html;
	private $modules = array();
	protected $db;

	public function printHeader() {
		$modlist = "";
		$this->addModule('IRC');
		foreach($this->modules as $modname){
			$modlist = $modlist . "<td><a href=" . $modname . ">" . $modname . "</a></td>";
		}
		echo <<<EOF
			<html>
			<head><title>nLogView</title></head>
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


}
