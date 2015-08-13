<?php

require_once("database.php");

$pdo = new PDO('mysql:host=localhost;dbname=forum;charset=utf8',"root");

$database = new DataBase($pdo);

// ------- The insert method

if ($database->insert("post", ["author_id" => 3, "title" => "Title", "body" => "Some example text here", "date_posted" => date("Y-m-d H:i:s"), "date_last_modified" => date("Y-m-d H:i:s")])) {
	echo "<h1>Yay!</h1>";
} else {
	echo "<h1>failure</h1>";
}

// ------- The select method

foreach ($database->select(["*"], "post") as $row) {
	echo $row['id'] . "<br>" . $row['title'] . "<br>" . $row['body'] . "<br><br>";
}

// -------The update method
if ($database->update("post", ["title" => "New Title", "body" => "New text here"]), "id = 3") {
	echo "Yay!";
} else {
	echo "Failure";
}

// -------The delete method

if ($database->delete("post", "id = 3")) {
	echo "Yay!";
} else {
	echo "Failure.";
}

?>
