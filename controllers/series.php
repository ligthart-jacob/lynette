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
  $stmt = $connection->prepare("INSERT INTO `series` (`uuid`, `name`) VALUES (?, ?)");
  $stmt->bind_param("ss", $_POST["uuid"], $_POST["seriesName"]);
  $stmt->execute();
  $connection->close();
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