<?php
	require_once(dirname(__FILE__) . '/../Command.php');
	require_once(dirname(__FILE__) . '/../MultiProtocolCommand.php');

	/**
	 * Class responsible for executing a bgp-related command on the router.
	 */
	class BGPCommand extends Command implements MultiProtocolCommand {
		/** BGP Type */
		private $type;

		/** BGP Subcommand. */
		private $subcommand;

		/**
		 * Create a new BGP Command.
		 *
		 * @param $subcommand (Default: 'summary') bgp subcommand to execute %s will
		 *                    be replaced by the first argument.
		 */
		public function __construct($subcommand = 'summary') {
			parent::__construct();
			$this->subcommand = $subcommand;
		}

		/** {@inheritDoc} */
		public function setProtocol($type = 'ipv4') {
			if ($type == 'ipv6') {
				$this->type = 'ipv6';
			} else {
				$this->type = 'ipv4';
			}
		}

		/** {@inheritDoc} */
		public function validateArgs($args) {
			if ($args == '') { return true; }
			if ($this->type == 'ipv6') {
				$validIP = filter_var($args, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
			} else {
				$validIP = filter_var($args, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
			}
			if (!$validIP) { $this->setError('"'.$args.'" is not a valid ' . $this->type . ' address.'); }

			return $validIP;
		}

		/** {@inheritDoc} */
		public function getCommandString($router, $args) {
			$args = explode(' ', $args);
			$args = $args[0];
			$bit = ($this->type == 'ipv6' ? 'ipv6' : 'ip');
			if ($this->subcommand == 'neighbors %s') { $bit = 'ip'; }
			return 'show ' . $bit . ' bgp ' . sprintf($this->subcommand, $args);
		}

		/** {@inheritDoc} */
		public function run($router, $args, $output = true) {
			if (empty($router)) {
				return $this->setError('Invalid Router');
			}
			if ($router->getRouterInfo('nobgp', false)) {
				return $this->setError('BGP lookups are not supported on this router.');
			}

			$args = explode(' ', $args);
			$args = $args[0];
			if (!$this->validateArgs($args)) { return FALSE; }
			if (stripos($this->subcommand, '%s') !== FALSE && empty($args[0])) {
				return $this->setError('This command requires a parameter.');
			}

			$command = $this->getCommandString($router, $args);

			$out = $router->runCommand($command);
			if ($out === false) {
				return $this->setError($router->getError());
			}
			if (!$output) { return $out; }

			echo '<pre>';
			if ($out == '') {
				echo '<em>No data returned.</em>';
			} else {
				$lines = explode("\n", $out);
				$count = 0;

				$ipregex = '(?:(?:[0-9]{0,3}\.){1,3}[0-9]{0,3}|(?:[A-Fa-f0-9]{0,4}:?){0,8}:[A-Fa-f0-9]{1,4})';

				foreach ($lines as $line) {
					$line = htmlspecialchars($line);

					if (isAdmin()) {
						// Add links to neighbor information
						$line = preg_replace('#^('.$ipregex.')#', '<a href="' . getLink('bgp neighbor', '') . '\1">\1</a>', $line);

						$line = preg_replace('#from ('.$ipregex.') \(#', 'from <a href="' . getLink('bgp neighbor', '') . '\1">\1</a> (', $line);
					}

					// Add highlights for bad lines...
					$line = preg_replace('#^(<.*[0-9A-Fa-f]+.* [^0-9]+)$#', '<span class="bad">\1</span>', $line);

					// And Good Lines
					$line = preg_replace('#^ (.*, best.*)$#', '<span class="good">\1</span>', $line);

					echo $line, "\n";
					if (++$count == 200) {
						echo "\n", '... Truncated ', (count($lines) - $count), ' lines of output.';
						break;
					}
				}
			}
			echo '</pre>';
			return TRUE;
		}
	}

	if (function_exists('registerCommand')) {
		registerCommand('bgp summary', new BGPCommand());
		if (isAdmin()) {
			registerCommand('bgp neighbor', new BGPCommand('neighbors %s'));
			registerCommand('bgp advertised-routes', new BGPCommand('neighbors %s advertised-routes'));
			registerCommand('bgp received-routes', new BGPCommand('neighbors %s received-routes'));
			registerCommand('bgp accepted routes', new BGPCommand('neighbors %s routes'));
		}
		registerCommand('bgp routes', new BGPCommand('%s'));
	}
?>
