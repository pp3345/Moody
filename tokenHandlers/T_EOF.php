<?php

	/****************************************************************/
	/* Moody                                                        */
	/* T_EOF.php                                					*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/

	namespace Moody\TokenHandlers {
		
	use Moody\TokenHandler;
	use Moody\TokenVM;
	use Moody\Token;
	
	class EOFDeleter implements TokenHandler {
		private static $instance = null;

		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}

		private function __construct() {
			TokenVM::globalRegisterTokenHandler(T_EOF, $this);
		}

		public function execute(Token $token, TokenVM $vm) {
			return TokenVM::DELETE_TOKEN | TokenVM::NEXT_TOKEN;
		}
	}
	
	}
?>