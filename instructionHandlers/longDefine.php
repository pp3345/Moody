<?php

	/****************************************************************/
	/* Moody                                                        */
	/* longDefine.php                 					            */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\InstructionHandlers {
		use Moody\InstructionHandlerWithRegister;
		use Moody\Token;
		use Moody\TokenHandlers\InstructionProcessor;
		use Moody\TokenVM;
		use Moody\InstructionProcessorException;
		use Moody\MultiTokenInstruction;
		use Moody\ConstantContainer;
	
		class LongDefineHandler implements InstructionHandlerWithRegister {
			private static $instance = null;
			
			private function __construct() {
				InstructionProcessor::getInstance()->registerHandler('longdefine', $this);
			}
			
			public static function getInstance() {
				if(!self::$instance)
					self::$instance = new self;
				return self::$instance;
			}
			
			public function execute(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm = null, $executionType = 0) {
				$args = $processor->parseArguments($token, $instructionName, 's');
				
				foreach(MultiTokenInstruction::getAll('longDefine') as $instruction) {
					if($instruction->getToken() == $token) {
						$endToken = $instruction->getEndToken();
						
						if(!($endToken instanceof Token))
							throw new InstructionProcessorException('Invalid end token for ' . $instructionName . ' - Probably you forgot an endLongDefine?', $token);
						
						$tokens = $vm->getTokenArray();
						
						$data = "";
						
						while($token = current($tokens)) {
							if($token == $endToken) {
								ConstantContainer::define($args[0], $data);
								$vm->jump($token);
								return TokenVM::JUMP | TokenVM::DELETE_TOKEN;
							}
							
							$data .= $token->content;
							next($tokens);
						}
					}
				}
				
				return TokenVM::ERROR;
			}
			
			public function register(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm) {
				new MultiTokenInstruction($token, 'longDefine');
			}
		}
	}
?>
