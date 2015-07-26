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
 * Model class will help to keep track of models of the website and hierarchy structures
 * of the items. This is a helper class, therefore for less requirements by website to 
 * get data from parsing raw information of website (as it may change over time, but this will not)
 * 
 * 
 * @author Germans Zaharovs
 * @version 1.0
 *
 */
class Model
{
	/**
	 * Property for representing category name of the current model
	 * @var String
	 */
	private $category;
	
	/**
	 * Property for representing subcategory name of the current model
	 * @var String
	 */
	private $subCategory;
	
	/**
	 * Property of model itself, for run
	 * @var string
	 */
	private $model;
	
	/**
	 * Constructor for Model class
	 * 
	 * @param string $categoryIn required. 			Specifies category of the model
	 * @param string $subcategoryIn required. 		Specifies subcategory of the model
	 * @param string $modelIn required. 			Specifies model itself for Model class. 
	 */
	public function __construct($categoryIn, $subcategoryIn, $modelIn)
	{
		$this->setCategory($categoryIn);
		$this->setSubCategory($subcategoryIn);
		$this->setModel($modelIn);
	}
	
	/**
	 * Getter method for category
	 *
	 * @return string
	 */
	public function getCategory() 
	{
		return $this->category;
	}
	
	/**
	 * Setter method for category property
	 *
	 * @param string $category required.	
	 */
	public function setCategory($category) 
	{
		$this->category = $category;
	}
	
	/**
	 * Getter method for subcategory
	 *
	 * @return string
	 */
	public function getSubCategory() 
	{
		return $this->subCategory;
	}
	
	/**
	 * Setter method for subCategory
	 *
	 * @param string $subCategory required.  	
	 */
	public function setSubCategory($subCategory) 
	{
		$this->subCategory = $subCategory;
	}
	
	/**
	 * Getter for model property
	 *
	 * @return string
	 */
	public function getModel() 
	{
		return $this->model;
	}
	
	/**
	 * Setter method for model property
	 *
	 * @param string $model required   	
	 */
	public function setModel($model) 
	{
		$this->model = $model;
	}
	
}