<?php
$pdo = new PDO('mysql:host=db;dbname=workshop;user=root;password=workshop');
$stmt = $pdo->prepare('delete from guestbook');
$stmt->execute();

echo "All messages deleted";
