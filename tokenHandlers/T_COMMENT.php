<?php

	/****************************************************************/
	/* Moody                                                        */
	/* moodyException.class.php                                     */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\TokenHandlers;
	
	use Moody\TokenHandlerWithRegister;
	use Moody\TokenVM;
	use Moody\Token;
	use Moody\InstructionProcessorException;
	use Moody\Configuration;
	use Moody\ConstantContainer;
	use Moody\InstructionHandler;

	/**
	 * Comment handler / Instruction processor
	 */
	class InstructionProcessor implements TokenHandlerWithRegister {
		private static $instance = null;
		private $handlerStack = array();
	
		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}
	
		private function __construct() {
			TokenVM::globalRegisterTokenHandler(T_COMMENT, $this);
		}
	
		public function execute(Token $token, TokenVM $vm) {
			$content = str_replace(array("//", "/*", "*/", "#"), "", $token->content);
			
			$matches = array();
			$vmRetval = 0;
			
			if(preg_match('~[.][a-zA-Z]+~', $content, $matches)) {
				$instruction = strtolower(str_replace('.', '', $matches[0]));
				
				if(isset($this->handlerStack[$instruction])) {
					if(!is_callable(array($this->handlerStack[$instruction], 'execute')))
						throw new InstructionProcessorException('Handler for instruction "' . $matches[0] . '" does not exist or is not callable', $token);
					$vmRetval = $this->handlerStack[$instruction]->execute($token, $matches[0], $this, $vm);
				} else if(!Configuration::get('ignoreunknowninstruction', false))
					throw new InstructionProcessorException('Unknown instruction "' . $matches[0] . '"', $token);
			} else if(Configuration::get('deletecomments', false))
				$vmRetval = TokenVM::DELETE_TOKEN;
	
			return TokenVM::NEXT_HANDLER | TokenVM::NEXT_TOKEN | $vmRetval;
		}
		
		public function register(Token $token, TokenVM $vm) {
			$content = str_replace(array("//", "/*", "*/", "#"), "", $token->content);
				
			$matches = array();
			
			if(preg_match('~[.][a-zA-Z]+~', $content, $matches)) {
				$instruction = strtolower(str_replace('.', '', $matches[0]));
				
				if(isset($this->handlerStack[$instruction])) {
					if(is_callable(array($this->handlerStack[$instruction], 'register')))
						$this->handlerStack[$instruction]->register($token, $matches[0], $this, $vm);
				}
			}
		}
		
		public function registerHandler($instruction, InstructionHandler $handler) {
			$this->handlerStack[$instruction] = $handler;
		}
		
		public function parseArguments(Token $origToken, $instructionName, $options) {
			$options = str_split($options);
			
			$content = str_replace(array("//", "/*", "*/", "#"), "", $origToken->content);
			$instructionArgs = substr($origToken->content, strpos($origToken->content, $instructionName) + strlen($instructionName));
			
			// Tokenize
			$tokens = Token::tokenize('<?php ' . $instructionArgs . ' ?>');
			
			$argNum = 0;
			$optionsOffset = 0;
			$args = array();
			
			foreach($tokens as $token) {
				if($token->type == T_OPEN_TAG 
				|| $token->type == T_CLOSE_TAG 
				|| $token->type == T_ROUND_BRACKET_OPEN 
				|| $token->type == T_ROUND_BRACKET_CLOSE 
				|| $token->type == T_COMMA
				|| $token->type == T_WHITESPACE)
					continue;
				
				$tokenValue = $token->content;
				if($token->type == T_STRING && ConstantContainer::isDefined($token->content))
					$tokenValue = ConstantContainer::getConstant($token->content);
				else if($token->type == T_CONSTANT_ENCAPSED_STRING || $token->type == T_LNUMBER || $token->type == T_DNUMBER)
					eval('$tokenValue = ' . $token->content . ';');
				
				parseArg:
				
				if(!isset($options[$argNum + $optionsOffset]) || !$options[$argNum + $optionsOffset]) {
					$args[] = $token->content;
				} else if($options[$argNum + $optionsOffset] == '?') {
					$optionsOffset++;
					goto parseArg;
				} else {
					switch(strtolower($options[$argNum + $optionsOffset])) {
						default:
							throw new InstructionProcessorException('Illegal option for argument parser given: ' . $options[$argNum + $optionsOffset], $origToken);
						case 'n':
							if(is_numeric($tokenValue))
								$args[] = (float) $tokenValue;
							else
								throw new InstructionProcessorException('Illegal argument ' . ($argNum + 1). ' for ' . $instructionName . ': ' . $token->content . ' given, number expected' , $origToken);
							break;
						case 's':
							if(is_string($tokenValue) && ($token->type == T_STRING || $token->type == T_CONSTANT_ENCAPSED_STRING))
								$args[] = $tokenValue;
							else
								throw new InstructionProcessorException('Illegal argument ' . ($argNum + 1). ' for ' . $instructionName . ': ' . $token->content . ' given, string expected' , $origToken);
							break;
						case 'x':
							$args[] = $tokenValue;
					}
				}
				
				$argNum++;
			}
			
			if(!$optionsOffset && $argNum < count($options))
				throw new InstructionProcessorException($instructionName . ' expects ' . count($options) . ' arguments, ' . $argNum . ' given', $origToken);
			
			return $args;
		}
	}
?>