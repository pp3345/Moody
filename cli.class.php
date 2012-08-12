<?php

	/****************************************************************/
	/* Moody                                                        */
	/* moodyException.class.php                                     */
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody;
	
	/**
	 * Command line interface for Moody
	 */
	class CLI  {
		public function __construct() {
			enter:
			echo 'Please enter file to tokenize: ';
			
			$fileName = str_replace(array("\r\n", "\n", "\r"), "", fread(STDIN, 128));
			if(!file_exists($fileName)) {
				echo "\r\nInvalid filename\r\n";
				goto enter;
			}
			
			$tokenArray = Token::tokenize(file_get_contents($fileName), $fileName);
			$vm = new TokenVM();
			try {
				$tokenArray = $vm->execute($tokenArray);
			} catch(\Exception $e) {
				echo (string) $e;
				exit;
			}
			
			$newCode = "";
			
			foreach($tokenArray as $token) {
				$newCode .= $token->content;
			}
			
			echo "\r\nNew code:\r\n";
			echo $newCode;
			echo "\r\n";
		}
	}
	
	require_once 'moodyException.class.php';
	require_once 'VMException.class.php';
	require_once 'token.class.php';
	require_once 'tokenVM.class.php';
	
	require_once 'tokenHandlers/T_OPEN_TAG.php';
	TokenHandlers\openTagHandler::getInstance();
	
	new CLI;
?>