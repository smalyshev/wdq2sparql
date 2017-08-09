<?php
$text = "Nothing yet...";
$run = false;
$status = 200;
$syntax = "Wikidata";
ini_set('xdebug.max_nesting_level', 10000);
if(!empty($_REQUEST['wdq'])) {
	require_once __DIR__.'/WDQ.php';
	$parser = new WDQParser();
	$parsed = $parser->parse($_REQUEST['wdq']);
	if(!$parsed) {
		$text = "Failed to parse the query";
		$status = 400;
	} else {
	    if(!empty($_REQUEST['syntax'])) {
            $syntax = preg_replace("/[^a-zA-Z]/", "", $_REQUEST['syntax']);
        }
		$klass = "Sparql\\Syntax\\$syntax";
		if(class_exists($klass) && is_a($klass, "Sparql\\Syntax", true)) {
			$syntaxClass = new $klass;
			$exp = $parser->generate($parsed, "?item");
			$sparql = $exp->emit($syntaxClass, '  ');
			$text = '';
			foreach($syntaxClass->getPrefixes() as $pref => $url) {
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
			header("Access-Control-Allow-Origin: *");
			if(!empty($_REQUEST['jsonp'])) {
			    $funcname = preg_replace("/[^a-zA-Z0-9_]/", "", $_REQUEST['jsonp']);
			    $text = "$funcname(".json_encode($text).");";
                header("Content-type: application/javascript");
            } else {
                header("Content-type: text/plain");
            }
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
<link rel="stylesheet" href="//tools-static.wmflabs.org/cdnjs/ajax/libs/github-fork-ribbon-css/0.2.0/gh-fork-ribbon.min.css" />
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
    <li>qualifiers
</ul>
Not supported yet:<br>
<ul>
<li>Subqueries within tree/web
</ul>
</div>
<form action="w2s.php" method="POST">
<input type="hidden" name="gui" value="1">
Please enter WDQ query:<br>
<textarea cols="80" rows="10" name="wdq" style="width: 40em">
<?= @$_POST['wdq']; ?>
</textarea><br>
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
<a target="_blank" href="<?=$runURLs[$syntax].rawurlencode($text); ?>">Run this query!</a>
<?php } ?>
<a class="github-fork-ribbon" href="https://github.com/smalyshev/wdq2sparql" data-ribbon="Fork me on GitHub" title="Fork me on GitHub">Fork me on GitHub</a>
</body>
</html>
