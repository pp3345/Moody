<?php

	/****************************************************************/
	/* Moody                                                        */
	/* eval.php                 					                */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody\InstructionHandlers {


		use Moody\InlineInstructionHandler;
	use Moody\Token;
	use Moody\TokenHandlers\InstructionProcessor;
	use Moody\TokenVM;
	
	class EvalHandler implements InlineInstructionHandler {
		private static $instance = null;
	
		private function __construct() {
			InstructionProcessor::getInstance()->registerHandler('eval', $this);
			InstructionProcessor::getInstance()->registerHandler('evaluate', $this);
		}
	
		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}
	
		public function execute(Token $token, $instructionName, InstructionProcessor $processor, TokenVM $vm = null, $executionType = 0) {
			$args = $processor->parseArguments($token, $instructionName, 's?bb');
			// arg 0 = code; arg 1 = execute vm? = true; arg 2 = make executable? true
			
			if(!isset($args[1]) || $args[1] === true) {
				if(!strpos($args[0], '<?')) {
					$addedPHPTokens = true;
					$tokenArray = Token::tokenize('<?php ' . $args[0] . ' ?>', $instructionName . ' code');
				} else
					$tokenArray = Token::tokenize($args[0],  $instructionName . ' code');
				
				$vm = new TokenVM();
				
				$tokenArray = $vm->execute($tokenArray);
				
				// Let's hope <?php and ? > are where they are supposed to be
				if(isset($addedPHPTokens)) {
					reset($tokenArray);
					unset($tokenArray[key($tokenArray)]);
					end($tokenArray);
					unset($tokenArray[key($tokenArray)]);
				}
				
				$args[0] = "";
				
				foreach($tokenArray as $ntoken) {
					$args[0] .= $ntoken->content;
				}
			}

			$result = eval($args[0]);
			
			if($result !== null) {
				if($executionType & InstructionProcessor::EXECUTE_TYPE_INLINE)
					return $result;
				$token->content = !isset($args[2]) || $args[2] === true ? Token::makeEvaluatable($result) : $result;
				return 0;
			}
			
			return TokenVM::DELETE_TOKEN;
		}
	}
	
	}
?>
