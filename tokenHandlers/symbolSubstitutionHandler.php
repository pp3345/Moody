<?php

	/****************************************************************/
	/* Moody                                                        */
	/* symbolSubstitutionHandler.php                                */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/

	namespace Moody\TokenHandlers {

	use Moody\Token;
	use Moody\TokenVM;
	use Moody\TokenHandler;
	use Moody\ConstantContainer;
	use Moody\Configuration;
	use Moody\InstructionHandlers\Macro;
	use Moody\TokenHandlers\InstructionProcessor;

	class SymbolSubstitutionHandler implements TokenHandler {
		private static $instance = null;
		private static $tokens = array(T_STRING, T_NS_SEPARATOR, T_SELF, T_PARENT);
		private $enabled = false;


		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}

		private function __construct() {
			Configuration::registerCallback('autosubstitutesymbols', true, array($this, 'invoke'));
		}

		public function invoke($value, TokenVM $tokenVM = null) {
			if(!$value && $this->enabled) {
				$this->enabled = false;
				if($tokenVM) {
					foreach(self::$tokens as $token)
						$tokenVM->unregisterTokenHandler($token, $this);
				} else {
					foreach(self::$tokens as $token)
						TokenVM::globalUnregisterTokenHandler($token, $this);
				}
			} else if($value && !$this->enabled) {
				$this->enabled = true;
				if($tokenVM) {
					foreach(self::$tokens as $token)
						$tokenVM->registerTokenHandler($token, $this);
				} else {
					foreach(self::$tokens as $token)
						TokenVM::globalRegisterTokenHandler($token, $this);
				}
			}
		}

		public function execute(Token $token, TokenVM $vm) {
			$tokenArray = $vm->getTokenArray();

			$deleteToken = 0;
			$vmToken = $token;

			switch($token->type) {
				case T_SELF:
				case T_PARENT:
					$class = ClassFetcher::getInstance()->getCurrentClass();
					if($token->type == T_PARENT)
						$class = $class->extends;

					for($currentToken = current($tokenArray);;$currentToken = next($tokenArray)) {
						if($currentToken->type == T_DOUBLE_COLON)
							continue;
						if($currentToken->type != T_STRING)
							break 2;
						if(ConstantContainer::isDefined($currentToken->content, $class) && is_scalar($constantValue = ConstantContainer::getConstant($currentToken->content, $class))) {
							$token->content = Token::makeEvaluatable($constantValue);
							$vm->jump(next($tokenArray));
							return TokenVM::JUMP | TokenVM::NEXT_TOKEN;
						}
						break 2;
					}
					break;
				case T_NS_SEPARATOR:
					$token = current($tokenArray);
					next($tokenArray);
					/* fallthrough */
				case T_STRING:
					$fullName = $token->content;

					if($macro = Macro::getMacro(strtolower($fullName))) {
						$argString = "";

						if($macro->numArgs()) {
							$scope = 0;

							for($currentToken = current($tokenArray);;$currentToken = next($tokenArray)) {
								switch($currentToken->type) {
									case T_ROUND_BRACKET_OPEN:
										$scope++;
										if($scope > 1)
											$argString .= $currentToken->content;
										break;
									case T_ROUND_BRACKET_CLOSE:
										if(!(--$scope))
											break 2;
										/* fallthrough */
									default:
										$argString .= $currentToken->content;
								}
							}

							$vm->moveTo(next($tokenArray));
						}

						InstructionProcessor::getInstance()->callInstruction($macro->name, $vm, 0, array(), $argString);
						return TokenVM::DELETE_TOKEN | TokenVM::NEXT_TOKEN;
					}

					for($currentToken = current($tokenArray);;$currentToken = next($tokenArray)) {
						switch($currentToken->type) {
							case T_DOUBLE_COLON:
								$namespace = NamespaceFetcher::getInstance()->getCurrentNamespace();
								if(!$namespace || !($class = ClassFetcher::getInstance()->fetchClass($namespace . "\\" . $fullName)))
									$class = ClassFetcher::getInstance()->fetchClass($fullName);
								continue 2;
							case T_STRING:
								if(isset($class) && ConstantContainer::isDefined($currentToken->content, $class) && is_scalar($constantValue = ConstantContainer::getConstant($currentToken->content, $class))) {
									$vmToken->content = Token::makeEvaluatable($constantValue);
									$vm->jump(next($tokenArray));
									return TokenVM::JUMP | TokenVM::NEXT_TOKEN;
								} else {
									$fullName .= $currentToken->content;
									continue 2;
								}
							case T_NS_SEPARATOR:
								$fullName .= "\\";
								continue 2;
							default:
								break 2;
						}
					}

					if(ConstantContainer::isDefined($fullName) && is_scalar($constantValue = ConstantContainer::getConstant($fullName))) {
						$vmToken->content = Token::makeEvaluatable($constantValue);
						$vm->jump($currentToken);
						return TokenVM::NEXT_TOKEN | TokenVM::JUMP;
					}

					break;
			}

			return TokenVM::NEXT_TOKEN | TokenVM::NEXT_HANDLER;
		}
	}

	}
?>