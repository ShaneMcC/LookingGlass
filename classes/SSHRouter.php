<?php
	require_once(dirname(__FILE__) . '/Router.php');

	/**
	 * Class responsible for communicating with an ssh based router and
	 * running commands.
	 */
	abstract class SSHRouter extends Router {
		/** Authentication type. */
		private $authtype;

		/** Password to connect with. */
		private $pass;

		/** Private key type. */
		private $keytype;

		/** Private key file. */
		private $privkey;

		/** Public key file. */
		private $pubkey;

		/** Password for private key. */
		private $keypass;

		/** SSH Connection object. */
		private $connection = null;

		/**
		 * Create a new router that is authenticated using a private/public key.
		 *
		 * @param $router Router Address
		 * @param $user Username to connect with
		 * @param $authInfo Array containing authentication info.
		 *                  authtype => 'pass' or 'key'
		 *
		 *                  Key Based Authentication:
		 *                    keytype => Type of key ('dsa' or 'rsa');
		 *                    pubkey => Path to key file
		 *                    privkey => Path to private key file
		 *                    keypass => (Optional) pass for private key
		 *
		 *                  Password based authentication;
		 *                    pass => Password to use.
		 * @param $routerInfo Array containing router-specific information that
		 *                    may be used by the implementation of this class.
		 */
		public function __construct($router, $user, $authInfo = array(), $routerInfo = array()) {
			parent::__construct($router, $user, $authInfo, $routerInfo);

			if (!is_array($authInfo) || !isset($authInfo['authtype'])) {
				return $this->setError('No authtype given.');
			} else {
				$this->authtype = $authInfo['authtype'];
			}

			if ($this->authtype == 'pass') {
				$this->pass = isset($authInfo['pass']) ? $authInfo['pass'] : '';
			}

			if ($this->authtype == 'key') {
				$keytype = isset($authInfo['keytype']) ? $authInfo['keytype'] : '';
				$this->keytype = ($keytype == 'dsa') ? 'ssh-dss' : 'ssh-rsa';
				$this->pubkey = isset($authInfo['pubkey']) ? $authInfo['pubkey'] : '';
				$this->privkey = isset($authInfo['privkey']) ? $authInfo['privkey'] : '';
				$this->keypass = isset($authInfo['keypass']) ? $authInfo['keypass'] : '';
			}
		}

		/** {@inheritDoc} */
		public function connected() {
			return ($this->connection != null);
		}

		/** {@inheritDoc} */
		public function connect($forceNew = false) {
			if ($this->connected()) {
				if ($forceNew) {
					$this->close();
				} else {
					return TRUE;
				}
			}

			$methods = array();

			if ($this->authtype == 'key') {
				$methods['hostkey'] = $this->keytype;
			}

			$connection = ssh2_connect($this->router, 22, $methods);

			if ($this->authtype == 'key') {
				$authok = ssh2_auth_pubkey_file($connection, $this->user, $this->pubkey, $this->privkey, $this->keypass);
			} else if ($this->authtype == 'pass') {
				$authok = ssh2_auth_password($connection, $this->user, $this->pass);
			} else {
				return $this->setError('No valid authtype given.');
			}

			if ($authok) {
				$this->connection = $connection;
			} else {
				$this->setError('Authentication failed.');
				return FALSE;
			}

			return TRUE;
		}

		/** {@inheritDoc} */
		public function close() {
			if ($this->connected()) {
				$this->doCommand('exit');
			}
		}

		/**
		 * Actually run the command. This will send the command to the
		 * underlying shell. Sub-classes should override runCommand if they
		 * need to modify the command before it is run.
		 *
		 * @param $returnArray Instead of returning $output or FALSE, return
		 *                     an array containing errors and output.
		 */
		protected final function doCommand($command, $returnArray = false) {
			if (!$this->connected()) {
				return $this->setError('Not connected.');
			}

			if (!($stream = ssh2_exec($this->connection, $command))) {
				return $this->setError('Unable to run command.');
			}
			@stream_set_blocking($stream, true);
			$data = @stream_get_contents($stream);

			$stderr = @ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
			@stream_set_blocking($stderr, true);
			$err = @stream_get_contents($stderr);

			@fclose($stderr);
			@fclose($stream);

			if ($returnArray) {
				return array('error' => $err, 'output' => $data);
			} else {
				if (!empty($err)) {
					return $this->setError('Command Error: ' . $err);
				} else {
					return $data;
				}
			}
		}

		/** {@inheritDoc} */
		public function runCommand($command, $returnArray = false) {
			return doCommand($command, $returnArray);
		}
	}
?>