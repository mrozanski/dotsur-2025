<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get form data
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

// Validate email
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Valid email address required']);
    exit;
}

// Validate message
if (!$message || strlen(trim($message)) < 5) {
    echo json_encode(['success' => false, 'message' => 'Message must be at least 5 characters long']);
    exit;
}

// Create the contact entry
$contactEntry = [
    'id' => uniqid(),
    'email' => $email,
    'message' => trim($message),
    'timestamp' => date('Y-m-d H:i:s'),
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
];

// File to store contacts (outside web root for security)
$contactsFile = '../data/contacts.json';

try {
    // Read existing contacts or create empty array
    if (file_exists($contactsFile)) {
        $existingContacts = json_decode(file_get_contents($contactsFile), true);
        if (!is_array($existingContacts)) {
            $existingContacts = [];
        }
    } else {
        $existingContacts = [];
    }
    
    // Add new contact to the beginning of the array
    array_unshift($existingContacts, $contactEntry);
    
    // Keep only the latest 1000 entries to prevent the file from getting too large
    $existingContacts = array_slice($existingContacts, 0, 1000);
    
    // Save back to file with pretty formatting
    $jsonData = json_encode($existingContacts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
    if (file_put_contents($contactsFile, $jsonData, LOCK_EX) === false) {
        throw new Exception('Failed to write to contacts file');
    }
    
    // Optional: Log successful submissions
    error_log("New contact form submission from: " . $email);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Contact form submitted successfully',
        'id' => $contactEntry['id']
    ]);
    
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred while processing your request'
    ]);
}
?>