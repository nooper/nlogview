<?php

require_once('../dbclient.php');

abstract class parser extends dbclient
{
	/* Base parser class.
	 * Each parser should extend this
	 */

	protected $input = array();

	public function addInput( $filename )
	{
		$this->input[] = $filename;
	}

}

?>
