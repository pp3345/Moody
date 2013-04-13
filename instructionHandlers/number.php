<?php

	/****************************************************************/
	/* Moody                                                        */
	/* number.php                 					        		*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\InstructionHandlers {


		use Moody\InlineInstructionHandler;
		use Moody\Token;
		use Moody\TokenHandlers\InstructionProcessor;
		use Moody\TokenVM;
	
		class NumberCastHandler implements InlineInstructionHandler {
			private static $instance = null;
	
			private function __construct() {
				InstructionProcessor::getInstance()->registerHandler('number', $this);
			}
	
			public static function getInstance() {
				if(!self::$instance)
					self::$instance = new self;
				return self::$instance;
			}
	
			public function execute(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm = null, $executionType = 0) {
				$args = $processor->parseArguments($token, $instructionName, 'x');
				
				if($executionType & InstructionProcessor::EXECUTE_TYPE_INLINE)
					return (float) $args[0];
				
				$token->content = Token::makeEvaluatable((float) $args[0]);
				
				return 0;
			}
		}
	
	}
?>