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
	 * Clock drift
	 */
	private $drift = 10;

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

	public function run()
	{
		$con=new Memcached(true);
		if(!$con->addServer($this->server['host'],$this->server['port'])){
			throw new Exception('Failed to connect to memcached server');
		}

		$limit=$this->limits['default'];
		// gen key, set limit
		$key=null;
		foreach($this->keys as $field){
			$key.='|'.$_SERVER[$field];
			if(isset($this->limits[$field]) and isset($this->limits[$field][$_SERVER[$field]])){
				$limit=$this->limits[$field][$_SERVER[$field]];
			}
		}
		$key=$this->prefix.base64_encode(sha1($key,true));

		// gen time period
		$start=time();
		$start-=$start%$this->resolution;
		$stop=$start+$this->lifetime;

		// Inc values
		$values=array();
		$multiSet=array();
		while($start<$stop){
			$token=$start.$key;
			$value=$con->increment($token);
			if($value==false and $con->getResultCode()==Memcached::RES_NOTFOUND)
			{
				$multiSet[$token]=1;
				$value=1;
			}
			$values[]=$value;
			$start+=$this->resolution;
		}

		if(count($multiSet)){
			$con->setMulti($multiSet,$stop+$this->drift);
		}

		$value=$values[0];
		if($value===false){
			if($this->quietMemcacheFail)
				return;
			throw new Exception('Failed to increment value, got'.
				print_r($value,true));
		}

		if($value>$limit){
			header("HTTP/1.0 403 Too many requests");
			die();
		}
		return $values;
	}

}
