<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Dashboard redirection logic
if ($_SESSION['user_type'] == 'admin') {
    $dashboardLink = 'admin_dashboard.php';
} elseif ($_SESSION['user_type'] == 'staff') {
    $dashboardLink = 'staff_dashboard.php';
} else {
    $dashboardLink = 'user_dashboard.php';
}

// Fetch events
$sql = "SELECT * FROM events WHERE status = 'Approved'"; // Show only approved events
$result = $conn->query($sql);
$events = [];
while($row = $result->fetch_assoc()) {
    $events[] = [
        'title' => $row['event_name'],
        'start' => $row['event_date'], // Remove time to make it all-day
        'description' => $row['event_description'],
        'venue' => $row['venue'],
        'status' => $row['status'],
        'color' => '#ffc107' // Force yellow color for all approved events
    ];
}
include 'sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Calendar</title>
    
    <!-- Styles & Scripts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    
    <style>
        body {
            display: flex;
            background: #f4f4f4;
            min-height: 100vh;
        }
        
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
        .sidebar a:hover, 
        .sidebar a.active {
            background: rgba(255, 255, 255, 0.2);
            border-left: 5px solid #fff;
        }
        
        .content {
            margin-left: 260px;
            padding: 20px;
            flex-grow: 1;
        }

        .navbar {
            background-color: #ffffff;
            border-bottom: 2px solid #e0e0e0;
            padding: 15px;
            box-shadow: 0px 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dropdown-toggle::after {
            margin-left: 8px;
        }

        .btn-light {
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .btn-light:hover {
            background-color: #f8f9fa;
        }
        
        .calendar-tools {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .fc-toolbar-title {
            color: #1E2A78;
            font-weight: 600;
        }

        .fc-day:hover {
            background-color: rgba(41, 60, 183, 0.05) !important;
        }

        .fc-day-highlight {
            background-color: rgba(41, 60, 183, 0.1) !important;
            transition: background-color 0.3s ease;
        }

        .fc-event:hover {
            transform: scale(1.02);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        #monthYearText {
            font-size: 1.75rem;
            letter-spacing: 0.5px;
            color: #1E2A78;
        }

        .search-bar-container {
            width: 300px;
            margin-right: 15px;
            position: relative;
        }

        .search-bar-container .form-control {
            border-radius: 6px;
            padding: 8px 15px;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .search-bar-container .form-control:focus {
            border-color: #293CB7;
            box-shadow: 0 0 0 3px rgba(41, 60, 183, 0.1);
        }
        .search-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            z-index: 2;
            cursor: pointer;
            padding: 0;
            height: 20px;
            width: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-buttons {
            display: flex;
            gap: 8px;
        }
        
        /* Search Results Styling */
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
        }
        
        .search-results .result-item {
            padding: 8px 12px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .search-results .result-item:hover {
            background: #f8f9fa;
        }
        
        .search-results .result-item:last-child {
            border-bottom: none;
        }
        
        .search-results .result-date {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .no-results {
            padding: 12px;
            color: #6c757d;
            text-align: center;
        }
        
        /* Toast Notification */
        .search-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            background: #293CB7;
            color: white;
            border-radius: 6px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 1050;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="content">
        <!-- Navbar -->
        <nav class="navbar navbar-light">
            <div class="container-fluid d-flex justify-content-between">
                <span class="navbar-brand mb-0 h1">Event Calendar</span>
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
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

        <!-- Calendar Tools -->
        <div class="calendar-tools">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex gap-2">
                    <div class="search-bar-container">
                        <input type="text" class="form-control" id="searchInput" placeholder="Search events...">
                        <button type="button" class="search-btn" id="searchBtn">
                            <i class="bi bi-search"></i>
                        </button>
                        <div class="search-results" id="searchResults"></div>
                    </div>
                    <?php if($_SESSION['user_type'] == 'admin'): ?>
                    <button class="btn btn-primary" onclick="location.href='admin_event form.php'">
                        <i class="bi bi-plus-lg"></i> Add Event
                    </button>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <div class="nav-buttons">
                        <button class="btn btn-outline-secondary" id="prevBtn"><i class="bi bi-chevron-left"></i></button>
                        <button class="btn btn-outline-secondary" id="todayBtn">Today</button>
                        <button class="btn btn-outline-secondary" id="nextBtn"><i class="bi bi-chevron-right"></i></button>
                    </div>
                    <div class="btn-group ms-2">
                        <button class="btn btn-outline-secondary active" data-view="month">Month</button>
                        <button class="btn btn-outline-secondary" data-view="week">Week</button>
                        <button class="btn btn-outline-secondary" data-view="day">Day</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="calendar-header mb-3">
            <h3 id="monthYearText" class="text-primary fw-bold"></h3>
        </div>

        <!-- Calendar Container -->
        <div id="calendar"></div>
        
        <!-- Toast Notification -->
        <div class="search-toast" id="searchToast">
            <span id="toastMessage"></span>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const calendarEl = document.getElementById('calendar');
        const monthYearText = document.getElementById('monthYearText');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const todayBtn = document.getElementById('todayBtn');
        const searchInput = document.getElementById('searchInput');
        const searchBtn = document.getElementById('searchBtn');
        const searchResults = document.getElementById('searchResults');
        const searchToast = document.getElementById('searchToast');
        const toastMessage = document.getElementById('toastMessage');
        
        // Event data
        const events = <?= json_encode($events) ?>;
        
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: '',
                center: '',
                right: ''
            },
            events: events,
            datesSet: function(info) {
                // Update month/year display
                const start = info.view.currentStart;
                const month = start.toLocaleString('default', { month: 'long' });
                const year = start.getFullYear();
                monthYearText.textContent = `${month} ${year}`;
            },
            eventDidMount: (info) => {
                const event = info.event;
                const description = event.extendedProps.description || 'No description';
                const venue = event.extendedProps.venue || 'No venue specified';
                
                info.el.setAttribute('title', 
                    `${event.title}\n
                    Description: ${description}\n
                    Venue: ${venue}`);
                
                // Permanently highlight the day cell
                const dayCell = document.querySelector(`.fc-day[data-date="${event.startStr}"]`);
                if (dayCell) {
                    dayCell.style.backgroundColor = 'rgba(255, 193, 7, 0.2)';
                }
                
                // Remove hover effects if needed
                info.el.style.cursor = 'default';
                info.el.style.boxShadow = 'none';
            }
        });
        
        calendar.render();
        
        // Initial month/year display
        const currentDate = calendar.getDate();
        const month = currentDate.toLocaleString('default', { month: 'long' });
        const year = currentDate.getFullYear();
        monthYearText.textContent = `${month} ${year}`;
        
        // Navigation buttons
        prevBtn.addEventListener('click', () => {
            calendar.prev();
        });
        
        nextBtn.addEventListener('click', () => {
            calendar.next();
        });
        
        todayBtn.addEventListener('click', () => {
            calendar.today();
        });
        
        // View change buttons
        document.querySelectorAll('.btn-group .btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const viewType = e.target.getAttribute('data-view');
                let calendarView;
                
                switch(viewType) {
                    case 'month':
                        calendarView = 'dayGridMonth';
                        break;
                    case 'week':
                        calendarView = 'dayGridWeek';
                        break;
                    case 'day':
                        calendarView = 'dayGridDay';
                        break;
                }
                
                calendar.changeView(calendarView);
                
                // Update active state on buttons
                document.querySelectorAll('.btn-group .btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                e.target.classList.add('active');
            });
        });

        // =========== Search Functionality ===========
        
        // Function to show toast notification
        function showToast(message, duration = 3000) {
            toastMessage.textContent = message;
            searchToast.style.display = 'block';
            
            // Trigger reflow to enable CSS transition
            searchToast.offsetHeight;
            
            searchToast.style.opacity = '1';
            
            setTimeout(() => {
                searchToast.style.opacity = '0';
                setTimeout(() => {
                    searchToast.style.display = 'none';
                }, 300);
            }, duration);
        }
        
        // Format date in a readable format
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('default', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }
        
        // Search function
        function searchEvents(query) {
            query = query.toLowerCase().trim();
            
            if (!query) {
                searchResults.style.display = 'none';
                return;
            }
            
            const matchingEvents = events.filter(event => 
                event.title.toLowerCase().includes(query) ||
                (event.description && event.description.toLowerCase().includes(query)) ||
                (event.venue && event.venue.toLowerCase().includes(query))
            );
            
            // Display results
            searchResults.innerHTML = '';
            
            if (matchingEvents.length === 0) {
                searchResults.innerHTML = '<div class="no-results">No events found</div>';
            } else {
                matchingEvents.forEach(event => {
                    const resultItem = document.createElement('div');
                    resultItem.className = 'result-item';
                    resultItem.innerHTML = `
                        <div>${event.title}</div>
                        <div class="result-date">${formatDate(event.start)}</div>
                    `;
                    
                    // Add click event to navigate to this event's date
                    resultItem.addEventListener('click', () => {
                        // Navigate to the date of this event
                        calendar.gotoDate(event.start);
                        
                        // If in day or week view, switch to month view to ensure visibility
                        if (calendar.view.type !== 'dayGridMonth') {
                            calendar.changeView('dayGridMonth');
                            
                            // Update button active state
                            document.querySelectorAll('.btn-group .btn').forEach(btn => {
                                btn.classList.remove('active');
                            });
                            document.querySelector('[data-view="month"]').classList.add('active');
                        }
                        
                        // Clear search and hide results
                        searchInput.value = '';
                        searchResults.style.display = 'none';
                        
                        // Show confirmation toast
                        showToast(`Navigated to "${event.title}" on ${formatDate(event.start)}`);
                    });
                    
                    searchResults.appendChild(resultItem);
                });
            }
            
            searchResults.style.display = 'block';
        }
        
        // Search button click event
        searchBtn.addEventListener('click', () => {
            searchEvents(searchInput.value);
        });
        
        // Search input keyup event (for real-time search)
        searchInput.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') {
                searchEvents(searchInput.value);
            } else if (searchInput.value.length >= 2) {
                searchEvents(searchInput.value);
            } else {
                searchResults.style.display = 'none';
            }
        });
        
        // Hide search results when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && 
                !searchBtn.contains(e.target) && 
                !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>