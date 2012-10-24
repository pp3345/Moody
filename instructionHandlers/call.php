<?php

	/****************************************************************/
	/* Moody                                                        */
	/* call.php                 					        		*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\InstructionHandlers {
	
		use Moody\InstructionProcessorException;
		use Moody\InlineInstructionHandler;
		use Moody\Token;
		use Moody\TokenHandlers\InstructionProcessor;
		use Moody\TokenVM;
	
		class CallHandler implements InlineInstructionHandler {
			private static $instance = null;
	
			private function __construct() {
				InstructionProcessor::getInstance()->registerHandler('call', $this);
			}
	
			public static function getInstance() {
				if(!self::$instance)
					self::$instance = new self;
				return self::$instance;
			}
	
			public function execute(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm = null, $inline = false) {
				$args = $processor->parseArguments($token, $instructionName, 's');
				
				if(strpos($args[0], '::')) {
					$parts = explode('::', $args[0], 2);
					$function = array($parts[0], $parts[1]);
					if(!method_exists($parts[0], $parts[1]))
						throw new InstructionProcessorException($args[0] . '() does not exist', $token);
				} else {
					$function = $args[0];
					if(!function_exists($function))
						throw new InstructionProcessorException($args[0] . '() does not exist', $token);
				}
				
				if(!is_callable($function))
					throw new InstructionProcessorException($args[0] . '() is not callable from the current scope', $token);
				
				$parameters = $args;
				unset($parameters[0]);
				
				$value = call_user_func_array($function, $parameters);
				
				if($inline)
					return $value;
				
				$token->content = Token::makeEvaluatable($value);
				
				return TokenVM::DELETE_TOKEN;
			}
	
			public function inlineExecute(Token $token, $instructionName, InstructionProcessor $processor) {
				return $this->execute($token, $instructionName, $processor, null, true);
			}
		}
	
	}
?>