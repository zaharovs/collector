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
 * Main exception for Collector programme
 * 
 * @author Germans Zaharovs
 * @version 1.0
 */
class CollectorException extends \Exception
{
	/**
	 * Will make optional override of main string of the exception.
	 * 
	 * @param string $mesageIn optional. 		Message to be seen if exception have been raised
	 * @param number $codeIn optional.			Code of the error, if requires track of what exact exc raised
	 */
	public function __construct($mesageIn = "Collector exception has been thrown", $codeIn = 1)
	{
		parent::__construct($mesageIn, $codeIn);
	}
}