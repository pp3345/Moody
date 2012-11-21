<?php

	/****************************************************************/
	/* Moody                                                        */
	/* ifndef.php                 					                */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\InstructionHandlers {
	
	use Moody\InstructionProcessorException;
	use Moody\MultiTokenInstruction;
	use Moody\InstructionHandlerWithRegister;
	use Moody\ConstantContainer;
	use Moody\Token;
	use Moody\TokenHandlers\InstructionProcessor;
	use Moody\TokenVM;
	
	class IfNotDefHandler implements InstructionHandlerWithRegister {
		private static $instance = null;
	
		private function __construct() {
			InstructionProcessor::getInstance()->registerHandler('ifndef', $this);
			InstructionProcessor::getInstance()->registerHandler('ifnotdefined', $this);
		}
	
		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}
	
		public function execute(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm) {
			$args = $processor->parseArguments($token, $instructionName, 's');
				
			// Search jump point
			foreach(MultiTokenInstruction::getAll('if') as $instruction) {
				if($instruction->getToken() == $token) {
					if(!($instruction->getEndToken() instanceof Token))
						throw new InstructionProcessorException('Invalid end token for ' . $instructionName . ' - Probably you forgot an endif?', $token);
						
					if(!ConstantContainer::isDefined($args[0])) {
						$instruction->setEndTokenAction(\Moody\END_TOKEN_NO_EXECUTE);
						return TokenVM::DELETE_TOKEN;
					}
						
					$vm->jump($instruction->getEndToken());
					$instruction->setEndTokenAction(\Moody\END_TOKEN_EXECUTE);
					return TokenVM::JUMP | TokenVM::DELETE_TOKEN;
				}
			}
		}
	
		public function register(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm) {
			new MultiTokenInstruction($token, 'if');
		}
	}
	
	}
?>