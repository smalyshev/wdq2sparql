<?php
namespace Sparql;

/**
 * WDQ clause string[PROPERTY:"STRING",...]
 */
class StringLiteral extends QualifiedExpression {
	public function __construct( $item, $id, $value ) {
		$this->itemName = $item;
		$this->id = $id;
		$this->value = $value;
	}

	public function emit( Syntax $syntax, $indent = "" ) {
        return $this->directOrQualifiedValue($syntax, $this->id, $this->value, $indent)
            . $this->addQualifiers($syntax, $indent);
	}
}

