<?php
// Copy this file as pdo.php, and enter your credentials below.
class PDOX {
  public static function getPDO() {
    	try
	{
	   $pdo = new PDO('mysql:host=localhost;port=13306;dbname=??????', 
	'username', 'password');
	   $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}  catch(Exception $ex)  {
	   die($ex->getMessage());
	}
	return $pdo;
  }
}
