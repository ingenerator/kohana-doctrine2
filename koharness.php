<?php
/**
 * Configuration for the koharness module testing environment.
 * 
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @copyright 2013 inGenerator Ltd
 * @link      https://github.com/ingenerator/koharness
 */
return array(
  'modules' => array(
    'kohana-doctrine2' => __DIR__,
    'unittest'    => __DIR__.'/modules/unittest',
  )
);
