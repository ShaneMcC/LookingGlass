<?php
	require_once(dirname(__FILE__) . '/../Command.php');
	require_once(dirname(__FILE__) . '/../MultiProtocolCommand.php');

	/**
	 * Class responsible for executing a route-related command on the router.
	 */
	class RouteCommand extends Command implements MultiProtocolCommand {
		/** Route Type */
		private $type;

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
			return 'show ' . ($this->type == 'ipv6' ? 'ipv6' : 'ip') . ' route ' . $args;
		}

		/** {@inheritDoc} */
		public function run($router, $args, $output = true) {
			if (empty($router)) {
				return $this->setError('Invalid Router');
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
		registerCommand('route', new RouteCommand());
	}
?>
