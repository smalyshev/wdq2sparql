<?php
namespace Sparql;

/**
 * WDQ CLAIM[]
 */
class Claim extends Expression {
	/**
	 * Variable name for item
	 * @var string
	 */
	private $itemName;

	/**
	 * CLAIM[PROPERTY:ITEM,...]
	 * @param string $item parent item variable, e.g. ?item
	 * @param string $id property ID
	 * @param ItemExpression $value item expression
	 */
	public function __construct( $item, $id, ItemExpression $value) {
		$this->itemName = $item;
		$this->id = $id;
		$this->value = $value;
	}

	public function emit( Syntax $syntax, $indent = "" ) {
		return "$indent{$this->itemName} {$syntax->propertyName($this->id)} {$this->value->getVarName($syntax)} .\n"
				. $this->value->emit($syntax, $indent);
	}
}

