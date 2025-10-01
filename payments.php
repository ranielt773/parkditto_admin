<?php
include 'includes/auth.php';

// Fetch data from the database using PDO
$sql = "SELECT 
            t.id, 
            u.username as name, 
            CONCAT(
                FLOOR(TIMESTAMPDIFF(SECOND, NOW(), t.expiry_time) / 86400), ':', 
                FLOOR((TIMESTAMPDIFF(SECOND, NOW(), t.expiry_time) % 86400) / 3600), ':', 
                FLOOR((TIMESTAMPDIFF(SECOND, NOW(), t.expiry_time) % 3600) / 60)
            ) as remaining,
            t.storm_pass as storm,
            t.duration_type as plan,
            t.status as state
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE t.status IN ('ongoing', 'pending', 'completed', 'cancelled')
        ORDER BY t.created_at DESC";

$stmt = $pdo->query($sql);
$plans = [];
if ($stmt) {
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get counts for stats
$count_total = count($plans);
$count_active = 0;
$count_completed = 0;
$count_pending = 0;
$count_cancelled = 0;
$storm_count = 0;

foreach ($plans as $plan) {
    if ($plan['state'] == 'ongoing')
        $count_active++;
    if ($plan['state'] == 'completed')
        $count_completed++;
    if ($plan['state'] == 'pending')
        $count_pending++;
    if ($plan['state'] == 'cancelled')
        $count_cancelled++;
    if ($plan['storm'] == 'Active')
        $storm_count++;
}

// Get filter status from URL
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plans & Payments</title>
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
                    <h1 class="text-2xl font-bold text-gray-800 mb-2 md:mb-0">Plans & Payments</h1>
                </div>
                <p class="text-gray-600">Welcome, <?php echo $_SESSION['username']; ?>!</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <a href="?status=all" class="transform transition-transform hover:scale-105">
                    <div
                        class="bg-gradient-to-r from-purple-600 to-purple-800 text-white rounded-xl p-4 shadow-lg <?php echo $filter_status == 'all' ? 'ring-2 ring-purple-400' : ''; ?>">
                        <h5 class="text-sm font-medium mb-2">Total Plans</h5>
                        <p class="text-3xl font-bold"><?= $count_total ?></p>
                    </div>
                </a>
                <a href="?status=ongoing" class="transform transition-transform hover:scale-105">
                    <div
                        class="bg-gradient-to-r from-teal-500 to-teal-700 text-white rounded-xl p-4 shadow-lg <?php echo $filter_status == 'ongoing' ? 'ring-2 ring-teal-400' : ''; ?>">
                        <h5 class="text-sm font-medium mb-2">Active Users</h5>
                        <p class="text-3xl font-bold"><?= $count_active ?></p>
                    </div>
                </a>
                <a href="?status=completed" class="transform transition-transform hover:scale-105">
                    <div
                        class="bg-gradient-to-r from-cyan-500 to-cyan-700 text-white rounded-xl p-4 shadow-lg <?php echo $filter_status == 'completed' ? 'ring-2 ring-cyan-400' : ''; ?>">
                        <h5 class="text-sm font-medium mb-2">Completed</h5>
                        <p class="text-3xl font-bold"><?= $count_completed ?></p>
                    </div>
                </a>
                <a href="?status=pending" class="transform transition-transform hover:scale-105">
                    <div
                        class="bg-gradient-to-r from-orange-500 to-orange-700 text-white rounded-xl p-4 shadow-lg <?php echo $filter_status == 'pending' ? 'ring-2 ring-orange-400' : ''; ?>">
                        <h5 class="text-sm font-medium mb-2">Storm Pass Users</h5>
                        <p class="text-3xl font-bold"><?= $storm_count ?></p>
                    </div>
                </a>
            </div>

            <!-- Search + Filter -->
            <div class="bg-amber-500 rounded-lg p-4 mb-6 shadow-md">
                <div class="flex flex-col md:flex-row md:items-center gap-3">
                    <input type="text"
                        class="flex-grow px-4 py-2 rounded-lg bg-white text-gray-800 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-600"
                        placeholder="Search by name, plan, or state..." id="searchInput">

                    <select id="statusFilter"
                        class="px-4 py-2 rounded-lg bg-white text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-600">
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

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 responsive-table" id="plansTable">
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
                                    Storm Pass</th>
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
                            <?php foreach ($plans as $index => $row):
                                // Format the state for display
                                $state_display = ucfirst($row['state']);
                                $state_class = 'state-pending';

                                if ($row['state'] == 'ongoing') {
                                    $state_display = 'Active';
                                    $state_class = 'state-active';
                                } elseif ($row['state'] == 'cancelled') {
                                    $state_display = 'Cancelled';
                                    $state_class = 'state-block';
                                } elseif ($row['state'] == 'completed') {
                                    $state_display = 'Completed';
                                    $state_class = 'state-inactive';
                                }
                                ?>
                                <tr class="hover:bg-gray-50" data-id="<?= $row['id'] ?>"
                                    data-name="<?= strtolower($row['name']) ?>" data-plan="<?= $row['plan'] ?>"
                                    data-state="<?= $row['state'] ?>">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-700"
                                        data-label="No."><?= $index + 1 ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700" data-label="Name">
                                        <?= $row['name'] ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"
                                        data-label="Remaining Time"><?= $row['remaining'] ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap" data-label="Storm Pass">
                                        <span
                                            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $row['storm'] == "Active" ? 'state-active' : 'state-inactive' ?>">
                                            <?= $row['storm'] ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700" data-label="Plan">
                                        <?= ucfirst($row['plan']) ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap" data-label="State">
                                        <span
                                            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $state_class ?>">
                                            <?= $state_display ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium" data-label="Actions">
                                        <div class="flex space-x-2">
                                            <button class="text-yellow-600 hover:text-yellow-900"
                                                onclick="editRow(<?= $row['id'] ?>)">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button class="text-blue-600 hover:text-blue-900"
                                                onclick="viewDetails(<?= $row['id'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="text-red-600 hover:text-red-900"
                                                onclick="deleteRow(<?= $row['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
            <div class="flex justify-between items-center px-6 py-4 border-b">
                <h3 class="text-lg font-semibold">Payment Details</h3>
                <button onclick="closeModal('viewModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 space-y-4" id="viewContent"></div>
            <div class="flex justify-end px-6 py-4 border-t">
                <button onclick="closeModal('viewModal')"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
            <div class="flex justify-between items-center px-6 py-4 border-b">
                <h3 class="text-lg font-semibold">Edit Information</h3>
                <button onclick="closeModal('editModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="editForm">
                <div class="p-6 space-y-4">
                    <input type="hidden" id="editId">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" id="editName"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            disabled>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Plan</label>
                        <select id="editPlan"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="monthly">Monthly</option>
                            <option value="weekly">Weekly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Storm Pass</label>
                        <select id="editStorm"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="Active">Active</option>
                            <option value="None">None</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                        <select id="editState"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="ongoing">Active</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end px-6 py-4 border-t">
                    <button type="button" onclick="closeModal('editModal')"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors mr-2">Cancel</button>
                    <button type="button" onclick="saveEdit()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">Save</button>
                </div>
            </form>
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

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        // Make table responsive on small screens
        function makeTableResponsive() {
            const table = document.getElementById('plansTable');
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

        // Search and Filter functionality
        function initSearchAndFilter() {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const tableRows = document.querySelectorAll('#plansTable tbody tr');

            // Add event listeners
            searchInput.addEventListener('input', filterTable);
            statusFilter.addEventListener('change', filterTable);

            function filterTable() {
                const searchText = searchInput.value.toLowerCase();
                const statusValue = statusFilter.value;

                tableRows.forEach(row => {
                    const name = row.getAttribute('data-name') || '';
                    const plan = row.getAttribute('data-plan') || '';
                    const state = row.getAttribute('data-state') || '';

                    // Check if row matches search criteria
                    const matchesSearch = name.includes(searchText) ||
                        plan.toLowerCase().includes(searchText) ||
                        state.toLowerCase().includes(searchText);

                    // Check if row matches status filter
                    const matchesStatus = statusValue === 'all' || state === statusValue;

                    // Show or hide row based on filters
                    if (matchesSearch && matchesStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            // Initial filter call
            filterTable();
        }

        // Initialize search and filter
        initSearchAndFilter();

        // Status filter functionality
        document.getElementById('statusFilter').addEventListener('change', function () {
            const status = this.value;
            window.location.href = `?status=${status}`;
        });

        function viewDetails(id) {
            let row = document.querySelector(`tr[data-id='${id}']`);
            let details = `
        <p><b>Name:</b> ${row.cells[1].textContent}</p>
        <p><b>Remaining Time:</b> ${row.cells[2].textContent}</p>
        <p><b>Storm Pass:</b> ${row.cells[3].textContent}</p>
        <p><b>Plan:</b> ${row.cells[4].textContent}</p>
        <p><b>State:</b> ${row.cells[5].textContent}</p>
      `;
            document.getElementById("viewContent").innerHTML = details;
            openModal('viewModal');
        }

        function editRow(id) {
            let row = document.querySelector(`tr[data-id='${id}']`);
            document.getElementById("editId").value = id;
            document.getElementById("editName").value = row.cells[1].textContent;
            document.getElementById("editPlan").value = row.cells[4].textContent.toLowerCase();

            // Extract storm pass value from badge
            const stormText = row.cells[3].textContent.trim();
            document.getElementById("editStorm").value = stormText;

            // Extract state value from badge
            const stateText = row.cells[5].textContent.trim();
            let stateValue = 'ongoing';
            if (stateText === 'Cancelled') stateValue = 'cancelled';
            else if (stateText === 'Completed') stateValue = 'completed';

            document.getElementById("editState").value = stateValue;
            openModal('editModal');
        }

        function saveEdit() {
            const id = document.getElementById("editId").value;
            const plan = document.getElementById("editPlan").value;
            const storm = document.getElementById("editStorm").value;
            const state = document.getElementById("editState").value;

            // Send the data to the server using AJAX
            fetch('update_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}&plan=${plan}&storm=${storm}&state=${state}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the table row with new values
                        let row = document.querySelector(`tr[data-id='${id}']`);
                        row.cells[4].textContent = plan.charAt(0).toUpperCase() + plan.slice(1);

                        // Update storm pass with badge
                        const stormClass = storm === 'Active' ? 'state-active' : 'state-inactive';
                        row.cells[3].innerHTML = `<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${stormClass}">${storm}</span>`;

                        // Update state with badge
                        let stateDisplay = '';
                        let stateClass = 'state-inactive';

                        if (state === 'ongoing') {
                            stateClass = 'state-active';
                            stateDisplay = 'Active';
                        } else if (state === 'cancelled') {
                            stateClass = 'state-block';
                            stateDisplay = 'Cancelled';
                        } else if (state === 'completed') {
                            stateClass = 'state-inactive';
                            stateDisplay = 'Completed';
                        }

                        row.cells[5].innerHTML = `<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${stateClass}">${stateDisplay}</span>`;

                        // Update data attributes for filtering
                        row.setAttribute('data-plan', plan);
                        row.setAttribute('data-state', state.toLowerCase());

                        // Close the modal
                        closeModal('editModal');

                        // Show success message
                        alert('Information updated successfully!');
                    } else {
                        alert('Error updating information: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the information.');
                });
        }

        function deleteRow(id) {
            if (confirm("Are you sure you want to delete this record?")) {
                // Send delete request to server
                fetch('delete_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the row from the table
                            document.querySelector(`tr[data-id='${id}']`).remove();
                            alert('Record deleted successfully!');
                        } else {
                            alert('Error deleting record: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the record.');
                    });
            }
        }
    </script>
</body>

</html>