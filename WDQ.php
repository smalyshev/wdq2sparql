<?php
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/SparqlGenerator.php';

use ParserGenerator\SyntaxTreeNode;

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

	private $counter = 0;

	public function __construct()
	{
		$this->parser = new \ParserGenerator\Parser($this->grammar, array("ignoreWhitespaces" => true));
	}

	private function dump($x) {
		echo "<pre>";
		var_dump($x);
		echo "</pre>";

	}

	protected function generateItem(SyntaxTreeNode\Branch $item) {
		$sub = $item->getSubnode(0);
		$left = $sub->getSubnode(0);
		if($left && !$left->isBranch() && $left->getContent() == "(") {
			$subvarName = "?sub".$this->counter++;
			$subexp = $this->generate($sub->getSubnode(1), $subvarName);
			return new SparqlSubquery($subvarName, $subexp);
		} else {
			$itemno = $item->getLeftLeaf()->getContent();
			return new SparqlItem($itemno);
		}
	}

	public function generate(SyntaxTreeNode\Branch $tree, $itemName)
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
				return $this->generate($tree->getSubnode(0), $itemName);
			case 'expressionpart':
				if(!$tree->getSubnode(0)->isBranch()) {
					// ( case
					return $this->generate($tree->getSubnode(1), $itemName);
				}
				return $this->generate($tree->getSubnode(0), $itemName);
			case 'expression':
				$op = $tree->getSubnode(1);
				if($op && !$op->isBranch()) {
					$left = $this->generate($tree->getSubnode(0), $itemName);
					$right = $this->generate($tree->getSubnode(2), $itemName);
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
					return $this->generate($subnode, $itemName);
				}
				break;
			case 'claim':
				$pid = $tree->getSubnode(1)->getLeftLeaf()->getContent();
				$items = array();
				foreach($tree->findAll('Item') as $item) {
					$items[] = new SparqlClaim($itemName, $pid, $this->generateItem($item) );
				}
				if(count($items) == 1) {
					return $items[0];
				}
				return new SparqlUnion($items);
			case 'noclaim':
				$pid = $tree->getSubnode(1)->getLeftLeaf()->getContent();
				$items = array();
				foreach($tree->findAll('Item') as $item) {
					$items[] = new SparqlNoClaim($itemName, $pid, $this->generateItem($item) );
				}
				if(!$items) {
					return new SparqlNoClaim($itemName, $pid, new SparqlSubquery("?dummy".$this->counter++) );
				}
				if(count($items) == 1) {
					return $items[0];
				}
				return new SparqlAnd($items);
			case 'string':
				$pid = $tree->getSubnode(1)->getLeftLeaf()->getContent();
				$items = array();
				foreach($tree->findAll('LiteralString') as $item) {
					$items[] = new SparqlString($itemName, $pid, $item->getLeftLeaf()->getContent());
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
						return new SparqlBetween($itemName, $pid, $subnumbers[0]->getLeftLeaf()->getContent() );
					case 2:
						return new SparqlBetween($itemName, $pid, null, $subnumbers[1]->getLeftLeaf()->getContent() );
					case 3:
						return new SparqlBetween($itemName, $pid, $subnumbers[0]->getLeftLeaf()->getContent(),
							 $subnumbers[2]->getLeftLeaf()->getContent() );
					default:
						throw new Exception("Weird number of args for Between");
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
				return new SparqlQuantity($itemName, $pid, $low, $high);
			case 'tree':
				$extract = function ($it) { return $it->getLeftLeaf()->getContent(); };
				if($tree->getSubnode(3)->isBranch()) {
					$forward = array_map($extract, $tree->getSubnode(3)->findAll('Number'));
				} else {
					$forward = array();
				}
				$back = $tree->getSubnode(5);
				if($back && $back->isBranch()) {
					$backward = array_map($extract, $back->findAll('Number'));
				} else {
					$backward = array();
				}
				$items = array_map(
					 function ($it) use ($forward, $backward, $itemName) {
					 	return new SparqlTree($itemName, $it->getLeftLeaf()->getContent(), $forward, $backward );
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
						function ($it) use ($props, $itemName) {
							return new SparqlTree($itemName, $it->getLeftLeaf()->getContent(), $props, $props );
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
// match("