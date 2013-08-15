<?php
/**
 * Database connection configuration - based on the same structure as the core Kohana database module to allow shared
 * management of Doctrine and Kohana database connections.
 *
 * @author     Andrew Coulton <andrew@ingenerator.com>
 * @author     Kohana Team
 * @copyright  2008-2012 Kohana Team
 * @licence    BSD
 * @package    kohana-doctrine2
 * @subpackage config
 */
return array
(
	'default' => array
	(
		'type'       => 'MySQL',
		'connection' => array(
			/**
			 * The following options are available for MySQL:
			 *
			 * string   hostname     server hostname, or socket
			 * string   database     database name
			 * string   username     database username
			 * string   password     database password
			 * boolean  persistent   use persistent connections?
			 *
			 * Ports and sockets may be appended to the hostname.
			 */
			'hostname'   => 'localhost',
			'database'   => 'kohana',
			'username'   => FALSE,
			'password'   => FALSE,
			'persistent' => FALSE,
		),
		'charset'      => 'utf8',
	),
);