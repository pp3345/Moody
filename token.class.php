<?php

	/****************************************************************/
	/* Moody                                                        */
	/* token.class.php                                              */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody;
	
	define('T_DOT', 16384);
	define('T_UNKNOWN', 16385);
	define('T_ROUND_BRACKET_OPEN', 16386);
	define('T_ROUND_BRACKET_CLOSE', 16387);
	define('T_COMMA', 16388);
	define('T_TRUE', 16389);
	define('T_FALSE', 16390);
	define('T_NULL', 16391);
	
	class Token {
		public $id = 0;
		public $type = 0;
		public $fileID = 0;
		public $fileName = "Unknown";
		public $line = 0;
		public $content = "";
		private static $files = 0;
		private static $typeNames = array(
				T_ABSTRACT => "T_ABSTRACT",
				T_AND_EQUAL => "T_AND_EQUAL",
				T_COMMA => "T_COMMA",
				T_COMMENT => "T_COMMENT",
				T_DOT => "T_DOT",
				T_FALSE => "T_FALSE",
				T_ROUND_BRACKET_CLOSE => "T_ROUND_BRACKET_CLOSE",
				T_ROUND_BRACKET_OPEN => "T_ROUND_BRACKET_OPEN",
				T_TRUE => "T_TRUE",
				T_UNKNOWN => "T_UNKNOWN"
				/* To be continued */);
		
		public static function tokenize($code, $file = null) {
			$tokens = token_get_all($code);

			if(!$tokens)
				throw new MoodyException('Token::tokenize() was called with a non-tokenizable code');
			
			$id = 0;
			$tokenObjects = array();
			
			foreach($tokens as $token) {
				$tokenObject = new Token;
				
				$tokenObject->fileID = self::$files++;
				if($file)
					$tokenObject->fileName = $file;
				$tokenObject->id = $id;
				
				if(is_array($token)) {
					$tokenObject->type = $token[0];
					$tokenObject->content = $token[1];
					$tokenObject->line = $token[2];
					
					if(strtolower($tokenObject->content) == 'true')
						$tokenObject->type = T_TRUE;
					else if(strtolower($tokenObject->content) == 'false')
						$tokenObject->type = T_FALSE;
					else if(strtolower($tokenObject->content) == 'null')
						$tokenObject->type = T_NULL;
				} else if($token == '.') {
					$tokenObject->type = \T_DOT;
					$tokenObject->content = '.';
				} else if($token == '(') {
					$tokenObject->type = \T_ROUND_BRACKET_OPEN;
					$tokenObject->content = '(';
				} else if($token == ')') {
					$tokenObject->type = \T_ROUND_BRACKET_CLOSE;
					$tokenObject->content = ')';
				} else if($token == ',') { 
					$tokenObject->type = \T_COMMA;
					$tokenObject->content = ',';
				} else {
					$tokenObject->type = \T_UNKNOWN;
					$tokenObject->content = $token;
				}
				
				$tokenObjects[$id] = $tokenObject;
				
				$id++;
			}
			
			return $tokenObjects;
		}
		
		public function __toString() {
			$string = 'Type: ' . (isset(self::$typeNames[$this->type]) ? self::$typeNames[$this->type] : $this->type) . "\r\n";
			$string .= 'Content: ' . $this->content . "\r\n";
			if($this->fileName != "Unknown") {
				$string .= 'File: ' . $this->fileName . "\r\n";
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
?>