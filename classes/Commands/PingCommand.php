<?php
	require_once(dirname(__FILE__) . '/../Command.php');
	require_once(dirname(__FILE__) . '/../MultiProtocolCommand.php');

	/**
	 * Class responsible for executing a ping command on the router.
	 */
	class PingCommand extends Command implements MultiProtocolCommand {
		/** Ping Type */
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
			if (!$validIP) { $this->setError('"'.$args.'" is not a valid ip address.'); }

			return $validIP || $validDomain;
		}

		/** {@inheritDoc} */
		public function getCommandString($router, $args) {
			if ($router != null && $router->getType() == 'QuaggaRouter') {
				return ($this->type == 'ipv6' ? 'ping6' : 'ping') . ' -c 5 ' . $args;
			} else {
				return 'ping ' . ($this->type == 'ipv6' ? 'ipv6' : 'ip') . ' ' . $args;
			}
		}

		/** {@inheritDoc} */
		public function run($router, $args, $output = true) {
			if (empty($router)) {
				return $this->setError('Invalid Router');
			}
			if (!$this->validateArgs($args)) { return FALSE; }

			$command = $this->getCommandString($router, $args);

			$out = $router->runCommand($command);
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
		registerCommand('ping <IP>', new PingCommand());
	}
?>