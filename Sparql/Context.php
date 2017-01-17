<?php
/**
 * Created by PhpStorm.
 * User: smalyshev
 * Date: 1/17/17
 * Time: 9:51 AM
 */

namespace Sparql;

/**
 * Context that encapsulates syntax and also allows to override default type.
 * @package Sparql
 */
class Context implements Syntax
{
    /**
     * @var Syntax
     */
    private $syntax;

    public function __construct(Syntax $syntax, $type)
    {
        $this->syntax = $syntax;
        $this->type = $type;
    }

    /**
     * Get SPARQL prefixes for this syntax
     */
    function getPrefixes()
    {
        return $this->syntax->getPrefixes();
    }

    /**
     * Expressing property name expression that states "X has property Y"
     * @param string $id
     */
    function propertyName($id, $type = self::TYPE_DIRECT)
    {
        return $this->syntax->propertyName($id, $this->type);
    }

    /**
     * Expressing item name, e.g. Q123
     * @param string $id
     */
    function entityName($id)
    {
        return $this->syntax->entityName($id);
    }

    /**
     * Expressing the fact that $var is unknown
     * @param string $var
     */
    function isUnknown($var)
    {
        return $this->syntax->isUnknown($var);
    }

    /**
     * Var is an item (any item)
     * @param string $var
     */
    function isItem($var)
    {
        return $this->syntax->isItem($var);
    }
}