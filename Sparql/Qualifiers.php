<?php
namespace Sparql;

class Qualifiers extends Expression
{
    /**
     * Variable name for item
     * @var string
     */
    private $itemName;
    /**
     * @var Expression
     */
    private $expression;

    /**
     * Qualifiers for property
     * @param string $item parent item variable, e.g. ?item
     * @param string $id property ID
     * @param string $qname Qualifier name
     * @param Expression $expression Qualifier expression
     */
    public function __construct( $item, $id, $qname, Expression $expression) {
        $this->itemName = $item;
        $this->id = $id;
        $this->qname = $qname;
        $this->expression = $expression;
    }
    /**
     * Produce output for this expression
     * @param Syntax $syntax Syntax engine to use
     * @return string
     */
    function emit(Syntax $syntax, $indent = "")
    {
        $context = new Context($syntax, Syntax::TYPE_QUALIFIER);
        return "${indent}{\n"
            . $this->expression->emit($context, $indent."  ")
            . "$indent}\n";
    }

    public function getVarName() {
        return $this->qname;
    }
}