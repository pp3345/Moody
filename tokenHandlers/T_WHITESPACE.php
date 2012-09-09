<?php

	/****************************************************************/
	/* Moody                                                        */
	/* T_WHITESPACE.php                                     		*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\TokenHandlers;
	
	use Moody\TokenHandler;
	use Moody\TokenVM;
	use Moody\Configuration;
	use Moody\Token;

	class WhitespaceHandler implements TokenHandler {
		private static $instance = null;
	
		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}
	
		private function __construct() {
			TokenVM::globalRegisterTokenHandler(T_WHITESPACE, $this);
			TokenVM::globalRegisterTokenHandler(T_ECHO, $this);
		}
	
		public function execute(Token $token, TokenVM $vm) {
			if(Configuration::get('deletewhitespaces', false)) {
				switch($token->type) {
					case T_WHITESPACE:
						return TokenVM::NEXT_HANDLER | TokenVM::NEXT_TOKEN | TokenVM::DELETE_TOKEN;
					case T_ECHO:
						$tokenArray = $vm->getTokenArray();

						if($tokenX = current($tokenArray)) {
							if($tokenX->type != T_WHITESPACE)
								return TokenVM::NEXT_HANDLER | TokenVM::NEXT_TOKEN;
							else if(($tokenX = next($tokenArray)) && $tokenX->type != T_CONSTANT_ENCAPSED_STRING && $tokenX->type != T_VARIABLE)
								$this->insertForcedWhitespace($vm);
						}
						break;
				}
			}

			return TokenVM::NEXT_HANDLER | TokenVM::NEXT_TOKEN;
		}

		private function insertForcedWhitespace(TokenVM $vm) {
			$token = new Token;
			$token->content = " ";
			$token->type = T_FORCED_WHITESPACE;
			$token->fileName = "Moody WhitespaceHandler";
			$vm->insertTokenArray(array($token));
		}
	}
?>