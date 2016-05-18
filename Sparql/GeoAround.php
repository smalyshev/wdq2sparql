<?php
namespace Sparql;

/**
 * around[PROPERTY,LATITUDE,LONGITUDE,RADIUS]
 */
class GeoAround extends Expression
{
    private $propId;
    private $lon;
    private $lat;
    private $radius;
    public function __construct($item, $pid, $lat, $lon, $radius)
    {
        $this->itemName = $item;
        $this->propId = $pid;
        $this->lon = $lon;
        $this->lat = $lat;
        $this->radius = $radius;
    }


    /**
     * Produce output for this expression
     * @param Syntax $syntax Syntax engine to use
     * @return string
     */
    public function emit(Syntax $syntax)
    {
        $service = <<<END
SERVICE wikibase:around {
      {$this->itemName} {$syntax->propertyName($this->propId)} {$this->itemName}_location .
      bd:serviceParam wikibase:center "Point({$this->lon} {$this->lat})"^^geo:wktLiteral .
      bd:serviceParam wikibase:radius "{$this->radius}" .
}
END;
        return $service;
    }
}