<?php
namespace Sparql;

/**
 * WDQ clause string[PROPERTY:"STRING",...]
 */
class StringLiteral extends Expression {
	private $itemName;

	public function __construct( $item, $id, $value ) {
		$this->itemName = $item;
		$this->id = $id;
		$this->value = $value;
	}

	public function emit( Syntax $syntax, $indent = "" ) {
		return "{$indent}{$this->itemName} {$syntax->propertyName($this->id)} {$this->value} .\n";
	}
}

