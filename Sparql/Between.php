<?php
namespace Sparql;

/**
 * BETWEEN[PROPERTY,BEGIN,END] WDQ clause
 */
class Between extends QualifiedExpression {
	private $from;
	private $to;

	/**
	 * Create BETWEEN[PROPERTY,BEGIN,END]
	 * @param string $item parent item
	 * @param string $id property ID
	 * @param string $from
	 * @param string $to
	 */
	public function __construct( $item, $id, $from = null, $to = null ) {
		$this->itemName = $item;
		$this->id = $id;
		$this->from = $from;
		$this->to = $to;
	}

	/**
	 * Fixup date
	 * @param string $dateStr
	 * @return string
	 */
	private function fixDate( $dateStr ) {
		preg_match( '/([+-]?\d+)(?:-(\d{2})(?:-(\d{2})(T\d{2}:\d{2}:\d{2}Z)?)?)?/', $dateStr, $m );

		$y = sprintf("%04d", (int)$m[1]);
		$mon = !empty( $m[2] ) ? $m[2] : "01";
		$day = !empty( $m[3] ) ? $m[3] : "01";
		$t = !empty( $m[4] ) ? $m[4] : "T00:00:00Z";

		return "$y-$mon-$day$t";
	}

	public function emit( Syntax $syntax, $indent = "" ) {
		$cond = array ();
		$tm = $this->counterVar("time");

		if ( !is_null( $this->from ) ) {
			$d = $this->fixDate( $this->from );
			$cond[] = "$tm >= \"$d\"^^xsd:dateTime";
		}
		if ( !is_null( $this->to ) ) {
			$d = $this->fixDate( $this->to );
			$cond[] = "$tm <= \"$d\"^^xsd:dateTime";
		}
		if ( !$cond ) {
			return "";
		}
		$cond = join( " && ", $cond );
		return $this->directOrQualifiedValue($syntax, $this->id, $tm, $indent)
            . $this->addQualifiers($syntax, $indent)
            . "{$indent}FILTER ( $cond )\n";
	}
}
