<?php
	require_once(dirname(__FILE__) . '/../Command.php');
	require_once(dirname(__FILE__) . '/../MultiProtocolCommand.php');

	/**
	 * Class responsible for executing a Traceroute command on the router.
	 */
	class TraceCommand extends Command implements MultiProtocolCommand {
		/** Traceroute Type */
		private $type;

		/**
		 * Check if a given argument is a valid domain name.
		 * Based on: http://stackoverflow.com/questions/1755144/how-to-validate-domain-name-in-php/4694816#4694816
		 *
		 * @param $name Name to check
		 * @return True or false.
		 */
		function validDomain($name) {
			$pieces = explode('.', $name);
			foreach ($pieces as $piece) {
				if (!preg_match('/^[a-z\d][a-z\d-]{0,62}$/i', $piece) || preg_match('/-$/', $piece)) {
					return false;
				}
			}
			return true;
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
			if ($this->type == 'ipv6') {
				$validIP = filter_var($args, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
			} else {
				$validIP = filter_var($args, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
			}
			$validDomain = $this->validDomain($args);

			if (!$validDomain) { $this->setError('"'.$args.'" is not a valid domain.'); }
			if (!$validIP) { $this->setError('"'.$args.'" is not a valid ' . $this->type . ' address.'); }

			return $validIP || $validDomain;
		}

		/** {@inheritDoc} */
		public function getCommandString($router, $args) {
			if ($router->getType() == 'QuaggaRouter') {
				if ($router->getRouterInfo('astrace', false)) {
					return ($this->type == 'ipv6' ? 'traceroute6' : 'traceroute') . ' -A ' . $args;
				} else {
					return ($this->type == 'ipv6' ? 'traceroute6' : 'traceroute') . ' ' . $args;
				}
			} else {
				return 'traceroute ' . ($this->type == 'ipv6' ? 'ipv6' : 'ip') . ' ' . $args;
			}
		}

		/** {@inheritDoc} */
		public function run($router, $args, $output = true) {
			if (empty($router)) {
				return $this->setError('Invalid Router');
			}
			if (!$this->validateArgs($args)) { return FALSE; }

			$command = $this->getCommandString($router, $args);

			if ($router->getType() == 'QuaggaRouter') {
				$res = $router->runCommand($command, true);
				// On some versions of traceroute, it does stupid things with its warnings and content..
				$out = $res['error'];
				$out .= $res['output'];
			} else {
				$out = $router->runCommand($command);
			}
			if ($out === false) {
				return $this->setError($router->getError());
			}
			if (!$output) { return $out; }

			echo '<pre>';
			echo htmlspecialchars($out);
			echo '</pre>';
			return TRUE;
		}
	}

	if (function_exists('registerCommand')) {
		registerCommand('traceroute', new TraceCommand());
	}
?>
