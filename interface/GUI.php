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
 * This class to be linking logic to interface: parameters to be taken from CLI (or at later versions of programme
 * GUI) parameter input and transfering logic to functional part libraries. 
 * 
 * @author Germans Zaharovs
 * @version 1.0
 */
class GUI
{
	//TODO think at later stage to make some proof, that user has been authenticated {version 2.0}
	/**
	 * Method for getting Collector capturing CAPTCHA from specified website
	 * 
	 * @param string $cookieIn requried.				Represents location, where cookie to be placed
	 * @param string $proxyIn required.					Represents proxy settings
	 * @param string $torDirIn required.				Directory of the TOR browser, which to be started for collector
	 * @param string $captchaURL required. 				Link to captcha URL for required website
	 * @param string $refererIn required.				Link of main page, which to be holding authentication (as CAPTCHA referer)
	 * @param string $savingLocationIn required.		Directory at which CAPTCHA image will be saved
	 * @param string $websiteToBeScannedIn requried.	Domain of the website to be scanned
	 * @throws CollectorException, if there was a problem of downloading captcha
	 */
	public static function getCaptcha($cookieIn, $proxyIn, $torDirIn, $captchaURL, $refererIn, $savingLocationIn, $websiteToBeScannedIn)
	{

		$spider = new Spider($cookieIn);
		$spider->setProxy($proxyIn);
		//TODO at the moment under development {version 2.0}
		$spider->startTor($torDirIn);
		//set website to be scanned
		WebsiteCollector::changeWebsiteDomain($websiteToBeScannedIn);
		//set step to be performed (for getting id of the cookie)
		$spider->addStep($refererIn, Step::RESOURCE_RESET, "http://".WebsiteCollector::$websiteDomain."/", WebsiteCollector::$websiteDomain);
		//get captcha image
		$spider->addStep($captchaURL, Step::RESOURCE, $refererIn, WebsiteCollector::$websiteDomain,
				$savingLocationIn);
		//get cookie
		while($spider->isStepsUnvisited())
		{
			$spider->startFetchData();
		}
	}
	
	/**
	 * Method for authenticating Collector before making default capturing 
	 * 
	 * @param string $cookieIn required.				Represents location, where cookie to be placed
	 * @param string $proxyIn required.					Represents proxy settings
	 * @param string $torDirIn required.				Directory of the TOR browser, which to be started for collector
	 * @param string $authenticateURL required.			Link to do authentication for given website
	 * @param string $refererIn required.				Link of main page, which to be holding authentication
	 * @param string $userNameIn required.				Username of the user
	 * @param string $passwordIn required.				Password of the user
	 * @param string $captchaIn required.				Captcha, uncovered from getCaptcha() method
	 * @param string $websiteToBeScannedIn required.	Domain of the website to be scanned
	 * @param string $browserNameIn optional. 			Specifies, 
	 * @throws CollectorException if there was a problem with authentication
	 * @return bool True if performed authentication successfuly, otherwise False
	 */
	public static function authenticateUser($cookieIn, $proxyIn, $torDirIn, $authenticateURL, $refererIn, $userNameIn, $passwordIn, 
																$captchaIn, $websiteToBeScannedIn, $browserNameIn="Mozilla/5.0 (Windows NT 6.1; rv:31.0) Gecko/20100101 Firefox/31.0")
	{
		$spider = new Spider($cookieIn, $browserNameIn);
		$spider->setProxy($proxyIn);
		//*********************
		//{version 2.0}
		$spider->startTor($torDirIn);
		//******************
		//set website
		WebsiteCollector::changeWebsiteDomain($websiteToBeScannedIn);
		//authentication step in here
		$spider->addStep($authenticateURL, Step::AUTHENTICATE,
		$refererIn, WebsiteCollector::$websiteDomain, null, $userNameIn, $passwordIn, $captchaIn);
		//start execution
		$output = $spider->startFetchData();
		//check if spider logged in -> return true
		//else -> false
		if($output)
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Method for performing required collection of categories specified, for limited number of steps (by spider)
	 * 												This method would be mostly used for testing purposes as a bit quicker.
	 * 							However, at the moment programme is built in such a way, that it requires to precollect all the 
	 * 							categories and subCategories before it can be processed, in next version it will be in far more	
	 * 							deep analyzis for performance improvement {version 2.0}
	 *
	 * @param string $cookieIn required.				Represents location, where cookie to be placed
	 * @param String[] $categoriesIn required			Categories required to be scanned by Collector 
	 * @param string $proxyIn required.					Represents proxy settings, in format "127.0.0.1:9050/"
	 * @param string $torDirIn required.				Directory of the TOR browser, which to be started for collector
	 * @param string $resourceLocationIn required.		Specifies directory, where resources captured from given website will be saved
	 * @param string $websiteToBeScannedIn required.	Domain of the website to be scanned
	 * @param int $numOfCapturesIn required				Number of steps to be performed by Collector
	 * @param int[] $sleepIn optional. 					Represents waiting time for spider between executions
	 * @return bool True, if collecting was successful, otherwise false
	 */
	public static function performCollectingLim($cookieIn, $categoriesIn, $proxyIn, $torDirIn, $torBrowserIn, 
															$resourceLocationIn, $websiteToBeScannedIn, $numOfCapturesIn, $sleepIn=array(1,10))
	{
		$performed=WebsiteCollector::collectData($cookieIn, $torBrowserIn, $categoriesIn, $proxyIn,
				$torDirIn, $resourceLocationIn, $websiteToBeScannedIn, $numOfCapturesIn);
		if($performed)
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Method for performing required collection of categories specified
	 *
	 * @param string $cookieIn required.				Represents location, where cookie to be placed
	 * @param String[] $categoriesIn required.			Categories required to be scanned by Collector
	 * @param String $proxyIn required.					Represents proxy settings, in format "127.0.0.0:9150/"
	 * @param string $torDirIn required.				Directory of the TOR browser, which to be started for collector
	 * @param string $resourceLocationIn required.  	Specifies directory, where resources captured from given website will be saved
	 * @param string $websiteToBeScannedIn required.	Domain of the website to be scanned
	 * @return bool True, if collecting was successful, otherwise false
	 */
	public static function performCollectingMax($cookieIn, $categoriesIn, $proxyIn, $torDirIn, $torBrowserIn, 
																	$resourceLocationIn, $websiteToBeScannedIn, $sleepIn=array(1,10))
	{
						
		$performed=WebsiteCollector::collectData($cookieIn, $torBrowserIn, $categoriesIn, $proxyIn,
				$torDirIn, $resourceLocationIn, $websiteToBeScannedIn, Spider::UNLIMITED, $sleepIn);
		if($performed)
		{
			return true;
		}
		return false;
	}
}