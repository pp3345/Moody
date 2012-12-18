<?php

	/****************************************************************/
	/* Moody                                                        */
	/* namespaceFetcher.php                                			*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/

	namespace Moody\TokenHandlers {

	use Moody\TokenHandler;
	use Moody\TokenVM;
	use Moody\Token;
				
	class NamespaceFetcher implements TokenHandler {
		/**
		 * 
		 * @var NamespaceFetcher
		 */
		private static $instance = null;
		private $currentNamespace = array("");

		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}

		private function __construct() {
			TokenVM::globalRegisterTokenHandler(T_NAMESPACE, $this);
		}

		public function execute(Token $token, TokenVM $vm) {
			if($token->type == T_NAMESPACE) {
				$tokenArray = $vm->getTokenArray();
				
				$namespace = "";
				
				for($token = current($tokenArray);;$token = next($tokenArray)) {
					switch($token->type) {
						case T_NS_SEPARATOR:
							if($namespace)
								$namespace .= "\\";
							break;
						case T_STRING:
							$namespace .= strtolower($token->content);
							break;
						case T_SEMICOLON:
							$vm->registerTokenHandler(T_EOF, $this);
							break 2;
						case T_CURLY_BRACKET_OPEN:
							$scopeFetcher = ScopeFetcher::getInstance();
							$scopeFetcher->addLeaveCallback(array($this, 'leaveNamespace'), $scopeFetcher->getDepth() + 1);
							break 2;
					}
				}
				
				$this->currentNamespace[] = $namespace;
			} else { // T_EOF
				$this->leaveNamespace();
				
				if(count($this->currentNamespace) == 1)
					$vm->unregisterTokenHandler(T_EOF, $this);

				return TokenVM::DELETE_TOKEN | TokenVM::NEXT_TOKEN;
			}
			
			return TokenVM::NEXT_HANDLER | TokenVM::NEXT_TOKEN;
		}
		
		public function leaveNamespace() {
			end($this->currentNamespace);
			unset($this->currentNamespace[key($this->currentNamespace)]);
		}
		
		public function getCurrentNamespace() {
			return end($this->currentNamespace);
		}
	}
	
	}
?>