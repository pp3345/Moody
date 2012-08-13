<?php

	/****************************************************************/
	/* Moody                                                        */
	/* configuration.class.php                                      */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody;
	
	class Configuration {
		private static $configuration = array();
		
		public static function load($string) {
			
		}
		
		public static function get($path, $defaultValue = null) {
			return $defaultValue;
		}
	}
?>