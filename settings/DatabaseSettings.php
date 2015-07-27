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
 * This class provides all of the database settings needed for making 
 * collection of required website. Configuration file to be changed for 
 * specific user's needs. 
 * 
 * Some notes here, at later versions delete user's control over creating 
 * database itself, as it is counted as insecure. 
 * 
 * @author Germans Zaharovs
 * @version 1.0
 */
class DatabaseSettings
{
	/**
	 * Settings for database to be connected
	 * @var string[]
	 */
	public static $CONFIGURE = array (
			"DB_USERNAME" =>"root",
			"DB_PASSWORD" =>"password",
			"DB_NAME" => "collector_db",
			"DB_DRIVER" => "mysql:host=localhost"
	);
	
	/**
	 * Prepared statement for using in product insertion
	 * @var \PDOStatement
	 */
	public static $stmtProduct;
	
	/**
	 * Prepared statement for using in vendor insertion
	 * @var \PDOStatement
	 */
	public static $stmtVendor;
	
	/**
	 * Prepared statement for using in website insertion
	 * @var \PDOStatement
	 */
	public static $stmtWebsite;
	
	/**
	 * Prepared statement for using in category insertion
	 * @var \PDOStatement
	 */
	public static $stmtCategory;
	
	/**
	 * Prepared statement for using in subcategory insertion
	 * @var \PDOStatement
	 */
	public static $stmtSubCategory;
	
	/**
	 * Prepared statement for updating image in product table
	 * @var \PDOStatement
	 */
	public static $stmtUpdateImage;
	
	/**
	 * Connection variable, specifying PDO 
	 * @var \PDOStatement
	 */
	public static $pdoConnection;
	

	
	
	/**
	 * Statement for checking that connection to database exist. In case there is no 
	 * such database, the code will create it for user (**make sure in next verison make 
	 * more sophisticated checking, as insecure)
	 * 
	 * @throws CollectorException if connection was not been able to establish
	 */
	public static function createDatabase()
	{
		//create statement for creating required database;
		//firstly connect to database, and check that all details are correct
		$connection = null;
		try 
		{
			$connection = new \PDO(self::$CONFIGURE['DB_DRIVER'], self::$CONFIGURE['DB_USERNAME'], self::$CONFIGURE['DB_PASSWORD']);
			//set for given connection of PDO handle exception mode (instead of quite inform mode)
			$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			//set static connection to given connection then
			HelperStaticChanger::changeStaticProperty(__CLASS__, "pdoConnection", $connection);
			//prepare statements for further execution
			self::prepareStatements($connection);
		}
		catch (\PDOException $e)
		{
			throw new CollectorException("Was not able to connect to ".self::$CONFIGURE['DB_DRIVER']." following error: ".$e->getMessage());
		}
		try 
		{
			//try to connect to required database
			$connection->query("use ".self::$CONFIGURE['DB_NAME']);
			//if it is successful -> then everything is just fine, and database can be used
			//however it is required to check all of the tables exist? At later stage!
		}
		catch (\PDOException $e)
		{
			//we know here that 
			self::helpBuildDatabase($connection);
		}
	}
	
	/**
	 * Helper function to build database required for collector
	 * 
	 * @param PDO $connectionIn required. 		Connection to the database.
	 */
	private static function helpBuildDatabase(\PDO $connectionIn)
	{
		//create database firstly
		self::makeDatabase($connectionIn);
		//and now build all required tables in sequence (respecting foreign keys)
		self::buildWebsiteTable($connectionIn);
		self::buildVendorTable($connectionIn);
		self::buildCategoryTable($connectionIn);
		self::buildSubCategoryTable($connectionIn);
		self::buildProductTable($connectionIn);
		//prepare statements for further execution 
		self::prepareStatements($connectionIn);
	}
	
	/**
	 * Method to create database, specified in configuration file
	 *
	 * @param \PDO $connectionIn required.			PHP Data Object, connected to required DBMS
	 * @throws CollectorException In case database can't be created
	 */
	private static function makeDatabase(\PDO $connectionIn)
	{
		//try oo approach
		try 
		{
			$connectionIn->exec("CREATE DATABASE ".self::$CONFIGURE['DB_NAME']);
			$connectionIn->exec("USE ".self::$CONFIGURE['DB_NAME']);
		}
		catch (\PDOException $e)
		{
			throw new CollectorException("Database ".self::$CONFIGURE['DB_NAME']." can't be created");
		}
	}
	
	/**
	 * Helper method for creating webiste table in db.
	 * 
	 * @param \PDO $connectionIn
	 */
	private static function buildWebsiteTable(\PDO $connectionIn)
	{
		$sql = "CREATE TABLE tblWebsite (
					website_id				VARCHAR(50)  PRIMARY KEY,
					website_url				VARCHAR(100) NOT NULL, 
					website_description		VARCHAR(500)
				)";
		
		try 
		{
			$connectionIn->exec($sql);
		}
		catch (\PDOException $e)
		{
			$string = $e->getMessage();
			throw CollectorException($string);
		}
	}
	
	/**
	 * Helper method for creating vendor's table in database.
	 * 
	 * @param \PDO $connectionIn required.		Database connection.
	 */
	private static function buildVendorTable(\PDO $connectionIn)
	{
		$sql = '
				CREATE TABLE tblVendor (
					vendor_id				INT(10) AUTO_INCREMENT PRIMARY KEY,
					vendor_name				VARCHAR(50) NOT NULL,
					vendor_description		VARCHAR(500),
					website_id				VARCHAR(50) NOT NULL,
					FOREIGN KEY(website_id) REFERENCES tblWebsite(website_id),
					CONSTRAINT unique_vendname_webid UNIQUE (vendor_name, website_id)
				)';
		try 
		{
			$connectionIn->exec($sql);
		}
		catch (\PDOException $e)
		{
			$string = $e->getMessage();
			throw CollectorException($string);
		}
	}
	
	/**
	 * Helper method for creating product's table in database.
	 * 
	 * @param \PDO $connectionIn required. 			Database connection
	 * @throws CollectorException if any PDO exception did happen
	 */
	private static function buildProductTable(\PDO $connectionIn)
	{
		$sql = "
				CREATE TABLE tblProduct (
					product_id				INT(20)	AUTO_INCREMENT PRIMARY KEY,
					product_model			VARCHAR(200) NOT NULL,
					product_name			VARCHAR(100) NOT NULL,
				    product_picture			VARCHAR(100),
					product_description		TEXT NOT NULL,
					product_origin			VARCHAR(50) NOT NULL,
					product_postage			VARCHAR(50) NOT NULL,
					product_price			VARCHAR(50) NOT NULL,
					vendor_id				INT(10) NOT NULL,
					subCategory_id			INT(10) NOT NULL,
					time_stamp				DATE NOT NULL,
					FOREIGN KEY (vendor_id) REFERENCES tblVendor(vendor_id),
					FOREIGN KEY (subCategory_id) REFERENCES tblSubCategory(subCategory_id),
					CONSTRAINT unique_model_product UNIQUE (product_model)
				)
				";
		try 
		{
			$connectionIn->exec($sql);
		}
		catch(\PDOException $e)
		{
			$string = $e->getMessage();
			throw CollectorException($string);
		}
	}
	
	/**
	 * Helper method for creating Category table. 
	 * 
	 * @param \PDO $connectionIn required.		Connection to database
	 * @throws CollectorException if there was anything wrong
	 */
	private static function buildCategoryTable(\PDO $connectionIn)
	{
		$sql = "
				CREATE TABLE tblCategory (
					category_id				INT(10)	AUTO_INCREMENT PRIMARY KEY,
					category_name			VARCHAR(50) NOT NULL,
					category_description	VARCHAR(500),
					website_id				VARCHAR(50) NOT NULL,
					FOREIGN KEY(website_id) REFERENCES tblWebsite(website_id),
					CONSTRAINT unique_catname_webid UNIQUE(category_name, website_id)
				)";
		try 
		{
			$connectionIn->exec($sql);
		}
		catch (\PDOException $e)
		{
			$string = $e->getMessage();
			throw CollectorException($string);
		}		
	}
	
	/**
	 * Helper method for creating SubCategories table
	 * 
	 * @param \PDO $connectionIn required. 			Specifies connection to database.
	 * @throws CollectorException if there anything happened during creation.
	 */
	private static function buildSubCategoryTable(\PDO $connectionIn)
	{
		$sql = "
				CREATE TABLE tblSubCategory (
					subCategory_id			INT(10) AUTO_INCREMENT PRIMARY KEY,
					subCategory_name		VARCHAR(50) NOT NULL,
					subCategory_description VARCHAR(500),
					category_id				INT(10) NOT NULL,
					FOREIGN KEY(category_id) REFERENCES tblCategory(category_id),
					CONSTRAINT unique_subname_catid UNIQUE (subCategory_name, category_id)
				)
				";
		try 
		{
			$connectionIn->exec($sql);
		}
		catch(\PDOException $e)
		{
			$string = $e->getMessage();
			throw CollectorException($string);
		}
	}
	
	/**
	 * Helper method for preparing statements to properties for different requierement db manipulation.
	 * 
	 * @param \PDO $connectionIn required. 			Connection to database. 
	 */
	private static function prepareStatements(\PDO $connectionIn)
	{
		//product statement
		$stmtProduct = $connectionIn->prepare("INSERT INTO tblProduct (product_name, product_model, 
												product_description, product_origin, product_postage,
												product_price, vendor_id, subCategory_id, time_stamp)
												VALUES (:product_name, :product_model, :product_description, :product_origin, 
												:product_postage, :product_price, :vendor_id, :subCategory_id, :time_stamp)");
		//now change here static variable
		HelperStaticChanger::changeStaticProperty(__CLASS__, "stmtProduct", $stmtProduct);
		
		//vendor statement
		$stmtVendor = $connectionIn->prepare("INSERT INTO tblVendor (vendor_name, vendor_description, 
					website_id) VALUES (:vendor_name, :vendor_description, :website_id)");
		//now change here static variable
		HelperStaticChanger::changeStaticProperty(__CLASS__, "stmtVendor", $stmtVendor);
		
		//now change here tblcategory statement
		$stmtCategory = $connectionIn->prepare("INSERT INTO tblCategory (category_name, category_description, website_id) 
				VALUES (:category_name, :category_description, :website_id)");
		HelperStaticChanger::changeStaticProperty(__CLASS__, "stmtCategory", $stmtCategory);
		
		//now change here tblSubCategory statement
		$stmtSubCategory = $connectionIn->prepare("INSERT INTO tblSubCategory (subCategory_name, subCategory_description, category_id) 
					VALUES (:subCategory_name, :subCategory_description, :category_id)");
		HelperStaticChanger::changeStaticProperty(__CLASS__, "stmtSubCategory", $stmtSubCategory);
		
		//now change here tblwebsite
		$stmtWebsite = $connectionIn->prepare("INSERT INTO tblWebsite (website_id, website_url, website_description)
					VALUES (:website_id, :website_url, :website_description)");
		HelperStaticChanger::changeStaticProperty(__CLASS__, "stmtWebsite", $stmtWebsite);
		
		//for additional information requires to save images to database (for retrieving them)
		$stmtUpdateImage = $connectionIn->prepare("UPDATE tblProduct SET product_picture = :product_picture WHERE product_model = :product_model");
		HelperStaticChanger::changeStaticProperty(__CLASS__, "stmtWebsite", $stmtUpdateImage);
	}
	
	/**
	 * Method for updating picture of the product in database
	 * 
	 * @param string $productPicture required.		String representing picture name & locationl
	 * @param string $productModel required.		String representing model of the product, where picture to be updated.
	 * @throws CollectorException	if there will any exception happening during updating picture.
	 */
	public static function updatePicture ($productPicture, $productModel)
	{
		try 
		{
			self::$stmtUpdateImage->bindParam("product_picture", $productPicture);
			self::$stmtUpdateImage->bindParam("product_model", $productModel);
			self::$stmtUpdateImage->execute();
		}
		catch (\PDOException $e)
		{
			$string = $e->getMessage();
			throw new CollectorException($string);
		}
	}
	
	/**
	 * Method to insert value into Product table
	 * 
	 * @param unknown $productName
	 * @param string $productModel required.		Represents model of the product
	 * @param string $productDesc required.			Represents product description.
	 * @param string $productOrigin	required.		Represents where product is came from
	 * @param string $productPostage required.		Represents, where product is selling to.
	 * @param string $productPrice	required.		Represents price of the product 
	 * @param string $vendorID required.			Represent seller's id of the product
	 * @param string $subCategoryID requiered.		Represents subCategoryId of the product
	 * @param string $timeStamp	required.			Time when product has been collected.
	 * @throws CollectorException if there was anything wrong with inserting value
	 */
	public static function insertProductValues($productName, $productModel, $productDesc,
					$productOrigin, $productPostage, $productPrice, $vendorID, $subCategoryID, $timeStamp)
	{
		try
		{
			self::$stmtProduct->bindParam('product_name', $productName, \PDO::PARAM_STR);
			self::$stmtProduct->bindParam('product_model', $productModel, \PDO::PARAM_STR);
			self::$stmtProduct->bindParam('product_description', $productDesc, \PDO::PARAM_STR);
			self::$stmtProduct->bindParam('product_origin', $productOrigin, \PDO::PARAM_STR);
			self::$stmtProduct->bindParam('product_postage', $productPostage, \PDO::PARAM_STR);
			self::$stmtProduct->bindParam('product_price', $productPrice, \PDO::PARAM_STR);
			self::$stmtProduct->bindParam('vendor_id', $vendorID, \PDO::PARAM_STR);
			self::$stmtProduct->bindParam('subCategory_id', $subCategoryID, \PDO::PARAM_STR);
			self::$stmtProduct->bindParam('time_stamp', $timeStamp, \PDO::PARAM_STR);
			//execute statement here
			self::$stmtProduct->execute();
		}
		catch (\PDOException $e)
		{
			$string = $e->getMessage();
			throw new CollectorException($string);
		}	
	}
	
	/**
	 * Method for inserting category values.
	 * 
	 * @param string $categoryName required.			Category name.
	 * @param string $categoryDescription required.		Category description.
	 * @param string $websiteID							Website's foreign key id
	 * @throws CollectorException	if there was a problem with inserting values
	 */
	public static function insertCategoryValues ($categoryName, $websiteID, $categoryDescription="NO DESCRIPTION")
	{
		try
		{
			self::$stmtCategory->bindParam('category_name',$categoryName, \PDO::PARAM_STR);
			self::$stmtCategory->bindParam('category_description',$categoryDescription, \PDO::PARAM_STR);
			self::$stmtCategory->bindParam('website_id',$websiteID, \PDO::PARAM_STR);
			self::$stmtCategory->execute();
		}
		catch (\PDOException $e)
		{
			$string = $e->getMessage();
			throw new CollectorException($string);
		}
	}
	
	/**
	 * Method to insert record into subcategory.
	 * 
	 * @param string $subCategoryName required.				Represents sub category name
	 * @param string $subCategoryDescription required.		Represents sub category description
	 * @param string $categoryID required.					Represents category id foreign key
	 * @throws CollectorException if there was a problem with inserting subcategory values.
	 */
	public static function insertSubcategoryValues($subCategoryName, $categoryID, $subCategoryDescription="NO DESCRIPTION")
	{
		try
		{
			self::$stmtSubCategory->bindParam('subCategory_name',$subCategoryName, \PDO::PARAM_STR);
			self::$stmtSubCategory->bindParam('subCategory_description',$subCategoryDescription, \PDO::PARAM_STR);
			self::$stmtSubCategory->bindParam('category_id',$categoryID, \PDO::PARAM_STR);
			self::$stmtSubCategory->execute();
		}
		catch (\PDOException $e)
		{
			$string = $e->getMessage();
			throw new CollectorException($string);
		}
	}
	
	/**
	 * Method for inserting vendor's record
	 * 
	 * @param string $vendor_name required.				Represents vendor name.
	 * @param string $vendor_description required.		Represents vendor's description.
	 * @param string $website_id required.				Represents website's id foreign key.
	 * @throws CollectorException if there was a problem with inserting record
	 */
	public static function insertVendorValues($vendor_name, $website_id, $vendor_description="NO DESCRIPTION")
	{
		try
		{
			self::$stmtVendor->bindParam('vendor_name',$vendor_name, \PDO::PARAM_STR);
			self::$stmtVendor->bindParam('vendor_description',$vendor_description, \PDO::PARAM_STR);
			self::$stmtVendor->bindParam('website_id',$website_id, \PDO::PARAM_STR);
			self::$stmtVendor->execute();
		}
		catch (\PDOException $e)
		{
			$string = $e->getMessage();
			throw new CollectorException($string);
		}
	}
	
	/**
	 * Method for inserting website's record
	 * 
	 * @param string $websiteID required.			represents name of the website
	 * @param string $websiteURL required.			represents webisites url
	 * @param string $websiteDescr required.		represents description of the website.
	 * @throws CollectorException if there was a problem with inserting a record.
	 */
	public static function insertWebsiteValues ($websiteID, $websiteURL, $websiteDescr="NO DESCRIPTION")
	{
		try
		{
			self::$stmtWebsite->bindParam('website_id',$websiteID, \PDO::PARAM_STR);
			self::$stmtWebsite->bindParam('website_url',$websiteURL, \PDO::PARAM_STR);
			self::$stmtWebsite->bindParam('website_description',$websiteDescr, \PDO::PARAM_STR);
			self::$stmtWebsite->execute();
		}
		catch (\PDOException $e)
		{
			$string = $e->getMessage();
			throw new CollectorException($string);
		}
	}
	
	/**
	 * Method for extracting primary key of vendor 
	 * 
	 * @param string $vendorName required.			Specifies vendors name (as composite keys actually, combined with website_id)
	 * @param string $websiteID required.			Specifies website's domain, for which vendor_id to be extracted
	 * @return string primary key, or unset, if none
	 */
	public static function pkVendor($vendorName, $websiteID)
	{
		//prepare statement for retreiving pk from vendor
		$connection = self::$pdoConnection;
		$statement = $connection->prepare("SELECT vendor_id FROM tblVendor WHERE vendor_name=:vendor_name AND website_id=:website_id");
		$statement->bindParam('vendor_name',$vendorName,\PDO::PARAM_STR);
		$statement->bindParam('website_id',$websiteID,\PDO::PARAM_STR);
		$statement->execute();
		//get id
		$result = $statement->fetch(\PDO::FETCH_ASSOC);
		//return it
		return $result['vendor_id'];
	}
	
	/**
	 * Method for getting primary key of the category.
	 * @param string $categoryName required.	Represents category name.
	 * @param string $websiteID required.		Represents website id.
	 * @return string Category id
	 */
	public static function pkCategory($categoryName, $websiteID)
	{
		$connection = self::$pdoConnection;
		$statement = $connection->prepare("SELECT category_id FROM tblCategory WHERE category_name=:category_name AND website_id=:website_id");
		$statement->bindParam('category_name',$categoryName,\PDO::PARAM_STR);
		$statement->bindParam('website_id',$websiteID,\PDO::PARAM_STR);
		$statement->execute();
		//get id
		$result = $statement->fetch(\PDO::FETCH_ASSOC);
		//return
		return $result['category_id'];
	}
	
	/**
	 * Method for retreiving primary key of subCategory
	 * 
	 * @param string $subcategoryName required.		Represents subcategory name.
	 * @param string $categoryID required.			Represents subcategory id.
	 * @return subcategory_id (or unset)
	 */
	public static function pkSubCategory($subcategoryName, $categoryID)
	{
		$connection = self::$pdoConnection;
		$statement = $connection->prepare("SELECT subCategory_id FROM tblSubCategory WHERE subCategory_name=:subCategory_name AND category_id=:category_id");
		$statement->bindParam('subCategory_name',$subcategoryName,\PDO::PARAM_STR);
		$statement->bindParam('category_id',$categoryID,\PDO::PARAM_STR);
		$statement->execute();
		//get id
		$result = $statement->fetch(\PDO::FETCH_ASSOC);
		//return
		return $result['subCategory_id'];
	}
	
	/**
	 * Method to check whether website already exists within table
	 * 
	 * @param string $websiteID required.
	 * @return boolean True if record exist, else false
	 */
	public static function existWebsite($websiteID)
	{
		$connection = self::$pdoConnection;
		$statement = $connection->prepare("SELECT website_id FROM tblWebsite WHERE website_id = :website_id");
		$statement->bindParam('website_id',$websiteID,\PDO::PARAM_STR);
		//execute
		$statement->execute();
		//get id
		$result = $statement->fetch(\PDO::FETCH_ASSOC);
		if($result==false)
		{
			return false;
		}
		return true;
	}
	
	/**
	 * Method to check that seller is already exist
	 * 
	 * @param string $vendorName required.
	 * @param string $websiteID required.
	 */
	public static function existVendor($vendorName, $websiteID)
	{
		$connection = self::$pdoConnection;
		$statement = $connection->prepare("SELECT vendor_id FROM tblVendor WHERE vendor_name = :vendor_name AND website_id=:website_id");
		// 		$statement = $connection->query("SELECT * FROM tblWebsite");
		$statement->bindParam('website_id',$websiteID,\PDO::PARAM_STR);
		$statement->bindParam('vendor_name',$vendorName,\PDO::PARAM_STR);
		//execute
		$statement->execute();
		//get id
		$result = $statement->fetch(\PDO::FETCH_ASSOC);
		if($result==false)
		{
			return false;
		}
		//return
		return true;
		
	}
	
	/**
	 * Method to check if category exist
	 * 
	 * @param string $categoryName required.
	 * @param stirng $websiteID required.
	 * @return boolean True, if category exists, False otherwise
	 */
	public static function existCategory($categoryName, $websiteID)
	{
		$connection = self::$pdoConnection;
		$statement = $connection->prepare("SELECT category_id FROM tblCategory WHERE category_name = :category_name AND website_id=:website_id");
		$statement->bindParam('website_id',$websiteID,\PDO::PARAM_STR);
		$statement->bindParam('category_name',$categoryName,\PDO::PARAM_STR);
		//execute
		$statement->execute();
		//get id
		$result = $statement->fetch(\PDO::FETCH_ASSOC);
		//return
		if($result==false)
		{
			return false;
		}
		return true;
	}
	
	/**
	 * Method for checking that subcategory exist within database
	 * 
	 * @param string $subCategoryName required.
	 * @param strign $categoryID required.
	 * @return boolean True, if subcategory exists, false otherwise
	 */
	public static function existSubCategory($subCategoryName, $categoryID)
	{
		$connection = self::$pdoConnection;
		$statement = $connection->prepare("SELECT subCategory_id FROM tblSubCategory WHERE subcategory_name = :subcategory_name AND category_id=:category_id");
		$statement->bindParam('category_id',$categoryID,\PDO::PARAM_STR);
		$statement->bindParam('subcategory_name',$subCategoryName,\PDO::PARAM_STR);
		//execute
		$statement->execute();
		//get id
		$result = $statement->fetch(\PDO::FETCH_ASSOC);
		//return
		if($result==false)
		{
			return false;
		}
		return true;
	}
	
	/**
	 * Method for checking that model exists within tblCategory
	 * 
	 * @param string $modelIn required.			model to be checked, whether it exists within tblproduct
	 */
	public static function existModel($modelIn)
	{
		$connection = self::$pdoConnection;
		$statement = $connection->prepare("SELECT product_id FROM tblProduct WHERE product_model = :product_model");
		$statement->bindParam('product_model',$modelIn,\PDO::PARAM_STR);
		//execute
		$statement->execute();
		//get id
		$result = $statement->fetch(\PDO::FETCH_ASSOC);
		//return
		if($result==false)
		{
			return false;
		}
		return true;
	}
	
	/**
	 * Method to get array of all models, which exist at current database
	 * 
	 * @return Models[]
	 */
	public static function getModels()
	{
		$connection = self::$pdoConnection;
		$statement = $connection->query("SELECT product_model FROM tblProduct");
		$result = $statement->fetchAll(\PDO::FETCH_ASSOC);
		if($result==false)
		{
			return false;
		}
		//remake array
		$return_arr= array();
		foreach($result as $part)
		{
			$return_arr[]=$part['product_model'];
		}
		return $return_arr;
	}
}