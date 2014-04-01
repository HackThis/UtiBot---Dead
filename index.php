#!/usr/bin/php
<?php
	empty($_SERVER['SHELL']) && die('shells only please');
	set_time_limit(0);
	ini_set('display_errors', 'on'); 

	include("base/class.connect.php");
	include("base/class.hocbot.php");
	
	$con = new connection();

	$bot = new bot($con);
?>
