<?php

	/****************************************************************/
	/* Moody                                                        */
	/* if.php                 					                	*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\InstructionHandlers {
	
	use Moody\END_TOKEN_ELSE;
	use Moody\InstructionProcessorException;
	use Moody\MultiTokenInstruction;
	use Moody\InstructionHandlerWithRegister;
	use Moody\ConstantContainer;
	use Moody\Token;
	use Moody\TokenHandlers\InstructionProcessor;
	use Moody\TokenVM;
	
	class IfHandler implements InstructionHandlerWithRegister {
		private static $instance = null;
	
		private function __construct() {
			InstructionProcessor::getInstance()->registerHandler('if', $this);
		}
	
		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}
	
		public function execute(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm) {
			$args = $processor->parseArguments($token, $instructionName, 'x');
			
			// Search jump point
			foreach(MultiTokenInstruction::getAll('if') as $instruction) {
				if($instruction->getToken() == $token) {
					if(!($instruction->getEndToken() instanceof Token))
						throw new InstructionProcessorException('Invalid end token for ' . $instructionName . ' - Probably you forgot an endif?', $token);
			
					$cond = "";

					// Build parsable condition
					foreach($args as $arg) {
						if(is_string($arg) && strlen($arg) && strtolower($arg) !== "true" && strtolower($arg) !== "false") {
							$tokens = Token::tokenize('<?php ' . $arg . ' ?>');
							if($tokens[1]->type == T_STRING)
								$arg = Token::makeEvaluatable($arg);
						} else if(is_string($arg) && !strlen($arg))
							$arg = "''";
							
						if($arg === true)
							$arg = "true";
						else if($arg === false)
							$arg = "false";
						else if($arg === null)
							$arg = "null";
						
						$cond .= $arg;
					}

					$result = eval('return (int) (bool) (' . $cond . ');');
					
					if($result === 1)  {
						$instruction->setEndTokenAction(\Moody\END_TOKEN_NO_EXECUTE);
						return TokenVM::DELETE_TOKEN;
					}
					if($result === false)
						throw new InstructionProcessorException('If-condition ' . $cond . ' is invalid', $token);
					
					$instruction->setEndTokenAction(\Moody\END_TOKEN_EXECUTE);
					$vm->jump($instruction->getEndToken());
					return TokenVM::JUMP | TokenVM::DELETE_TOKEN;
				}
			}
			
			return TokenVM::ERROR;
		}
	
		public function register(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm) {
			new MultiTokenInstruction($token, 'if');
		}
	}
	
	}
?>
