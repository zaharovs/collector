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
 * Class for checking for checking peroformance of the Collector
 * 
 * @author Germans Zaharovs
 * @version 1.0
 */
class GeneralPerformance
{
	/**
	 * stamp time 
	 * @var time
	 */
	public static $currentStamp=null;
	
	/**
	 * Collect items to be scanned within script
	 * @var int
	 */
	public static $scannedModels=null;
	
	
	/**
	 * Setter method for making statistics of how long script is running
	 * @param int $numOfModells required.
	 * @throws CollectorException If parameter is not of type integer
	 */
	public static function setNumOfModells($numOfModells)
	{
		//check that numOfModels to be numeric
		if(!is_numeric($numOfModells))
		{
			throw new CollectorException("Parameter for models to be scanned is to be of type integer only! Please re-check!");
		}
		HelperStaticChanger::changeStaticProperty("zaharovs\collector\GeneralPerformance", "scannedModels", $numOfModells);
	}
	
	/**
	 * Simple method to make difference between times
	 * @return int (time difference)
	 * @throws CollectorException if there was no time set before
	 */
	public static function calculateTime()
	{
		//throw exception if there is no time set before
		if(self::$currentStamp==null)
		{
			throw new CollectorException("You must firstly set time to be stamped! use setCurrentTime()");
		}
		$currentTime= time();
		$timeDifference = $currentTime-self::$currentStamp;
		return $timeDifference;
	}
	
	/**
	 * Setter for current time stamp
	 */
	public static function setCurrentTime()
	{
		HelperStaticChanger::changeStaticProperty("zaharovs\collector\GeneralPerformance", "currentStamp", time());
	}
	
}