<?php
  
$connection = new mysqli("localhost", "root", "", "lynette_db");

$result = $connection->query("SELECT `image` FROM `characters` ORDER BY `image`");


function onlyName($result)
{
  return str_replace("/cards/", "", $result[0]); 
}

$results = array_map("onlyName", $result->fetch_all());

$local = array_diff(scandir("./cards"), [".", ".."]);

$diff = array_diff($local, $results);

foreach ($diff as $image)
{
  echo $image . "<br>";
}


$connection->close();