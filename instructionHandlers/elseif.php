<?php

	/****************************************************************/
	/* Moody                                                        */
	/* elseif.php                 					            	*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\InstructionHandlers;
	
	use Moody\InstructionProcessorException;
	use Moody\IfInstruction;
	use Moody\InstructionHandlerWithRegister;
	use Moody\Token;
	use Moody\TokenHandlers\InstructionProcessor;
	use Moody\TokenVM;
	
	class ElseIfHandler implements InstructionHandlerWithRegister {
		private static $instance = null;
	
		private function __construct() {
			InstructionProcessor::getInstance()->registerHandler('elseif', $this);
			InstructionProcessor::getInstance()->registerHandler('elif', $this);
		}
	
		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}
	
		public function execute(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm) {
			return IfHandler::getInstance()->execute($token, $instructionName, $processor, $vm);
		}
		
		public function register(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm) {
			IfInstruction::setEndToken($token);
			new IfInstruction($token);
		}
	}
?>