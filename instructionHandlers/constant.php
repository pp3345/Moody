<?php

	/****************************************************************/
	/* Moody                                                        */
	/* constant.php                 					            */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\InstructionHandlers {
	
	use Moody\DefaultInstructionHandler;
	use Moody\InstructionHandler;
	use Moody\InstructionProcessorException;
	use Moody\IfInstruction;
	use Moody\InlineInstructionHandler;
	use Moody\ConstantContainer;
	use Moody\Token;
	use Moody\TokenHandlers\InstructionProcessor;
	use Moody\TokenVM;
	
	class GetConstantHandler implements InlineInstructionHandler, DefaultInstructionHandler {
		private static $instance = null;
	
		private function __construct() {
			InstructionProcessor::getInstance()->registerHandler('const', $this);
			InstructionProcessor::getInstance()->registerHandler('constant', $this);
			InstructionProcessor::getInstance()->registerHandler('getconstant', $this);
			InstructionProcessor::getInstance()->registerDefaultHandler($this);
		}
	
		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}
	
		public function execute(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm = null, $executionType = 0) {
			if($executionType & InstructionProcessor::EXECUTE_TYPE_DEFAULT)
				$args = array(substr($instructionName, 1));
			else
				$args = $processor->parseArguments($token, $instructionName, 's');
				
				
			if(!ConstantContainer::isDefined($args[0]))
				throw new InstructionProcessorException($instructionName . ': Undefined constant: ' . $args[0], $token);
			
			$constValue = ConstantContainer::getConstant($args[0]);
			
			if($executionType & InstructionProcessor::EXECUTE_TYPE_INLINE)
				return $constValue;
			
			$token->content = Token::makeEvaluatable($constValue);
			
			return 0;
		}

	 	public function canExecute(Token $token, $instructionName, InstructionProcessor $processor) {
			if($processor->parseArguments($token, $instructionName, ''))
				return false;
			
			if(!ConstantContainer::isDefined(substr($instructionName, 1)))
				return false;
			
			return true;
		}

	}
	
	}
?>