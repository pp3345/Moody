<?php

	/****************************************************************/
	/* Moody                                                        */
	/* constantDefinitionParser.php                                	*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/

	namespace Moody\TokenHandlers {

	use Moody\TokenHandler;
	use Moody\TokenVM;
	use Moody\Token;
	use Moody\ConstantContainer;

	class ConstantDefinitionHandler implements TokenHandler {
		private static $instance = null;

		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}

		private function __construct() {
			TokenVM::globalRegisterTokenHandler(T_CONST, $this);
		}

		public function execute(Token $token, TokenVM $vm) {
			$tokenArray = $vm->getTokenArray();

			$rval = false;

			$class = ClassFetcher::getInstance()->getCurrentClass();

			// Although the pointer of the VM token array always points to the next token we can simply use next() because there always has to be a T_WHITESPACE after a T_CONST
			while($currentToken = next($tokenArray)) {
				switch($currentToken->type) {
					case T_SEMICOLON:
						break 2;
					case T_EQUAL:
						$rval = true;
						/* fallthrough */
					case T_WHITESPACE:
						continue 2;
					case T_STRING:
						if($rval) {
							if(ConstantContainer::isDefined($currentToken->content)) {
								ConstantContainer::define($name, ConstantContainer::getConstant($currentToken->content), $class, true);
							} else {
								ConstantContainer::define($name, $currentToken->content, $class, true);
							}
						} else {
							$name = $currentToken->content;
						}
						break;
					case T_LNUMBER:
						ConstantContainer::define($name, (int) $currentToken->content, $class, true);
						break;
					case T_DNUMBER:
						ConstantContainer::define($name, (float) $currentToken->content, $class, true);
						break;
					case T_TRUE:
						ConstantContainer::define($name, true, $class, true);
						break;
					case T_FALSE:
						ConstantContainer::define($name, false, $class, true);
						break;
					case T_CONSTANT_ENCAPSED_STRING:
						ConstantContainer::define($name, eval('return (' . $currentToken->content . ');'), $class, true);
						break;
					case T_COMMA:
						$rval = false;
				}
			}

			$vm->jump($currentToken);

			return TokenVM::JUMP_WITHOUT_DELETE_TOKEN | TokenVM::NEXT_HANDLER | TokenVM::NEXT_TOKEN;
		}
	}

	}
?>