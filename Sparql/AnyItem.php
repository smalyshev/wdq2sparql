<?php
namespace Sparql;

/**
 * Any item
 */
class AnyItem extends ItemExpression {
	public function emit(Syntax $syntax, $indent = "") {
		$var = $this->getVarName($syntax);
		return $indent . $syntax->isItem($var). "\n";
	}

	public function getVarName(Syntax $syntax) {
		return $this->var;
	}
}
