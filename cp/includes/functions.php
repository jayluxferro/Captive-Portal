<?php
	require "dbconfig.php";
	class CaptivePortal{

		private $con;
		
		function __construct(){
			$this->con = new PDO("mysql:host=".HOST.";dbname=".DB_NAME."",DB_USERNAME,DB_PASSWORD);
			$this->con->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		}
	}
?>