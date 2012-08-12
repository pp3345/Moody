<?php

	/****************************************************************/
	/* Moody                                                        */
	/* moodyException.class.php                                     */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\TokenHandlers;
	
	class openTagHandler {
		private static $instance = null;
		
		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}
		
		private function __construct() {
			\Moody\TokenVM::globalRegisterTokenHandler(T_OPEN_TAG, $this);
		}
		
		public function execute(\Moody\Token $token) {
			if($token->content == '<?' || $token->content == '<%')
				$token->content = '<?php';
			
			return \Moody\TokenVM::NEXT_HANDLER | \Moody\TokenVM::NEXT_TOKEN;
		}
	}
?>