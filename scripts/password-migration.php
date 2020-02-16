<?php

$db = new PDO("mysql:host=localhost;dbname=joindin", "root", null);

$update_sql = "update user set password = :password where ID = :id";
$update_stmt = $db->prepare($update_sql);

$select_sql = "select ID, password from user";
$select_stmt = $db->prepare($select_sql);
$select_stmt->execute();

$count = 0;

while ($row = $select_stmt->fetch(PDO::FETCH_ASSOC)) {
    $update_stmt->execute([
        "password" => password_hash($row['password'], PASSWORD_DEFAULT),
        "id" => $row['ID']
    ]);
    $count++;
}

echo $count . " rows processed\n";
