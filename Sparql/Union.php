<?php
namespace Sparql;

/**
 * Union of two clause sets, also represents WDQ "OR"
 */
class Union extends Collection {

	public function emit( Syntax $syntax, $indent = "" ) {
		if ( count( $this->items ) > 1 ) {
			// Don't need UNION if we have just one item
			return "{\n" . join( "} UNION {\n", $this->emitAll( $syntax, $indent . "  " ) ) . "}\n";
		} elseif ( count( $this->items ) == 1 ) {
			return $this->items[0]->emit( $syntax, $indent );
		}
		return "";
	}
}
