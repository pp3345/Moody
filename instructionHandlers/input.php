<?php

	/****************************************************************/
	/* Moody                                                        */
	/* input.php                 					            	*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\InstructionHandlers {
	
	use Moody\InstructionProcessorException;
	use Moody\InlineInstructionHandler;
	use Moody\Token;
	use Moody\TokenHandlers\InstructionProcessor;
	use Moody\TokenVM;
	
	class InputHandler implements InlineInstructionHandler {
		private static $instance = null;
	
		private function __construct() {
			InstructionProcessor::getInstance()->registerHandler('input', $this);
			InstructionProcessor::getInstance()->registerHandler('getinput', $this);
		}
	
		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}
	
		public function execute(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm = null, $executionType = 0) {
			$args = $processor->parseArguments($token, $instructionName, '?n');

			if(!defined('STDIN') || !is_resource(\STDIN))
				throw new InstructionProcessorException('No input stream available', $token);
			
			$value = fread(\STDIN, isset($args[0]) ? $args[0] : 1024);
			
			if($executionType & InstructionProcessor::EXECUTE_TYPE_INLINE)
				return $value;
			
			$token->content = Token::makeEvaluatable($value);
			
			return 0;
		}
	}
	
	}
?>