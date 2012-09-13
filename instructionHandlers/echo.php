<?php

	/****************************************************************/
	/* Moody                                                        */
	/* echo.php                 					                */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\InstructionHandlers;
	
	use Moody\InstructionHandler;
	use Moody\Token;
	use Moody\TokenHandlers\InstructionProcessor;
	use Moody\TokenVM;
	
	class EchoHandler implements InstructionHandler {
		private static $instance = null;
		
		private function __construct() {
			InstructionProcessor::getInstance()->registerHandler('echo', $this);
		}
		
		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}

		public function execute(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm) {
			$args = $processor->parseArguments($token, $instructionName, 's');
			
			echo $args[0];
			
			return TokenVM::DELETE_TOKEN;
		}
	}
?>