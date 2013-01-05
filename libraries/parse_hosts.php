<?php
require_once 'cli.php';

class ParseHosts {

	protected $identifier = "Compiled with ParseHosts";
	protected $rawHosts;
	protected $map;
	protected $entries;

	public function __construct() {
		try {
			$this->rawHosts = file_get_contents("/etc/hosts");
		} catch (exception $e) {
			out("Failure:",$e->getMessage());
			exit;
		}
		$this->rawHosts = explode("\n",str_replace(
			["\n\r", "\r\n", "\r"],
			["\n",   "\n",   "\n"],
			$this->rawHosts));
		$this->getEntries();
	}

	private function getEntries() {
		$rh = $this->rawHosts;
		$firstline = implode(array_slice($rh, 0, 1));
		$firstline = ltrim($firstline,'# ');

		if ($firstline == $this->identifier) {
			$this->_parseClean();
		} else {
			$this->_parseRaw();
		}
	}

	private function categoryHeader($category) {
		$len = 80;

		$left = floor(($len - 2 - strlen($category)) / 2);
		$right = $len - 2 - strlen($category) - $left;

		$header = "\n\n".str_repeat("#", $len);
		$header.= "\n#".str_repeat(' ',$left).$category.str_repeat(' ', $right)."#\n";
		$header.= str_repeat("#", $len)."\n";
		return $header;
	}

	private function hostsByCategory() {
		$hosts = $this->entries;
		$hbc = [];
		foreach($hosts as $host) {
			if (!isset($hbc[$host->category])) $hbc[$host->category] = [];
			$hbc[$host->category][] = $host;
		}
		return $hbc;
	}

	public function _export() {
		elevate();
		try {
			$output = $this->_output();
			file_put_contents("/etc/hosts", $output);
		} catch (exception $e) {
			fail($e->getMessage());
		}
		return true;
	}

	public function _output() {
		$hostsfile = "# $this->identifier\n";
		$categories = $this->hostsByCategory();

		foreach ($categories as $category=>$hosts) {
			if (!count($hosts)) continue;
			$hostsfile .= $this->categoryHeader($category);
			foreach ($hosts as $host) {
				if (!($host instanceof ParseHostsHost)) continue;
				$spaces = str_repeat(" ", 80-strlen($host->ip)-strlen($host->domain));
				$hostsfile .= "$host->ip{$spaces}$host->domain\n";
			}
		}
		return $hostsfile."\n";
	}

	private function _parseClean() {
		$rh = $this->rawHosts;

		$hosts = [];
		$category = '';
		foreach ($rh as $line=>$text) {
			if (substr($text,0,1) == '#') {
				if (substr($text,0,2) == '##') {
					continue;
				}
				$category = trim($text,'# ');
				continue;
			}
			if (!$text) continue;
			if (!preg_match("/^[ \t]*(.+?)[ \t]+(.+?)[\t ]*$/", $text, $matches)) {
				$host = new ParseHostsHost("",$text,$line,"Cant Parse");
				$hosts[$text] = $host;
			} else {
				list($full,$ip,$domain) = $matches;
				$host = new ParseHostsHost($domain,$ip,$line,$category);
				$hosts[$domain] = $host;
			}
		}
		$this->entries = $hosts;
	}

	public function removeHost($domain) {
		unset($this->entries[$domain]);
	}

	public function addHost($domain,$ip='127.0.0.1',$category='Uncategorized') {
		$host = new ParseHostsHost($domain,$ip,0,$category);
		$this->entries[$domain] = $host;
		return $host;
	}

	private function _parseRaw() {
		$rh = $this->rawHosts;

		$hosts = [];
		foreach ($rh as $line=>$text) {
			if (substr(ltrim($text),0,1) == '#') continue;
			if (!$text) continue;
			if (!preg_match("/^[ \t]*(.+?)[ \t]+(.+?)[\t ]*$/", $text, $matches)) {
				$host = new ParseHostsHost("",$text,$line,"Cant Parse");
				$hosts[$text] = $host;
			} else {
				list($full,$ip,$domain) = $matches;
				$host = new ParseHostsHost($domain,$ip,$line,"Uncategorized");
				$hosts[$domain] = $host;
			}
		}
		$this->entries = $hosts;
	}

}

class ParseHostsHost {

	public $domain;
	public $ip;
	public $line;
	public $category;

	public function __construct($domain,$ip,$line,$category) {
		$this->domain = $domain;
		$this->ip = $ip;
		$this->line = $line;
		$this->category = $category;
	}
}












