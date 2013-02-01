<?php

	/****************************************************************/
	/* Moody                                                        */
	/* configuration.class.php                                      */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/

	namespace Moody {

	class Configuration {
		private static $configuration = array();
		private static $callbacks = array();

		public static function load($string) {

		}

		public static function set($path, $value, TokenVM $tokenVM = null) {
			$path = strtolower($path);
			if(isset(self::$callbacks[$path])) {
				call_user_func(self::$callbacks[$path], $value, $tokenVM);
			}
			return self::$configuration[$path] = $value;
		}

		public static function get($path, $defaultValue = null) {
			$path = strtolower($path);

			if(isset(self::$configuration[$path]))
				return self::$configuration[$path];
			return $defaultValue;
		}

		public static function registerCallback($path, $defaultValue, $callback, $invoke = true) {
			if(!is_callable($callback)) {
				throw new MoodyException('Bad configuration callback');
			}

			$path = strtolower($path);

			self::$callbacks[$path] = $callback;
			if($invoke) {
				$callback(isset(self::$configuration[$path]) ? self::$configuration[$path] : $defaultValue);
			}
		}
	}

	}
?>