<?php
session_start();

// Add security headers
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:;");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Invalid request. Please try again.");
}

require 'vendor/autoload.php'; // AWS SDK

use Aws\S3\S3Client;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Exception\AwsException;

// Load configuration
$config = require '/var/private/config.php';

// Database connection
$host = $config['db']['host'];
$dbname = $config['db']['name'];
$username = $config['db']['user'];
$password = $config['db']['pass'];

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("Database Connection Failed: " . $conn->connect_error, 3, "/var/www/html/error_log.txt");
    die("Database Connection Error. Please try again later.");
}

// AWS S3 Setup (using IAM role)
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => $config['aws']['region'],
    // No credentials - will use IAM role or environment variables
]);

$bucket = $config['aws']['bucket'];

// AWS DynamoDB Setup
$dynamodb = new DynamoDbClient([
    'version' => 'latest',
    'region'  => $config['aws']['region'],
    // No credentials - will use IAM role or environment variables
]);

$dynamoTable = $config['aws']['dynamo_table'];

// Validate Input Data
if (empty($_POST['name']) || empty($_POST['location']) || empty($_POST['technology']) || 
    empty($_POST['salary']) || empty($_FILES['image']['name'])) {
    die("Error: All fields are required.");
}

// Validate and sanitize input data
$name = trim($_POST['name']);
if (empty($name) || strlen($name) > 100) {
    die("Error: Name is required and must be under 100 characters.");
}

$location = trim($_POST['location']);
if (empty($location) || strlen($location) > 100) {
    die("Error: Location is required and must be under 100 characters.");
}

$technology = trim($_POST['technology']);
if (empty($technology) || strlen($technology) > 100) {
    die("Error: Technology is required and must be under 100 characters.");
}

// Stricter salary validation
$salary = filter_var($_POST['salary'], FILTER_VALIDATE_FLOAT);
if ($salary === false || $salary < 0 || $salary > 1000000) {
    die("Error: Salary must be a valid positive number less than 1,000,000.");
}

// More thorough file validation
$allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
$image = $_FILES['image'];

// Check if file type is in allowed list
if (!array_key_exists($image['type'], $allowedTypes)) {
    die("Error: Only JPG, PNG, and GIF files are allowed.");
}

// Verify the actual file content matches the claimed type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$fileContentsType = $finfo->file($image['tmp_name']);
if (!array_key_exists($fileContentsType, $allowedTypes)) {
    die("Error: File content does not match allowed types.");
}

// Check File Size (Limit to 5MB)
if ($image['size'] > 5 * 1024 * 1024) {
    die("Error: File size must be under 5MB.");
}

// Generate a secure filename
$extension = $allowedTypes[$fileContentsType];
$imageName = bin2hex(random_bytes(16)) . '.' . $extension;
$tempPath = $image['tmp_name'];

// Debugging: Log file details
error_log("File Details: " . print_r($_FILES, true), 3, "/var/www/html/error_log.txt");

// Upload Image to S3 with improved security
try {
    $result = $s3->putObject([
        'Bucket' => $bucket,
        'Key'    => "uploads/" . $imageName,
        'SourceFile' => $tempPath,
        'ACL'    => 'private', // More restrictive ACL
        'ContentType' => $image['type'],
        'Metadata' => [
            'employee-name' => $name,
            'upload-date' => date('Y-m-d')
        ]
    ]);
    $imageUrl = (string) $result['ObjectURL'];
} catch (AwsException $e) {
    error_log("S3 Upload Error: " . $e->getMessage(), 3, "/var/www/html/error_log.txt");
    die("File upload error. Please try again later.");
}

// Insert into MySQL (Auto-generates Employee ID)
$sql = "INSERT INTO employees (name, location, technology, salary, image_url) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("MySQL Prepare Error: " . $conn->error, 3, "/var/www/html/error_log.txt");
    die("Database Error: Could not prepare statement.");
}

$stmt->bind_param("sssds", $name, $location, $technology, $salary, $imageUrl);
$executionSuccess = $stmt->execute();

if ($executionSuccess) {
    $employeeId = $stmt->insert_id; // Retrieve Employee ID after successful insert

    // Store Image Metadata in DynamoDB
    try {
        $dynamodb->putItem([
            'TableName' => $dynamoTable,
            'Item' => [
                'image_id' => ['S' => bin2hex(random_bytes(16))],
                'employee_id' => ['N' => (string) $employeeId],
                'image_url' => ['S' => $imageUrl],
                'upload_time' => ['S' => date("Y-m-d H:i:s")]
            ]
        ]);
    } catch (AwsException $e) {
        error_log("DynamoDB Error: " . $e->getMessage(), 3, "/var/www/html/error_log.txt");
        // Continue even if DynamoDB fails, as the main data is in MySQL
    }

    // Modified success message to match the requested format
    $_SESSION['success'] = "Employee details added successfully! Employee ID: " . $employeeId;
    
    // Store the employee ID separately for more flexibility in display
    $_SESSION['employee_id'] = $employeeId;
    
    header("Location: success.php");
    exit();
} else {
    error_log("MySQL Insert Error: " . $stmt->error, 3, "/var/www/html/error_log.txt");
    die("Database Error: Please check logs.");
}

// Close Database Connection
$stmt->close();
$conn->close();
?>
