<?php
require_once 'includes/config/database.php';

$houses = [
    ['name' => 'Gryffindor', 'description' => 'House of the brave at heart', 'color' => '#AE0001'],
    ['name' => 'Hufflepuff', 'description' => 'House of the loyal and just', 'color' => '#FFDB00'],
    ['name' => 'Ravenclaw', 'description' => 'House of the wise and witty', 'color' => '#222F5B'],
    ['name' => 'Slytherin', 'description' => 'House of the ambitious and cunning', 'color' => '#2A623D']
];

try {
    // Disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    
    // First, clear existing houses to avoid duplicates
    $conn->query("DELETE FROM houses");
    
    // Reset auto-increment
    $conn->query("ALTER TABLE houses AUTO_INCREMENT = 1");

    // Prepare the insert statement
    $stmt = $conn->prepare("INSERT INTO houses (name, description, color) VALUES (?, ?, ?)");
    
    // Insert each house
    foreach ($houses as $house) {
        $stmt->bind_param("sss", $house['name'], $house['description'], $house['color']);
        $stmt->execute();
    }
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    
    echo "Successfully added default houses!";
} catch (Exception $e) {
    // Make sure to re-enable foreign key checks even if there's an error
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    die("Error: " . $e->getMessage());
}
?>