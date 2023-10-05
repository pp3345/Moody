<?php

	/****************************************************************/
	/* Moody                                                        */
	/* scopeFetcher.php                                				*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/

	namespace Moody\TokenHandlers {

	use Moody\TokenHandler;
	use Moody\TokenVM;
	use Moody\Token;
			
	class ScopeFetcher implements TokenHandler {
		private static $instance = null;
		private $depth = 0;
		private $enterCallbacks = array();
		private $leaveCallbacks = array();

		public static function getInstance() {
			if(!self::$instance)
				self::$instance = new self;
			return self::$instance;
		}

		private function __construct() {
			TokenVM::globalRegisterTokenHandler(T_CURLY_BRACKET_OPEN, $this);
			TokenVM::globalRegisterTokenHandler(T_CURLY_BRACKET_CLOSE, $this);
		}

		public function execute(Token $token, TokenVM $vm) {
			if($token->type == T_CURLY_BRACKET_OPEN) {
				$this->depth++;
				if(isset($this->enterCallbacks[$this->depth])) {
					foreach($this->enterCallbacks[$this->depth] as $id => $callback) {
						call_user_func($callback, $this->depth);
						unset($this->enterCallbacks[$id]);
					}
				}
			} else {
				if(isset($this->leaveCallbacks[$this->depth])) {
					foreach($this->leaveCallbacks[$this->depth] as $id => $callback) {
						call_user_func($callback, $this->depth);
						unset($this->leaveCallbacks[$id]);
					}
				}
				
				$this->depth--;
			}
			
			return TokenVM::NEXT_HANDLER | TokenVM::NEXT_TOKEN;
		}
		
		public function addEnterCallback($callback, $depth) {
			$this->enterCallbacks[$depth][] = $callback;
		}
		
		public function addLeaveCallback($callback, $depth) {
			$this->leaveCallbacks[$depth][] = $callback;
		}
		
		public function getDepth() {
			return $this->depth;
		}
	}
	
	}
?>
