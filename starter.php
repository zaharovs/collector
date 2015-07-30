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
 * Given class works closely with CLI for determining programmes routine.
 * 
 * @author Germans Zaharovs
 * @version 1.0
 */
class Run
{
	/**
	 * Identification, that run is for authentication
	 * @var boolean
	 */
	public static $isAuthentication 	= 	false;
	
	/**
	 * Identification, that run is for captcha
	 * @var boolean
	 */
	public static $isCaptcha 			= 	false;
	
	/**
	 * Identification, that run is for scanning
	 * @var boolean
	 */
	public static $isScanning			= 	false;
	
	/**
	 * Method, for checking, which run is to be performed. 
	 * 
	 * @param boolean $isAuthentication requried.		True, if run is for authentication purpose
	 * @param boolean $isCaptcha required.				True, if run is for capturing Captcha purpose
	 * @param boolean $isScanning reqiered.				True, if run is for scanning specified products
	 * @throws CollectorException If there is more than one run selected, or none run selected
	 */
	public static function isOneSelected($isAuthentication, $isCaptcha, $isScanning)
	{
		//make rotate method for checking, that only one to be true, if that statement false -> raise an exception
		$arrayRotate = array($isAuthentication, $isCaptcha, $isScanning);
		$onlyOne = false;
		$run = -999;
		for($i=0; $i<count($arrayRotate); $i++)
		{
			//now check, if $onlyOne is already true, however other will be true => raise an Exception
			if($onlyOne == true)
			{
				if($arrayRotate[$i]===true)
				{
					//raise an exception
					throw new CollectorException("Only one run must be undertaken at one single moment for Collector");
				}
			}
			if($onlyOne == false)
			{
				if($arrayRotate[$i]===true)
				{
					$onlyOne = true;
					//keep now track for selecting proper statement for run
					$run=$i;
				}
			}
			
		}
		//now here we know, that one run must be selected, therefore if $onlyOne is false -> raise an exception
		if($onlyOne==false)
		{
			throw new CollectorException("At least one run must be specified for running Collector");
		}
		//else we know here, that run was made, therefore now we need to make sure that right run is made true
		switch ($run)
		{
			case 0:
				HelperStaticChanger::changeStaticProperty("zaharovs\collector\Run", "isAuthentication", true);
				break;
			case 1:
				HelperStaticChanger::changeStaticProperty("zaharovs\collector\Run", "isCaptcha", true);
				break;
			case 2: 
				HelperStaticChanger::changeStaticProperty("zaharovs\collector\Run", "isScanning", true);
				break;
		}
		
	}
	
	/**
	 * Method for running authentication for Collector
	 * 
	 * @param \Console_CommandLine_Result $resultIn required.		Output of the CLI CommandLine PEAR package
	 * @return True, if authenticated, else false
	 */
	public static function runAuthentication(\Console_CommandLine_Result $resultIn)
	{
		//check all necessary parameters in here
		$website = $resultIn->options['websiteScanning'];
		if($website==false)
		{
			throw new CollectorException("Website domain must be specified for Collector");
		}
		$link = $resultIn->options['link'];
		if($link==false)
		{
			throw new CollectorException("Location must be specified");
		}
		//check referer
		$referer = $resultIn->options['referer'];
		if($referer==false)
		{
			throw new CollectorException("Referer must be specified to download captcha");
		}
		//check username
		$userName = $resultIn->options['username'];
		if($userName==false)
		{
			throw new CollectorException("Username must be specified for authentication");
		}
		//check password
		$password = $resultIn->options['password'];
		if($password==false)
		{
			throw new CollectorException("Password must be specified for authentication");
		}
		//check captcha
		$captcha = $resultIn->options['captchaValue'];
		if($captcha==false)
		{
			throw new CollectorException("Capthca must be specified for authentication");
		}
		
		//authenticate here
		GUI::authenticateUser($resultIn->options['cookie'], $resultIn->options['proxy'], $resultIn->options['torDir'], $link, $referer, $userName, $password, $captcha, $website,
					$resultIn->options['browserName']);
		
		//if authenticated return true, else false
		return true;
	}
	
	/**
	 * Method for downloading captcha of the website
	 * 
	 * @param \Console_CommandLine_Result $resultIn required.		Output of the CLI CommandLine PEAR package
	 * @throws CollectorException if any required parameter for captcha is not specified
	 */
	public static function runCaptcha(\Console_CommandLine_Result $resultIn)
	{
		$website = $resultIn->options['websiteScanning'];
		if($website==false)
		{
			throw new CollectorException("Website domain must be specified for Collector");
		}
		$resourceLocation = $resultIn->options['resourceLocation'];
		if($resourceLocation==false)
		{
			throw new CollectorException("Resource locations needs to be specified for captcha saving");
		}
		$link = $resultIn->options['link'];
		if($link==false)
		{
			throw new CollectorException("Location must be specified");
		}
		//check referer
		$referer = $resultIn->options['referer'];
		if($referer==false)
		{
			throw new CollectorException("Referer must be specified to download captcha");
		}
		
		//make download of captcha in here
		GUI::getCaptcha($resultIn->options['cookie'], 
			$resultIn->options['proxy'], $resultIn->options['browserName'], $resultIn->options['link'], 
			$resultIn->options['referer'],  $resultIn->options['resourceLocation'], $resultIn->options['websiteScanning']);
	}
	
	/**
	 * Method for scanning all of the items from categories specified
	 * 
	 * @param \Console_CommandLine_Result $resultIn required.		Output of cmd/terminal results
	 * @param boolean $isReseting optional.							Specifies, whether spider has been restarted, or not
	 * @throws CollectorException for various reasons -> mostly disconnecting one, maybe make a code for it, 
	 * 			as this is the only one we care to restart spider -> if others then 
	 */
	public static function runScanning(\Console_CommandLine_Result $resultIn, $isReseting = false)
	{
		//think what we need,
		$website = $resultIn->options['websiteScanning'];
		if($website==false)
		{
			throw new CollectorException("Website domain must be specified for Collector");
		}
		//make sure to make logic for category collection
		$categories = $resultIn->options['scanCategories'];
		//explode categories by comma separation
		//space separation must be by %20 symbol
		$categories = explode(",", $categories);
		$newCategories = array();
		//now make calculations of each category to glue it with space
		foreach ($categories as $category)
		{
			$tempCat = explode("%20", $category);
			//and now glue it
			$tempCat = implode(" ", $tempCat);
			//and add it to array
			$newCategories [] = $tempCat;
		}
		
		$resourceLocation = $resultIn->options['resourceLocation'];
		if($resourceLocation==false)
		{
			throw new CollectorException("Resource locations needs to be specified for all resources collected saving");
		}
		//check limit in here
		$limit = $resultIn->options['limit'];
		if($limit==Spider::UNLIMITED)
		{
			//adiitional checks
			if($isReseting==true)
			{
				$spider = new Spider($resultIn->options['cookie'], $resultIn->options['browserName'], 
						Spider::UNLIMITED);
				//and now copy collected steps
				$spider->setSteps(Spider::$collectedSteps);
				//restart spider here
				WebsiteCollector::collectProducts($resultIn->options['resourceLocationIn'], $spider, $resultIn->options['sleepTime']);
				//in case of success clean all collected steps {version 2.0}
				HelperStaticChanger::changeStaticProperty("zaharovs\collector\Spider", "collectedSteps", array());
			}
			else 
			{
				//then run unlimited spider
				GUI::performCollectingMax($resultIn->options['cookie'], $newCategories, $resultIn->options['proxy'], 
													$resultIn->options['torDirectory'], $resultIn->options['browserName'], 
																	$resultIn->options['resourceLocation'], $website, $resultIn->options['sleepTime']);
			}
		}
		else
		{
			//we know that limit exist then, and we have to run limited spider
			//check firstly, that limit is numeric -> else raise an CollectorException
			if(!is_numeric($limit))
			{
				throw new CollectorException("Limit for collector specs must be numeric");
			}
			//convert it
			$limit = (int) $limit;
			
			//check if reset or not
			if($isReseting)
			{
				//FIXME -> at the moment can't find sleep time request, therefore later under review
				$spider = new Spider($resultIn->options['cookie'],$resultIn->options['broserName'], $limit);
				//and now copy collected steps
				$spider->setSteps(Spider::$collectedSteps);
				//restart spider here
				WebsiteCollector::collectProducts($resultIn->options['resourceLocationIn'], $spider, $resultIn->options['sleepTime']);
				//in case of success clean all collected steps
				HelperStaticChanger::changeStaticProperty("zaharovs\collector\Spider", "collectedSteps", array());
			}
			else 
			{
				//then run unlimited spider
				GUI::performCollectingLim($resultIn->options['cookie'], $newCategories, $resultIn->options['proxy'],
													$resultIn->options['torDirectory'], $resultIn->options['broserName'],
															$resultIn->options['resourceLocation'], $website, $limit, $resultIn->options['sleepTime']);
			}
		}
	}
}


//required for command line
	//make command line parser
	$parser = new \Console_CommandLine(array(
			'description'=>'CLI interface of the Collector',
			'version'=>'1.0'
	));
	
	
	//authentication specifics
	$parser->addOption('username',
		array(
		'short_name' 	=> '-u',
		'long_name'		=> '--username',
		'description'	=> 'Username for authentication to the website',
		'action'		=> 'StoreString',
		'default'		=> false
		)
	);
	
	$parser->addOption('password',
		array(
		'long_name'		=> '--password',		
		'description'	=> 'Password for authentication to the website',
		'action'		=> 'Password'
	));
	
	$parser->addOption('captchaValue',
		array(
		'long_name'		=> '--captchaN',
		'description'	=> 'Captcha number to be entered:',
		'action'		=> 'Password'
		)
	);
	
	//scanning specifics
	$parser->addOption('scanCategories',
		array(
		'long_name'		=> '--scan',
		'desctiption'	=> 'Categories, specified to be scanned from website',
		'action'		=> 'StoreString',
		'default'		=> false
		)
	);
	
	//add an option to store a proxy
	//proxy settings option
	$parser->addOption('proxy',
		array(
		'short_name'	=> '-p',
		'long_name'		=> '--proxy',
		'description'	=> 'Proxy settings of the Collector',
		'action'		=> 'StoreString',
		'default'		=> '127.0.0.1:9150/',
		'help_name'		=> 'proxy_help'
		)
	);
	
	//add an option to store a proxy
	$parser->addOption('torDirectory',
	array(
		'short_name'	=> '-t',
		'long_name'		=> '--torDirectory',
		'description'	=> 'TOR browser location in system',
		'action'		=> 'StoreString',
		'default'		=> 'someTorLocation'
		)
	);
	 
	$parser->addOption('cookie',
	array(
		'short_name'	=> '-c',
		'long_name'		=> '--cookie',
		'description'	=> 'Specify location and cookie name to be set',
		'action'		=> 'StoreString',
		'default'		=> '\home\apache\cookie.txt'
	)
	);
	
	//force user to enter given info here -> however share between interface if pipe is used?
	$parser->addOption('resourceLocation',
			array(
			'short_name'	=> '-r',
			'long_name'		=> '--resource',
			'description'	=> 'Specify location where all resources to be saved',
			'action'		=> 'StoreString',
			'default'		=> false
	)
	);
	
	$parser->addOption('websiteScanning',
			array(
			'short_name'	=> '-w',
			'long_name'		=> '--website',
			'description'	=> 'website to be scanned',
			'action'		=> 'StoreString',
			'default'		=> false
	)
	);
	
	$parser->addOption('sleepTime',
			array(
			'short_name'	=> '-s',
			'long_name'		=> '--sleep',
			'description'	=> 'time array, to be entered as seconds, first min time to be slept, second max time',
			'action'		=> 'StoreArray',
			'default'		=>  array(1,10)
			)
	);
	
	$parser->addOption('limit',
			array(
			'short_name'	=> '-l',
			'long_name'		=> '--limit',
			'description'	=> 'limit of the collector, of how many items to be scanned',
			'default'		=> Spider::UNLIMITED
					
			));
	
	$parser->addOption('referer',
			array(
					'short_name'	=> '-r',
					'long_name'		=> '--referer',
					'description'	=> 'referer of the step to be undertaken by Collector',
					'default'		=> false
			));
	
	$parser->addOption('link',
			array(
					'long_name'		=> '--link',
					'description'	=> 'link, by which Collector must make an action',
					'default'		=> false
			));
	
	$parser->addOption('authentication',
			array(
			'short_name'	=> '-a',
			'long_name'		=> '--authenticate',
			'description'	=> 'authenticate collector to website',
			'action'		=> 'StoreTrue',
			'default'		=> false
	));
	
	$parser->addOption('itemScan',
			array(
					'short_name'	=> '-i',
					'long_name'		=> '--item_scan',
					'description'	=> 'scan unlimited items specified',
					'action'		=> 'StoreTrue',
					'default'		=> false
			));
			
	$parser->addOption('limited_scan',
			array(
					'long_name'		=> '--limited_scan',
					'description'	=> 'scan limited items specified',
					'action'		=> 'StoreTrue'
			));
	$parser->addOption('captcha',
			array(
					'long_name'		=> '--captcha',
					'description'	=> 'make step for captcha uncovering',
					'action'		=> 'StoreTrue',
					'default'		=> false
			)
	);
	
	$parser->addOption("browserName",
	array(
			'long_name'		=> '--bName',
			'description'	=> 'Browser name for collector',
			'action'		=> 'StoreString',
			'default'		=> 'Mozilla/5.0 (Windows NT 6.1; rv:31.0) Gecko/20100101 Firefox/31.0'
	)			
			
);

//start programme here
try
{
	//start parsing data captured from user
	$result = $parser->parse();
	//now check here, that only one and only one run is selected
	//check mandatory fields, which to be entered in any case to program run successfully
	$runningCaptcha = $result->options['captcha'];
	$runningAuthentication = $result->options['authentication'];
	$runningScan = $result->options['itemScan'];
	
	//check which one to be run
	Run::isOneSelected($runningAuthentication, $runningCaptcha,  $runningScan);
	
	switch (true)
	{
		case Run::$isAuthentication:
			//make authentication method in here
			$success = Run::runAuthentication($result);
			//make sure to reset authentication value back to false, once finished
			HelperStaticChanger::changeStaticProperty("zaharovs\collector\Run", "isAuthentication", false);
			//make some greetings message in here
			//make if statement
			if($success)
			{
				echo "\nAuthentication has been successfully completed\n";
			}
			else
			{
				echo "\nThere was a problem with authentication. Re-load captcha and try again!";
			}
			break;
		case Run::$isCaptcha:
			//make captcha method in here
			Run::runCaptcha($result);
			//make sure to reset isCaptcha once finished
			HelperStaticChanger::changeStaticProperty("zaharovs\collector\Run", "isCaptcha", false);
			//make some greetings message in here
			echo "\nThe Captcha image has been successfully downloaded\n";
			break;
		case Run::$isScanning:
			//make scanning method in here
			try 
			{
				//don't forget about measuring performance here as well
				GeneralPerformance::setCurrentTime();
				//make scan
				helper_run($result);
				//once finished ouput message with all of the performance
				echo "\nScanning has been finished in: ".gmdate('H:i:s',GeneralPerformance::calculateTime())." per: ".GeneralPerformance::$scannedModels. " scanned models";
				//don't forget to catch exceptions in here and re-start spider
			}
			catch (\zaharovs\collector\CollectorException $e)
			{
				//restart scanning method
				//? tried to restart it later, however make sure to notify user of this
				echo $e->getMessage();
			}
				
			//make sure to reset isScanning once finished in here
			HelperStaticChanger::changeStaticProperty("zaharovs\collector\Run", "isScanning", false);
			break;
		//maybe raise an exception, however now looks redundant
	}
}
catch (\zaharovs\collector\CollectorException $e)
{
	$parser->displayError($e->getMessage());
}
catch (\Exception $e)
{
	$parser->displayError($e->getMessage());
}


/**
 * Scanning method goes in here
 * 
 * @param \Console_CommandLine_Result $resultIn required.
 * @throws CollectorException if error code isn't 2 (not because of curl exec)
 */
function scan_categories(\Console_CommandLine_Result $resultIn, $errN=0)
{
	try 
	{
		if($errN = 0 || $errN = 3)
		{
			//make sure to try execute already collected steps ef exist in here
			if(count(Spider::$collectedSteps)>0)
			{
				//add steps to spider, and execute it
				$spider = new Spider($resultIn->options['cookie']);
				$spider->setProxy($resultIn->options['proxy']);
				//add steps
				$spider->setSteps(Spider::$collectedSteps);
				//and now make sure to set steps to nothing, therefore no same collection
				HelperStaticChanger::changeStaticProperty("zaharovs\collector\Spider", "collectedSteps", array());
				//start spider in here
				$spider->startFetchData();
			}
			else 
			{
				//make additional check, where to start -> if data was collected, restart with that data
				Run::runScanning($resultIn);
			}
		}
// 		elseif($errN ==3)
// 		{
// 			//at the moment needs to be rebuilt
// 		}
	}
	catch (\zaharovs\collector\CollectorException $e)
	{
		//FIXME rethink here later {version 2.0}
		//check that exception is num 2 if would like to restart (for execution exceptions)
		if($e->getCode()==2)
		{	
			//check if the code for exception is 2, else raise an error
			//at later stage
			//check performance in here, as it will be reset again
			echo "\nCurrent time of the execution is: ".gmdate('H:i:s',GeneralPerformance::calculateTime())." per partly scanned of :"
										.GeneralPerformance::$scannedModels. " models";
			echo "\nexception happened. Details are: {$e->getMessage()}\n";
			
			//make sure to try execute step for a 60 times, and see if it helps.
			if(GeneralPerformance::$numOfExceptions<60)
			{
				//make some sleep
				GeneralPerformance::waitForResponse();
				//restart else throw again in here
				helper_run($resultIn);
			}			
			
		}
		elseif($e->getCode()==3)
		{
			//REDUNDANT, BUT ANYWAYS CHECK IT OUT
			//check if the code for exception is 2, else raise an error
			//at later stage
			//check performance in here, as it will be reset again
			echo "\nCurrent time of the execution is: ".gmdate('H:i:s',GeneralPerformance::calculateTime())." per partly scanned of :"
					.GeneralPerformance::$scannedModels. " models";
					echo "\nexception happened. Details are: {$e->getMessage()}\n";
			
			GeneralPerformance::waitForResponse();
			//restart else throw again in here
			helper_run($resultIn, 3);
		}
		else 
		{
			//at the moment restart anyways
			GeneralPerformance::waitForResponse();
			helper_run($resultIn);
		}
	}
}

/**
 * Helper method for keep running, even if exception is raised
 * @param \Console_CommandLine_Result $resultIn
 */
function helper_run (\Console_CommandLine_Result $resultIn, $errNo=0)
{
	scan_categories($resultIn, $errNo);
}