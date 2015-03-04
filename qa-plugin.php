<?php

/*
	Plugin Name: Elastic Search Support for Q2A
	Plugin URI: https://github.com/vijsha79/q2a-elasticsearch
	Plugin Update Check URI: https://raw.github.com/vijsha79/q2a-elasticsearch/master/qa-plugin.php
	Plugin Description: Adds Support for Elastic Saarch for Q2A
	Plugin Version: 0.1
	Plugin Date: 2015-03-03
	Plugin Author: Vijay Sharma
	Plugin Author URI: http://www.question2answer.org/
	Plugin License: MIT
	Plugin Minimum Question2Answer Version: 1.3
*/


	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../../');
		exit;
	}


	qa_register_plugin_module('search', 'qa-es-admin.php', 'qa_elasticsearch', 'qa_elasticsearch');
	//qa_register_plugin_layer('qa-es-layer.php', 'ElasticSearch Layer');	
	//qa_register_plugin_module('module', 'qa-es-page.php', 'qa_elasticsearch_admin', 'ElasticSearch Admin');
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
