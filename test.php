<?php

// Library
include('Elasticsearch.php');

// ElasticSearch
$es=new Elasticsearch('http://127.0.0.1:9200');

// Execute
$es->method='GET';
$es->path='/_stats';
$es->query='{}';
$es->result=$es->execute($es->method, $es->path, $es->query);

// debug
print_r($es->result);
