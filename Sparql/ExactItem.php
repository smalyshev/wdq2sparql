<?php
namespace Sparql;

/**
 * For WDQ ITEMS[]
 */
class ExactItem extends Expression {
  private $itemName;
  private $id;

  public function __construct( $item, $id ) {
		$this->itemName = $item;
		$this->id = $id;
	}

  public function emit( Syntax $syntax, $indent = "" ) {
    return $indent . "BIND({$syntax->entityName($this->id)} as $this->itemName)\n";
  }

}
