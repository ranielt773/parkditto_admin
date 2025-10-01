<?php
include 'includes/auth.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require __DIR__ . '/includes/config.php';

$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$filter = isset($_GET['trend']) ? $_GET['trend'] : 'Monthly';
try {
  $bookingCount = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'ongoing'")->fetchColumn();
} catch (PDOException $e) {
  $bookingCount = 0;
}

$ownerCount = $pdo->query("SELECT COUNT(*) FROM parking_spaces")->fetchColumn();

if ($filter === 'Weekly') {
  $startDate = date('Y-m-d', strtotime('sunday last week'));
  $endDate = date('Y-m-d', strtotime('saturday this week'));
} else { // Monthly
  $startDate = date('Y-m-01'); // unang araw ng buwan
  $endDate = date('Y-m-t');    // huling araw ng buwan
}
if ($filter === 'Weekly') {
  $startDatee = date('Y-m-d 00:00:00', strtotime('sunday last week'));
  $endDatee = date('Y-m-d 23:59:59', strtotime('saturday this week'));

} elseif ($filter === 'Monthly') {
  $startDatee = date('Y-m-01 00:00:00'); // first day of month
  $endDatee = date('Y-m-t 23:59:59');  // last day of month

} elseif ($filter === 'Yearly') {
  $startDatee = date('Y-01-01 00:00:00'); // Jan 1 this year
  $endDatee = date('Y-12-31 23:59:59'); // Dec 31 this year
}

function getVehicleCount($pdo, $type, $startDate, $endDate)
{
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions 
                         WHERE vehicle_type = ? 
                         AND status = 'ongoing'
                         AND DATE(created_at) BETWEEN ? AND ?");
  $stmt->execute([$type, $startDate, $endDate]);
  return $stmt->fetchColumn();
}

$carCount = getVehicleCount($pdo, 'car', $startDate, $endDate);
$truckCount = getVehicleCount($pdo, 'mini truck', $startDate, $endDate);
$motorCount = getVehicleCount($pdo, 'motorcycle', $startDate, $endDate);


// Booking trend per day of current week
$bookingTrend = [];
$labels = [];

if ($filter === 'Weekly') {
  $labels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  $bookingTrend = array_fill(0, 7, 0);

  $stmt = $pdo->prepare("
        SELECT DAYOFWEEK(created_at) as day, COUNT(*) as total
        FROM transactions
        WHERE status = 'ongoing'
          AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DAYOFWEEK(created_at)
    ");
  $stmt->execute([$startDate, $endDate]);
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($results as $row) {
    $index = $row['day'] - 1; // 1=Sunday → index 0
    $bookingTrend[$index] = (int) $row['total'];
  }

} elseif ($filter === 'Monthly') {
  $daysInMonth = date('t');
  $labels = range(1, $daysInMonth);
  $bookingTrend = array_fill(0, $daysInMonth, 0);

  $stmt = $pdo->prepare("
        SELECT DAY(created_at) as d, COUNT(*) as total
        FROM transactions
        WHERE status = 'ongoing'
          AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DAY(created_at)
    ");
  $stmt->execute([$startDate, $endDate]);
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($results as $row) {
    $index = $row['d'] - 1;
    $bookingTrend[$index] = (int) $row['total'];
  }

} elseif ($filter === 'Yearly') {
  $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  $bookingTrend = array_fill(0, 12, 0);

  $stmt = $pdo->prepare("
        SELECT MONTH(created_at) as m, COUNT(*) as total
        FROM transactions
        WHERE status = 'ongoing'
          AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY MONTH(created_at)
    ");
  $stmt->execute([$startDate, $endDate]);
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($results as $row) {
    $index = $row['m'] - 1;
    $bookingTrend[$index] = (int) $row['total'];
  }
}

// Hourly Peak (0-23)
// Hourly Peak (0-23)
$hourlyPeak = array_fill(0, 24, 0);

$stmt = $pdo->prepare("
    SELECT HOUR(arrival_time) as hr, COUNT(*) as total
    FROM transactions
    WHERE status = 'ongoing'
      AND arrival_time BETWEEN ? AND ?
    GROUP BY HOUR(arrival_time)
    ORDER BY hr ASC
");
$stmt->execute([$startDatee, $endDatee]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
  $hourlyPeak[(int) $row['hr']] = (int) $row['total'];
}



$occupancy = [
  "Location A" => 25,
  "Location B" => 15,
  "Location C" => 30,
  "Location D" => 20,
  "Location E" => 10
];

$topUsers = [
  "Juan Dela Cruz",
  "Juan Ponce Enrile",
  "Kitty Duterte",
  "Ivan Rubiales",
  "Baby"
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ParkDitto - Dashboard</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="css/navbar.css">
</head>

<body class="min-h-screen bg-gradient-to-b from-[#fcd462] to-[#FFFFFF] font-[Poppins] text-[#3B060A]">
  <!-- Mobile overlay for closing sidebar -->
  <div class="mobile-overlay" id="mobileOverlay"></div>

  <?php include "includes/navbar.php";?>
  
  <!-- Main content -->
  <div class="ml-64 flex-1 overflow-auto transition-all duration-300" id="mainContent">
    <div class="p-4 md:p-6"> 
      <!-- Dashboard header -->
      <div class="mb-6 flex justify-between items-center">
        <button class="md:hidden bg-red-800 text-white p-2 rounded-md shadow-lg" id="sidebarToggleMobile">
          <i class="fas fa-bars"></i>
        </button>
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
          <p class="text-gray-600">Welcome, <?php echo $_SESSION['username']; ?>!</p>
        </div>

      </div>

      <!-- Top Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-6">
        <div class="bg-white rounded-xl p-4 md:p-6 shadow-lg text-center">
          <h5 class="text-gray-700 mb-2">Users</h5>
          <h2 class="text-3xl font-bold text-gray-800"><?php echo $userCount; ?></h2>
        </div>
        <div class="bg-white rounded-xl p-4 md:p-6 shadow-lg text-center">
          <h5 class="text-gray-700 mb-2">Bookings</h5>
          <h2 class="text-3xl font-bold text-gray-800"><?php echo $bookingCount; ?></h2>
        </div>
        <div class="bg-white rounded-xl p-4 md:p-6 shadow-lg text-center">
          <h5 class="text-gray-700 mb-2">Parking Lot Owners</h5>
          <h2 class="text-3xl font-bold text-gray-800"><?php echo $ownerCount; ?></h2>
        </div>
      </div>

      <!-- Vehicle Trend -->
      <div class="bg-white rounded-xl p-4 md:p-6 shadow-lg mb-6">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-4">
          <h5 class="text-lg font-semibold text-gray-800">Vehicle Trend</h5>
          <select class="px-3 py-1 bg-amber-100 border-none rounded-md text-gray-700 mt-2 md:mt-0"
            onchange="location = '?trend=' + this.value">
            <option value="Yearly" <?php if ($filter === 'Yearly')
              echo 'selected'; ?>>Yearly</option>
            <option value="Monthly" <?php if ($filter === 'Monthly')
              echo 'selected'; ?>>Monthly</option>
            <option value="Weekly" <?php if ($filter === 'Weekly')
              echo 'selected'; ?>>Weekly</option>
          </select>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
          <!-- Car -->
          <div class="relative text-center">
            <canvas id="carChart" class="mx-auto" width="180" height="180"></canvas>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-4xl">🚗</div>
            <div class="mt-2 text-xl font-bold text-gray-800"><?php echo $carCount; ?></div>
            <div class="text-sm text-gray-600">Cars</div>
          </div>
          <!-- Truck -->
          <div class="relative text-center">
            <canvas id="truckChart" class="mx-auto" width="180" height="180"></canvas>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-4xl">🚚</div>
            <div class="mt-2 text-xl font-bold text-gray-800"><?php echo $truckCount; ?></div>
            <div class="text-sm text-gray-600">Trucks</div>
          </div>
          <!-- Motorcycle -->
          <div class="relative text-center">
            <canvas id="motorChart" class="mx-auto" width="180" height="180"></canvas>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-4xl">🏍️</div>
            <div class="mt-2 text-xl font-bold text-gray-800"><?php echo $motorCount; ?></div>
            <div class="text-sm text-gray-600">Motorcycles</div>
          </div>
        </div>
      </div>

      <!-- Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-6">
        <!-- Booking Trend -->
        <div class="bg-white rounded-xl p-4 md:p-6 shadow-lg">
          <div class="flex justify-between items-center mb-4">
            <h5 class="text-lg font-semibold text-gray-800">Booking Trend</h5>
            <select class="px-3 py-1 bg-amber-100 border-none rounded-md text-gray-700"
              onchange="location = '?trend=' + this.value">
              <option value="Yearly" <?php if ($filter === 'Yearly')
                echo 'selected'; ?>>Yearly</option>
              <option value="Monthly" <?php if ($filter === 'Monthly')
                echo 'selected'; ?>>Monthly</option>
              <option value="Weekly" <?php if ($filter === 'Weekly')
                echo 'selected'; ?>>Weekly</option>
            </select>
          </div>

          <canvas id="bookingChart" height="250"></canvas>
        </div>
        <!-- Peak Hours -->
        <div class="bg-white rounded-xl p-4 md:p-6 shadow-lg">
          <h5 class="text-lg font-semibold text-gray-800 mb-4">Peak Hours</h5>
          <canvas id="peakChart" height="250"></canvas>
        </div>
      </div>

      <!-- Bottom Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
        <!-- Occupancy Rate -->
        <div class="bg-white rounded-xl p-4 md:p-6 shadow-lg">
          <h5 class="text-lg font-semibold text-gray-800 mb-4">Occupancy Rate by Location</h5>
          <div class="w-48 h-48 md:w-64 md:h-64 mx-auto">
            <canvas id="occupancyChart"></canvas>
          </div>
        </div>
        <!-- Top Users -->
        <div class="bg-white rounded-xl p-4 md:p-6 shadow-lg">
          <h5 class="text-lg font-semibold text-gray-800 mb-4">Top Users (Most Bookings)</h5>
          <ul class="list-disc list-inside space-y-2 pl-4">
            <?php foreach ($topUsers as $i => $user): ?>
              <li class="text-gray-700"><?php echo ($i + 1) . ". " . $user; ?></li>
            <?php endforeach; ?>
          </ul>
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

    // Function para gumawa ng full donut chart
    function createDonut(ctxId, value, color) {
      return new Chart(document.getElementById(ctxId), {
        type: 'doughnut',
        data: {
          datasets: [{
            data: [value, 500 - value],
            backgroundColor: [color, '#f8f8f8'],
            borderWidth: 0
          }]
        },
        options: {
          cutout: "80%",
          plugins: {
            legend: { display: false },
            tooltip: { enabled: false }
          }
        }
      });
    }

    // Create vehicle charts
    createDonut("carChart", <?php echo $carCount; ?>, "#b30000");
    createDonut("truckChart", <?php echo $truckCount; ?>, "#d4ac0d");
    createDonut("motorChart", <?php echo $motorCount; ?>, "#0b5345");

    // Booking Trend
    new Chart(document.getElementById('bookingChart'), {
      type: 'bar',
      data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
          data: <?php echo json_encode($bookingTrend); ?>,
          backgroundColor: '#2980b9'
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
    });



    // Occupancy Rate
    new Chart(document.getElementById('occupancyChart'), {
      type: 'doughnut',
      data: {
        labels: <?php echo json_encode(array_keys($occupancy)); ?>,
        datasets: [{
          data: <?php echo json_encode(array_values($occupancy)); ?>,
          backgroundColor: ['#e74c3c', '#f39c12', '#8e44ad', '#27ae60', '#3498db']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              boxWidth: 12,
              font: {
                size: 11
              }
            }
          }
        }
      }
    });
    const hourLabels = [
      "12 AM", "1 AM", "2 AM", "3 AM", "4 AM", "5 AM", "6 AM", "7 AM", "8 AM", "9 AM", "10 AM", "11 AM",
      "12 PM", "1 PM", "2 PM", "3 PM", "4 PM", "5 PM", "6 PM", "7 PM", "8 PM", "9 PM", "10 PM", "11 PM"
    ];

    new Chart(document.getElementById('peakChart'), {
      type: 'bar',
      data: {
        labels: hourLabels,
        datasets: [{
          data: <?php echo json_encode($hourlyPeak); ?>,
          backgroundColor: '#145a32'
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          x: { title: { display: true, text: 'Hour of Day' } },
          y: { beginAtZero: true, title: { display: true, text: 'Bookings' } }
        }
      }
    });


  </script>
</body>

</html>