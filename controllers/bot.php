<?php
include_once "./../config.php";

function storeImage($url)
{
  $filename = uniqid(rand()) . ".png";
  $source = imagecreatefrompng($url);
  $result = imagecreatetruecolor(225, 350);
  imagecopyresized($result, $source, 0, 0, 1, 1, 225, 350, 223, 348);
  imagepng($result, "./../cards/$filename");
  return "/cards/$filename";
}

function propertyInArrayOfObjects($array, $property, $value)
{
    $matches = [];
    foreach ($array as $object)
    {
        if ($object[$property] == $value)
        {
            array_push($matches, $object);
        }
    }
    return $matches;
}

function getSlug($string, $delimiter = "-")
{
  return str_replace(" ", $delimiter, trim(preg_replace("/\W+/", " ", strtolower($string))));
}

function removeImage($path)
{
  $path = "./..$path";
  if (file_exists($path)) unlink($path);
}

function getCharacters()
{
    $connection = connect();
    $stmt = $connection->prepare("SELECT CONCAT_WS(' ', `firstname`, `lastname`) as `name`, `image`, `seriesId`, `series`.`name` as `series` FROM `characters`
        INNER JOIN `series` ON `characters`.`seriesId` = `series`.`id`
        WHERE CONCAT_WS(' ', `firstname`, `lastname`) = ? OR `series`.`name` = ?
    ");
    $stmt->bind_param("ss", $_POST["name"], $_POST["series"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $connection->close();
    return $rows;
}

function addCharacter()
{
    $connection = connect();
    $parts = explode(" ", $_POST["name"], 2);
    $firstname = $parts[0];
    $lastname = $parts[1] ?? null;
    // Execute script that resizes images
    $path = storeImage($_POST["image"]);
    $stmt = $connection->prepare("INSERT INTO `characters` (`firstname`, `lastname`, `image`, `obtained`, `seriesId`)
        VALUES (?, ?, ?, ?, (SELECT `id` FROM `series` WHERE `name` = ?));
    ");
    $stmt->bind_param("sssis", $firstname, $lastname, $path, $_POST["obtained"], $_POST["series"]);
    $stmt->execute();
    $connection->close();
}

function updateCharacter($character)
{
    $connection = connect();
    // Remove the current image
    removeImage($character["image"]);
    // Execute script that resizes images
    $path = storeImage($_POST["image"]);
    $stmt = $connection->prepare("UPDATE `characters` SET
        `image` = ?,
        `obtained` = ?
        WHERE CONCAT_WS(' ', `firstname`, `lastname`) = ? AND `seriesId` = ?
    ");
    $stmt->bind_param("sssi", $path, $_POST["obtained"], $_POST["name"], $character["seriesId"]);
    $stmt->execute();
    $connection->close();
}

function addSeries()
{
    $connection = connect();
    $stmt = $connection->prepare("INSERT INTO `series` (`name`, `slug`)
        VALUES (?, ?);
    ");
    $slug = getSlug($_POST["series"]);
    $stmt->bind_param("ss", $_POST["series"], $slug);
    $stmt->execute();
    $connection->close();
}

if ($_POST)
{
    $characters = getCharacters();
    if ($matches = propertyInArrayOfObjects($characters, "series", $_POST["series"]))
    {
        if ($match = propertyInArrayOfObjects($matches, "name", $_POST["name"]))
        {
            updateCharacter($match[0]);
            echo json_encode(["message" => "Character updated"]);
        }
        else
        {
            addCharacter();
            echo json_encode(["message" => "Character added"]);
        }
    }
    else
    {
        addSeries();
        addCharacter();
        echo json_encode(["message" => "Character & Series added"]);
    }
}