<?php

include('../dbclient.php');

class genmap extends dbclient {

	protected $db;

	public function getmap( $userids, $cellheight = 10, $cellwidth = 1, $celltime = 120, $cellsperrow = 0, $logbase = 2 ) {

		if ( $cellsperrow == 0 ) {
			$cellsperrow = 86400 / $celltime;
		}
		$rowtime = $cellsperrow * $celltime;

		/* default row time is 1 day */
		/* default sample time per column is 2 minutes */

		$sql = "select $celltime * round(unix_timestamp(min(activitytime))/$celltime), $celltime * round(unix_timestamp(max(activitytime))/$celltime) ";
		$sql .= "from nlogview_activity ";
		$sql .= "where ircuserid in ($userids)";
		$q = $this->db->query($sql);
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }
		$row = $q->fetchrow();
		$unix_begin_time = $row[0];
		$unix_end_time = $row[1];
		$unix_interval_time = $row[1] - $row[0];
		$rowcount = ceil($unix_interval_time / $rowtime);

		$image = imagecreate( $cellsperrow * $cellwidth, $rowcount * $cellheight );
		$blue = imagecolorallocate($image, 0, 0, 255);
		$white = imagecolorallocate($image, 255, 255, 255);
		
		$sql = "select round(log(2,count(activityid)))+1, $celltime * round(unix_timestamp(activitytime) / $celltime) ";
		$sql .= "from nlogview_activity ";
		$sql .= "where ircuserid in ($userids) ";
		$sql .= "group by round(unix_timestamp(activitytime)/$celltime) ";
		$sql .= "order by round(unix_timestamp(activitytime)/$celltime)";
		$q = $this->db->query($sql);
		if (DB::isError($q)) { die("SQL Error: " . $q->getDebugInfo( )); }
		while($row = $q->fetchrow()){
			$index = ($row[1] - $unix_begin_time) / $celltime;
			$x = fmod($index, $cellsperrow);
			$y = floor($index / $cellsperrow);
			$rc = imagefilledrectangle( $image, $x, ($y*$cellheight)-$row[0], $x, ($y*$cellheight)+$row[0] , $white );
		}

		return $image;
	}
}

$mapper = new genmap;
$im = $mapper->getmap($_GET['ids']);
header('Content-type: image/gif');
imagegif($im);
imagedestroy($im);

?>
