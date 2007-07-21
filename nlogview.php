<?php
require 'DB.php';
include('vars.php');

interface webPage{
	public function getContent();
	public function addChildContent($value);
}

class nLogView implements webPage{
	private $childhtml;
	private $html;
	private $modules = array();
	protected $db;

	public function __construct()
	{
		$sqlstr = "mysql://{$GLOBALS['dbuser']}:{$GLOBALS['dbpass']}@{$GLOBALS['dbhost']}/{$GLOBALS['dbname']}";
		$this->db = DB::connect($sqlstr);
		if (DB::isError($this->db)) { die("$sqlstr :: Can't connect: " . $this->db->getMessage( )); }
	}

	public function getContent(){
		$modlist = "";
		$this->addModule('IRC');
		foreach($this->modules as $modname){
			$modlist = $modlist . "<td><a href=" . $modname . ">" . $modname . "</a></td>";
		}
		$this->html = "
			<html>
			<head><title>nLogView</title></head>
			<body>
			<table border=1 width=100% name=wholepage>
			<tr>
			<td>
			<table name=modules>
			<tr>
			<td><b>nLogView</b> :: </td>
			"
			. $modlist .
			"
			</tr>
			</table>
			</td>
			</tr>
			<tr>
			<td>
			"
			. $this->childhtml .
			"
			</td>
			</tr>
			</table>
			</body>
			<html>
			";
		return $this->html;
	}

	public function addChildContent($value)
	{
		$this->childhtml .= $value;
	}

	public function addModule($modulename){
		$this->modules[] = $modulename;
	}


}
