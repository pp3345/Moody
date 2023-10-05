<?php

	/****************************************************************/
	/* Moody                                                        */
	/* useStatementParser.php                                		*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/

	namespace Moody\TokenHandlers {

	use Moody\ConstantContainer;

	use Moody\TokenHandler;
	use Moody\TokenVM;
	use Moody\Token;
	
	class UseStatementParser implements TokenHandler {
		/**
		 *
		 * @var UseStatementFetcher
		 */
		private static $instance = null;

		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}

		private function __construct() {
			TokenVM::globalRegisterTokenHandler(T_USE, $this);
		}

		public function execute(Token $token, TokenVM $vm) {
			$classFetcher = ClassFetcher::getInstance();
			// This parser is only for namespace use statements, not for traits
			if($classFetcher->getCurrentClass())
				return TokenVM::NEXT_HANDLER | TokenVM::NEXT_TOKEN;
			
			$tokenArray = $vm->getTokenArray();
			$step = T_USE;
			$originalName = "";
			$importName = "";
			
			for($currentToken = current($tokenArray);;$currentToken = next($tokenArray)) {
				switch($currentToken->type) {
					case T_AS:						
						$step = T_AS;
						break;
					case T_NS_SEPARATOR:
						if($step == T_AS) {
							if($importName)
								$importName .= "\\";
						} else if($originalName) {
							$originalName .= "\\";
						}
						break;
					case T_STRING:
						if($step == T_AS)
							$importName .= $currentToken->content;
						else
							$originalName .= $currentToken->content;
						break;
					case T_COMMA:
					case T_SEMICOLON:
						if(!($class = $classFetcher->fetchClass($originalName)))
							break 2;
						
						if($step == T_USE) {
							$namespace = NamespaceFetcher::getInstance()->getCurrentNamespace();
							if($namespace)
								$importName = $namespace . "\\" . $class->localName;
							else
								$importName = $class->localName;
						}
						
						$classFetcher->registerClass(strtolower($importName), $class);
						if($currentToken->type == T_COMMA) {
							$importName = $originalName = "";
							break;
						}
						break 2;
				}
			}
			
			$vm->jump($currentToken);
			return TokenVM::JUMP_WITHOUT_DELETE_TOKEN | TokenVM::NEXT_TOKEN;
		}
	}
	
	}
?>
