<?php
require('Server.php');
$thispage = new Server($_GET['serverid']);
$thispage->printHeader();





$thispage->printFooter();
?>
