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
 * Given class to be used for helping start TOR service independently {version 2.0}
 * some thoughts: use threads, to see if it has been exited, and notify programme
 * 
 * @author Germans Zaharovs
 * @version 1.0
 */
class ExecClass 
{
	/**
	 * @return true, if process has been finished, once exits from TOR client (under development) 
	 * 			{version 2.0}
	 */
	function process()
	{
		return true;
	}
	
}