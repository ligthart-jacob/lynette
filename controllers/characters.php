<?php

include_once "./../config.php";

function storeImage($url)
{
  $filename = uniqid(rand()) . ".png";
  $source = imagecreatefromwebp($url);
  $result = imagecreatetruecolor(225, 350);
  imagecopyresized($result, $source, 0, 0, 1, 1, 225, 350, 223, 348);
  imagepng($result, "./../cards/$filename");
  return "/cards/$filename";
}

function getSort()
{
  $order = strtoupper($_GET["order"] ?? "ASC");
  if ($order != "DESC" && $order != "ASC") $order = "ASC";
  switch ($_GET["sort"] ?? "new")
  {
    case "name": return "`characters`.`firstname` $order";
    case "series": return "`series`.`name` $order, `characters`.`firstname`";
    case "new": return "`characters`.`id` $order";
    case "obtained": return "`characters`.`obtained` $order";
  }
}

function create()
{
  $file = $_FILES ? $_FILES["image"]["tmp_name"] : $_POST["image"];
  $connection = connect();
  // Execute script that resizes images
  $path = storeImage($file);
  // Lastname
  $lastname = (bool)$_POST["lastname"] ? $_POST["lastname"] : null;
  // Insert the character
  $stmt = $connection->prepare("INSERT INTO `characters` (`firstname`, `lastname`, `image`, `seriesId`) 
    VALUES (?, ?, ?, (SELECT `id` FROM `series` WHERE `slug` = ?));");
  $stmt->bind_param("ssss", $_POST["firstname"], $lastname, $path, $_POST["series"]);
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
      CONCAT_WS(', ', `lastname`, `firstname`) AS `name`,
      `characters`.`image`,
      `characters`.`obtained`,
      `series`.`name` as `series`,
      `series`.`uuid` as `seriesUuid`,
      `series`.`slug` as `slug`
      FROM `characters` 
      INNER JOIN `series` ON `characters`.`seriesId` = `series`.`id`
      WHERE `characters`.`firstname` LIKE ? AND `series`.`slug` = ?
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
      CONCAT_WS(', ', `lastname`, `firstname`) AS `name`,
      `characters`.`image`,
      `characters`.`obtained`,
      `series`.`name` as `series`,
      `series`.`uuid` as `seriesUuid`,
      `series`.`slug` as `slug`
      FROM `characters` 
      INNER JOIN `series` ON `characters`.`seriesId` = `series`.`id`
      WHERE `characters`.`firstname` LIKE ?
      ORDER BY $sort
      LIMIT ?, ?"
    );
    $stmt->bind_param("sii", $search, $offset, $amount);
  } 
  else if ($series)
  {
    $stmt = $connection->prepare("SELECT 
      `characters`.`uuid`, 
      CONCAT_WS(', ', `lastname`, `firstname`) AS `name`,
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
      CONCAT_WS(', ', `lastname`, `firstname`) AS `name`,
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
    // Remove the previous image if there is one
    if ($_POST["prevImage"]) removeImage($_POST['prevImage']);
      $_POST["image"] = storeImage($file);
  }
  // Lastname
  $lastname = (bool)$_POST["lastname"] ? $_POST["lastname"] : null;
  // Insert the character
  $stmt = $connection->prepare("UPDATE `characters` SET
    `firstname` = ?,
    `lastname` = ?,
    `image` = ?,
    `seriesId` = (SELECT `id` FROM `series` WHERE `slug` = ?)
    WHERE `uuid` = ?
  ");
  $stmt->bind_param("sssss", $_POST["firstname"], $lastname, $_POST["image"], $_POST["series"], $_POST["uuid"]);
  $stmt->execute();
  // Show the new image link
  echo $_POST["image"];
  // Close the connection
  $connection->close();
}

function removeImage($path)
{
  $path = "./..$path";
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