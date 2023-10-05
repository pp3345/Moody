<?php

	/****************************************************************/
	/* Moody                                                        */
	/* macro.php                 					            	*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\InstructionHandlers {
	
	use Moody\InstructionProcessorException;
	use Moody\InlineInstructionHandler;
	use Moody\Token;
	use Moody\TokenHandlers\InstructionProcessor;
	use Moody\TokenVM;
	
	class MacroHandler implements InlineInstructionHandler {
		private static $instance = null;
	
		private function __construct() {
			InstructionProcessor::getInstance()->registerHandler('macro', $this);
		}
	
		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}
	
		public function execute(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm = null, $executionType = 0) {
			// New macro definition
			if(strtolower($instructionName) == 'macro') {
				if($executionType & InstructionProcessor::EXECUTE_TYPE_INLINE)
					throw new InstructionProcessorException($instructionName . ' does not support inline execution', $token);
				
				$args = $processor->parseArguments($token, $instructionName, 'ss');
				
				if(!$args[0])
					throw new InstructionProcessorException('Macro name cannot be empty', $token);

				$args[0] = strtolower($args[0]);
				
				$macro = new Macro($args[0], $args[1]);
				$processor->registerHandler($args[0], $this);
				unset($args[0], $args[1]);
				
				foreach($args as $arg)
					$macro->addArgument($arg);
				
				return TokenVM::DELETE_TOKEN;
			}
						
			$macro = Macro::getMacro($instructionName);
			if(!$macro)
				throw new InstructionProcessorException('Call to bad macro', $token);
			
			$options = "";
			for($i = 0; $i < $macro->numArgs(); $i++)
				$options .= 'x';
			
			$args = $processor->parseArguments($token, $instructionName, $options);
			
			if($executionType & InstructionProcessor::EXECUTE_TYPE_INLINE) {
				$code = "";
				foreach($macro->buildCode($args) as $token)
					$code .= $token->content;
				return $code;
			} else
				$vm->insertTokenArray($macro->buildCode($args));

			return TokenVM::DELETE_TOKEN;
		}
	}
	
	class Macro {
		public $name = "";
		private $code = "";
		private $arguments = array();
		private static $macros = array();
		
		public function __construct($name, $code) {
			$this->name = $name;
			$this->code = $code;
			self::$macros[$name] = $this;
		}
		
		public function addArgument($variableName) {
			$this->arguments[] = $variableName;
		}
		
		public function numArgs() {
			return count($this->arguments);
		}
		
		public function buildCode($args = array()) {
			$i = 0;
			$code = $this->code;

			foreach($this->arguments as $arg) {
				$code = str_replace($arg, $args[$i], $code);
				
				$i++;
			}

			$tokens = Token::tokenize('<?php ' . $code, 'Macro ' . $this->name);
			unset($tokens[0]);

			return $tokens;
		}
		
		/**
		 * 
		 * @param string $name
		 * @return Macro
		 */
		public static function getMacro($name) {
			$name = strtolower($name);
			if(isset(self::$macros[$name]))
				return self::$macros[$name];
		}
	}
	
	}
?>
