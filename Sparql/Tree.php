<?php
namespace Sparql;

/**
 * WDQ TREE clause
 */
class Tree extends Expression {
	private $itemName;
	private $forward;
	private $backward;

	public function __construct( $item, $id, $forward, $back ) {
		$this->itemName = $item;
		$this->id = $id;
		$this->forward = $forward;
		$this->backward = $back;
	}

	public function emit( Syntax $syntax, $indent = "" ) {
		$res = "";
		if ( $this->forward ) {
			$treeVar = $this->counterVar("tree");
			$propNames = join( "|", array_map( array ($syntax,"propertyName"
			), $this->forward ) );
			$res .= "{$indent}$treeVar ($propNames)* {$this->itemName} .\n";
		} else {
			$treeVar = $this->itemName;
		}

		if ( $this->backward ) {
			$propNames = join( "|", array_map( array ($syntax,"propertyName" ), $this->backward ) );
			$res .= "{$indent}$treeVar ($propNames)* {$syntax->entityName($this->id)} .\n";
		} else {
			$res .= "{$indent}BIND ({$syntax->entityName($this->id)} AS $treeVar)\n";
		}
		return $res;
	}
}
