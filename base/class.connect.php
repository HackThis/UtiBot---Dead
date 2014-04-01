<?php
	class connection {
		private $db_host = 'localhost';
		private $db_user = '';
		private $db_pass = '';
		private $db_name = '';
		
		private $con;
		
		function __construct() {
			$this->connect();
		}

		function connect() {
			$this->con = mysql_connect($this->db_host, $this->db_user, $this->db_pass) or die ('Error connecting to mysql');
			mysql_select_db($this->db_name);
		}

		function get_setting($setting) {
			$setting = mysql_real_escape_string($setting);
			$result = mysql_query("SELECT value FROM settings WHERE `setting` = '$setting'");
			return $result;
		}

		function add_setting($setting, $value) {
			$setting = mysql_real_escape_string($setting);
			$value = mysql_real_escape_string($value);
			$result = mysql_query("SELECT value FROM settings WHERE `setting` = '$setting' AND `value` = '$value'");
			if (mysql_num_rows($result) == 0)
				mysql_query("INSERT INTO settings VALUES ('$setting', '$value')");
		}

		function remove_setting($setting, $value) {
			$setting = mysql_real_escape_string($setting);
			$value = mysql_real_escape_string($value);
			$result = mysql_query("SELECT value FROM settings WHERE `setting` = '$setting' AND `value` = '$value'");
			if (mysql_num_rows($result) > 0) {
				mysql_query("DELETE FROM settings WHERE `setting`='$setting' AND `value`='$value'");
				echo(mysql_error());
			}
		}

		function raw_logs($action, $user, $log, $chan="") {
			if (!mysql_ping($this->con) ) {
				mysql_close($this->con);
			   $this->connect();
			}
			$user = mysql_real_escape_string($user);
			$log = mysql_real_escape_string($log);
			$chan = mysql_real_escape_string($chan);
			mysql_query("INSERT INTO raw_logs VALUES ('$action', '$user', '$chan', '$log', '".time()."')");
			echo(mysql_error());
		}
		
		function update_stats($user, $l, $w, $c) {
			if (!mysql_ping($this->con) ) {
				mysql_close($this->con);
			   $this->connect();
			}
			//echo "\n$user $l $w $c\n";
			$user = mysql_real_escape_string($user);
			//is user in table
			$r = mysql_query("SELECT user FROM user_stats WHERE user='$user'");
			if (mysql_num_rows($r) > 0)
				$sql = "UPDATE user_stats SET `lines`=`lines`+$l, words=words+$w, chars=chars+$c, `time`=".time()." WHERE user='$user'";
			else
				$sql = "INSERT INTO user_stats VALUES ('$user', $l, $w, $c, ".time().")";
			mysql_query($sql);
			echo(mysql_error());
		}
		
		function current_logs($c, $chan) {
			if (!mysql_ping($this->con) ) {
				mysql_close($this->con);
				$this->connect();
			}

			$sql = "INSERT INTO current_stats (`user_count`, `channel`) VALUES ($c, '$chan')";
			mysql_query($sql);
		}

		function get_stats($user = '.') {
			if (!mysql_ping($this->con) ) {
				mysql_close($this->con);
			   $this->connect();
			}
			$user = mysql_real_escape_string($user);
			$r = mysql_query("SELECT * FROM user_stats WHERE user = '$user' LIMIT 1");
			return $r;
		}
	}
?>
