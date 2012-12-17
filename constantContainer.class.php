<?php

	/****************************************************************/
	/* Moody                                                        */
	/* constantContainer.class.php                                  */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/

	namespace Moody {

	use Moody\TokenHandlers\ClassEntry;
	use Moody\TokenHandlers\ClassFetcher;
		
	class ConstantContainer {
		private static $constants = array();
		
		public static function initialize() {
			foreach(get_defined_constants() as $constantName => $constantValue)
				if(!self::isDefined($constantName))
					self::define($constantName, $constantValue);
		}
		
		public static function getConstant($name, ClassEntry $class = null) {
			$name = strtolower($name);
			if(strpos($name, '::')) {
				$name = explode('::', $name, 2);
				$class = ClassFetcher::getInstance()->fetchClass($name[0]);
				if($class) {
					do {
						if(isset($class->constants[$name[1]]))
							return $class->constants[$name[1]];
					} while($class = $class->extends);
				}
				return;
			}
			
			if($class) {
				if(isset($class->constants[$name])) {
					return $class->constants[$name];
				}
				return;
			}
			
			if(isset(self::$constants[$name]))
				return self::$constants[$name];
		}
		
		public static function isDefined($name) {
			if(strpos($name, '::')) {
				$name = explode('::', strtolower($name), 2);
				$class = ClassFetcher::getInstance()->fetchClass($name[0]);

				if($class) {
					do {
						if(isset($class->constants[$name[1]]))
							return true;
					} while($class = $class->extends);
				}
				
				return false;
			}
			
			return isset(self::$constants[strtolower($name)]);
		}
		
		public static function define($name, $value, ClassEntry $class = null) {
			if($class) {
				$class->constants[strtolower($name)] = $value;
			} else {
				self::$constants[strtolower($name)] = $value;
			}
		}
		
		public static function undefine($name) {
			$name = strtolower($name);
			if(isset(self::$constants[$name]))
				unset(self::$constants[$name]);
		}
	}
	
	}
?>