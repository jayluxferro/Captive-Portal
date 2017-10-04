<?php
	session_start();
	ob_start("ob_gzhandler");
	include "../ssFiles/includes/ajax.php";
?>