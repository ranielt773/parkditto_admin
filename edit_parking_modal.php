<?php
// edit_parking_modal.php
include 'includes/config.php';

// Define upload directory
$upload_dir = __DIR__ . '/uploads/parking_spaces/';

// Create upload directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Fetch parking space details
$parking_id = $_GET['parking_id'] ?? null;
$parking_details = null;

if ($parking_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT ps.*, po.firstname, po.lastname, po.email 
            FROM parking_spaces ps 
            LEFT JOIN parking_owners po ON ps.partner_id = po.id 
            WHERE ps.id = ?
        ");
        $stmt->execute([$parking_id]);
        $parking_details = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Error fetching parking details: " . $e->getMessage();
    }
}
?>

<!-- Edit Parking Space Modal -->
<div id="editParkingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-5 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white max-h-[95vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="flex justify-between items-center pb-3 border-b sticky top-0 bg-white z-10">
            <h3 class="text-xl font-bold text-gray-800">Edit Parking Space</h3>
            <button type="button" onclick="closeEditParkingModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <?php if ($parking_details): ?>
        <form id="editParkingForm" method="POST" action="update_parking.php" enctype="multipart/form-data" class="mt-4 space-y-6">
            <input type="hidden" name="parking_id" value="<?= $parking_details['id'] ?>">
            
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <!-- Parking Owner (Readonly) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parking Owner</label>
                        <input type="text" 
                               value="<?= htmlspecialchars($parking_details['firstname'] . ' ' . $parking_details['lastname'] . ' (' . $parking_details['email'] . ')') ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600" 
                               readonly>
                    </div>
                    
                    <!-- Parking Name (Readonly) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parking Name</label>
                        <input type="text" 
                               value="<?= htmlspecialchars($parking_details['name']) ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600" 
                               readonly>
                    </div>

                    <!-- Address -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
                        <textarea name="address" required rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                                  placeholder="Full address of the parking space"><?= htmlspecialchars($parking_details['address']) ?></textarea>
                    </div>

                    <!-- Coordinates Section -->
                    <div class="border-t pt-4">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">Location Coordinates</h4>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Latitude *</label>
                                <input type="number" step="any" name="latitude" id="edit_latitude" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                                       value="<?= htmlspecialchars($parking_details['latitude']) ?>">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Longitude *</label>
                                <input type="number" step="any" name="longitude" id="edit_longitude" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                                       value="<?= htmlspecialchars($parking_details['longitude']) ?>">
                            </div>
                        </div>
                        <button type="button" onclick="getCurrentLocationEdit()" 
                                class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-sm">
                            <i class="fas fa-location-arrow mr-2"></i>Use Current Location
                        </button>
                    </div>

                    <!-- Image Upload -->
                    <div class="border-t pt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parking Image</label>
                        
                        <!-- Current Image -->
                        <?php if ($parking_details['image_url']): ?>
                        <div class="mb-3">
                            <p class="text-sm text-gray-600 mb-2">Current Image:</p>
                            <img src="<?= htmlspecialchars($parking_details['image_url']) ?>" 
                                 class="h-32 rounded-md shadow-md border">
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center justify-center w-full">
                            <label for="edit_image" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                    <p class="mb-2 text-sm text-gray-500">
                                        <span class="font-semibold">Click to upload new image</span> or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-500">PNG, JPG, GIF, WEBP (MAX. 5MB)</p>
                                </div>
                                <input id="edit_image" name="image" type="file" class="hidden" accept="image/*" />
                            </label>
                        </div>
                        <div id="edit_image-preview" class="mt-2 hidden">
                            <img id="edit_preview-image" class="h-32 mx-auto rounded-md shadow-md">
                            <button type="button" onclick="removeEditImage()" class="mt-2 text-red-600 text-sm hover:text-red-800">
                                <i class="fas fa-times mr-1"></i>Remove New Image
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Map Section -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Location on Map</label>
                        <div id="edit_map" class="w-full h-64 rounded-md border border-gray-300"></div>
                        <p class="text-xs text-gray-500 mt-1">Click on the map to update the location coordinates</p>
                    </div>

                    <!-- Additional Capacity Section -->
                    <div class="border-t pt-4">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">Add Additional Capacity</h4>
                        <p class="text-sm text-gray-600 mb-3">Add more parking slots to existing floors:</p>
                        
                        <?php
                        $existing_floors = json_decode($parking_details['floors'], true);
                        $floor_configs = [
                            'ground' => 'Ground',
                            'second' => '2nd Floor', 
                            'third' => '3rd Floor'
                        ];
                        
                        foreach ($floor_configs as $floor_key => $floor_name): 
                            if (in_array($floor_name, $existing_floors)): 
                        ?>
                        <div class="floor-section mb-4 p-3 border rounded-md">
                            <div class="flex items-center mb-2">
                                <input type="checkbox" name="<?= $floor_key ?>_floor" id="edit_<?= $floor_key ?>_floor" 
                                       class="rounded border-gray-300 text-red-600 focus:ring-red-500 mr-2">
                                <label for="edit_<?= $floor_key ?>_floor" class="font-medium text-gray-700"><?= $floor_name ?></label>
                            </div>
                            <div class="grid grid-cols-3 gap-2 ml-4">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Additional Car Slots</label>
                                    <input type="number" name="<?= $floor_key ?>_car" value="0" min="0" 
                                           class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm edit-floor-capacity" disabled>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Additional Motorcycle Slots</label>
                                    <input type="number" name="<?= $floor_key ?>_motorcycle" value="0" min="0"
                                           class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm edit-floor-capacity" disabled>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Additional Mini Truck Slots</label>
                                    <input type="number" name="<?= $floor_key ?>_mini_truck" value="0" min="0"
                                           class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm edit-floor-capacity" disabled>
                                </div>
                            </div>
                        </div>
                        <?php endif; endforeach; ?>
                    </div>

                    <!-- Additional Spaces Summary -->
                    <div class="border-t pt-4">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">Additional Spaces Summary</h4>
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div class="p-2 bg-gray-100 rounded-md">
                                <div class="font-semibold text-gray-800" id="edit_total_car">0</div>
                                <div class="text-xs text-gray-600">Additional Car</div>
                            </div>
                            <div class="p-2 bg-gray-100 rounded-md">
                                <div class="font-semibold text-gray-800" id="edit_total_motorcycle">0</div>
                                <div class="text-xs text-gray-600">Additional Motorcycle</div>
                            </div>
                            <div class="p-2 bg-gray-100 rounded-md">
                                <div class="font-semibold text-gray-800" id="edit_total_mini_truck">0</div>
                                <div class="text-xs text-gray-600">Additional Mini Truck</div>
                            </div>
                        </div>
                        <div class="mt-3 p-2 bg-green-50 rounded-md text-center">
                            <div class="font-bold text-lg text-green-800" id="edit_total_additional_spaces">0</div>
                            <div class="text-xs text-green-600">Total Additional Spaces</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end space-x-3 pt-6 border-t sticky bottom-0 bg-white z-10">
                <button type="button" onclick="closeEditParkingModal()"
                        class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                    Cancel
                </button>
                <button type="submit" id="editSubmitBtn"
                        class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Update Parking Space
                </button>
            </div>
        </form>
        <?php else: ?>
            <div class="mt-4 p-4 bg-red-100 text-red-700 rounded-md">
                Parking space not found.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<!-- Leaflet JavaScript -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// Map variables for edit modal
let edit_map;
let edit_marker;
let isEditFormSubmitting = false;

// Function to show the modal
function showEditParkingModal(parkingId) {
    // Close any existing modal first
    closeEditParkingModal();
    
    // Load modal content via AJAX
    fetch(`edit_parking_modal.php?parking_id=${parkingId}`)
        .then(response => response.text())
        .then(html => {
            // Remove any existing modal container
            const existingContainer = document.getElementById('editParkingModalContainer');
            if (existingContainer) {
                existingContainer.remove();
            }
            
            // Create new modal container
            const modalContainer = document.createElement('div');
            modalContainer.id = 'editParkingModalContainer';
            document.body.appendChild(modalContainer);
            modalContainer.innerHTML = html;
            
            // Show modal
            document.getElementById('editParkingModal').classList.remove('hidden');
            setTimeout(initializeEditMap, 100);
            
            // Initialize events
            setTimeout(initEditModalEvents, 200);
        })
        .catch(error => {
            console.error('Error loading edit modal:', error);
            alert('Error loading parking details');
        });
}

// Function to close the modal
function closeEditParkingModal() {
    const editModal = document.getElementById('editParkingModal');
    if (editModal) {
        editModal.classList.add('hidden');
    }
    
    // Remove modal container completely
    const modalContainer = document.getElementById('editParkingModalContainer');
    if (modalContainer) {
        modalContainer.remove();
    }
    
    if (edit_map) {
        edit_map.remove();
        edit_map = null;
        edit_marker = null;
    }
    
    isEditFormSubmitting = false;
}

// Initialize map for edit modal
function initializeEditMap() {
    const editLatitude = document.getElementById('edit_latitude');
    const editLongitude = document.getElementById('edit_longitude');
    
    if (!editLatitude || !editLongitude) return;
    
    const latitude = parseFloat(editLatitude.value) || 14.14870000;
    const longitude = parseFloat(editLongitude.value) || 121.31580000;
    
    // Remove existing map if any
    if (edit_map) {
        edit_map.remove();
    }
    
    edit_map = L.map('edit_map').setView([latitude, longitude], 15);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(edit_map);
    
    edit_marker = L.marker([latitude, longitude], {
        draggable: true
    }).addTo(edit_map);
    
    edit_marker.on('dragend', function(e) {
        const position = edit_marker.getLatLng();
        document.getElementById('edit_latitude').value = position.lat.toFixed(8);
        document.getElementById('edit_longitude').value = position.lng.toFixed(8);
    });
    
    edit_map.on('click', function(e) {
        const latlng = e.latlng;
        edit_marker.setLatLng(latlng);
        document.getElementById('edit_latitude').value = latlng.lat.toFixed(8);
        document.getElementById('edit_longitude').value = latlng.lng.toFixed(8);
    });
}

// Image preview functionality for edit modal
function initEditImagePreview() {
    const editImageInput = document.getElementById('edit_image');
    if (editImageInput) {
        editImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewImage = document.getElementById('edit_preview-image');
                    const previewContainer = document.getElementById('edit_image-preview');
                    if (previewImage && previewContainer) {
                        previewImage.src = e.target.result;
                        previewContainer.classList.remove('hidden');
                    }
                }
                reader.readAsDataURL(file);
            }
        });
    }
}

function removeEditImage() {
    const editImageInput = document.getElementById('edit_image');
    const previewContainer = document.getElementById('edit_image-preview');
    if (editImageInput && previewContainer) {
        editImageInput.value = '';
        previewContainer.classList.add('hidden');
    }
}

// Get current location for edit modal
function getCurrentLocationEdit() {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser');
        return;
    }
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            if (edit_map && edit_marker) {
                edit_map.setView([lat, lng], 15);
                edit_marker.setLatLng([lat, lng]);
            }
            
            const editLatitude = document.getElementById('edit_latitude');
            const editLongitude = document.getElementById('edit_longitude');
            if (editLatitude && editLongitude) {
                editLatitude.value = lat.toFixed(8);
                editLongitude.value = lng.toFixed(8);
            }
        },
        function(error) {
            alert('Unable to get your location: ' + error.message);
        }
    );
}

// Calculate additional spaces for edit modal
function calculateAdditionalSpaces() {
    let additionalCar = 0, additionalMotorcycle = 0, additionalMiniTruck = 0;
    
    const floors = ['ground', 'second', 'third'];
    floors.forEach(floor => {
        const checkbox = document.querySelector(`#editParkingModal input[name="${floor}_floor"]`);
        if (checkbox && checkbox.checked) {
            const carInput = document.querySelector(`#editParkingModal input[name="${floor}_car"]`);
            const motorcycleInput = document.querySelector(`#editParkingModal input[name="${floor}_motorcycle"]`);
            const miniTruckInput = document.querySelector(`#editParkingModal input[name="${floor}_mini_truck"]`);
            
            if (carInput) additionalCar += parseInt(carInput.value) || 0;
            if (motorcycleInput) additionalMotorcycle += parseInt(motorcycleInput.value) || 0;
            if (miniTruckInput) additionalMiniTruck += parseInt(miniTruckInput.value) || 0;
        }
    });
    
    const totalAdditionalSpaces = additionalCar + additionalMotorcycle + additionalMiniTruck;
    
    const totalCarElem = document.getElementById('edit_total_car');
    const totalMotorcycleElem = document.getElementById('edit_total_motorcycle');
    const totalMiniTruckElem = document.getElementById('edit_total_mini_truck');
    const totalAdditionalElem = document.getElementById('edit_total_additional_spaces');
    
    if (totalCarElem) totalCarElem.textContent = additionalCar;
    if (totalMotorcycleElem) totalMotorcycleElem.textContent = additionalMotorcycle;
    if (totalMiniTruckElem) totalMiniTruckElem.textContent = additionalMiniTruck;
    if (totalAdditionalElem) totalAdditionalElem.textContent = totalAdditionalSpaces;
}

// Toggle floor capacity inputs for edit modal
function toggleEditFloorInputs(floor) {
    const inputs = document.querySelectorAll(`#editParkingModal input[name="${floor}_car"], #editParkingModal input[name="${floor}_motorcycle"], #editParkingModal input[name="${floor}_mini_truck"]`);
    const checkbox = document.querySelector(`#editParkingModal input[name="${floor}_floor"]`);
    
    if (inputs && checkbox) {
        inputs.forEach(input => {
            input.disabled = !checkbox.checked;
            if (!checkbox.checked) {
                input.value = '0';
            }
        });
        
        calculateAdditionalSpaces();
    }
}

// Initialize event listeners for edit modal
function initEditModalEvents() {
    // Floor checkbox event listeners
    document.querySelectorAll('#editParkingModal input[name$="_floor"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const floor = this.name.replace('_floor', '');
            toggleEditFloorInputs(floor);
        });
    });
    
    // Capacity input event listeners
    document.querySelectorAll('#editParkingModal .edit-floor-capacity').forEach(input => {
        input.addEventListener('input', calculateAdditionalSpaces);
    });
    
    // Coordinate input event listeners
    const editLatitude = document.getElementById('edit_latitude');
    const editLongitude = document.getElementById('edit_longitude');
    if (editLatitude && editLongitude) {
        editLatitude.addEventListener('change', updateEditMarkerFromInputs);
        editLongitude.addEventListener('change', updateEditMarkerFromInputs);
    }
    
    // Initialize
    calculateAdditionalSpaces();
    
    // Initialize only existing floors
    const floors = ['ground', 'second', 'third'];
    floors.forEach(floor => {
        toggleEditFloorInputs(floor);
    });
    
    // Initialize image preview
    initEditImagePreview();
    
    // Form submission handler
    const editForm = document.getElementById('editParkingForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            if (isEditFormSubmitting) {
                e.preventDefault();
                return;
            }
            
            const submitBtn = document.getElementById('editSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
            }
            
            isEditFormSubmitting = true;
            
            // Allow form to submit normally
        });
    }
}

function updateEditMarkerFromInputs() {
    if (!edit_map || !edit_marker) return;
    
    const editLatitude = document.getElementById('edit_latitude');
    const editLongitude = document.getElementById('edit_longitude');
    
    if (!editLatitude || !editLongitude) return;
    
    const lat = parseFloat(editLatitude.value);
    const lng = parseFloat(editLongitude.value);
    
    if (!isNaN(lat) && !isNaN(lng)) {
        edit_marker.setLatLng([lat, lng]);
        edit_map.setView([lat, lng]);
    }
}
</script>