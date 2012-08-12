<?php

	/****************************************************************/
	/* Moody                                                        */
	/* token.class.php                                              */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody;
	
	class Token {
		public $id = 0;
		public $type = 0;
		public $fileID = 0;
		public $fileName = "Unknown";
		public $line = 0;
		public $content = "";
		private static $files = 0;
		private static $typeNames = array(
				"T_ABSTRACT" => T_ABSTRACT,
				"T_AND_EQUAL" => T_AND_EQUAL
				/* To be continued */);
		
		public static function tokenize($code, $file = null) {
			$tokens = token_get_all($code);

			if(!$tokens)
				throw new MoodyException('Token::tokenize() was called with a non-tokenizable code');
			
			$id = 0;
			$tokenObjects = array();
			
			foreach($tokens as $token) {
				$tokenObject = new Token;
				$tokenObject->type = $token[0];
				$tokenObject->fileID = self::$files++;
				$tokenObject->content = $token[1];
				if($file)
					$tokenObject->fileName = $file;
				$tokenObject->line = $token[2];
				$tokenObject->id = $id;
				
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
	}
?>