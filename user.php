<?php
include 'includes/auth.php';

// ===== DATABASE CONNECTION =====
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "parkditto";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// ===== GET COMBINED USERS (USERS + PARKING OWNERS) =====
$sql = "(SELECT id, first_name as firstname, last_name as lastname, username, email, password, type as role, 'user' as user_type, created_at 
         FROM users)
        UNION ALL
        (SELECT id, firstname, lastname, username, email, password, 'owner' as role, 'owner' as user_type, date_created as created_at 
         FROM parking_owners)
        ORDER BY created_at DESC";

$result = $conn->query($sql);

// ===== COUNT USERS =====
$count_total = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$count_active = $conn->query("SELECT COUNT(*) as total FROM users WHERE type='user'")->fetch_assoc()['total'];
$count_owners = $conn->query("SELECT COUNT(*) as total FROM parking_owners")->fetch_assoc()['total'];
$count_all_combined = $count_total + $count_owners; // Total combined count

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Users</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/navbar.css">
  <style>
    .user-badge {
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: bold;
    }

    .user-role {
      background: #e3f2fd;
      color: #1976d2;
    }

    .owner-role {
      background: #fff3e0;
      color: #f57c00;
    }

    .staff-role {
      background: #e8f5e8;
      color: #2e7d32;
    }

    .state-active {
      background-color: #d1fae5;
      color: #065f46;
    }

    .state-inactive {
      background-color: #fee2e2;
      color: #991b1b;
    }
  </style>
</head>

<body class="min-h-screen bg-gradient-to-b from-[#fcd462] to-[#FFFFFF] font-[Poppins] text-[#3B060A]">
  <!-- Mobile overlay for closing sidebar -->
  <div class="mobile-overlay" id="mobileOverlay"></div>

  <!-- Sidebar -->
  <?php include "includes/navbar.php"; ?>

  <!-- Main content -->
  <div class="ml-64 flex-1 overflow-auto transition-all duration-300" id="mainContent">
    <div class="p-4 md:p-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 pb-4 border-b border-gray-200">
        <div class="flex items-center">
          <button class="md:hidden bg-red-800 text-white p-2 rounded-md shadow-lg mr-3" id="sidebarToggleMobile">
            <i class="fas fa-bars"></i>
          </button>
          <h1 class="text-2xl font-bold text-gray-800 mb-2 md:mb-0">All Users & Owners</h1>
        </div>
        <p class="text-gray-600">Welcome, <?php echo $_SESSION['username']; ?>!</p>
      </div>

      <!-- Toast Notification -->
      <?php if (isset($_GET['msg']) && !empty($_GET['msg'])): ?>
        <div id="toastMsg"
          class="fixed top-4 right-4 bg-green-600 text-white px-4 py-3 rounded-lg shadow-lg z-50 transition-opacity duration-300">
          <div class="flex items-center">
            <span><?= htmlspecialchars($_GET['msg']) ?></span>
            <button onclick="document.getElementById('toastMsg').classList.add('opacity-0')" class="ml-4 text-white">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      <?php endif; ?>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-r from-purple-500 to-purple-700 text-white rounded-xl p-4 shadow-lg">
          <h5 class="text-sm font-medium mb-2">Parking Lot Owners</h5>
          <p class="text-3xl font-bold"><?= $count_owners ?></p>
        </div>
        <div class="bg-gradient-to-r from-blue-500 to-blue-700 text-white rounded-xl p-4 shadow-lg">
          <h5 class="text-sm font-medium mb-2">App Users</h5>
          <p class="text-3xl font-bold"><?= $count_total ?></p>
        </div>
        <div class="bg-gradient-to-r from-green-500 to-green-700 text-white rounded-xl p-4 shadow-lg">
          <h5 class="text-sm font-medium mb-2">Active Users</h5>
          <p class="text-3xl font-bold"><?= $count_active ?></p>
        </div>
        <div class="bg-gradient-to-r from-red-500 to-red-700 text-white rounded-xl p-4 shadow-lg">
          <h5 class="text-sm font-medium mb-2">Total Combined</h5>
          <p class="text-3xl font-bold"><?= $count_all_combined ?></p>
        </div>
      </div>

      <!-- Search + Filter -->
      <div class="bg-amber-500 rounded-lg p-4 mb-6 shadow-md">
        <div class="flex flex-col md:flex-row md:items-center gap-3">
          <input type="text"
            class="flex-grow px-4 py-2 rounded-lg bg-white text-gray-800 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-600"
            placeholder="Search Users (Name/Email/Username)" id="searchInput">

          <select
            class="px-4 py-2 rounded-lg bg-white text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-600"
            id="userTypeFilter">
            <option value="all">All Types</option>
            <option value="user">App User</option>
            <option value="owner">Parking Owner</option>
          </select>

        </div>
        <div class="flex justify-end mt-4 mb-1">
          <button onclick="openModal('addModal')"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
            <i class="fas fa-user-plus mr-2"></i> Add User
          </button>
        </div>
      </div>

      <!-- Users Table -->
      <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 responsive-table" id="usersTable">
            <thead>
              <tr>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border-b border-gray-200">
                  No.</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border-b border-gray-200">
                  Name</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border-b border-gray-200">
                  Username</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border-b border-gray-200">
                  Email</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border-b border-gray-200">
                  Role</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border-b border-gray-200">
                  User Type</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border-b border-gray-200">
                  State</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border-b border-gray-200">
                  Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php
              $i = 1;
              if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                  $fullname = $row['firstname'] . " " . $row['lastname'];
                  $role = strtolower($row['role']);
                  $user_type = $row['user_type'];
                  $state = !empty($row['email']) ? "Active" : "Inactive";
                  $stateClass = ($state == "Active") ? "state-active" : "state-inactive";

                  // Determine badge class based on role
                  $roleBadgeClass = '';
                  if ($role == 'owner') {
                    $roleBadgeClass = 'owner-role';
                  } elseif ($role == 'staff') {
                    $roleBadgeClass = 'staff-role';
                  } else {
                    $roleBadgeClass = 'user-role';
                  }
                  ?>
                  <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-700" data-label="No."><?= $i++ ?>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 user-name" data-label="Name">
                      <?= $fullname ?>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 user-username" data-label="Username">
                      <?= $row['username'] ?>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 user-email" data-label="Email">
                      <?= $row['email'] ?>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 user-role" data-label="Role">
                      <span class="user-badge <?= $roleBadgeClass ?>"><?= ucfirst($role) ?></span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 user-type" data-label="User Type">
                      <span class="user-badge <?= $user_type == 'owner' ? 'owner-role' : 'user-role' ?>">
                        <?= $user_type == 'owner' ? 'Parking Owner' : 'App User' ?>
                      </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap" data-label="State">
                      <span
                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $stateClass ?>"><?= $state ?></span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium" data-label="Actions">
                      <div class="flex space-x-2">
                        <button class="text-blue-600 hover:text-blue-900 view-btn" data-name="<?= $fullname ?>"
                          data-username="<?= $row['username'] ?>" data-email="<?= $row['email'] ?>" data-role="<?= $role ?>"
                          data-user-type="<?= $user_type ?>" data-state="<?= $state ?>">
                          <i class="fa fa-eye"></i>
                        </button>
                        <button class="text-yellow-600 hover:text-yellow-900 edit-btn" data-id="<?= $row['id'] ?>"
                          data-user-type="<?= $user_type ?>">
                          <i class="fa fa-pen"></i>
                        </button>
                        <button class="text-red-600 hover:text-red-900 delete-btn" data-id="<?= $row['id'] ?>"
                          data-user-type="<?= $user_type ?>" data-name="<?= $fullname ?>">
                          <i class="fa fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php
                }
              } else {
                echo '<tr><td colspan="8" class="px-4 py-4 text-center text-sm text-gray-500">No users found</td></tr>';
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- VIEW MODAL -->
  <div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
      <div class="flex justify-between items-center px-6 py-4 border-b">
        <h3 class="text-lg font-semibold">User Details</h3>
        <button onclick="closeModal('viewModal')" class="text-gray-500 hover:text-gray-700">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="p-6 space-y-4">
        <p><strong>Name:</strong> <span id="viewName"></span></p>
        <p><strong>Username:</strong> <span id="viewUsername"></span></p>
        <p><strong>Email:</strong> <span id="viewEmail"></span></p>
        <p><strong>Role:</strong> <span id="viewRole"></span></p>
        <p><strong>User Type:</strong> <span id="viewUserType"></span></p>
        <p><strong>Status:</strong> <span id="viewState"></span></p>
      </div>
    </div>
  </div>

  <!-- DELETE CONFIRMATION MODAL -->
  <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
      <div class="bg-red-600 text-white px-6 py-4 rounded-t-lg">
        <h3 class="text-lg font-semibold"><i class="fas fa-exclamation-triangle mr-2"></i> Confirm Delete</h3>
      </div>
      <div class="p-6">
        <p>Are you sure you want to delete this user?</p>
        <p class="font-semibold mt-2" id="deleteUserName"></p>
        <p class="text-sm text-gray-600" id="deleteUserType"></p>
      </div>
      <div class="flex justify-end px-6 py-4 border-t">
        <button onclick="closeModal('deleteModal')"
          class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors mr-2">Cancel</button>
        <a href="#" id="confirmDeleteBtn"
          class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">Delete</a>
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

    // Modal functions
    function openModal(modalId) {
      document.getElementById(modalId).classList.remove('hidden');
    }

    function closeModal(modalId) {
      document.getElementById(modalId).classList.add('hidden');
    }

    // Make table responsive on small screens
    function makeTableResponsive() {
      const table = document.getElementById('usersTable');
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

    // Filter + Search
    document.getElementById("userTypeFilter").addEventListener("change", filterTable);
    document.getElementById("searchInput").addEventListener("keyup", filterTable);

    function filterTable() {
      const filterUserType = document.getElementById("userTypeFilter").value.toLowerCase();
      const searchText = document.getElementById("searchInput").value.toLowerCase();
      const rows = document.querySelectorAll("#usersTable tbody tr");

      rows.forEach(row => {
        const userType = row.querySelector(".user-type").textContent.toLowerCase();
        const name = row.querySelector(".user-name").textContent.toLowerCase();
        const email = row.querySelector(".user-email").textContent.toLowerCase();
        const username = row.querySelector(".user-username").textContent.toLowerCase();

        const matchesUserType = (filterUserType === "all") ||
          (filterUserType === "user" && userType.includes("app user")) ||
          (filterUserType === "owner" && userType.includes("parking owner"));
        const matchesSearch = name.includes(searchText) || email.includes(searchText) || username.includes(searchText);

        row.style.display = (matchesUserType && matchesSearch) ? "" : "none";
      });
    }

    // View button
    document.querySelectorAll(".view-btn").forEach(btn => {
      btn.addEventListener("click", function () {
        document.getElementById("viewName").textContent = this.dataset.name;
        document.getElementById("viewUsername").textContent = this.dataset.username;
        document.getElementById("viewEmail").textContent = this.dataset.email;
        document.getElementById("viewRole").textContent = this.dataset.role;
        document.getElementById("viewUserType").textContent = this.dataset.userType === 'owner' ? 'Parking Owner' : 'App User';
        document.getElementById("viewState").textContent = this.dataset.state;
        openModal('viewModal');
      });
    });

    // Edit button - show appropriate modal based on user type
    document.querySelectorAll(".edit-btn").forEach(btn => {
      btn.addEventListener("click", function () {
        const id = this.dataset.id;
        const userType = this.dataset.userType;
        const name = this.closest("tr").querySelector(".user-name").textContent.trim();
        const username = this.closest("tr").querySelector(".user-username").textContent.trim();
        const email = this.closest("tr").querySelector(".user-email").textContent.trim();
        const role = this.closest("tr").querySelector(".user-role").textContent.trim().toLowerCase();

        if (userType === 'user') {
          // Populate user modal
          document.getElementById("editUserId").value = id;
          document.getElementById("editUserName").value = name;
          document.getElementById("editUserUsername").value = username;
          document.getElementById("editUserEmail").value = email;
          document.getElementById("editUserRole").value = role;
          openModal('editUserModal');
        } else {
          // Populate owner modal
          document.getElementById("editOwnerId").value = id;
          document.getElementById("editOwnerName").value = name;
          document.getElementById("editOwnerUsername").value = username;
          document.getElementById("editOwnerEmail").value = email;
          document.getElementById("editOwnerPassword").value = '';
          openModal('editOwnerModal');
        }
      });
    });
    // Delete button
    document.querySelectorAll(".delete-btn").forEach(btn => {
      btn.addEventListener("click", function () {
        const userName = this.dataset.name;
        const userId = this.dataset.id;
        const userType = this.dataset.userType;

        document.getElementById("deleteUserName").textContent = userName;
        document.getElementById("deleteUserType").textContent = `Type: ${userType === 'owner' ? 'Parking Owner' : 'App User'}`;

        // Set appropriate delete URL based on user type
        if (userType === 'user') {
          document.getElementById("confirmDeleteBtn").href = "delete_user.php?id=" + userId;
        } else {
          document.getElementById("confirmDeleteBtn").href = "delete_owner.php?id=" + userId;
        }

        openModal('deleteModal');
      });
    });

    // Auto-hide toast after 3 seconds
    document.addEventListener("DOMContentLoaded", function () {
      const toast = document.getElementById("toastMsg");
      if (toast) {
        setTimeout(() => {
          toast.classList.add('opacity-0');
          setTimeout(() => toast.remove(), 300);
        }, 3000);
      }
    });
  </script>
  <?php
  include 'add_user_modal.php';
  include 'edit_user_modal.php';
  include 'edit_owner_modal.php';
  ?>

</body>

</html>