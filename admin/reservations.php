<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_role(['admin']); // Guard: Admins only

// --- 1. HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ACTION: APPROVE OR REJECT
    if (isset($_POST['action'], $_POST['reservation_id']) && in_array($_POST['action'], ['approve', 'reject'])) {
        $newStatus = $_POST['action'] === 'approve' ? 'approved' : 'cancelled';
        $resId = (int)$_POST['reservation_id'];

        try {
            $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $resId]);
            set_flash('message', "Reservation marked as " . ucfirst($newStatus));
        } catch (PDOException $e) {
            set_flash('error', "Error updating status: " . $e->getMessage());
        }
        
        // FIX: Redirect to the CORRECT filename
        header("Location: reservations.php");
        exit();
    }

    // ACTION: ARCHIVE (Instead of Delete)
    if (isset($_POST['delete_id'])) {
        $archiveId = (int)$_POST['delete_id'];
        try {
            // WE DO NOT DELETE. WE UPDATE STATUS TO 'ARCHIVED'
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'archived' WHERE id = ?");
            $stmt->execute([$archiveId]);
            set_flash('message', "Reservation has been archived.");
        } catch (PDOException $e) {
            set_flash('error', "Error archiving record: " . $e->getMessage());
        }

        // FIX: Redirect to the CORRECT filename
        header("Location: reservations.php");
        exit();
    }
}

// --- 2. FETCH DATA ---
try {
    // FIX: Added "WHERE r.status != 'archived'" so we don't see deleted items
    $sql = "SELECT 
                r.*,
                rt.price as current_room_price
            FROM reservations r
            LEFT JOIN room_types rt ON r.room_type = rt.name
            WHERE r.status != 'archived' 
            ORDER BY 
                CASE WHEN r.status = 'pending' THEN 0 ELSE 1 END,
                r.created_at DESC";
    
    $stmt = $pdo->query($sql);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Reservations | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .sidebar-link.active { background: rgba(255,255,255,0.1); border-left: 4px solid #3b82f6; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[#f4f6f9] h-screen overflow-hidden flex" x-data="{ showDeleteModal: false, deleteId: null }">

    <aside class="w-64 bg-[#343a40] text-gray-300 flex-shrink-0 hidden md:flex flex-col">
        <div class="p-4 text-white text-xl font-bold border-b border-gray-700 flex items-center gap-2">
            <i class="fas fa-hotel text-blue-400"></i> Hotel Admin
        </div>
        <nav class="mt-4 flex-1">
            <a href="index.php" class="sidebar-link flex items-center px-6 py-3 hover:bg-gray-700 transition">
                <i class="fas fa-bed w-8"></i> Rooms
            </a>
            <a href="analytics.php" class="sidebar-link flex items-center px-6 py-3 hover:bg-gray-700 transition">
                <i class="fas fa-chart-bar w-8"></i> Analytics
            </a>
            <a href="reservations.php" class="sidebar-link active flex items-center px-6 py-3 text-white">
                <i class="fas fa-calendar-check w-8"></i> Reservations
            </a>
        </nav>
        <div class="p-4 border-t border-gray-700">
            <a href="../auth/logout.php" class="flex items-center text-red-400 hover:text-red-300 transition">
                <i class="fas fa-sign-out-alt w-6"></i> Logout
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="bg-white shadow-sm px-8 py-4 flex justify-between items-center z-10">
            <h2 class="text-xl font-semibold text-gray-800">Reservation Management</h2>
            <div class="text-sm text-gray-500">
                <span class="font-bold text-gray-800"><?= count($reservations) ?></span> Active Bookings
            </div>
        </header>

        <?php if ($msg = get_flash('message')): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mx-8 mt-6" role="alert">
                <p><?= htmlspecialchars($msg) ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($err = get_flash('error')): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mx-8 mt-6" role="alert">
                <p><?= htmlspecialchars($err) ?></p>
            </div>
        <?php endif; ?>

        <div class="flex-1 overflow-auto p-8">
            <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <th class="px-5 py-3">Guest Info</th>
                            <th class="px-5 py-3">Room Details</th>
                            <th class="px-5 py-3">Dates</th>
                            <th class="px-5 py-3 text-right">Total</th>
                            <th class="px-5 py-3 text-center">Status</th>
                            <th class="px-5 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservations)): ?>
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-3 block text-gray-300"></i>
                                    No active reservations found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reservations as $res): 
                                $checkIn = new DateTime($res['check_in']);
                                $checkOut = new DateTime($res['check_out']);
                                $nights = $checkIn->diff($checkOut)->days;
                                if ($nights < 1) $nights = 1;
                                
                                $price = $res['current_room_price'] ?? 0;
                                $total = $nights * $price;
                                
                                $status = strtolower($res['status']);
                                
                                // Fallback for guest info
                                $displayEmail = $res['customer_email'] ?? $res['email'] ?? 'No Email';
                                $displayContact = $res['phone'] ?? $res['contact_number'] ?? '';
                                $displayName = $res['name'] ?? 'Guest #' . $res['user_id'];
                            ?>
                            <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                <td class="px-5 py-4">
                                    <div class="flex items-center">
                                        <div class="ml-3">
                                            <p class="text-gray-900 whitespace-no-wrap font-bold">
                                                <?= htmlspecialchars($displayName) ?>
                                            </p>
                                            <p class="text-gray-500 text-xs mt-1">
                                                <?= htmlspecialchars($displayEmail) ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-4">
                                    <p class="text-gray-900 whitespace-no-wrap font-semibold"><?= htmlspecialchars($res['room_type']) ?></p>
                                    <p class="text-gray-400 text-xs">ID: #<?= $res['id'] ?></p>
                                </td>

                                <td class="px-5 py-4">
                                    <div class="text-sm text-gray-600">
                                        <div class="flex items-center gap-2"><i class="fas fa-sign-in-alt text-green-400 w-4"></i> <?= $checkIn->format('M d') ?></div>
                                        <div class="flex items-center gap-2 mt-1"><i class="fas fa-sign-out-alt text-red-400 w-4"></i> <?= $checkOut->format('M d') ?></div>
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-right">
                                    <p class="text-gray-900 font-bold">₱<?= number_format($total) ?></p>
                                    <p class="text-gray-400 text-xs"><?= $nights ?> nights</p>
                                </td>

                                <td class="px-5 py-4 text-center">
                                    <?php if ($status == 'approved'): ?>
                                        <span class="px-3 py-1 font-semibold leading-tight text-green-900 bg-green-100 rounded-full text-xs">Approved</span>
                                    <?php elseif ($status == 'pending'): ?>
                                        <span class="px-3 py-1 font-semibold leading-tight text-yellow-900 bg-yellow-100 rounded-full text-xs animate-pulse">Pending</span>
                                    <?php elseif ($status == 'cancelled'): ?>
                                        <span class="px-3 py-1 font-semibold leading-tight text-red-900 bg-red-100 rounded-full text-xs">Cancelled</span>
                                    <?php endif; ?>
                                </td>

                                <td class="px-5 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <?php if ($status == 'pending'): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white p-2 rounded shadow transition" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>

                                            <form method="POST" class="inline">
                                                <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white p-2 rounded shadow transition" title="Reject">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <button @click="showDeleteModal = true; deleteId = <?= $res['id'] ?>" 
                                                class="bg-gray-100 hover:bg-red-500 hover:text-white text-gray-400 p-2 rounded shadow transition" 
                                                title="Archive Record">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div x-show="showDeleteModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         x-cloak aria-labelledby="modal-title" role="dialog" aria-modal="true">
        
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showDeleteModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 @click="showDeleteModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-archive text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Archive Reservation</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Are you sure you want to archive this record? It will be hidden from this list but kept in the database.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form method="POST">
                        <input type="hidden" name="delete_id" :value="deleteId">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            Yes, Archive
                        </button>
                    </form>
                    <button type="button" @click="showDeleteModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>