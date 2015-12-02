<?php
namespace Sparql;

/**
 * WDQ clause having sub-clauses - such as AND, OR, etc.
 */
abstract class Collection extends Expression {
	protected $items;

	public function __construct( array $expressions ) {
		$this->items = $expressions;
	}

	public function add( Expression $ex ) {
		$this->items[] = $ex;
	}

	public function merge( Collection $c2 ) {
		$this->items = array_merge($this->items, $c2->items);
	}

	/**
	 * Emit all contained expressions
	 * @param Syntax $syntax
	 * @param string $indent
	 * @return array Emitted sub-expressions
	 */
	protected function emitAll( Syntax $syntax, $indent = "" ) {
		return array_map( function ( Expression $ex ) use($syntax, $indent ) {
			return $ex->emit( $syntax, $indent );
		}, $this->items );
	}

	/**
	 * Produce combination of two expressions
	 * @param Expression $ex1
	 * @param Expression $ex2
	 * @return \Sparql\Collection
	 */
	public static function addTwo( Expression $ex1, Expression $ex2 ) {
		if( $ex1 instanceof static && $ex2 instanceof static ) {
			$ex1->merge( $ex2 );
			return $ex1;
		} else if( $ex1 instanceof static ) {
			$ex1->add( $ex2 );
			return $ex1;
		} else if( $ex2 instanceof static ) {
			$ex = new static( array ($ex1) );
			$ex->merge($ex2);
			return $ex;
		}
		return new static( array ($ex1,$ex2) );
	}
}
