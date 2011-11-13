<?php

	/**
	 * Interface to define a command that behaves differently for
	 * IPv4 then IPv6.
	 */
	interface MultiProtocolCommand {
		/**
		 * Set the protocol for this command.
		 *
		 * @param $type (Default: ipv4) Type of command. 'ipv4' or 'ipv6'
		 */
		public function setProtocol($type = 'ipv4');
	}
?>