<?php
	include('/home/flabbyrabbit/UtiBot/rss/rss_php.php');
	class bot {
		private $config = array('server' => 'irc.hackthis.co.uk',
								'port' => 6667,
								'nick' => 'UtiBot',
								'name' => 'Uti Bot',
								'pass' => 'pass');

		public $con;
		public $rss;

		function __construct($con) {
			$this->start_time = time();
			$this->con = $con;
			$this->log_name = 'log_'.$this->start_time;

			//Join server
			$this->connect();			

			//Join channels
			$a = $this->con->get_setting("chan");
			while ($row = mysql_fetch_array($a))
				$this->join_chan($row['value']);

			$this->rss = new rss_php;

			//Load plugins
			$a = $this->con->get_setting("plugin");
			while ($row = mysql_fetch_array($a))
				$this->load_plugin($row['value']);

			//Start loop
			$this->main();
		}

		function output($text, $c=0) {
			if ($c > 0)
				echo "\033[3".$c."m";
			echo $text;
			if ($c > 0)
				echo "\033[0m";
			echo "\r\n";
		}

		function connect() {
			$this->output("Connecting..", 2);
			$erno = $errstr = 0;
 			$this->sock = fsockopen($this->config['server'], $this->config['port'], $erno, $errstr, 30);
			if(!$this->sock) die("Could not connect $erno $errstr");
			stream_set_blocking($this->sock,0);

			$this->output("Logging in...", 2);
			fputs($this->sock,'USER '. $this->config['nick'].' hackthis.co.uk '.$this->config['nick'].' :'.$this->config['name']."\r\n");		
			fputs($this->sock,'NICK '. $this->config['nick']."\r\n");
			fputs($this->sock,'PRIVMSG nickserv IDENTIFY ' . $this->config['pass']."\r\n");
		}

		function join_chan($chan) {
			if ($this->sock != false) {
				$this->output("Joining $chan", 2);
				fputs($this->sock,'JOIN ' . $chan."\r\n");
			}
		}

		function part_chan($chan) {
			if ($this->sock != false) {
				$this->output("Leaving $chan", 2);
				fputs($this->sock,'PART ' . $chan."\r\n");
			}
		}

		function main() {
			while (!feof($this->sock)) {
				$data = trim(fgets($this->sock, 2048));
				flush();

				//Play PING PONG with server
				$ex = explode(' ', $data);
				if($ex[0] == 'PING') {
					echo "PONG\n";
					fputs($this->sock,'PONG '.$ex[1]."\r\n");
				} else {
					if ($data != '') {
						$this->output(nl2br($data));
						$this->parse($data);
					}
				}

				if ($this->timer < time() - 2) {
					foreach ($this->plugins as $pluginName => $instance) {
						if (!isset($this->plugins[$pluginName])) continue;
						if (method_exists($instance, "check"))
							$instance->check();
					}
					$this->timer = time();
				}

				usleep(10);
			}
		}

		function parse($data) {
			if (preg_match('/^:(\S*) (\S*) (\S*) :(.*)$/Ui', $data, $matches))
  				list(,$addr, $event, $chan, $text) = $matches;
			else if (preg_match('/^:(\S*) JOIN :(.*)$/Ui',$data,$matches)) {
  				list(,$addr, $chan) = $matches;
				$event = "JOIN";
			} else if (preg_match('/^:(\S*) PART (.*)$/Ui',$data,$matches)) {
  				list(,$addr, $chan) = $matches;
				$event = "PART";
			} else if (preg_match('/^:(\S*) QUIT (.*)$/Ui',$data,$matches)) {
  				list(,$addr, $chan) = $matches;
				$event = "QUIT";
			}
			else
				return;

			@list($nick, $user, $host) = @explode('@',str_replace('!','@',$addr));
			$text = trim($text);

			if ($chan == $this->config['nick'])
				$chan = $nick;

			if (($event == "PRIVMSG" || $event == "JOIN") && $nick != $this->config['nick']) {
				//Pass it on to all plugins
				foreach ($this->plugins as $pluginName => $instance) {
					if (!isset($this->plugins[$pluginName])) continue;
					if (method_exists($this->plugins[$pluginName], "process"))
						$instance->process($nick, $text, $chan, $event);
				}
			}
		}

		function send($msg, $where) {
			$cmd = 'PRIVMSG ' . $where . " :" . $msg;
			fputs($this->sock, $cmd."\r\n");
			$this->output($cmd, 3);
		}

		function send_raw($msg) {
			fputs($this->sock, $msg."\r\n");
			$this->output($msg, 3);
		}

		function load_plugin($name) {
			$name = strtolower($name);
    		$filename = dirname(__FILE__) . "/../plugins/class.$name.php";
    		if (!file_exists($filename)) {
				$this->output("$filename does not exists", 1);
      			return "$filename does not exists";
			}
    		$error = trim(`php -l $filename`);
    		if (stripos($error, 'No syntax errors detected') === FALSE ) {
				$this->output("$error", 1);
      			return $error;
    		}
    		$file = file_get_contents($filename);
    		$rev  = 'plugin'.uniqid();
    		//set fake classname (one replace only)
    		$file = preg_replace("@class\s+(\w+)@i", "class {$rev}", $file,1);
    		$this->output("Loading $name as $rev from $filename", 2);
    		eval('?>'.$file );
			$this->plugins[$name] = new $rev($this);
    		return "$name loaded from $filename as $rev";
		}

		function unload_plugin($name) {
			$name = strtolower($name);
			unset($this->plugins[$name]);
			$this->output("$name unloaded", 2);
			return "$name unloaded";
		}

		function get_setting($setting) {
			$this->con->get_setting($setting);
		}

		function add_setting($setting, $value) {
			$this->con->add_setting($setting, $value);
		}

		function remove_setting($setting, $value) {
			$this->con->remove_setting($setting, $value);
		}

		function get_con() {
			return $this->con;
		}
	}
?>
