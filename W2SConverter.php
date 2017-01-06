<?php

require_once __DIR__.'/WDQ.php';

class W2SConverter {
	private $parser;

	public function __construct() {
		$this->parser = new WDQParser();
	}

	public function convert($wdq, $syntaxName, $indent = '  ') {
		$parsed = $this->parser->parse($wdq);
		if (!$parsed) throw new Exception('Failed to parse the query');
		$syntaxClass = W2SConverter::getSyntaxClass($syntaxName);
		$syntax = new $syntaxClass;
		$exp = $this->parser->generate($parsed, "?item");
		$sparql = $exp->emit($syntax, $indent);
		$result = '';
		foreach($syntax->getPrefixes() as $pref => $url) {
			$result .= "prefix $pref: <$url>\n";
		}
		$result .= "SELECT ?item WHERE {\n$sparql}";
		return $result;
	}

	// Gets the class from the given syntax name.
	// Throws errors if invalid.
	protected static function getSyntaxClass($syntaxName) {
		$klass;
		$valid = preg_match("/^[a-zA-Z]+$/", $syntaxName);
		if ($valid) {
			$klass = "Sparql\\Syntax\\$syntaxName";
			$valid = class_exists($klass) && is_a($klass, "Sparql\\Syntax", true);
		}

		if ($valid) return $klass;
		else throw new Exception('Unrecognized syntax');
	}
}
