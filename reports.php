<?php
include 'includes/auth.php';
require __DIR__ . '/includes/config.php';

// Default filter values
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'monthly';
$current_date = date('Y-m-d H:i:s');

// Calculate date ranges based on filter
switch($filter) {
    case 'daily':
        $start_date = date('Y-m-d 00:00:00');
        $end_date = date('Y-m-d 23:59:59');
        break;
    case 'weekly':
        $start_date = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $end_date = date('Y-m-d 23:59:59', strtotime('sunday this week'));
        break;
    case 'monthly':
    default:
        $start_date = date('Y-m-01 00:00:00');
        $end_date = date('Y-m-t 23:59:59');
        break;
}

// Fetch new user registrations based on filter
try {
    $stmt = $pdo->prepare("
        SELECT id, username, email, first_name, last_name, created_at 
        FROM users 
        WHERE created_at BETWEEN ? AND ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $new_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $new_users = [];
    $error = "Error fetching user data: " . $e->getMessage();
}

// Fetch feedbacks based on filter
try {
    $feedback_filter = isset($_GET['feedback_filter']) ? $_GET['feedback_filter'] : $filter;
    
    switch($feedback_filter) {
        case 'daily':
            $feedback_start_date = date('Y-m-d 00:00:00');
            $feedback_end_date = date('Y-m-d 23:59:59');
            break;
        case 'weekly':
            $feedback_start_date = date('Y-m-d 00:00:00', strtotime('monday this week'));
            $feedback_end_date = date('Y-m-d 23:59:59', strtotime('sunday this week'));
            break;
        case 'monthly':
        default:
            $feedback_start_date = date('Y-m-01 00:00:00');
            $feedback_end_date = date('Y-m-t 23:59:59');
            break;
    }
    
    $stmt = $pdo->prepare("
        SELECT f.*, u.first_name, u.last_name, u.username, ps.name as parking_space_name
        FROM feedback f
        JOIN users u ON f.user_id = u.id
        JOIN parking_spaces ps ON f.parking_space_id = ps.id
        WHERE f.created_at BETWEEN ? AND ?
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$feedback_start_date, $feedback_end_date]);
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $feedbacks = [];
    $feedback_error = "Error fetching feedback data: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <title>Reports</title>
    <link rel="stylesheet" href="css/navbar.css">
</head>

<body class="min-h-screen bg-gradient-to-b from-[#fcd462] to-[#FFFFFF] font-[Poppins] text-[#3B060A]">
    <!-- Mobile overlay for closing sidebar -->
    <div class="mobile-overlay" id="mobileOverlay"></div>

    <!-- Sidebar -->
    <?php include "includes/navbar.php";?>

    <!-- Main Content -->
    <div class="ml-64 flex-1 overflow-auto transition-all duration-300" id="mainContent">
        <div class="p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 pb-4 border-b border-gray-200">
                <div class="flex items-center">
                    <button class="md:hidden bg-red-800 text-white p-2 rounded-md shadow-lg mr-3"
                        id="sidebarToggleMobile">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2 md:mb-0">Reports</h1>
                </div>
            </div>

            <!-- Top Row: New User Registrations & Feedbacks -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

                <!-- New User Registrations -->
                <div class="bg-white rounded-xl shadow-md p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h2 class="font-semibold text-lg text-[#3B060A]">New User Registrations</h2>
                        <form method="GET" class="m-0">
                            <select name="filter" onchange="this.form.submit()" class="bg-[#FDF7D8] rounded-md px-2 py-1 text-sm">
                                <option value="daily" <?php echo $filter == 'daily' ? 'selected' : ''; ?>>Daily</option>
                                <option value="weekly" <?php echo $filter == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="monthly" <?php echo $filter == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                            </select>
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full table-fixed text-sm text-left">
                            <thead class="bg-[#D9D9D973] text-[#3B060A]">
                                <tr>
                                    <th class="px-3 py-2 w-12">No.</th>
                                    <th class="px-3 py-2 w-40">Name</th>
                                    <th class="px-3 py-2 w-48">Time of Registration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($new_users)): ?>
                                    <?php $counter = 1; ?>
                                    <?php foreach ($new_users as $user): ?>
                                        <tr class="border-b">
                                            <td class="px-3 py-2"><?php echo $counter++; ?>.</td>
                                            <td class="px-3 py-2">
                                                <?php 
                                                $full_name = trim($user['first_name'] . ' ' . $user['last_name']);
                                                echo !empty($full_name) ? htmlspecialchars($full_name) : htmlspecialchars($user['username']);
                                                ?>
                                            </td>
                                            <td class="px-3 py-2">
                                                <?php echo date('d/m/y g:ia', strtotime($user['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="px-3 py-4 text-center text-gray-500">
                                            No new user registrations found for the selected period.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Feedbacks -->
                <div class="bg-white rounded-xl shadow-md p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h2 class="font-semibold text-lg text-[#3B060A]">Feedbacks</h2>
                        <form method="GET" class="m-0">
                            <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                            <select name="feedback_filter" onchange="this.form.submit()" class="bg-[#FDF7D8] rounded-md px-2 py-1 text-sm">
                                <option value="daily" <?php echo (isset($_GET['feedback_filter']) ? $_GET['feedback_filter'] : $filter) == 'daily' ? 'selected' : ''; ?>>Daily</option>
                                <option value="weekly" <?php echo (isset($_GET['feedback_filter']) ? $_GET['feedback_filter'] : $filter) == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="monthly" <?php echo (isset($_GET['feedback_filter']) ? $_GET['feedback_filter'] : $filter) == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                            </select>
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full table-fixed text-sm text-left">
                            <thead class="bg-[#D9D9D973] text-[#3B060A]">
                                <tr>
                                    <th class="px-3 py-2 w-12">No.</th>
                                    <th class="px-3 py-2 w-40">Name</th>
                                    <th class="px-3 py-2 w-48">Parking Space</th>
                                    <th class="px-3 py-2 w-64">Messages</th>
                                    <th class="px-3 py-2 w-20">Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($feedbacks)): ?>
                                    <?php $counter = 1; ?>
                                    <?php foreach ($feedbacks as $feedback): ?>
                                        <tr class="border-b">
                                            <td class="px-3 py-2"><?php echo $counter++; ?>.</td>
                                            <td class="px-3 py-2">
                                                <?php 
                                                $full_name = trim($feedback['first_name'] . ' ' . $feedback['last_name']);
                                                echo !empty($full_name) ? htmlspecialchars($full_name) : htmlspecialchars($feedback['username']);
                                                ?>
                                            </td>
                                            <td class="px-3 py-2"><?php echo htmlspecialchars($feedback['parking_space_name']); ?></td>
                                            <td class="px-3 py-2"><?php echo htmlspecialchars($feedback['message']); ?></td>
                                            <td class="px-3 py-2">
                                                <?php if ($feedback['rating']): ?>
                                                    <div class="flex items-center">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star <?php echo $i <= $feedback['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?> text-sm"></i>
                                                        <?php endfor; ?>
                                                        <span class="ml-1 text-xs">(<?php echo $feedback['rating']; ?>)</span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-gray-400 text-xs">No rating</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-3 py-4 text-center text-gray-500">
                                            No feedbacks found for the selected period.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Rest of your existing code remains the same -->
            <!-- CCTV Request -->
            <h2 class="font-semibold text-lg text-[#3B060A] mb-0">CCTV Request</h2>

            <!-- Header Row (outside table) -->
            <div class="overflow-x-auto mb-0 px-4">
                <table class="w-full table-fixed text-sm text-left">
                    <thead class="text-[#3B060A]">
                        <tr>
                            <th class="px-3 py-2 w-12 font-medium text-center">No.</th>
                            <th class="px-3 py-2 w-40 font-medium text-center">Name</th>
                            <th class="px-3 py-2 w-32 font-medium text-center">Remaining Time</th>
                            <th class="px-3 py-2 w-48 font-medium text-center">Place</th>
                            <th class="px-3 py-2 w-24 font-medium text-center">Floor</th>
                            <th class="px-3 py-2 w-28 font-medium text-center">Plan</th>
                            <th class="px-3 py-2 w-16 font-medium text-center">Allow</th>
                            <th class="px-3 py-2 w-16 font-medium text-center">Deny</th>
                        </tr>
                    </thead>
                </table>
            </div>

            <!-- Table with data only -->
            <div class="bg-white rounded-xl shadow-md p-4 mb-6">
                <div class="overflow-x-auto">
                    <table class="w-full table-fixed text-sm text-left">
                        <tbody>
                            <tr class="border-b h-14">
                                <td class="px-3 py-2 w-12 truncate text-center">1.</td>
                                <td class="px-3 py-2 w-40 truncate text-center">Juan Dela Cruz</td>
                                <td class="px-3 py-2 w-32 truncate text-center">365:12:48</td>
                                <td class="px-3 py-2 w-48 truncate text-center">Farmdale Parking</td>
                                <td class="px-3 py-2 w-24 truncate text-center">Ground</td>
                                <td class="px-3 py-2 w-28 truncate text-center">Monthly</td>
                                <td class="px-3 py-2 w-16 text-center">
                                    <img src="assets/check_icon.png" alt="Allow"
                                        class="w-8 h-8 mx-auto cursor-pointer hover:scale-110 transition-transform">
                                </td>
                                <td class="px-3 py-2 w-16 text-center">
                                    <img src="assets/x_icon.png" alt="Deny"
                                        class="w-8 h-8 mx-auto cursor-pointer hover:scale-110 transition-transform">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bottom Row (same as before but status fixed size) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- User IDs -->
                <div class="bg-white rounded-xl shadow-md p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h2 class="font-semibold text-lg text-[#3B060A]">User IDs for verification</h2>
                        <select class="bg-[#FDF7D8] rounded-md px-2 py-1 text-sm">
                            <option>Senior</option>
                            <option>Regular</option>
                        </select>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full table-fixed text-sm text-left">
                            <thead class="bg-[#D9D9D973] text-[#3B060A]">
                                <tr>
                                    <th class="px-3 py-2 w-12">No.</th>
                                    <th class="px-3 py-2 w-40">Name</th>
                                    <th class="px-3 py-2 w-28">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b">
                                    <td class="px-3 py-2">1.</td>
                                    <td class="px-3 py-2">Juan Dela Cruz</td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="inline-flex items-center justify-center w-24 h-8 bg-green-500 text-white rounded">
                                                Approved
                                            </span>
                                            <div
                                                class="bg-[#8390FF69] rounded p-1.5 cursor-pointer hover:bg-[#8390FF99] transition-colors">
                                                <img src="assets/eye_icon.png" alt="View" class="w-5 h-5">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="px-3 py-2">2.</td>
                                    <td class="px-3 py-2">Juan Dela Cruz</td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="inline-flex items-center justify-center w-24 h-8 bg-yellow-400 text-white rounded">
                                                Pending
                                            </span>
                                            <div
                                                class="bg-[#8390FF69] rounded p-1.5 cursor-pointer hover:bg-[#8390FF99] transition-colors">
                                                <img src="assets/eye_icon.png" alt="View" class="w-5 h-5">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Incident Log -->
                <div class="bg-white rounded-xl shadow-md p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h2 class="font-semibold text-lg text-[#3B060A]">Incident Log</h2>
                        <select class="bg-[#FDF7D8] rounded-md px-2 py-1 text-sm">
                            <option>Leads parking</option>
                            <option>Farmdale</option>
                        </select>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full table-fixed text-sm text-left">
                            <thead class="bg-[#D9D9D973] text-[#3B060A]">
                                <tr>
                                    <th class="px-3 py-2 w-12">No.</th>
                                    <th class="px-3 py-2 w-40">Name</th>
                                    <th class="px-3 py-2 w-28">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b">
                                    <td class="px-3 py-2">1.</td>
                                    <td class="px-3 py-2">Juan Dela Cruz</td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="inline-flex items-center justify-center w-24 h-8 bg-green-500 text-white rounded">
                                                Approved
                                            </span>
                                            <div
                                                class="bg-[#8390FF69] rounded p-1.5 cursor-pointer hover:bg-[#8390FF99] transition-colors">
                                                <img src="assets/eye_icon.png" alt="View" class="w-5 h-5">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="px-3 py-2">2.</td>
                                    <td class="px-3 py-2">Juan Dela Cruz</td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="inline-flex items-center justify-center w-24 h-8 bg-yellow-400 text-white rounded">
                                                Pending
                                            </span>
                                            <div
                                                class="bg-[#8390FF69] rounded p-1.5 cursor-pointer hover:bg-[#8390FF99] transition-colors">
                                                <img src="assets/eye_icon.png" alt="View" class="w-5 h-5">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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
</script>

</body>

</html>