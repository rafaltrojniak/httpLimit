<?php
class httpLimit{
	
	/**
	 * Memcache server connector
	 */
	private $server;

	/**
	 * Memcache prefix to use 
	 */
	private $prefix;

	/**
	 * Array of keys to use for generating token 
	 */
	private $keys;

	/**
	 * Array of limits 
	 */
	private $limits;
	
	/**
	 * Resolution of checks  in seconds
	 */
	private $resolution;
	
	/**
	 * Lifetime for checks in seconds
	 */
	private $lifetime;

	/**
	 * Should we ignore memcache fils ? 
	 */
	private $quietMemcacheFail;

	/**
	 * Sets full configuration array 
	 * 
	 * @param $config 
	 * @return 
	 * @author : Rafał Trójniak rafal@trojniak.net
	 */
	public function setConfig($config)
	{
		extract($config);
		if(isset($prefix))
			$this->prefix=(string)$prefix;

		if(isset($server)) {
			if(!is_array($server) or !isset($server['host']) or !isset($server['port'])){
				throw new Exception(
					'Server should be array with keys host and port');
			}
			$this->server=$server;
		}

		if(isset($keys)){
			if(!is_array($keys)){
				throw new Exception( 'keys should be array of strings');
			}
			$this->keys=$keys;
		}

		if(isset($limits)){
			if(!is_array($limits)){
				throw new Exception( 'limits should be associate array of key=>limit');
			}
			$this->limits=$limits;
		}

		if(isset($resolution))
			$this->resolution=(int)$resolution;

		if(isset($lifetime))
			$this->lifetime=(int)$lifetime;

		if(isset($quietMemcacheFail))
			$this->quietMemcacheFail=(bool)$quietMemcacheFail;
	}

	public function runCheck()
	{
		$con=new Memcached(true);
		$con->addServer($this->server['host'],$this->server['port']);

		// gen key
		$key=$this->prefix;
		foreach($this->keys as $field){
			$key.='|'.$field;
		}

		// gen time period
		$start=time();
		$start-=$start%$this->resolution;
		$stop=$start+$this->lifetime;

		// Inc values
		$values=array();
		while($start<$stop){
			$token=$start.$key;
			$values[]=$con->increment($token);
			$start+=$this->resolution;
		}

		$value=$values[0];
		if($value==Memcached::RES_NOTFOUND or $value===false){
			if($this->quietMemcacheFail)
				return;
			throw new Exception('Failed to increment value, got'.
				print_r($value,true));
		}

		$limit=$this->limits['default'];
		if(isset($this->limits[$key])){
			$limit=$this->limits[$key];
		}

		if($value>$limit){
			header("HTTP/1.0 403 Too many requests");
			die();
		}

	}

}
