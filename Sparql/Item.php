<?php
namespace Sparql;

/**
 * Entity Item - like Q123
 */
class Item extends ItemExpression {

	public function emit(Syntax $syntax) {
		// do not produce expression
		return "";
	}

	public function getVarName(Syntax $syntax) {
		// produce Q-name directly as variable
		return $syntax->entityName($this->var);
	}
}
