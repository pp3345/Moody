<?php

	/****************************************************************/
	/* Moody                                                        */
	/* longDefine.php                 					            */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\InstructionHandlers {
		use Moody\InstructionHandler;
		use Moody\Token;
		use Moody\TokenHandlers\InstructionProcessor;
		use Moody\TokenVM;
		use Moody\InstructionProcessorException;
	
		class LongDefineHandler implements InstructionHandler {
			private static $instance = null;
			
			private function __construct() {
				InstructionProcessor::getInstance()->registerHandler('define', $this);
				InstructionProcessor::getInstance()->registerHandler('def', $this);
				InstructionProcessor::getInstance()->registerHandler('d', $this);
				InstructionProcessor::getInstance()->registerDefaultHandler($this);
			}
			
			public static function getInstance() {
				if(!self::$instance)
					self::$instance = new self;
				return self::$instance;
			}
			
			public function execute(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm = null, $executionType = 0) {
				
			}
		}
	}
	
	namespace Moody\TokenHandlers {
		use Moody\TokenHandler;
		use Moody\Token;
		use Moody\TokenHandlers\InstructionProcessor;
		use Moody\TokenVM;
		use Moody\InstructionProcessorException;
		
		class LongDefineHandler implements InstructionHandler {
			private static $instance = null;
				
			public static function getInstance() {
				if(!self::$instance)
					self::$instance = new self;
				return self::$instance;
			}
			
			public function execute(Token $token, TokenVM $vm) {
				
			}
		}
	}
?>