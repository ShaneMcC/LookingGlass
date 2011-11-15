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
			if (!$validIP) { $this->setError('"'.$args.'" is not a valid ip address.'); }

			return $validIP;
		}

		/** {@inheritDoc} */
		public function getCommandString($router, $args) {
			$args = explode(' ', $args);
			$args = $args[0];
			return 'show ' . ($this->type == 'ipv6' ? 'ipv6' : 'ip') . ' bgp ' . sprintf($this->subcommand, $args);
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
				echo htmlspecialchars($out);
			}
			echo '</pre>';
			return TRUE;
		}
	}

	if (function_exists('registerCommand')) {
		registerCommand('bgp summary', new BGPCommand());
		registerCommand('bgp advertised-routes', new BGPCommand('neighbors %s advertised-routes'));
	}
?>