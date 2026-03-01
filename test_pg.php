<?php
try {
    $pdo = new PDO("pgsql:host=localhost;port=5432;dbname=quizsystem", "postgres", "rorn");
    echo "Connected successfully!";
} catch (PDOException $e) {
    echo $e->getMessage();
}