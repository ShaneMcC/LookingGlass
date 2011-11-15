<?php
	require_once(dirname(__FILE__) . '/Router.php');

	/**
	 * Class responsible for communicating with a router and running a
	 * command.
	 */
	abstract class Command {
		/** Last error. */
		private $lastError = '';

		/**
		 * Create a new Command.
		 */
		public function __construct() {
			// Do nothing for now. This exists to give sub classes something to call
			// with parent::__construct();
		}

		/**
		 * Check that the given arguments are valid.
		 *
		 * @param $args Arguments for the command as a string.
		 * @return True or false.
		 */
		public abstract function validateArgs($args);

		/**
		 * Get the command being run as a string.
		 *
		 * @param $router Router to run the command on.
		 * @param $args Arguments for the command as a string.
		 * @return Command as a string.
		 */
		public abstract function getCommandString($router, $args);

		/**
		 * Run the given command.
		 *
		 * @param $router Router to run the command on.
		 * @param $args Arguments for the command as a string.
		 * @param $output (Default: true) Output the result rather than
		 *                returning it. Returned result should be unformatted,
		 *                outputed result can be reformated.
		 * @return TRUE, FALSE or output.
		 */
		public abstract function run($router, $args, $output = true);

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
		public final function getError() {
			return $this->lastError;
		}
	}
?>