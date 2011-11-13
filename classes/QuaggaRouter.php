<?php

	require_once(dirname(__FILE__) . '/SSHRouter.php');

	/**
	 * This class represents a quagga router. Quagga routers can either have
	 * a full bash shell, or just vtysh.
	 *
	 * By default we assume bash, however this can be specified using the
	 * 'shellType' routerInfo option.
	 */
	class QuaggaRouter extends SSHRouter {
		/** {@inheritDoc} */
		public function getType() {
			return __CLASS__;
		}

		/** {@inheritDoc} */
		public function runCommand($command, $returnArray = false) {
			$shell = $this->getRouterInfo('shellType', 'bash');

			if ($shell == 'bash') {
				if (!preg_match('/^(ping|traceroute)/', $command)) {
					$command = 'vtysh -c ' . escapeshellarg($command);
				}
			} else if ($shell == 'vtysh') {
				if (preg_match('/^ping/', $command)) {
					// vtysh will ping forever...
					return $this->setError('Ping is not available from this router.');
				}
			} else {
				return FALSE;
			}

			return $this->doCommand($command, $returnArray);
		}
	}
?>