<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_role(['customer', 'staff']); 

$message = get_flash('message');
$error = get_flash('error');

// 1. GET FILTER PARAMETERS
$room_type = $_GET['room_type'] ?? 'all';
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 15000;

// 2. FETCH ROOMS (Renamed from Hotels to Hotel Rooms as requested)
$query = "SELECT * FROM hotels WHERE 1=1";
$params = [];

if ($room_type !== 'all') {
    $query .= " AND name LIKE ?";
    $params[] = "%$room_type%";
}
$query .= " AND id > 0 ORDER BY name ASC"; // Simplified for demo; usually involves a price column

$rooms = $pdo->prepare($query);
$rooms->execute($params);
$room_list = $rooms->fetchAll();

// Room Photo Mapping
function getRoomDetails($name) {
    $data = [
        'Barkadahan'  => ['price' => 5000,  'img' => 'https://images.unsplash.com/photo-1555854816-802f188095e4?w=800'],
        'Family'      => ['price' => 7500,  'img' => 'https://images.unsplash.com/photo-1584132967334-10e028bd69f7?w=800'],
        'Single'      => ['price' => 2500,  'img' => 'https://images.unsplash.com/photo-1505691938895-1758d7eaa511?w=800'],
        'Double'      => ['price' => 3500,  'img' => 'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?w=800'],
        'Deluxe'      => ['price' => 12000, 'img' => 'https://images.unsplash.com/photo-1578683062331-92935c7048c0?w=800'],
    ];
    
    foreach ($data as $key => $val) {
        if (stripos($name, $key) !== false) return $val;
    }
    return ['price' => 3000, 'img' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800'];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grand Horizon | Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50" x-data="{ imgModal: false, imgUrl: '' }">

    <nav class="bg-gray-900 px-8 py-4 flex justify-between items-center sticky top-0 z-50 shadow-2xl">
        <div class="text-xl font-serif font-bold tracking-widest text-white uppercase">Grand Horizon</div>
        <a href="../auth/logout.php" class="text-amber-500 font-bold text-xs uppercase border border-amber-500/30 px-4 py-2 rounded-lg">Logout</a>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-8 flex flex-col md:flex-row gap-8">
        
        <aside class="w-full md:w-80">
            <form action="home.php" method="GET" class="bg-white rounded-2xl shadow-sm border p-6 space-y-6">
                <div>
                    <label class="text-[10px] uppercase font-black text-gray-400 mb-3 block">Room Type</label>
                    <select name="room_type" class="w-full border rounded-xl p-3 bg-gray-50 text-sm font-bold outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="all" <?= $room_type == 'all' ? 'selected' : '' ?>>All Rooms</option>
                        <option value="Single" <?= $room_type == 'Single' ? 'selected' : '' ?>>Single Room</option>
                        <option value="Double" <?= $room_type == 'Double' ? 'selected' : '' ?>>Double Room</option>
                        <option value="Family" <?= $room_type == 'Family' ? 'selected' : '' ?>>Family Room</option>
                        <option value="Barkadahan" <?= $room_type == 'Barkadahan' ? 'selected' : '' ?>>Barkadahan</option>
                        <option value="Deluxe" <?= $room_type == 'Deluxe' ? 'selected' : '' ?>>Deluxe Room</option>
                    </select>
                </div>

                <div>
                    <label class="text-[10px] uppercase font-black text-gray-400 mb-3 block">Max Price: ₱<span id="priceVal"><?= $max_price ?></span></label>
                    <input type="range" name="max_price" min="2000" max="15000" step="500" value="<?= $max_price ?>" 
                           oninput="document.getElementById('priceVal').innerText = this.value"
                           class="w-full accent-amber-600">
                </div>

                <button type="submit" class="w-full bg-gray-900 text-amber-500 font-bold py-4 rounded-xl shadow-lg uppercase text-xs tracking-widest hover:bg-black transition">
                    Apply Filters
                </button>
            </form>
        </aside>

        <div class="flex-1 space-y-6">
            <?php foreach ($room_list as $room): 
                $details = getRoomDetails($room['name']);
                if($details['price'] > $max_price) continue;
            ?>
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden flex flex-col lg:flex-row hover:shadow-xl transition duration-500">
                    <div class="lg:w-1/3 p-3 h-64 lg:h-auto cursor-zoom-in" @click="imgModal = true; imgUrl = '<?= $details['img'] ?>'">
                        <img src="<?= $details['img'] ?>" class="w-full h-full object-cover rounded-2xl hover:opacity-90 transition">
                    </div>

                    <div class="flex-1 p-8 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start">
                                <h3 class="text-2xl font-serif font-bold text-gray-900"><?= safe_output($room['name']) ?></h3>
                                <span class="text-2xl font-black text-gray-900 uppercase tracking-tighter">₱<?= number_format($details['price']) ?></span>
                            </div>
                            <p class="text-sm text-gray-500 italic mt-2 line-clamp-2"><?= safe_output($room['description']) ?></p>
                            
                            <div class="flex gap-3 mt-4">
                                <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-[10px] font-bold">Free WiFi</span>
                                <span class="bg-amber-50 text-amber-600 px-3 py-1 rounded-full text-[10px] font-bold uppercase">Breakfast Included</span>
                            </div>
                        </div>

                        <button onclick="window.location.href='booking.php?room_id=<?= $room['id'] ?>'" 
                                class="mt-6 w-full lg:w-max bg-gray-900 text-amber-500 px-10 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition shadow-xl active:scale-95">
                            Book Now
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <div x-show="imgModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-4" x-transition @click.away="imgModal = false">
        <button @click="imgModal = false" class="absolute top-8 right-8 text-white text-4xl">&times;</button>
        <img :src="imgUrl" class="max-w-full max-h-[90vh] rounded-lg shadow-2xl">
    </div>

</body>
</html>