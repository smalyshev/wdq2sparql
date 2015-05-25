<?php
namespace Sparql;

/**
 * WWDQ clause quantity[PROPERTY:VALUE1,VALUE2]
 */
class Quantity extends Expression {
	/**
	 * Parent item name
	 * @var string
	 */
	private $itemName;
	/**
	 * Expression running counter
	 * @var int
	 */
	private static $qCounter = 0;

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
		$q = "?q" . self::$qCounter ++;

		if ( !is_null( $this->to ) ) {
			$cond = "$q >= $this->from && $q <= $this->to";
		} else {
			$cond = "$q = $this->from";
		}

		return "{$indent}{$this->itemName} {$syntax->propertyName($this->id)} $q .\n{$indent}FILTER { $cond }\n";
	}
}

