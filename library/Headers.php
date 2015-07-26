<?php
/*
 * * Copyright (C) 2015  Germans Zaharovs <germans@germans.me.uk>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>
 */
namespace zaharovs\collector;
require 'vendor/autoload.php';

/**
 * This class to be used for storing headear information for required 
 * specific website. These headers to be used for cURL library at later 
 * process
 * 
 * @author GermansZaharovs
 * @version 1.0
 */
class Headers 
{
	
	/**
	 * Property holding all of the headers information
	 * @var array of strings
	 */
	private $headers = array();
	
	/**
	 * Property holding host header
	 * @var String
	 */
	private $host;
	
	/**
	 * Property holding user Agent
	 * @var String
	 */
	private $userAgent;
	
	/**
	 * Property holding certain media types, which are 
	 * acceptable for the response.
	 * @var String
	 */
	private $accept;
	
	/**
	 * The set of natural languages that are preferred as a response 
	 * to the request
	 * @var String
	 */
	private $acceptLanguage;
	
	/**
	 * Content coding restriction that are acceptable
	 * @var String
	 */
	private $acceptEncoding;
	
	/**
	 * Allows the client to specify, for the server's benefit,
	 * the address (URI) of the resource from which the Request-URL 
	 * was obtained (checck cURL documention for more CURLOPT_REFERER)
	 * @var String
	 */
	private $referer;
	
	/**
	 * Allows the sender to specify options that are desired for
	 * that particular connection
	 * @var String
	 */
	private $connection;
	
	/**
	 * Getter method for host property of the header
	 *
	 * @return String
	 */
	public function getHost() 
	{
		return $this->host;
	}
	
	/**
	 * Setter method for host header of the cURL
	 *
	 * @param String $host required       	
	 */
	public function setHost($host) 
	{
		$this->host = $host;
	}
	
	/**
	 * Getter method for user agent of cURL
	 *
	 * @return String
	 */
	public function getUserAgent() 
	{
		return $this->userAgent;
	}
	
	/**
	 * Setter method for user agent of cURL
	 *
	 * @param String $userAgent required.     	
	 */
	public function setUserAgent($userAgent) 
	{
		$this->userAgent = $userAgent;
	}
	
	/**
	 * Getter method for accepting header (whether text, or image specifics)
	 *
	 * @return String
	 */
	public function getAccept() 
	{
		return $this->accept;
	}
	
	/**
	 * Setter method for accept header
	 *
	 * @param String $accept required.       	
	 */
	public function setAccept($accept) 
	{
		$this->accept = $accept;
	}
	
	/**
	 * Getter method for language cURL
	 *
	 * @return String
	 */
	public function getAcceptLanguage() 
	{
		return $this->acceptLanguage;
	}
	
	/**
	 * Setter method for language header cURL
	 *
	 * @param String $acceptLanguage required.     	
	 */
	public function setAcceptLanguage($acceptLanguage) 
	{
		$this->acceptLanguage = $acceptLanguage;
	}
	
	/**
	 * Getter method for Accept Encoding header
	 *
	 * @return String
	 */
	public function getAcceptEncoding() 
	{
		return $this->acceptEncoding;
	}
	
	/**
	 * Setter method for accept encoding header
	 *
	 * @param String $acceptEncoding required.      	
	 */
	public function setAcceptEncoding($acceptEncoding) 
	{
		$this->acceptEncoding = $acceptEncoding;
	}
	
	/**
	 * Getter method for referer header
	 *
	 * @return String
	 */
	public function getReferer() 
	{
		return $this->referer;
	}
	
	/**
	 * Setter method for referer header
	 *
	 * @param String $referer        	
	 */
	public function setReferer($referer) 
	{
		$this->referer = $referer;
	}
	
	/**
	 * Getter method for connection header
	 *
	 * @return String
	 */
	public function getConnection() 
	{
		return $this->connection;
	}
	
	/**
	 * Setter method for connection header
	 *
	 * @param String $connection        	
	 */
	public function setConnection($connection) 
	{
		$this->connection = $connection;
	}
	
	/**
	 * Helper method, for checking, whether any property receiving is null or not,
	 * if it is -> then raise an exception
	 * 
	 * @param string $property required.		header property to check agains null
	 * @throws CollectorException if parameter received is null
	 */
	private function checkNotNull($property)
	{
		if($property==null)
		{
			throw new CollectorException("Header function exception: no properties can be null!");
		}
	}
	
	/**
	 * Method for building headers
	 * 
	 * @return array of built headers
	 */
	public function buildHeaders()
	{
		//set header empty again
		$this->headers=array();
		//get all of the information here
		$this->headers[]=$this->getAccept();
		$this->headers[]=$this->getAcceptEncoding();
		$this->headers[]=$this->getAcceptLanguage();
		$this->headers[]=$this->getConnection();
		$this->headers[]=$this->getHost();
		$this->headers[]=$this->getReferer();
		$this->headers[]=$this->getUserAgent();
		return $this->headers;
	}
	
	/**
	 * Make basic Fake headers (for quick check of anything, as many admins in web,
	 * 			will be not too cautious about header property, and therefore for 
	 * 			quick snap of some methods would require quick fake headers)
	 * 
	 * @param String $refererIn required.		representing referer to current page
	 * @param String $userAgentIn required.		represents current browser agent used
	 * @return string[] header array
	 */
	public static function makeFakeHeaders($refererIn, $userAgentIn)
	{
		$headers = array();
		//make necessary headers
		$headers[] = "Referer: $refererIn";
		$headers[] = "User-Agent: $userAgentIn";
		
		//return basic fake headers
		return $headers;
	}
	
}