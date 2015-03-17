<?php

abstract class SparqlExpression {
	abstract function emit();
	protected function propertyName( $id ) {
		return "wdt:P{$id}";
	}
	protected function entityName( $id ) {
		return "entity:Q{$id}";
	}
}

abstract class SparqlCollection extends SparqlExpression {
	protected $items;
	public function __construct( array $expressions ) {
		$this->items = $expressions;
	}
	public function add( SparqlExpression $ex ) {
		$this->items[] = $ex;
	}
	protected function emitAll( $indent = "" ) {
		return array_map( function ( SparqlExpression $ex ) use($indent ) {
			return $ex->emit( $indent );
		}, $this->items );
	}
	public static function addTwo( SparqlExpression $ex1, SparqlExpression $ex2 ) {
		if ( $ex1 instanceof static ) {
			$ex1->add( $ex2 );
			return $ex1;
		}
		return new static( array ($ex1,$ex2
		) );
	}
}

class SparqlAnd extends SparqlCollection {
	public function emit( $indent = "" ) {
		return join( "", $this->emitAll( $indent ) );
	}
}

class SparqlUnion extends SparqlCollection {
	public function emit( $indent = "" ) {
		if ( count( $this->items ) > 1 ) {
			return "{\n" . join( "} UNION {\n", $this->emitAll( $indent . "  " ) ) . "}\n";
		} elseif ( count( $this->items ) == 1 ) {
			return $this->items[0]->emit( $indent );
		}
		return "";
	}
}

abstract class SparqlVar extends SparqlExpression {
	protected $var;
	public function __construct( $name ) {
		$this->var = $name;
	}
	abstract function getVarName();
}

class SparqlItem extends SparqlVar {
	public function emit() {
		return "";
	}

	public function getVarName() {
		return $this->entityName($this->id);
	}
}

class SparqlSubquery extends SparqlVar {
	public function __construct( $var, SparqlExpression $sub = null ) {
		parent::__construct($var);
		$this->sub = $sub;
	}

	public function getVarName() {
		return $this->var;
	}

	public function emit($indent = "") {
		return $this->sub?$this->sub->emit($indent):"";
	}
}

class SparqlClaim extends SparqlExpression {
	private $itemName;
	public function __construct( $item, $id, SparqlVar $value) {
		$this->itemName = $item;
		$this->id = $id;
		$this->value = $value;
	}
	public function emit( $indent = "" ) {
		return "$indent{$this->itemName} {$this->propertyName($this->id)} {$this->value->getVarName()} .\n"
				. $this->value->emit($indent);
	}
}

class SparqlNoClaim extends SparqlExpression {
	private $itemName;
	public function __construct( $item, $id, SparqlVar $value = null ) {
		$this->itemName = $item;
		$this->id = $id;
		$this->value = $value;
	}
	public function emit( $indent = "" ) {
		return "{$indent}FILTER NOT EXISTS { {$this->itemName} {$this->propertyName($this->id)} {$this->value->getVarName()} }\n"
			. $this->value->emit($indent);
	}
}

class SparqlString extends SparqlExpression {
	private $itemName;
	public function __construct( $item, $id, $value ) {
		$this->itemName = $item;
		$this->id = $id;
		$this->value = $value;
	}
	public function emit( $indent = "" ) {
		return "$indent{$this->itemName} {$this->propertyName($this->id)} {$this->value} .\n";
	}
}

class SparqlBetween extends SparqlExpression {
	private $itemName;
	private static $timeCounter = 0;
	private $from;
	private $to;
	public function __construct( $item, $id, $from = null, $to = null ) {
		$this->itemName = $item;
		$this->id = $id;
		$this->from = $from;
		$this->to = $to;
	}
	private function fixDate( $dateStr ) {
		preg_match( '/([+-]?\d+)(?:-(\d{2})(?:-(\d{2})(T\d{2}:\d{2}:\d{2}Z)?)?)?/', $dateStr, $m );

		$y = (int)$m[1];
		$mon = !empty( $m[2] ) ? $m[2] : "01";
		$day = !empty( $m[3] ) ? $m[3] : "01";
		$t = !empty( $m[4] ) ? $m[4] : "T00:00:00Z";

		return "$y-$mon-$day$t";
	}
	public function emit( $indent = "" ) {
		$cond = array ();
		$tm = "?time" . self::$timeCounter ++;

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
		$cond = join( " AND ", $cond );
		return "{$indent}{$this->itemName} {$this->propertyName($this->id)} $tm .\n{$indent}FILTER { $cond }\n";
	}
}

class SparqlQuantity extends SparqlExpression {
	private $itemName;
	private static $qCounter = 0;
	private $from;
	private $to;
	public function __construct( $item, $id, $from, $to ) {
		$this->itemName = $item;
		$this->id = $id;
		$this->from = $from;
		$this->to = $to;
	}
	public function emit( $indent = "" ) {
		$cond = array ();
		$q = "?q" . self::$qCounter ++;

		if ( !is_null( $this->to ) ) {
			$cond = "$q >= $this->from && $q <= $this->to";
		} else {
			$cond = "$q = $this->from";
		}

		return "{$indent}{$this->itemName} {$this->propertyName($this->id)} $q .\n{$indent}FILTER { $cond }\n";
	}
}

class SparqlTree extends SparqlExpression {
	private $itemName;
	private $forward;
	private $backward;
	private static $treeCounter = 0;
	public function __construct( $item, $id, $forward, $back ) {
		$this->itemName = $item;
		$this->id = $id;
		$this->forward = $forward;
		$this->backward = $back;
	}
	public function emit( $indent = "" ) {
		$res = "";
		if ( $this->forward ) {
			$treeVar = "?tree" . self::$treeCounter ++;
			$propNames = join( "|", array_map( array ($this,"propertyName"
			), $this->forward ) );
			$res .= "{$indent}$treeVar ($propNames)* {$this->itemName} .\n";
		} else {
			$treeVar = $this->itemName;
		}

		if ( $this->backward ) {
			$propNames = join( "|", array_map( array ($this,"propertyName" ), $this->backward ) );
			$res .= "{$indent}$treeVar ($propNames)* {$this->entityName($this->id)} .\n";
		} else {
			$res .= "{$indent}BIND ({$this->entityName($this->id)} AS $treeVar)\n";
		}
		return $res;
	}
}