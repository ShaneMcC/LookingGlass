<?php
	/**
	 * Interface responsible for communicating with a router and running
	 * commands.
	 */
	abstract class Router {
		/** Last error. */
		private $lastError = '';

		/** Router to connect to. */
		protected $router;

		/** Username to connect with. */
		protected $user;

		/** AuthInfo array. */
		protected $authInfo = array();

		/** RouterInfo array. */
		protected $routerInfo = array();

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
			$this->router = $router;
			$this->user = $user;
			$this->authInfo = $authInfo;
			$this->routerInfo = $routerInfo;
		}


		/**
		 * This will return an array with the information used to create this
		 * router object.
		 *
		 * It will contain 4 keys, 'router', 'user', 'authInfo', 'routerInfo'
		 * which correspond to the parameters passed at construction time.
		 *
		 * For ease of access, if a specific key is given, then that key will
		 * be return from $this->routerInfo (or $default if not given)
		 *
		 * @param $key (Optiona) Key from $this->routerInfo to return.
		 * @param $default (Default: null) Default value to return if $key is
		 *                 not set in $this->routerInfo;
		 * @return Construction variables, or specific key.
		 */
		public function getRouterInfo($key = null, $default = null) {
			if ($key != null) {
				return isset($this->routerInfo[$key]) ? $this->routerInfo[$key] : null;
			} else {
				return array('router' => $this->router,
				             'user' => $this->user,
				             'authInfo' => $this->authInfo,
				             'routerInfo' => $this->routerInfo,
				            );
			}
		}

		/**
		 * Set the last error.
		 *
		 * @param $error The error messaage.
		 * @return FALSE to allow "return $this->setError('Message');"
		 */
		protected function setError($error = '') {
			$this->lastError = $error;
			return FALSE;
		}

		/**
		 * Return the last error.
		 *
		 * @return The last error.
		 */
		public function getError() {
			return $this->lastError;
		}

		/**
		 * Check if we are connected or not.
		 *
		 * @return True or false.
		 */
		public abstract function connected();

		/**
		 * Connect to the router.
		 *
		 * @param $forceNew close an existing connection and start a new one,
		 *                  otherwise just return TRUE;
		 * @return TRUE if a connection was established, else false.
		 */
		public abstract function connect($forceNew = false);

		/**
		 * Close the router connection.
		 */
		public abstract function close();

		/**
		 * Execute a command on the router.
		 *
		 * @param $command Command to execute.
		 * @param $returnArray Instead of returning $output or FALSE, return
		 *                     an array containing errors and output.
		 * @return Command output or false if there is an error.
		 */
		public abstract function runCommand($command, $returnArray = false);

		/**
		 * What type of router is this?
		 * This allows different Command objects to use different commands.
		 */
		public abstract function getType();
	}
?>