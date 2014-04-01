<?php
	class tinyurl {
		function __construct($hocbot) {
			$this->hocbot = $hocbot;
		}

		function process($nick, $text, $to, $event) {
			if ($event == "PRIVMSG")
				if ($nick == $to)
					$this->pm($nick, $text, $to);
		}

		function pm($nick, $text, $to) {
			/* TINYURL */
			if (preg_match('/^!tinyurl (\S*) (\S*)$/Ui', $text, $matches)) {
				if ((substr($matches[1], 0, 7) != "http://") AND (substr($matches[1], 0, 8) != "https://"))
    				$matches[1] = "http://" . $matches[1];
				$this->hocbot->output("Tinyurl request: " . $matches[1], 1);
				$url = file_get_contents("http://tinyurl.com/api-create.php?url=".$matches[1]);
				$this->hocbot->send($nick . ": " . $url, $matches[2]);
			}
		}
	}
?>
