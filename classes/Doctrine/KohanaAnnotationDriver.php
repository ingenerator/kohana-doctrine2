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

		foreach ($files as $relative => $absolute)
		{
			$class = $this->fileToValidClassname($relative, $absolute);
			if ($class AND  ! $this->isTransient($class))
			{
				$classes[] = $class;
			}
		}

		return $classes;
	}

	/**
	 * Locate the name of a class defined in a file - bearing in mind that PSR0 allows the directory separator to mean
	 * either or both an underscore or a namespace. So we have to try with underscores (most common in a Kohana
	 * project), then with namespaces, and then with every combination in between (rare, but possible). Fortunately this
	 * code only really runs during generating schema etc rather than on web requests especially once caching is in
	 * place.
	 *
	 * It's still pretty insane.
	 *
	 * @param string $relative relative CFS path to the file from classes/
	 * @param string $absolute absolute path to the file
	 *
	 * @return string name of a class if one exists, or NULL if we couldn't make this filename into a class name
	 */
	protected function fileToValidClassname($relative, $absolute)
	{
		$ext_length = strlen(EXT);

		// Skip files that don't end with the Kohana php file extension
		if (substr($relative, -$ext_length) !== EXT)
		{
			return NULL;
		}

		// Strip the classes/ prefix and the extension, then split into directory parts
		$file = substr($relative, 8, -$ext_length);
		$name_parts = explode(DIRECTORY_SEPARATOR, $file);

		// Ensure the file has been included so we don't have to run autoloader cycles
		require_once($absolute);

		// Try underscored first - most likely
		$class = implode('_', $name_parts);
		if (class_exists($class, FALSE))
		{
			return $class;
		}

		// Try namespaced as second guess
		$class = implode('\\', $name_parts);
		if (class_exists($class, FALSE))
		{
			return $class;
		}

		// It could be a mix of both namespace and underscores
		$namespace = array();
		while ($name_parts)
		{
			$namespace[] = array_shift($name_parts);
			$class = implode('\\', $namespace).'\\'.implode('_', $name_parts);
			if (class_exists($class, FALSE))
			{
				return $class;
			}
		}

		// No matches, return NULL
		return NULL;
	}

}