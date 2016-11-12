<?php
echo "a";
include("Db.class.php");

$db = "Dbtest";
$dbs = "localhost";
$dbu = "dbtest";
$dbp = "dbtest";

$db = new Db($dbs, $dbu, $dbp, $db);

var_dump($db);
