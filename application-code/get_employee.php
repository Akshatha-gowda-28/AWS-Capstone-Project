<?php
session_start();
// Generate CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Add security headers
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:;");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Get Employee Info</title>
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
                    <li class="nav-item"><a class="nav-link active" href="get_employee.php">View Employee Details</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Employee Search Form -->
    <h2 class="mb-3">Enter Employee ID</h2>
    <form action="fetch.php" method="post">
        <!-- Add CSRF token -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div class="mb-3">
            <label class="form-label">Employee ID:</label>
            <input type="number" name="employee_id" class="form-control" required min="1">
        </div>
        <button type="submit" class="btn btn-primary">Fetch</button>
    </form>

    <!-- Bootstrap JS (For Navbar Toggle) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
