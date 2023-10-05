<?php

	/****************************************************************/
	/* Moody                                                        */
	/* isdefined.php                 					            */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\InstructionHandlers {


		use Moody\InlineInstructionHandler;
	use Moody\ConstantContainer;
	use Moody\Token;
	use Moody\TokenHandlers\InstructionProcessor;
	use Moody\TokenVM;
	
	class IsDefinedHandler implements InlineInstructionHandler {
		private static $instance = null;
	
		private function __construct() {
			InstructionProcessor::getInstance()->registerHandler('isdefined', $this);
			InstructionProcessor::getInstance()->registerHandler('isdef', $this);
		}
	
		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}
	
		public function execute(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm = null, $executionType = 0) {
			$args = $processor->parseArguments($token, $instructionName, 's');
				
			if(ConstantContainer::isDefined($args[0])) {
				if($executionType & InstructionProcessor::EXECUTE_TYPE_INLINE)
					return true;
				$token->content = Token::makeEvaluatable(true);
			} else {
				if($executionType & InstructionProcessor::EXECUTE_TYPE_INLINE)
					return false;
				$token->content = Token::makeEvaluatable(false);
			}
			
			return 0;
		}
	}
	
	}
?>
