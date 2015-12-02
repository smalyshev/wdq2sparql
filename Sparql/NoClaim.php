<?php
namespace Sparql;

/**
 * WDQ NOCLAIM[] construct
 */
class NoClaim extends Expression {
	private $itemName;
	public function __construct( $item, $id, ItemExpression $value ) {
		$this->itemName = $item;
		$this->id = $id;
		$this->value = $value;
	}
	public function emit( Syntax $syntax, $indent = "" ) {
		$sub = $this->value->emit($syntax, $indent."  ");
		if($this->value instanceof Item) {
			$var = $this->value->getVarName($syntax) ;
			return "{$indent}MINUS { {$this->itemName} {$syntax->propertyName($this->id)} {$var} }\n";
		} else {
			if($sub) {
				return "{$indent}FILTER NOT EXISTS {\n{$indent}  {$this->itemName} {$syntax->propertyName($this->id)} {$this->value->getVarName($syntax)} .\n$sub{$indent}}\n";
			} else {
				$dummy = $this->value->getVarName($syntax) ;
				return "{$indent}OPTIONAL { {$this->itemName} {$syntax->propertyName($this->id)} {$dummy} }\n{$indent}FILTER(!bound({$dummy}))\n";
			}
		}
	}
}
