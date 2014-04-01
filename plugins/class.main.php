<?php
	class main {
		private $flood_limit = 5; //Number of lines allowed per 10secs
		private $con, $said;

		function __construct($hocbot) {
			$this->hocbot = $hocbot;
			$this->con = $hocbot->get_con();
		}

		function process($nick, $text, $to, $event) {
			if ($event == "PRIVMSG") {
				$this->either($nick, $text, $to);
				if ($nick == $to)
					$this->pm($nick, $text, $to);
				else
					$this->gm($nick, $text, $to);
			} else if ($event == "JOIN") {
				$this->hocbot->send("Welcome, $nick", $to);
				$this->con->raw_logs(3, $nick, '', $to);
			}
		}

		function pm($nick, $text, $to) {
			$this->con->raw_logs(2, $nick, $text);
			/* HELP */
			if (preg_match('/^!help$/Ui', $text, $matches)) {
				$this->hocbot->send("   ----------------- PM -----------------", $to);
				$this->hocbot->send("!help <level id>              Get level help", $to);
				$this->hocbot->send("!tinyurl <url> <channel>      Create tinyurl link and posts to chan", $to);
				$this->hocbot->send("   --------------- EITHER ----------------", $to);
				$this->hocbot->send("!info <user>                  Displays user information", $to);
			}
		}

		function gm($nick, $text, $to) {
			$this->con->raw_logs(1, $nick, $text, $to);

			//Handle replacements
			if (preg_match('/^s\/(.+)\/(.+)\/(.+)(\/)?$/Ui', $text, $matches)) {
				$str = $this->said[$to][$matches[3]];
				$str = preg_replace("/".$matches[1]. "/i", $matches[2], $str);
				$this->hocbot->send($str, $to);
			} else if (preg_match('/^s\/(.+)\/(.+)(\/)?$/Ui', $text, $matches)) {
				$str = $this->said[$to][$nick];
				$str = preg_replace("/".$matches[1]. "/i", $matches[2], $str);
				$this->hocbot->send($str, $to);
			} else if (preg_match('/^ACTION licks UtiBot$/Ui', $text, $matches)) {
				$str = "I love you too";
				$this->hocbot->send($str, $to);
			} else {
				//Keep track of users last statement
				$this->said[$to][$nick] = $text;
			}

			//count
			$this->flood[$nick][$to]++;
		}

		function either($nick, $text, $to) {
			if (preg_match('/^!info (\S*)$/Ui', $text, $matches)) {
				$r = $this->con->get_stats($matches[1]);
				if (mysql_num_rows($r) > 0) {
					$row = mysql_fetch_array($r);
					$this->hocbot->send("User: " . $row['user'] . " | Lines: " . $row['lines'] . " | Words: " . $row['words'] . " | Chars: " . $row['chars'] . "", $to);
				} else
					$this->hocbot->send("User not found", $to);		
			}
		}

		function check() {
			if (isset($this->flood_time) && $this->flood_time < time() - 10) {
				$this->flood_check();
				$this->flood_time = time();
			} else if (!isset($this->flood_time)) {
				$this->flood_time = time();
			}			
		}

		function flood_check() {
			if (is_array($this->flood))
				foreach ($this->flood as $key => $value)
					foreach ($value as $key2 => $value2)
						if ($value2 > $this->flood_limit)
							$this->kick($key, $key2, "No flooding");
			$this->flood_clear();
		}

		function flood_clear() {
			$this->flood = array();
		}

		function kick($user, $to, $msg = "") {
			$this->hocbot->send_raw("KICK $to $user $msg");
		}
	}
?>
