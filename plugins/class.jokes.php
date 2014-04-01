<?php
	class jokes {
		function __construct($hocbot) {
			$this->hocbot = $hocbot;
			//fetch RSS
			$this->rss = $this->hocbot->rss;
			$this->load_jokes();

			$this->rand_time = rand(1200, 1800);
		}

		function load_jokes() {
			$this->rss->load('http://www.sickipedia.org/feeds/?1262761033.xml');
			$this->jokes = $this->rss->getItems();
		}

		function process($nick, $text, $to, $event) {
			if ($event == "PRIVMSG") {
				if ($to != $nick)
					$this->last[$to] = time();
				if (preg_match('/^!joke (\S*)$/Ui', $text, $matches))
					$this->topic_joke($matches[1], $to);
			}
		}

		function check() {
			if (is_array($this->last)) {
				foreach ($this->last as $chan => &$x) {
					$time = time() - $this->rand_time;
					if ($x < $time) {
						$this->random_joke($chan);
						$x = time();
						$this->rand_time = rand(1200, 1800);
					}
				}
			}
		}

		function random_joke($to) {
			$this->j++;
			$joke = $this->jokes[$this->j]['description'];
			$arr = explode('<br/>',$joke);
			$this->hocbot->send("- Joke #".$this->j." -", $to);
			foreach($arr as $value) {
				$value = str_replace("&#39;", "'", $value);
				if (strlen($value) > 1)
					$this->hocbot->send(trim($value), $to);
			}
			if ($this->j == 45)
				$this->load_jokes();
		}

		/*function parse_joke($code, $to) {
			$n = strpos($code, "<div id='jokes' style='width: 95%; float: left;'>");
			$code = substr($code, $n);
			$this->hocbot->output($code);
			$n = strpos($code, "<td style='color: #000000'>");
			$code = substr($code, $n);
			$n = strpos($code, "</td>");
			$n = $n - strlen("<td style='color: #000000'>");
			$code = substr($code, strlen("<td style='color: #000000'>"), $n);

			$arr = explode('<br />',$code);
			$this->hocbot->send(" ", $to);
			foreach($arr as $value) {
				$value = str_replace("&quot;", "'", $value);
				$value = str_replace("&Acirc;&pound;", "£", $value);
				if (strlen($value) > 1)
					$this->hocbot->send(trim($value), $to);
			}
		}*/
	}
?>
