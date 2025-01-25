<?php
session_start();

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <title>Dashboard</title>
    <style>
        .new-bookings-table {
            margin-top: 30px;
        }
        .delete-btn {
            color: #dc3545; 
            text-decoration: none;
            font-weight: bold;
        }
        .delete-btn:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">AU JAS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item me-3">
                        <a class="nav-link active" aria-current="page" href="#">Dashboard</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link" href="#">Event Calendar</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link" href="#">Event Management</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link" href="#">User Management</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link" href="#">Reports</a>
                    </li>
                </ul>

                <!-- User Info -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?> <!-- Display user's name -->
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="#">User Type: <?php echo htmlspecialchars($_SESSION['user_type']); ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <header class="bg-light p-4 border-bottom">
        <div class="container">
            <h1 class="mb-3">Welcome to the Event Management System</h1>
            <div class="alert alert-info" role="alert">
                Breaking News: The ICT Department’s event is scheduled for 1/22/2025! Don’t miss it.
            </div>
        </div>
    </header>

    <!-- Boxes Section -->
    <section class="my-5">
        <div class="container">
            <div class="row text-center">
                <!-- Total Events -->
                <div class="col-md-4">
                    <a href="#" class="btn btn-primary w-100 py-4">
                        <h5>Total Events</h5>
                        <p class="mb-0">Click to View</p>
                    </a>
                </div>
                <!-- Upcoming Events -->
                <div class="col-md-4">
                    <a href="#" class="btn btn-success w-100 py-4">
                        <h5>Upcoming Events</h5>
                        <p class="mb-0">Click to View</p>
                    </a>
                </div>
                <!-- Cancelled Bookings -->
                <div class="col-md-4">
                    <a href="#" class="btn btn-danger w-100 py-4">
                        <h5>Cancelled Bookings</h5>
                        <p class="mb-0">Click to View</p>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- New Bookings Section -->
    <section class="new-bookings-table">
        <div class="container">
            <h2 class="mb-4">New Bookings</h2>
            <table class="table table-borderless">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>ICT Department</td>
                        <td>1/22/2025</td>
                        <td><a href="#" class="delete-btn">Delete</a></td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>CAS Department</td>
                        <td>1/23/2025</td>
                        <td><a href="#" class="delete-btn">Delete</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</body>
</html>
