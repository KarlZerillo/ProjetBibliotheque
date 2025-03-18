<?php
$host = 'localhost';
$dbname = 'bibliotheque';
$username = 'root';
$password = '';
try
{
      $objet_PDO = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
}
catch (Exception $e)
{
      die("Impossible de se connecter à la base de données $dbname : " . $e->getMessage());
}
?>