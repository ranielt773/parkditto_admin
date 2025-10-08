<?php
include "includes/config.php";

// Function to check and update expired transactions
function updateExpiredTransactions($pdo)
{
    try {
        // Get all ongoing transactions that have expired
        $current_time = date('Y-m-d H:i:s');
        $expired_stmt = $pdo->prepare("
            SELECT * FROM transactions 
            WHERE status = 'ongoing' 
            AND expiry_time < ? 
            AND expiry_time IS NOT NULL
        ");
        $expired_stmt->execute([$current_time]);
        $expired_transactions = $expired_stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($expired_transactions as $transaction) {
            // Update transaction status to completed
            $update_stmt = $pdo->prepare("
                UPDATE transactions 
                SET status = 'completed', departure_time = ?
                WHERE id = ?
            ");
            $update_stmt->execute([$current_time, $transaction['id']]);

            // Remove from occupied slots in parking_spaces
            $parking_stmt = $pdo->prepare("SELECT occupied_slots FROM parking_spaces WHERE id = ?");
            $parking_stmt->execute([$transaction['parking_space_id']]);
            $parking_space = $parking_stmt->fetch(PDO::FETCH_ASSOC);

            if ($parking_space) {
                $occupied_slots = json_decode($parking_space['occupied_slots'], true);

                // Remove the slot from occupied slots
                if (isset($occupied_slots[$transaction['vehicle_type']][$transaction['floor']])) {
                    $key = array_search($transaction['lot_number'], $occupied_slots[$transaction['vehicle_type']][$transaction['floor']]);
                    if ($key !== false) {
                        unset($occupied_slots[$transaction['vehicle_type']][$transaction['floor']][$key]);
                        // Re-index array
                        $occupied_slots[$transaction['vehicle_type']][$transaction['floor']] = array_values($occupied_slots[$transaction['vehicle_type']][$transaction['floor']]);
                    }
                }

                // Update available_spaces and available_per_floor
                $update_parking_stmt = $pdo->prepare("
                    UPDATE parking_spaces 
                    SET occupied_slots = ?, 
                        available_spaces = available_spaces + 1,
                        available_per_floor = JSON_SET(
                            available_per_floor,
                            CONCAT('$.', ?, '.', ?),
                            JSON_EXTRACT(available_per_floor, CONCAT('$.', ?, '.', ?)) + 1
                        )
                    WHERE id = ?
                ");
                $update_parking_stmt->execute([
                    json_encode($occupied_slots),
                    $transaction['floor'],
                    $transaction['vehicle_type'],
                    $transaction['floor'],
                    $transaction['vehicle_type'],
                    $transaction['parking_space_id']
                ]);
            }
        }

        return count($expired_transactions);
    } catch (PDOException $e) {
        error_log("Error updating expired transactions: " . $e->getMessage());
        return 0;
    }
}
// Check and update expired transactions before processing
updateExpiredTransactions($pdo);

try {
    $selectedParking = $_GET['parking_id'] ?? 1;
    $selectedFloor = $_GET['floor'] ?? 'Ground';
    $selectedVehicle = $_GET['vehicle'] ?? 'all';

    // Get all parking spaces for dropdown
    $spaces = $pdo->query("SELECT id, name FROM parking_spaces")->fetchAll(PDO::FETCH_ASSOC);
    // ✅ Get selected parking space name

    $selectedParkingName = '';
    foreach ($spaces as $space) {
        if ($space['id'] == $selectedParking) {
            $selectedParkingName = $space['name'];
            break;
        }
    }
    // Get selected parking space details
    $parking_stmt = $pdo->prepare("SELECT * FROM parking_spaces WHERE id = ?");
    $parking_stmt->execute([$selectedParking]);
    $parking_space = $parking_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$parking_space) {
        die("Parking space not found");
    }

    // Decode JSON data
    $vehicle_types = json_decode($parking_space['vehicle_types'], true);
    $floors = json_decode($parking_space['floors'], true);
    $floor_capacity = json_decode($parking_space['floor_capacity'], true);
    $available_per_floor = json_decode($parking_space['available_per_floor'], true);
    $occupied_slots = json_decode($parking_space['occupied_slots'], true);

    // Get transactions for this parking space
    $transactions_sql = "
        SELECT t.*, u.first_name, u.last_name 
        FROM transactions t 
        LEFT JOIN users u ON t.user_id = u.id 
        WHERE t.parking_space_id = ? 
        AND t.status IN ('ongoing', 'pending')
    ";
    $transactions_stmt = $pdo->prepare($transactions_sql);
    $transactions_stmt->execute([$selectedParking]);
    $transactions = $transactions_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create transaction lookup array for quick access
    // Create transaction lookup array for quick access
    $transaction_lookup = [];
    foreach ($transactions as $transaction) {
        $key = $transaction['vehicle_type'] . '_' . $transaction['floor'] . '_' . $transaction['lot_number'];
        $transaction_lookup[$key] = $transaction;
    }

    // Generate parking data based on actual capacity
    // Generate parking data based on actual capacity
    $parking_data = [];
    $slot_counter = 1;

    foreach ($floors as $floor) {
        foreach ($vehicle_types as $vehicle_type => $vehicle_data) {
            $total_slots = $floor_capacity[$floor][$vehicle_type] ?? 0;

            for ($slot_number = 1; $slot_number <= $total_slots; $slot_number++) {
                $key = $vehicle_type . '_' . $floor . '_' . $slot_number;
                $transaction = $transaction_lookup[$key] ?? null;

                // Check if transaction is expired
                $is_expired = false;
                if ($transaction && $transaction['expiry_time']) {
                    $expiry_time = new DateTime($transaction['expiry_time']);
                    $now = new DateTime();
                    $is_expired = $expiry_time <= $now;
                }

                $parking_data[] = [
                    'id' => $slot_counter++,
                    'name' => $transaction ? $transaction['first_name'] . ' ' . $transaction['last_name'] : 'None',
                    'remaining_time' => $transaction ? calculateRemainingTime($transaction['expiry_time'], $transaction['status']) : '-',
                    'slot_number' => $slot_number,
                    'floor' => $floor,
                    'plan' => $transaction ? $transaction['duration_type'] : '-',
                    'vehicle_type' => $vehicle_type,
                    'state' => $transaction ? ($is_expired ? 'completed' : $transaction['status']) : 'available'
                ];
            }
        }
    }

    // Filter data based on floor and vehicle type
    $filtered_data = array_filter($parking_data, function ($row) use ($selectedFloor, $selectedVehicle) {
        $floorMatch = $row['floor'] === $selectedFloor;
        $vehicleMatch = $selectedVehicle === 'all' || $row['vehicle_type'] === $selectedVehicle;
        return $floorMatch && $vehicleMatch;
    });

    // Counts based on actual data
    $available_slots = count(array_filter($parking_data, fn($row) => $row['state'] === 'available'));
    $active_users = count(array_unique(array_column(array_filter($parking_data, fn($row) => $row['state'] !== 'available'), 'name')));

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Helper function to calculate remaining time
// Helper function to calculate remaining time
// Helper function to calculate remaining time
function calculateRemainingTime($expiry_time, $transaction_status)
{
    if (!$expiry_time || $transaction_status === 'completed') {
        return '-';
    }

    $now = new DateTime();
    $expiry = new DateTime($expiry_time);

    if ($expiry <= $now) {
        return 'Expired';
    }

    $interval = $now->diff($expiry);

    // Format based on the time difference
    if ($interval->days > 0) {
        // Show days if more than 1 day remaining
        if ($interval->days >= 30) {
            $months = floor($interval->days / 30);
            $remainingDays = $interval->days % 30;
            if ($remainingDays > 0) {
                return $months . 'm ' . $remainingDays . 'd';
            }
            return $months . 'm';
        } elseif ($interval->days >= 7) {
            $weeks = floor($interval->days / 7);
            $remainingDays = $interval->days % 7;
            if ($remainingDays > 0) {
                return $weeks . 'w ' . $remainingDays . 'd';
            }
            return $weeks . 'w';
        } else {
            return $interval->days . 'd ' . $interval->h . 'h';
        }
    } else {
        // Show hours and minutes if less than 1 day
        return $interval->h . 'h ' . $interval->i . 'm';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parking Management</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/navbar.css">
</head>

<body class="min-h-screen bg-gradient-to-b from-[#fcd462] to-[#FFFFFF] font-[Poppins] text-[#3B060A]">
    <!-- Mobile overlay for closing sidebar -->
    <div class="mobile-overlay" id="mobileOverlay"></div>

    <?php include "includes/navbar.php"; ?>

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
                    <h1 class="text-2xl font-bold text-gray-800 mb-2 md:mb-0">Parking Management</h1>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-gradient-to-r from-purple-600 to-purple-800 text-white rounded-xl p-4 shadow-lg">
                    <h5 class="text-sm font-medium mb-2">Total Spaces</h5>
                    <p class="text-3xl font-bold"><?= $parking_space['total_spaces'] ?></p>
                </div>
                <div class="bg-gradient-to-r from-green-600 to-green-800 text-white rounded-xl p-4 shadow-lg">
                    <h5 class="text-sm font-medium mb-2">Available Slots</h5>
                    <p class="text-3xl font-bold"><?= $available_slots ?></p>
                </div>
                <div class="bg-gradient-to-r from-teal-500 to-teal-700 text-white rounded-xl p-4 shadow-lg">
                    <h5 class="text-sm font-medium mb-2">Active Users</h5>
                    <p class="text-3xl font-bold"><?= $active_users ?></p>
                </div>
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-xl p-4 shadow-lg">
                    <h5 class="text-sm font-medium mb-2">Occupied Slots</h5>
                    <p class="text-3xl font-bold"><?= $parking_space['total_spaces'] - $available_slots ?></p>
                </div>
            </div>

            <!-- Search + Filter -->
            <div class="bg-amber-500 rounded-lg p-4 mb-6 shadow-md">
                <!-- Search Bar -->
                <div class="mb-3">
                    <input type="text"
                        class="w-full px-4 py-2 rounded-lg bg-white text-gray-800 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-600"
                        placeholder="Search Parking Lots" id="searchInput">
                </div>

                <!-- Parking Dropdown + Buttons -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <select
                        class="px-4 py-2 rounded-lg bg-white text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-600"
                        id="parkingFilter" onchange="filterByParking(this.value)">
                        <?php foreach ($spaces as $space): ?>
                            <option value="<?= $space['id'] ?>" <?= $space['id'] == $selectedParking ? 'selected' : '' ?>>
                                <?= htmlspecialchars($space['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- In the buttons section of parkingManagement.php -->
                    <div class="flex flex-wrap gap-2 mt-2 md:mt-0">
                        <button onclick="showDeleteParkingModal(<?= $selectedParking ?>)"
                            class="px-3 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition-colors">
                            Delete
                        </button>
                        <button onclick="showAddParkingModal()"
                            class="px-3 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition-colors">
                            <i class="fas fa-plus mr-1"></i>Add Parking
                        </button>
                        <button onclick="showEditParkingModal(<?= $selectedParking ?>)"
                            class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors">
                            <i class="fas fa-edit mr-1"></i>Edit Parking Space
                        </button>
                    </div>
                </div>
            </div>

            <!-- Parking Table -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 responsive-table" id="parkingTable">
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
                                    Remaining Time</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Slot</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Plan</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <div class="flex flex-col items-center">
                                        <select
                                            class="mt-1 block w-full py-1 px-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 text-sm"
                                            onchange="filterByVehicle(this.value)">
                                            <option value="all" <?= $selectedVehicle === 'all' ? 'selected' : '' ?>>Vehicle
                                            </option>
                                            <?php foreach ($vehicle_types as $vehicle_type => $data): ?>
                                                <option value="<?= $vehicle_type ?>" <?= $selectedVehicle === $vehicle_type ? 'selected' : '' ?>>
                                                    <?= $vehicle_type ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    State</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php $i = 1;
                            foreach ($filtered_data as $row): ?>
                                <?php
                                $stateClass = "bg-gray-200 text-gray-800";
                                if ($row['state'] == "ongoing")
                                    $stateClass = "state-active";
                                if ($row['state'] == "pending")
                                    $stateClass = "state-pending";
                                if ($row['state'] == "cancelled")
                                    $stateClass = "state-block";
                                if ($row['state'] == "completed")
                                    $stateClass = "state-inactive";
                                if ($row['state'] == "available")
                                    $stateClass = "bg-green-100 text-green-800";

                                // vehicle icon using Tailwind
                                $vehicleIcon = "fa-car";
                                if ($row['vehicle_type'] == "Motorcycle")
                                    $vehicleIcon = "fa-motorcycle";
                                if ($row['vehicle_type'] == "Mini Truck")
                                    $vehicleIcon = "fa-truck";

                                // Get transaction ID if exists
                                $transaction_id = 0;
                                if ($row['state'] !== 'available') {
                                    $key = $row['vehicle_type'] . '_' . $row['floor'] . '_' . $row['slot_number'];
                                    $transaction = $transaction_lookup[$key] ?? null;
                                    $transaction_id = $transaction['id'] ?? 0;
                                }
                                ?>
                                <tr class="hover:bg-gray-50" data-transaction-id="<?= $transaction_id ?>"
                                    data-parking-id="<?= $selectedParking ?>">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-700"
                                        data-label="No."><?= $i++ ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700" data-label="Name">
                                        <?= $row['name'] ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"
                                        data-label="Remaining Time">
                                        <?= $row['remaining_time'] ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap" data-label="Slot">
                                        <span
                                            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            <?= $row['slot_number'] ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700" data-label="Plan">
                                        <?= ucfirst($row['plan'] ?? '-') ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700" data-label="Vehicle">
                                        <i class="fas <?= $vehicleIcon ?> text-xl" title="<?= $row['vehicle_type'] ?>"></i>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap" data-label="State">
                                        <span
                                            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $stateClass ?>">
                                            <?= ucfirst($row['state'] ?? 'Available') ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium" data-label="Actions">
                                        <div class="flex space-x-2">
                                            <button class="text-blue-600 hover:text-blue-900"
                                                onclick='openViewModal(<?= json_encode($row) ?>, <?= $transaction_id ?>)'>
                                                <i class="fa fa-eye"></i>
                                            </button>
                                            <?php if ($row['state'] !== 'available'): ?>
                                                <button class="text-yellow-600 hover:text-yellow-900"
                                                    onclick='openEditModal(<?= json_encode($row) ?>, <?= $transaction_id ?>)'>
                                                    <i class="fa fa-pen"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($filtered_data)): ?>
                                <tr>
                                    <td colspan="8" class="px-4 py-4 text-center text-sm text-gray-500">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        No parking slots found for the selected filters.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination / Floor selector -->
            <div class="flex justify-center items-center space-x-4 mt-5">
                <?php
                $currentIndex = array_search($selectedFloor, $floors);
                $prevFloor = $floors[($currentIndex - 1 + count($floors)) % count($floors)] ?? $floors[0];
                $nextFloor = $floors[($currentIndex + 1) % count($floors)] ?? $floors[0];
                ?>
                <a href="?floor=<?= urlencode($prevFloor) ?>&vehicle=<?= urlencode($selectedVehicle) ?>&parking_id=<?= $selectedParking ?>"
                    class="bg-white p-2 rounded-full shadow-md hover:bg-gray-100 transition-colors">
                    <i class="fas fa-chevron-left text-gray-700"></i>
                </a>
                <div class="bg-white px-6 py-2 rounded-full shadow-md font-semibold"><?= $selectedFloor ?></div>
                <a href="?floor=<?= urlencode($nextFloor) ?>&vehicle=<?= urlencode($selectedVehicle) ?>&parking_id=<?= $selectedParking ?>"
                    class="bg-white p-2 rounded-full shadow-md hover:bg-gray-100 transition-colors">
                    <i class="fas fa-chevron-right text-gray-700"></i>
                </a>
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
            const table = document.getElementById('parkingTable');
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

        // Filter functions
        function filterByVehicle(vehicleType) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('vehicle', vehicleType);

            // Preserve the floor and parking_id parameters
            const floor = '<?= $selectedFloor ?>';
            const parkingId = '<?= $selectedParking ?>';
            urlParams.set('floor', floor);
            urlParams.set('parking_id', parkingId);

            window.location.href = '?' + urlParams.toString();
        }

        function filterByParking(parkingId) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('parking_id', parkingId);

            // Preserve the floor and vehicle filters
            const floor = '<?= $selectedFloor ?>';
            const vehicle = '<?= $selectedVehicle ?>';
            urlParams.set('floor', floor);
            urlParams.set('vehicle', vehicle);

            window.location.href = '?' + urlParams.toString();
        }
        // ===== Search Filter by Lot Number =====
        document.getElementById("searchInput").addEventListener("keyup", function () {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll("#parkingTable tbody tr");

            rows.forEach(row => {
                // Slot column = pang-4 (index 3)
                const lotCell = row.cells[3];
                if (lotCell) {
                    const lotText = lotCell.textContent.toLowerCase();
                    if (lotText.includes(searchValue)) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                }
            });
        });

    </script>
    <?php include 'add_parking_modal.php';
    include 'delete_parking_modal.php';
    include "viewParkingManagement_modal.php";
    include "edit_parking_modal.php"; ?>
</body>

</html>