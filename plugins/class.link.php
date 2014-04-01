<?php
	class main {
		private $con;
	
		function __construct($hocbot) {
			$this->hocbot = $hocbot;
			$this->con = $this->hocbot->get_con();
		}

		function process($nick, $text, $to, $event) {
			if ($event == "PRIVMSG")
				if ($nick == $to)
					$this->pm($nick, $text, $to);
		}

		function pm($nick, $text, $to) {
			if (preg_match('/^!link (\S*)$/Ui', $text, $matches)) {
				$this->hocbot->send("Adding link...".$matches[1], $to);
			}
		}
	}
?>
