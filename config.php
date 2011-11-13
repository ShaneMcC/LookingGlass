<?php
	/**
	 * This is the config file for the looking glass.
	 *
	 * This file lists the config options, and has some defaults and
	 * examples.
	 *
	 * This will be overwritten if the looking glass is updated,
	 * site-specific changes should be placed in "config.local.php" which is
	 * "included" at the bottom of this config file.
	 */

	/** Our AS Number. */
	$config['ouras'] = '123456';

	/** Our Company Name. */
	$config['company'] = 'My Company';

	/** Our Logo URL. */
	$config['logo'] = 'LookingGlass.png';

	/** Our Website URL. */
	$config['url'] = 'https://github.com/ShaneMcC/LookingGlass';

	/**
	 * Link to use to show links to AS numbers in the result of some
	 * commands.
	 */
	$config['aspage'] = 'http://bgp.he.net/AS%d';
	// $config['aspage'] = 'http://www.robtex.com/as/as%d.html';

	/**
	 * Custom CSS Files can be included by creating an array here, or if
	 * only a single file is needed, a string.
	 */
	$config['cssfile'] = '';

	/**
	 * If the default CSS causes problems with the included CSS, it can be
	 * hidden by setting this to true.
	 */
	$config['hidedefaultcss'] = false;

	/**
	 * Custom Javascript Files can be included by creating an array here, or
	 * if only a single file is needed, a string.
	 */
	$config['scriptfile'] = '';

	/**
	 * If set, this file will be included onto the page in place of the
	 * default header. (This is a php include() so PHP code can be used.)
	 *
	 * The file must exist on disk for this to be included (so no remote
	 * file includes.)
	 */
	$config['headerfile'] = '';

	/**
	 * Same as above, but for the footer.
	 */
	$config['footerfile'] = '';

	/**
	 * Authentication options for routers.
	 *
	 * This is used down below when Router objects are being created and no
	 * where else. This could be done inline at creation, but if all routers
	 * are the same, its easier to do it once here. What is valid is
	 * dependant on the router connection type. (SSHRouter or TelnetRouter).
	 *
	 * In general, valid keys are:
	 *   authtype => 'none', 'pass' or 'key'
	 *
	 *   Key Based Authentication:
	 *     keytype => Type of key ('dsa' or 'rsa');
	 *     pubkey => Path to key file
	 *     privkey => Path to private key file
	 *     keypass => (Optional) pass for private key
	 *
	 *   Password based authentication;
	 *     pass => Password to use.
	 */
	$config['authInfo'] = array('authtype' => 'key',
	                            'keytype' => 'rsa',
	                            'privkey' => dirname(__FILE__) . '/keys/id_rsa',
	                            'pubkey' => dirname(__FILE__) . '/keys/id_rsa.pub');

	/**
	 * Some commands and router types need some more information to allow
	 * them to operate correctly.
	 *
	 * For example with quagga based routers we need to know if we are going
	 * to be dumped to a `vtysh` session immediately, or if we get a bash
	 * session and thus need to use `vtysh -c "command"` instead of just
	 * `command`.
	 *
	 * The values that make sense here depend entirely on what routers you
	 * are using. This example config file assumes quagga-based routers with
	 * a bash shell.
	 *
	 * Other possible options could be 'nobgp', 'noping' etc.
	 */
	$config['routerInfo'] = array('shellType' => 'bash');

	/**
	 * Examples on how to create routers.
	 * This is inside an "if (false) { block to allow syntax highlighting.
	 */
	if (false) {

		// Routers can be organised into groups.
		// Groups are created using registerRouterGroup($id, $name);
		//
		// - The ID is an internal ID used when reigstering routers, and not
		//   displayed unless no description is given.
		//
		// - The description is used in the router drop down.
		//
		// If only one group exists, then no group names are shown in the drop
		// down.
		registerRouterGroup('London', 'London Routers');
		registerRouterGroup('Manchester', 'Manchester Routers');


		// Routers are registered using "registerRouter"
		//
		// This takes 3 parameters, a router description, a router object, and
		// a router group id. If the group ID is a group that is not already
		// created, it will be created as registerRouterGroup($group, $group);
		// If no group is given, then the group will be "Routers".
		//
		// The default router can be set by wrapping this with setDefaultRouter
		//
		// Router objects can be created inline or in advance depending on
		// your needs.
		//
		// In general constructor for a router takes 4 parameters:
		//   - IP Address or hostname
		//   - Login Username
		//   - authInfo Array (See above)
		//   - routerInfo Array (See above)
		registerRouter('London Router 1', new QuaggaRouter('192.168.0.1', 'admin', $config['authInfo'], $config['routerInfo']), 'London');
		setDefaultRouter(registerRouter('London Router 2', new QuaggaRouter('192.168.1.2', 'admin', $config['authInfo'], $config['routerInfo']), 'London'));
		registerRouter('Manchester Router 1', new QuaggaRouter('192.168.2.1', 'admin', $config['authInfo'], $config['routerInfo']), 'Manchester');

	}


	if (file_exists(dirname(__FILE__) . '/config.local.php')) {
		include(dirname(__FILE__) . '/config.local.php');
	}
?>