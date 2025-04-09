<?php
session_start();
require_once 'config.php'; // Add this line to initialize database connection

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get events assigned to this staff member
$stmt = $conn->prepare("SELECT e.* FROM events e
                      INNER JOIN event_staff es ON e.id = es.event_id
                      WHERE es.staff_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

// Get counts for cards
$totalEvents = 0;
$upcomingEvents = 0;

// Reuse the result to count
while ($row = $result->fetch_assoc()) {
    $totalEvents++;
    if (strtotime($row['event_date']) >= strtotime(date('Y-m-d'))) {
        $upcomingEvents++;
    }
}

// Reset result pointer
mysqli_data_seek($result, 0);

// Get latest 3 events for news ticker
$latestEvents = $conn->query("SELECT e.event_name, e.event_date 
                           FROM events e
                           INNER JOIN event_staff es ON e.id = es.event_id
                           WHERE es.staff_id = " . $_SESSION['user_id'] . "
                           AND e.status = 'Approved'
                           ORDER BY e.created_at DESC LIMIT 3");

$newsItems = [];
if ($latestEvents->num_rows > 0) {
    while($event = $latestEvents->fetch_assoc()) {
        $newsItems[] = htmlspecialchars($event['event_name']) . " is scheduled for " . 
                      date('M d, Y', strtotime($event['event_date']));
    }
} else {
    $newsItems[] = "No upcoming events found.";
}

include 'sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/countup.js/2.0.8/countUp.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <title>Staff Dashboard</title>
    <style>
        body {
            display: flex;
            background: #f8f9fa;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        /* Sidebar - kept as is */
        .sidebar {
            width: 260px;
            height: 100vh;
            background: linear-gradient(135deg, #293CB7, #1E2A78);
            padding-top: 20px;
            position: fixed;
            color: #ffffff;
            box-shadow: 4px 0px 10px rgba(0, 0, 0, 0.2);
        }
        .sidebar h4 {
            text-align: center;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            text-decoration: none;
            color: #f0f0f0;
            font-size: 16px;
            transition: background 0.3s ease, border-left 0.3s ease;
        }
        .sidebar a i {
            margin-right: 10px;
            font-size: 18px;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255, 255, 255, 0.2);
            border-left: 5px solid #fff;
        }

        /* Main Content */
        .content {
            margin-left: 270px;
            padding: 20px;
            width: 100%;
        }

        /* Navbar - kept as is */
        .navbar {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }
        .navbar .dropdown-menu {
            min-width: 200px;
        }


        .btn-logout {
            background: #f44336;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-logout:hover {
            background: #d32f2f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Welcome Section */
        .welcome-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .welcome-section h2 {
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .welcome-section p {
            color: #6b7280;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        /* Breaking News Ticker - Animated */
        .breaking-news-container {
            position: relative;
            background: linear-gradient(135deg, #3949ab, #1e3a8a);
            color: white;
            border-radius: 12px;
            height: 60px;
            margin-bottom: 30px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .breaking-news-label {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 160px;
            background: #f44336;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            z-index: 2;
            clip-path: polygon(0 0, 85% 0, 100% 50%, 85% 100%, 0 100%);
        }
        
        .breaking-news-label i {
            margin-right: 8px;
        }
        
        .news-ticker {
            position: absolute;
            white-space: nowrap;
            left: 180px;
            top: 50%;
            transform: translateY(-50%);
            animation: ticker-animation 20s linear infinite;
            display: flex;
            gap: 80px;
        }
        
        .news-item {
            display: inline-block;
            position: relative;
            font-weight: 500;
        }
        
        .news-item::after {
            content: '•';
            margin-left: 40px;
            color: #ffc107;
        }
        
        .news-item:last-child::after {
            content: '';
        }
        
        @keyframes ticker-animation {
            0% {
                transform: translate3d(0, -50%, 0); /* Start already visible */
            }
            100% {
                transform: translate3d(-100%, -50%, 0);
            }
        }

        /* Dashboard Cards - Modernized */
        .dashboard-card {
            border-radius: 12px;
            padding: 30px 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            text-align: center;
            position: relative;
            overflow: hidden;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }
        
        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
            z-index: 1;
        }
        
        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .dashboard-card h5 {
            font-weight: 600;
            font-size: 1.3rem;
            margin-bottom: 15px;
            position: relative;
            z-index: 2;
        }
        
        .card-count {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            position: relative;
            z-index: 2;
        }
        
        .card-icon {
            position: absolute;
            bottom: -25px;
            right: -25px;
            font-size: 8rem;
            opacity: 0.15;
            transform: rotate(-15deg);
        }
        
        .card-description {
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }

        /* Event List Styling */
        .events-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .events-section h2 {
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        
        .event-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .event-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: all 0.2s ease;
        }
        
        .event-item:hover {
            background-color: #f8f9fa;
        }
        
        .event-date {
            font-weight: 600;
            color: #3949ab;
        }
        
        .event-venue {
            color: #6b7280;
            font-size: 0.9rem;
        }

        /* Modal Styles */
        .modal-header {
            background: linear-gradient(135deg, #3949ab, #1e3a8a);
            color: white;
            border-radius: 10px 10px 0 0;
        }
        
        .modal-body {
            padding: 20px;
        }

        /* Responsive Fix */
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .content {
                margin-left: 210px;
                padding: 15px;
            }
            .dashboard-card {
                margin-bottom: 20px;
            }
            .breaking-news-container {
                height: 50px;
            }
            .breaking-news-label {
                width: 120px;
            }
            .news-ticker {
                left: 140px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

     <div class="content">
        <!-- Navbar - kept as is -->
        <nav class="navbar navbar-light">
            <div class="container-fluid d-flex justify-content-between">
                <span class="navbar-brand mb-0 h1" id="headerTitle">Admin Dashboard</span>
                
                <!-- User Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#">User Type: <?php echo htmlspecialchars($_SESSION['user_type']); ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Welcome Section -->
        <section class="welcome-section">
            <h2>Welcome to Your Staff Portal</h2>
            <p>Here you can view all events assigned to you and manage your schedule.</p>
        </section>

        <!-- Breaking News Ticker - Animated -->
        <div class="breaking-news-container">
            <div class="breaking-news-label">
                <i class="bi bi-megaphone-fill"></i> NEWS
            </div>
            <div class="news-ticker">
                <?php foreach($newsItems as $item): ?>
                    <div class="news-item"><?php echo $item; ?></div>
                <?php endforeach; ?>
                <!-- Duplicate items to create a seamless loop -->
                <?php foreach($newsItems as $item): ?>
                    <div class="news-item"><?php echo $item; ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Dashboard Cards -->
        <section class="my-4">
            <div class="container px-0">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="dashboard-card bg-primary text-white" data-bs-toggle="modal" data-bs-target="#allEventsModal">
                            <i class="bi bi-calendar-check card-icon"></i>
                            <h5>Total Events</h5>
                            <div class="card-count" id="totalEvents"><?php echo $totalEvents; ?></div>
                            <p class="card-description">All events assigned to you</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="dashboard-card bg-success text-white" data-bs-toggle="modal" data-bs-target="#upcomingEventsModal">
                            <i class="bi bi-calendar2-event card-icon"></i>
                            <h5>Upcoming Events</h5>
                            <div class="card-count" id="upcomingEvents"><?php echo $upcomingEvents; ?></div>
                            <p class="card-description">Events yet to come</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Events List Section -->
        <section class="events-section">
            <h2><i class="bi bi-calendar3-week me-2"></i>Your Events</h2>
            <div class="event-list">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($event = $result->fetch_assoc()): ?>
                        <div class="event-item">
                            <h5><?php echo htmlspecialchars($event['event_name']); ?></h5>
                            <div class="event-date">
                                <i class="bi bi-calendar3"></i> 
                                <?php echo date('F d, Y', strtotime($event['event_date'])); ?>
                            </div>
                            <div class="event-venue">
                                <i class="bi bi-geo-alt"></i> 
                                <?php echo htmlspecialchars($event['venue']); ?>
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-<?php echo $event['status'] == 'Approved' ? 'success' : ($event['status'] == 'Pending' ? 'warning' : 'danger'); ?>">
                                    <?php echo $event['status']; ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x display-4 d-block mb-3 text-muted"></i>
                        <p class="lead">No events assigned to you</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- All Events Modal -->
    <div class="modal fade" id="allEventsModal" tabindex="-1" aria-labelledby="allEventsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="allEventsModalLabel">
                        <i class="bi bi-calendar-check me-2"></i>
                        All Events (<?php echo $totalEvents; ?>)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="event-list">
                        <?php 
                        // Reset result pointer
                        mysqli_data_seek($result, 0);
                        
                        if ($result->num_rows > 0): ?>
                            <?php while ($event = $result->fetch_assoc()): ?>
                                <div class="event-item">
                                    <h5><?php echo htmlspecialchars($event['event_name']); ?></h5>
                                    <div class="event-date">
                                        <i class="bi bi-calendar3"></i> 
                                        <?php echo date('F d, Y', strtotime($event['event_date'])); ?>
                                    </div>
                                    <div class="event-venue">
                                        <i class="bi bi-geo-alt"></i> 
                                        <?php echo htmlspecialchars($event['venue']); ?>
                                    </div>
                                    <div class="mt-2">
                                        <span class="badge bg-<?php echo $event['status'] == 'Approved' ? 'success' : ($event['status'] == 'Pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo $event['status']; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-calendar-x display-4 d-block mb-3 text-muted"></i>
                                <p class="lead">No events found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upcoming Events Modal -->
    <div class="modal fade" id="upcomingEventsModal" tabindex="-1" aria-labelledby="upcomingEventsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="upcomingEventsModalLabel">
                        <i class="bi bi-calendar2-event me-2"></i>
                        Upcoming Events (<?php echo $upcomingEvents; ?>)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="event-list">
                        <?php 
                        // Reset result pointer
                        mysqli_data_seek($result, 0);
                        
                        $hasUpcoming = false;
                        
                        if ($result->num_rows > 0): 
                            while ($event = $result->fetch_assoc()): 
                                if (strtotime($event['event_date']) >= strtotime(date('Y-m-d'))):
                                    $hasUpcoming = true;
                        ?>
                                <div class="event-item">
                                    <h5><?php echo htmlspecialchars($event['event_name']); ?></h5>
                                    <div class="event-date">
                                        <i class="bi bi-calendar3"></i> 
                                        <?php echo date('F d, Y', strtotime($event['event_date'])); ?>
                                    </div>
                                    <div class="event-venue">
                                        <i class="bi bi-geo-alt"></i> 
                                        <?php echo htmlspecialchars($event['venue']); ?>
                                    </div>
                                    <div class="mt-2">
                                        <span class="badge bg-<?php echo $event['status'] == 'Approved' ? 'success' : ($event['status'] == 'Pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo $event['status']; ?>
                                        </span>
                                    </div>
                                </div>
                        <?php 
                                endif;
                            endwhile;
                            
                            if (!$hasUpcoming): 
                        ?>
                            <div class="text-center py-5">
                                <i class="bi bi-calendar-x display-4 d-block mb-3 text-muted"></i>
                                <p class="lead">No upcoming events found</p>
                            </div>
                        <?php 
                            endif;
                        else: 
                        ?>
                            <div class="text-center py-5">
                                <i class="bi bi-calendar-x display-4 d-block mb-3 text-muted"></i>
                                <p class="lead">No upcoming events found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get the actual values from the PHP variables now displayed in the HTML
            const totalEventsElement = document.getElementById('totalEvents');
            const upcomingEventsElement = document.getElementById('upcomingEvents');
            
            // Get the numeric values
            const totalEventsValue = parseInt(totalEventsElement.textContent) || 0;
            const upcomingEventsValue = parseInt(upcomingEventsElement.textContent) || 0;
            
            // Start with 0 and animate to the actual values
            const totalCounter = new CountUp.CountUp('totalEvents', totalEventsValue, {
                duration: 1.5,
                useEasing: true,
                useGrouping: true,
                startVal: 0 // Start from 0
            });
            
            const upcomingCounter = new CountUp.CountUp('upcomingEvents', upcomingEventsValue, {
                duration: 1.5,
                useEasing: true,
                useGrouping: true,
                startVal: 0 // Start from 0
            });
            
            // Start animations
            totalCounter.start();
            upcomingCounter.start();
        });
    </script>
</body>
</html>