<?php
/**
 *  Configuration for the doctrine installation
 *
 * @author     Andrew Coulton <andrew@ingenerator.com>
 * @copyright  2013 inGenerator Ltd
 * @licence    BSD
 * @package    kohana-doctrine2
 * @subpackage config
 */
return array(
	// Path to the composer vendor directory - may need to be changed in a non-standard directory structure
	'composer_vendor_path' => APPPATH.'../vendor/',

	// Default proxy class directory
	'proxy_dir'           => APPPATH.'/classes/Proxies/Model',

	// Default proxy class namespace
	'proxy_namespace'     => 'Proxies\Model',
);