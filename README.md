# ElasticSearch - PHP

This library executes Query DSL (Domain Specific Language) based on JSON, of extremely simple format to comunicate with the ElasticSearch.
Also contain  a **Scroll**  function for the execute of paginate in queries.

> This library support ALL methods GET, PUT, POST, DELETE ... :heart_eyes:

### Config

Declare class.

```php
$es=new Elasticsearch('http://127.0.0.1:9200');
```

### Example

Example of use.

```php
$es->method='GET';
$es->path='/_stats';
$es->query='{}';
$es->result=$es->execute($es->method, $es->path, $es->query);
```

For **BigData** execute Scroll.

```php
$es->result=$es->scroll($es->path, $es->query);
```

> This is very good :sunglasses:

For _GET_ execute.

```php
$es->get('/index/_doc/_search', '{"size":9}');
```

For _PUT_ execute.

```php
$es->put('/index/_doc/:id', '{}');
```

For _POST_ execute.

```php
$es->post('/index/_doc/:id', '{}');
```

For _DELETE_ execute.

```php
$es->delete('/index/_doc/:id');
```
