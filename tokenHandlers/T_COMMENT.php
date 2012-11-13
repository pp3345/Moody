<?php

	/****************************************************************/
	/* Moody                                                        */
	/* T_COMMENT.php                                     			*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\TokenHandlers {
	
	use Moody\InstructionHandlerWithRegister;

	use Moody\InlineInstructionHandler;
	use Moody\DefaultInstructionHandler;
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
		private $defaultHandlerStack = array();
		const EXECUTE_TYPE_INLINE = 1;
		const EXECUTE_TYPE_DEFAULT = 2;
	
		/**
		 * 
		 * @return InstructionProcessor
		 */
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
			
			if(preg_match('~^\s*(\.([A-Za-z_:\\\0-9]+))~', $content, $matches)) {
				$instruction = strtolower($matches[2]);

				if(isset($this->handlerStack[$instruction])) {
					if(!($this->handlerStack[$instruction] instanceof InstructionHandler))
						throw new InstructionProcessorException('Handler for instruction "' . $matches[1] . '" does not exist or is not callable', $token);
					$vmRetval = $this->handlerStack[$instruction]->execute($token, $matches[1], $this, $vm);
					goto end;
				} else if($this->defaultHandlerStack) { 
					foreach($this->defaultHandlerStack as $handler) {
						if(!($handler instanceof DefaultInstructionHandler))
							throw new InstructionProcessorException('Default Handler for instruction "' . $matches[1] . '" is invalid', $token);
						if($handler->canExecute($token, $matches[1], $this)) {
							$vmRetval = $handler->execute($token, $matches[1], $this, $vm, self::EXECUTE_TYPE_DEFAULT);
							goto end;
						}
					}
				}
				
				if(!Configuration::get('ignoreunknowninstruction', false))
					throw new InstructionProcessorException('Unknown instruction "' . $matches[1] . '"', $token);
			} else if(Configuration::get('deletecomments', true))
				$vmRetval = TokenVM::DELETE_TOKEN;
	
			end:
			
			return (TokenVM::NEXT_HANDLER | TokenVM::NEXT_TOKEN) ^ $vmRetval;
		}
		
		public function register(Token $token, TokenVM $vm) {
			$content = str_replace(array("//", "/*", "*/", "#"), "", $token->content);
				
			$matches = array();
			
			if(preg_match('~^\s*(\.([A-Za-z_:\\\0-9]+))~', $content, $matches)) {
				$instruction = strtolower($matches[2]);

				if(isset($this->handlerStack[$instruction]) && $this->handlerStack[$instruction] instanceof InstructionHandlerWithRegister)
					$this->handlerStack[$instruction]->register($token, $matches[1], $this, $vm);
			}
		}
		
		private function inlineExecute(Token $token) {
			$content = str_replace(array("//", "/*", "*/", "#"), "", $token->content);
				
			$matches = array();
				
			if(preg_match('~^\s*(\.([A-Za-z_:\\\0-9]+))~', $content, $matches)) {
				$instruction = strtolower($matches[2]);
			
				if(isset($this->handlerStack[$instruction])) {
					if(!($this->handlerStack[$instruction] instanceof InlineInstructionHandler))
						throw new InstructionProcessorException($matches[1] . ' does not support inline execution', $token);
					return $this->handlerStack[$instruction]->execute($token, $matches[1], $this, null, self::EXECUTE_TYPE_INLINE);
				} else if($this->defaultHandlerStack) {
					foreach($this->defaultHandlerStack as $handler) {
						if(!($handler instanceof InlineInstructionHandler))
							continue;
						if(!($handler instanceof DefaultInstructionHandler))
							throw new InstructionProcessorException('Default handler for instruction "' . $matches[1] . '" is invalid', $token);
						if($handler->canExecute($token, $matches[1], $this))
							return $handler->execute($token, $matches[1], $this, null, self::EXECUTE_TYPE_DEFAULT | self::EXECUTE_TYPE_INLINE);
					}
				}
				
				if(!Configuration::get('ignoreunknowninstruction', false))
					throw new InstructionProcessorException('Unknown instruction "' . $matches[1] . '"', $token);
			}
		}
		
		public function registerHandler($instruction, InstructionHandler $handler) {
			$this->handlerStack[$instruction] = $handler;
		}
		
		public function registerDefaultHandler(DefaultInstructionHandler $handler) {
			$this->defaultHandlerStack[] = $handler;
		}
		
		public function parseArguments(Token $origToken, $instructionName, $optionsStr) {
			if($optionsStr)
				$options = str_split($optionsStr);
			else
				$options = array();
			
			if(!strpos($origToken->content, $instructionName))
				throw new InstructionProcessorException('Token corrupted', $origToken);
			
			if(substr($origToken->content, 0, 2) == '/*')
				$content = substr($origToken->content, 2, strrpos($origToken->content, '*/') - 2);
			else if(substr($origToken->content, 0, 1) == '#')
				$content = substr($origToken->content, 1);
			else
				$content = substr($origToken->content, 2);
			$instructionArgs = substr($content, strpos($content, $instructionName) + strlen($instructionName));

			// Tokenize
			$tokens = Token::tokenize('<?php ' . $instructionArgs . ' ?>', 'Moody Argument Parser');
			
			foreach($tokens as $token)
				if($token->type == T_COMMA) 
					$useCommaSeperator = true;
			
			$argNum = 0;
			$optionsOffset = 0;
			$args = $ignoreTokens = array();
			$tokenValue = null;
			
			parseArgs:
			
			foreach($tokens as $token) {
				if(isset($parseLastArg))
					goto parseArg;
				
				if($token->type == T_OPEN_TAG
				|| $token->type == T_CLOSE_TAG
				|| $token->type == T_ROUND_BRACKET_OPEN
				|| $token->type == T_ROUND_BRACKET_CLOSE
				|| $token->type == T_WHITESPACE
				|| in_array($token, $ignoreTokens))
					continue;
				
				switch($token->type) {
					case T_STRING:
						if(ConstantContainer::isDefined($token->content))
							if($tokenValue !== null)
								$tokenValue .= ConstantContainer::getConstant($token->content);
							else
								$tokenValue = ConstantContainer::getConstant($token->content);
						else
							$tokenValue .= $token->content;
						break;
					case T_CONSTANT_ENCAPSED_STRING:
						$tokenValue .= eval('return ' . $token->content . ';');
						break;
					case T_TRUE:
						if($tokenValue !== null)
							$tokenValue .= true;
						else
							$tokenValue = true;
						break;
					case T_FALSE:
						if($tokenValue !== null)
							$tokenValue .= false;
						else
							$tokenValue = false;
						break;
					case T_LNUMBER:
						if($tokenValue !== null)
							$tokenValue .= (int) $token->content;
						else
							$tokenValue = (int) $token->content;
						break;
					case T_DNUMBER:
						if($tokenValue !== null)
							$tokenValue .= (float) $token->content;
						else
							$tokenValue = (float) $token->content;
						break;
					case T_NULL:
						if($tokenValue !== null)
							$tokenValue .= null;
						else
							$tokenValue = null;
						break;
					case T_NS_SEPARATOR:
						$totalString = "";
						$pos = key($tokens) - 1;
						prev($tokens);
						// Resolve previous parts
						while($prev = prev($tokens)) {
							if($prev->type != T_STRING)
								break;
							end($args);
							unset($args[key($args)]);
							$totalString = $prev->content . $totalString;
						}
						
						while(key($tokens) != $pos)
							next($tokens);
						
						// Insert current token
						$totalString .= $token->content;
						
						// Resolve next parts
						while($next = next($tokens)) {
							if($next->type != T_NS_SEPARATOR && $next->type != T_STRING)
								break;
							$totalString .= $next->content;
							
							// The doc states
							// "As foreach relies on the internal array pointer changing it within the loop may lead to unexpected behavior."
							// This is not true. Therefore I have to use this workaround. Silly PHP.
							$ignoreTokens[] = $next;
						}

						if(ConstantContainer::isDefined($totalString))
							if($tokenValue !== null)
								$tokenValue .= ConstantContainer::getConstant($totalString);
							else
								$tokenValue = ConstantContainer::getConstant($totalString);
						else
							$tokenValue .= $totalString;
						break;
					case T_COMMENT:
						if($tokenValue !== null)
							$tokenValue .= $this->inlineExecute($token);
						else
							$tokenValue = $this->inlineExecute($token);
						break;
					case T_COMMA:
						goto parseArg;
					default:
						if($tokenValue !== null)
							$tokenValue .= $token->content;
						else
							$tokenValue = $token->content;
				}
				
				if(isset($useCommaSeperator))
					continue;
				
				parseArg:
				
				if(!isset($options[$argNum + $optionsOffset]) || !$options[$argNum + $optionsOffset]) {
					$args[] = $tokenValue;
				} else if($options[$argNum + $optionsOffset] == '?') {
					$optionsOffset++;
					goto parseArg;
				} else {
					switch(strtolower($options[$argNum + $optionsOffset])) {
						default:
							throw new InstructionProcessorException('Illegal option for argument parser given: ' . $options[$argNum + $optionsOffset], $origToken);
						case 'n':
							if(is_numeric($tokenValue) && is_string($tokenValue))
								$args[] = (float) $tokenValue;
							else if(is_int($tokenValue) || is_float($tokenValue) || $tokenValue === null)
								$args[] = $tokenValue;
							else
								throw new InstructionProcessorException('Illegal argument ' . ($argNum + 1). ' for ' . $instructionName . ': ' . gettype($tokenValue) . ' ' . (string) $tokenValue . ' given, number expected' , $origToken);
							break;
						case 's':
							if(is_string($tokenValue) || $tokenValue === null)
								$args[] = $tokenValue;
							else
								throw new InstructionProcessorException('Illegal argument ' . ($argNum + 1). ' for ' . $instructionName . ': ' . gettype($tokenValue) . ' ' . (string) $tokenValue . ' given, string expected' , $origToken);
							break;
						case 'b':
							if(is_bool($tokenValue) || $tokenValue === null)
								$args[] = $tokenValue;
							else
								throw new InstructionProcessorException('Illegal argument ' . ($argNum + 1). ' for ' . $instructionName . ': ' . gettype($tokenValue) . ' ' . (string) $tokenValue . ' given, bool expected' , $origToken);
							break;
						case 'x':
							$args[] = $tokenValue;
					}
				}
				
				$tokenValue = null;
				$argNum++;
				
				if(isset($parseLastArg))
					break;
			}
			
			if($tokenValue !== null) {
				$parseLastArg = true;
				goto parseArgs;
			}
			
			if((strpos($optionsStr, '?') !== false && $argNum < strpos($optionsStr, '?')) || ($argNum < count($options) && strpos($optionsStr, '?') === false))
				throw new InstructionProcessorException($instructionName . ' expects ' . count($options) . ' arguments, ' . $argNum . ' given', $origToken);
			
			return $args;
		}
	}
	
	}
?>