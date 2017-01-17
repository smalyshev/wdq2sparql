<?php
namespace Sparql\Syntax;

use Sparql\Syntax;

class WDTK implements Syntax {

	public function getPrefixes() {
		return array("" => "http://www.wikidata.org/entity/" );
	}

	public function propertyName( $id, $type = self::TYPE_DIRECT ) {
        switch($type) {
            case self::TYPE_DIRECT:
                return ":P{$id}s/:P{$id}v";
            case self::TYPE_STATEMENT:
                return ":P{$id}s";
            case self::TYPE_STATEMENT_SIMPLE:
                return ":P{$id}";
            case self::TYPE_QUALIFIER:
                return ":P{$id}q";
        }
	}

	public function entityName( $id ) {
		return ":Q{$id}";
	}

	public function isUnknown( $var ) {
		return "{$var} = {$this->entityName('4294967294')}";
	}

	public function isItem( $var ) {
		return "{$var} a <http://www.wikidata.org/ontology#Item> .";
	}
}
