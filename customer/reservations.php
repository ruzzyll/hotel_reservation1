<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_role(['customer']);

$message = get_flash('message');
$error = get_flash('error');

$stmt = $pdo->prepare(
    "SELECT r.id, r.reservation_time, r.created_at, h.name AS hotel_name, s.name AS status_name
    FROM reservations r
    JOIN hotels h ON r.hotel_id = h.id
    JOIN reservation_status s ON r.status_id = s.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC"
);
$stmt->execute([current_user()['id']]);
$reservations = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
    <title>My Bookings</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-6 md:p-12">
    <div class="max-w-4xl mx-auto">
        <?php if ($message): ?><div class="bg-green-100 text-green-700 p-5 rounded-3xl mb-6 border border-green-200"><?= $message ?></div><?php endif; ?>
        
        <div class="flex justify-between items-center mb-10">
            <h2 class="text-3xl font-bold text-gray-900">Your Reservations</h2>
            <a href="home.php" class="bg-[#1a1c2e] text-amber-500 px-6 py-3 rounded-2xl font-bold text-xs uppercase">Back to Gallery</a>
        </div>

        <div class="grid gap-4">
            <?php if(empty($reservations)): ?>
                <p class="text-gray-400 italic text-center py-10">No bookings yet.</p>
            <?php else: ?>
                <?php foreach ($reservations as $r): ?>
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 flex justify-between items-center">
                        <div>
                            <h4 class="text-xl font-bold text-gray-800"><?= $r['hotel_name'] ?></h4>
                            <p class="text-sm text-gray-400 mt-1">Date: <?= date('F j, Y', strtotime($r['reservation_time'])) ?></p>
                        </div>
                        <span class="px-5 py-2 rounded-full text-[10px] font-black uppercase tracking-widest bg-amber-100 text-amber-700"><?= $r['status_name'] ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>