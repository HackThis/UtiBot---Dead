<?php
	class main {
		private $num_warnings = 3;
		private $admin;
		private $admin_pass = "password";

		function __construct($hocbot) {
			$this->hocbot = $hocbot;
		}

		function isAdmin($nick) {
			return isset($this->admin[$nick]);
		}

		function process($nick, $text, $to, $event) {
			if ($event == "PRIVMSG") {
				if ($nick == $to && preg_match('/^~auth (\S*)$/Ui', $text, $matches)) {
					if ($matches[1] == $this->admin_pass) {
						$this->admin[$nick] = true;
						$this->hocbot->send("Correct password", $to);
					} else
						$this->hocbot->send("Invalid admin password [".$matches[1]."]", $to);
				} else {	
					//Check if user is admin
					if ($this->isAdmin($nick)) {
						if ($nick == $to)
							$this->pm($nick, $text, $to);
						else
							$this->gm($nick, $text, $to);
					} else if (preg_match('/^~(.*)$/Ui', $text, $matches))
						$this->hocbot->send("Unauthorised request", $nick);
				}
			}
		}

		function pm($nick, $text, $to) {
			/* HELP */
			if (preg_match('/^~help$/Ui', $text, $matches)) {
				$this->hocbot->send("   ----------------- ADMIN ----------------", $to);
				$this->hocbot->send("   ---------------- PLUGINS ----------------", $to);
				$this->hocbot->send("~plugin <name>                Load plugin", $to);
				$this->hocbot->send("~plugin unload <name>         Unload plugin", $to);
				$this->hocbot->send("   --------------- POLICING ----------------", $to);
				$this->hocbot->send("~warn <user>                  Warn user in current channel", $to);
				$this->hocbot->send("~warn <user> <channel> <msg>  Warn user, each user has three strikes", $to);
				$this->hocbot->send("~kick <user> <channel> <msg>  Kick user from a channel", $to);
				$this->hocbot->send("   ----------------- MISC ------------------", $to);
				$this->hocbot->send("~speak <msg> <channel>        Speak on a channel", $to);
				$this->hocbot->send("~join <channel>               Join channel", $to);
				$this->hocbot->send("~part <channel>               Leave channel", $to);
			}
			/* PLUGINS */
			if (preg_match('/^~plugin (\S*)$/Ui', $text, $matches)) {
				$this->hocbot->send("Loading plugin: $matches[1]", $to);
				$this->hocbot->load_plugin($matches[1]);

				$this->hocbot->add_setting("plugin", $matches[1]);
			}
			if (preg_match('/^~plugin unload (\S*)$/Ui', $text, $matches)) {
				$this->hocbot->send("Unloading plugin: $matches[1]", $to);
				$this->hocbot->unload_plugin($matches[1]);
				$this->hocbot->remove_setting("plugin", $matches[1]);
				//$this->hocbot->add_setting("plugin", $matches[1]);
			}
			/* JOIN CHAN */
			if (preg_match('/^~join (\S*)$/Ui', $text, $matches)) {
				$this->hocbot->send("Joining chan: $matches[1]", $to);
				$this->hocbot->join_chan($matches[1]);
				$this->hocbot->add_setting("chan", $matches[1]);
			}
			/* PART CHAN */
			if (preg_match('/^~part (\S*)$/Ui', $text, $matches)) {
				$this->hocbot->send("Leaving chan: $matches[1]", $to);
				$this->hocbot->part_chan($matches[1]);
				$this->hocbot->remove_setting("chan", $matches[1]);
			}
			/* SPEAK */
			if (preg_match('/^~say (.*) (\S*)$/Ui', $text, $matches)) {
				$this->hocbot->send($matches[1], $matches[2]);
			}
			/* WARNING */
			if (preg_match('/^~warn (\S*) (\S*) (.*)$/Ui', $text, $matches)) {
				if ($this->warnings[$matches[1]] < $this->num_warnings) {
					$this->warnings[$matches[1]]++;
					$war = "warnings";
					if (($this->num_warnings - $this->warnings[$matches[1]]) == 1)
						$war = "warning";
					$this->hocbot->send("Warning: $matches[1] - $matches[3] [" . ($this->num_warnings - $this->warnings[$matches[1]]) . " " . $war . " left]", $matches[2]);
				} else
					$this->kick($matches[1], $matches[2]);
			}
		}

		function gm($nick, $text, $to) {
			/* WARNING */
			if (preg_match('/^~warn (\S*)$/Ui', $text, $matches)) {
				if ($this->warnings[$matches[1]] < $this->num_warnings) {
					$this->warnings[$matches[1]]++;
					$war = "warnings";
					if (($this->num_warnings - $this->warnings[$matches[1]]) == 1)
						$war = "warning";
					$this->hocbot->send("Warning: $matches[1] [" . ($this->num_warnings - $this->warnings[$matches[1]]) . " " . $war . " left]", $to);
				} else
					$this->kick($matches[1], $to);
			}
		}

		function kick($user, $to) {
			$this->hocbot->send_raw("KICK $to $user");
		}
	}
?>
