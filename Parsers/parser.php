<?php

abstract class parser
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
