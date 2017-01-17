[WDQ syntax](https://wdq.wmflabs.org/api_documentation.html) to SPARQL translator.

Try it at: http://tools.wmflabs.org/wdq2sparql/w2s.php

### API Endpoint

Located at http://tools.wmflabs.org/wdq2sparql/api.php

#### Mandatory URL Parameters
| Parameter | Type   | Description             |
| :-------- | :----- | :----------             |
| wdq       | string | The query to translate. |

#### Optional URL Parameters
| Parameter  | Type          | Default  | Description |
| :--------- | :------------ | :------- | :---------- |
| callback   | string        |          | If `format=json`, makes a jsonp request. |
| format     | json,text     | json     |  |
| indent     | string        | 2 spaces | What to use to indent the SPARQL. |
| syntax     | Wikidata,WDTK | Wikidata | The syntax of the outputted SPARQL. |

[![Build Status](https://travis-ci.org/smalyshev/wdq2sparql.svg?branch=master)](https://travis-ci.org/smalyshev/wdq2sparql)
