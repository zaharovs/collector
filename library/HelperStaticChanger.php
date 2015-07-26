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
 * Helper class for changing static properties for classes of collector
 * 
 * @author Germans Zaharovs
 * @version 1.0
 */
class HelperStaticChanger
{
	/**
	 * Helper method for changing  static properties of the class
	 * 
	 * @param string $classNameIn required.			name of the class
	 * @param string $staticPropertyIn required.	name of the static property
	 * @param mixed $newValueIn required.			new value for the static property
	 * @throws \ReflectionException if there is problems with finding class name specified, or property 
	 * 			given
	 */
	public static function changeStaticProperty($classNameIn, $staticPropertyIn, $newValueIn)
	{
		$reflectionClass = new \ReflectionClass($classNameIn);
		$reflectionClass->setStaticPropertyValue($staticPropertyIn, $newValueIn);
	}
}