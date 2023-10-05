<?php

	/****************************************************************/
	/* Moody                                                        */
	/* registerConfiguration.php                 					*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\InstructionHandlers {
	
	use Moody\Configuration;
	use Moody\InstructionHandlerWithRegister;
	use Moody\Token;
	use Moody\TokenHandlers\InstructionProcessor;
	use Moody\TokenVM;
	
	class RegisterConfigurationHandler implements InstructionHandlerWithRegister {
		private static $instance = null;
	
		private function __construct() {
			InstructionProcessor::getInstance()->registerHandler('registerconfiguration', $this);
			InstructionProcessor::getInstance()->registerHandler('registerconfig', $this);
		}
	
		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}
	
		public function execute(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm) {
			return TokenVM::DELETE_TOKEN;
		}
		
		public function register(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm) {
			$args = $processor->parseArguments($token, $instructionName, 'sx');
			
			Configuration::set($args[0], $args[1]);
		}
	}
	
	}
?>
