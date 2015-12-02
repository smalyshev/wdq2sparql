<?php
namespace Sparql;

/**
 * Any item
 */
class AnyItem extends ItemExpression {
	private static $counter = 0;

	public function emit(Syntax $syntax, $indent = "") {
		$var = $this->getVarName($syntax);
		return $indent . $syntax->isItem($var). "\n";
	}

	public function getVarName(Syntax $syntax) {
		return $this->var;
	}
}
