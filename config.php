<?php
// Azure SQL Server details
$serverName = "tcp:rb-sql-server.database.windows.net,1433"; 
$databaseName = "cloudcart-db";

// Fetch credentials securely from environment variables set in Azure
$username = getenv('DB_USERNAME'); 
$password = getenv('DB_PASSWORD');

try {
    $conn = new PDO(
        "sqlsrv:server=$serverName;Database=$databaseName", 
        $username, 
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, true);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>