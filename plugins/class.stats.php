<?php
	class main {
		private $flood_limit = 5; //Number of lines allowed per 10secs
		private $con;
	
		function __construct($hocbot) {
			$this->hocbot = $hocbot;
			$this->con = $this->hocbot->get_con();
		}

		function process($nick, $text, $to, $event) {
			if ($event == "PRIVMSG" && $nick != $to)
				$this->gm($nick, $text, $to);
		}

		function gm($nick, $text, $chan) {
			$lines = str_replace(array(chr(10), chr(13)), '', $text);
		
			//calc
			$word_count = count(explode(' ', $lines));
			$char_count = strlen($lines);

			//Update global stats
			$this->con->update_stats('.', 1, $word_count, $char_count);
			//Update users stats
			$this->con->update_stats($nick, 1, $word_count, $char_count);
		}
	}
?>
