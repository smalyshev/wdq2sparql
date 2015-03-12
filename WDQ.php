<?php
require_once 'SparqlGenerator.php';

// function match($str) {
// 	$x = new WDQParser($str) ;
// 	$res = $x->match_Expression() ;
// 	if ( $res === FALSE ) {
// 		print "No Match\n" ;
// 	}
// 	else {
// 		print_r( $res ) ;
// 	}

// }
$grammar = <<<ENDG
start :=> Expression .

Expression
		:=> ExpressionPart
		:=> Expression "AND" ExpressionPart
		:=> Expression "OR" ExpressionPart
		.

ExpressionPart
		:=> Clause
		:=> "(" Expression ")" .

Clause :=> ( Claim | NoClaim | String | Between | Quantity) .

Claim :=> "CLAIM[" Number ":" Item+"," "]" .

NoClaim :=> "NOCLAIM[" Number (":" Item+",")? "]" .

Item :=> ( Number | "(" Expression ")"  ) .

String :=> "STRING[" Number ":" LiteralString+"," "]" .

Between :=> "BETWEEN[" Number "," BetweenParams "]" .

BetweenParams
		:=> Date
		:=> "," Date
		:=> Date "," Date .

Quantity :=> "QUANTITY[" Number ":" Number ("," Number)? "]".

Number :=> /\d+/ .
LiteralString :=> /"[^"]*?"/ .
Date :=> /[+-]?\d+(-\d{2}(-\d{2}(T\d{2}:\d{2}:\d{2}Z)?)?)?/ .
ENDG;


require_once 'vendor/autoload.php';

function generate($tree) {
	static $counter = 0;

	$res = "";
	if(!$tree) {
		debug_print_backtrace();
		return;
	}
	if(!$tree->isBranch()) {
		var_dump($tree);
		return "LEAF";
	}
	switch($tree->getType()) {
		case 'Clause':
		case 'start':
			return generate($tree->getSubnode(0));
		case 'ExpressionPart':
			if(!$tree->getSubnode(0)->isBranch()) {
				// ( case
				return generate($tree->getSubnode(1));
			}
			return generate($tree->getSubnode(0));
		case 'Expression':
			$op = $tree->getSubnode(1);
			if($op && !$op->isBranch()) {
				$left = generate($tree->getSubnode(0));
				$right = generate($tree->getSubnode(2));
				if($op->getContent() == "OR") {
					return SparqlUnion::addTwo($left, $right);
				} else {
					// AND
					return SparqlAnd::addTwo($left, $right);
				}
			}
			foreach($tree->getSubnodes() as $subnode) {
				if(!$subnode->isBranch()) {
					continue;
				}
				return generate($subnode);
			}
			break;
		case 'Claim':
			$pid = $tree->getSubnode(1)->getLeftLeaf()->getContent();
			$items = array();
			foreach($tree->findAll('Item') as $item) {
				$itemno = $item->getLeftLeaf()->getContent();
				$items[] = new SparqlClaim($pid, $itemno);
			}
			if(count($items) == 1) {
				return $items[0];
			}
			return new SparqlUnion($items);
		case 'NoClaim':
			$pid = $tree->getSubnode(1)->getLeftLeaf()->getContent();
			$items = array();
			foreach($tree->findAll('Item') as $item) {
				$itemno = $item->getLeftLeaf()->getContent();
				$items[] = new SparqlNoClaim($pid, $itemno);
			}
			if(!$items) {
				return new SparqlNoClaim($pid);
			}
			if(count($items) == 1) {
				return $items[0];
			}
			return new SparqlAnd($items);
		case 'String':
			$pid = $tree->getSubnode(1)->getLeftLeaf()->getContent();
			$items = array();
			foreach($tree->findAll('LiteralString') as $item) {
				$items[] = new SparqlString($pid, $item->getLeftLeaf()->getContent());
			}
			if(count($items) == 1) {
				return $items[0];
			}
			return new SparqlUnion($items);
		case 'Between':
			$pid = $tree->getSubnode(1)->getLeftLeaf()->getContent();
			$numbers = $tree->getSubnode(3);
			$subnumbers = $numbers->getSubnodes();
			switch(count($subnumbers)) {
				case 1:
					return new SparqlBetween($pid, $subnumbers[0]->getLeftLeaf()->getContent(), null);
				case 2:
					return new SparqlBetween($pid, null, $subnumbers[1]->getLeftLeaf()->getContent());
				case 3:
					return new SparqlBetween($pid, $subnumbers[0]->getLeftLeaf()->getContent(),
						 $subnumbers[2]->getLeftLeaf()->getContent());
			}
			break;
		case 'Quantity':
			$pid = $tree->getSubnode(1)->getLeftLeaf()->getContent();
			$low = $tree->getSubnode(3)->getLeftLeaf()->getContent();
			$sub = $tree->getSubnode(4);
			if($sub && $sub->isBranch()) {
				$high = $sub->getSubnode(1)->getLeftLeaf()->getContent();
			} else {
				$high = null;
			}
			return new SparqlQuantity($pid, $low, $high);
		default:
			var_dump($tree->getType());
	}
	var_dump($tree);
	return $res;
}

function match($str) {
	global $grammar;
	$parser = new \ParserGenerator\Parser($grammar, array("ignoreWhitespaces" => true));
	$parsed = $parser->parse($str);
	if(!$parsed) {
		echo "Failed on $str\n";
		return;
	}
	$exp = generate($parsed);
	echo "\nEXP: $str\nPARSED: \n".$exp->emit();
}

// match("CLAIM[31:5]");
// match("(CLAIM[31:5])");
// match("CLAIM[31:5] AND CLAIM[27:801]");
// match("CLAIM[31:5] AND CLAIM[27:801] AND NOCLAIM[21]");
// match("CLAIM[31:5] AND CLAIM[27:801] AND NOCLAIM[21:34,56]");
// match("CLAIM[31:5] OR CLAIM[27:801] AND CLAIM[45:37]");
// match("CLAIM[31:5,67] OR CLAIM[27:801] AND NOCLAIM[45]");
// match("CLAIM[31:5] AND (CLAIM[27:801] OR NOCLAIM[1:23,45])");
// match("CLAIM[31:5] AND CLAIM[27:801] OR NOCLAIM[1:23,45]");
// match('CLAIM[31:5] AND STRING[5:"TEST"]');
// match('CLAIM[31:5] OR QUANTITY[5:10,20]');
// match('CLAIM[31:5] AND QUANTITY[5:42]');
match('CLAIM[31:5] OR BETWEEN[5,1880,1990-05]');
match('CLAIM[31:5] AND BETWEEN[5,+00000001861-03-17T00:00:00Z]');
match('CLAIM[31:5] AND BETWEEN[5,,-10000861-03-17]');
