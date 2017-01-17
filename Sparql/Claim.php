<?php
namespace Sparql;

/**
 * WDQ CLAIM[]
 */
class Claim extends QualifiedExpression {
    /**
     * @var ItemExpression
     */
    private $value;

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
	    return $this->directOrQualifiedValue($syntax, $this->id, $this->value->getVarName($syntax), $indent)
            . $this->addQualifiers($syntax, $indent)
            . $this->value->emit($syntax, $indent);
	}

}

