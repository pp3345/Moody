<?php

	/****************************************************************/
    /* Moody                                                        */
    /* tokenVM.class.php                                            */
    /* 2012 Yussuf Khalil                                           */
    /****************************************************************/

	namespace Moody {
	
	const MOODY_VERSION = "1.0";	
	
	/**
	 * Virtual Machine that executes the token handlers
	 */
	class TokenVM {
		/* VM actions (values that can be returned by token handlers) */
		const NEXT_TOKEN = 1;					// 0b1
		const QUIT = 2;							// 0b10
		const NEXT_HANDLER = 4;					// 0b100
		const ERROR = 8;						// 0b1000
		const REEXECUTE_TOKEN = 16;				// 0b10000
		const REEXECUTE_HANDLER = 32;			// 0b100000
		const JUMP = 64;						// 0b1000000
		const CLEAR_RETVAL = 128;				// 0b10000000
		const CLEAR_ERROR = 256;				// 0b100000000
		const DELETE_TOKEN = 512;				// 0b1000000000
		const JUMP_WITHOUT_DELETE_TOKEN = 1024; // 0b10000000000
		
		private static $sHandlerStack = array();
		
		private $handlerStack = array();
		private $executedTokens = array();
		private $tokenArray = array();
		private $jump = null;
		
		public function __construct() {
			// Copy global handler stack into this vm
			$this->handlerStack = self::$sHandlerStack;
		}
		
		public function execute($tokenArray) {
			if(!$tokenArray)
				throw new VMException('Virtual machine received empty token array');
			
			$this->tokenArray = $tokenArray;
			
			// Register tokens
			foreach($this->tokenArray as $token) {
				if(isset($this->handlerStack[$token->type])) {
					foreach($this->handlerStack[$token->type] as $executor) {						
						// Protect token from modification by handler
						$nToken = clone $token;
						
						if(is_callable(array($executor, 'register')))
							$executor->register($token, $this);
						
						if($token != $nToken)
							throw new VMException('Token corrupted in register step', $token, $nToken);
					}
					
					reset($this->handlerStack[$token->type]);
				}
			}
			
			reset($this->tokenArray);
			
			$newArray = array();
			
			nextToken:

			if(!($token = current($this->tokenArray)))
				goto quit;
			
			$tokenID = $token->id;
			next($this->tokenArray);
			
			$originalToken = clone $token;
			
			executeToken:

			$this->executedTokens[$tokenID] = true;
			
			$retval = 0;

			nextHandler:

			if(isset($this->handlerStack[$token->type])) {
				$executor = current($this->handlerStack[$token->type]);
				next($this->handlerStack[$token->type]);
			} else {
				$newArray[] = $token;
				goto nextToken;
			}

			executeHandler:
						
			$newRetval = $executor->execute($token, $this);

			if($newRetval & self::CLEAR_RETVAL)
				$retval = $newRetval;
			else
				$retval |= $newRetval;
			
			doRetval:

			if($retval & self::REEXECUTE_HANDLER) {
				$retval ^= self::REEXECUTE_HANDLER;
				goto executeHandler;
			}
			
			if($retval & self::NEXT_HANDLER) {
				$retval ^= self::NEXT_HANDLER;
				if(current($this->handlerStack[$token->type]) !== false)
					goto nextHandler;
			}
			
			if($retval & self::REEXECUTE_TOKEN) {
				reset($this->handlerStack[$token->type]);
				$retval ^= self::REEXECUTE_TOKEN;
				goto executeToken; 
			}
			
			if(!($retval & self::DELETE_TOKEN))
				$newArray[] = $token;
			
			if($retval & self::QUIT)
				goto quit;
			
			if($retval & self::ERROR && !($retval & self::CLEAR_ERROR))
				throw new VMException('Token handler returned an error', $token, $originalToken);
			
			if(($retval & self::JUMP) || ($retval & self::JUMP_WITHOUT_DELETE_TOKEN)) {
				if(!($this->jump instanceof Token))
					throw new VMException('Cannot jump to new token as it is not a token', $token, $originalToken);
				
				if(!in_array($this->jump, $this->tokenArray))
					throw new VMException('Cannot jump to new token as it is not specified in current token array', $token, $originalToken);
				
				if(array_search($this->jump, $this->tokenArray) < key($this->tokenArray)) {
					while(prev($this->tokenArray) != $this->jump);
				} else if(array_search($this->jump, $this->tokenArray) > key($this->tokenArray)) {
					if($retval & self::JUMP_WITHOUT_DELETE_TOKEN) {
						// Since the array pointer always points to execute token + 1 we have to add the current token
						$newArray[] = current($this->tokenArray);
						while(($jToken = next($this->tokenArray)) != $this->jump) {
							$newArray[] = $jToken;
						}
					} else {
						while(next($this->tokenArray) != $this->jump);
					}
				}
			}

			if($retval & self::NEXT_TOKEN) {
				reset($this->handlerStack[$token->type]);
				goto nextToken;
			}

			throw new VMException('Token handler did not specify an action for the virtual machine', $token, $originalToken);
			
			quit:
			
			return $newArray;
		}
		
		public function hasExecuted($tokenID) {
			return isset($this->executedTokens[$tokenID]);
		}
		
		public static function globalRegisterTokenHandler($tokenType, TokenHandler $handler) {
			if(!($handler instanceof TokenHandler))
				throw new VMException('Handler for token ' . Token::getName($tokenType) . ' is invalid');
			
			if(!isset(self::$sHandlerStack[$tokenType]))
				self::$sHandlerStack[$tokenType] = array($handler);
			else
				self::$sHandlerStack[$tokenType][] = $handler;
		}
		
		public static function globalUnregisterTokenHandler($tokenType, TokenHandler $handler) {
			if(!isset(self::$sHandlerStack[$tokenType]) || ($key = array_search(self::$handlerStack[$tokenType])) === false)
				return;
			unset(self::$sHandlerStack[$tokenType][$key]);
		}
		
		public function registerTokenHandler($tokenType, TokenHandler $handler) {
			if(!($handler instanceof TokenHandler))
				throw new VMException('Handler for token ' . Token::getName($tokenType) . ' is invalid');
			
			if(!isset($this->handlerStack[$tokenType]))
				$this->handlerStack[$tokenType] = array($handler);
			else
				$this->handlerStack[$tokenType][] = $handler;
		}
		
		public function unregisterTokenHandler($tokenType, TokenHandler $handler) {
			if(!isset($this->handlerStack[$tokenType]) || ($key = array_search($handler, $this->handlerStack[$tokenType])) === false)
				return;
			unset($this->handlerStack[$tokenType][$key]);
		}
		
		public function jump(Token $token) {
			$this->jump = $token;
		}

		public function insertTokenArray($tokenArray) {
			reset($tokenArray);
			$nextElement = current($tokenArray);

			while($token = current($this->tokenArray)) {
				$shiftTokens[] = $token;
				unset($this->tokenArray[key($this->tokenArray)]);
			}

			foreach($tokenArray as $token) {
				if(isset($this->handlerStack[$token->type])) {
					// Get current position
					$key = key($this->handlerStack[$token->type]);

					foreach($this->handlerStack[$token->type] as $executor) {						
						// Protect token from modification by handler
						$nToken = clone $token;
						
						if(is_callable(array($executor, 'register')))
							$executor->register($token, $this);
						
						if($token != $nToken)
							throw new VMException('Token corrupted in register step', $token, $nToken);
					}

					reset($this->handlerStack[$token->type]);

					while(key($this->handlerStack[$token->type]) !== $key)
						next($this->handlerStack[$token->type]);
				}

				$this->tokenArray[] = $token;
			}

			foreach($shiftTokens as $token)
				$this->tokenArray[] = $token;

			$key = array_search($nextElement, $this->tokenArray);
			
			while(key($this->tokenArray) != $key)
				prev($this->tokenArray);
		}

		public function getTokenArray() {
			return $this->tokenArray;
		}
	}
	
	}
?>