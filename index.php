<?php 
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($request, 'inventory_custom_layout') !== false) {
      include 'inventory_custom_layout.html';
} else {
      include 'index.html';
}
?>
