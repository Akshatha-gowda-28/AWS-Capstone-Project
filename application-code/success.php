<?php
session_start();

// Add security headers
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:;");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Redirect if accessed directly without success message
if (!isset($_SESSION['success'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Success - XYZ Company</title>
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

    <div class="alert alert-success mt-4" role="alert">
        <h4 class="alert-heading">Success!</h4>
        <?php if(isset($_SESSION['success'])): ?>
            <p><?php echo htmlspecialchars($_SESSION['success']); ?></p>
            <?php 
            // Clear the success message to prevent it from showing again on refresh
            unset($_SESSION['success']); 
            ?>
        <?php endif; ?>
    </div>
    
    <?php if(isset($_SESSION['employee_id'])): ?>
    <div class="card mt-3 mb-3">
        <div class="card-body">
            <h5 class="card-title">Employee Information</h5>
            <p class="card-text">You can view the complete details for Employee ID: <?php echo htmlspecialchars($_SESSION['employee_id']); ?> using the View Employee option.</p>
        </div>
    </div>
    <?php 
    // Clear the employee ID to prevent it from showing again on refresh
    unset($_SESSION['employee_id']); 
    ?>
    <?php endif; ?>
    
    <div class="mt-3">
        <a href="index.php" class="btn btn-primary">Add Another Employee</a>
        <a href="get_employee.php" class="btn btn-secondary ms-2">View Employee</a>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
