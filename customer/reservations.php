<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_role(['customer', 'staff']); 

$user = current_user();
$user_id = $user['id'];

// --- 1. DELETE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $stmtDelete = $pdo->prepare("DELETE FROM reservations WHERE id = ? AND customer_id = ?");
        $stmtDelete->execute([$_POST['delete_id'], $user_id]);
        
        set_flash('message', "Reservation has been successfully cancelled.");
        header("Location: reservations.php");
        exit();
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

// --- 2. FETCH RESERVATIONS ---
try {
    // FIXED SQL: 
    // 1. Used LEFT JOIN so reservations don't disappear if a join fails.
    // 2. Joined 'room_types' to get the real price based on the room name.
    $sql = "SELECT 
                r.*, 
                r.status as status_text,      -- Read status text directly from reservation
                r.room_type as room_name,     -- Read room name directly
                rt.price as current_price,    -- Get price from room_types table
                c.name as customer_name, 
                c.contact as customer_contact
            FROM reservations r
            LEFT JOIN room_types rt ON r.room_type = rt.name
            LEFT JOIN customer c ON r.customer_id = c.id
            WHERE r.customer_id = ?
            ORDER BY r.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $my_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Bookings | Grand Horizon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-50 min-h-screen" x-data="{ deleteModal: false, detailsModal: false, activeId: null, selectedReservation: {} }">

    <nav class="bg-[#1a1c2e] px-10 py-6 flex justify-between items-center shadow-xl">
        <h1 class="text-white font-serif font-bold tracking-widest text-xl">GRAND HORIZON</h1>
        <div class="flex items-center gap-8 text-white text-xs font-black uppercase tracking-widest">
            <a href="home.php" class="hover:text-amber-500 transition">Back to Rooms</a>
            <a href="../auth/logout.php" class="bg-white/10 px-4 py-2 rounded-lg">Logout</a>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-6 py-16">
        <div class="mb-10">
            <h2 class="text-4xl font-serif font-bold text-slate-900">My Reservations</h2>
            <p class="text-slate-500 mt-2">Manage and track your stays at Grand Horizon.</p>
        </div>

        <?php $msg = get_flash('message'); if ($msg): ?>
            <div class="mb-8 bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-xl text-emerald-800 flex justify-between items-center shadow-sm">
                <span><i class="fas fa-check-circle mr-2 text-emerald-500"></i> <?= htmlspecialchars($msg) ?></span>
                <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-600">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (empty($my_reservations)): ?>
            <div class="text-center py-20 bg-white rounded-[3.5rem] shadow-sm border border-dashed border-slate-300">
                <i class="fas fa-calendar-times text-6xl text-slate-200 mb-6"></i>
                <h3 class="text-xl font-bold text-slate-400">No reservations found.</h3>
                <a href="index.php" class="mt-4 inline-block text-amber-600 font-bold uppercase tracking-widest text-xs">Book your first room &rarr;</a>
            </div>
        <?php else: ?>
            <div class="grid gap-6">
                <?php foreach ($my_reservations as $res): 
                    // Logic to handle status colors/text
                    $s_label = ucfirst($res['status_text'] ?? 'Pending'); // Default to Pending
                    $s_color = strtolower($s_label);

                    $check_in = new DateTime($res['check_in']);
                    $check_out = new DateTime($res['check_out']);
                    $nights = $check_in->diff($check_out)->days;
                    if($nights <= 0) $nights = 1;

                    // Use price from DB, default to 0 if missing
                    $pricePerNight = $res['current_price'] ?? 0;
                    $total_price = $nights * $pricePerNight;
                    
                    // JSON for Modal
                    $resData = htmlspecialchars(json_encode([
                        'id' => $res['id'],
                        'room_name' => $res['room_name'],
                        'check_in' => $check_in->format('M d, Y'),
                        'check_out' => $check_out->format('M d, Y'),
                        'total_price' => number_format($total_price),
                        'status_text' => $s_label,
                        'customer_name' => $res['customer_name'] ?? $user['name'],
                        'customer_email' => $res['customer_email'],
                        'contact_number' => $res['contact_number'] ?? $res['customer_contact']
                    ]));
                ?>
                    <div class="bg-white rounded-[2.5rem] p-8 shadow-md border border-slate-100 flex flex-col md:flex-row justify-between items-center group hover:shadow-xl transition-all">
                        <div class="flex items-center gap-8 w-full">
                            <div class="h-16 w-16 rounded-3xl flex items-center justify-center 
                                <?= str_contains($s_color, 'confirm') || str_contains($s_color, 'approve') ? 'bg-green-100 text-green-600' : 
                                   (str_contains($s_color, 'cancel') || str_contains($s_color, 'reject') ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600') ?>">
                                
                                <i class="fas <?= str_contains($s_color, 'confirm') || str_contains($s_color, 'approve') ? 'fa-check' : 
                                               (str_contains($s_color, 'cancel') || str_contains($s_color, 'reject') ? 'fa-times' : 'fa-clock') ?> text-2xl"></i>
                            </div>

                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-slate-900"><?= htmlspecialchars($res['room_name']) ?></h4>
                                <div class="flex gap-4 mt-2 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    <span><i class="far fa-calendar mr-1"></i> In: <?= date('M d, Y', strtotime($res['check_in'])) ?></span>
                                    <span><i class="far fa-calendar-check mr-1"></i> Out: <?= date('M d, Y', strtotime($res['check_out'])) ?></span>
                                </div>
                                <div class="mt-3 inline-flex flex-col">
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Total Payment</span>
                                    <span class="text-xl font-black text-slate-900">₱<?= number_format($total_price) ?> <span class="text-xs text-slate-400 font-normal">/ <?= $nights ?> Nights</span></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 md:mt-0 flex items-center gap-4 w-full md:w-auto justify-between md:justify-end">
                            <div class="text-right mr-2">
                                <span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest">Status</span>
                                <span class="inline-block px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-widest 
                                    <?= str_contains($s_color, 'confirm') || str_contains($s_color, 'approve') ? 'bg-green-500 text-white' : 
                                       (str_contains($s_color, 'cancel') || str_contains($s_color, 'reject') ? 'bg-red-500 text-white' : 'bg-amber-500 text-white') ?>">
                                    <?= htmlspecialchars($s_label) ?>
                                </span>
                            </div>

                            <button @click="deleteModal = true; activeId = <?= $res['id'] ?>" 
                                    class="bg-rose-50 hover:bg-rose-500 text-rose-500 hover:text-white h-12 w-12 rounded-2xl transition-all duration-300">
                                <i class="fas fa-trash-alt"></i>
                            </button>

                            <button @click="detailsModal = true; selectedReservation = JSON.parse('<?= $resData ?>')" 
                                    class="bg-blue-50 hover:bg-blue-500 text-blue-500 hover:text-white h-12 w-12 rounded-2xl transition-all duration-300">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <div x-show="deleteModal" class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-900/60 backdrop-blur-sm" x-cloak x-transition>
        <div class="bg-white w-full max-w-sm rounded-[2.5rem] p-10 text-center shadow-2xl" @click.away="deleteModal = false">
            <div class="w-20 h-20 bg-rose-100 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-exclamation-triangle text-3xl"></i>
            </div>
            <h3 class="text-2xl font-serif font-bold text-slate-900 mb-2">Delete Booking?</h3>
            <p class="text-slate-500 text-sm mb-8 leading-relaxed">Are you sure you want to cancel this reservation? This action cannot be undone.</p>
            <div class="flex flex-col gap-3">
                <form action="reservations.php" method="POST">
                    <input type="hidden" name="delete_id" :value="activeId">
                    <button type="submit" class="w-full bg-rose-500 text-white font-black py-4 rounded-2xl uppercase tracking-widest text-xs hover:bg-rose-600 transition shadow-lg shadow-rose-200">
                        Yes, Delete It
                    </button>
                </form>
                <button @click="deleteModal = false" class="w-full text-slate-400 font-black py-2 uppercase tracking-widest text-[10px] hover:text-slate-600 transition">
                    No, Keep Reservation
                </button>
            </div>
        </div>
    </div>

    <div x-show="detailsModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak x-transition>
        <div class="bg-white w-full max-w-2xl rounded-[2rem] overflow-hidden shadow-2xl" @click.away="detailsModal = false">
            <div class="bg-[#1a1c2e] p-6 flex justify-between items-center text-white">
                <h3 class="font-bold text-lg flex items-center"><i class="fas fa-info-circle mr-3"></i> Reservation Details</h3>
                <button @click="detailsModal = false" class="text-slate-400 hover:text-white transition"><i class="fas fa-times text-xl"></i></button>
            </div>
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Guest Name</label>
                        <p class="font-bold text-slate-900 text-lg" x-text="selectedReservation.customer_name"></p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Contact Email</label>
                        <p class="font-bold text-slate-900" x-text="selectedReservation.customer_email"></p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Phone</label>
                        <p class="font-bold text-slate-900" x-text="selectedReservation.contact_number || 'N/A'"></p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Amount</label>
                        <p class="font-black text-amber-600 text-2xl">₱<span x-text="selectedReservation.total_price"></span></p>
                    </div>
                </div>
                <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100 mb-8">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Room Info</label>
                    <p class="font-bold text-slate-900 text-lg mb-1" x-text="selectedReservation.room_name"></p>
                    <p class="text-sm text-slate-500">
                        Check-in: <span class="font-semibold" x-text="selectedReservation.check_in"></span> &nbsp;|&nbsp; 
                        Check-out: <span class="font-semibold" x-text="selectedReservation.check_out"></span>
                    </p>
                </div>
                <div class="text-right">
                    <button @click="detailsModal = false" class="bg-slate-200 text-slate-700 font-bold py-3 px-8 rounded-xl uppercase tracking-widest text-xs hover:bg-slate-300 transition">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>[x-cloak] { display: none !important; }</style>
</body>
</html>