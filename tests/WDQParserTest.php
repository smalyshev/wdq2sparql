<?php
require_once __DIR__.'/../WDQ.php';

use Sparql\Syntax\Wikidata;
use Sparql\Syntax\WDTK;

/**
 * WDQParser test case.
 */
class WDQParserTest extends PHPUnit_Framework_TestCase {

	private function loadCase($path) {
		if(!file_exists($path)) {
			return false;
		}
		$case = file_get_contents($path);
		return explode("\n", $case, 2);
	}

	public function getCases() {
		$cases = array();
		$wikidata = new Wikidata();
		for($i=1;$case = $this->loadCase(__DIR__."/data/wikidata/$i");$i++) {
			$case[] = $wikidata;
			$cases[] = $case;
		}
		$wdtk = new WDTK();
		for($i=1;$case = $this->loadCase(__DIR__."/data/wdtk/$i");$i++) {
			$case[] = $wdtk;
			$cases[] = $case;
		}
		return $cases;
	}

	/**
	 * @dataProvider getCases
	 */
	public function testParser($case, $expected, $syntax) {
		$parser = new WDQParser();
		$parsed = $parser->parse($case);
		if(!$parsed) {
			throw new Exception("failed to parse!");
		}
		$sparql = $parser->generate($parsed, "?item");
		$this->assertEquals(trim($expected), trim($sparql->emit($syntax)));
	}
}

