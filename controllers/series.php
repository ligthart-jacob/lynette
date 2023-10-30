<?php

function connect()
{
  $connection = new mysqli("localhost", "root", "", "lynette_db");
  if ($connection->connect_error)
  {
    die("Connection Failed: {$connection->connect_error}");
  }
  return $connection;
}

function create()
{
  $connection = connect();
  try 
  {
    $stmt = $connection->prepare("INSERT INTO `series` (`uuid`, `name`) VALUES (?, ?)");
    $stmt->bind_param("ss", $_POST["uuid"], $_POST["name"]);
    $stmt->execute();
    echo $_POST["uuid"];
  }
  catch (Exception $e)
  {
    if ($connection->errno == 1062)
    {
      $stmt = $connection->prepare("SELECT `uuid` FROM `series` WHERE `name` = ?");
      $stmt->bind_param("s", $_POST["name"]);
      $stmt->execute();
      $result = $stmt->get_result();
      echo $result->fetch_row()[0];
    }
  }
  finally
  {
    $connection->close(); 
  }
}

function view()
{
  $connection = connect();
  $result = $connection->query("SELECT `uuid`, `name` FROM `series` ORDER BY `name`");
  echo json_encode($result->fetch_all(MYSQLI_ASSOC));
  $connection->close();
}

switch ($_GET["action"] ?? "view")
{
  case "create": return create();
  case "view": return view();
}