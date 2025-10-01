<!-- DELETE MODAL -->
<div id="deleteParkingModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <form method="POST" action="process_delete_parking.php">
            <input type="hidden" name="parking_id" id="delete_parking_id">

            <!-- Header -->
            <div class="flex justify-between items-center px-6 py-4 border-b">
                <h3 class="text-lg font-semibold">Delete Parking Space</h3>
                <button type="button" onclick="closeModal('deleteParkingModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4">
                <p class="text-sm text-gray-600">
                    Are you sure you want to delete this parking space?  
                    Please enter your <span class="font-semibold text-red-600">admin password</span> to continue.
                </p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Admin Password</label>
                    <input type="password" name="admin_password"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                        required>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end px-6 py-4 border-t space-x-2">
                <button type="button" onclick="closeModal('deleteParkingModal')"
                    class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                    Delete
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Open Delete Modal
    function showDeleteParkingModal(parkingId) {
        document.getElementById('delete_parking_id').value = parkingId;
        document.getElementById('deleteParkingModal').classList.remove('hidden');
    }

    // Close Modal
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
</script>
