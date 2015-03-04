<?php
    require 'vendor/autoload.php';

function create_es_client ( $host , $port ) {
    $params = array ();
    $params['hosts'] = array ( 
	$host . ':' . $port
    ); 	
    $client = new Elasticsearch\Client($params);
    return $client;
}
