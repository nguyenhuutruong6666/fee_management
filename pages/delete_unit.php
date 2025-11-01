<?php
include("../config/db.php");
$id = $_GET['id'] ?? 0;

if ($id) {
  $conn->query("DELETE FROM organization_units WHERE id=$id");
}
header("Location: units.php");
exit();
?>
