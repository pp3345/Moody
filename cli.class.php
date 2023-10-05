<?php

	/****************************************************************/
	/* Moody                                                        */
	/* cli.class.php                                     			*/
	/* 2012 Yussuf Khalil                                           */
	/****************************************************************/
	
	namespace Moody;

	/**
	 * Command line interface for Moody
	 */
	class CLI  {
		private $argv = array();
		private $args = array();
		private $inExecute = false;
		
		public function main($argv) {
			$this->argv = $argv;
			unset($this->argv[0], $argv);
			
			set_error_handler(array($this, 'errorHandler'));
			
			entry:
			
			/* Load moody.cphp */
			if(!file_exists('moody.cphp')) {
				if(file_exists('build.php') && is_readable('build.php')) {
					echo "moody.cphp not found. Build from source now? (y/n) ";
					if(strtolower(fread(\STDIN, 1)) != 'y') {
						echo "Aborting." . \PHP_EOL;
						exit;
					} else {
						require_once 'build.php';
						
						/* Do not load moody.cphp as build.php already has included the Moody sources */
						goto loaded;
					}
				} else {
					echo "moody.cphp not found." . \PHP_EOL;
					exit;
				}
			}
			
			if(!is_readable('moody.cphp')) {
				echo "moody.cphp exists but is not readable." . \PHP_EOL;
				exit;
			}
			
			require_once 'moody.cphp';
			
			loaded:
			
			/* Load token handlers */
			foreach(get_declared_classes() as $class) {
				if(in_array('Moody\TokenHandler', class_implements($class)))
					$class::getInstance();
			}
			
			/* Parse arguments */
			foreach($this->argv as $arg) {
				if(substr($arg, 0, 1) != '-')  {
					if(isset($executeFile))
						$destinationFile = $arg;
					else
						$executeFile = $arg;
					break;
				}
			}
			
			$this->args = getopt("", array('benchmark', 'silent', 'dump'));
			
			if(isset($executeFile)) {
				$source = $this->executeFile($executeFile);
				
				if($source === false)
					exit;
				
				if(isset($destinationFile)) {
					if(!file_put_contents($destinationFile, $source))
						echo "Failed to put new source into destination file." . \PHP_EOL;
				}
				
				if(isset($this->args['dump'])) {
					echo \PHP_EOL . "Generated source: " . \PHP_EOL;
					echo $source . \PHP_EOL;
				}
				
				exit;
			}
			
			echo "Moody CLI Interpreter v" . MOODY_VERSION . \PHP_EOL;
			echo "2012 Yussuf Khalil" . \PHP_EOL;
		}
		
		public function executeFile($fileName) {
			if(!file_exists($fileName)) {
				echo "File does not exist." . \PHP_EOL;
				return false;
			}
			
			if(!is_readable($fileName)) {
				echo "File is not readable." . \PHP_EOL;
				return false;
			}
			
			$file = file_get_contents($fileName);
			
			if(substr($fileName, -3) == 'mdy' || substr($fileName, -5) == "moody") {
				Configuration::set("requireinstructiondot", false);
				/* Satisfy the tokenizer */
				$file = '<?php ' . $file . ' ?>';
			}
			
			return $this->executeSource($file, $fileName, true);
		}
		
		public function executeSource($source, $origin = "Unknown", $appendT_EOF = false) {
			$tokenArray = Token::tokenize($source, $origin);
			if($appendT_EOF) {
				$token = new Token;
				$token->type = T_EOF;
				$token->fileName = $origin;
				$tokenArray[] = $token;
			}
			$source = $this->executeScript($tokenArray);
			
			if(is_array($source)) {
				$sourceString = "";
				
				foreach($source as $token)
					$sourceString .= $token->content;
				
				return $sourceString;
			}
			
			return $source;
		}
		
		public function executeScript($tokenArray) {
			try {
				ConstantContainer::initialize();
				
				$vm = new TokenVM;
				
				$this->inExecute = true;
				
				if(isset($this->args['benchmark'])) {
					$timeStart = microtime(true);
				}
				
				$tokenArray = $vm->execute($tokenArray);
				
				if(isset($this->args['benchmark'])) {
					echo "Script execution took " . ((microtime(true) - $timeStart) * 1000) . " ms." . \PHP_EOL;
				}
				
				$this->inExecute = false;
				return $tokenArray;
			} catch(\Exception $exception) {
				if(isset($this->args['benchmark']))
					$executionTime = (microtime(true) - $timeStart) * 1000;
				
				echo (string) $exception . \PHP_EOL;
				
				if(isset($this->args['benchmark'])) {
					echo "Script execution took " . $executionTime . " ms." . \PHP_EOL;
				}
				
				$this->inExecute = false;
				return false;
			}
		}
		
		public function errorHandler($errType, $errStr, $errFile, $errLine) {
			if($this->inExecute)
				throw new MoodyException($errStr, $errType);
			
			throw new \ErrorException($errStr, 0, $errType, $errFile, $errLine);
		}
	}

	$cli = new CLI;
	$cli->main($argv);
?>