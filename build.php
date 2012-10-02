<?php

	/****************************************************************/
	/* Moody                                                        */
	/* build.php                                     				*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody;
	
	echo 'Building Moody...' . "\r\n";
	
	require_once 'configuration.class.php';
	require_once 'constantContainer.class.php';
	require_once 'ifInstruction.class.php';
	require_once 'instructionHandler.interface.php';
	require_once 'instructionProcessorException.class.php';
	require_once 'moodyException.class.php';
	require_once 'token.class.php';
	require_once 'tokenHandler.interface.php';
	require_once 'tokenVM.class.php';
	require_once 'VMException.class.php';
	
	$source = '<?php ';
	
	$source .= '/* .mapVariable \'$message\' \'$message\' */';
	$source .= '/* .mapVariable \'$code\' \'$code\' */';
	$source .= '/* .include "configuration.class.php" */';
	$source .= '/* .include "constantContainer.class.php" */';
	$source .= '/* .include "ifInstruction.class.php" */';
	$source .= '/* .include "instructionHandler.interface.php" */';
	$source .= '/* .include "instructionProcessorException.class.php" */';
	$source .= '/* .include "moodyException.class.php" */';
	$source .= '/* .include "token.class.php" */';
	$source .= '/* .include "tokenHandler.interface.php" */';
	$source .= '/* .include "tokenVM.class.php" */';
	$source .= '/* .include "VMException.class.php" */';
	
	$dir = scandir('tokenHandlers');
	
	foreach($dir as $file) {
		if(substr($file, -4, 4) != ".php")
			continue;
	
		require_once 'tokenHandlers/' . $file;
		
		$source .= '/* .echo "Processing ' . $file . '...\r\n" */';
		$source .= '/* .include "tokenHandlers/' . $file . '" */';
	}
	
	$dir = scandir('instructionHandlers');
	
	foreach($dir as $file) {
		if(substr($file, -4, 4) != ".php")
			continue;
	
		require_once 'instructionHandlers/' . $file;
		
		$source .= '/* .echo "Processing ' . $file . '...\r\n" */';
		$source .= '/* .include "instructionHandlers/' . $file . '" */';
	}
	
	$source .= '?>';
	
	foreach(get_declared_classes() as $class) {
		if(in_array('Moody\TokenHandler', class_implements($class)) || in_array('Moody\InstructionHandler', class_implements($class)))
			$class::getInstance();
	}
	
	ConstantContainer::initialize();
	
	Configuration::set('deletewhitespaces', true);
	Configuration::set('compressvariables', true);
	Configuration::set('compressproperties', true);
	Configuration::set('deletecomments', true);
	
	try {
		$tokens = Token::tokenize($source, 'Moody Builder');
	
		$vm = new TokenVM;
		$result = $vm->execute($tokens);
	} catch(\Exception $e) {
		echo (string) $e;
		exit;
	}
	
	$code = "";
	
	foreach($result as $token)
		$code .= $token->content;
	
	file_put_contents('moody.cphp', $code);
	
	echo 'Build complete.' . "\r\n";
?>