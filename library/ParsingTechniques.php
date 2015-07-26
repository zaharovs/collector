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
/**
 * Given class to be used for parsing techniqes required for Collector to perform
 * 
 * @author Germans Zaharovs
 * @version 1.0
 */
class ParsingTechniques
{
	/**
	 * Constant to represent, that <tag|delimiter|etc...> to be included
	 * @var bool
	 */
	const INCLUDE_DELIM = INCL;
	
	/**
	 * Constant to represent, that <tag|delimiter|etc...> to be excluded
	 * @var bool
	 */
	const EXCLUDE_DELIM = EXCL;
	
	/**
	 * Constant to represent, that return string parsed BEFORE delimiter specified
	 * @var bool
	 */
	const BEFORE_DELIM = BEFORE;
	
	/**
	 * Constant to represent, that return string parsed AFTER delimiter specified
	 * @var unknown
	 */
	const AFTER_DELIM = AFTER;
	
	
	/**
	 * Get array of tags needed required
	 * 
	 * @param String $stringIn Specifies string, from which tags to be extracted
	 * @param String $delim Specifies start of the tag, which to be extracted
	 * @return array of Strings Specifies end of the tag to be extracted
	 */
	public static function getAllTags($stringIn, $delimIn, $endDelimIn)
	{
		return parse_array($stringIn, $delimIn, $endDelimIn);
	}
	
	/**
	 * Get specific attribute from the tag
	 * 
	 * @param String $tagIn Represents tag, from which attribute to be taken from
	 * @param String $attributeIn Specifies attribute value, which value is needed
	 * @return string Value of the attribute specified
	 */
	public static function getAttribute($tagIn, $attributeIn)
	{
		return get_attribute($tagIn, $attributeIn);
	}
	
	/**
	 * Method to manipulate split string function, delimiter specifies first split point,
	 * 			 other parameters determine how parsing should flow.
	 * 
	 * @param String $stringIn required.		String to be manipulated
	 * @param String $delimitIn required.		Delimiter point
	 * @param Boolean $boolPos required.		constants: BEFORE || AFTER are to be entered for before string or after
	 * @param Boolean $boolIncl required.		constants: INCLUDE || EXCLUDE to be entered for include delimeter itself, or not
	 * @throws CollectorException if any parameter entered is not accepting type
	 * @return string Parsed string
	 */
	public static function splitString($stringIn, $delimitIn, $boolPos, $boolIncl)
	{
		//check all types
		if(!is_string($stringIn))
		{
			throw new CollectorException("Input value for \$stringIn must be of type string!");
		}
		if(!is_string($delimitIn))
		{
			throw new CollectorException("Input value for delimiter parameter must be of type string");
		}
		if(!is_bool($boolIncl))
		{
			throw new CollectorException("Input value for \$boolIncl must be of type boolean");
		}
		if(!is_bool($boolPos))
		{
			throw new CollectorException("Input value for \$boolPos must be of type boolean");
		}
		//return parsed string here
		return split_string($stringIn, $delimitIn, $boolPos, $boolIncl);
	}
	
	/**
	 * Method for returning specific tag [including or excluding tag itself] value
	 * 
	 * @param string $stringIn required.	Text, from which tag to be extracted
	 * @param string $opTag required.		Opening tag
	 * @param string $clTag required.		Closing tag
	 * @param boolean $boolIn required.		Constant INCL_DELIM, if tags to be included in return, EXCL_DELIM -> otherwise
	 * @return string parsed by parameter
	 */
	public static function returnValue($stringIn, $opTag, $clTag, $boolIn)
	{
		return  return_between($stringIn, $opTag, $clTag, $boolIn);
	}
	
}