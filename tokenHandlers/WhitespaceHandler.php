<?php

	/****************************************************************/
	/* Moody                                                        */
	/* WhitespaceHandler.php                                     		*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/

	namespace Moody\TokenHandlers {

	use Moody\TokenHandler;
	use Moody\TokenVM;
	use Moody\Configuration;
	use Moody\Token;

	class WhitespaceHandler implements TokenHandler {
		private static $instance = null;
		private $enabled = false;
		private static $tokens = array(T_WHITESPACE, T_ECHO, T_VARIABLE, T_GOTO, T_ELSE, T_NAMESPACE, T_CONST,
										T_NEW, T_INSTANCEOF, T_INSTEADOF, T_STRING, T_CLASS, T_EXTENDS, T_PUBLIC, T_PROTECTED,
										T_PRIVATE, T_FINAL, T_STATIC, T_FUNCTION, T_RETURN, T_CASE, T_START_HEREDOC, T_SEMICOLON,
										T_END_HEREDOC, T_BREAK, T_CONTINUE, T_USE, T_THROW, T_INTERFACE, T_TRAIT, T_IMPLEMENTS);

		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}

		private function __construct() {
			if(Configuration::get('supportwhitespacedeletion', true)) {
				Configuration::registerCallback('deletewhitespaces', false, array($this, 'invoke'));
			}
		}

		public function invoke($value, TokenVM $tokenVM = null) {
			if(!$value && $this->enabled) {
				$this->enabled = false;
				if($tokenVM) {
					foreach(self::$tokens as $token)
						$tokenVM->unregisterTokenHandler($token, $this);
				} else {
					foreach(self::$tokens as $token)
						TokenVM::globalUnregisterTokenHandler($token, $this);
				}
			} else if($value && !$this->enabled) {
				$this->enabled = true;
				if($tokenVM) {
					foreach(self::$tokens as $token)
						$tokenVM->registerTokenHandler($token, $this);
				} else {
					foreach(self::$tokens as $token)
						TokenVM::globalRegisterTokenHandler($token, $this);
				}
			}
		}

		public function execute(Token $token, TokenVM $vm) {
			switch($token->type) {
				case T_WHITESPACE:
					$tokenArray = $vm->getTokenArray();

					if(($tokenX = current($tokenArray)) && $tokenX->type == T_END_HEREDOC)
						$this->insertForcedWhitespace($vm, true);
					return TokenVM::NEXT_HANDLER | TokenVM::NEXT_TOKEN | TokenVM::DELETE_TOKEN;
				case T_ECHO:
				case T_RETURN:
				case T_PUBLIC:
				case T_PROTECTED:
				case T_PRIVATE:
				case T_STATIC:
				case T_FINAL:
				case T_CASE:
				case T_CONTINUE:
				case T_BREAK:
				case T_THROW:
					$tokenArray = $vm->getTokenArray();

					if($tokenX = current($tokenArray)) {
						if($tokenX->type != T_WHITESPACE)
							return TokenVM::NEXT_HANDLER | TokenVM::NEXT_TOKEN;
						else if(($tokenX = next($tokenArray)) && $tokenX->type != T_CONSTANT_ENCAPSED_STRING && $tokenX->type != T_VARIABLE)
							$this->insertForcedWhitespace($vm);
					}
					break;
				case T_VARIABLE:
					$tokenArray = $vm->getTokenArray();

					if($tokenX = current($tokenArray)) {
						if($tokenX->type != T_WHITESPACE)
							return TokenVM::NEXT_HANDLER | TokenVM::NEXT_TOKEN;
						else if(($tokenX = next($tokenArray)) && ($tokenX->type == T_AS || $tokenX->type == T_INSTANCEOF))
							$this->insertForcedWhitespace($vm);
					}
					break;
				case T_GOTO:
				case T_NAMESPACE:
				case T_CONST:
				case T_NEW:
				case T_INSTANCEOF:
				case T_INSTEADOF:
				case T_CLASS:
				case T_EXTENDS:
				case T_FUNCTION:
				case T_START_HEREDOC:
				case T_USE:
				case T_INTERFACE:
				case T_TRAIT:
				case T_IMPLEMENTS:
					$this->insertForcedWhitespace($vm);
					break;
				case T_ELSE:
					$tokenArray = $vm->getTokenArray();

					if($tokenX = current($tokenArray)) {
						if($tokenX->type != T_WHITESPACE)
							return TokenVM::NEXT_HANDLER | TokenVM::NEXT_TOKEN;
						else if(($tokenX = next($tokenArray)) && $tokenX->type != T_CURLY_OPEN)
							$this->insertForcedWhitespace($vm);
					}
					break;
				case T_STRING:
					$tokenArray = $vm->getTokenArray();

					if($tokenX = current($tokenArray)) {
						if($tokenX->type != T_WHITESPACE)
							return TokenVM::NEXT_HANDLER | TokenVM::NEXT_TOKEN;
						else if(($tokenX = next($tokenArray)) && ($tokenX->type == T_EXTENDS || $tokenX->type == T_INSTEADOF || $tokenX->type == T_INSTANCEOF || $tokenX->type == T_AS || $tokenX->type == T_IMPLEMENTS))
							$this->insertForcedWhitespace($vm);
					}
					break;
				case T_SEMICOLON:
					$tokenArray = $vm->getTokenArray();

					prev($tokenArray);
					$tokenX = prev($tokenArray);

					if($tokenX->type == T_END_HEREDOC)
						$this->insertForcedWhitespace($vm, true);
					break;
				case T_END_HEREDOC:
					$tokenArray = $vm->getTokenArray();

					if(($tokenX = current($tokenArray)) && $tokenX->type != T_SEMICOLON)
						$this->insertForcedWhitespace($vm, true);
					break;
			}

			end:

			return TokenVM::NEXT_HANDLER | TokenVM::NEXT_TOKEN;
		}

		private function insertForcedWhitespace(TokenVM $vm, $lineBreak = false) {
			$token = new Token;
			$token->content = $lineBreak ? "\r\n" : " ";
			$token->type = T_FORCED_WHITESPACE;
			$token->fileName = "Moody WhitespaceHandler";
			$vm->insertTokenArray(array($token));
		}
	}

	}
?>