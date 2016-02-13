<?php
namespace Sparql;

/**
 * WDQ NOLINK[]
 */
class NoLink extends Link {

	public function emit( Syntax $syntax, $indent = "" ) {
		$hasLink = parent::emit($syntax, $indent . "  ");
		$wikiVar = $this->getLastVar("wiki");
		return "{$indent}OPTIONAL {\n$hasLink{$indent}}\n" .
		"{$indent}FILTER(!bound($wikiVar))\n";
	}
}

