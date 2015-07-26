<?php
/*
 * Copyright (C) 2015  Germans Zaharovs <germans@germans.me.uk>
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

//NOTE here:
//TODO think to implement rotation of usernames and passwords after certain time of traffic in the server {version 2.0}
//TODO also think to implement additional capability for storing steps, therefore it could be divided 
//		between many processes {version 2.0}

/**
 * Given class to represent core of the Collector programme. It holds all of the basic cURL logic
 * for manipulating websites specified.
 * 
 * Given class to be able to initiate Tor connection (using Tor interface) therefore to collect TOR network websites.
 * 
 * @author Germans Zaharovs
 * @version 1.0
 */
class Spider implements TorAble
{	
	/**
	 * Constant, specifying that array of category=>subcategory to be collected for 
	 * 			 startFetchData() method.
	 * @var boolean
	 */
	const PRE_COLLECT_CATEGORY=true;
	
	/**
	 * Constant, specifying that array of category=>subcategory WILL NOT be collected for 
	 * 			 startFetchData() method
	 * @var boolean
	 * 
	 */
	const SKIP_COLLECT_CATEGORY=false;
	
	/**
	 * Constant of the Spider, defining, that steps spider is capable to scan unlimited products
	 * @var String 
	 */
	const UNLIMITED="unlimited";
	
	/**
	 * Constant representing fastest collection method for spider -> no sleep will affect spider
	 * @var String
	 */
	const FASTEST_METHOD="fastest_collect_method";
	
	/**
	 * Stores limit capacity of spider, of how many steps (MAX) can be added
	 * @var integer
	 */
	private $limit;
	
	/**
	 * Stores proxy string, which to be set with Tor interface
	 * @var String
	 */
	private $proxy;
	
	/**
	 * Stores all the steps to be taken by given spider
	 * @var array of Steps
	 */
	private $steps = array();
	
	//TODO MAY NOT BE NEEDED AT ALL, CHECK LATER. THIS MIGHT NEED TO BE, IF DISCOVERING, HOW
	//WOULD THREADS be implemented here {version 2.0}
	/**
	 * Identifies, whether spider has been finished to run, or still running
	 * @var boolean
	 */
	private $isRunning;
	
	/**
	 * Location, where cookie file for Spider has been set
	 * @var String
	 */
	private $cookie;
	
	//NOTE here, is not used yet for checking, whether is or not authenticated. Make implemen-
	//tation in version 2, or delete it later.
	/**
	 * Check, whether website has been authenticated or not
	 * @var boolean
	 */
	private static $isAuthenticated=false;
	
	/**
	 * Browser name, which to be seen to admins of website server
	 * @var String specifying specific browser name
	 */
	private $browserName;
	
	//TODO think at {version 2.0} whether it static, or it could be threaded spiders
	/**
	 * Instance in processes to check, if the process is still running {under development - as windows specific}
	 * @var String procees name
	 */
	private static $browserInstance;
	
	/**
	 * Property showing whether steps have been reset, and requires to check from beginning, or not
	 * @var Integer
	 */
	private $resetStep=0;
	
	/**
	 * Steps already collected, to be re-run in case of failure
	 * @var Step[]
	 */
	public static $collectedSteps = array();
	
	/**
	 * Getter method for current step of the spider
	 * 
	 * @return int Current step
	 */
	public function getResetStep()
	{
		return $this->resetStep;
	}
	
	/**
	 * Setter for $limit property of the class
	 * 
	 * @param int $limitIn required.		Limit of the steps for spider (don't forget about constant UNLIMITED)
	 * @throws CollectorException if either parameter is not of type integer, or is zero || negative && not UNLIMITED
	 */
	public function setLimit($limitIn)
	{
		//check if input is of type integer or of type unlimited
		if(!is_int($limitIn)&& strcasecmp($limitIn, self::UNLIMITED)!=0)
		{
			throw new CollectorException("Input parameter must be of type integer!");
		}
		//check that limit is more than zero if not unlimited
		if(!strcasecmp($limitIn, self::UNLIMITED)==0)
		{
			if($limitIn <=0)
			{
				throw new CollectorException("Input parameter must be greater than zero");
			}
		}
		//else set it in here
		$this->limit=$limitIn;
	}
	
	/**
	 * Getter for limit property of the spider
	 * @return int OR constant "unlimited"
	 */
	public function getLimit()
	{
		return $this->limit;
	}
	
	/**
	 * Method, checking whether limit of spider has been reached or not
	 * 
	 * @return boolean True if limit of spider steps is reached, otherwise False
	 */
	protected function isReachedLimit()
	{
		//if limit is UNLIMITED -> then always false
		if(is_string($this->limit))
		{
			return false;
		}
		//if limit has reached count -> then true
		if($this->limit==count($this->steps))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Getter for steps array, to be performed by spider(s)
	 * 
	 * @return Step array:
	 */
	public function getSteps()
	{
		return $this->steps;
	}
	
	/**
	 * Getter for browserName property of the spider
	 * 
	 * @return string browserName property
	 */
	public function getBrowserName()
	{
		return $this->browserName;
	}
	
	/**
	 * Setter for browserName property for the spider
	 * 
	 * @param String $browserNameIn required.	Variable representing browser of the client
	 */
	public function setBrowserName($browserNameIn)
	{
		//property which to be seen by server admins
		$this->browserName=$browserNameIn;
		
	}

	/**
	 * (non-PHPdoc)
	 * @see TorAble::setProxy()
	 */
	public function setProxy($proxyIn)
	{
		//TODO maybe check proxy in here? {version 2.0}
		$this->proxy=$proxyIn;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see TorAble::getProxy()
	 */
	public function getProxy()
	{
		return $this->proxy;
	}
	
	//FIXME at the moment has to be fixed for starting TOR in Linux CentOS and 
	/**
	 * (non-PHPdoc)
	 * @see TorAble::startTor()
	 */
	public function startTor($torLocation)
	{
		//check what is the process name? and set browserInstance property
		self::$browserInstance=self::getProcessName($torLocation);
		//TODO check if process is already running, not start, else start UNDER DEVELOPMENT ASAP
		if (!$this->isProcessRunning())
		{
			//THOUGHTS IN HERE:
			//escape ordinar string, therefore all parameters could be read by system
			//FIXME HERE EXEC WILL NOT WORK, AS IT HANGS ON PROCESS TILL IT FINISHES,
			//THEREFORE TRY TO USE POWERSHELL INSTEAD 
			//http://stackoverflow.com/questions/5367261/php-exec-as-background-process-windows-wampserver-environment
			//OR? http://stackoverflow.com/questions/7692263/running-a-php-exec-in-the-background-on-windows
			return false;
		}
		return true;
	}
	
	
	//TODO not used at the moment method, intent to get from tasklist (of all processes) 
	//names. However leave here for proper review at later. Might be useful for {version 2.0}
	/**
	 * Get last instance of the direction path - for process name in this case
	 * 
	 * @param String $dirWithFile direction path with file name
	 * @return String - process name
	 */
	public static function getProcessName($dirWithFile)
	{
		//explode received parameter, that makes sure,
		//that windows and linux directory separates
		$arr = explode(DIRECTORY_SEPARATOR, $dirWithFile);
		//return name of the process
		return $arr[count($arr)-1];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see TorAble::cancelTor()
	 */
	public function cancelTor()
	{
		//FIXME this section to be reviewed also
		if(!$this->isProcessRunning ())
		{
			throw new CollectorException(self::$browserInstance." is not running.");
		}
		//windows specific
		//exec("TASKKILL /IM ".self::$browserInstance);
	}
	
	/**
	 * Check, whether process is still running in OS (works now in beta for windows, but linux to be reviewed)*
	 * 													*uncomment for windows use [TO BE REVIEWED LATER]
	 * 
	 * @return True, if process is found in tasklist, False otherwise
	 */
	 public function isProcessRunning() 
	 {
	 	//FIXME make generic method, which will tell, if TOR IS running or not;
	 	//this could be something like openning web page set for WebsiteCollector or something
	 	//maybe make it for limited num of times, and on failure throw exception?
		// 	 	$tasklist=array();
		// 	 	//rethink this code here 	
		// 		exec("tasklist", $tasklist);
		// 		$num =0;
		// 		foreach ($tasklist as $task)
		// 		{
		// 			$taskArray = explode(" ", $task);
		// 			$task = trim($taskArray[0]);
		// 			if(strcmp(self::$browserInstance, $task)==0)
		// 			{
		// 				return true;
		// 			}
		// 			$num++;
		// 		}
		// 		return false;
		//at the moment return just true, as connected. at version 2.0 fix this, to make sure it is connected
		return true;
	}

	//FIXME helper method, to search for a string of processes if occurence is there. 
	//at the moment never used, but might be required in {version 2.0} therefore  leave here for a while
	/**
	 * Recursive function for Divide & Conquere technique
	 * 
	 * @param String $strIn required.			Represents what value it is looking
	 * @param String[] $arrIn required.			Sorted array -> in which $strIn to be searched
	 * @return boolean True, if $strIn is found, False otherwise
	 */
	private static function divideConquer($strIn, $arrIn)
	{
		//check if $arrIn is bigger than 1 and if that value doesn't
		//equal to value we are looking for
		if(count($arrIn)==1)
		{
			if(strcmp($arrIn[0], $strIn)==0)
			{
				return true;
			}
			else 
			{
				return false;
			}
		}
		
		$middleNum = (int) (count($arrIn)/2);
		$middleVal = $arrIn[$middleNum];
		
		if(strnatcmp($strIn,$middleVal)==0)
		{
			//value found
			return true;
		}
		elseif (strnatcasecmp($strIn, trim($middleVal))>0)
		{
			//as $strIn is bigger, then value might be in end of array
			return self::divideConquer($strIn, array_slice($arrIn, $middleNum));
		}
		else 
		{
			return self::divideConquer($strIn, array_splice($arrIn, $middleNum));
		}	
	}
	
	/**
	 * Constructor for Spider specifying very basic needs {ps Proxy is not added, as not sure yet, there might 
	 * 					be approach to use spider with not only on TOR services} {version 2.0}
	 * 
	 * @param string $cookieIn required.		Cookie location for given spider
	 * @param String $browserNameIn	optional.	Specifies useragent client	{default is: "Mozilla/5.0 (Windows NT 6.1; rv:31.0) Gecko/20100101 Firefox/31.0"
	 * @param mixed $limitIn optional.			Integer value representing limit (UNLIMITED as default) [OPTIONAL] 
	 * @throws CollectorException if no such directory in system $cookieIn found
	 */
	public function __construct($cookieIn, 
			$browserNameIn="Mozilla/5.0 (Windows NT 6.1; rv:31.0) Gecko/20100101 Firefox/31.0",
					$limitIn=self::UNLIMITED)
	{
		//set cookie
		//check directory at first
		self::checkDirectory($cookieIn);
		//if no exception -> then set cookie
		$this->cookie=$cookieIn;
		//set limit
		$this->setLimit($limitIn);
		//and set browser
		$this->setBrowserName($browserNameIn);
	}
	
	/**
	 * Private function, to check whether directory specified in parameter exists in server.
	 * 
	 * @param String $directoryIn required.			Specifies directory with cookie file
	 * @throws CollectorException Exception, if no such directory exists in the system.
	 */
	public static function checkDirectory($directoryIn)
	{
		//make manipulation of the string, to check, whether cookie would be valid
		$tempArr = explode(DIRECTORY_SEPARATOR, $directoryIn);
		$dir = "";
		//last one is not counting
		for ($i=0; $i<count($tempArr)-1; $i++)
		{
			$dir .= $tempArr[$i].DIRECTORY_SEPARATOR;
		}
		//check whether directory exists
		if (!file_exists($dir))
		{
			//throw exception in here
			throw new CollectorException("Directory set error: In system is no such directory! Re-check!");
		}
	}
	
	
	/**
	 * Setter for cookie location
	 * 
	 * @param String $cookieIn required.		Location, of the cookie
	 */
	public function setCookie($cookieIn)
	{
		//TODO check location, before making cookie? {version 2.0}
		$this->cookie=$cookieIn;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see TorAble::setTor()
	 */
	public function setTor($chIn)
	{
		//check whether tor settings have been set at all
		if(!$this->proxy)
		{
			throw new CollectorException("Tor settings have not been set (CHECK PROXY)");
		}		
		curl_setopt($chIn, CURLOPT_PROXY, $this->proxy);
		curl_setopt($chIn, CURLOPT_PROXYTYPE, 7);
		return $chIn;
	}
	
	/**
	 * Method to execute ordinary fetch routine to website specified
	 * 
	 * @param String $url required.				Specifies location, to which cURL has to go
	 * @param String[] $headersIn required.		Header information to be needed for protection
	 * @param int[]	$sleepIn required.			Two values, which are specifying for how long to sleep spider between ordinary steps
	 * @param bool $isReset optional.			For new cookie id to be set requires to reset cookie parameter. 
	 * 											Given argument will do the job.
	 * @return String representing output of the connection 
	 * @throws CollectorException If error has appeared during execution  
	 */
	private function ordinaryExecute($url, $headersIn, $sleepIn, $isReset=false)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		//set cookie -> make and read
		//check if cookie exists?
		if($isReset)
		{
			//check if file exists firstly
			if(file_exists($this->cookie))
			{
				//delete cookie here
				unlink($this->cookie);
			}
		}		
		if(!file_exists($this->cookie))
		{
			//if not set it
			curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie);
		}
		//use cookie
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
		//make curl return string
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		//make TOR network
		$ch = $this->setTor($ch);
		//set headers
		curl_setopt($ch, CURLOPT_HTTPHEADER,$headersIn);
		//set maximum wait time
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, 20000);
		//allow redirection
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);		
		//execute
		$output = curl_exec($ch);
		//DEBUG in here, to be deleted later
// // 		//additional information about errors
// 		$string = curl_getinfo($ch);
// // 		//if $output is false
// 		if($output==false)
// 		{
// 			echo "\nThere was an error occured for following URL: {$url}\n";
// 			echo "\nHere are some information relating this error:\n";
// 			print_r($string);
// 			echo "\n";
// 			echo "here are some more details about headers:\n";
// 			print_r($headersIn);
// 		}
// 		//DEBUG finishes here.
		
		//if error did occur -> throw an exception
		if (curl_errno($ch)!=0)
		{
// 			print_r($string); 
			//make code as 2, for checking on running, that it was disconnected and re-run requires
			throw new CollectorException(printf("Error number of execution: %s. This happened for given URL: %s", curl_errno($ch), $url), 2);
		}
		//throw an exception, if there is 404 error (required for checking image allowance)
		if(curl_getinfo($ch, CURLINFO_HTTP_CODE)==404)
		{
			//don't forget to make code 2 for given kind of exceptions, to differ them
			throw new CollectorException("Error code for execution is 404", 2);
		}
		//close connection
		curl_close($ch);
		//now make sleep
		$this->sleepForWhile($sleepIn);
		return $output;
	}
	
	//NOTE pre-collection works just fine, however, it might need to be reviewed, as
	//at the moment spider does collect this information before (in WebsiteCollection class)
	//however, maybe this would be more generic approach allowing flexibility. Need to spend
	//some time on experimenting {version 2.0}
	/**
	 * Method to start execute all of the steps, which were collected by the Spider.
	 * 
	 * @param boolean $collectCategory optional. 		Predefined constant of Spider 
	 * 													[PRE_COLLECT_CATEGORY or SKIP_COLLECT_CATEGORY].
	 * 													SKIP_COLLECT_CATEGORY is default. Specifies, whether 
	 * 													return array of category & subcategory of step, or not.
	 * @param array $sleepIn optional. 					Accepting two integers -> interval by which
	 * 						 							spider needs to sleep between 
	 * 													ordinary execution (to reduce traffic).
	 * @return mixed Output of the execution, if $collectCategory parameter is SKIP_COLLECT_CATEGORY
	 * 				 if exec is successful, false otherwise. If parameter PRE_COLLECT_CATEGORY is passed
	 * 				 returns array of [category]=>[subcategory] of the step.
	 */
	public function startFetchData($collectCategoryIn=self::SKIP_COLLECT_CATEGORY, $sleepIn=array(1,10))
	{
		//check if $collectCategoryIn is PRE_COLLECT_CATEGORY
		//then make output of the first step to be executed
		if($collectCategoryIn==self::PRE_COLLECT_CATEGORY)
		{
			//get next step to be executed
			//check firstly, if $resetStep property is unvisited -> then return it, else 
			//go and make brute force check
			if(!$this->steps[$this->resetStep]->isVisited())
			{
				//return step's category & subcategory array property
				return $this->steps[$this->resetStep];
			}
			//brute force check
			$visited =0;
			while (count($this->steps)>$visited)
			{
				if(!$this->steps[$visited]->isVisited())
				{
					//return step's category & subcategory array property
					return $this->steps[$visited];
				}
				$visited++;
			}
			//if there was nothing found -> return false;
			return false;
		}
		
		//check next step to be executed
		//check steps in here
		while (count($this->steps)>$this->resetStep)
		{
			//check if step has already been taken -> ignore
			if(!$this->steps[$this->resetStep]->isVisited())
			{
				//make step
				//#1 what type of step is it?
				switch ($this->steps[$this->resetStep]->getType())
				{
					case Step::ORDINARY:
						//set path and referer for ordinary execution
						$output=$this->ordinaryExecute(
								$this->steps[$this->resetStep]->getPath(),
							$this->steps[$this->resetStep]->getHeaders(),
							$sleepIn);
						//make step visited, if action performed was successfully executed
						//FIXME however, action will be able to return false, and therefore 
						//requires to be reviewed, if step needs to be put as visited
						//or not. Maybe make additional collection class of unable to exec
						//therefore later discover what is wrong {version 2.0}
						$this->steps[$this->resetStep]->setVisited(true);
						//get output
						return $output;
						break;
					case Step::AUTHENTICATE:
						//set all the required propeerties for authentication
						$output=$this->authenticateSpider(
							 $this->steps[$this->resetStep]->getPath(),
							 $this->steps[$this->resetStep]->getHeaders(),
							 $this->steps[$this->resetStep]->getCaptcha(), 
							 $this->steps[$this->resetStep]->getUserName(), 
							 $this->steps[$this->resetStep]->getPassword(),
							 $this->steps[$this->resetStep]->getWebsite());
						$this->steps[$this->resetStep]->setVisited(true);
						//get output
						return $output;
						break;
					case Step::RESOURCE:
						//set all the required properties for saving resource
						$this->saveResource(
									$this->steps[$this->resetStep]->getPath(), 
									$this->steps[$this->resetStep]->getResourceLocation(),
									$this->steps[$this->resetStep]->getHeaders());
						$this->steps[$this->resetStep]->setVisited(true);
						break;
					case Step::RESOURCE_RESET:
						//make ordinary execute with reseting cookie
						$this->ordinaryExecute($this->steps[$this->resetStep]->getPath(), 
									$this->steps[$this->resetStep]->getHeaders(), 
																	$sleepIn, true);				
						//after execution make step as visited
						$this->steps[$this->resetStep]->setVisited(true);
				}
			}
			//increment $num
			$this->resetStep++;
		}
	}
	
	/**
	 * Check whether any step has been unvisited collected in spider
	 * 
	 * For *additional information*, when use setResetSteps() method, 
	 * make sure to use additional parameter $bruteForce true, 
	 * to check every step in spider and return (slower)
	 * 
	 * @param bool $bruteForce optional. 		Specifies, if is required to check all of the steps
	 * 											one by one to see if any is unvisited.	 * 
	 * @return boolean True if is unvisited, else false
	 */
	public function isStepsUnvisited($bruteForce=false)
	{
		if(!$bruteForce)
		{
			//check if count of steps minus current step is 0 
			//if so -> all steps are completed by program
			//make note here, that +1 is because of step to be executed not 
			//count number
			if(count($this->steps)-($this->resetStep+1)==0)
			{
				return false;
			}
		}
		else
		{
			//brute force check -> for properly sure checking, if
			//there is any step unvisited in spider
			foreach ($this->steps as $step)
			{
				if ($step->isVisited()==false)
				{
					return true;
				}
			}
			return false;
		}
		return true;
	}
	
	/**
	 * Summary: Method for the spider to add procedure steps, which to be executed later. This works like
	 * 			making all of the plan before executing step by step it.
	 * 
	 * 
	 * Detailed explanation: Method to add execution steps of the spider. 
	 * 						 Exists 3 types of the execution used here:
	 * 							#1. AUTHENTICATION: 			Execution of cURL for authentication 
	 * 															purpose: using username, password and captcha
	 * 							#2. RESOURCE: 					Execution of cURL for getting resource from 
	 * 															the webpage - like image, document etc.
	 * 							#3. ORDINARY: 					Execution of cURL for getting ordinary location 
	 * 															-> like text from specific link
	 * 							#4. RESOURCE_RESET: 			Will make additional set for ordinary execution
	 * 															to receive cookie id for downloading CAPTCHA. 
	 * 															*Some parts of the documentation might refer
	 * 															to specifying as RESOURCE extension, but no more
	 * 															true, after part analysis.
	 * 
	 * @param String $urlIn required.					Specifies path to be added for execution
	 * @param Integer $typeIn required.					Specifies type of the execution (from Step class constants,
	 * 													look at detailed description)
	 * @param String $refererIn required.				Specifies path, how did spider get to this link.
	 * @param string $saveResourceLocationIn optional.	Specifies location in server in which to save resource,
	 * 													to be specified for RESOURCE && RESOURCE_RESET steps.
	 * @param string $usernameIn optional.				Specifies username string, by which to do authentication.
	 * 													Is necessary for AUTHENTICATION step.
	 * @param string $passwordIn optional.				Specifies password string, by which to do authentication.
	 * 													Required for AUTHENTICATION step.
	 * @param string $captchaIn optional.				Specifies captcha string, by which to do authentication.
	 * 													Required for AUTHENTICATION step.
	 * @param string $websiteAuthIn optional.			Specifies website name string, for authentication checks. 
	 * @param string $categoryIn optional. 				Specifies category to be passed for step.
	 * 													*Is necessary at the moment for ORDINARY step, however for 
	 * 													{version 2.0} it is under review, as all the categories &&
	 * 													subcategories are collected before, and therefore there might
	 * 													be a solution for skipping this parameter. Now is required however! 													
	 * @param string $subcategoryIn optional.			Specifies subcategory to be passed for step. 
	 * 													*Same as above note.
	 * @return boolean False if limit of spider step has been reached
	 * @throws CollectorException If type specified is not in list of supported types OR there is specified $websiteAuthIn which is not existing
	 */
	public function addStep($urlIn, $typeIn, $refererIn, $websiteAuthIn="none", $saveResourceLocationIn = "", 
			$usernameIn = "", $passwordIn = "", $captchaIn = "", $categoryIn="", $subcategoryIn="")
	{
		//check that spider does accept more steps
		if($this->isReachedLimit())
		{
			return false;
		}
		//check for type in, if there is some unspecified type - exception raise.
		switch ($typeIn)
		{
			case Step::ORDINARY:
				break;
			case Step::AUTHENTICATE:
				break;
			case Step::RESOURCE:
				break;
			case Step::RESOURCE_RESET:
				break;
			default:
				throw new CollectorException("Collector has no such option to fetch as n: ".$typeIn);
		}
		//header array to be built
		$headers = array();
		// make additional option for $websiteAuthIn -> logic
		// if none still to run => then make ordinary headers
		// if there is no specified one -> throw an exception
		switch ($websiteAuthIn)
		{
			case "none":
				//do ordinary steps for building headers
				$headers = Headers::makeFakeHeaders($refererIn, $this->browserName);
				break;
			case WebsiteCollector::getWebsiteDomain():
				//do ordinary steps for $websiteDomain building headers
				$headers = WebsiteCollector::getHeaders($refererIn, $this->browserName, $typeIn);
				break;
			default:
				throw new CollectorException
					("There is no such website, as you mentioned, for building headers: ".$websiteAuthIn);
		}
		//make new step
		$step = new Step($urlIn, $typeIn, $headers);
		//check type firstly and make required steps in here
		switch ($typeIn)
		{
			case Step::ORDINARY:
				//here goes additional steps, for making sure that category and 
				//subcategory have been specified.
				$step->setCategory($categoryIn, $subcategoryIn);
				break;
			case Step::AUTHENTICATE:
				$step->setCaptcha($captchaIn);
				$step->setUserName($usernameIn);
				$step->setPassword($passwordIn);
				$step->setWebsite($websiteAuthIn);
				break;
			case Step::RESOURCE:
				$step->setResourceLocation($saveResourceLocationIn);
				//this part specifies, that cookie will be used same
				$step->setResetCookie(false);
				break;
			case Step::RESOURCE_RESET:
				$step->setResourceLocation($saveResourceLocationIn);
				//this part will make sure, that cookie will be re-set for
				//obtaining new identity
				$step->setResetCookie(true);
				break;
		}
		//add it to array of steps;
		$this->steps[]=$step;
		//makes sure that step has been added -> return true
		return true;
	}
	
	/**
	 * Check whether Authenticated has been entered - not working in logic yet {version 2.0}
	 * 
	 * @return True if captcha has been uncovered, else False
	 */
	public static function isAuthenticated()
	{
		return self::$isAuthenticated;
	}
	
	/**
	 * Setter for Authenticated property of the instance {version 2.0}
	 * 
	 * @param boolean $boolIn True, if captcha has been uncovered, False otherwise
	 * @throws CollectorException, if parameter is not of type boolean
	 */
	public static function setAuthenticated($boolIn)
	{
		if(!is_bool($boolIn))
		{
			throw  new CollectorException("Authenticated property can be only boolean value!");
		}
		self::$isAuthenticated=$boolIn;
	}
	
	
	/**
	 * This method is used to download to server directory Multimedia Internet Mail Extension (MIME)
	 * 
	 * @param String $urlIn required.				Specifies link to MIME (*images mostly)
	 * @param String $imageLocationIn required.		Specifies directory in server
	 * @param array $headersIn required.			Specifies specific headers of this step
	 * @param Boolean $isReset optional.			Specifies, whether cookie to be reset, or not
	 * @throws CollectorException thrown, if no such directory exists in server
	 */
	private function saveResource($urlIn, $imageLocationIn, $headersIn, $isReset=false)
	{
		//NOTE under review for whether is required $isReset here at all (since discovered
				//that ordinary execution is made for reset. {version 2.0}
		//check if normal execution is possible without error -> then do
		//also additionally if it is $isReset==false
		//if ! $isReset, as requires faster to download captcha here
		if(!$isReset)
		{
			try 
			{
				//fastest method of execution here
				$this->ordinaryExecute($urlIn, $headersIn, Spider::FASTEST_METHOD);
			}
			catch (CollectorException $e)
			{
				//if 404 error happens -> don't download picture - return false;
				//NOTE here, that this technique might mislead later, check it by programme
				return false;
			}
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $urlIn);
		//check if reset is required
		if($isReset)
		{
			//deprecated technique here, as ordinary exec does the thing workout {version 2.0}
		}
		//make use of cookies
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
		//open stream for writing and reading files in server
		$fp = fopen($imageLocationIn, "wb");
		curl_setopt($ch, CURLOPT_FILE, $fp);
		//make tor connection
		$ch = $this->setTor($ch);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headersIn);
		//execute curl
		curl_exec($ch);
		//check if error happened
		$error = curl_error($ch);
		if(!$error=="")
		{
			throw new CollectorException("Exception has been raised and is". 
					"following=".$error, 2);
		}
		//close connection stream
		curl_close($ch);
	}
	
	/**
	 * This method authenticates cURL to any provided authentication location (for each specific website different)
	 * 							Think to implement additional checks and methods for making spider authenticated.
	 * 
	 * @param String $url required.					Specifies location, to which user has to authenticate
	 * @param String[] $headersIn required.			Specifies specific headers to set in this step 
	 * @param String $captcha required.				Specifies captcha string, entered by the user
	 * @param String $username required.			Specifies username
	 * @param String $password required.			Specifies password
	 * @param String $websiteIn optional.			If given has predefined actions to check, 
	 * 												whether authentication has been successful. Should be constant
	 * 												from the WebsiteCollector class (or any other created). Think 
	 * 												to make interface for this later {version 2.0} and receiving this web as 
	 * 												parameter. 
	 * @return HTML output of the executed cURL [or FALSE if website has been specified 
	 * 														was not able to authenticat (if $websiteIn was given)]
	 */
	public function authenticateSpider($url, $headersIn, $captcha, $username, $password, $websiteIn="none")
	{
		//firstly make authenticated property as false
		self::setAuthenticated(false);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		//set browser
		curl_setopt($ch, CURLOPT_USERAGENT, $this->getBrowserName());
		//return output as a string instead of standard output
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		//redirect
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		//set cookie
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
		//make tor connection
		$ch = $this->setTor($ch);
		//set headers in here
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headersIn);
			//FIXME make sure, that take this information later from website itself, 
			//not predefined here. {version 2.0}
			$auth = "username=$username&password=$password&enterCaptcha=$captcha";
		//post data in here
		curl_setopt($ch, CURLOPT_POSTFIELDS, $auth);
		//now execute 
		$output = curl_exec($ch);
		curl_close($ch);
		
		//check authentication, if 'none' it will be assumed that it is authenticated (not in {version 2.0}) 
		if(!strcmp($websiteIn,"none")==0)
		{
			switch ($websiteIn)
			{
				case WebsiteCollector::getWebsiteDomain():
					if(WebsiteCollector::checkAuthentication($output))
					{
						self::setAuthenticated(true);
					}
					else 
					{
						self::setAuthenticated(false);
						return false;
					}
					break;
				default:
					throw new CollectorException("Authentication Exception: No such website
													technique is found! Re-check!");
			}
		}
		else 
		{
			//set as authenticated
			$this->setAuthenticated(true);
		}
		return $output;
	}
	
	/**
	 * Reset step values, therefore to start from beginning specified
	 * to be used, maybe, for more sophisticated logic later, like re-login
	 * and make some brute-force scanning for unmet steps (like skipped) etc {version 2.0}
	 */
	public function setResetSteps()
	{
		//if ok make set parameter
		$this->resetStep=0;
	}
	
	/**
	 * Method to randomise steps taken {version 2.0}
	 * 		To make step array completely mess - how user is going from product to product,
	 * 		therefore admin would not be able to see the traffic by this approach. {version 2.0}
	 */
	public function shuffleSteps()
	{
		//reset steps
		$this->setResetSteps();
		//now change all the data to be collected
		shuffle($this->getSteps()); 
	}

	/**
	 * Summary: Given method is for making array of models collected -> making a steps for spider to perform 
	 * 
	 * Detailed: First parameter receiving, is collection of Model objects, specifying, what is required for 
	 * 			 spider to perform. Model is holding necessary basic information, like model itself, category &
	 * 			 subcategory values.
	 * 
	 * @param array $modelsIn required.				Models to be performed by spider
	 * @param boolean $collectCategoryIn optional. 	Default value is SKIP_COLLECT_CATEGORY, which means,
	 * 											   	that steps to be collected from empty array. If, however
	 * 											   	PRE_COLLECT constant is specified -> collecting of steps will 
	 * 											   	continue from given already existing $steps property of the spider. 
	 */
	public function collectSteps($modelsIn, $collectCategoryIn=self::SKIP_COLLECT_CATEGORY)
	{
		//get website domain in here
		$websiteDomain = WebsiteCollector::getWebsiteDomain();
		//for each model add step?
		//and as a referer add previous model
		if($collectCategoryIn==self::SKIP_COLLECT_CATEGORY)
		{
			for($i=0; $i<count($modelsIn);$i++)
			{
				//output check variable
				$success=null;
				if($i==0)
				{
					//like a refresh first step in
					$success=$this->addStep("http://".$websiteDomain.$modelsIn[$i]->getModel(),
					Step::ORDINARY, "http://".$websiteDomain.$modelsIn[$i]->getModel(),$websiteDomain);
				}
				else
				{
					//like previous step from collection
					$success = $this->addStep("http://".$websiteDomain.$modelsIn[$i]->getModel(),
						Step::ORDINARY, "http://".$websiteDomain.$modelsIn[$i-1]->getModel(),$websiteDomain,
						null, null, null, null, $modelsIn[$i]->getCategory(), $modelsIn[$i]->getSubCategory());
				}
				//if there is limit - then finish execution
				if(!$success)
				{
					//exit loop
					break;
				}
			}
		}
		else 
		{
			//bug fix -> there was a problem with loop dynamic evaluation -> as steps would grow over time
			//make it static here
			//FIXME critical here, might not work. Was count($this->steps)+count($modelsIn)-1; check it 
			$numOfSteps = count($this->steps)+count($modelsIn);
			//for each step
			for($i=count($this->steps); $i<$numOfSteps; $i++)
			{
				//simple check output
				$success=null;
				if($i==0)
				{
					//like a refresh first run
					$success=$this->addStep("http://".$websiteDomain.$modelsIn[$i]->getModel(),
					Step::ORDINARY, "http://".$websiteDomain.$modelsIn[$i]->getModel(),$websiteDomain,
					null, null, null, null, $modelsIn[$i]->getCategory(), $modelsIn[$i]->getSubCategory());
				}
				else 
				{
					//like previous step
					$success = $this->addStep("http://".$websiteDomain.$modelsIn[$i]->getModel(),
						Step::ORDINARY, "http://".$websiteDomain.$modelsIn[$i-1]->getModel(),$websiteDomain,
						null, null, null, null, $modelsIn[$i]->getCategory(), $modelsIn[$i]->getSubCategory());
				}
				//exit loop if limit is set
				if(!$success)
				{
					//exit loop
					break;
				}
			}
		}
	}
	
	/**
	 * Method for make spider perform randomly slower
	 * 
	 * @param int[] $sleepIn required.		Representing minimum and maximum interval of sleep {min:max}
	 * @throws CollectorException if more than 2 values in array found; if either parameter is not numeric; or 
	 * 							  if second parameter is less or equals to first parameter (for quickest function
	 * 							  use FASTEST_METHOD pre-defined constant)
	 */
	private function sleepForWhile($sleepIn)
	{
		if($sleepIn==self::FASTEST_METHOD)
		{
			return true;			//quick skip of sleep 
		}
		//check now here that sleep is actually two value array
		if(count($sleepIn)!=2)
		{
			throw new CollectorException("Array \$sleepIn can accept only two int values -> MIN & MAX sleep times");
		}
		//check that two properties are of type integer
		if(!is_numeric($sleepIn[0])||!is_numeric($sleepIn[1]))
		{
			throw new CollectorException("Both sleep values must be of type integer. Please make sure!");
		}
		
		//check that first value is smaller than second one
		if($sleepIn[0]>$sleepIn[1] || $sleepIn[0]==$sleepIn[1])
		{
			throw new CollectorException("First sleep time must be smaller than second sleep time. Please make sure to fix it");
		}
		//here is a method for making program sleep
		$sleepTime= rand($sleepIn[0], $sleepIn[1]);
		sleep($sleepTime);
	}
	
	/**
	 * Setter for array of steps to be performed by spider (in case
	 *  of failure at some point it will speed up the process)
	 * @param Step[] $stepsIn
	 */
	public function setSteps($stepsIn)
	{
		//set parameter steps here
		$this->steps = $stepsIn;
	}

	//TODO NOTE (s) here:
	/*
	 * overall notes are going here:
	 * #1. Product description HTML isn't working for each product, therefore it re
	 *     quires to skip, if we can't load it. We can fix this issue later, by making 
	 *     transparent check. 
	 * #2. Sessions seems to expire, as not allowing to go through and disconnect all of the 
	 * 	   connections at one. Make rotation, therefore to overcome this problem somehow.
	 * #3. Also, there was a problem (like mentioned above) for getting HTML product, but for 
	 * 		counterfeits there is no possible to execute some links, have tried to put 
	 * 		HTTPOPT_FOLLOWLOCATION, but seems like might not work at all. Anyways check this later.
	 * FOR NOW IT'S IT!:) 
	 * #4. Captcha is using now different technique to be able to download. It was required to reset
	 * 		cookie within ordinary execution, not within captcha download itself. Make sure to document
	 * 		this issue later. 
	 * #5. Next step would be to divide array of steps into specified parts, for quick scan (let's say in 10 parts)
	 */
}