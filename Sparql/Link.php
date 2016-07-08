<?php
namespace Sparql;

/**
 * WDQ LINK[]
 */
class Link extends Expression {
	/**
	 * Variable name for item
	 * @var string
	 */
	private $itemName;

	/**
	 * @var array
	 */
	protected static $wikis;

	private $wiki;

	private function getWikiURL($wiki) {
		if(empty(self::$wikis)) {
			$wikis = [];
			include __DIR__."/wikilist.php";
			self::$wikis = $wikis;
		}
		if(empty(self::$wikis[$wiki])) {
			return "UNKNOWN_WIKI";
		}
		return self::$wikis[$wiki] . '/';
	}

	/**
	 * LINK[wiki]
	 * @param string $item parent item variable, e.g. ?item
	 * @param string $wiki wiki
	 */
	public function __construct( $item, $wiki) {
		$this->itemName = $item;
		$this->wiki = $this->getWikiURL($wiki);
	}

	public function emit( Syntax $syntax, $indent = "" ) {
		$wikiVar = $this->counterVar("wiki");
		$wikiLen = strlen($this->wiki);
		return "{$indent}$wikiVar <http://schema.org/about> {$this->itemName} .\n" .
		"{$indent}$wikiVar <http://schema.org/isPartOf> <{$this->wiki}> .\n";
	}
}

