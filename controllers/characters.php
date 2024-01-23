<?php

include_once "./../config.php";

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
  $file = $_FILES ? $_FILES["image"]["tmp_name"] : $_POST["image"];
  $extension = explode(".", $_FILES ? $_FILES["image"]["name"] : $_POST["image"])[1];
  $connection = connect();
  // Execute script that resizes images
  $path = trim(shell_exec("python ./../scripts/trim.py {$file} .{$extension}"));
  // Insert the character
  $stmt = $connection->prepare("INSERT INTO `characters` (`name`, `image`, `seriesId`) 
    VALUES (?, ?, (SELECT `id` FROM `series` WHERE `slug` = ?));");
  $stmt->bind_param("sss", $_POST["name"], $path, $_POST["series"]);
  $stmt->execute();
  // Close the connection
  $connection->close();
}

function view()
{
  $offset = $_GET["offset"] ?? 0;
  $amount = $_GET["amount"] ?? 15;
  $series = $_GET["series"] ?? false;
  $search = $_GET["search"] ?? false;
  $sort = getSort();

  if ($series == "null") $series = false;
  if ($search == "null") $search = false;
  
  $connection = connect();

  if ($search && $series)
  {
    $search = "%" . $search . "%";
    $stmt = $connection->prepare("SELECT 
      `characters`.`uuid`, 
      `characters`.`name`,
      `characters`.`image`,
      `characters`.`obtained`,
      `series`.`name` as `series`,
      `series`.`uuid` as `seriesUuid`,
      `series`.`slug` as `slug`
      FROM `characters` 
      INNER JOIN `series` ON `characters`.`seriesId` = `series`.`id`
      WHERE `characters`.`name` LIKE ? AND `series`.`slug` = ?
      ORDER BY $sort
      LIMIT ?, ?"
    );
    $stmt->bind_param("ssii", $search, $series, $offset, $amount);
  }
  else if ($search)
  {
    $search = $search . "%";
    $stmt = $connection->prepare("SELECT 
      `characters`.`uuid`, 
      `characters`.`name`,
      `characters`.`image`,
      `characters`.`obtained`,
      `series`.`name` as `series`,
      `series`.`uuid` as `seriesUuid`,
      `series`.`slug` as `slug`
      FROM `characters` 
      INNER JOIN `series` ON `characters`.`seriesId` = `series`.`id`
      WHERE `characters`.`name` LIKE ?
      ORDER BY $sort
      LIMIT ?, ?"
    );
    $stmt->bind_param("sii", $search, $offset, $amount);
  } 
  else if ($series)
  {
    $stmt = $connection->prepare("SELECT 
      `characters`.`uuid`, 
      `characters`.`name`,
      `characters`.`image`,
      `characters`.`obtained`,
      `series`.`name` as `series`,
      `series`.`uuid` as `seriesUuid`,
      `series`.`slug`
      FROM `characters` 
      INNER JOIN `series` ON `characters`.`seriesId` = `series`.`id`
      WHERE `series`.`slug` = ?
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
      `series`.`uuid` as `seriesUuid`,
      `series`.`slug` as `slug`
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

function update()
{
  $connection = connect();
  // Execute script that resizes images
  if (filter_var($_POST["image"] ?? "", FILTER_VALIDATE_URL) || $_FILES) 
  {
    $file = $_FILES ? $_FILES["image"]["tmp_name"] : $_POST["image"];
    $extension = explode(".", $_FILES ? $_FILES["image"]["name"] : $_POST["image"])[1];
    // Remove the previous image if there is one
    if ($_POST["prevImage"]) removeImage($_POST['prevImage']);
    $_POST["image"] = trim(shell_exec("python ./../scripts/trim.py {$file} .{$extension} 2>&1"));
  }
  // Insert the character
  $stmt = $connection->prepare("UPDATE `characters` SET
    `name` = ?,
    `image` = ?,
    `seriesId` = (SELECT `id` FROM `series` WHERE `slug` = ?)
    WHERE `uuid` = ?
  ");
  $stmt->bind_param("ssss", $_POST["name"], $_POST["image"], $_POST["series"], $_POST["uuid"]);
  $stmt->execute();
  // Show the new image link
  echo $_POST["image"];
  // Close the connection
  $connection->close();
}

function removeImage($path)
{
  $path = $_SERVER['DOCUMENT_ROOT'] . $path;
  if (file_exists($path)) unlink($path);
}

function remove()
{
  $connection = connect();
  $stmt = $connection->prepare("DELETE FROM `characters` WHERE `uuid` = ?");
  $stmt->bind_param("s", $_POST["uuid"]);
  $stmt->execute();
  $connection->close();
  removeImage(parse_url($_POST["image"], PHP_URL_PATH));
}

switch ($_GET["action"] ?? "view")
{
  case "create": return create();
  case "view": return view();
  case "obtain": return obtain();
  case "remove": return remove();
  case "update": return update();
  case "test": return removeImage(parse_url($_POST["image"], PHP_URL_PATH));
}