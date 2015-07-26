<?php
/*
 *  Copyright (C) 2015  Germans Zaharovs <germans@germans.me.uk>
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
 * Class to be of control, of what directories to be scanned, what resources to be downloaded & what pages
 * to authenticate
 * 
 * @author Germans Zaharovs
 * @version 1.0
 */
class Step
{
	/**
	 * Constant, showing explicitely, that type of step is resource download to server (e.g. image)
	 * @var Integer
	 */
	const RESOURCE=0;
	
	/**
	 * Constant, showing explicitely, that type of step is Authentication to website
	 * @var Integer
	 */
	const AUTHENTICATE=1;
	
	/**
	 * Constant, showing explicitely, that type of step is Ordinary execution to any location of the website
	 * @var Integer
	 */
	const ORDINARY=2;
	
	/**
	 * Constant, showing explicitely, that type of step is resource download to server (e.g. image) with reseting cookie function
	 * UPDATE here: not within resource to be set, but within main page location, after this use RESOURCE as CAPTCHA download.
	 * @var Integer
	 */
	const RESOURCE_RESET=3;
	
	/**
	 * Passowrd of the Authentication step
	 * @var String
	 */
	private $password;
	
	/**
	 * Username of the Authentication step
	 * @var String
	 */
	private $username;
	
	/**
	 * Captcha of the Authentication step
	 * @var String
	 */
	private $captcha;

	/**
	 * For security reasons some parts of website require to have new cookie,
	 * however CLI PHP automatically don't do this. This property will make sure 
	 * to reset (delete & set back) cookie
	 * @var bool
	 */
	private $resetCookie;
	
	/**
	 * Directory path, into which save resource downloaded from the Step $path
	 * @var String representing path to directory location
	 */
	private $saveResourceLocation;
	
	/**
	 * Path variable is link in website, which Step is going to execute
	 * @var String (link in website)
	 */
	private $path;
	
	/**
	 * Property, showing which enumerated type of the Step it belongs to 
	 * @var Enumerated integer 
	 */
	private $type;
	
	/**
	 * Visited property represents, whether step has been already visited or not
	 * @var bool
	 */
	private $visited;
	
	/**
	 * Website domain, which to be discovered
	 * @var String
	 */
	private $website;
	
	/**
	 * Header information of the step
	 * @var Headers
	 */
	private $headers=null;
	
	/**
	 * Property for holding information of category and subcategory of the step
	 * @var array of category=>subcategory;
	 */
	private $category;
	
	/**
	 * Getter method for category && subcategory array
	 * 
	 * @return array of category=>subcategory;
	 */
	public function getCategory()
	{
		return $this->category;
	}
	
	/**
	 * Setter method for $category array 
	 * 
	 * @param string $categoryIn required. 		Specifies category for the step to be performed
	 * @param string $subCategoryIn optional. 	Specifies subcategory for the step to be performed
	 */
	public function setCategory($categoryIn, $subCategoryIn=false)
	{
		//check if $subcategory is given
		if($subCategoryIn)
		{
			$this->category[$categoryIn] = $subCategoryIn;
		}
		else 
		{
			$this->category[$categoryIn] = "NO_SUBCATEGORY";
		}
	}
	
	/**
	 * Getter method for $headers property
	 * 
	 * @return String[] Representing headers  of the cURL
	 * @throws CollectorException If there is no Headers set for current step
	 */
	public function getHeaders() 
	{
		if($this->headers==null)
		{
			throw new CollectorException("Headers are need to be set for every step to be performed");
		}
		return $this->headers;
	}
	
	/**
	 * Setter method for $headers property
	 * 
	 * @param array $headers of header information
	 */
	public function setHeaders($headers) 
	{
		$this->headers = $headers;
	}
	
	/**
	 * Getter function of $website property 
	 * (REQUIRED FOR AUTHENTICATION AND SPECIFIC WEBSITE PROPERTIES CHECKS)
	 * 
	 * @return string
	 */
	public function getWebsite()
	{
		return $this->website;
	}
	
	/**
	 * Setter for website's domain
	 * 
	 * @param String $websiteIn required.		Website's domain
	 * @throws CollectorException If parameter is not of type string
	 */
	public function setWebsite($websiteIn)
	{
		if(!is_string($websiteIn))
		{
			throw new CollectorException("Website name must be of type string. Re-check");
		}
		$this->website=$websiteIn;
	}
	
	/**
	 * Setter for $resetCookie property, for reseting cookie
	 * 
	 * @param boolean $boolIn required. 		True, if reset is required, else False
	 * @throws CollectorException If parameter $boolIn is not of type boolean
	 */
	public function setResetCookie($boolIn)
	{
		//check that input is boolean
		if(!is_bool($boolIn))
		{
			throw new CollectorException("Property \$resetCookie must be of type boolean!");
		}
		$this->resetCookie=$boolIn;
	}
	
	/**
	 * Getter for $resetCookie property
	 * 
	 * @return boolean
	 */
	public function getResetCookie()
	{
		return $this->resetCookie;
	}
	
	/**
	 * Getter for type of the current step
	 * 
	 * @return Enumerated (integer constant of the Step)
	 */
	public function getType()
	{
		return $this->type;
	}
	
	/**
	 * Setter for captcha property
	 * 
	 * @param String $captchaIn required.		Represents captcha string
	 * @throws CollectorException is thrown, if captcha is either not string or empty
	 */
	public function setCaptcha($captchaIn)
	{
		if(!is_string($captchaIn)||strlen($captchaIn)==0)
		{
			throw new CollectorException("Captcha has to be string and not empty. Re-check");
		}
		$this->captcha=$captchaIn;
	}
	
	/**
	 * Getter for captcha property
	 * 
	 * @return string representing captcha property
	 */	
	public function getCaptcha()
	{
		return $this->captcha;
	}
	
	/**
	 * Setter for password property of the step
	 * 
	 * @param String $passwordIn - password representation string
	 * @throws CollectorException is thrown, if password is either not of type string, or length is zero
	 */
	public function setPassword($passwordIn)
	{
		if(!is_string($passwordIn)||strlen($passwordIn)==0)
		{
			throw new CollectorException("Password has to be string and not empty. Re-check!");
		}
		$this->password=$passwordIn;
	}
	
	/**
	 * Getter of the password property
	 * 
	 * @return String representing password of the Authentication step
	 */
	public function getPassword()
	{
		return $this->password;
	}
	
	/**
	 * Setter for username property of the Step
	 * 
	 * @param String $usernameIn required. 		Representation of user
	 * @throws CollectorException is thrown if username is not a string or length of username is zero
	 */
	public function setUserName($usernameIn)
	{
		if(!is_string($usernameIn)||!strlen($usernameIn))
		{
			throw new CollectorException("Username has to be string and not empty! Re-check!");
		}
		$this->username=$usernameIn;
	}
	
	/**
	 * Getter function for username property
	 * 
	 * @return string Representing username property
	 */
	public function getUserName()
	{
		return $this->username;
	}
	
	/**
	 * Constructor for Step class
	 * 
	 * @param string $pathIn required.			Representing Path, which to be scanned
	 * @param enumerated $typeIn required.		Enumerated from class constants type of the step
	 * @param String[] $headerIn 				Headers representation of the step
	 * @throws CollectorException if there is no such type specified
	 */
	public function __construct($pathIn, $typeIn, $headerIn)
	{
		switch ($typeIn)
		{
			case Step::ORDINARY: 
					$this->type=$typeIn;
					break;
			case Step::RESOURCE:
					$this->type=$typeIn;
					break;
			case Step::AUTHENTICATE:
					$this->type=$typeIn;
					break;
			case Step::RESOURCE_RESET:
					$this->type=$typeIn;
					break;
			default:
				throw new CollectorException("Type error: There is no such type as: ".
									$typeIn . " please re-check.");
		}
		$this->setPath($pathIn);
		//set Step functions required -> unvisited and headers
		$this->setHeaders($headerIn);
		$this->visited=false;
	}
	
	/**
	 * Getter for Resource location 
	 * 
	 * @return String representing directory for resource
	 */
	public function getResourceLocation()
	{
		return $this->saveResourceLocation;
	}
	
	/**
	 * Setter for resource directory 
	 * 
	 * @param String $resourceIn represents directory path
	 */
	public function setResourceLocation($resourceIn)
	{
		//TODO IMPORTANT -> THINK THIS (RESOURCE REQUIRES TO CREATE DIRECTORIES!) {version 2.0}
		//check if resource using system location ? {version 2.0}
		$this->saveResourceLocation=$resourceIn;
	}
	
	/**
	 * Getter function of path property
	 * 
	 * @return Path of the step
	 */
	public function getPath() 
	{
		return $this->path;
	}
	
	/**
	 * Getter function for referer property
	 * 
	 * @return Referer string property
	 */
	public function getReferer()
	{
		return $this->referer;
	}
	
	/**
	 * Check whether Step has been already visited by Collector fetch or not
	 * 
	 * @return True if has been visited, or False otherwise
	 */
	public function isVisited()
	{
		return $this->visited;
	}
	
	/**
	 * Setter for the path of the step
	 * 
	 * @param String $pathIn required.			Represents path of the desired step to be completed by collector
	 * @throws Exception if parameter receiving is not of type string.
	 */
	public function setPath($pathIn)
	{
		//check that path is string beforehand, and if it is not raise an exception
		if (!is_string($pathIn))
		{
			throw new \Exception("Path has to be a String, please re-check it!");
		}
		//TODO think here to make some validation before setting path?
		
		//if it passes then put it as property
		$this->path=$pathIn;
	}
	
	//TODO under review for spider's reseting functions
	/**
	 * Setter for visited property of the Step. If spider's instance has been given, it will reset 
	 * 			execution plan, therefore brute force re-check could be possible {version 2.0}
	 * 
	 * @param boolean $boolIn required.				Set true, if step has been visited, else false
	 * @param Spider $inst optional.				Spider, which requires to reset current execution (if not null)
	 * @throws CollectorException if parameter is not of type boolean 
	 */
	public function setVisited($boolIn, Spider $inst=null)
	{
		//check as $bool parameter has to be of type boolean
		if (!is_bool($boolIn))
		{
			throw new CollectorException("Setter for visited must be either false, or true boolean!");
		}
		if($inst!=null)
		{
			//reset steps in spider
			$inst->setResetSteps();
		}
		//set parameter 
		$this->visited=$boolIn;
	}
	
	/**
	 * Setter method for referer for webpage
	 * 
	 * @param String $refererIn required.			Represents referer
	 * @throws CollectorException if referer's parameter is not of type string
	 */
	public function setReferer($refererIn)
	{
		//check that refererIn is a string, throw exception otherwise
		if(!is_string($refererIn))
		{
			throw new CollectorException("Referer variable to be of type string! Type: ". var_dump($refererIn));
		}
		//set parameter
		$this->referer=$refererIn;
	}
	
	
}