<?php
$mysqli = new mysqli("localhost", "root", "", "kisan_kart");
if ($mysqli->connect_error) {
    file_put_contents("products_table_structure.txt", "Connection failed: " . $mysqli->connect_error);
    exit;
}
$result = $mysqli->query("DESCRIBE products");
$output = "";
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $output .= $row['Field'] . " - " . $row['Type'] . "\\n";
    }
} else {
    $output = "Error: " . $mysqli->error;
}
file_put_contents("products_table_structure.txt", $output);
$mysqli->close();
?>
