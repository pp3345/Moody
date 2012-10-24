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
			echo 'Please enter file to process: ';
			
			$fileName = str_replace(array("\r\n", "\n", "\r"), "", fread(STDIN, 128));
			if(!file_exists($fileName)) {
				echo "\r\nInvalid filename\r\n";
				goto enter;
			}
			
			ConstantContainer::initialize();
			$tokenArray = Token::tokenize(file_get_contents($fileName), $fileName);
			$vm = new TokenVM();
			try {
				$tokenArray = $vm->execute($tokenArray);
			} catch(\Exception $e) {
				echo (string) $e . "\r\n";
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
	
	require_once 'moody.cphp';
	
	foreach(get_declared_classes() as $class) {
		if(in_array('Moody\TokenHandler', class_implements($class)) || in_array('Moody\InstructionHandler', class_implements($class)))
			$class::getInstance();
	}
	
	ConstantContainer::initialize();
	
	new CLI;
?>