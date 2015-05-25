<?php
namespace Sparql;

/**
 * Conjunction of expressions, also represents WDQ "X AND Y"
 */
class AndClause extends Collection {

	public function emit( Syntax $syntax, $indent = "" ) {
		return join( "", $this->emitAll( $syntax, $indent ) );
	}
}
