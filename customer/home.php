<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_role(['customer', 'staff']); 

$user = current_user();
$user_id = $user['id'];
$user_email = $user['email'];
$user_full_name = $user['name'];

// Get today's date for HTML 'min' attribute
$today = date('Y-m-d');

// --- 1. RESERVATION PROCESSING LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_name'])) {
    
    // NOTE: Since we are now using room_types, we assume Hotel ID is 1 (Single Hotel System)
    // If you have multiple hotels, you need to add a 'hotel_id' column to your 'room_types' table.
    $hotel_id = 1; 

    // Check if customer already exists in 'customer' table; if not, add them
    $stmtCustCheck = $pdo->prepare("SELECT id FROM customer WHERE id = ?");
    $stmtCustCheck->execute([$user_id]);
    if (!$stmtCustCheck->fetch()) {
        $insCust = $pdo->prepare("INSERT INTO customer (id, name, contact) VALUES (?, ?, ?)");
        $insCust->execute([$user_id, $user_full_name, $_POST['phone']]); 
    }

    try {
        $sql = "INSERT INTO reservations 
                (customer_id, hotel_id, user_id, customer_email, contact_number, room_type, status_id, status, check_in, check_out, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            $user_id,           // customer_id
            $hotel_id,          // hotel_id
            $user_id,           // user_id
            $user_email,        // customer_email
            $_POST['phone'],    // contact_number
            $_POST['room_name'], // room_type
            1,                  // status_id (1 = Pending)
            'Pending',          // status
            $_POST['check_in'],  // check_in
            $_POST['check_out']  // check_out
        ]);
        
        set_flash('message', "Success! Your reservation for " . $_POST['room_name'] . " has been received.");
        header("Location: reservations.php");
        exit(); 

    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}

// --- 2. FETCH ROOMS FROM DATABASE (Admin Data) ---
// This replaces the hardcoded array
$stmt = $pdo->query("SELECT * FROM room_types ORDER BY price ASC");
$db_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Grand Horizon | Signature Rooms</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-50" 
      x-data="{ 
        bookingModal: false, imgModal: false, selectedRoom: '', currentImg: '',
        checkIn: '', checkOut: '', pricePerNight: 0,
        today: '<?= $today ?>',
        userEmail: '<?= htmlspecialchars($user_email) ?>',
        userName: '<?= htmlspecialchars($user_full_name) ?>',
        calculateTotal() {
            if (!this.checkIn || !this.checkOut) return 0;
            const diff = (new Date(this.checkOut) - new Date(this.checkIn)) / (1000 * 60 * 60 * 24);
            return (diff > 0 ? diff : 0) * this.pricePerNight;
        }
      }">

    <nav class="bg-[#1a1c2e] px-10 py-6 flex justify-between items-center sticky top-0 z-50 shadow-xl">
        <h1 class="text-white font-serif font-bold tracking-widest text-xl">GRAND HORIZON</h1>
        <div class="flex items-center gap-8 text-white text-xs font-black uppercase tracking-widest">
            <a href="reservations.php" class="text-amber-500 hover:text-white transition">My Bookings</a>
            <a href="../auth/logout.php" class="bg-white/10 px-4 py-2 rounded-lg">Logout</a>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-6 py-20 space-y-20">
        
        <?php if (empty($db_rooms)): ?>
            <div class="text-center text-gray-500 py-20">
                <i class="fas fa-bed text-6xl mb-4 text-gray-300"></i>
                <p>No rooms available at the moment.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($db_rooms as $room): 
            // 1. Handle Image Path
            // If image is in DB, look in uploads folder. If not, use a default fallback.
            $imgSource = !empty($room['image']) 
                ? '../assets/uploads/' . htmlspecialchars($room['image']) 
                : 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=800'; 
        ?>
            <div class="bg-white rounded-[3rem] p-6 shadow-2xl border border-gray-100 flex flex-col lg:flex-row gap-8 group hover:translate-y-[-5px] transition-all duration-500">
                
                <div class="lg:w-1/2 h-[350px] overflow-hidden rounded-[2.5rem] cursor-pointer relative" 
                     @click="imgModal = true; currentImg = '<?= $imgSource ?>'">
                    <img src="<?= $imgSource ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-700">
                    <div class="absolute bottom-4 right-4 bg-black/50 text-white px-3 py-1 rounded-full text-xs backdrop-blur-sm">
                        <i class="fas fa-expand-arrows-alt mr-1"></i> View
                    </div>
                </div>

                <div class="flex-1 py-4 flex flex-col justify-between">
                    <div>
                        <h3 class="text-4xl font-serif font-bold text-gray-900 mb-4"><?= htmlspecialchars($room['name']) ?></h3>
                        <p class="text-gray-500 italic border-l-4 border-amber-500 pl-6 mb-8 leading-relaxed">
                            <?= htmlspecialchars($room['description']) ?>
                        </p>
                    </div>

                    <div class="flex flex-col gap-4">
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-black text-slate-900">₱<?= number_format($room['price']) ?></span>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">/ Night</span>
                        </div>
                        
                        <button @click="bookingModal = true; selectedRoom = '<?= htmlspecialchars($room['name']) ?>'; pricePerNight = <?= $room['price'] ?>;" 
                                class="w-full bg-[#1a1c2e] text-amber-500 py-5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-black hover:text-white transition-all shadow-xl">
                            Reserve This Room
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </main>

    <div x-show="bookingModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md" x-cloak x-transition>
        <div class="bg-white w-full max-w-lg rounded-[2.5rem] overflow-hidden shadow-2xl max-h-[90vh] overflow-y-auto" @click.away="bookingModal = false">
            
            <div class="bg-[#1a1c2e] p-6 text-center text-white sticky top-0 z-10">
                <h3 class="text-amber-500 font-black text-[10px] uppercase tracking-[0.3em] mb-2">Confirm Your Stay</h3>
                <h2 class="text-2xl font-serif font-bold" x-text="selectedRoom"></h2>
            </div>
            
            <form action="home.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="room_name" :value="selectedRoom">
                
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2 mb-1 block">Full Name</label>
                            <input type="text" x-model="userName" readonly class="w-full p-3 bg-slate-100 border border-slate-200 rounded-xl text-[11px] text-slate-500 cursor-not-allowed font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2 mb-1 block">Email</label>
                            <input type="email" name="customer_email" x-model="userEmail" readonly class="w-full p-3 bg-slate-100 border border-slate-200 rounded-xl text-[11px] text-slate-500 cursor-not-allowed">
                        </div>
                    </div>

                    <input type="text" name="phone" placeholder="Contact Number" required class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-amber-500 transition">
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2 mb-1 block">Check-In</label>
                            <input type="date" name="check_in" x-model="checkIn" :min="today" required 
                                   class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2 mb-1 block">Check-Out</label>
                            <input type="date" name="check_out" x-model="checkOut" :min="checkIn ? checkIn : today" required 
                                   class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl flex gap-3">
                    <i class="fas fa-info-circle text-blue-500 text-lg mt-1"></i>
                    <div>
                        <h4 class="text-xs font-black text-blue-900 uppercase tracking-widest mb-1">Payment Notice</h4>
                        <p class="text-[11px] text-blue-700 leading-relaxed">
                            Payment is Walk-in Only. Settle balance at front desk.
                        </p>
                    </div>
                </div>

                <div class="bg-amber-50 p-4 rounded-xl border border-amber-100 flex justify-between items-center" x-show="checkIn && checkOut">
                    <div>
                        <span class="block text-[10px] font-black text-amber-600 uppercase mb-1">Total Stay Amount</span>
                        <span class="text-2xl font-black text-slate-900">₱<span x-text="calculateTotal().toLocaleString()"></span></span>
                    </div>
                    <i class="fas fa-coins text-amber-200 text-2xl"></i>
                </div>

                <button type="submit" class="w-full bg-amber-500 text-white font-black py-4 rounded-2xl uppercase tracking-widest hover:bg-amber-600 transition-all shadow-xl shadow-amber-200 text-xs">
                    Confirm Reservation
                </button>
            </form>
        </div>
    </div>

    <div x-show="imgModal" class="fixed inset-0 z-[200] flex items-center justify-center bg-black/95 p-6" x-cloak @click="imgModal = false">
        <img :src="currentImg" class="max-w-full max-h-full rounded-3xl border border-white/10 shadow-2xl">
    </div>

    <style>[x-cloak] { display: none !important; }</style>
</body>
</html>