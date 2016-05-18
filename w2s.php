<?php
$text = "Nothing yet...";
$run = false;
$status = 200;
ini_set('xdebug.max_nesting_level', 10000);
if(!empty($_REQUEST['wdq'])) {
	require_once __DIR__.'/WDQ.php';
	$parser = new WDQParser();
	$parsed = $parser->parse($_REQUEST['wdq']);
	if(!$parsed) {
		$text = "Failed to parse the query";
		$status = 400;
	} else {
		$syntax = preg_replace("/[^a-zA-Z]/", "",$_REQUEST['syntax']);
		if(!$syntax) {
			$syntax = "Wikidata";
		}
		$klass = "Sparql\\Syntax\\$syntax";
		if(class_exists($klass) && is_a($klass, "Sparql\\Syntax", true)) {
			$syntax = new $klass;
			$exp = $parser->generate($parsed, "?item");
			$sparql = $exp->emit($syntax, '  ');
			$text = '';
			foreach($syntax->getPrefixes() as $pref => $url) {
				$text .= "prefix $pref: <$url>\n";
			}
			$text .= "SELECT ?item WHERE {\n$sparql}";
			$run = true;
		} else {
			$text = "Unknown syntax";
			$status = 400;
		}
		if(empty($_POST['gui'])) {
			// Labs runs 5.3, 5.3 does not have http_response_code(). Sad.
			header("HTTP/1.0 $status Banana");
			header("Content-type: text/plain");
			echo $text;
			exit();
		}
	}
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link href="//tools-static.wmflabs.org/cdnjs/ajax/libs/twitter-bootstrap/2.3.1/css/bootstrap.min.css" rel="stylesheet">
<title>WDQ2SPARQL</title>
</head>
<body style="margin: 10px">
<div style="float: right; margin-right: 100px">
Supported syntax:<br>
<ul>
<li>claim, noclaim
<li>tree/web
<li>quantity/between/string
<li>and/or
<li>Subqueries within claim/noclaim
<li>link/nolink
<li>around
</ul>
Not supported yet:<br>
<ul>
<li>Subqueries within tree/web
<li>qualifiers
</ul>
</div>
<form action="w2s.php" method="POST">
<input type="hidden" name="gui" value="1">
Please enter WDQ query:<br>
<textarea cols="80" rows="10" name="wdq" style="width: 40em">
<?= @$_POST['wdq']; ?>
</textarea><br>
Syntax: <select name="syntax">
<option label="Wikidata RDF syntax">Wikidata</option>
<option label="WDTK Syntax">WDTK</option>
</select><br>
<input type="submit" value="Translate"/>
<br clear="all">
</form>
<hr>
Translation to SPARQL:<br>
<pre>
<?= htmlentities($text); ?>
</pre>
<?php if($run) {
	$runURLs = array('Wikidata' => 'http://query.wikidata.org/#', "WDTK" => 'http://milenio.dcc.uchile.cl/sparql?query=');
?>
<a target="_blank" href=" <?=$runURLs[$_POST['syntax']].rawurlencode($text); ?>">Run this query!</a>
<?php } ?>
<a href="https://github.com/smalyshev/wdq2sparql"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/38ef81f8aca64bb9a64448d0d70f1308ef5341ab/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f6461726b626c75655f3132313632312e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_darkblue_121621.png"></a>
</body>
</html>
