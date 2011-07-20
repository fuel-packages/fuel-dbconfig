<?php
/**
 * FuelPHP DbConfig Package
 *
 * @author     Frank Bardon Jr.
 * @version    1.0
 * @package    Fuel
 * @subpackage DbConfig
 */
Autoloader::add_core_namespace('DbConfig');

Autoloader::add_classes(array(
	'DbConfig\\DbConfig'             => __DIR__.'/classes/dbconfig.php',
));

/* End of file bootstrap.php */
