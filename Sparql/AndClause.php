<?php
namespace Sparql;

/**
 * Conjunction of expressions, also represents WDQ "X AND Y"
 */
class AndClause extends Collection {

	public function emit( Syntax $syntax, $indent = "" ) {
		foreach($this->items as $k => $v) {
			if ($k != 0 && $v instanceof AnyItem) {
				unset($this->items[$k]);
			}
		}
		return join( "", $this->emitAll( $syntax, $indent ) );
	}
}
