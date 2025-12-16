<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect data
    $name = $_POST['name'] ?? 'Unknown';
    $email = $_POST['email'] ?? 'No Email';
    $phone = $_POST['phone'] ?? 'No Phone';
    $message = $_POST['message'] ?? '';
    $type = $_POST['type'] ?? 'General'; // New field to distinguish type

    $output = "--- NEW MESSAGE ---\n";
    $output .= "Type: " . $type . "\n";
    $output .= "Name: " . $name . "\n";
    $output .= "Email: " . $email . "\n";
    $output .= "Phone: " . $phone . "\n";
    $output .= "Message: " . $message . "\n";
    $output .= "Date: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Log to the existing file
    file_put_contents(__DIR__ . '/../contact_log.txt', $output, FILE_APPEND);

    // If it's for the admin, you might want to log to a separate file or perform a specific action
    if ($type === 'Admin') {
         // Example: Log to a specific admin file
         file_put_contents(__DIR__ . '/../admin_messages.txt', $output, FILE_APPEND);
    }

    echo "Thank You! Message has been sent to " . htmlspecialchars($type);
}
?>
