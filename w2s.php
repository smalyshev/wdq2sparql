<?php
$text = "Nothing yet...";
ini_set('xdebug.max_nesting_level', 10000);
if(!empty($_POST['wdq'])) {
	require_once __DIR__.'/WDQ.php';
	$parser = new WDQParser();
	$parsed = $parser->parse($_POST['wdq']);
	if(!$parsed) {
		$text = "Failed to parse the query";
	} else {
		$exp = $parser->generate($parsed, "?item");
		$sparql = $exp->emit('  ');
		$text = "SELECT ?item WHERE {\n$sparql}";
	}
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>WDQ2SPARQL</title>
</head>
<body>
<div style="float: right">
Supported syntax:<br>
<ul>
<li>claim, noclaim
<li>tree/web
<li>quantity/between/string
<li>and/or
<li>Subqueries within claim/noclaim
</ul>
Not supported yet:<br>
<ul>
<li>Subqueries within tree/web
<li>around
<li>qualifiers
<li>link
</ul>
</div>
<form action="w2s.php" method="POST">
Please enter WDQ query:<br>
<textarea cols="80" rows="10" name="wdq">
<?= @$_POST['wdq']; ?>
</textarea><br>
<input type="submit" value="Translate"/>
</form>
<br clear="all">
<hr>
Translation to SPARQL:<br>
<pre>
<?= $text; ?>
</pre>
</body>
</html>