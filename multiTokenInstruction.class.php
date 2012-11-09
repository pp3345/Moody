<?php

	/****************************************************************/
	/* Moody                                                        */
	/* multiTokenInstruction.class.php                 				*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody {
	
	const END_TOKEN_NO_EXECUTE = 1;
	const END_TOKEN_EXECUTE = 2;
	
	class MultiTokenInstruction {
		private static $instructionStack = array();
		private static $nestedStack = array();
		private $token;
		private $endToken;
		private $endTokenAction = END_TOKEN_EXECUTE;
		
		public function __construct(Token $token, $class) {
			self::$instructionStack[$class][] = $this;
			self::$nestedStack[$class][] = $this;
			$this->token = $token;
		}
		
		public static function setEndToken(Token $token, $class) {
			if(!isset(self::$nestedStack[$class]) || !self::$nestedStack[$class])
				throw new InstructionProcessorException('End token of type ' . $class . ' while not active', $token);
			
			end(self::$nestedStack[$class]);
			
			self::$nestedStack[$class][key(self::$nestedStack[$class])]->endToken = $token;
			unset(self::$nestedStack[$class][key(self::$nestedStack[$class])]);
		}
		
		public function getToken() {
			return $this->token;
		}
		
		public function getEndToken() {
			return $this->endToken;
		}
		
		public function setEndTokenAction($action) {
			$this->endTokenAction = $action;
		}
		
		public function getEndTokenAction() {
			return $this->endTokenAction;
		}
		
		public static function getAll($class) {
			return self::$instructionStack[$class];
		}
	}
	
	}
?>