<?php
namespace Sparql;


/**
 * Expression that can accept qualifiers.
 * @package Sparql
 */
abstract class QualifiedExpression extends Expression
{
    /**
     * Variable name for item
     * @var string
     */
    protected $itemName;
    /**
     * @var Qualifiers
     */
    protected $qualifiers;

    protected function addQualifiers(Syntax $syntax, $indent) {
        if(empty($this->qualifiers)) {
            return "";
        }
        return $this->qualifiers->emit($syntax, $indent);
    }

    public function setQualifiers(Qualifiers $q) {
        $this->qualifiers = $q;
    }

    public function directOrQualifiedValue(Syntax $syntax, $propId, $value, $indent) {
        if($this->qualifiers) {
            return "$indent{$this->itemName} {$syntax->propertyName($propId, Syntax::TYPE_STATEMENT)} {$this->qualifiers->getVarName()} .\n"
            . "$indent{$this->qualifiers->getVarName()} {$syntax->propertyName($propId, Syntax::TYPE_STATEMENT_SIMPLE)} $value .\n";
        } else {
            return "$indent{$this->itemName} {$syntax->propertyName($propId)} $value .\n";
        }
    }
}