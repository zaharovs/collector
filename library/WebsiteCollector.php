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

/**
 * This class to be used for website's logic (would require to have specific for each one)
 * 
 * @author Germans Zaharovs
 * @version 1.0
 */
class WebsiteCollector
{
	/**
	 * Constant indicating website's dependency
	 * @var String
	 */
	public static $websiteDomain;
	
	/**
	 * Variable, to help keep track of parrent category
	 * @var string
	 */
	public static $parentCategory=array();
	
	/**
	 * Constant indicating, that subcategory names as output is are needed
	 * @var Integer
	 */
	const SUB_NAMES  = 0;
	
	/**
	 * Constant indicating, that subcategory models as output are needed 
	 * @var Integer
	 */
	const SUB_MODELS = 1;
	
	/**
	 * Ordinary headers information of the spider for specific website
	 * @var Header property array
	 */
	public static $headers=null;
	
	
	/**
	 * Collection of category, and subcategory structure array of current choice of 
	 * the Category collecting.
	 * @var array
	 */
	public static $collectedCategories = array();
	
	/**
	 * Getter method for website domain
	 * @return string WebsiteDomain
	 */
	public static function getWebsiteDomain()
	{
		$websiteDomain = self::$websiteDomain;
		return $websiteDomain;
	}
	
	
	/**
	 * Method to change domain of the website 
	 * 
	 * @param string $websiteDomainIn Domain of the website to be collected data from (without any http:// or any / signs)
	 */
	public static function changeWebsiteDomain($websiteDomainIn)
	{
		HelperStaticChanger::changeStaticProperty(__CLASS__, "websiteDomain", $websiteDomainIn);
	}
	
	
	/**
	 * Helper method for building specific headers for WebsiteCollector class
	 * 
	 * @return Headers complete website's specific instance
	 */
	protected static function buildHeaders()
	{
		//build all required information
		$header = new Headers();
		$header->setAcceptEncoding('Accept-Encoding: gzip, deflate');
		$header->setAcceptLanguage('Accept-Language: en-us,en;q=0.5');
		$header->setConnection('Connection: keep-alive');
		$header->setHost('Host: '.self::$websiteDomain);
		return $header;
	}
	
	/**
	 * Helper method for making final step of creating headers -> make them array 
	 * (as only this will be allowed for CURL)
	 */
	protected static function finalizeBuildHeaders()
	{
		return self::$headers->buildHeaders();
	}
	
	/**
	 * Method to get specific headers for website (as for image and text would have different formats;
	 * 		resources are taken from Step's logic
	 * 
	 * @param string $refererIn required. 		Represent a referer to be in headers 
	 * @param string $userAgentIn required. 	Represents a browser string to be appear at server
	 * @param string $typeIn required.			Constant of the WebsiteCollector [check documentation of constants].
	 * @return array of headers, representing specified website website
	**/
	public static function getHeaders($refererIn, $userAgentIn, $typeIn)
	{
		//make temporary holding variable for headers
		$tempHeaders = self::buildHeaders();
	
		switch ($typeIn)
		{
			//set logic for resource
			case Step::RESOURCE:
				//set three properties -> rebuild and get back
				$tempHeaders->setReferer("Referer: ".$refererIn);
				$tempHeaders->setUserAgent("User-Agent: ".$userAgentIn);
				//image representation
				$tempHeaders->setAccept('Accept: image/png,image/*;q=0.8,*/*;q=0.5');
				//build headers final
				HelperStaticChanger::changeStaticProperty("zaharovs\collector\WebsiteCollector", "headers", $tempHeaders);
				HelperStaticChanger::changeStaticProperty("zaharovs\collector\WebsiteCollector", "headers", self::finalizeBuildHeaders());
				break;
				//all the other properties (text based properties)
			default:
				//set three properties -> rebuild and get back
				$tempHeaders->setReferer("Referer: ".$refererIn);
				$tempHeaders->setUserAgent("User-Agent: ".$userAgentIn);
				//text representation
				$tempHeaders->setAccept('Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');
				//build final
				HelperStaticChanger::changeStaticProperty("zaharovs\collector\WebsiteCollector", "headers", $tempHeaders);
				HelperStaticChanger::changeStaticProperty("zaharovs\collector\WebsiteCollector", "headers", self::finalizeBuildHeaders());	
				break;
		}
		//return headers
		return self::$headers;	
	}
	
	/**
	 * Method to check, whether authentication in agora website has been successful.
	 * 					This method is website specific.
	 * 
	 * @param String $outputIn required. 	HTML output of the execution parameter
	 * @return boolean True if authentication has been successful, False otherwise
	 */
	public static function checkAuthentication($outputIn)
	{
		$tdTags = ParsingTechniques::getAllTags($outputIn, 
				"<td", "/td>");
		foreach ($tdTags as $tag)
		{
			$attr = ParsingTechniques::getAttribute($tag, "class");
			if(strcasecmp("login-message", trim($attr))==0)
			{
				return false;
			}	
		}
		return true;
	}
	
	/**
	 * Method to get from main webpage left side menu.
	 * 
	 * @param String $htmlIn required. 		Main menu to be collected from specific website.
	 * @return Array of the left-menu items from website
	 */
	protected static function collectMenu($htmlIn)
	{
		$leftMenuArr = array();
		//get all div tags in html
		$divTags = ParsingTechniques::getAllTags($htmlIn, "<div class=\"leftmenu", "</a></div>");
		
		//once we have all div tags, find one which has id attribute "leftmenu"
		foreach ($divTags as $divTag)
		{
			$idAttr = ParsingTechniques::getAttribute($divTag, "class");
			//check if equals
			if(strcasecmp("leftmenu-element", trim($idAttr))==0)
			{
				$leftMenuArr[]=$divTag;
			}
		}
		return $leftMenuArr;
	}
	
	/**
	 * Given method receives HTML representation of sidemenu of the specific website, and outputs menu names
	 * 
	 * @param String/array $leftMenu required.		Parameter, holding HTML representation of side menu of the specific website.
	 * @throws CollectorException If receiving parameter can't be resolved to String
	 * @return multitype:string array of menu items of specific Website
	 */
	protected static function getLeftMenuNames($leftMenuIn)
	{
		$names = array();
		$aTags = self::getAnchors($leftMenuIn);
		//for each tag
		foreach ($aTags as $aTag)
		{
			$aTag = ParsingTechniques::splitString($aTag, "\">", 
					ParsingTechniques::AFTER_DELIM, 
					ParsingTechniques::EXCLUDE_DELIM);
			$aTag = ParsingTechniques::splitString($aTag, "<span", 
					ParsingTechniques::BEFORE_DELIM, 
					ParsingTechniques::EXCLUDE_DELIM);
			$names[]=$aTag;
		}
		return $names;
	}
	
	/**
	 * Method, to get all of the anchors from text receiving parameter
	 * 
	 * @param String $textIn required.		Receiving text from which to be anchors found
	 * @throws CollectorException If $textIn is not of type String && not of type array
	 * @return array of anchors (strings)
	 */
	protected static function getAnchors($textIn)
	{
		if(is_array($textIn))
		{
			$textIn=implode("\n", $textIn);
		}
		//check that $leftMenu must be string
		if(!is_string($textIn))
		{
			throw new CollectorException("Parameter \$leftMenuIn must
							be of type String or array (of Strings)!");
		}
		
		//get all a tags from html received
		$aTags = ParsingTechniques::getAllTags($textIn, "<a", "</a>");
		
		return $aTags;
	}
	
	/**
	 * Method to get models from clean extracted anchor HTML anchors
	 * 
	 * @param string $anchorsIn required.		Represents anchors, where from to extract models
	 */
	protected static function getModels($anchorsIn)
	{
		$aTags = self::getAnchors($anchorsIn);
		//extract all of the href attributes into array
		$models=array();
		foreach ($aTags as $aTag)
		{
			$models[]=ParsingTechniques::getAttribute($aTag, "href");
		}
		return $models;
	}
	
	/**
	 * Method for getting model of the left menu by specified name
	 * 
	 * @param string $menuNameIn required. 			Specifies name which model is required to return 
	 * @param string $outputIn required.			HTML part of the website (not cleaned - as it is)
	 * @throws CollectorException If specified $menuNameIn is not existing within left menu
	 * @return string Model of the menu required
	 */
	protected static function getLeftMenuModel($menuNameIn, $outputIn)
	{
		//get left menu first
		$leftMenuArr = self::collectMenu($outputIn);
		//get all names
		$leftMenuNames = self::getLeftMenuNames($leftMenuArr);
		//check that specified name exists within $leftMenuNames
		if(array_search($menuNameIn, $leftMenuNames)===false)
		{
			throw new CollectorException("Please re-check name -> does not exist within left menu!");
		}
		//get all models for names
		$leftMenuModels = self::getModels($leftMenuArr);
		//combine two arrays, to make name access model
		$combinedArray=array_combine($leftMenuNames, $leftMenuModels);
		//return corresponding model
		return $combinedArray[$menuNameIn];
	}
		
	/**
	 * Method to extract from menu submenus (Like Dog -> Shephard) models or names (to be specified by constant SUB_NAMES or SUB_MODELS)
	 * 
	 * @param String $outputIn required. 				Specifies output HTML part, which is thought to contain submenus 
	 * @param int $elementIn required. 					Specifies, what to be collected from HTML output, whether names or models (from constants SUB_NAMES or SUB_MODELS)
	 * @throws CollectorException If output HTML does not contain submenus
	 * @return array of strings (whether to be models or names)
	 */
	protected static function getSubMenuElement($outputIn, $elementIn)
	{
		//check firstly, if submenu exist, if not -> exception
		if(!self::isSubElements($outputIn))
		{
			throw new CollectorException("\$elementIn parameter expected to have submenu,
									however it was not found!");
		}
		//return submenu firstly
		$subMenu = self::collectSubMenus($outputIn);
		//get anchors
		$aSubMenu = self::getAnchors($subMenu);
		
		//check that sub element is correct
		switch ($elementIn)
		{
			case self::SUB_NAMES:
				return self::getLeftMenuNames($aSubMenu);
				break;
			case self::SUB_MODELS:
				return self::getModels($aSubMenu);
				break;
			default:
				throw new CollectorException("\$elementIn parameter in getSubMenuElement method accepts only
												predefined constants from WebsiteCollector class.");
		}
	}
	
	/**
	 * Method, to check if subelements do exist in input HTML part ($outputIn)
	 * 
	 * @param string $outputIn Specifies input string, where submenus to be searched
	 * @return boolean True, if submenus found, False otherwise
	 */
	protected static function isSubElements($outputIn)
	{
		//remove all white spaces?
		$subMenu = self::collectSubMenus($outputIn);
		//get submenus now
		$subMenu = self::getAnchors($subMenu);
		//return true, if exist. Else return false;
		if(count($subMenu))
		{
			return true;
		}
	}
	
	/**
	 * Helper class for collecting main part of submenu from input string 
	 * 
	 * @param String $htmlIn required. 		Represents html of left menu 
	 * @return String of HTML part of the submenus
	 */
	protected static function collectSubMenus($htmlIn)
	{
		//check if array
		if(is_array($htmlIn))
		{
			$htmlIn = implode("\n", $htmlIn);
		}
		return ParsingTechniques::getAllTags($htmlIn,
				 "<div class=\"leftmenu-subelements", "Main menu:");
	}
	
	/**
	 * Method for extracting pages from html (website's specifics)
	 * 
	 * @param string $outputIn required. 		HTML of the website
	 * @throws CollectorException If receiving parameter $outputIn is not of type string
	 * @return models of all pages collected, or if none False
	 */
	protected static function collectAllPages($outputIn)
	{
		if(!is_string($outputIn))
		{
			throw new CollectorException("Receiving parameter of collectAllPages must be of type String!");
		}
		$pageModels = array();
		//get all of the pages, listed above items
		$pages = ParsingTechniques::getAllTags($outputIn, "<div class=\"product-list-pages product-list-pages-top", 
															"</div>");
		//return only string
		//if no pages return false
		if($pages==null)
		{
			return false;
		}
		//get all anchors
		$anchors = self::getAnchors($pages[0]);
		//for each anchor get model
		foreach($anchors as $anchor)
		{
			//parse href
			$href = ParsingTechniques::getAttribute($anchor, "href");
			$pageModels[]=$href;
		}
		return $pageModels;
	}
	
	/**
	 * Helper class to get html for collecting middle page items and vendors
	 * 
	 * @param string $outputIn html input
	 */
	protected static function collectMiddlePage($outputIn)
	{
		return ParsingTechniques::getAllTags($outputIn, "<table class=\"product", "</table>");
	}
	
	/**
	 * Helper method to get all of the middle page item anchors (excluding vendors)
	 * 
	 * @param string $outputIn required. 	HTML output page (as collectMiddlePage() method output)
	 * @return array of item anchors
	 */
	protected static function getAllItemsMiddlePageAnchors($outputIn)
	{
		//specify array of items
		$itemAnchors = array();
		//get middle content
		$outputIn = self::collectMiddlePage($outputIn);
		//get all anchors
		$anchors = self::getAnchors($outputIn);
		//sort all anchors which to be items
		foreach ($anchors as $anchor)
		{
			$class = ParsingTechniques::getAttribute($anchor, "class");
			if(!strcasecmp(trim($class), "gen-user-link")==0)
			{
				$itemAnchors[]=$anchor;
			}
		}
		return $itemAnchors;
	}
	
	/**
	 * Method to get all middle page item models. 
	 * 
	 * @param string $outputIn required.		html input
	 * @return string[] array of models
	 */
	protected static function getAllItemMiddlePageModels($outputIn)
	{
		$models = array();
		$itemAnchors = self::getAllItemsMiddlePageAnchors($outputIn);
		foreach ($itemAnchors as $itemAnchor)
		{
			$models[]=ParsingTechniques::getAttribute($itemAnchor, "href");
		}
		return $models;
	}
	
	/**
	 * Method to get from input all of the middle page Vendor Anchors
	 * 
	 * @param string $outputIn required. 	html input
	 * @return string[] array of vendor anchors
	 */
	protected static function getAllVendorsMiddlePageAnchors($outputIn)
	{
		//specify array of items
		$vendorAnchors = array();
		//get middle content
		$outputIn = self::collectMiddlePage($outputIn);
		//get all anchors
		$anchors = self::getAnchors($outputIn);
		//sort all anchors which to be items
		foreach ($anchors as $anchor)
		{
			$class = ParsingTechniques::getAttribute($anchor, "class");
			if(strcasecmp(trim($class), "gen-user-link")==0)
			{
				$vendorAnchors[]=$anchor;
			}
		}
		return $vendorAnchors;
	}

	//PRODUCT SPECIFIC METHODS ARE GOING IN HERE	
	/**
	 * Gets all product information, including image, price, 
	 * 								vendors and descriptions
	 * 
	 * @param string $outputIn required.		html output from main page exec (product page)
	 * @return string html part of all product information specifics, or false if there is no possible to find 
	 * 			product part of the page. (for developing purpose at the moment)
	 */
	protected static function getProductHTML($outputIn)
	{
		$arr= ParsingTechniques::getAllTags($outputIn, "<div id=\"single-product", "<div class=\"button-red");
		//FIXME temporary check here
		if(!isset($arr))
		{
			return false;
		}
		return $arr[0];
	}
	
	/**
	 * Method for getting model of the image of the product
	 * 
	 * @param string $productHTML required.		Product's html 
	 * @return src model of the image
	 */
	protected static function getProductImage($productHTML)
	{
		$arr = self::helperProductImage($productHTML);
		if($arr==false)
		{
			throw new CollectorException("image was not found in page");
		}
		return ParsingTechniques::getAttribute($arr, "src");
	}
	
	/**
	 * Helper class for getting whole image tag to be used within specific methods only (like getProductImage etc)
	 * 
	 * @param string $productHTML required.				Product's html
	 * @return string image tag (complete) or False, if nothing found
	 */
	protected static function helperProductImage($productHTML)
	{
		//check that image is not a flag country
		//so first check how many images exist in page -> if two, check if class equals to "flag-img"
		$arr = ParsingTechniques::getAllTags($productHTML, "<img", ">");
		foreach ($arr as $img)
		{
			$class = ParsingTechniques::getAttribute($img, "class");
			if(!strcasecmp($class, "flag-img")==0)
			{
				return $img;
			}
		}
		//check here for second one, if nothing found on first run
		$arr = ParsingTechniques::getAllTags($productHTML, "<img", "</img>");
		foreach ($arr as $img)
		{
			$class = ParsingTechniques::getAttribute($img, "class");
			if(!strcasecmp($class, "flag-img")==0)
			{
				return $img;
			}
		}
		//if nothing found -false
		return false;
	}
	
	/**
	 * Method to extract image name from model 
	 * @param string $imageHTML required.
	 * @return string name of the image
	 */
	protected static function getProductImageName($imageHTML)
	{
		//explode
		$arr = explode("/", $imageHTML);
		//return name of the image
		return $arr[count($arr)-1];
	}
	
	/**
	 * Method to extract name of the product html page
	 * 
	 * @param string $productHTML required.
	 * @return string product name
	 */
	protected static function getProductName($productHTML)
	{
		$arr = ParsingTechniques::getAllTags($productHTML, "<h1>", "</h1>");
		$productName = ParsingTechniques::returnValue($arr[0], "<h1>", "</h1>", ParsingTechniques::EXCLUDE_DELIM);
		return $productName;
	}
	
	/**
	 * Method, to collect specific product description (including all HTML formatting)
	 * 
	 * @param string $productHTML required.		Represents output html (ordinary product page)
	 * @return string Description of product
	 */
	protected static function getProductDescription($productHTML)
	{
		
		$separatorOne = self::helperProductImage($productHTML);
		$separatorTwo = "<a class=\"gen";
		//we might get false in here? for image output HTML
		if($separatorOne==false)
		{
			//make for false statement description
			//then $separatorOne -> something else
			$separatorOne="\"></div>";
		}
		$description = ParsingTechniques::splitString($productHTML, $separatorOne,
				ParsingTechniques::AFTER_DELIM, ParsingTechniques::EXCLUDE_DELIM);
		$description = ParsingTechniques::splitString($description, $separatorTwo,
				ParsingTechniques::BEFORE_DELIM, ParsingTechniques::EXCLUDE_DELIM);
		//use trim to get rid of white spaces
		return trim($description);
	}
	
	/**
	 * Method to get price of the product html output
	 * 
	 * @param string $productHTML required.
	 * @return string price of the product
	 */
	protected static function  getProductPrice($productHTML)
	{
		$arr = ParsingTechniques::getAllTags($productHTML, "<div class=\"product-page-price", "</div>");
		//get middle item
		return ParsingTechniques::returnValue($arr[0], "<div class=\"product-page-price\">", "</div>", ParsingTechniques::EXCLUDE_DELIM);
	}
	
	/**
	 * Method to get seller's name of the product
	 * 
	 * @param string $productHTML required. inner item HTML representation
	 * @return string seller's name
	 */
	protected static function getProductSellerName($productHTML)
	{
		$arr = ParsingTechniques::getAllTags($productHTML, "<a class=\"gen-user-link", "</a>");
		
		$arr[0]= ParsingTechniques::returnValue($arr[0], "<a", "</a>", ParsingTechniques::EXCLUDE_DELIM);
		$sellerName = ParsingTechniques::splitString($arr[0], ">", 
				ParsingTechniques::AFTER_DELIM, ParsingTechniques::EXCLUDE_DELIM);
		return $sellerName;
	}
	
	/**
	 * Method for extracting country to which shipping is going to 
	 * 
	 * @param string $productHTML
	 * @return boolean|string False, if no shipping country, else return shipping country
	 */
	protected static function getProductShipCountry($productHTML)
	{
		//firstly -> not all of the products do have shipping country
		//therefore return shipping country or false
		$arr = ParsingTechniques::getAllTags($productHTML, "<img class=\"flag-img", "<br/>");
		if(count($arr)==0)
		{
			return false;
		}
		
		//else think to get country
		//think two approaches
		$frst = ParsingTechniques::getAllTags($arr[0], "</img>", "<br/>");
		//in case returns nothing -> go for second
		if(count($frst)>0)
		{
			$sellerCountry = ParsingTechniques::splitString($arr[0], "</img>",
					ParsingTechniques::AFTER_DELIM, ParsingTechniques::EXCLUDE_DELIM);

			}
		else 
		{
			$sellerCountry = ParsingTechniques::splitString($arr[0], "/>",
					ParsingTechniques::AFTER_DELIM, ParsingTechniques::EXCLUDE_DELIM);	
		}
		$sellerCountry = ParsingTechniques::splitString($sellerCountry, "<br/>",
				ParsingTechniques::BEFORE_DELIM, ParsingTechniques::EXCLUDE_DELIM);
		return trim($sellerCountry);
	}
	
	/**
	 * Method for checking, if specified menu has been selected as parent menu (and has submenu as common sense as well)
	 *
	 * @param string $outputIn required. 				HTML output of the page
	 * @param string $menuNameToCheck required.			Menu name to check
	 * @param string $modelIn required. 				Model of the menu to be checked
	 * @return True, if menu is selected and has submenus, or False otherwise
	 */
	protected static function checkSelectedMenu($outputIn, $menuNameToCheck, $modelIn)
	{
		//so firstly cut string to the end
		//get leftmenu
		$outputFormated = ParsingTechniques::getAllTags($outputIn, "<div id=\"leftmenu\">", "<td class=\"mainpagedividertd\">");
		//we know that first element is menu
		$outputFormated = $outputFormated[0];
		//now from here find how to extract selected menu
		$outputFormated = ParsingTechniques::splitString($outputFormated, "<a href=\"{$modelIn}\">", ParsingTechniques::AFTER_DELIM, ParsingTechniques::EXCLUDE_DELIM);
		//and now last element for getting selected submenu
		$outputFormated = ParsingTechniques::splitString($outputFormated, "</a>", ParsingTechniques::BEFORE_DELIM, ParsingTechniques::EXCLUDE_DELIM);
	
		if(strcasecmp($menuNameToCheck, trim($outputFormated))==0)
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Method for getting distribution area for product
	 * 
	 * @param string $htmlIn required. 			HTML output of the product page 
	 * 												(NOTE: not product html exec self::getProductHTML())
	 * @return string Distribution area (if exist) or false if not existing
	 */
	public static function getProductDistribution($htmlIn)
	{
		$data = ParsingTechniques::getAllTags($htmlIn, "\"fa fa-home\">", "</div>");
		if(count($data)==0)
		{
			return false;
		}
		//now extract required data
		$data = ParsingTechniques::splitString($data[0], "To:", ParsingTechniques::AFTER_DELIM, ParsingTechniques::EXCLUDE_DELIM);
		$data = ParsingTechniques::splitString($data, "</div>", ParsingTechniques::BEFORE_DELIM, ParsingTechniques::EXCLUDE_DELIM);
		return trim($data);
	}
		
	/**
	 * Make ordinary execution of spider with specific model or main page, if model has not been specified
	 * 
	 * @param string $cookieIn required. 				Cookie location for current spider.
	 * @param string $modelIn optional. 				model to be executed
	 * @param string $refererIn optional. 				referer for given execution
	 * @param string $torProxyIn optional. 				proxy settings of TOR network {default: 127.0.0.1:9150/}
	 * @param string $torBrowserLocationIn optional. 	Location of the tor browser to be executed for program {under development yet - somelocation}
	 * @return  False, if no execution is possible, or HTML output if successfull.
	 */
	protected static function getOrdinaryPageExec($cookieIn, $torProxyIn,  $modelIn = null, 
											$refererIn = null, $torBrowserLocationIn = "someLocation")
	{
		$spider = new Spider($cookieIn);
		$spider->setProxy($torProxyIn);
		$spider->startTor($torBrowserLocationIn);
		//check if model is not received -> then make ordinary page execution
		if($modelIn==null)
		{
			$spider->addStep("http://".self::$websiteDomain."/", Step::ORDINARY, "http://".self::$websiteDomain."/dologin", self::$websiteDomain);
		}
		else 
		{
			$spider->addStep("http://".self::$websiteDomain.$modelIn, Step::ORDINARY, $refererIn, self::$websiteDomain);
		}
		//get ordinary output
		$output = $spider->startFetchData();
		return $output;
	}
	
	//TODO now, at the moment recognized error in here, proxy can't be sent through the CLI and only predefined will 
	//work.
	/**
	 * Method for saving resource at specified location
	 * 
	 * @param string $cookieIn required. 		Location of cookie to be used for spider.
	 * @param string $modelIn required.			Location of model to be executed
	 * @param string $refererIn required.		Referer for given execution for resource saving
	 * @param string $savingLocation required.	Directory where resource will be saved in
	 * @param string $torProxyIn optional.		Tor proxy settings to be set. {Default: 127.0.0.1:9050/}
	 * @param string $torBrowserLocationIn		Tor browser directory to be started {under development yet}
	 * @return True, if execution was successful, false otherwise
	 */
	protected static function getResourceExec($cookieIn, $modelIn, $refererIn, $savingLocation,
																	$torProxyIn, $torBrowserLocationIn)
	{
		 $spider = new Spider($cookieIn);
		 $spider->setProxy($torProxyIn);
		 $spider->startTor($torBrowserLocationIn);
		 $spider->addStep("http://".self::$websiteDomain.$modelIn, Step::RESOURCE, 
		 							$refererIn,self::$websiteDomain,$savingLocation);
		 try 
		 {
		 	$spider->startFetchData();
		 	return true;
		 }
		 catch (CollectorException $e)
		 {
		 	return false;
		 }		 
	}
	
	/**
	 * Method for determining whether subCategories exist within menu, or not.
	 * 
	 * In detail: Execution will continue for around 10 hours if server will fail to process querry, therefore 
	 * to make sure it will be up (as TOR services tend to stay down a while each day - couple of hours), and 
	 * if however it fails, it will return to previous step anyways (in case menu had been deleted). At later version
	 * make sure to add more user control over this waiting time.
	 * 
	 * @param string $cookieIn required.		Represents file in the system, cookie location
	 * @param string $proxyIn required. 		Represents proxy settings of the Collector.
	 * @param string $pageHTML required. 		Represents html output of the model as refererModelIn
	 * @param string $categoryNameIn required.	Name of the category, which to be collected
	 * @param string $refererIn required. 		Represents referer for category.
	 * @param string $menuModel optional.		If null, then it is expected, that menu could be found within left 
	 * 											menu from ordinary page exec. All the submenu parents need to have
	 * 											this argument as model to make execution for it.
	 * @return array(sub_models[], sub_names[], outputHTML  of submenu models and submenu names OR False, 
	 * 			if there was no subcategories, and OutputHTML of the category specified)
	 */
	protected static function collectCategoryHierarchy($cookieIn, $proxyIn, $pageHTML, $categoryNameIn, 
																		$refererIn, $menuModel=null)
	{
		//very first thing, is to check if there is at least one parent category set
		if(count(self::$parentCategory)==0)
		{
			//set parent directory here
			$arrHelper = self::$parentCategory;
			array_push($arrHelper, $categoryNameIn);
			HelperStaticChanger::changeStaticProperty(__CLASS__, "parentCategory", $arrHelper);
		}
		
		//make sure to collect all required categories into database
		if(!DatabaseSettings::existCategory($categoryNameIn, self::$websiteDomain))
		{
			//make sure to capture this data into database (if already not exist)
			DatabaseSettings::insertCategoryValues($categoryNameIn, self::$websiteDomain);
		}
		if($menuModel==null)
		{
			//get required menu model for $itemNameIn
			$menuModel = self::getLeftMenuModel($categoryNameIn, $pageHTML);
		}
		
		//THOUGHTS HERE:
		//check which element is selected? and then make submenu decision upon it?
		//however we do need to have model extracted from it
		
		//FIXME at some point, there might be produced error 7 or 28 (can't connect to host, or timeout)
		// therefore requires to make some more attempts to download cURL exec, than just one (because by 
		// code at the moment it will jump back to first run). This, of course, must be reviewed at far more
		// in deep at later stage, but at the moment, we might go with 5 min attempt to connect, if not ->
		// then throw an exception with details. 
		//TODO NOTE also think to make standard library for output messages to client, because at later stage
		//it is highly possible, that message will be removed from here
		
		//with left menu model we can extract it and view, if there is any other submenus
		//TODO for performance betterness think at later stage to save all of the $categoryOutput {version 2.0}?
		try 
		{
			$categoryOutput = self::getOrdinaryPageExec($cookieIn, $proxyIn, $menuModel, $refererIn);
		}
		catch (CollectorException $e)
		{
			//output message for the user
			echo "\nException has been raised with collecting hierarchies modelling.";
			echo "\nHere is following exception message: {$e->getMessage()}";
			echo "\nThis happened in following time: ".gmdate('H:i:s', GeneralPerformance::calculateTime());
			
			//make sure to try execute step for a 60 times, and see if it helps.
			if(GeneralPerformance::$numOfExceptions<60)
			{
				//wait for some time specified in GeneralPerformance
				GeneralPerformance::waitForResponse();
				return  self::collectCategoryHierarchy($cookieIn, $proxyIn, $pageHTML, $categoryNameIn, $refererIn, $menuModel);
			}
			else 
			{
				//reset $numOfExceptions in generalPerformance
				GeneralPerformance::resetWaitForResponse();
				throw new CollectorException($e->getMessage());
			}
			
		}
		
		//additional check here, as sub-elements might not present, even if it is showing. Requires to have additional
		//check, to determine, if selected item is actually parent
		if(self::isSubElements($categoryOutput))
		{
			//checks for parent's selection 
			if(self::checkSelectedMenu($categoryOutput, $categoryNameIn, $menuModel))
			{
				$arrSubMenus = self::getSubMenuElement($categoryOutput, self::SUB_MODELS);
				$arrSubNames = self::getSubMenuElement($categoryOutput, self::SUB_NAMES);
				//think here, we have no model, but we need to match output, therefore
				//second requires to have model. However to keep up database, we require, to have 
				return array($categoryNameIn, $categoryOutput, $arrSubMenus, $arrSubNames, true);
			}
		}
		return array($categoryNameIn,$pageHTML, $refererIn, $menuModel, false, $categoryOutput);
	}
	
	/**
	 * This method aims to extract all page' models of the items, and collect them into receiving parameter array $collectedModelsIn
	 * 
	 * @param string $modelIn required.					Represents model to be extracted 
	 * @param string $categoryIn required.				Represents category name to be entered into returning model array
	 * @param string $subCategoryIn required.			Represents subcategory name to be entered into returning model array
	 * @param array $collectedModelsIn required.		Receiving array of models, to which models of the execution to be collected
	 * @param string $refererIn required. 				Referer of the execution model
	 * @param string $proxyIn required.					Proxy settings of the TOR curl
	 * @param string $torBrowserLocationIn reqiered.	Represents tor browser location
	 * @return Model[] array of all required info
	 */
	protected static function extractModel($cookieIn, $modelIn, $categoryIn, $subCategoryIn, $collectedModelsIn, $refererIn, $proxyIn, $torBrowserLocationIn)
	{
		
		$output = self::getOrdinaryPageExec($cookieIn, $proxyIn, $modelIn, $refererIn, $torBrowserLocationIn);
		//check if pages exist within output
		$pages = self::collectAllPages($output);
		//if pages, go through each one to collect
		if($pages)
		{
			foreach ($pages as $page)
			{
				//TODO	we may improve performance here by removing first page from calculations? {version 2.0}
				$output = self::getOrdinaryPageExec($cookieIn, $proxyIn, $page, $refererIn, $torBrowserLocationIn);
				$models = self::getAllItemMiddlePageModels($output);
				foreach ($models as $model)
				{
					$collectedModelsIn[] = new Model($categoryIn, $subCategoryIn, $model);
				}
			}
		}
		else 
		{
			//no pages do exist here
			$models = self::getAllItemMiddlePageModels($output);
			foreach ($models as $model)
			{
				$collectedModelsIn[]= new Model($categoryIn, $subCategoryIn, $model);
			}
		}
		return $collectedModelsIn;
	}
	
	/**
	 * Recursion method for calling and discovering categories and sub_categories of the products. At the moment limited for max 2 subcat {to be fixed in 2.0}
	 * 
	 * @param string $cookieIn required. 		Directory, specifying where cookie is located.
	 * @param string $proxyIn required.			Proxy settings required for executing spider TOR.
	 * @param mixed $arrOfList required.		Output of the collectCategoryHierarchy() method. Check documentation.
	 * @return array[$categoryName, $referer, $modelIn, $pageHTML, $parentCategory] OR array of arrays [like $categoryName will have array of all 
	 * 										subcategories of next parent, $pageHTMLS etc]
	 */
	private static function calculateRepeatCategories($cookieIn, $proxyIn, $arrOfList)
	{
		//THOUGHTS HERE:
		//make notes in here. think, we send same parameter to indicate, whether it is parent or not. we don't need it.
		//secondly. we need to check
		//make additional array, which will hold all of the information, about, what steps to be done
		$collectedSteps = array();
		//get required variables from parameter
		list($categoryName, $pageHTML, $refererIn, $modelIn, $result)=$arrOfList;
		if($result==false)
		{
			//return array of the required calculations to be done for checking pages and make calculations of all 
			//required items and pop parent category
			//check if model was the last?
			return array($categoryName, $refererIn, $modelIn, $pageHTML, self::$parentCategory[count(self::$parentCategory)-1]);
		}
		else 
		{
			//THOUGHTS
			//save parent directory in here
			//i need to return to parent, once finished all child parent collection
			//therefore it is not so good idea to store within static variable
			//however use pop and push logic might make the job
			
			//check that no category already exist
			//and if it's not -> then push it in
			$exist = false;
			$arrayHelper = self::$parentCategory;
			foreach ($arrayHelper as $categoryHelper)
			{
				if(strcasecmp($categoryHelper, $categoryName)==0)
				{
					$exist = true;
				}
			}
			if(!$exist)
			{
				array_push($arrayHelper, $categoryName);
				HelperStaticChanger::changeStaticProperty(__CLASS__, "parentCategory", $arrayHelper);
				
			}
			//make correct names here
			$arrSubMenus = $refererIn;
			$arrSubNames = $modelIn;
			//will not work, requires a bit different logic.
			//maybe not return, but store, straight away?
			for($i=0;$i<count($arrSubMenus);$i++)
			{
				//think to write into database required subcategory in this block
				//so check firstly if it doesn't exist and make record
				if(!DatabaseSettings::existSubCategory(
								$arrSubNames[$i], DatabaseSettings::pkCategory(
												$categoryName, self::$websiteDomain)))
				{
					//insert new record in here
					DatabaseSettings::insertSubcategoryValues($arrSubNames[$i], 
						DatabaseSettings::pkCategory($categoryName, self::$websiteDomain));
				}
				//and now make recursion for required collection of categories again
				//for first run, there is no referer, just re-fresh
				if($i==0)
				{
					//IMPORTANT STUFF HERE -> ALLOWS TO NOT CARE OF HOW MANY SUB_CATEGORIES EXIST
					//go back to calculation of the recursion
					//think here, I need to nest it, to make additionally check of the self::calculateRepeatCategories
					//NOTE however, that some other parts of the code at the moment allows only two sub sub categories (fixed)
					$collectedSteps[] = self::calculateRepeatCategories($cookieIn, $proxyIn,
									self::collectCategoryHierarchy($cookieIn, $proxyIn, $pageHTML, $arrSubNames[$i], 
														"http://".self::$websiteDomain.$arrSubMenus[$i], 
																							$arrSubMenus[$i]));	
				}
				//referer exist as previous page
				else 
				{	
					$collectedSteps[] = self::calculateRepeatCategories($cookieIn, $proxyIn,
										self::collectCategoryHierarchy($cookieIn, $proxyIn, $pageHTML, $arrSubNames[$i],
											"http://".self::$websiteDomain.$arrSubMenus[$i-1], $arrSubMenus[$i]));	
				}
			}
			//else make sure, to tell to category pop -> as all of the child class collections are finished here
			$arrayHelper = self::$parentCategory;
			//pop parent category (push pop logic), however at the moment parent would not be needed
			//as use static track
			array_pop($arrayHelper);
			HelperStaticChanger::changeStaticProperty(__CLASS__, "parentCategory", $arrayHelper);
			$arrayHelper=null;
			//once finished recursion -> return collected steps
			return $collectedSteps;
		}
	}
	
	//TODO in {version 2.0} requires to make throughout analyzis of how to make sure, if any part of the 
	//code in precollecting models suspends, it will be able to try it for more times, or skip some steps 
	//for performance (or even for cruciality of the system, if there is huge category part to be collected)
	/**
	 * Method to collect all the models from specified category (including all pages) 
	 * 
	 * @param string $cookieIn requried.				Specifies location of the cookie in the system.
	 * @param string $categoryNameIn required.			Category to be collected
	 * @param string $outputIn required.				output HTML of main page
	 * @param string $proxyIn required. 				Proxy settings, to be used within collecting models.
	 * @param string $torBrowserLocationIn required. 	Location of TOR able browser software.
	 * @throws CollectorException If there is no such category as specified (in left menu)
	 * @return string[] Models array
	 */
	protected static function collectAllModels($cookieIn, $categoryNameIn, $outputIn, $proxyIn, $torBrowserLocationIn)
	{
		//THOUGHTS:
		//think now, that additionally it is possible to have sub-sub menus
		//firstly -> first output to be done with main page referer
		$refererIn = "http://".self::$websiteDomain."/";
		//array of models to be returned
		$collectedModels = array();
		
		//check if $output was executed - if not exception raise
		if($outputIn)
		{
			//make simple checking if the parameter is single or multiple category object
			//and do appropriate techniques to make procedures out of it.
			//check  how it works firstly by collecting all of the step output
			$arrOutput = self::calculateRepeatCategories($cookieIn, $proxyIn, 
							self::collectCategoryHierarchy($cookieIn, $proxyIn, $outputIn, $categoryNameIn, $refererIn));
			//save output of the repeat categories to website collector, therefore programme would be able to make own startup, if they were
			//existing, to save time in case of no execution
				
			//save current status of the programme and arrOutput
			HelperStaticChanger::changeStaticProperty(__CLASS__, "collectedCategories", $arrOutput);
			//FIXME think after a while to add STATUS property for knowing where is collector at the moment
					
			//FIXME output of the method finalizeCollectingModels()
			//think -> if catched exception restart in here
			try 
			{
				$collectedModels = self::finalizeCollectingModels($arrOutput, $cookieIn, $proxyIn, $torBrowserLocationIn);
			}
			catch (\zaharovs\collector\CollectorException $e)
			{
				//make specific number of exception -> not 2 (to differentiate)
				throw new CollectorException($e->getMessage(), 3);
			}
		}
		else 
		{
			//what should I do, if can't make ordinary output? Exception
			throw new CollectorException("Can't make ordinary output from Collector");
		}
		//check if models are already in database, therefore to focus only on those which need to be collected
		$current_models = array();
		//database models
		$db_models=DatabaseSettings::getModels();
		//make sure to check that $db_models is array at all (in case no records)
		if(is_array($db_models))
		{
			foreach ($collectedModels as $model)
			{
				if(!array_search("http://".self::$websiteDomain.$model->getModel(),$db_models))
				{
					$current_models[]=$model;
				}
			}
		}
		else 
		{
			//make initial case by copying all of the values
			$current_models = $collectedModels;
		}
		//set for performance measurement num of items to be scanned
		GeneralPerformance::setNumOfModells(count($current_models));
		//return models to be scanned
		return $current_models;		
	}
	
	
	
	
	/**
	 * This method will do shortcut for collecting models required.
	 * 
	 * @param array $collectedCategoriesIn required. 		Output of the calculateRepeatCategories() method of self
	 * @param array $collectedModelsIn
	 */
	public static function finalizeCollectingModels($collectedCategoriesIn, $cookieIn, $proxyIn, $torBrowserLocationIn)
	{
		
		//THOUGHTS:
		//now here we have complete output array of all steps to be performed including all subcategories
		//so firstly, check if first item arrived is of type array, if not we know, than this category had
		//no any subcategories, and therefore make normal calculations. Else, make calculations for each array
		//received in, from result
		$collectedModels = array();	
		//not all of the categories will have subcategory at all, requires to check this.
		//therefore both have to be strings, as array will also equal to array
		
		//NOTE here, this means no subcategories at all, as subcategory will be equal to category?
		//return collectedModels
		return self::multipleCategories($collectedCategoriesIn, $collectedModels, $cookieIn, $proxyIn, $torBrowserLocationIn);
	}
	
	/**
	 * Helper method for collecting from array multiple items
	 * 
	 * @param array $collectedCategoriesIn required. 		Part procedure of the finalizeCollectingModels() method.
	 */
	protected static function multipleCategories($collectedCategoriesIn, $collectedModels, 
																$cookieIn, $proxyIn, $torBrowserLocationIn)
	{
		//checking process in here, if there is multiple categories
		if(is_string($collectedCategoriesIn[0])&&is_string($collectedCategoriesIn[4]))
		{
			//make single one
			$collectedModels = self::singleCategory($collectedCategoriesIn, $collectedModels, $cookieIn, $proxyIn, $torBrowserLocationIn);
		}
		//else make recursive check for categories
		else 
		{
			//however requires to make additional helper method, which will go over array, and collect all of the models
			//so output HTML will show, if it is parent or child
			for ($i=0; $i<count($collectedCategoriesIn); $i++)
			{
				//anyways of receiving input, only one with array will be parent, otherwise it will be calculated
				//make recursive collection in here
				$collectedModels = self::multipleCategories($collectedCategoriesIn[$i], $collectedModels, $cookieIn, $proxyIn, $torBrowserLocationIn);
			}
		}
		//return statemnt here
		return $collectedModels;
	}
	
	/**
	 * Helper method for collecting from array with single item
	 * 
	 * @param array $collectedCategoryIn required.		   Part procedure of the finalizeCollectingmodels() method.
	 */
	private static function singleCategory($collectedCategoriesIn, $collectedModels, 
																$cookieIn, $proxyIn, $torBrowserLocationIn)
	{
		//here we see that it is only one parent category for all items
		//collect all models from here
		//FIXME think to make some saving resource calculations of output {version 2.0}
		list($subcategory, $referer, $model, $output, $parentCategory)=$collectedCategoriesIn;
		//suppress warning in here for output as not used yet
		unset($output);
		//requires to check if pages exist within subcategory
		//and collect them?
		//sorted in extractedModel itself;
		//make extraction of models in here
		
		//additional exception handler here, think to implement additional time for 
		//waiting server response in case it becomes unresponsive. Therefore, 
		//will keep doing same step over and over, for let's say X time
		try 
		{
			$collectedModels = self::extractModel($cookieIn, $model, $parentCategory, $subcategory,
											$collectedModels, $referer, $proxyIn, $torBrowserLocationIn);
			//make error statement back to to zero, if success
			GeneralPerformance::resetWaitForResponse();
		}
		catch (CollectorException $e)
		{
			//wait required time
			if(GeneralPerformance::$numOfExceptions<60)
			{
				//wait and try again
				GeneralPerformance::waitForResponse();
				self::singleCategory($collectedCategoriesIn, $collectedModels, $cookieIn, $proxyIn, $torBrowserLocationIn);
			}
			else 
			{
				//reset steps
				GeneralPerformance::resetWaitForResponse();
				throw new CollectorException($e->getMessage());
			}
		}
		
		//return collected models
		return $collectedModels;
	}
	
	/**
	 * Main interface to callect data for website specified with other classes in collector.
	 * 
	 * @param string $cookieIn required.				Specifies cookie file, which to be used for spider
	 * @param string $torBrowserIn required.			Specifies tor browser id, which to be seen by server admin
	 * @param array $categoriesIn required.				Represents array of all categories to be collected from website
	 * @param string $proxySettingsIn required.			Specifies proxy settings to be TOR compatible
	 * @param string $torBrowserLocationIn required. 	Represents directory of the TOR client
	 * @param string $resourceSaveLocationIn required.	Specify directory where to save all of the resources from website
	 * @param string $websiteDomainIn required.			Specifies domain of the website to be collected. 
	 * @param int $limitIn required.					Represents maximum of steps to be performed by spider {default = 50}
	 * @param array $sleepIn required.					Represents array of two integer values for setting interval, of how long will each execution going to take [OPTIONAL] {1,10}-default
	 * @throws CollectorException if there is no such categories to be collected from website (not exist within left menu)
	 * @return bool True if everything was successful (might not be needed as throws exception otherwise)
	 */
	public static function collectData($cookieIn, $torBrowserIn, $categoriesIn, $proxySettingsIn, $torBrowserLocationIn,
																$resourceSaveLocationIn, $websiteDomainIn, $limitIn, $sleepIn)
	{
		//set domain
		self::changeWebsiteDomain($websiteDomainIn);
		//connect to db -> or if it doesn't exist -> make one
		DatabaseSettings::createDatabase();
		//make website table insert record
		//firstly check if there is already exist record
		if(!DatabaseSettings::existWebsite(self::$websiteDomain))
		{
			//as it doesn't exist make record here
			DatabaseSettings::insertWebsiteValues(self::$websiteDomain, "http://".self::$websiteDomain."/");	//at the moment make no description
																				//in website table
		}
		//normal execution of the page
		$output = self::getOrdinaryPageExec($cookieIn, $proxySettingsIn, null, null, $torBrowserIn);
		//check for each item in array, which to be collected, that names do exist -> otherwise rise an CollectorException
		self::checkCategories($categoriesIn, $output);
		
		//start making Spider collection here
		$spider = new Spider($cookieIn, $torBrowserIn, $limitIn);
		$spider->setProxy($proxySettingsIn);
		$spider->startTor($torBrowserLocationIn);
		
		//if all exist -> then for each category specified make execution of models required
		foreach ($categoriesIn as $category)
		{
			$modelCollected = self::collectAllModels($cookieIn, $category, $output, $proxySettingsIn, $torBrowserLocationIn);			
			//collect all of the items to be collected by spider, by pre-collecting categories (might not be needed any more) 
			//TODO review at {version 2.0}
			$spider->collectSteps($modelCollected, Spider::PRE_COLLECT_CATEGORY);
		}
		//after we have full array of all required data we can manipulate to build steps required
		
		//THOUGHTS HERE:
		//now think what to do for each model
		//also think about shuffling?
		//timing as well
		self::collectProducts($cookieIn, $resourceSaveLocationIn, $spider, $sleepIn);
		
		//if everything was successful -> return true
		return true;
   }

   
   //TODO documentation at later stage
	/**
	 * Helper method for collecting product required details.
	 * 
	 * @param string $resourceSaveLocationIn required.		Specifies, where to save MIME resources.
	 * @param Spider $spider required. 						Specifies, for which spider collecting will be executed.
	 * @param int[] $sleepIn required. 						Specifies array of time delay to be set for spider's execution
	 * @throws CollectorException if there was a problem of execution of spider. however will try to restore steps, therefore 
	 * 							  new created spider would have a chance to continue steps's execution.	
	 */
	 public static function collectProducts($cookieIn, $resourceSaveLocationIn, Spider $spider, $sleepIn) 
	 {
	 	//make output n
	 	$outN = 0;
	 	while($spider->isStepsUnvisited())
		{
			try 
		 	{
		 		//FIXME at later stage {version 2.0} for output in correct way, 
		 		//however for user interface purpose output current execution num
		 		++$outN; //increment
		 		echo "\nData has been started to collecting n: {$outN}";
		 		
		 		$model = $spider->startFetchData(Spider::PRE_COLLECT_CATEGORY);
		 		$output = $spider->startFetchData(Spider::SKIP_COLLECT_CATEGORY);
				//check what is output in here
				if($output==false)
				{
					//skip for now, later check requires
					//check model?
					echo "There was a problem with collecting spider data on step: {$spider->getResetStep()}".
									"\nModel: {$spider->getSteps()[$spider->getResetStep()]}";
					//echo "\nOutput is false! website collector line 1287.\n";
				}
				else
				{
					//anulate exceptions, if execution did happen
					HelperStaticChanger::changeStaticProperty("zaharovs\collector\GeneralPerformance", "numOfExceptions", 0);
				}
		 	}
		 	catch (CollectorException $e)
		 	{
		 		//In case cURL will not be able execute page, make spider be sustainable
	 			//for repeating execution of [5 minutes every 10 seconds (60times*10s)] NOT CORRECT ANY MORE HERE, to make sure
	 			//that this happened not for sake of internet, or tor temporary disconection.
		 		
		 		//TODO under testing yet here
		 		//save steps
		 		HelperStaticChanger::changeStaticProperty("zaharovs\collector\Spider", "collectedSteps", $spider->getSteps());
		 		
		 		//output feedback to the user.
		 		printf("\nException has been risen: %s\n",$e->getMessage());
		 		
		 		//don't throw error for some time
		 		if(GeneralPerformance::$numOfExceptions<60)
		 		{
		 			//wait for response time specified in General Peroformance
		 			GeneralPerformance::waitForResponse();
		 			//execute later here
		 			self::collectProducts($cookieIn, $resourceSaveLocationIn, $spider, $sleepIn);
		 		}
		 		else 
		 		{
		 			//make sure to annulate num of exceptions
		 			GeneralPerformance::resetWaitForResponse();
		 			//re-throw exception
		 			throw new CollectorException($e->getMessage());
		 		}
		 	}
		 	//to be re-think here
			//check if output false -> go out of loop
			if(!$output)
			{
				//if skip is specified
				//don't throw error for some time
				if(GeneralPerformance::$numOfExceptions<30)
				{
					//wait for response time specified in General Peroformance
					GeneralPerformance::waitForResponse();
					//execute later here
					self::collectProducts($cookieIn, $resourceSaveLocationIn, $spider, $sleepIn);
				}
				else
				{
					//make sure to annulate num of exceptions
					GeneralPerformance::resetWaitForResponse();
					//skip given step here
					continue;
				}
			}
			//as in item's page now here -> collect all necessary data
			$itemHTML = self::getProductHTML($output);
			//check $itemHTML for debugging now, to see why it can't collect data
			if($itemHTML == false)
			{
				//just skip it at the moment for later fix
				echo "\$itemHTML happened to be false, check it later!\n";
				//now here we need to make sure, that step becomes visited (to skip)
				//and execute new statement
				//if skip is specified
				//don't throw error for some time
				if(GeneralPerformance::$numOfExceptions<30)
				{
					//wait for response time specified in General Peroformance
					GeneralPerformance::waitForResponse();
					//execute later here
					self::collectProducts($cookieIn, $resourceSaveLocationIn, $spider, $sleepIn);
				}
				else
				{
					//make sure to annulate num of exceptions
					GeneralPerformance::resetWaitForResponse();
					//make step visited
					//TODO ? how to set it visited here?
					$spider->setVisitedNextStep();
					//skip given step here
					continue;
				}
			}
			//if it went fine continue
			$itemName = self::getProductName($itemHTML);
			$itemDescription = self::getProductDescription($itemHTML);
			$itemPrice = self::getProductPrice($itemHTML);
			$itemSeller = self::getProductSellerName($itemHTML);
			$itemCountry = self::getProductShipCountry($itemHTML);
			$itemDistribution = self::getProductDistribution($output);
			//make sure to check pictures, or specify false if there is no image
			//TODO as not all of the pictures will be downloaded, maybe specify in db this? {version 2.0} 
			try 
			{
				$itemPictureModel = self::getProductImage($itemHTML);
			}
			catch (CollectorException $e)
			{
				$itemPictureModel = false;
			}
			//collect additional info from model class
			$itemCategory = key($model->getCategory());
			//TODO think, to make additional checks, if product has been updated? like an option additional? like price update? {version 2.0} 
			$itemModel = $model->getPath();
			$itemSubcategory = $model->getCategory()[$itemCategory];
			//make sure to have date stamp (supress warnings, as needed for the system ini file)
			$itemDateCollected = @date("Y-m-d");
			//check picture model -> if false then do not make any picture related manipulations (error 404)
			//like mentioned above, maybe specify this in website?
			//think now here how to construct database
			//check if name already exist in tblwebsite;
			//make database logic in here
			//so first make vendor
			//check if record already exist -> skip it
			if(!DatabaseSettings::existVendor($itemSeller, self::$websiteDomain))
			{
				DatabaseSettings::insertVendorValues($itemSeller, self::$websiteDomain);
			}
			//make category here
			//check category if already exist
			if(!DatabaseSettings::existCategory($itemCategory, self::$websiteDomain))
			{
				DatabaseSettings::insertCategoryValues($itemCategory, self::$websiteDomain);
			}
			//make subcategory in here
			//check if subcategory already exist
			if(!DatabaseSettings::existSubCategory($itemSubcategory, DatabaseSettings::pkCategory($itemCategory, self::$websiteDomain)))
			{
				DatabaseSettings::insertSubcategoryValues($itemSubcategory, 
						DatabaseSettings::pkCategory($itemCategory, self::$websiteDomain));
			}
			//insert product if there is no such model yet TO BE UPDATED LATER FOR CHECKING ADDITIONAL STUFF LIKE UPDATE ON EXISTING {version 2.0}
			if(!DatabaseSettings::existModel($itemModel))
			{
				DatabaseSettings::insertProductValues($itemName, $itemModel, $itemDescription, $itemCountry, $itemDistribution, 
						$itemPrice, DatabaseSettings::pkVendor($itemSeller, self::$websiteDomain), 
						DatabaseSettings::pkSubCategory($itemSubcategory, 
						DatabaseSettings::pkCategory($itemCategory, self::$websiteDomain)), 
						$itemDateCollected);
				//get name of the picuture model for saving
				$pictureName = self::getProductImageName($itemPictureModel);
				//TODO here this step must go up, if we want to specify, that picture was not able to be loaded in system
				//check if picture is available then load it into the system
				if($itemPictureModel!=false)
				{
					//for picture is required to save it in location specified e.g. perform resource method self
					$output = self::getResourceExec($cookieIn, $itemPictureModel, "http://".WebsiteCollector::$websiteDomain."/",
					$resourceSaveLocationIn.DIRECTORY_SEPARATOR.$pictureName, $spider->getProxy(), $spider->getBrowserName());
					//updating product picture, if it was collected (and make note, if there was a problem of downloading)
					if($output)
					{
						DatabaseSettings::updatePicture($itemModel, $pictureName);
					}
					else 
					{
						DatabaseSettings::updatePicture($itemModel, $pictureName." [NOT DOWNLOADED]");
					}
				}
			}
		 }
		return true;
	}

   /**
    * Method to check that all categories exist within menu
    * 
    * @param string[] $categoriesIn required.		represent categories to be scanned
    * @param HTML $outputIn required.				represents ordinary page output
    * @throws CollectorException if there is no such category as specified
    */
   public static function checkCategories($categoriesIn, $outputIn)
   {
	   	foreach ($categoriesIn as $category)
	   	{
	   		//check that each model exist in menu
	   		self::getLeftMenuModel($category, $outputIn);
	   	}
   }
}
