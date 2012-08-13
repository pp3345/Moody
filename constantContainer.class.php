<?php

	/****************************************************************/
	/* Moody                                                        */
	/* tokenVM.class.php                                            */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/

	namespace Moody;

	class ConstantContainer {
		private static $constants = array();
		
		public static function getConstant($name) {
			if(isset(self::$constants[$name]))
				return self::$constants[$name];
		}
		
		public static function isDefined($name) {
			return isset(self::$constants[$name]);
		}
		
		public static function define($name, $value) {
			self::$constants[$name] = $value;
		}
		
		public static function undefine($name) {
			if(isset(self::$constants[$name]))
				unset(self::$constants[$name]);
		}
	}
?>