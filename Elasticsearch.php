<?php

/**
 * ElasticSearch
 *
 */

class Elasticsearch {

	// library
	static public $config;
	static public $log;
	static public $curl;

	// data
	static public $data;
	static public $tmp;
	static public $debug;

	/**
	 * construct
	 *
	 */
	public function __construct($server='http://127.0.0.1:9200')
	{
		// config
		ini_set('memory_limit', -1);

		// declcare
		self::$config=(object) array();
		self::$log=(object) array();

		// config
		self::$config->host=$server;

		// build
		self::__build();
	}

	/**
	 * Build
	 *
	 */
	public function __build()
	{
		// log
		self::$log->path=getenv('DOCUMENT_ROOT').'/log';
		self::$log->archive='es-error-'.date('YmdHi').'-'.'%rand%'.'.log';

		// config
		self::$config->log=false;
		self::$config->debug=false;

		// header
		self::$config->header=array();
		self::$config->header[]='accept: application/json';
		self::$config->header[]='content-type: application/json';

		// scroll
		self::$config->scroll='1m';

		// data
		self::$data=(object) array();
	}

	/**
	 * Log
	 *
	 */
	public function _log()
	{
		// check
		if(!(isset(self::$data->error) || self::$data->code!=200))
			return true;

		// config
		self::$log->archive=str_replace('%rand%', rand(111111, 999999), self::$log->archive);

		// content
		self::$log->content=self::$log->archive.PHP_EOL.PHP_EOL;
		self::$log->content.='Time: '.self::$config->time.PHP_EOL;
		self::$log->content.='Code: '.self::$data->code.PHP_EOL;
		self::$log->content.='Method: '.self::$config->method.PHP_EOL;
		self::$log->content.='Path: '.self::$config->path.PHP_EOL;
		self::$log->content.='Took: '.self::$data->took.PHP_EOL;
		self::$log->content.='DSL: '.self::$config->dsl.PHP_EOL;
		self::$log->content.='Result: '.print_r(self::$data, true).PHP_EOL;

		// file
		self::$log->file=fopen(self::$log->path.'/'.self::$log->archive, 'w+');
		fwrite(self::$log->file, self::$log->content);
		fclose(self::$log->file);
	}

	/**
	 * Debug
	 *
	 */
	public function _debug()
	{
		// debug
		$key=date('YmdHis').'-'.microtime(true);
		$this->debug[$key]=(object) array();
		$this->debug[$key]->time=self::$config->time;
		$this->debug[$key]->code=self::$data->code;
		$this->debug[$key]->method=self::$config->method;
		$this->debug[$key]->path=self::$config->path;
		$this->debug[$key]->took=self::$data->took;
		$this->debug[$key]->dsl=self::$config->dsl;
	}

	/**
	 * Address
	 *
	 */
	public function _address($path='/')
	{
		// path
		self::$config->path=$path;

		// url
		if(strpos('http://', self::$config->path)===false && strpos('https://', self::$config->path)===false)
			self::$config->url=self::$config->host.self::$config->path;
		else
			self::$config->url=self::$config->path;

		// return
		return self::$config->url;
	}

	/**
	 * Execute
	 *
	 * @param type $method
	 * @param type $path
	 * @param type $data
	 *
	 * @return type
	 * @throws Exception
	 */
	public function execute($method='GET', $path='/', $dsl=null)
	{
		// config
		self::$config->time=microtime(true);
		self::$config->method=strtoupper($method);
		self::$config->url=self::_address($path);
		self::$config->dsl=$dsl;

		// execute
		self::$curl=curl_init();
		curl_setopt(self::$curl, CURLOPT_URL, self::$config->url);
		curl_setopt(self::$curl, CURLOPT_TIMEOUT, 1000);
		curl_setopt(self::$curl, CURLOPT_HTTPHEADER, self::$config->header);
		curl_setopt(self::$curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt(self::$curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt(self::$curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt(self::$curl, CURLOPT_CUSTOMREQUEST, self::$config->method);
		if(!empty(self::$config->dsl))
			curl_setopt(self::$curl, CURLOPT_POSTFIELDS, self::$config->dsl);
		self::$data=curl_exec(self::$curl);
		self::$data=(object) json_decode(self::$data);
		self::$data->code=curl_getinfo(self::$curl, CURLINFO_HTTP_CODE);
		curl_close(self::$curl);

		// result
		if(!isset(self::$data->took))
			self::$data->took=0;

		// debug
		if(self::$config->debug)
			self::_debug();

		// log
		if(self::$config->log)
			self::_log();

		// time
		self::$config->time=(microtime(true)-self::$config->time);

		// check
		if(empty(self::$data))
			return false;

		// result
		return self::$data;
	}

	/**
	 * Scroll
	 *
	 * @param type $path
	 * @param type $data
	 *
	 * @return type
	 * @throws Exception
	 */
	public function scroll($path='/', $dsl=null)
	{
		// config
		self::$config->time=microtime(true);
		self::$config->method='GET';
		self::$config->url=self::_address($path);
		self::$config->dsl=$dsl;
		self::$config->execute=0;
		self::$config->took=0;
		self::$config->scroll_id=array();

		// start
		do{

			// control
			self::$config->next=false;
			if(self::$config->execute==0)
				self::$config->url=self::$config->host.self::$config->path.'?scroll='.self::$config->scroll;
			else{
				self::$config->url=self::$config->host.'/_search/scroll';
				self::$config->dsl=json_encode(array(
					'scroll'=>self::$config->scroll,
					'scroll_id'=>end(self::$config->scroll_id)
				));
			}

			// execute
			self::$curl=curl_init();
			curl_setopt(self::$curl, CURLOPT_URL, self::$config->url);
			curl_setopt(self::$curl, CURLOPT_HTTPHEADER, self::$config->header);
			curl_setopt(self::$curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt(self::$curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt(self::$curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt(self::$curl, CURLOPT_CUSTOMREQUEST, self::$config->method);
			if(!empty(self::$config->dsl))
				curl_setopt(self::$curl, CURLOPT_POSTFIELDS, self::$config->dsl);
			self::$tmp=curl_exec(self::$curl);
			self::$tmp=(object) json_decode(self::$tmp);
			if(self::$config->execute==0)
				self::$data=(object) (array) self::$tmp;
			self::$data->code=curl_getinfo(self::$curl, CURLINFO_HTTP_CODE);
			curl_close(self::$curl);

			// result
			if(isset(self::$tmp->_scroll_id))
				self::$config->scroll_id[]=self::$tmp->_scroll_id;
			if(!isset(self::$data->total))
				self::$data->total=0;
			if(self::$config->execute>0 && isset(self::$tmp->took))
				self::$data->took+=self::$tmp->took;
			if(isset(self::$tmp->hits->hits) && count(self::$tmp->hits->hits)>0)
				self::$config->next=true;

			// mount
			if(self::$config->execute>0 && isset(self::$tmp->hits->hits) && count(self::$tmp->hits->hits)>0){
				self::$data->total+=count(self::$tmp->hits->hits);
				foreach(self::$tmp->hits->hits as $row)
					self::$data->hits->hits[]=$row;
			}

			// clear
			self::$tmp=null;

			// control
			self::$config->execute++;

		// check
		}while(self::$config->next);

		// debug
		if(self::$config->debug)
			self::_debug();

		// log
		if(self::$config->log)
			self::_log();

		// clear
		self::$config->method='DELETE';
		self::$config->url=self::$config->host.'/_search/scroll';
		self::$config->dsl='{
			"scroll_id":[
				"'.implode('", "', self::$config->scroll_id).'"
			]
		}';

		// execute
		self::$curl=curl_init();
		curl_setopt(self::$curl, CURLOPT_URL, self::$config->url);
		curl_setopt(self::$curl, CURLOPT_HTTPHEADER, self::$config->header);
		curl_setopt(self::$curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt(self::$curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt(self::$curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt(self::$curl, CURLOPT_CUSTOMREQUEST, self::$config->method);
		if(!empty(self::$config->dsl))
			curl_setopt(self::$curl, CURLOPT_POSTFIELDS, self::$config->dsl);
		curl_exec(self::$curl);
		curl_close(self::$curl);

		// time
		self::$config->time=(microtime(true)-self::$config->time);

		// check
		if(empty(self::$data))
			return false;

		// result
		return self::$data;
	}

	/**
	 * GET
	 *
	 * @param type $path
	 * @param type $data
	 *
	 * @return type
	 * @throws Exception
	 */
	public function get($path='/', $dsl=null)
	{
		return self::execute('GET', $path, $dsl);
	}

	/**
	 * PUT
	 *
	 * @param type $path
	 * @param type $data
	 *
	 * @return type
	 * @throws Exception
	 */
	public function put($path='/', $dsl=null)
	{
		return self::execute('PUT', $path, $dsl);
	}

	/**
	 * POST
	 *
	 * @param type $path
	 * @param type $data
	 *
	 * @return type
	 * @throws Exception
	 */
	public function post($path='/', $dsl=null)
	{
		return self::execute('POST', $path, $dsl);
	}

	/**
	 * DELETE
	 *
	 * @param type $path
	 * @param type $data
	 *
	 * @return type
	 * @throws Exception
	 */
	public function delete($path='/')
	{
		return self::execute('DELETE', $path);
	}

}
