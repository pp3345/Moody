<?php

	/****************************************************************/
	/* Moody                                                        */
	/* InstructionProcessor.php                                     */
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
				new self;
			return self::$instance;
		}
	
		private function __construct() {
			self::$instance = $this;
			
			TokenVM::globalRegisterTokenHandler(T_COMMENT, $this);
			
			foreach(get_declared_classes() as $class) {
				if(in_array('Moody\InstructionHandler', class_implements($class)))
					$class::getInstance();
			}
		}
		
		public function callInstruction($instructionName, TokenVM $vm, $executeType = 0, $args = array(), $argString = "") {
			$token = new Token;
			$token->type = T_COMMENT;
            $token->instruction = $instructionName;
            if($argString)
			     $token->content = "#" . $instructionName . " " . $argString;
			$token->fileName = "Moody Instruction Processor Direct Call";
			$token->argumentCache = $args;
			return $executeType == self::EXECUTE_TYPE_INLINE ? $this->inlineExecute($token) : $this->execute($token, $vm);
		}
	
		public function execute(Token $token, TokenVM $vm) {
			$content = str_replace(array("//", "/*", "*/", "#"), "", $token->content);
			
			$matches = array();
			$vmRetval = 0;
			
            if($token->instruction || preg_match(Configuration::get('requireinstructiondot', true) ? '~^\s*(\.([A-Za-z_:\\\0-9]+))~' : '~^\s*(\.?[A-Za-z_:\\\0-9]+)~', $content, $matches)) {
			    if(!$token->instruction) {
    				$token->instruction = strtolower($matches[1]);
    				if(substr($token->instruction, 0, 1) == '.')
    					$token->instruction = substr($token->instruction, 1);
                }
                
				if(isset($this->handlerStack[$token->instruction])) {
					$vmRetval = $this->handlerStack[$token->instruction]->execute($token, $token->instruction, $this, $vm);
                    if($token->haveDynamicArguments)
					   $token->argumentCache = array();
					goto end;
				} else if($this->defaultHandlerStack) {
					foreach($this->defaultHandlerStack as $handler) {
						if($handler->canExecute($token, $token->instruction, $this)) {
							$vmRetval = $handler->execute($token, $token->instruction, $this, $vm, self::EXECUTE_TYPE_DEFAULT);
                            if($token->haveDynamicArguments)
							    $token->argumentCache = array();
							goto end;
						}
					}
				}
				
				if(!Configuration::get('ignoreunknowninstruction', false))
					throw new InstructionProcessorException('Unknown instruction "' . $token->instruction . '"', $token);
			} else if(Configuration::get('deletecomments', true))
				$vmRetval = TokenVM::DELETE_TOKEN;
	
			end:
			
			return (TokenVM::NEXT_HANDLER | TokenVM::NEXT_TOKEN) ^ $vmRetval;
		}
		
		public function register(Token $token, TokenVM $vm) {
			$content = str_replace(array("//", "/*", "*/", "#"), "", $token->content);
				
			$matches = array();
			
			if($token->instruction || preg_match(Configuration::get('requireinstructiondot', true) ? '~^\s*(\.([A-Za-z_:\\\0-9]+))~' : '~^\s*(\.?[A-Za-z_:\\\0-9]+)~', $content, $matches)) {
			    if(!$token->instruction) {
    				$token->instruction = strtolower($matches[1]);
    				if(substr($token->instruction, 0, 1) == '.')
    					$token->instruction = substr($token->instruction, 1);
                }

				if(isset($this->handlerStack[$token->instruction]) && $this->handlerStack[$token->instruction] instanceof InstructionHandlerWithRegister) {
					$this->handlerStack[$token->instruction]->register($token, $token->instruction, $this, $vm);
                    if($token->haveDynamicArguments)
					   $token->argumentCache = array();
				}
			}
		}
		
		private function inlineExecute(Token $token) {
			$content = str_replace(array("//", "/*", "*/", "#"), "", $token->content);
				
			$matches = array();
				
			if($token->instruction || preg_match(Configuration::get('requireinstructiondot', true) ? '~^\s*(\.([A-Za-z_:\\\0-9]+))~' : '~^\s*(\.?[A-Za-z_:\\\0-9]+)~', $content, $matches)) {
				if(!$token->instruction) {
    				$token->instruction = strtolower($matches[1]);
    				if(substr($token->instruction, 0, 1) == '.')
    					$token->instruction = substr($token->instruction, 1);
                }
			
				if(isset($this->handlerStack[$token->instruction])) {
					if(!($this->handlerStack[$token->instruction] instanceof InlineInstructionHandler))
						throw new InstructionProcessorException($token->instruction . ' does not support inline execution', $token);
					$retval = $this->handlerStack[$token->instruction]->execute($token, $token->instruction, $this, null, self::EXECUTE_TYPE_INLINE);
                    if($token->haveDynamicArguments)
					   $token->argumentCache = array();
					return $retval;
				} else if($this->defaultHandlerStack) {
					foreach($this->defaultHandlerStack as $handler) {
						if(!($handler instanceof InlineInstructionHandler))
							continue;
						if($handler->canExecute($token, $token->instruction, $this)) {
							$retval = $handler->execute($token, $token->instruction, $this, null, self::EXECUTE_TYPE_DEFAULT | self::EXECUTE_TYPE_INLINE);
                            if($token->haveDynamicArguments)
							 $token->argumentCache = array();
							return $retval;
						}
					}
				}
				
				if(!Configuration::get('ignoreunknowninstruction', false))
					throw new InstructionProcessorException('Unknown instruction "' . $matches[1] . '"', $token);
			}
		}
		
		public function registerHandler($instruction, InstructionHandler $handler) {
			if(!($handler instanceof InstructionHandler))
				throw new InstructionProcessorException('Handler for instruction "' . $instruction . '" is invalid');
			$this->handlerStack[$instruction] = $handler;
		}
		
		public function registerDefaultHandler(DefaultInstructionHandler $handler) {
			if(!($handler instanceof DefaultInstructionHandler))
				throw new InstructionProcessorException('Default handler ' . get_class($handler) . ' is invalid');
			$this->defaultHandlerStack[] = $handler;
		}
		
		public function parseArguments(Token $origToken, $instructionName, $optionsStr) {
			if($origToken->argumentCache)
				return $origToken->argumentCache;

			if($optionsStr)
				$options = str_split($optionsStr);
			else
				$options = array();
			
			if(!stripos($origToken->content, $instructionName))
				throw new InstructionProcessorException('Token corrupted', $origToken);
			
            $instructionArgs = substr($origToken->content, stripos($origToken->content, $instructionName) + strlen($instructionName));
            if(strpos($origToken->content, "/*") === 0)
                $instructionArgs = substr($instructionArgs, 0, strlen($instructionArgs) - 2);

			// Tokenize
			$tokens = Token::tokenize('<?php ' . $instructionArgs . ' ?>', 'Moody Argument Parser');
			
            unset($tokens[0], $tokens[1]);
                                    
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
                        $origToken->haveDynamicArguments = true;
                        
						if(($nextToken = current($tokens)) && $nextToken->type == T_DOUBLE_COLON) {
							$nextToken2 = next($tokens);
							$totalName = $token->content . "::" . $nextToken2->content;
							if(ConstantContainer::isDefined($totalName)) {
								if($tokenValue !== null)
									$tokenValue .= ConstantContainer::getConstant($totalName);
								else
									$tokenValue = ConstantContainer::getConstant($totalName);
								
								$ignoreTokens = array($nextToken, $nextToken2);
								break;
							}
						}

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
                        $origToken->haveDynamicArguments = true;

                        if(current($tokens) != $token)
                            while(next($tokens) != $token);
                        
						$prev = prev($tokens);
						if($prev && $prev->type == T_STRING) {
							$totalString = $prev->content . $token->content;
							end($args);
							unset($args[key($args)]);
						}
                        
                        next($tokens);

						// Resolve next parts
						while($next = next($tokens)) {
							if($next->type != T_NS_SEPARATOR && $next->type != T_STRING && $next->type != T_DOUBLE_COLON)
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
                        $origToken->haveDynamicArguments = true;
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
			
			return $origToken->argumentCache = $args;
		}
	}
	
	}
?>