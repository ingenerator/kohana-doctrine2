<?php
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

/**
 * Customised implementation of the Doctrine AnnotationDriver with support for loading Model files from across the
 * Kohana CFS, including with Kohana-style transparent extension.
 *
 * @author     Andrew Coulton <andrew@ingenerator.com>
 * @copyright  2013 inGenerator Ltd
 * @licence    BSD
 * @package    kohana-doctrine2
 * @subpackage annotations
 */
class Doctrine_KohanaAnnotationDriver extends AnnotationDriver {

	/**
	 * {@inheritDoc}
	 */
	public function getAllClassNames()
	{
		$classes = array();

		// Get all files within the CFS as a flat array of relative => absolute path
		$files = Arr::flatten(Kohana::list_files('classes/Model', $this->getPaths()));

		// This will be used a lot!
		$ext_length = strlen(EXT);
		foreach ($files as $relative => $absolute)
		{
			// Skip files that don't end with the Kohana php file extension
			if (substr($relative, -$ext_length) !== EXT)
			{
				continue;
			}

			// Strip the classes/ prefix and the extension
			$file = substr($relative, 8, -$ext_length);

			// Convert slashes to underscores to get to a class name
			$class = str_replace(DIRECTORY_SEPARATOR, '_', $file);

			// Check if this class exists (allow autoloading)
			if (class_exists($class))
			{
				$classes[] = $class;
			}
		}

		return $classes;
	}

}