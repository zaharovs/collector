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
/**
 * Interface to set cURL connection to TOR proxy network 
 * 							(Spider specifically for Collector programme)
 * 
 * @author Germans Zaharovs
 * @version 1.0
 */
interface TorAble
{
	/**
	 * Setter for proxy string 
	 * @param String $proxyIn variable, representing proxy settings
	 */
	public function setProxy($proxyIn);
	
	/**
	 * Getter for proxy property
	 * @return String proxy property 
	 */
	public function getProxy();
	
	/**
	 * Function, to set Spider to Tor connection, applying all the settings
	 * provided earlier
	 * @param cURL $chIn required. 		instance, which requires to be in TOR network
	 * @throws CollectorException If proxy settings have not been set
	 */
	public function setTor($chIn);
	
	/**
	 * Function, which supposed to start tor service 
	 * 
	 * @param String $torLocation required. 		Specifies direction to tor
	 * @return True, if process has been started, False otherwise
	 */
	public function startTor($torLocation);
	
	/**
	 * Function, which supposed to stop tor service
	 * 
	 * @throws CollectorException, if no process of Tor is running
	 */
	public function cancelTor();
}