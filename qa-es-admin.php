<?php

require_once dirname(__FILE__) . '/es-client/client.php';

class qa_elasticsearch {
	private $es_client;
	private $es_hostname;
	private $es_port;
	private $es_index_name;
	private $es_enabled;

	function create_es_client_if_needed() {
		$this->es_enabled = qa_opt('elasticsearch_enabled');
		if ( $this->es_enabled && !$this->es_client) {
			$this->es_hostname = qa_opt('elasticsearch_hostname');
			$this->es_port = qa_opt('elasticsearch_port');
			$this->es_index_name = qa_opt('elasticsearch_index_name');
			$this->es_client = create_es_client($this->es_hostname, $this->es_port);
			$params = array( 'index' => $this->es_index_name);
			if ( !$this->es_client->indices()->exists($params))
				$this->es_client->indices()->create($params);
		}
	}
	
	function allow_template($template)
	{
		return ($template!='admin');
	}
	
	function option_default($option) {
		switch($option) {
			case 'elasticsearch_hostname':
				return 'localhost';
			case 'elasticsearch_port':
				return '9200';
			case 'elasticsearch_index_name':
				return 'q2a-elasticsearch';
			case 'elasticsearch_enabled':
				return false;
			default:
				return null;
		}

	}
	function admin_form(&$qa_content)
	{
		//      Process form input
		$ok = null;
		if (qa_clicked('accept_save_button')) {
			$is_es_enabled = (bool)qa_post_text('elasticsearch_enabled');
			qa_opt('elasticsearch_hostname',qa_post_text('elasticsearch_hostname'));
			qa_opt('elasticsearch_port',qa_post_text('elasticsearch_port'));
			qa_opt('elasticsearch_index_name',qa_post_text('elasticsearch_index_name'));
			qa_opt('elasticsearch_enabled',$is_es_enabled);
			if ( $is_es_enabled ) { 
			   qa_opt('search_module', 'qa_elasticsearch');	
			}
			$ok = qa_lang('admin/options_saved');
		}
		else if (qa_clicked('accept_reset_button')) {
			foreach($_POST as $i => $v) {
				$def = $this->option_default($i);
				if($def !== null) qa_opt($i,$def);
			}
			qa_opt('search_module', '');	
			$ok = qa_lang('admin/options_reset');
		}

		//      Create the form for display

		$fields = array();

		$fields[] = array(
				'label' => 'Enable ElasticSearch',
				'tags' => 'NAME="elasticsearch_enabled"',
				'value' => qa_opt('elasticsearch_enabled'),
				'type' => 'checkbox',
				);
		$fields[] = array(
				'label' => 'ElasticSearch Hostname',
				'tags' => 'NAME="elasticsearch_hostname"',
				'value' => qa_opt('elasticsearch_hostname'),
				'type' => 'input',
				);
		$fields[] = array(
				'label' => 'ElasticSearch Port',
				'tags' => 'NAME="elasticsearch_port"',
				'value' => qa_opt('elasticsearch_port'),
				'type' => 'input',
				);
		$fields[] = array(
				'label' => 'Index Name',
				'tags' => 'NAME="elasticsearch_index_name"',
				'value' => qa_opt('elasticsearch_index_name'),
				'type' => 'input',
				);
		return array(
				'ok' => ($ok && !isset($error)) ? $ok : null,

				'fields' => $fields,

				'buttons' => array(
					array(
						'label' => qa_lang_html('main/save_button'),
						'tags' => 'NAME="accept_save_button"',
					     ),
					array(
						'label' => qa_lang_html('admin/reset_options_button'),
						'tags' => 'NAME="accept_reset_button"',
					     ),
					),
			    );
	}

	public function index_post($postid, $type, $questionid, $parentid, $title, $content, $format, $text, $tagstring, $categoryid) {
		$this->create_es_client_if_needed();
		$params = array();
		$params['body']  = array(
		     'questionid' => $questionid,
		     'title' => $title,
		     'content' => $content,
		     'format' => $format,
		     'text' => $text,
		     'tagstring' => $tagstring,
		     'categoryid' => $categoryid,
		     'parentid' => $parentid,
		     'postid' => $postid,
		     'type' => $type	
		);

		$params['index'] = $this->es_index_name;
		$params['type']  = 'post';
		$params['id']    = $postid;

		// Document will be indexed to my_index/my_type/my_id
		$ret = $this->es_client->index($params);
	}

	public function unindex_post($postid) {
		$this->create_es_client_if_needed();
		$deleteParams = array();
		$deleteParams['index'] = $this->es_index_name;
		$deleteParams['type'] = 'post';
		$deleteParams['id'] = $postid;
		if ( $this->es_client->exists($deleteParams)) 
			$retDelete = $this->es_client->delete($deleteParams);
	}

	public function move_post($postid, $categoryid) {
		$this->create_es_client_if_needed();
		$params = array();
		$params['index'] = $this->es_index_name;
                $params['type']  = 'post';
                $params['id']    = $postid;
		
		if ( $this->es_client->exists($params)) {
		   $params['body'] = array ('categoryid' => $categoryid);
		   $this->es_client->update($params);	
		}	
	}

	public function process_search($query, $start, $count, $userid, $absoluteurls, $fullcontent) {
		$this->create_es_client_if_needed();
		$results = array();
		$params['index'] = $this->es_index_name;
		$params['type']  = 'post';
		$params['body']['query']['multi_match'] = array ( 'query' => $query , 'fields' => array('title','content','text'));
        $params['from'] = $start;
        $params['size'] = $count;
		$es_results = $this->es_client->search($params);

		$total_found = $es_results['hits']['total'];

		foreach ( $es_results['hits']['hits'] as $q) {
			$question = $q['_source'];
			$results[]=array(
                                'question_postid' => $question['questionid'],
                                'match_type' => $question['type'],
                                'match_postid' => $question['postid'],
				'title' => $question['title']
                        );
		}
		return $results;
	}
	
}
