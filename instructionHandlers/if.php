<?php

	/****************************************************************/
	/* Moody                                                        */
	/* if.php                 					                	*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\InstructionHandlers;
	
	use Moody\InstructionProcessorException;
	use Moody\IfInstruction;
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
			foreach(IfInstruction::getAll() as $instruction) {
				if($instruction->getToken() == $token) {
					if(!($instruction->getEndToken() instanceof Token))
						throw new InstructionProcessorException('Invalid end token for ' . $instructionName . ' - Probably you forgot an endif?', $token);
			
					$cond = "";
					$result = false;
			
					// Build parsable condition
					foreach($args as $arg) {
						if(is_string($arg) && strtolower($arg) !== "true" && strtolower($arg) !== "false") {
							$tokens = Token::tokenize('<?php ' . $arg . ' ?>');
							if($tokens[1]->type == T_STRING)
								$arg = Token::makeEvaluatable($arg);
						}
						
						if($arg === true)
							$arg = "true";
						if($arg === false)
							$arg = "false";
						
						$cond .= $arg;
					}

					eval('$result = (bool) (' . $cond . ');');
					
					if($result === true)
						return TokenVM::DELETE_TOKEN;
			
					$vm->jump($instruction->getEndToken());
					return TokenVM::JUMP | TokenVM::DELETE_TOKEN;
				}
			}
		}
	
		public function register(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm) {
			new IfInstruction($token);
		}
	}
?>