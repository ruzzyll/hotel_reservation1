<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_role(['admin']);

// --- 1. HANDLE FORM SUBMISSIONS (ADD & UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $uploadDir = __DIR__ . '/../assets/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true); // Ensure folder exists

    // A. ADD NEW ROOM
    if ($_POST['action'] === 'add_room') {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = (float)$_POST['price'];
        
        // Handle Image Upload
        $imageName = ''; 
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imageName = 'room_' . time() . '.' . $fileExt;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
        }

        $stmt = $pdo->prepare("INSERT INTO room_types (name, description, price, image) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $imageName]);
        
        header("Location: index.php");
        exit;
    }

    // B. UPDATE EXISTING ROOM (Modified to support Image)
    if ($_POST['action'] === 'update_room') {
        $id = (int)$_POST['room_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = (float)$_POST['price'];

        // Check if a NEW image was uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            // 1. Upload new image
            $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $newImageName = 'room_' . time() . '.' . $fileExt;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newImageName);

            // 2. Update WITH image
            $stmt = $pdo->prepare("UPDATE room_types SET name = ?, description = ?, price = ?, image = ? WHERE id = ?");
            $stmt->execute([$name, $description, $price, $newImageName, $id]);
        } else {
            // 3. Update WITHOUT changing image (Keep old one)
            $stmt = $pdo->prepare("UPDATE room_types SET name = ?, description = ?, price = ? WHERE id = ?");
            $stmt->execute([$name, $description, $price, $id]);
        }

        header("Location: index.php");
        exit;
    }
}

// --- 2. FETCH ROOM DATA ---
$rooms = $pdo->query("SELECT * FROM room_types ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Hotel Admin | Rooms</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar-link.active { background: rgba(255,255,255,0.1); border-left: 4px solid #3b82f6; }
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow-x: hidden; overflow-y: hidden !important; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[#f4f6f9]">

<div class="flex min-h-screen">
    <aside class="w-64 bg-[#343a40] text-gray-300 flex-shrink-0">
        <div class="p-4 text-white text-xl font-bold border-b border-gray-700 flex items-center gap-2">
            <i class="fas fa-hotel text-blue-400"></i> Hotel Admin
        </div>
        <nav class="mt-4">
            <a href="index.php" class="sidebar-link active flex items-center px-6 py-3 text-white">
                <i class="fas fa-bed w-8"></i> Rooms
            </a>
            <a href="analytics.php" class="sidebar-link flex items-center px-6 py-3 hover:bg-gray-700 transition">
                <i class="fas fa-chart-bar w-8"></i> Analytics
            </a>
            <a href="reservations.php" class="sidebar-link flex items-center px-6 py-3 hover:bg-gray-700 transition">
                <i class="fas fa-calendar-check w-8"></i> Customer Reserve
            </a>
            <div class="border-t border-gray-700 mt-4 pt-4">
                <a href="../auth/logout.php" class="flex items-center px-6 py-3 text-red-400 hover:bg-red-900/20 transition">
                    <i class="fas fa-sign-out-alt w-8"></i> Logout
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-grow h-screen overflow-y-auto">
        <header class="bg-white shadow-sm px-8 py-4 flex justify-between items-center sticky top-0 z-10">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Room Management</h2>
                <p class="text-sm text-gray-500">Manage room types, pricing, and descriptions.</p>
            </div>
            <button onclick="openAddModal()" class="bg-[#0f2e46] text-white px-4 py-2 rounded hover:bg-[#1a4b70] transition shadow-md">
                <i class="fas fa-plus-circle mr-2"></i> Add New Room Type
            </button>
        </header>

        <div class="p-8">
            <div class="flex items-center gap-2 mb-6">
                <i class="fas fa-tags text-yellow-600"></i>
                <h3 class="text-lg font-bold text-gray-700">Active Room Types</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($rooms as $room): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition border border-gray-100 flex flex-col h-full">
                        <div class="h-48 bg-gray-200 relative group">
                            <?php if (!empty($room['image'])): ?>
                                <img src="../assets/uploads/<?php echo htmlspecialchars($room['image']); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <i class="fas fa-image text-4xl"></i>
                                </div>
                            <?php endif; ?>
                            
                            <button onclick='openEditModal(<?php echo json_encode($room); ?>)' class="absolute top-3 right-3 bg-white/90 text-blue-600 px-3 py-1 rounded shadow text-sm font-bold hover:bg-blue-600 hover:text-white transition">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </button>
                        </div>

                        <div class="p-5 flex-grow flex flex-col">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($room['name']); ?></h4>
                                <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded uppercase font-bold tracking-wider">Active</span>
                            </div>
                            
                            <p class="text-gray-500 text-sm mb-4 line-clamp-2 flex-grow">
                                <?php echo htmlspecialchars($room['description']); ?>
                            </p>

                            <div class="pt-4 border-t border-gray-100">
                                <div class="text-gray-400 text-xs uppercase font-bold">Price per night</div>
                                <div class="text-2xl font-bold text-gray-800">₱<?php echo number_format($room['price'], 2); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>

<div id="addModal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
    <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-60" onclick="closeAddModal()"></div>
    <div class="modal-container bg-white w-full max-w-lg mx-auto rounded-lg shadow-xl z-50 overflow-hidden transform transition-all">
        <div class="bg-[#0f2e46] px-6 py-4 flex justify-between items-center text-white">
            <h3 class="font-bold text-lg"><i class="fas fa-plus mr-2"></i> Add New Room</h3>
            <button onclick="closeAddModal()" class="hover:text-gray-300"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" action="index.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_room">
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Room Name</label>
                    <input type="text" name="name" class="w-full px-3 py-2 border rounded focus:border-blue-500 outline-none" required>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded focus:border-blue-500 outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Price (₱)</label>
                    <input type="number" step="0.01" name="price" class="w-full px-3 py-2 border rounded focus:border-blue-500 outline-none" required>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Room Photo</label>
                    <input type="file" name="image" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                <button type="button" onclick="closeAddModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-[#0f2e46] text-white rounded hover:bg-[#1a4b70]">Save Room</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
    <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-60" onclick="closeEditModal()"></div>
    <div class="modal-container bg-white w-full max-w-lg mx-auto rounded-lg shadow-xl z-50 overflow-hidden transform transition-all">
        <div class="bg-[#0f2e46] px-6 py-4 flex justify-between items-center text-white">
            <h3 class="font-bold text-lg"><i class="fas fa-edit mr-2"></i> Edit Room Type</h3>
            <button onclick="closeEditModal()" class="hover:text-gray-300"><i class="fas fa-times"></i></button>
        </div>

        <form method="POST" action="index.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_room">
            <input type="hidden" id="edit_room_id" name="room_id">
            
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Room Type Name</label>
                    <input type="text" id="edit_name" name="name" class="w-full px-3 py-2 border rounded focus:border-blue-500 outline-none" required>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                    <textarea id="edit_description" name="description" rows="3" class="w-full px-3 py-2 border rounded focus:border-blue-500 outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Base Price (₱)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500">₱</span>
                        <input type="number" step="0.01" id="edit_price" name="price" class="w-full pl-8 pr-3 py-2 border rounded focus:border-blue-500 outline-none" required>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Update Photo (Optional)</label>
                    <div id="current_image_container" class="mb-2 hidden">
                        <p class="text-xs text-gray-500 mb-1">Current Image:</p>
                        <img id="edit_image_preview" src="" class="h-20 w-32 object-cover rounded border border-gray-300">
                    </div>
                    
                    <input type="file" name="image" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-xs text-gray-500 mt-1">Leave blank to keep the current photo.</p>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-[#0f2e46] text-white rounded hover:bg-[#1a4b70]">Update Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- ADD MODAL FUNCTIONS ---
    function openAddModal() {
        const modal = document.getElementById('addModal');
        modal.classList.remove('opacity-0', 'pointer-events-none');
        document.body.classList.add('modal-active');
    }
    function closeAddModal() {
        const modal = document.getElementById('addModal');
        modal.classList.add('opacity-0', 'pointer-events-none');
        document.body.classList.remove('modal-active');
    }

    // --- EDIT MODAL FUNCTIONS ---
    function openEditModal(room) {
        document.getElementById('edit_room_id').value = room.id;
        document.getElementById('edit_name').value = room.name;
        document.getElementById('edit_description').value = room.description;
        document.getElementById('edit_price').value = room.price;

        // Image Preview Logic
        const imgContainer = document.getElementById('current_image_container');
        const imgPreview = document.getElementById('edit_image_preview');

        if (room.image) {
            imgPreview.src = '../assets/uploads/' + room.image;
            imgContainer.classList.remove('hidden');
        } else {
            imgContainer.classList.add('hidden');
        }

        const modal = document.getElementById('editModal');
        modal.classList.remove('opacity-0', 'pointer-events-none');
        document.body.classList.add('modal-active');
    }
    function closeEditModal() {
        const modal = document.getElementById('editModal');
        modal.classList.add('opacity-0', 'pointer-events-none');
        document.body.classList.remove('modal-active');
    }
</script>
</body>
</html>