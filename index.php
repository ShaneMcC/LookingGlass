<?php
	/***********************************************************************
	 * Load required classes.
	 ***********************************************************************/
	foreach (glob(dirname(__FILE__) . '/classes/*.php') as $class) {
		require_once($class);
	}
	unset($class);

	/***********************************************************************
	 * Load config file.
	 ***********************************************************************/
	$config = array('ouras' => '', 'company' => '');
	require_once('config.php');

	/***********************************************************************
	 * Load Commands
	 ***********************************************************************/
	$commands = array();
	$defaultCommand = '-1';
	$commandCount = 0;

	/**
	 * Register an object as a command.
	 */
	function registerCommand($name, $object) {
		global $commands, $defaultCommand, $commandCount;
		$id = abs(crc32($name));
		$commands[$id] = array('Name' => $name, 'Object' => $object);

		if ($defaultCommand == '-1') { $defaultCommand = $id; };
		$commandCount++;
		return $id;
	}

	foreach (glob(dirname(__FILE__) . '/classes/Commands/*Command.php') as $command) {
		require_once($command);
	}
	unset($command);

	/***********************************************************************
	 * Router related functions
	 ***********************************************************************/
	$routers = array();
	$routerMap = array();
	$defaultRouter = '-1';
	$routerCount = 0;

	/**
	 * Reset Router List.
	 */
	function resetRouters() {
		global $routers, $routerMap, $defaultRouter, $routerCount;
		$routers = array();
		$routerMap = array();
		$defaultRouter = '-1';
		$routerCount = 0;
	}

	/**
	 * Register a router group.
	 *
	 * @param $name Name of group
	 * @param $description Description of group
	 */
	function registerRouterGroup($name, $description = '') {
		global $routers;
		if ($description == '') { $description = $name; }
		$routers[$name] = array('Name' => $name,
		                        'Description' => $description,
		                        'Routers' => array());
	}
	/**
	 * Register a router.
	 *
	 * @param $name Name of group
	 * @param $object Router object.
	 */
	function registerRouter($name, $object, $group = 'Routers') {
		global $routers, $routerMap, $defaultRouter, $routerCount;

		if (!isset($routers[$group])) { registerRouterGroup($group); }
		$id = abs(crc32($group)) . '-' . abs(crc32($name));
		$routers[$group]['Routers'][] = array('ID' => $id, 'Name' => $name);
		$routerMap[$id] = array('Name' => $name, 'Object' => $object);

		if ($defaultRouter == '-1') { $defaultRouter = $id; };
		$routerCount++;
		return $id;
	}

	/**
	 * Set the default Router ID.
	 *
	 * @param $id ID of default router. If an unknown ID is given, the
	 *            default will not change.
	 * @return The ID of the default router.
	 */
	function setDefaultRouter($id) {
		global $defaultRouter, $routerMap;
		$defaultRouter = isset($routerMap[$id]) ? $id : $defaultRouter;
		return $defaultRouter;
	}

	/**
	 * Check if the current user is an admin.
	 *
	 * @return True if the user is an admin.
	 */
	function isAdmin() {
		global $config;
		return isset($_SERVER['REMOTE_ADDR']) && isset($config['admin']) && in_array($_SERVER['REMOTE_ADDR'], $config['admin']);
	}

	/**
	 * Get a link pointing to the requested command.
	 */
	function getLink($command = null, $input = null, $protocol = null, $router = null) {
		global $defaultCommand, $commands, $routerMap, $defaultRouter;
		$params = array();

		$params['command'] = $defaultCommand;
		if ($command != null) {
			foreach ($commands as $id => $c) {
				if (strtolower($command) == strtolower($c['Name']) || $command == $id) {
					$params['command'] = $id;
					break;
				}
			}
		}
		if ($protocol == null) { $protocol = $_REQUEST['protocol']; }
		$params['protocol'] = ($protocol == 'ipv6') ? 'ipv6' : 'ipv4';

		$params['router'] = isset($_REQUEST['router']) ? $_REQUEST['router'] : $defaultRouter;
		if ($router !== null) {
			foreach ($routerMap as $id => $r) {
				if (strtolower($router) == strtolower($r['Name']) || $router == $id) {
					$params['router'] = $id;
					break;
				}
			}
		}

		$params['runcommand'] = 'Submit Query';

		if ($input !== null) {
			$params['input'] = $input;
		}

		$result = array();
		foreach ($params as $k => $v) {
			$result[] = urlencode($k) . '=' . urlencode($v);
		}
		return  'index.php?' . implode('&', $result);
	}

	/***********************************************************************
	 * Load routers file.
	 ***********************************************************************/
	if (file_exists(dirname(__FILE__) . '/routers.php')) {
		resetRouters();
		require_once(dirname(__FILE__) . '/routers.php');
	}

	$hasResult = false;
	/***********************************************************************
	 * Begin
	 ***********************************************************************/
?>
<HTML>
	<HEAD>
		<TITLE><?php echo $config['company']; ?> Looking Glass</TITLE>
		<?php if (!isset($config['cssfile']) || !isset($config['hidedefaultcss']) || !$config['hidedefaultcss']) { ?>
			<STYLE type="text/css">
				.center {
					margin-left: auto;
					margin-right: auto;
					text-align: center;
				}

				table.commandtable {
					background: #EEE;
				}

				table.commandtable td {

				}

				table.commandtable tr th {
					background: black;
					color: white;
				}

				table.commandtable tr td {
					color: black;
					border: 0px;
				}

				div.footercontent {
					text-align: right;
					font-size: small;
					width: 100%;
					border-top: 1px solid black;
				}

				div.result {
					margin: 5px auto;
					border: none;
					padding: 2px;
					width: 95%;
					min-width: 800px;
				}

				div.errorbox, div.successbox {
					border: 1px dashed #cc0000;
					background-color: #FBEEEB;
					color: #cc0000;

					/* font-weight: bold; */
					text-align: center;
					width: 800px;
					padding: 10px;
					margin: 10px auto;
				}

				div.successbox {
					border: 1px dashed #00cc00;
					background-color: #EEFBEB;
					color: #009900;
				}

				div.runinfo table {
					width: 800px;
				}

				div.runinfo table td {
					text-align: left;
				}

				div.runinfo table th {
					text-align: right;
					padding-right: 5px;
					width: 33%;
				}

				div.result div.output {
					text-align: left;
					min-width: 800px;

					margin: 20px auto;
					padding: 10px;
					padding-right: none;

					border-left: 4px solid black;
					background: #EEE;
				}

				div.result div.output span.bad {
					color: red;
				}

				div.result div.output span.good {
					color: green;
					font-weight: bold;
				}
			</STYLE>
		<?php }

			if (isset($config['cssfile'])) {
				if (!is_array($config['cssfile'])) {
					$config['cssfile'] = array($config['cssfile']);
				}

				foreach ($config['cssfile'] as $file) {
					if (!empty($file)) {
						echo '<link href="', $file, '" rel="stylesheet" type="text/css">';
					}
				}
			}

			if (isset($config['scriptfile'])) {
				if (!is_array($config['scriptfile'])) {
					$config['scriptfile'] = array($config['scriptfile']);
				}

				foreach ($config['scriptfile'] as $file) {
					if (!empty($file)) {
						echo '<script type="text/javascript" src="', $file, '"></script>';
					}
				}
			}
			?>
	</HEAD>
	<BODY>
		<div class="page">
			<div class="header">
				<?php
					if (isset($config['headerfile']) && !empty($config['headerfile']) && file_exists($config['headerfile'])) {
						include($config['headerfile']);
					} else {
						if (isset($config['logo'])) {
							echo '<div class="logo center">';
							if (isset($config['url'])) {
								echo '<a href="', $config['url'],'">';
							}
							echo '<img src="', $config['logo'], '" alt="', $config['company'], '">';
							if (isset($config['url'])) {
								echo '</a>';
							}
							echo '</div>';
							echo '<h1 class="center">', $config['company'],' Looking Glass</h1>';
						}
					}
				?>
			</div>

			<div class="body">
				<?php
				$showForm = true;
				$formError = '';

				if ($commandCount == 0) {
					$formError = 'No commands found. Please check that '.dirname(__FILE__).'/classes/Commands/ exists.';
					$showForm = false;
				} else if ($routerCount == 0) {
					$formError = 'No routers found. Please check your config file.';
					$showForm = false;
				}

				if ($showForm && isset($_REQUEST['runcommand'])) {
					$runcmd = array();

					$runcmd['router'] = isset($_REQUEST['router']) && isset($routerMap[$_REQUEST['router']]) ? $routerMap[$_REQUEST['router']] : null;
					$runcmd['command'] = isset($_REQUEST['command']) && isset($commands[$_REQUEST['command']]) ? $commands[$_REQUEST['command']] : null;
					$runcmd['input'] = isset($_REQUEST['input']) && isset($_REQUEST['input']) ? $_REQUEST['input'] : '';
					$runcmd['protocol'] = isset($_REQUEST['protocol']) ? $_REQUEST['protocol'] : 'ipv4';

					// Run the command here to get the output.
					ob_start();
					if (empty($runcmd['router'])) {
						$runcmd['error'] = 'No valid router selected.';
						$runcmd['result'] = FALSE;
					} else  if (empty($runcmd['command'])) {
						$runcmd['error'] = 'No valid command selected.';
						$runcmd['result'] = FALSE;
					} else {
						$runcmd['command']['Object']->setProtocol($runcmd['protocol']);
						$runcmd['router']['Object']->connect();
						$runcmd['commandstring'] = $runcmd['command']['Object']->getCommandString($runcmd['router']['Object'], $runcmd['input']);
						$runcmd['result'] = $runcmd['command']['Object']->run($runcmd['router']['Object'], $runcmd['input']);
					}
					$runcmd['output'] = ob_get_contents();
					ob_end_clean();
				}
				?>

				<?php if ($showForm) { ?>
					<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET">
						<table class="commandtable center"  width="800px">
							<tr>
								<th width="33%">Command</th>
								<th width="34%">Arguments</th>
								<th width="33%">Router</th>
							</tr>
							<tr>
								<td>
									<table class="commands center">
									<?php foreach ($commands as $id => $command) { ?>
										<?php
											if (isset($_REQUEST['command'])) {
												$selected = ($id == $_REQUEST['command']) ? 'checked' : '';
											} else {
												$selected = ($id == $defaultCommand) ? 'checked' : '';
											}
										?>
										<tr>
											<td>
												<input type="radio" name="command" value="<?php echo $id; ?>" <?php echo $selected; ?>/>
											</td>
											<td style="text-align: left">
												<?php echo htmlspecialchars($command['Name']); ?>
											</td>
										</tr>
									<? } ?>
										<tr><td colspan=2>&nbsp;</td></tr>
										<tr>
											<td>&nbsp;</td>
											<td>Protocol:
												<select name="protocol">
													<option value="ipv4" <?php echo (empty($_REQUEST['protocol']) || $_REQUEST['protocol'] == 'ipv4' ? 'selected' : '');?>>IPv4</option>
													<option value="ipv6" <?php echo (!empty($_REQUEST['protocol']) && $_REQUEST['protocol'] == 'ipv6' ? 'selected' : '');?>>IPv6</option>
												</select>
											</td>
										</tr>
									</table>
								</td>
								<td>
									<input type="text" name="input" style="width: 80%;" value="<?php echo isset($_REQUEST['input']) ? htmlspecialchars($_REQUEST['input']) : ''; ?>"></input>
								</td>
								<td>
									<select name="router">
									<?php foreach ($routers as $gid => $group) {
										if (count($routers) > 1) {
											echo '<optgroup label="', $group['Description'], '">';
										}
										foreach ($group['Routers'] as $router) {
											if (isset($_REQUEST['router'])) {
												$selected = ($router['ID'] == $_REQUEST['router']) ? 'selected' : '';
											} else {
												$selected = ($router['ID'] == $defaultRouter) ? 'selected' : '';
											}
											echo '<option value="', htmlspecialchars($router['ID']), '" ', $selected, '>', htmlspecialchars($router['Name']), '</option>';
										}
										if (count($routers) > 1) {
											echo '</optgroup>';
										}
									} ?>
									</select>
								</td>
							</tr>

							<tr><td colspan="3">&nbsp;</td></tr>

							<tr>
								<td colspan="2">&nbsp;</td>
								<td>
									<input type="submit" name="runcommand" value="Submit Query">
									<input type="reset" value="Reset">
								</td>
							</tr>

						</table>
					</form>
				<?php } elseif (!empty($formError)) {
					echo '<div class="errorbox">';
					echo '<Strong>Error:</strong> ', $formError;
					echo '</div>';
				} ?>
			</div>

			<?php if (isset($runcmd)) { ?>
				<div class="result">
					<div class="runinfo">
						<table class="center">
							<?php if (!empty($runcmd['router'])) { ?>
							<tr>
								<th>Router</th>
								<td><?php echo $runcmd['router']['Name']; ?></td>
							</tr>
							<?php } ?>
							<?php if (!empty($runcmd['commandstring'])) { ?>
								<tr>
									<th>Command</th>
									<td><?php echo $runcmd['commandstring']; ?></td>
								</tr>
							<?php } ?>
						</table>

						<?php
							// If the command did not succeed, show the error.
							if ($runcmd['result'] === FALSE) {
								echo '<div class="errorbox">';
								echo '<Strong>Error:</strong> There was an error running the requested command.';
								if (isset($runcmd['error'])) {
									$error = $runcmd['error'];
								} else if (empty($runcmd['command'])) {
									$error = 'Invalid command given.';
								} else {
									$error = $runcmd['command']['Object']->getError();
								}
								if (!empty($error)) {
									echo '<br>';
									echo $error;
								}
								echo '</div>';
							}
						?>
					</div>

					<?php if ($runcmd['result'] !== FALSE) { ?>
					<div class="center output">
						<?php echo $runcmd['output']; ?>
					</div>
					<?php } ?>
				</div>
			<?php } ?>

			<div class="footer">
				<?php
					if (isset($config['footerfile']) && !empty($config['footerfile']) && file_exists($config['footerfile'])) {
						include($config['footerfile']);
					} else {
						$year = '2011';
						if (date('Y') != $year) {
							$year .= ' - ';
							$year .= date('Y');
						}
						?>
						<div class="footercontent">
						<a href="http://github.com/ShaneMcC/LookingGlass">PHP Looking Glass</a> &copy; <?echo $year; ?> - Shane "Dataforce" Mc Cormack
						</div>

					<?php }
				?>
			</div>
		</div>
	</BODY>
</HTML>
