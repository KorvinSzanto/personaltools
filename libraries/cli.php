<?php
function out() {
	foreach (func_get_args() as $line) {
		echo $line."\n";
	}
}
function fail() {
	call_user_func_array('out', func_get_args());
	exit;
}
function elevate() {
	if (!testRun('sudo echo')) {
		throw new exception('Failed to elevate.');
		return false;
	}
	return true;
}

function yesOrNo() {
	$args = func_get_args();
	$last = array_pop($args);
	$last.= " - [ y / N ]";
	$args[] = $last;
	
	$res = call_user_func_array("getInput", $args);

	return substr(trim(strtolower($res)),0,1) == 'y';
}

function getInput() {
	call_user_func_array('out', func_get_args());
	return trim(fgets(STDIN),"\n");
}

function testRun() {
	try {
		exec(implode(';',func_get_args()),$error);
		if (array_filter($error)) {
			throw new exception($error);
		}
	} catch(exception $e) {
		echo $e;
		return false;
	}
	return true;
}

function runOutput($r) {
	return `$r`;
}
function run() {
	exec(implode(';',func_get_args()));
}

// http://stackoverflow.com/a/3439885
function exception_error_handler($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");

class InputParse {

	public $flagMap;

	public function argCount() {
		return count($this->arguments);
	}

	public function getArgument($key) {
		if (!isset($this->arguments[$key])) {
			return false;
		}
		return $this->arguments[$key];
	}

	public function getFlag($flag) {
		if (!isset($this->flags[$flag])) {
			return false;
		}
		return $this->flags[$flag];
	}

	function __construct($argv) {
		if (!$this->flagMap) {
			$this->flagMap = [
				'v'=>'verbose',
				'r'=>'recursive',
				'f'=>'force'
			];
		}
		$this->file = array_shift($argv);
		$this->arguments = [];
		$this->flags = [];
		foreach ($argv as $input) {
			if (substr($input, 0, 1) == '-') {
				// Last flag entry takes precedence.
				$flag = array_pad(explode('=',ltrim(strtolower($input),'-')),2,true);
				if (isset($this->flagMap[$flag[0]])) {
					$flag[0] = $this->flagMap[$flag[0]];
				}
				$this->flags[$flag[0]] = $flag[1]?:true;
			} else {
				$this->arguments[] = $input;
			}
		}
	}

}