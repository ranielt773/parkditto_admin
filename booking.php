<?php
include 'includes/auth.php';
// ===== DATABASE CONNECTION =====
$host = "localhost";
$user = "root"; // palitan depende sa DB mo
$pass = "";
$dbname = "parkditto"; // pangalan ng database mo

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ===== GET FILTER STATUS FROM URL =====
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// ===== BUILD SQL QUERY BASED ON FILTER =====
if ($filter_status == 'all') {
    $sql = "SELECT t.*, u.first_name, u.last_name 
            FROM transactions t
            JOIN users u ON t.user_id = u.id
            ORDER BY t.id DESC";
} else {
    $sql = "SELECT t.*, u.first_name, u.last_name 
            FROM transactions t
            JOIN users u ON t.user_id = u.id
            WHERE t.status = '$filter_status'
            ORDER BY t.id DESC";
}

$result = $conn->query($sql);

// ===== COUNTS =====
$count_total = $conn->query("SELECT COUNT(*) as total FROM transactions")->fetch_assoc()['total'];
$count_completed = $conn->query("SELECT COUNT(*) as total FROM transactions WHERE status='completed'")->fetch_assoc()['total'];
$count_active = $conn->query("SELECT COUNT(*) as total FROM transactions WHERE status='ongoing'")->fetch_assoc()['total'];
$count_pending = $conn->query("SELECT COUNT(*) as total FROM transactions WHERE status='pending'")->fetch_assoc()['total'];
$count_cancelled = $conn->query("SELECT COUNT(*) as total FROM transactions WHERE status='cancelled'")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ParkDitto - Bookings</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/navbar.css">
</head>

<body class="min-h-screen bg-gradient-to-b from-[#fcd462] to-[#FFFFFF] font-[Poppins] text-[#3B060A]">
    <!-- Mobile overlay for closing sidebar -->
    <div class="mobile-overlay" id="mobileOverlay"></div>

    <!-- Sidebar -->
  <?php include "includes/navbar.php";?>
    <!-- Main content -->
    <div class="ml-64 flex-1 overflow-auto transition-all duration-300" id="mainContent">
        <div class="p-4 md:p-6">
            <!-- Header -->
            <div
                class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 pb-4 border-b border-gray-200">
                <div class="flex items-center">
                    <button class="md:hidden bg-red-800 text-white p-2 rounded-md shadow-lg mr-3"
                        id="sidebarToggleMobile">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2 md:mb-0">Bookings Overview</h1>
                </div>
                <p class="text-gray-600">Welcome, <?php echo $_SESSION['username']; ?>!</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <a href="?status=all" class="transform transition-transform hover:scale-105">
                    <div
                        class="bg-gradient-to-r from-purple-600 to-purple-800 text-white rounded-xl p-4 shadow-lg <?php echo $filter_status == 'all' ? 'ring-2 ring-purple-400' : ''; ?>">
                        <h5 class="text-sm font-medium mb-2">Total Bookings</h5>
                        <p class="text-3xl font-bold"><?= $count_total ?></p>
                    </div>
                </a>
                <a href="?status=ongoing" class="transform transition-transform hover:scale-105">
                    <div
                        class="bg-gradient-to-r from-teal-500 to-teal-700 text-white rounded-xl p-4 shadow-lg <?php echo $filter_status == 'ongoing' ? 'ring-2 ring-teal-400' : ''; ?>">
                        <h5 class="text-sm font-medium mb-2">Active Bookings</h5>
                        <p class="text-3xl font-bold"><?= $count_active ?></p>
                    </div>
                </a>
                <a href="?status=pending" class="transform transition-transform hover:scale-105">
                    <div
                        class="bg-gradient-to-r from-orange-500 to-orange-700 text-white rounded-xl p-4 shadow-lg <?php echo $filter_status == 'pending' ? 'ring-2 ring-orange-400' : ''; ?>">
                        <h5 class="text-sm font-medium mb-2">Pending</h5>
                        <p class="text-3xl font-bold"><?= $count_pending ?></p>
                    </div>
                </a>
                <a href="?status=completed" class="transform transition-transform hover:scale-105">
                    <div
                        class="bg-gradient-to-r from-cyan-500 to-cyan-700 text-white rounded-xl p-4 shadow-lg <?php echo $filter_status == 'completed' ? 'ring-2 ring-cyan-400' : ''; ?>">
                        <h5 class="text-sm font-medium mb-2">Completed</h5>
                        <p class="text-3xl font-bold"><?= $count_completed ?></p>
                    </div>
                </a>
            </div>

            <!-- Search + Filter -->
            <div class="bg-amber-500 rounded-lg p-4 mb-6 shadow-md">
                <div class="flex flex-col md:flex-row md:items-center gap-3">
                    <input type="text"
                        class="flex-grow px-4 py-2 rounded-lg bg-white text-gray-800 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-600"
                        placeholder="Search Bookings" id="searchInput">

                    <select
                        class="px-4 py-2 rounded-lg bg-white text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-600"
                        id="statusFilter">
                        <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="ongoing" <?php echo $filter_status == 'ongoing' ? 'selected' : ''; ?>>Ongoing
                        </option>
                        <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending
                        </option>
                        <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed
                        </option>
                        <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled
                        </option>
                    </select>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 responsive-table" id="bookingsTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    No.</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Name</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Remaining time</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Storm pass</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Plan</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    State</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $i = 1;
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $fullname = $row['first_name'] . " " . $row['last_name'];

                                    // Remaining time (expiry_time - now)
                                    $remaining = "N/A";
                                    if ($row['expiry_time']) {
                                        $exp = new DateTime($row['expiry_time']);
                                        $now = new DateTime();
                                        if ($exp > $now) {
                                            $diff = $now->diff($exp);
                                            $remaining = $diff->days . "d " . $diff->h . ":" . $diff->i . ":" . $diff->s;
                                        } else {
                                            $remaining = "Expired";
                                        }
                                    }

                                    // Storm pass (example: kapag may reservation type = 'reservation')
                                    $storm_pass = ($row['transaction_type'] == "reservation") ? "Active" : "None";
                                    $storm_class = ($storm_pass == "Active") ? "state-active" : "state-inactive";

                                    // Duration/Plan
                                    $plan = ucfirst($row['duration_type']);

                                    // State (status field)
                                    $state = ucfirst($row['status']);
                                    $stateClass = "state-pending";
                                    if ($row['status'] == "ongoing")
                                        $stateClass = "state-active";
                                    if ($row['status'] == "cancelled")
                                        $stateClass = "state-block";
                                    if ($row['status'] == "completed")
                                        $stateClass = "state-inactive";
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-700"
                                            data-label="No."><?= $i++ ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700" data-label="Name">
                                            <?= $fullname ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"
                                            data-label="Remaining time"><?= $remaining ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap" data-label="Storm pass">
                                            <span
                                                class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $storm_class ?>"><?= $storm_pass ?></span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700" data-label="Plan">
                                            <?= $plan ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap" data-label="State">
                                            <span
                                                class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $stateClass ?>"><?= $state ?></span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium" data-label="Actions">
                                            <div class="flex space-x-2">
                                                <button class="text-blue-600 hover:text-blue-900">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                                <button class="text-yellow-600 hover:text-yellow-900">
                                                    <i class="fa fa-pen"></i>
                                                </button>
                                                <button class="text-red-600 hover:text-red-900">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="7" class="px-4 py-4 text-center text-sm text-gray-500">No bookings found</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const mobileOverlay = document.getElementById('mobileOverlay');
        const sidebarToggleMobile = document.getElementById('sidebarToggleMobile');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            mobileOverlay.classList.toggle('active');
        }

        sidebarToggleMobile.addEventListener('click', toggleSidebar);
        mobileOverlay.addEventListener('click', toggleSidebar);

        // Automatically collapse sidebar on medium screens
        function handleResponsiveSidebar() {
            if (window.innerWidth <= 1024) {
                sidebar.classList.add('sidebar-collapsed');
                mainContent.classList.add('main-content-expanded');
            } else {
                sidebar.classList.remove('sidebar-collapsed');
                mainContent.classList.remove('main-content-expanded');
            }

            if (window.innerWidth > 768) {
                sidebar.classList.remove('sidebar-mobile-hidden');
                mainContent.classList.remove('main-content-full');
                mobileOverlay.classList.remove('active');
            }
        }

        // Initial call
        handleResponsiveSidebar();

        // Listen for window resize
        window.addEventListener('resize', handleResponsiveSidebar);

        // Make table responsive on small screens
        function makeTableResponsive() {
            const table = document.getElementById('bookingsTable');
            if (window.innerWidth < 768) {
                table.classList.add('responsive-table');

                // Add data attributes for mobile labels
                const headers = [];
                table.querySelectorAll('thead th').forEach((header, index) => {
                    headers[index] = header.textContent.trim();
                });

                table.querySelectorAll('tbody tr').forEach(row => {
                    row.querySelectorAll('td').forEach((cell, index) => {
                        cell.setAttribute('data-label', headers[index]);
                    });
                });
            } else {
                table.classList.remove('responsive-table');

                // Remove data attributes
                table.querySelectorAll('tbody td').forEach(cell => {
                    cell.removeAttribute('data-label');
                });
            }
        }

        // Initial call
        makeTableResponsive();

        // Call on window resize
        window.addEventListener('resize', makeTableResponsive);

        // Status filter functionality
        document.getElementById('statusFilter').addEventListener('change', function () {
            const status = this.value;
            window.location.href = `?status=${status}`;
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function () {
            const searchText = this.value.toLowerCase();
            const rows = document.querySelectorAll('#bookingsTable tbody tr');

            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const plan = row.cells[4].textContent.toLowerCase();
                const state = row.cells[5].textContent.toLowerCase();

                if (name.includes(searchText) || plan.includes(searchText) || state.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>