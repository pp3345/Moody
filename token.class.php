<?php

	/****************************************************************/
	/* Moody                                                        */
	/* token.class.php                                              */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody {
	
	define('T_DOT', 16384);
	define('T_UNKNOWN', 16385);
	define('T_ROUND_BRACKET_OPEN', 16386);
	define('T_ROUND_BRACKET_CLOSE', 16387);
	define('T_COMMA', 16388);
	define('T_TRUE', 16389);
	define('T_FALSE', 16390);
	define('T_NULL', 16391);
	define('T_FORCED_WHITESPACE', 16392);
	define('T_SEMICOLON', 16393);
	define('T_EQUAL', 16394);
	define('T_CURLY_BRACKET_OPEN', 16395);
	define('T_CURLY_BRACKET_CLOSE', 16396);
	define('T_EOF', 16397);
	define('T_SELF', 16398);
	define('T_PARENT', 16399);
	if(!defined('T_INSTEADOF'))
		define('T_INSTEADOF', 32768);
	if(!defined('T_TRAIT'))
		define('T_TRAIT', 32769);
	
	#.mapVariable '$content' '$content'
	
	class Token {
		public $id = 0;
		public $type = 0;
		public $fileID = 0;
		public $fileName = "Unknown";
		public $line = 0;
		public $content = "";
		public $argumentCache = array();
        public $haveDynamicArguments = false;
        public $instruction = "";
		private static $tokens = 0;
		private static $files = 0;
		
		public function __construct() {
			$this->id = self::$tokens++;
		}

		public static function tokenize($code, $file = null) {
			$tokens = token_get_all($code);

			if(!$tokens)
				throw new MoodyException('Token::tokenize() was called with a non-tokenizable code');
			
			$tokenObjects = array();
			self::$files++;
			
			foreach($tokens as $token) {
				$tokenObject = new Token;
				
				$tokenObject->fileID = self::$files;
				if($file)
					$tokenObject->fileName = $file;

				if(is_array($token)) {
					$tokenObject->type = $token[0];
					$tokenObject->content = $token[1];
					$tokenObject->line = $token[2];
					
					switch(strtolower($tokenObject->content)) {
						case 'true':
							$tokenObject->type = T_TRUE;
							break;
						case 'false':
							$tokenObject->type = T_FALSE;
							break;
						case 'null':
							$tokenObject->type = T_NULL;
							break;
						case 'self':
							$tokenObject->type = T_SELF;
							break;
						case 'parent':
							$tokenObject->type = T_PARENT;
							break;
					}
				} else  {
					$tokenObject->content = $token;
					$tokenObject->line = -1;

					switch($token) {
						case '.':
							$tokenObject->type = T_DOT;
							break;
						case '(':
							$tokenObject->type = T_ROUND_BRACKET_OPEN;
							break;
						case ')':
							$tokenObject->type = T_ROUND_BRACKET_CLOSE;
							break;
						case ',':
							$tokenObject->type = T_COMMA;
							break;
						case ';':
							$tokenObject->type = T_SEMICOLON;
							break;
						case '=':
							$tokenObject->type = T_EQUAL;
							break;
						case '{':
							$tokenObject->type = T_CURLY_BRACKET_OPEN;
							break;
						case '}':
							$tokenObject->type = T_CURLY_BRACKET_CLOSE;
							break;
						default:
							$tokenObject->type = T_UNKNOWN;
					}
				}

				$tokenObjects[] = $tokenObject;
			}
			
			return $tokenObjects;
		}
		
		public static function getName($tokenType) {
			return token_name($tokenType) == "UNKNOWN" ? $tokenType : token_name($tokenType);
		}
		
		public function __toString() {
			$string = 'Type: ' . self::getName($this->type) . "\r\n";
			$string .= 'Content: ' . $this->content . "\r\n";
			if($this->fileName != "Unknown") {
				$string .= 'Origin: ' . $this->fileName . "\r\n";
				$string .= 'Line: ' . $this->line . "\r\n";
			}
			
			return $string;
		}
		
		public static function makeEvaluatable($value) {
			if(is_string($value))
				return "'" . str_replace("'", "\'", $value) . "'";
			if(is_int($value) || is_float($value))
				return $value;
			if($value === true)
				return "true";
			if($value === false)
				return "false";
			if($value === null)
				return "null";
		}
	}
	
	}
?>