<?php

include_once "./../config.php";

function getSlug($string, $delimiter = "-")
{
  return str_replace(" ", $delimiter, trim(preg_replace("/\W+/", " ", strtolower($string))));
}

function create()
{
  $connection = connect();
  $slug = getSlug($_POST["name"]);
  try 
  {
    $stmt = $connection->prepare("INSERT INTO `series` (`name`, `slug`) VALUES (?, ?)");
    $stmt->bind_param("ss", $_POST["name"], $slug);
    $stmt->execute();
    echo $slug;
  }
  catch (Exception $e)
  {
    if ($connection->errno == 1062)
    {
      $stmt = $connection->prepare("SELECT `slug` FROM `series` WHERE `name` = ?");
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
  $result = $connection->query("SELECT `uuid`, `slug`, `name` FROM `series` ORDER BY `name`");
  echo json_encode($result->fetch_all(MYSQLI_ASSOC));
  $connection->close();
}

function update()
{
  $slug = getSlug($_POST["name"]);
  $connection = connect();
  $stmt = $connection->prepare("UPDATE `series` SET `name` = ?, `slug` = ? WHERE `slug` = ?");
  $stmt->bind_param("sss", $_POST["name"], $slug, $_POST["series"]);
  $stmt->execute();
  $connection->close();
}

function remove()
{
  $connection = connect();
  try
  {
    $stmt = $connection->prepare("DELETE FROM `series` WHERE `slug` = ?");
    $stmt->bind_param("s", $_POST["series"]);
    $stmt->execute();
  }
  catch (Exception $e)
  {
    if ($connection->errno == 1451)
    {
      http_response_code(405);
    }
  }
  finally
  {
    $connection->close();
  }
}

switch ($_GET["action"] ?? "view")
{
  case "create": return create();
  case "view": return view();
  case "update": return update();
  case "remove": return remove();
}