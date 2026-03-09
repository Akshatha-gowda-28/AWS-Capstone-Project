<?php
session_start();

// Add security headers
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net; img-src 'self' data: https:;");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Invalid request. Please try again.");
}

// Include Composer's autoloader
require 'vendor/autoload.php';

// Load configuration
$config = require '/var/private/config.php';

// Database Connection
$host = $config['db']['host'];
$dbname = $config['db']['name'];
$username = $config['db']['user'];
$password = $config['db']['pass'];

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed. Please try again later.");
}

// Check if employee_id is set and valid
if (!isset($_POST['employee_id']) || !is_numeric($_POST['employee_id']) || $_POST['employee_id'] <= 0) {
    die("Error: Valid Employee ID is required.");
}

$employee_id = (int) $_POST['employee_id'];

$sql = "SELECT * FROM employees WHERE employee_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database error: Failed to prepare statement.");
}

$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Employee Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">XYZ Company</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="https://my-xyz-company-bucket.s3.us-east-1.amazonaws.com/about-us.html">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php">New Employee Form</a></li>
                    <li class="nav-item"><a class="nav-link" href="get_employee.php">View Employee Details</a></li>
                </ul>
            </div>
        </div>
    </nav>

<?php
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $imageUrl = !empty($row["image_url"]) ? $row["image_url"] : "default-placeholder.jpg";
    
    // If the image is from the uploads folder, generate a signed URL
    if (strpos($imageUrl, 'uploads/') !== false) {
        try {
            // Extract the key from the URL
            $urlParts = parse_url($imageUrl);
            $path = ltrim($urlParts['path'] ?? '', '/');
            $key = substr($path, strpos($path, 'uploads/'));
            
            // Generate a signed URL that expires in 5 minutes
            $s3 = new Aws\S3\S3Client([
                'version' => 'latest',
                'region'  => $config['aws']['region']
            ]);
            
            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => $config['aws']['bucket'],
                'Key'    => $key
            ]);
            
            $request = $s3->createPresignedRequest($cmd, '+5 minutes');
            $imageUrl = (string) $request->getUri();
        } catch (Exception $e) {
            // If S3 signing fails, log the error but continue with the original URL
            error_log("S3 URL signing error: " . $e->getMessage());
        }
    }
    ?>
    <h2 class="mb-3">Employee Details</h2>
    <ul class="list-group">
        <li class="list-group-item"><strong>ID:</strong> <?php echo htmlspecialchars($row["employee_id"]); ?></li>
        <li class="list-group-item"><strong>Name:</strong> <?php echo htmlspecialchars($row["name"]); ?></li>
        <li class="list-group-item"><strong>Location:</strong> <?php echo htmlspecialchars($row["location"]); ?></li>
        <li class="list-group-item"><strong>Technology:</strong> <?php echo htmlspecialchars($row["technology"]); ?></li>
        <li class="list-group-item"><strong>Salary:</strong> 
            $<?php echo isset($row["salary"]) ? number_format($row["salary"], 2) : "N/A"; ?>
        </li>
        <li class="list-group-item">
            <strong>Profile Image:</strong><br>
            <img src="<?php echo htmlspecialchars($imageUrl); ?>" width="200" class="img-thumbnail">
        </li>
    </ul>
    <div class="mt-3">
        <a href="get_employee.php" class="btn btn-primary">Go Back</a>
    </div>
    <?php
} else {
    echo '<div class="alert alert-danger" role="alert">Employee not found!</div>';
    echo '<div class="mt-3"><a href="get_employee.php" class="btn btn-primary">Go Back</a></div>';
}

$conn->close();
?>

<!-- Bootstrap JS for Navbar Toggle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
