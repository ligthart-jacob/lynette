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

function getSort()
{
  $order = strtoupper($_GET["order"] ?? "ASC");
  if ($order != "DESC" && $order != "ASC") $order = "ASC";
  switch ($_GET["sort"] ?? "new")
  {
    case "name": return "`characters`.`name` $order";
    case "series": return "`series`.`name` $order, `characters`.`name`";
    case "new": return "`characters`.`id` $order";
    case "obtained": return "`characters`.`obtained` $order";
  }
}

function create()
{
  $connection = connect();
  // Execute script that resizes images
  $path = shell_exec("python ./../scripts/trim.py {$_POST['image']}");
  // Insert the character
  $stmt = $connection->prepare("INSERT INTO `characters` (`name`, `image`, `seriesId`) 
    VALUES (?, ?, (SELECT `id` FROM `series` WHERE `uuid` = ?));");
  $stmt->bind_param("sss", $_POST["characterName"], $path, $_POST["series"]);
  $stmt->execute();
  // Return the image path
  echo $path;
  // Close the connection
  $connection->close();
}

function view()
{
  $offset = $_GET["offset"] ?? 0;
  $amount = $_GET["amount"] ?? 30;
  $series = $_GET["series"] ?? false;
  $sort = getSort();

  if ($series == "null") $series = false;
  
  $connection = connect();

  if ($series)
  {
    $stmt = $connection->prepare("SELECT 
      `characters`.`uuid`, 
      `characters`.`name`,
      `characters`.`image`,
      `characters`.`obtained`,
      `series`.`name` as `series`,
      `series`.`uuid` as `seriesUuid`
      FROM `characters` 
      INNER JOIN `series` ON `characters`.`seriesId` = `series`.`id`
      WHERE `series`.`uuid` = ?
      ORDER BY $sort
      LIMIT ?, ?"
    );
    $stmt->bind_param("sii", $series, $offset, $amount);
  }
  else
  {
    $stmt = $connection->prepare("SELECT 
      `characters`.`uuid`, 
      `characters`.`name`,
      `characters`.`image`,
      `characters`.`obtained`,
      `series`.`name` as `series`,
      `series`.`uuid` as `seriesUuid`
      FROM `characters`
      INNER JOIN `series` ON `characters`.`seriesId` = `series`.`id`
      ORDER BY $sort
      LIMIT ?, ?"
    );
    $stmt->bind_param("ii", $offset, $amount);
  }
  $stmt->execute();
  $result = $stmt->get_result();
  echo json_encode($result->fetch_all(MYSQLI_ASSOC));
  $connection->close();
}

function obtain()
{
  $connection = connect();
  $stmt = $connection->prepare("UPDATE `characters` SET `obtained` = ? WHERE `uuid` = ?");
  $stmt->bind_param("is", $_GET["obtained"], $_GET["uuid"]);
  $stmt->execute();
  $connection->close();
}

function remove()
{
  $connection = connect();
  $stmt = $connection->prepare("DELETE FROM `characters` WHERE `uuid` = ?");
  $stmt->bind_param("s", $_POST["uuid"]);
  $stmt->execute();
  $connection->close();
  $path = $_SERVER['DOCUMENT_ROOT'] . parse_url($_POST["image"], PHP_URL_PATH);
  if (file_exists($path)) unlink($path);
}

switch ($_GET["action"] ?? "view")
{
  case "create": return create();
  case "view": return view();
  case "obtain": return obtain();
  case "remove": return remove();
}