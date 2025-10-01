<?php
$host = "localhost";
$dbname = "parkditto";
$username = "root";
$password = "";

// Define upload directory
$upload_dir = __DIR__ . '/uploads/parking_spaces/';

// Create upload directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch partners (staff users)
$partners = [];
try {
    $stmt = $pdo->prepare("SELECT id, firstname, lastname, email FROM parking_owners");
    $stmt->execute();
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching partners: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $partner_id = $_POST['partner_id'];
        $name = $_POST['name'];
        $address = $_POST['address'];
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];
        
        // Floors and per-floor capacities
        $floors = [];
        $floor_capacity = [];
        $available_per_floor = [];
        $total_car = 0;
        $total_motorcycle = 0;
        $total_mini_truck = 0;
        
        // Process each floor
        $floor_configs = [
            'ground' => 'Ground',
            'second' => '2nd Floor', 
            'third' => '3rd Floor'
        ];
        
        foreach ($floor_configs as $floor_key => $floor_name) {
            if (isset($_POST["{$floor_key}_floor"])) {
                $floors[] = $floor_name;
                
                $car_capacity = intval($_POST["{$floor_key}_car"] ?? 0);
                $motorcycle_capacity = intval($_POST["{$floor_key}_motorcycle"] ?? 0);
                $mini_truck_capacity = intval($_POST["{$floor_key}_mini_truck"] ?? 0);
                
                $floor_capacity[$floor_name] = [
                    "Car" => $car_capacity,
                    "Motorcycle" => $motorcycle_capacity,
                    "Mini Truck" => $mini_truck_capacity
                ];
                
                $available_per_floor[$floor_name] = [
                    "Car" => $car_capacity,
                    "Motorcycle" => $motorcycle_capacity,
                    "Mini Truck" => $mini_truck_capacity
                ];
                
                $total_car += $car_capacity;
                $total_motorcycle += $motorcycle_capacity;
                $total_mini_truck += $mini_truck_capacity;
            }
        }
        
        $total_spaces = $total_car + $total_motorcycle + $total_mini_truck;
        
        // Vehicle types (totals across all floors)
        $vehicle_types = json_encode([
            "Car" => ["total" => $total_car, "available" => $total_car],
            "Motorcycle" => ["total" => $total_motorcycle, "available" => $total_motorcycle],
            "Mini Truck" => ["total" => $total_mini_truck, "available" => $total_mini_truck]
        ]);
        
        $floors_json = json_encode($floors);
        $floor_capacity_json = json_encode($floor_capacity);
        $available_per_floor_json = json_encode($available_per_floor);
        
        // Occupied slots structure
        $occupied_slots = [
            "Car" => [],
            "Motorcycle" => [],
            "Mini Truck" => []
        ];
        
        foreach ($floors as $floor) {
            $occupied_slots["Car"][$floor] = [];
            $occupied_slots["Motorcycle"][$floor] = [];
            $occupied_slots["Mini Truck"][$floor] = [];
        }
        $occupied_slots_json = json_encode($occupied_slots);
        
        // Handle image upload
        $image_url = 'uploads/parking_spaces/default_parking.jpg'; // Default image
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_file = $_FILES['image'];
            $file_extension = pathinfo($image_file['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            // Validate file type
            if (in_array(strtolower($file_extension), $allowed_extensions)) {
                // Generate unique filename
                $filename = 'parking_' . time() . '_' . uniqid() . '.' . $file_extension;
                $target_path = $upload_dir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($image_file['tmp_name'], $target_path)) {
                    $image_url = 'uploads/parking_spaces/' . $filename;
                } else {
                    throw new Exception("Failed to upload image file.");
                }
            } else {
                throw new Exception("Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP are allowed.");
            }
        }
        
        // Insert into database
        $sql = "INSERT INTO parking_spaces 
                (partner_id, name, address, latitude, longitude, total_spaces, available_spaces, 
                 vehicle_types, floors, floor_capacity, available_per_floor, occupied_slots, image_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $partner_id, $name, $address, $latitude, $longitude, $total_spaces, $total_spaces,
            $vehicle_types, $floors_json, $floor_capacity_json, $available_per_floor_json, 
            $occupied_slots_json, $image_url
        ]);
        
        $success_message = "Parking space added successfully!";
        echo '<script>setTimeout(() => { closeAddParkingModal(); }, 2000);</script>';
        
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!-- Add Parking Space Modal -->
<div id="addParkingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-5 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white max-h-[95vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="flex justify-between items-center pb-3 border-b sticky top-0 bg-white z-10">
            <h3 class="text-xl font-bold text-gray-800">Add New Parking Space</h3>
            <button onclick="closeAddParkingModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="mt-4 p-3 bg-green-100 text-green-700 rounded-md">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="mt-4 p-3 bg-red-100 text-red-700 rounded-md">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- Modal Body -->
        <form id="addParkingForm" method="POST" enctype="multipart/form-data" class="mt-4 space-y-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Partner *</label>
                        <select name="partner_id" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="">Select a Partner</option>
                            <?php foreach ($partners as $partner): ?>
                                <option value="<?= $partner['id'] ?>">
                                    <?= htmlspecialchars($partner['firstname'] . ' ' . $partner['lastname'] . ' (' . $partner['email'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parking Name *</label>
                        <input type="text" name="name" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                               placeholder="e.g., Calauan Public Market Parking">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
                        <textarea name="address" required rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                                  placeholder="Full address of the parking space"></textarea>
                    </div>

                    <!-- Coordinates Section -->
                    <div class="border-t pt-4">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">Location Coordinates</h4>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Latitude *</label>
                                <input type="number" step="any" name="latitude" id="latitude" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                                       placeholder="14.14870000">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Longitude *</label>
                                <input type="number" step="any" name="longitude" id="longitude" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                                       placeholder="121.31580000">
                            </div>
                        </div>
                        <button type="button" onclick="getCurrentLocation()" 
                                class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-sm">
                            <i class="fas fa-location-arrow mr-2"></i>Use Current Location
                        </button>
                    </div>

                    <!-- Image Upload -->
                    <div class="border-t pt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parking Image</label>
                        <div class="flex items-center justify-center w-full">
                            <label for="image" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                    <p class="mb-2 text-sm text-gray-500">
                                        <span class="font-semibold">Click to upload</span> or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-500">PNG, JPG, GIF, WEBP (MAX. 5MB)</p>
                                </div>
                                <input id="image" name="image" type="file" class="hidden" accept="image/*" />
                            </label>
                        </div>
                        <div id="image-preview" class="mt-2 hidden">
                            <img id="preview-image" class="h-32 mx-auto rounded-md shadow-md">
                            <button type="button" onclick="removeImage()" class="mt-2 text-red-600 text-sm hover:text-red-800">
                                <i class="fas fa-times mr-1"></i>Remove Image
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Map Section -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Location on Map</label>
                        <div id="map" class="w-full h-64 rounded-md border border-gray-300"></div>
                        <p class="text-xs text-gray-500 mt-1">Click on the map to set the location coordinates</p>
                    </div>

                    <!-- Per-Floor Capacity Section -->
                    <div class="border-t pt-4">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">Per-Floor Vehicle Capacity</h4>
                        
                        <!-- Ground Floor -->
                        <div class="floor-section mb-4 p-3 border rounded-md">
                            <div class="flex items-center mb-2">
                                <input type="checkbox" name="ground_floor" id="ground_floor" checked 
                                       class="rounded border-gray-300 text-red-600 focus:ring-red-500 mr-2">
                                <label for="ground_floor" class="font-medium text-gray-700">Ground Floor</label>
                            </div>
                            <div class="grid grid-cols-3 gap-2 ml-4">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Car</label>
                                    <input type="number" name="ground_car" value="0" min="0" 
                                           class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm floor-capacity">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Motorcycle</label>
                                    <input type="number" name="ground_motorcycle" value="0" min="0"
                                           class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm floor-capacity">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Mini Truck</label>
                                    <input type="number" name="ground_mini_truck" value="0" min="0"
                                           class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm floor-capacity">
                                </div>
                            </div>
                        </div>

                        <!-- 2nd Floor -->
                        <div class="floor-section mb-4 p-3 border rounded-md">
                            <div class="flex items-center mb-2">
                                <input type="checkbox" name="second_floor" id="second_floor"
                                       class="rounded border-gray-300 text-red-600 focus:ring-red-500 mr-2">
                                <label for="second_floor" class="font-medium text-gray-700">2nd Floor</label>
                            </div>
                            <div class="grid grid-cols-3 gap-2 ml-4">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Car</label>
                                    <input type="number" name="second_car" value="0" min="0" 
                                           class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm floor-capacity" disabled>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Motorcycle</label>
                                    <input type="number" name="second_motorcycle" value="0" min="0"
                                           class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm floor-capacity" disabled>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Mini Truck</label>
                                    <input type="number" name="second_mini_truck" value="0" min="0"
                                           class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm floor-capacity" disabled>
                                </div>
                            </div>
                        </div>

                        <!-- 3rd Floor -->
                        <div class="floor-section mb-4 p-3 border rounded-md">
                            <div class="flex items-center mb-2">
                                <input type="checkbox" name="third_floor" id="third_floor"
                                       class="rounded border-gray-300 text-red-600 focus:ring-red-500 mr-2">
                                <label for="third_floor" class="font-medium text-gray-700">3rd Floor</label>
                            </div>
                            <div class="grid grid-cols-3 gap-2 ml-4">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Car</label>
                                    <input type="number" name="third_car" value="0" min="0" 
                                           class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm floor-capacity" disabled>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Motorcycle</label>
                                    <input type="number" name="third_motorcycle" value="0" min="0"
                                           class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm floor-capacity" disabled>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Mini Truck</label>
                                    <input type="number" name="third_mini_truck" value="0" min="0"
                                           class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm floor-capacity" disabled>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Spaces Summary -->
                    <div class="border-t pt-4">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">Total Spaces Summary</h4>
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div class="p-2 bg-gray-100 rounded-md">
                                <div class="font-semibold text-gray-800" id="total_car">0</div>
                                <div class="text-xs text-gray-600">Car Spaces</div>
                            </div>
                            <div class="p-2 bg-gray-100 rounded-md">
                                <div class="font-semibold text-gray-800" id="total_motorcycle">0</div>
                                <div class="text-xs text-gray-600">Motorcycle Spaces</div>
                            </div>
                            <div class="p-2 bg-gray-100 rounded-md">
                                <div class="font-semibold text-gray-800" id="total_mini_truck">0</div>
                                <div class="text-xs text-gray-600">Mini Truck Spaces</div>
                            </div>
                        </div>
                        <div class="mt-3 p-2 bg-red-50 rounded-md text-center">
                            <div class="font-bold text-lg text-red-800" id="total_spaces_display">0</div>
                            <div class="text-xs text-red-600">Total Parking Spaces</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end space-x-3 pt-6 border-t sticky bottom-0 bg-white z-10">
                <button type="button" onclick="closeAddParkingModal()"
                        class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add Parking Space
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<!-- Leaflet JavaScript -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// Map variables
let map;
let marker;

// Function to show the modal
function showAddParkingModal() {
    document.getElementById('addParkingModal').classList.remove('hidden');
    setTimeout(initializeMap, 100);
}

// Function to close the modal
function closeAddParkingModal() {
    document.getElementById('addParkingModal').classList.add('hidden');
    if (map) {
        map.remove();
        map = null;
        marker = null;
    }
}

// Initialize map
function initializeMap() {
    const defaultLat = 14.14870000;
    const defaultLng = 121.31580000;
    
    map = L.map('map').setView([defaultLat, defaultLng], 15);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    marker = L.marker([defaultLat, defaultLng], {
        draggable: true
    }).addTo(map);
    
    document.getElementById('latitude').value = defaultLat;
    document.getElementById('longitude').value = defaultLng;
    
    marker.on('dragend', function(e) {
        const position = marker.getLatLng();
        document.getElementById('latitude').value = position.lat.toFixed(8);
        document.getElementById('longitude').value = position.lng.toFixed(8);
    });
    
    map.on('click', function(e) {
        const latlng = e.latlng;
        marker.setLatLng(latlng);
        document.getElementById('latitude').value = latlng.lat.toFixed(8);
        document.getElementById('longitude').value = latlng.lng.toFixed(8);
    });
}

// Image preview functionality
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-image').src = e.target.result;
            document.getElementById('image-preview').classList.remove('hidden');
        }
        reader.readAsDataURL(file);
    }
});

function removeImage() {
    document.getElementById('image').value = '';
    document.getElementById('image-preview').classList.add('hidden');
}

// Get current location
function getCurrentLocation() {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser');
        return;
    }
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            if (map && marker) {
                map.setView([lat, lng], 15);
                marker.setLatLng([lat, lng]);
            }
            
            document.getElementById('latitude').value = lat.toFixed(8);
            document.getElementById('longitude').value = lng.toFixed(8);
        },
        function(error) {
            alert('Unable to get your location: ' + error.message);
        }
    );
}

// Calculate total spaces
function calculateTotalSpaces() {
    let totalCar = 0, totalMotorcycle = 0, totalMiniTruck = 0;
    
    const floors = ['ground', 'second', 'third'];
    floors.forEach(floor => {
        const checkbox = document.querySelector(`input[name="${floor}_floor"]`);
        if (checkbox && checkbox.checked) {
            totalCar += parseInt(document.querySelector(`input[name="${floor}_car"]`).value) || 0;
            totalMotorcycle += parseInt(document.querySelector(`input[name="${floor}_motorcycle"]`).value) || 0;
            totalMiniTruck += parseInt(document.querySelector(`input[name="${floor}_mini_truck"]`).value) || 0;
        }
    });
    
    const totalSpaces = totalCar + totalMotorcycle + totalMiniTruck;
    
    document.getElementById('total_car').textContent = totalCar;
    document.getElementById('total_motorcycle').textContent = totalMotorcycle;
    document.getElementById('total_mini_truck').textContent = totalMiniTruck;
    document.getElementById('total_spaces_display').textContent = totalSpaces;
}

// Toggle floor capacity inputs
function toggleFloorInputs(floor) {
    const inputs = document.querySelectorAll(`input[name="${floor}_car"], input[name="${floor}_motorcycle"], input[name="${floor}_mini_truck"]`);
    const checkbox = document.querySelector(`input[name="${floor}_floor"]`);
    
    inputs.forEach(input => {
        input.disabled = !checkbox.checked;
        if (!checkbox.checked) {
            input.value = '0';
        }
    });
    
    calculateTotalSpaces();
}

// Update marker from inputs
function updateMarkerFromInputs() {
    if (!map || !marker) return;
    
    const lat = parseFloat(document.getElementById('latitude').value);
    const lng = parseFloat(document.getElementById('longitude').value);
    
    if (!isNaN(lat) && !isNaN(lng)) {
        marker.setLatLng([lat, lng]);
        map.setView([lat, lng]);
    }
}

// Add event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Floor checkbox event listeners
    document.querySelectorAll('input[name$="_floor"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const floor = this.name.replace('_floor', '');
            toggleFloorInputs(floor);
        });
    });
    
    // Capacity input event listeners
    document.querySelectorAll('.floor-capacity').forEach(input => {
        input.addEventListener('input', calculateTotalSpaces);
    });
    
    // Coordinate input event listeners
    document.getElementById('latitude').addEventListener('change', updateMarkerFromInputs);
    document.getElementById('longitude').addEventListener('change', updateMarkerFromInputs);
    
    // Initialize
    calculateTotalSpaces();
    toggleFloorInputs('ground');
    toggleFloorInputs('second');
    toggleFloorInputs('third');
});

// Close modal when clicking outside
document.getElementById('addParkingModal').addEventListener('click', function(e) {
    if (e.target.id === 'addParkingModal') {
        closeAddParkingModal();
    }
});
</script>