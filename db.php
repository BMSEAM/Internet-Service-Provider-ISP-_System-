<?php
// includes/db.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'isp_system');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("<h3 style='color:red;font-family:sans-serif;'>
        Database connection failed: " . mysqli_connect_error() . "
        <br>Make sure XAMPP is running and you imported database.sql
    </h3>");
}

mysqli_set_charset($conn, "utf8");

// ── Helper Functions ─────────────────────────────────────────

// Run a SELECT query, return all rows as array
function db_get_all($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

// Run a SELECT query, return only the first row
function db_get_one($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($result);
}

// Run a SELECT query, return a single value
function db_get_value($conn, $sql, $col) {
    $row = db_get_one($conn, $sql);
    return $row ? $row[$col] : 0;
}

// Run an INSERT/UPDATE/DELETE query, return true/false
function db_run($conn, $sql) {
    return mysqli_query($conn, $sql);
}

// Run INSERT, return the new row ID
function db_insert($conn, $sql) {
    mysqli_query($conn, $sql);
    return mysqli_insert_id($conn);
}

// Escape a string safely
function esc($conn, $val) {
    return mysqli_real_escape_string($conn, trim($val));
}
?>