<!-- View Modal -->
<div id="viewModal"
    class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex justify-center items-center transition-opacity duration-300">
    <div
        class="bg-white w-full max-w-lg rounded-xl shadow-lg p-6 relative transform transition-all duration-300 scale-95 opacity-0">

        <!-- Centered, underlined title -->
        <h2 class="text-xl font-semibold text-[#3B060A] text-center border-b-2 border-[#3B060A] pb-2 mb-4">
            <?= htmlspecialchars($selectedParkingName) ?>
        </h2>

        <div id="viewContent" class="space-y-3"></div>
        <button onclick="closeModal('viewModal')" class="absolute top-3 right-3 text-gray-500 hover:text-gray-800">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal"
    class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex justify-center items-center transition-opacity duration-300">
    <div
        class="bg-white w-full max-w-lg rounded-xl shadow-lg p-6 relative transform transition-all duration-300 scale-95 opacity-0">

        <!-- Centered, underlined title -->
        <h2 class="text-xl font-semibold text-[#3B060A] text-center border-b-2 border-[#3B060A] pb-2 mb-4">
            Edit Parking Slot
        </h2>

        <form id="editForm" class="space-y-4">
            <input type="hidden" id="editTransactionId" name="transaction_id">
            <input type="hidden" id="editParkingId" name="parking_id">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" id="editName" name="name"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    readonly>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Slot Number</label>
                <input type="text" id="editSlotNumber" name="slot_number"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    readonly>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Floor</label>
                <input type="text" id="editFloor" name="floor"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    readonly>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Type</label>
                <input type="text" id="editVehicleType" name="vehicle_type"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    readonly>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Plan</label>
                <select id="editPlan" name="plan"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="hourly">Hourly</option>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="editStatus" name="status"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="pending">Pending</option>
                    <option value="ongoing">Active</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Storm Pass</label>
                <select id="editStormPass" name="storm_pass"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="Active">Active</option>
                    <option value="None">None</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Time</label>
                <input type="datetime-local" id="editExpiryTime" name="expiry_time"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </form>

        <div class="flex justify-end gap-3 mt-6">
            <button type="button" onclick="closeModal('editModal')"
                class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition-colors">Cancel</button>
            <button type="button" onclick="saveChanges()"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Save
                Changes</button>
        </div>

        <button onclick="closeModal('editModal')" class="absolute top-3 right-3 text-gray-500 hover:text-gray-800">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<!-- Success/Error Message Toast -->
<div id="messageToast" class="fixed top-4 right-4 p-4 rounded-lg shadow-lg hidden z-50">
    <div class="flex items-center">
        <span id="toastMessage" class="text-white"></span>
        <button onclick="hideToast()" class="ml-4 text-white hover:text-gray-200">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<script>
    function closeModal(id) {
        const modal = document.getElementById(id);
        const box = modal.querySelector("div.bg-white");

        // Animate out
        box.classList.remove("opacity-100", "scale-100");
        box.classList.add("opacity-0", "scale-95");
        modal.classList.remove("opacity-100");
        modal.classList.add("opacity-0");

        setTimeout(() => {
            modal.classList.add("hidden");
        }, 200);
    }

    function openModal(id) {
        const modal = document.getElementById(id);
        const box = modal.querySelector("div.bg-white");

        modal.classList.remove("hidden", "opacity-0");
        modal.classList.add("opacity-100");

        setTimeout(() => {
            box.classList.remove("opacity-0", "scale-95");
            box.classList.add("opacity-100", "scale-100");
        }, 50);
    }

    function showToast(message, type = 'success') {
        const toast = document.getElementById('messageToast');
        const toastMessage = document.getElementById('toastMessage');

        toastMessage.textContent = message;
        toast.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 flex items-center ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
        toast.classList.remove('hidden');

        setTimeout(hideToast, 5000);
    }

    function hideToast() {
        const toast = document.getElementById('messageToast');
        toast.classList.add('hidden');
    }

    // Defining function for color mapping
    function getStateBadge(state) {
        let base = "px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ";

        switch (state?.toLowerCase()) {
            case "ongoing":
                return base + "bg-green-200 text-green-800";
            case "pending":
                return base + "bg-yellow-200 text-yellow-800";
            case "completed":
                return base + "bg-amber-300 text-amber-900";
            case "cancelled":
                return base + "bg-red-200 text-red-800";
            case "available":
                return base + "bg-gray-200 text-gray-800";
            default:
                return base + "bg-gray-200 text-gray-800";
        }
    }

    function capitalize(word) {
        if (!word) return "None";
        return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
    }

    function formatRemainingTime(timeStr) {
        if (!timeStr || timeStr === "-") return "None";
        if (timeStr === "Expired") return "Expired";

        let parts = timeStr.split(":");
        if (parts.length !== 3) return timeStr;

        let [hours, minutes, seconds] = parts.map(Number);
        let result = [];

        if (hours > 0) result.push(hours + " " + (hours === 1 ? "hour" : "hours"));
        if (minutes > 0) result.push(minutes + " " + (minutes === 1 ? "minute" : "minutes"));
        if (seconds > 0) result.push(seconds + " " + (seconds === 1 ? "second" : "seconds"));

        return result.length > 0 ? result.join(" ") : "0 seconds";
    }

    function openViewModal(data, transactionId) {
        let stateText = capitalize(data.state);
        let formattedTime = formatRemainingTime(data.remaining_time);
        let planText = capitalize(data.plan);

        let html = `
            <p><strong>Name:</strong> ${data.name}</p>
            <p><strong>Remaining Time:</strong> ${formattedTime}</p>
            <p><strong>Slot:</strong> ${data.slot_number}</p>
            <p><strong>Floor:</strong> ${data.floor}</p>
            <p><strong>Plan:</strong> ${planText ?? '-'}</p>
            <p><strong>Vehicle:</strong> ${data.vehicle_type}</p>
            <p><strong>State:</strong> 
                <span class="${getStateBadge(data.state)}">
                    ${stateText}
                </span>
            </p>
            ${transactionId ? `<p><strong>Transaction ID:</strong> ${transactionId}</p>` : ''}
        `;
        document.getElementById("viewContent").innerHTML = html;
        openModal("viewModal");
    }

    function openEditModal(data, transactionId) {
        if (!transactionId) {
            alert('Cannot edit available slot');
            return;
        }

        // Get the parking ID from the table row
        const row = document.querySelector(`tr[data-transaction-id="${transactionId}"]`);
        const parkingId = row ? row.getAttribute('data-parking-id') : '<?= $selectedParking ?>';

        // Fetch transaction details to get actual expiry time from database
        fetch(`get_transaction_details.php?transaction_id=${transactionId}`)
            .then(response => response.json())
            .then(transactionData => {
                if (transactionData.success) {
                    // Set form values with actual data from database
                    document.getElementById('editTransactionId').value = transactionId;
                    document.getElementById('editParkingId').value = parkingId;
                    document.getElementById('editName').value = data.name;
                    document.getElementById('editSlotNumber').value = data.slot_number;
                    document.getElementById('editFloor').value = data.floor;
                    document.getElementById('editVehicleType').value = data.vehicle_type;
                    document.getElementById('editPlan').value = data.plan?.toLowerCase() || 'hourly';
                    document.getElementById('editStatus').value = data.state?.toLowerCase() || 'pending';

                    // Set storm pass from actual database value
                    document.getElementById('editStormPass').value = transactionData.storm_pass || 'None';

                    // Set actual expiry time from database
                    if (transactionData.expiry_time) {
                        const expiryDate = new Date(transactionData.expiry_time);
                        document.getElementById('editExpiryTime').value = expiryDate.toISOString().slice(0, 16);
                    }

                    openModal("editModal");
                } else {
                    showToast('Error loading transaction details', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error loading transaction details', 'error');
            });
    }

    function saveChanges() {
        const formData = new FormData();
        formData.append('transaction_id', document.getElementById('editTransactionId').value);
        formData.append('parking_id', document.getElementById('editParkingId').value);
        formData.append('plan', document.getElementById('editPlan').value);
        formData.append('status', document.getElementById('editStatus').value);
        formData.append('storm_pass', document.getElementById('editStormPass').value);
        formData.append('expiry_time', document.getElementById('editExpiryTime').value);

        fetch('update_parking_slot.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Changes saved successfully!', 'success');
                    closeModal('editModal');
                    // Reload page after a short delay to show updated data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast('Error saving changes: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while saving changes', 'error');
            });
    }
</script>