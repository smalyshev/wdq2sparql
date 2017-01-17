<?php
namespace Sparql;

/**
 * WWDQ clause quantity[PROPERTY:VALUE1,VALUE2]
 */
class Quantity extends QualifiedExpression {
	private $from;

	private $to;
	/**
	 *
	 * @param string $item parent item
	 * @param string $id property ID
	 * @param string $from
	 * @param string $to
	 */
	public function __construct( $item, $id, $from, $to ) {
		$this->itemName = $item;
		$this->id = $id;
		$this->from = $from;
		$this->to = $to;
	}

	public function emit( Syntax $syntax, $indent = "" ) {
		$cond = array ();
		$q = $this->counterVar("q");

		if ( !is_null( $this->to ) ) {
			$cond = "$q >= $this->from && $q <= $this->to";
		} else {
			$cond = "$q = $this->from";
		}

		return $this->directOrQualifiedValue($syntax, $this->id, $q, $indent)
            . $this->addQualifiers($syntax, $indent)
            . "{$indent}FILTER ( $cond )\n";
	}
}

