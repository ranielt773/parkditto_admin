<?php
// Get the current page filename
$current_page = basename($_SERVER['PHP_SELF']);

// Function to check if menu item is active
function isActive($page_name, $current_page) {
    return $page_name === $current_page ? 'bg-red-900' : '';
}
?>

<!-- Sidebar -->
<div
    class="fixed inset-y-0 left-0 w-64 bg-red-800 text-white transform transition-all duration-300 ease-in-out z-20 md:z-10"
    id="sidebar">
    <div class="flex items-center justify-center p-4 border-b border-red-700 sidebar-logo">
      <div class="flex flex-col items-center">
        <img src="assets/logo.png" alt="ParkDitto Logo" class="h-12 w-auto mb-2">
        <h3 class="text-lg font-semibold logo-text">ParkDitto</h3>
      </div>
    </div>
    <nav class="mt-4">
      <a href="dashboard.php" class="flex items-center px-4 py-3 text-red-100 hover:bg-red-700 <?php echo isActive('dashboard.php', $current_page); ?>">
        <i class="fas fa-tachometer-alt w-6 text-center"></i>
        <span class="ml-3 menu-text">Dashboard</span>
      </a>
      <a href="user.php" class="flex items-center px-4 py-3 text-red-100 hover:bg-red-700 <?php echo isActive('user.php', $current_page); ?>">
        <i class="fas fa-users w-6 text-center"></i>
        <span class="ml-3 menu-text">Users</span>
      </a>
      <a href="parkingManagement.php" class="flex items-center px-4 py-3 text-red-100 hover:bg-red-700 <?php echo isActive('parkingManagement.php', $current_page); ?>">
        <i class="fas fa-parking w-6 text-center"></i>
        <span class="ml-3 menu-text">Parking Management</span>
      </a>
      <a href="booking.php" class="flex items-center px-4 py-3 text-red-100 hover:bg-red-700 <?php echo isActive('booking.php', $current_page); ?>">
        <i class="fas fa-calendar-check w-6 text-center"></i>
        <span class="ml-3 menu-text">Bookings</span>
      </a>
      <a href="payments.php" class="flex items-center px-4 py-3 text-red-100 hover:bg-red-700 <?php echo isActive('payments.php', $current_page); ?>">
        <i class="fas fa-credit-card w-6 text-center"></i>
        <span class="ml-3 menu-text">Plans & Payments</span>
      </a>
      <a href="reports.php" class="flex items-center px-4 py-3 text-red-100 hover:bg-red-700 <?php echo isActive('reports.php', $current_page); ?>">
        <i class="fas fa-chart-bar w-6 text-center"></i>
        <span class="ml-3 menu-text">Reports</span>
      </a>
      <a href="settings.php" class="flex items-center px-4 py-3 text-red-100 hover:bg-red-700 <?php echo isActive('settings.php', $current_page); ?>">
        <i class="fas fa-cog w-6 text-center"></i>
        <span class="ml-3 menu-text">Settings</span>
      </a>
    </nav>
  </div>