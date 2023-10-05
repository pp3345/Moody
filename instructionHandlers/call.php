<?php

	/****************************************************************/
	/* Moody                                                        */
	/* call.php                 					        		*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\InstructionHandlers {
	
		use Moody\InstructionProcessorException;
		use Moody\InlineInstructionHandler;
		use Moody\DefaultInstructionHandler;
		use Moody\Token;
		use Moody\TokenHandlers\InstructionProcessor;
		use Moody\TokenVM;
	
		class CallHandler implements InlineInstructionHandler, DefaultInstructionHandler {
			private static $instance = null;
	
			private function __construct() {
				InstructionProcessor::getInstance()->registerHandler('call', $this);
				InstructionProcessor::getInstance()->registerDefaultHandler($this);
			}
	
			public static function getInstance() {
				if(!self::$instance)
					self::$instance = new self;
				return self::$instance;
			}
	
			public function execute(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm = null, $executionType = 0) {
				if($executionType & InstructionProcessor::EXECUTE_TYPE_DEFAULT) {
					$function = $instructionName;
					$args = $processor->parseArguments($token, $instructionName, '');
					$args = array_merge(array($function), $args);
				} else {
					$args = $processor->parseArguments($token, $instructionName, 's');
					$function = $args[0];
				}
				
				if(strpos($function, '::')) {
					$parts = explode('::', $function, 2);
					$function = array($parts[0], $parts[1]);
					if(!method_exists($parts[0], $parts[1]))
						throw new InstructionProcessorException($args[0] . '() does not exist', $token);
				} else if(!function_exists($function))
						throw new InstructionProcessorException($args[0] . '() does not exist', $token);
				
				if(!is_callable($function))
					throw new InstructionProcessorException($args[0] . '() is not callable from the current scope', $token);
				
				$parameters = $args;
				unset($parameters[0]);

				$value = call_user_func_array($function, $parameters);
				
				if($executionType & InstructionProcessor::EXECUTE_TYPE_INLINE)
					return $value;
				
				$token->content = Token::makeEvaluatable($value);
				
				return 0;
			}

			public function canExecute(Token $token, $instructionName, InstructionProcessor $processor) {
				if(strpos($instructionName, '::')) {
					$parts = explode('::', $instructionName, 2);
					if(!method_exists($parts[0], $parts[1]))
						return false;
				} else if(!function_exists($instructionName))
						return false;
				return true;
			}
		}
	
	}
?>
