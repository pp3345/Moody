<?php

	/****************************************************************/
	/* Moody                                                        */
	/* endLongDefine.php                 					        */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\InstructionHandlers {
		use Moody\InstructionHandlerWithRegister;
		use Moody\Token;
		use Moody\TokenHandlers\InstructionProcessor;
		use Moody\TokenVM;

		use Moody\MultiTokenInstruction;
		use Moody\ConstantContainer;
	
		class EndLongDefineHandler implements InstructionHandlerWithRegister {
			private static $instance = null;
				
			private function __construct() {
				InstructionProcessor::getInstance()->registerHandler('endlongdefine', $this);
			}
				
			public static function getInstance() {
				if(!self::$instance)
					self::$instance = new self;
				return self::$instance;
			}
				
			public function execute(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm = null, $executionType = 0) {
				return TokenVM::DELETE_TOKEN;
			}
				
			public function register(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm) {
				MultiTokenInstruction::setEndToken($token, 'longDefine');
			}
		}
	}
?>