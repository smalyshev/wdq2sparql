<?php
require_once __DIR__.'/vendor/autoload.php';

use ParserGenerator\SyntaxTreeNode;
use ParserGenerator\Parser;
use Sparql\AndClause;
use Sparql\Qualifiers;
use Sparql\Subquery;
use Sparql\Item;
use Sparql\Union;
use Sparql\Claim;
use Sparql\NoClaim;
use Sparql\StringLiteral;
use Sparql\Between;
use Sparql\Quantity;
use Sparql\Tree;
use Sparql\Unknown;
use Sparql\AnyItem;
use Sparql\Link;
use Sparql\NoLink;
use Sparql\GeoAround;

/**
 * Main WDQ parser class
 */
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

Clause :=> ( Claim | NoClaim | String | Between | Quantity | Tree | Web | Link | NoLink | Around ) .

Claim :=> "CLAIM[" Propvalue+"," "]" ( "{" Expression "}" )?.

NoClaim :=> "NOCLAIM[" Propvalue+"," "]" .

Propvalue :=> Number ( ":" Item )? .

Item :=> (Number | "(" Expression ")" ) .

String :=> "STRING[" Number ":" LiteralString+"," "]" ( "{" Expression "}" )?.

Between :=> "BETWEEN[" Number "," BetweenParams "]" ( "{" Expression "}" )?.

BetweenParams
		:=> Date
		:=> "," Date
		:=> Date "," Date .

Around :=> "AROUND[" Number "," Float "," Float "," Float "]" .

Quantity :=> "QUANTITY[" Number ":" Number ("," Number)? "]" ( "{" Expression "}" )?.

Tree :=> "TREE[" Item+"," "][" PropList? "]" ("[" PropList? "]")? .

Web :=> "WEB[" Item+"," "][" PropList? "]" .

Link :=> "LINK[" /\w+/ "]" .

NoLink :=> "NOLINK[" /\w+/ "]" .

PropList :=> Number+"," .

Number :=> /\d+/ .
LiteralString :=> /"[^"]*?"/ .
Date :=> /[+-]?\d+(-\d\d?(-\d\d?(T\d{2}:\d{2}:\d{2}Z)?)?)?/ .
Float :=> /\d+(\.\d+)?/
ENDG;

	/**
	 * Sub-items counter
	 * @var int
	 */
	private $counter = 0;

	public function __construct()
	{
		$this->parser = new Parser($this->grammar,
				array("ignoreWhitespaces" => true, 'caseInsensitive' => true)
			);
	}

	protected function generateItem(SyntaxTreeNode\Branch $item) {
		$sub = $item->getSubnode(0);
		$left = $sub->getSubnode(0);
		if($left && !$left->isBranch() && $left->getContent() == "(") {
			// we've got subquery
			$subvarName = "?sub".$this->counter++;
			$subexp = $this->generate($sub->getSubnode(1), $subvarName);
			return new Subquery($subvarName, $subexp);
		} else {
			// simple item
			$itemno = $item->getLeftLeaf()->getContent();
			if($itemno == '4294967294') {
				// Unknown item - special handling
				$subvarName = "?unk".$this->counter++;
				return new Unknown($subvarName);
			}
			return new Item($itemno);
		}
	}

	protected function getQualifiers(SyntaxTreeNode\Branch $tree, $itemName, $position, &$qname)
    {
        if($tree->getSubnode($position)->isBranch()) {
            $qname = $itemName."_st".$this->counter++;
            return $this->generate($tree->getSubnode($position)->getSubnode(1), $qname);
        }
        return null;
    }

    /**
     * @param SyntaxTreeNode\Branch $tree
     * @param $itemName
     * @return \Sparql\Expression
     * @throws Exception
     */
	public function generate(SyntaxTreeNode\Branch $tree, $itemName)
	{
		$res = "";
		if(!$tree) {
			throw new Exception("Tree missing!");
		}
		if(!$tree->isBranch()) {
			// FIXME: this should never happen
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
					if( strtoupper($op->getContent()) == "OR") {
						return Union::addTwo($left, $right);
					} else {
						// AND
						return AndClause::addTwo($left, $right);
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
				$items = array();
				$qualifiers = $this->getQualifiers($tree, $itemName, 3, $qname );
				foreach($tree->getSubnode(1)->findAll('Propvalue') as $prop) {
					$pid = $prop->getSubnode(0)->getLeftLeaf()->getContent();
					$item = $prop->getSubnode(1);
					if(!$item->isBranch()) {
                        $newItem = new Claim($itemName, $pid, new Subquery("[]") );
					} else {
						$item = $item->getSubnode(1);
                        $newItem = new Claim($itemName, $pid, $this->generateItem($item) );
					}
                    if(!empty($qualifiers)) {
                        $qualifierExpr = new Qualifiers($itemName, $pid, $qname, $qualifiers);
                        $newItem->setQualifiers($qualifierExpr);
                    }

                    $items[] = $newItem;
				}
				if(!$items) {
					throw new Exception("No items found for claim");
				}
				if(count($items) == 1) {
					return $items[0];
				}
				return new Union($items);
			case 'noclaim':
				$items = array();
				foreach($tree->getSubnode(1)->findAll('Propvalue') as $prop) {
					$pid = $prop->getSubnode(0)->getLeftLeaf()->getContent();
					$item = $prop->getSubnode(1);
					if(!$item->isBranch()) {
						$items[] = new NoClaim($itemName, $pid, new Subquery("?dummy".$this->counter++) );
					} else {
						$item = $item->getSubnode(1);
						$items[] = new NoClaim($itemName, $pid, $this->generateItem($item) );
					}
				}
				if(!$items) {
					throw new Exception("No items found for claim");
				}
				if(count($items) == 1) {
					return $items[0];
				}
				array_unshift($items, new AnyItem($itemName));
				return new AndClause($items);
			case 'string':
                $pid = $tree->getSubnode(1)->getLeftLeaf()->getContent();
                $qualifiers = $this->getQualifiers($tree, $itemName, 5, $qname );
                if(!empty($qualifiers)) {
                    $qualifierExpr = new Qualifiers($itemName, $pid, $qname, $qualifiers);
                }
				$items = array();
				foreach($tree->findAll('LiteralString') as $item) {
					$newItem = new StringLiteral($itemName, $pid, $item->getLeftLeaf()->getContent());
					if(!empty($qualifierExpr)) {
                        $newItem->setQualifiers($qualifierExpr);
                    }
                    $items[] = $newItem;
				}
				if(count($items) == 1) {
					return $items[0];
				}
				return new Union($items);
			case 'between':
				$pid = $tree->getSubnode(1)->getLeftLeaf()->getContent();
                $qualifiers = $this->getQualifiers($tree, $itemName, 5, $qname );
                if(!empty($qualifiers)) {
                    $qualifierExpr = new Qualifiers($itemName, $pid, $qname, $qualifiers);
                }
				$numbers = $tree->getSubnode(3);
				$subnumbers = $numbers->getSubnodes();
				switch(count($subnumbers)) {
					case 1:
						$newItem = new Between($itemName, $pid, $subnumbers[0]->getLeftLeaf()->getContent() );
						break;
					case 2:
                        $newItem = new Between($itemName, $pid, null, $subnumbers[1]->getLeftLeaf()->getContent() );
                        break;
					case 3:
                        $newItem = new Between($itemName, $pid, $subnumbers[0]->getLeftLeaf()->getContent(),
							 $subnumbers[2]->getLeftLeaf()->getContent() );
                        break;
					default:
						throw new Exception("Weird number of args for Between");
				}
				if(!empty($qualifierExpr)) {
				    $newItem->setQualifiers($qualifierExpr);
                }
                return $newItem;
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
                $qualifiers = $this->getQualifiers($tree, $itemName, 6, $qname );
                if(!empty($qualifiers)) {
                    $qualifierExpr = new Qualifiers($itemName, $pid, $qname, $qualifiers);
                }
                $newItem = new Quantity($itemName, $pid, $low, $high);
                if(!empty($qualifierExpr)) {
                    $newItem->setQualifiers($qualifierExpr);
                }
                return $newItem;
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
					 	return new Tree($itemName, $it->getLeftLeaf()->getContent(), $forward, $backward );
					 },
					 $tree->findAll('Item')
				);
				if(count($items) == 1) {
					return $items[0];
				}
				return new Union($items);
			case 'web':
				$extract = function ($it) { return $it->getLeftLeaf()->getContent(); };
				$props = array_map($extract, $tree->getSubnode(3)->findAll('Number'));
				$items = array_map(
						function ($it) use ($props, $itemName) {
							return new Tree($itemName, $it->getLeftLeaf()->getContent(), $props, $props );
						},
						$tree->findAll('Item')
				);
				if(count($items) == 1) {
					return $items[0];
				}
				return new Union($items);
			case 'link':
				$wiki = $tree->getSubnode(1)->getLeftLeaf()->getContent();
				return new Link($itemName, $wiki);
			case 'nolink':
				$wiki = $tree->getSubnode(1)->getLeftLeaf()->getContent();
				return new NoLink($itemName, $wiki);
			case 'around':
				$pid = $tree->getSubnode(1)->getLeftLeaf()->getContent();
				$lat = $tree->getSubnode(3)->getLeftLeaf()->getContent();
				$lon = $tree->getSubnode(5)->getLeftLeaf()->getContent();
				$radius = $tree->getSubnode(7)->getLeftLeaf()->getContent();
				return new GeoAround($itemName, $pid, $lat, $lon, $radius);
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
