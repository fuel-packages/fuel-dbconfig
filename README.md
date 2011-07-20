# Database Configuration Package for FuelPHP

This package is a drop in replacement for Fuel's Config class. It has the same basic functionality, except that the data is stored in the database instead of the file system. It has the added ability to autoload and autosave date to and from the database.

## Development Team

* Frank Bardon Jr. - Lead Developer ([http://nerdsrescue.me] (http://nerdsrescue.me))

## Installation

This package follows standard installation rules, which can be found within the [FuelPHP Documentation for Packages] (http://fuelphp.com/docs/general/packages.html)

To install via the oil package command, first ninjarite's github to the package config.

```
// fuel/app/config/package.php
return array(
	'sources' => array(
		'github.com/fuel-packages',
		'github.com/ninjarite', // ADD THIS LINE
	),
),
```

Then run the following command from your shell while in your applications' base directory

```
oil package install dbconfig
```

## Usage

This package is used in the same way as [http://fuelphp.com/docs/classes/config.html] (Fuel's Config class). Please see that documentation for basic usage, any additional functionality see below.

### Autoload/Autosave

To globally change this functionality set your dbconfig.php configuration file autoload and autosave variables to true.

To change this at runtime

```
DbConfig::$autoload = true;
DbConfig::get('config.key.requested');

DbConfig::$autosave = true;
DbConfig::set('config.key.toset', 'value');
``` 
