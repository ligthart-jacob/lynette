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

function getSlug($string, $delimiter = "-")
{
  return str_replace(" ", $delimiter, trim(preg_replace("/\W+/", " ", strtolower($string))));
}

function getCharacter()
{
    $connection = connect();
    $stmt = $connection->prepare("SELECT CONCAT_WS(' ', `firstname`, `lastname`) as `name`, `series`.`name` as `series` FROM `characters`
        INNER JOIN `series` ON `characters`.`seriesId` = `series`.`id`
        WHERE CONCAT_WS(' ', `firstname`, `lastname`) = ? OR `series`.`name` = ?
    ");
    $stmt->bind_param("ss", $_POST["name"], $_POST["series"]);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
    $connection->close();
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
    $character = getCharacter();
    if ($character)
    {
        if ($character["name"] != $_POST["name"])
        {
            addCharacter();
            echo json_encode(["message" => "Character added"]);
        }
        else if ($character["series"] != $_POST["series"])
        {
            addSeries();
            addCharacter();
            echo json_encode(["message" => "Character & Series added"]);
        }
        else
        {
            echo json_encode(["message" => "Character already exists"]);
        }
    }
    else
    {
        addSeries();
        addCharacter();
        echo json_encode(["message" => "Character & Series added"]);
    }
}


