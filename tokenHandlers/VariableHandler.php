<?php

	/****************************************************************/
	/* Moody                                                        */
	/* VariableHandler.php                                     		*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/

	namespace Moody\TokenHandlers {

	use Moody\TokenHandler;
	use Moody\TokenVM;
	use Moody\Token;
	use Moody\Configuration;

	/**
	 * Variable name compression handler
	 */
	class VariableHandler implements TokenHandler {
		private static $instance = null;
		private static $tokens = array(T_VARIABLE, T_OBJECT_OPERATOR);
		private $variableMappings = array();
		private $untouchable = array();
		private $nextLetter = "A";
		private $enabled = false;

		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}

		private function __construct() {
			Configuration::registerCallback('compressvariables', false, array($this, 'invoke'));
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
			static $forbiddenVariables = array('$this', '$_GET', '$_POST', '$_REQUEST', '$_COOKIE', '$_ENV', '$_SESSION', '$_SERVER', '$_FILES');

			if(!in_array($token->content, $forbiddenVariables)) {
				if($token->type == T_OBJECT_OPERATOR) {
					if(!Configuration::get('compressproperties', false))
						goto end;
					$tokenArray = $vm->getTokenArray();

					$varToken = current($tokenArray);
					if($varToken->type != T_STRING)
						goto end;
					while($tokenX = next($tokenArray)) {
						if($tokenX->type == T_WHITESPACE)
							continue;
						if($tokenX->type == T_ROUND_BRACKET_OPEN)
							goto end;
						break;
					}

					$localToken = $varToken;

					$localToken->content = '$' . $localToken->content;
					$objectvar = true;
					$classaccess = true;
				} else {
					$tokenArray = $vm->getTokenArray();
					prev($tokenArray);
					while($tokenX = prev($tokenArray)) {
						if($tokenX->type == T_WHITESPACE)
							continue;
						if($tokenX->type == T_PAAMAYIM_NEKUDOTAYIM)
							$classaccess = true;
						break;
					}

					$localToken = $token;
				}

				if(isset($this->untouchable[$localToken->content]))
					goto end;

				if(!isset($this->variableMappings[$localToken->content])) {
					if(!Configuration::get('compressaccessible', false) && isset($classaccess)) {
						$this->untouchable[$localToken->content] = true;
						goto end;
					}

					if(!Configuration::get('compressproperties', false) || !Configuration::get('compressaccessible', false)) {
						$tokenArray = $vm->getTokenArray();
						prev($tokenArray);
						while($tokenX = prev($tokenArray)) {
							switch($tokenX->type) {
								case T_STATIC:
									$static = true;
									$prop = true;
									continue 2;
								default:
									if(isset($prop)) {
										if(!isset($static) && !Configuration::get('compressproperties', false))
											goto end;
										if(!isset($private) && !Configuration::get('compressaccessible', false)) {
											$this->untouchable[$localToken->content] = true;
											goto end;
										}
									}
									goto map;
								case T_PRIVATE:
									$private = true;
								case T_PUBLIC:
								case T_PROTECTED:
									$prop = true;
								case T_WHITESPACE:
								case T_FORCED_WHITESPACE:
									continue 2;
							}
						}

					}

					map:

					do {
						$this->mapVariable($localToken->content, is_int($this->nextLetter) ? '$i' . $this->nextLetter : '$' . $this->nextLetter);

						if($this->nextLetter === "Z")
							$this->nextLetter = "a";
						else if($this->nextLetter === "z")
							$this->nextLetter = 0;
						else if(is_int($this->nextLetter))
							$this->nextLetter++;
						else
							$this->nextLetter = chr(ord($this->nextLetter) + 1);
					} while(count(array_keys($this->variableMappings, $this->variableMappings[$localToken->content])) > 1);
				}

				$localToken->content = $this->variableMappings[$localToken->content];

				end:

				if(isset($objectvar)) {
					$localToken->content = substr($localToken->content, 1);
				}
			}

			return TokenVM::NEXT_HANDLER | TokenVM::NEXT_TOKEN;
		}

		public function mapVariable($originalVariable, $newName) {
			return $this->variableMappings[$originalVariable] = $newName;
		}
	}

	}
?>