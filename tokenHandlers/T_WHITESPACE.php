<?php

	/****************************************************************/
	/* Moody                                                        */
	/* moodyException.class.php                                     */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\TokenHandlers;
	
	class whitespaceHandler implements \Moody\TokenHandler {
		private static $instance = null;
	
		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}
	
		private function __construct() {
			\Moody\TokenVM::globalRegisterTokenHandler(T_WHITESPACE, $this);
		}
	
		public function execute(\Moody\Token $token, \Moody\TokenVM $vm) {
			$token->content = "";
				
			return \Moody\TokenVM::NEXT_HANDLER | \Moody\TokenVM::NEXT_TOKEN;
		}
	}
?>