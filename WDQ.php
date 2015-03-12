<?php
require_once 'vendor/autoload.php';
require_once 'SparqlGenerator.php';

class WDQParser {
	private $grammar = <<<ENDG
start :=> Expression .

Expression
		:=> ExpressionPart
		:=> Expression "AND" ExpressionPart
		:=> Expression "OR" ExpressionPart
		.

ExpressionPart
		:=> Clause
		:=> "(" Expression ")" .

Clause :=> ( Claim | NoClaim | String | Between | Quantity | Tree | Web) .

Claim :=> "CLAIM[" Number ":" Item+"," "]" .

NoClaim :=> "NOCLAIM[" Number (":" Item+",")? "]" .

Item :=> (Number | "(" Expression ")" ) .

String :=> "STRING[" Number ":" LiteralString+"," "]" .

Between :=> "BETWEEN[" Number "," BetweenParams "]" .

BetweenParams
		:=> Date
		:=> "," Date
		:=> Date "," Date .

Quantity :=> "QUANTITY[" Number ":" Number ("," Number)? "]".

Tree :=> "TREE[" Item+"," "][" PropList? "]" ("[" PropList? "]")? .

Web :=> "WEB[" Item+"," "][" PropList? "]" .

PropList :=> Number+"," .

Number :=> /\d+/ .
LiteralString :=> /"[^"]*?"/ .
Date :=> /[+-]?\d+(-\d{2}(-\d{2}(T\d{2}:\d{2}:\d{2}Z)?)?)?/ .
ENDG;

	public function __construct()
	{
		$this->parser = new \ParserGenerator\Parser($this->grammar, array("ignoreWhitespaces" => true));
	}

	public function generate($tree)
	{
		$res = "";
		if(!$tree) {
			throw new Exception("Tree missing!");
		}
		if(!$tree->isBranch()) {
			var_dump($tree);
			throw new Exception("Unexpected leaf!");
		}
		switch(strtolower($tree->getType())) {
			case 'clause':
			case 'start':
				return $this->generate($tree->getSubnode(0));
			case 'expressionpart':
				if(!$tree->getSubnode(0)->isBranch()) {
					// ( case
					return $this->generate($tree->getSubnode(1));
				}
				return $this->generate($tree->getSubnode(0));
			case 'expression':
				$op = $tree->getSubnode(1);
				if($op && !$op->isBranch()) {
					$left = $this->generate($tree->getSubnode(0));
					$right = $this->generate($tree->getSubnode(2));
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
					return $this->generate($subnode);
				}
				break;
			case 'claim':
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
			case 'noclaim':
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
			case 'string':
				$pid = $tree->getSubnode(1)->getLeftLeaf()->getContent();
				$items = array();
				foreach($tree->findAll('LiteralString') as $item) {
					$items[] = new SparqlString($pid, $item->getLeftLeaf()->getContent());
				}
				if(count($items) == 1) {
					return $items[0];
				}
				return new SparqlUnion($items);
			case 'between':
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
					default:
						throw new Exception("Weird number of args for Betweeen");
				}
				break;
			case 'quantity':
				$pid = $tree->getSubnode(1)->getLeftLeaf()->getContent();
				$low = $tree->getSubnode(3)->getLeftLeaf()->getContent();
				$sub = $tree->getSubnode(4);
				if($sub && $sub->isBranch()) {
					$high = $sub->getSubnode(1)->getLeftLeaf()->getContent();
				} else {
					$high = null;
				}
				return new SparqlQuantity($pid, $low, $high);
			case 'tree':
				$extract = function ($it) { return $it->getLeftLeaf()->getContent(); };
				$forward = array_map($extract, $tree->getSubnode(3)->findAll('Number'));
				$back = $tree->getSubnode(5);
				if($back && $back->isBranch()) {
					$backward = array_map($extract, $back->findAll('Number'));
				} else {
					$backward = array();
				}
				$items = array_map(
					 function ($it) use ($forward, $backward) {
					 	return new SparqlTree($it->getLeftLeaf()->getContent(), $forward, $backward);
					 },
					 $tree->findAll('Item')
				);
				if(count($items) == 1) {
					return $items[0];
				}
				return new SparqlUnion($items);
			case 'web':
				$extract = function ($it) { return $it->getLeftLeaf()->getContent(); };
				$props = array_map($extract, $tree->getSubnode(3)->findAll('Number'));
				$items = array_map(
						function ($it) use ($props) {
							return new SparqlTree($it->getLeftLeaf()->getContent(), $props, $props);
						},
						$tree->findAll('Item')
				);
				if(count($items) == 1) {
					return $items[0];
				}
				return new SparqlUnion($items);
			default:
				throw new Exception("Unknown type {$tree->getType()}");
		}
		return "";
	}

	public function parse($str) {
		return $this->parser->parse($str);
	}
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
// match('CLAIM[31:5] OR BETWEEN[5,1880,1990-05]');
// match('CLAIM[31:5] AND BETWEEN[5,+00000001861-03-17T00:00:00Z]');
// match('CLAIM[31:5] AND BETWEEN[5,,-10000861-03-17]');
// match("TREE[30][150][17,131]");
// match("CLAIM[138:676555] AND NOCLAIM[31:515]");
// match("(TREE[30][150][17,131] AND CLAIM[138:676555])");
// match("TREE[4504][171,273,75,76,77,70,71,74,89]");
// match("WEB[9682][25,22,40,26,7,9,1038]");