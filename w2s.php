<?php
$text = "What?";
if(!empty($_POST['wdq'])) {
	require_once 'WDQ.php';
	$parser = new WDQParser();
	$parsed = $parser->parse($_POST['wdq']);
	if(!$parsed) {
		$text = "Failed to parse the query";
	} else {
		$exp = $parser->generate($parsed);
		$sparql = $exp->emit('  ');
		$text = "SELECT ?item WHERE {\n$sparql}";
	}
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Insert title here</title>
</head>
<body>
<form action="w2s.php" method="POST">
Please enter WDQ query:<br>
<textarea cols="80" rows="10" name="wdq">
<?= @$_POST['wdq']; ?>
</textarea><br>
<input type="submit" value="Translate"/>
</form>
<hr>
SPARQL:<br>
<pre>
<?= $text; ?>
</pre>
</body>
</html>