<?php

	/****************************************************************/
	/* Moody                                                        */
	/* constantContainer.class.php                                  */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/

	namespace Moody {

	use Moody\TokenHandlers\ClassEntry;
	use Moody\TokenHandlers\ClassFetcher;
	use Moody\TokenHandlers\NamespaceFetcher;

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

				if(!($namespace = NamespaceFetcher::getInstance()->getCurrentNamespace())
				|| ($namespace
					&& !($class = ClassFetcher::getInstance()->fetchClass($namespace . '\\' . $name[0])))) {
					$class = ClassFetcher::getInstance()->fetchClass($name[0]);
				}

				if($class) {
					do {
						if(isset($class->constants[$name[1]]))
							return $class->constants[$name[1]];
					} while($class = $class->extends);
				}
				return;
			}

			if($class) {
				do {
					if(isset($class->constants[$name]))
						return $class->constants[$name];
				} while($class = $class->extends);
				return;
			}

			$namespace = NamespaceFetcher::getInstance()->getCurrentNamespace();

			if($namespace && isset(self::$constants[$namespace . "\\" . $name]))
				return self::$constants[$namespace . "\\" . $name];

			if(isset(self::$constants[$name]))
				return self::$constants[$name];
		}

		public static function isDefined($name, ClassEntry $class = null) {
			$name = strtolower($name);

			if($class) {
				do {
					if(isset($class->constants[$name]))
						return true;
				} while($class = $class->extends);

				return false;
			}

			if(strpos($name, '::')) {
				$name = explode('::', $name, 2);
				if(!($namespace = NamespaceFetcher::getInstance()->getCurrentNamespace())
				|| ($namespace
					&& !($class = ClassFetcher::getInstance()->fetchClass($namespace . '\\' . $name[0])))) {
					$class = ClassFetcher::getInstance()->fetchClass($name[0]);
				}

				if($class) {
					do {
						if(isset($class->constants[$name[1]]))
							return true;
					} while($class = $class->extends);
				}

				return false;
			}

			$namespace = NamespaceFetcher::getInstance()->getCurrentNamespace();

			if($namespace && isset(self::$constants[$namespace . "\\" . $name]))
				return true;

			return isset(self::$constants[$name]);
		}

		public static function define($name, $value, ClassEntry $class = null, $namespace = false) {
			if($class) {
				$class->constants[strtolower($name)] = $value;
			} else {
				if($namespace && $namespace = NamespaceFetcher::getInstance()->getCurrentNamespace())
					$name = $namespace . "\\" . strtolower($name);
				else
					$name = strtolower($name);

				self::$constants[$name] = $value;
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
