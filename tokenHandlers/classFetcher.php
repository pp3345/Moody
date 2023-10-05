<?php

	/****************************************************************/
	/* Moody                                                        */
	/* classFetcher.php                                				*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/

	namespace Moody\TokenHandlers {

	use Moody\TokenHandler;
	use Moody\TokenVM;
	use Moody\Token;
			
	class ClassEntry {
		public $implements = array();
		public $name = "";
		public $localName = "";
		public $extends = null;
		public $constants = array();
	}
	
	class ClassFetcher implements TokenHandler {
		/**
		 *
		 * @var ClassFetcher
		 */
		private static $instance = null;
		/**
		 * 
		 * @var Class
		 */
		private $currentClass = null;
		private $classes = array();

		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}

		private function __construct() {
			TokenVM::globalRegisterTokenHandler(T_CLASS, $this);
		}

		public function execute(Token $token, TokenVM $vm) {
			$tokenArray = $vm->getTokenArray();
			
			$step = T_CLASS;
			
			$class = new ClassEntry;
			
			while($currentToken = next($tokenArray)) {
				switch($currentToken->type) {
					case T_IMPLEMENTS:
						$step = T_IMPLEMENTS;
						break;
					case T_EXTENDS:
						$step = T_EXTENDS;
						break;
					case T_STRING:
						switch($step) {
							case T_CLASS:
								$lower = strtolower($currentToken->content);
								if($namespace = NamespaceFetcher::getInstance()->getCurrentNamespace())
									$class->name = $namespace . "\\" . $lower;
								else
									$class->name = $lower;
								$class->localName = $lower;
								break;
							case T_EXTENDS:
								$class->extends = $this->fetchClass(strtolower($currentToken->content));
								break;
							case T_IMPLEMENTS:
								$class->implements[] = strtolower($currentToken->content);
						}
						break;
					case T_CURLY_BRACKET_OPEN:
						break 2;
				}
			}
						
			$this->currentClass = $this->classes[$class->name] = $class;
			
			$scopeFetcher = ScopeFetcher::getInstance();
			
			$scopeFetcher->addLeaveCallback(array($this, 'leaveClass'), $scopeFetcher->getDepth() + 1);
			
			$vm->jump($currentToken);
			
			return TokenVM::JUMP_WITHOUT_DELETE_TOKEN | TokenVM::NEXT_HANDLER | TokenVM::NEXT_TOKEN;
		}
		
		public function getCurrentClass() {
			return $this->currentClass;
		}
		
		public function leaveClass() {
			$this->currentClass = null;
		}
		
		public function fetchClass($name) {
			$name = strtolower($name);
			switch($name) {
				case "self":
					return $this->currentClass;
				case "parent":
					return $this->currentClass ? $this->currentClass->extends : null;
				default:
					return isset($this->classes[$name]) ? $this->classes[$name] : null;
			}
		}
		
		public function registerClass($name, ClassEntry $classEntry) {
			$this->classes[$name] = $classEntry;
		}
	}
	
	}
?>
