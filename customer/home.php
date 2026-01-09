<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_role(['customer', 'staff']); 

$room_details = [
    'Single Room' => [
        'price' => 2200, 'beds' => '1 Single Bed', 'guests' => '1 Guest',
        'gallery' => [
            'https://images.unsplash.com/photo-1505691938895-1758d7eaa511?w=800',
            'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800',
            'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?w=800'
        ]
    ],
    'Double Room' => [
        'price' => 3500, 'beds' => '2 Double Beds', 'guests' => '2 Guests',
        'gallery' => [
            'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?w=800',
            'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=800',
            'https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=800'
        ]
    ],
    'Deluxe Room' => [
        'price' => 5500, 'beds' => '1 Royal King Bed', 'guests' => '2 Guests',
        'gallery' => [
            'https://images.unsplash.com/photo-1578683062331-92935c7048c0?w=800',
            'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=800',
            'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800'
        ]
    ],
    'Family Room' => [
        'price' => 8500, 'beds' => '2 Queen Beds', 'guests' => '4-6 Guests',
        'gallery' => [
            'https://images.unsplash.com/photo-1584132967334-10e028bd69f7?w=800',
            'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800',
            'https://images.unsplash.com/photo-1544124499-58912cbddaad?w=800'
        ]
    ],
    'Barkadahan Room' => [
        'price' => 12000, 'beds' => '4 Bunk Beds (10 Pax)', 'guests' => '8-10 Guests',
        'gallery' => [
            'https://images.unsplash.com/photo-1555854816-802f188095e4?w=800',
            'https://images.unsplash.com/photo-1520277739336-7bf67edfa768?w=800',
            'https://images.unsplash.com/photo-1507652313519-d4e9174996dd?w=800'
        ]
    ]
];

$stmt = $pdo->query("SELECT * FROM hotels ORDER BY id ASC");
$rooms = $stmt->fetchAll();
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
<body class="bg-[#f8fafc]" x-data="{ bookingModal: false, imgModal: false, selectedRoom: '', currentImg: '' }">

    <nav class="bg-[#1a1c2e] px-10 py-5 flex justify-between items-center sticky top-0 z-50 shadow-xl">
        <div class="flex items-center gap-3">
            <div class="bg-amber-500 p-2 rounded text-white"><i class="fas fa-hotel"></i></div>
            <h1 class="text-white font-serif font-bold tracking-widest text-xl uppercase">Grand Horizon</h1>
        </div>
        <div class="flex items-center gap-6">
            <a href="reservation.php" class="text-amber-500 font-bold text-xs uppercase tracking-widest">My Bookings</a>
            <a href="../auth/logout.php" class="text-gray-400 font-bold text-xs uppercase hover:text-white transition">Logout</a>
        </div>
    </nav>

    <section class="relative h-[450px] overflow-hidden bg-black" x-data="{ active: 0, slides: [
        'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=1200',
        'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=1200',
        'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?w=1200'
    ] }" x-init="setInterval(() => { active = (active + 1) % slides.length }, 5000)">
        <template x-for="(slide, index) in slides" :key="index">
            <div x-show="active === index" x-transition.opacity.duration.1000ms class="absolute inset-0">
                <img :src="slide" class="w-full h-full object-cover opacity-60">
            </div>
        </template>
        <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-4">
            <h1 class="text-white text-5xl md:text-7xl font-serif font-bold mb-6">Explore Our Rooms</h1>
            <div class="w-32 h-1 bg-amber-500 rounded-full shadow-lg"></div>
        </div>
    </section>

    <main class="max-w-6xl mx-auto px-6 py-20 space-y-16">
        <?php foreach ($rooms as $room): 
            $meta = $room_details[$room['name']] ?? null;
            if (!$meta) continue; 
        ?>
            <div class="bg-white rounded-[3rem] p-6 shadow-xl border border-gray-100 flex flex-col lg:flex-row gap-10 group">
                <div class="lg:w-1/2 grid grid-cols-2 gap-3 h-[350px]">
                    <div class="row-span-2 cursor-pointer overflow-hidden rounded-[2.5rem]" @click="imgModal = true; currentImg = '<?= $meta['gallery'][0] ?>'">
                        <img src="<?= $meta['gallery'][0] ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                    </div>
                    <div class="cursor-pointer overflow-hidden rounded-3xl" @click="imgModal = true; currentImg = '<?= $meta['gallery'][1] ?>'">
                        <img src="<?= $meta['gallery'][1] ?>" class="w-full h-full object-cover">
                    </div>
                    <div class="cursor-pointer overflow-hidden rounded-3xl" @click="imgModal = true; currentImg = '<?= $meta['gallery'][2] ?>'">
                        <img src="<?= $meta['gallery'][2] ?>" class="w-full h-full object-cover">
                    </div>
                </div>

                <div class="flex-1 py-6 flex flex-col justify-between">
                    <div>
                        <h3 class="text-3xl font-serif font-bold text-gray-900 mb-4"><?= $room['name'] ?></h3>
                        <div class="flex gap-3 mb-6">
                            <span class="bg-amber-50 text-amber-600 px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border border-amber-100"><?= $meta['beds'] ?></span>
                            <span class="bg-blue-50 text-blue-600 px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border border-blue-100"><?= $meta['guests'] ?></span>
                        </div>
                        <p class="text-gray-500 text-sm italic border-l-4 border-amber-500 pl-4"><?= $room['description'] ?></p>
                    </div>

                    <div class="mt-8">
                        <div class="mb-4">
                            <span class="text-3xl font-black text-gray-900">₱<?= number_format($meta['price']) ?></span>
                            <span class="text-xs text-gray-400 font-bold uppercase tracking-widest">/ Night</span>
                        </div>
                        <button @click="bookingModal = true; selectedRoom = '<?= $room['name'] ?>'" 
                                class="w-full bg-[#1a1c2e] text-amber-500 py-5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-black transition-all shadow-xl">
                            Reserve Room
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </main>

    <div x-show="bookingModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md" x-transition x-cloak>
        <div class="bg-white w-full max-w-lg rounded-[3rem] overflow-hidden shadow-2xl" @click.away="bookingModal = false">
            <div class="bg-[#1a1c2e] p-8 text-center text-white">
                <h2 class="text-amber-500 font-black text-xs uppercase tracking-widest mb-2">Grand Horizon Signature</h2>
                <h3 class="text-2xl font-serif font-bold" x-text="selectedRoom"></h3>
            </div>
            
            <form action="reservation.php" method="POST" class="p-10 space-y-5">
                <input type="hidden" name="room_name" :value="selectedRoom">
                
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-2">Customer Email</label>
                    <input type="email" name="customer_email" required class="w-full p-4 bg-gray-50 border rounded-2xl outline-none focus:ring-2 focus:ring-amber-500">
                </div>

                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-2">Contact Phone</label>
                    <input type="text" name="phone" required class="w-full p-4 bg-gray-50 border rounded-2xl outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-2">Check-in</label>
                        <input type="date" name="check_in" required class="w-full p-4 bg-gray-50 border rounded-2xl outline-none">
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-2">Check-out</label>
                        <input type="date" name="check_out" required class="w-full p-4 bg-gray-50 border rounded-2xl outline-none">
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-amber-500 text-white font-black py-5 rounded-2xl uppercase tracking-widest hover:bg-amber-600 transition shadow-xl">
                        Confirm Reservation
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="imgModal" class="fixed inset-0 z-[200] flex items-center justify-center bg-black/95 p-4" x-transition x-cloak @click="imgModal = false">
        <img :src="currentImg" class="max-w-full max-h-full rounded-2xl border-2 border-white/20 shadow-2xl">
    </div>

    <style>[x-cloak] { display: none !important; }</style>
</body>
</html>