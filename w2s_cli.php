<?php
require_once __DIR__.'/WDQ.php';
ini_set('xdebug.max_nesting_level', 10000);
if($argv[1] == "-") {
  $argv[1] = "php://stdin";
}
$fp = fopen($argv[1], "r");
while($s = fgets($fp)) {
$s = trim($s);
$parser = new WDQParser();
$parsed = $parser->parse($s);
if(!$parsed) {
	print("Failed to parse the query $s\n");
	continue;
} else {
	$klass = "Sparql\\Syntax\\Wikidata";
//	$klass = "Sparql\\Syntax\\WDTK";
	$syntax = new $klass;
	$exp = $parser->generate($parsed, "?item");
	$sparql = $exp->emit($syntax, '  ');
	$text = '';
	foreach($syntax->getPrefixes() as $pref => $url) {
		$text .= "prefix $pref: <$url>\n";
	}
	$text .= "SELECT ?item WHERE {\n$sparql}";
	echo "Origin: $s\nTranslated: $text\n";
}
}
