<?php

/**
 * FuelPHP DbConfig Package
 *
 * @author     Frank Bardon Jr.
 * @version    1.0
 * @package    Fuel
 * @subpackage DbConfig
 */
namespace DbConfig;

class DbConfig {

	/**
	 * Array of keys that have been loaded from the database
	 *
	 * @var    array loaded database keys
	 * @access public
	 */
	public static $loaded_keys = array();
	
	/**
	 * Key/value pairs pulled from the database
	 *
	 * @var    array loaded database information
	 * @access public
	 */
	public static $items = array();

	/**
	 * Name of the database table to pull from
	 *
	 * @var    string database table name
	 * @access public
	 */
	public static $table = null;

	/**
	 * Autoload configuration data during get()?
	 *
	 * @var boolean
	 * @access public
	 */
	public static $autoload = false;

	/**
	 * Autosave configuration data during set()?
	 *
	 * @var boolean
	 * @access public
	 */
	public static $autosave = false;


	/**
	 * Load configuration data from the database
	 *
	 * @access public
	 * @param  string  database key
	 * @param  string  group name
	 * @param  boolean reload this request
	 */
	public static function load($key, $group = null, $reload = false)
	{
		if (in_array($key, static::$loaded_keys) and ! $reload)
		{
			return false;
		}
		
		if ( ! $config = static::_get_by_key($key)->current())
		{
			return false;
		}

		if ( ! $group)
		{
			$group = $key;
		}

		static::$items[$group] = json_decode($config['value'], true);

		if (is_array(static::$items[$group]))
		{
			static::$loaded_keys[] = $key;
			return true;
		}
		
		return false;
	}

	/**
	 * Save configuration array to database
	 *
	 * @access public
	 * @param  string database key
	 * @param  array  configuration values
	 */
	public static function save($key, $config)
	{
		if ( ! is_array($config))
		{
			throw new \Fuel_Exception('DbConfig: value passed to DbConfig::save() must be an array');
			return false;
		}
		
		$config = json_encode($config);

		if (count(static::_get_by_key($key)) > 0)
		{
			return \DB::update(static::$table)->value('value', $config)->where('key', $key)->execute();
		}
		else
		{
			return \DB::insert(static::$table)->set(array('key' => $key, 'value' => $config))->execute();
		}
	}

	/**
	 * Get configuration value from array
	 *
	 * The overwhelming majority of this function has been taken from
	 * the Fuel Core Config class.
	 *
	 * @access public
	 * @param  string key of value to retrieve
	 * @param  string value to use if not found
	 */
	public static function get($item, $default = null)
	{
		if (isset(static::$items[$item]))
		{
			return static::$items[$item];
		}
		
		if (strpos($item, '.') !== false)
		{
			$parts = explode('.', $item);
			
			if (static::$autoload)
			{
				static::load($parts[0]);
			}

			switch (count($parts))
			{
				case 2:
					if (isset(static::$items[$parts[0]][$parts[1]]))
					{
						return static::$items[$parts[0]][$parts[1]];
					}
				break;

				case 3:
					if (isset(static::$items[$parts[0]][$parts[1]][$parts[2]]))
					{
						return static::$items[$parts[0]][$parts[1]][$parts[2]];
					}
				break;

				case 4:
					if (isset(static::$items[$parts[0]][$parts[1]][$parts[2]][$parts[3]]))
					{
						return static::$items[$parts[0]][$parts[1]][$parts[2]][$parts[3]];
					}
				break;

				default:
					$return = false;
					foreach ($parts as $part)
					{
						if ($return === false and isset(static::$items[$part]))
						{
							$return = static::$items[$part];
						}
						elseif (isset($return[$part]))
						{
							$return = $return[$part];
						}
						else
						{
							return $default;
						}
					}
					return $return;
				break;
			}
		}

		return $default;
	}

	/**
	 * Set value in configuration array
	 *
	 * The overwhelming majority of this function has been taken from
	 * the Fuel Core Config class.
	 *
	 * @access public
	 * @param  string configuration item key
	 * @param  array  configuration item value
	 */
	public static function set($item, $value)
	{
		$parts = explode('.', $item);

		switch (count($parts))
		{
			case 1:
				static::$items[$parts[0]] = $value;
			break;

			case 2:
				static::$items[$parts[0]][$parts[1]] = $value;
			break;

			case 3:
				static::$items[$parts[0]][$parts[1]][$parts[2]] = $value;
			break;

			case 4:
				static::$items[$parts[0]][$parts[1]][$parts[2]][$parts[3]] = $value;
			break;

			default:
				$item =& static::$items;
				foreach ($parts as $part)
				{
					// if it's not an array it can't have a subvalue
					if ( ! is_array($item))
					{
						return false;
					}

					// if the part didn't exist yet: add it
					if ( ! isset($item[$part]))
					{
						$item[$part] = array();
					}

					$item =& $item[$part];
				}
				$item = $value;
			break;
		}

		if (static::$autosave)
		{
			return static::save($parts[0], static::$items[$part[0]]);
		}

		return true;
	}

	/**
	 * Initialize DbConfig class
	 *
	 * @access public
	 */
	public static function _init()
	{
		\Config::load('dbconfig', true);

		static::$table = \Config::get('dbconfig.db.table', 'config');
		static::$autoload = \Config::get('dbconfig.db.autoload', false);
		static::$autosave = \Config::get('dbconfig.db.autosave', false);
		
		$installed = \Config::get('dbconfig.db.installed', true);

		if ( ! $installed)
		{
			if ( ! static::_install_db())
			{
				throw new \Exception('Could not create configuration table.');
			}
			
			\Config::set('dbconfig.db', array('table' => static::$table, 'installed' => true));
			\Config::save('dbconfig', \Config::get('dbconfig'));
		}
	}

	/**
	 * Get database row for a given key
	 *
	 * @access private
	 */
	private static function _get_by_key($key)
	{
		return \DB::select('value')->from(static::$table)->where('key', $key)->limit(1)->execute();
	}

	/**
	 * Install the database structure for DbConfig
	 *
	 * Set the database table name in config/dbconfig.php and set the installed key to false.
	 * send a page request that includes the DbConfig array and set installed to true.
	 *
	 * @access private
	 */
	private static function _install_db()
	{
		$rows = \DBUtil::create_table(static::$table, array(
			'id'    => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true),
			'key'   => array('constraint' => 30, 'type' => 'varchar', 'null' => false),
			'value' => array('type' => 'text', 'null' => false),
		), array('id'), true);
		
		return ($rows > 0);
	}
}

/* End of file: classes/dbconfig.php */
