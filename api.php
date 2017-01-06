<?php
$status = 200;
ini_set('xdebug.max_nesting_level', 10000);

require_once __DIR__.'/W2SConverter.php';

// Inputs
$format = 'json'; // (json|text)
$indent = '  ';
$syntax = 'Wikidata'; // See Sparql/Syntax for valid syntaxes

// Outputs
$sparql;
$error;

if (!empty($_REQUEST['format'])) $format = strtolower($_REQUEST['format']);
if (!preg_match("/^(json|text)$/i", $format)) {
	header('Status: 400 Bad Request', true, 400);
	exit();
}

if (!is_null($_REQUEST['indent'])) $indent = $_REQUEST['indent'];
if (!empty($_REQUEST['syntax'])) $syntax = $_REQUEST['syntax'];

try {
	$converter = new W2SConverter();
	$sparql = $converter->convert($_REQUEST['wdq'], $syntax, $indent);
} catch (Exception $e) {
	$error = $e->getMessage();
}

switch ($format) {
	case 'text':
		header("Content-type: text/plain");
		if (isset($error)) echo "Error: $error";
		else if (isset($sparql)) echo $sparql;
		break;
	case 'json':
	default:
		$object = array();
		if (isset($sparql)) $response['sparql'] = $sparql;
		if (isset($error)) $response['error'] = $error;

		$json = json_encode($response);
		if (!empty($_REQUEST['callback'])) {
			header("Content-type: application/javascript");
			echo "{$_REQUEST['callback']}($json)";
		} else {
			header("Content-type: application/json");
			echo $json;
		}
}
