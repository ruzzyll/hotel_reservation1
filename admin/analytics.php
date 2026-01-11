<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_role(['admin']);

// --- 1. FETCH REAL PRICES FROM DATABASE ---
// We fetch the prices from the 'room_types' table so they are always accurate.
$roomPrices = [];
try {
    $stmtPrice = $pdo->query("SELECT name, price FROM room_types");
    // This creates an array like: ['Deluxe Room' => 5000, 'Single Room' => 2000]
    $roomPrices = $stmtPrice->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    // If table doesn't exist yet, fallback to empty array
    $roomPrices = [];
}

// Fallback price if a room name isn't found in the database list
$defaultPrice = 0; 

// --- 2. HANDLE STATUS UPDATES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['reservation_id'])) {
    $newStatus = $_POST['action'] === 'approve' ? 'approved' : 'cancelled'; 
    $resId = (int)$_POST['reservation_id'];
    
    // Update the status
    $updateStmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
    $updateStmt->execute([$newStatus, $resId]);

    header("Location: analytics.php");
    exit;
}

// --- 3. FETCH AND PROCESS DATA ---

$statusCounts = ['pending' => 0, 'approved' => 0, 'cancelled' => 0];
$totalRevenue = 0;
$allReservations = [];

// Fetch ALL reservations
$stmt = $pdo->query("SELECT * FROM reservations ORDER BY created_at DESC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    // 1. Normalize Status
    $status = strtolower(trim($row['status'])); 
    if ($status == 'rejected') $status = 'cancelled'; 

    if (array_key_exists($status, $statusCounts)) {
        $statusCounts[$status]++;
    }

    // 2. Calculate Price ACCURATELY
    $checkIn = new DateTime($row['check_in']);
    $checkOut = new DateTime($row['check_out']);
    $interval = $checkIn->diff($checkOut);
    $nights = $interval->days;
    if ($nights < 1) $nights = 1; 

    // FIX: Look up the price from the DATABASE array we fetched at the top
    $roomName = $row['room_type'];
    $pricePerNight = $roomPrices[$roomName] ?? $defaultPrice; // Uses DB price
    
    $totalAmount = $pricePerNight * $nights;

    // Add calculated amount to the row data
    $row['calculated_amount'] = $totalAmount; 
    $allReservations[] = $row;

    // 3. Add to Total Revenue if Approved
    if ($status == 'approved') {
        $totalRevenue += $totalAmount;
    }
}

$total = count($allReservations);

// --- 4. PREDICTIVE DATA (Mock Data for Line Graph) ---
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul (Proj)'];
$revenueData = [15000, 25000, 20000, 35000, 42000, 50000, null]; 
$predictiveData = [null, null, null, null, null, 50000, 65000]; 
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Hotel Admin | Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar-link.active { background: rgba(255,255,255,0.1); border-left: 4px solid #3b82f6; }
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow: hidden; }
    </style>
</head>
<body class="bg-[#f4f6f9]">

<div class="flex min-h-screen">
    <aside class="w-64 bg-[#343a40] text-gray-300 flex-shrink-0 hidden md:block">
        <div class="p-4 text-white text-xl font-bold border-b border-gray-700 flex items-center gap-2">
            <i class="fas fa-hotel text-blue-400"></i> Hotel Admin
        </div>
        <nav class="mt-4">
            <a href="index.php" class="sidebar-link flex items-center px-6 py-3 hover:bg-gray-700 transition">
                <i class="fas fa-bed w-8"></i> Rooms
            </a>
            <a href="analytics.php" class="sidebar-link active flex items-center px-6 py-3 text-white">
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
            <h2 class="text-xl font-semibold text-gray-700">Analytics Overview</h2>
            <div class="flex items-center gap-2 text-gray-600">
                <i class="fas fa-user-circle text-2xl"></i>
                <span class="font-medium">Admin</span>
            </div>
        </header>

        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                
                <div class="bg-indigo-600 text-white rounded shadow-md overflow-hidden flex flex-col">
                    <div class="p-5 flex justify-between items-center">
                        <div>
                            <div class="text-3xl font-bold">₱<?php echo number_format($totalRevenue); ?></div>
                            <div class="uppercase text-xs font-semibold opacity-80">Total Revenue</div>
                        </div>
                        <i class="fas fa-coins text-5xl opacity-20"></i>
                    </div>
                    <div class="bg-black/10 py-1 text-center text-xs">Real-time DB Calculation</div>
                </div>

                <div class="bg-[#00c0ef] text-white rounded shadow-md overflow-hidden flex flex-col">
                    <div class="p-5 flex justify-between items-center">
                        <div>
                            <div class="text-4xl font-bold"><?php echo $total; ?></div>
                            <div class="uppercase text-xs font-semibold opacity-80">Reservations</div>
                        </div>
                        <i class="fas fa-clipboard-list text-5xl opacity-20"></i>
                    </div>
                    <button onclick="openModal('all')" class="bg-black/10 py-1 text-center text-xs hover:bg-black/20 transition w-full">View All <i class="fas fa-arrow-circle-right"></i></button>
                </div>

                <div class="bg-[#00a65a] text-white rounded shadow-md overflow-hidden flex flex-col">
                    <div class="p-5 flex justify-between items-center">
                        <div>
                            <div class="text-4xl font-bold"><?php echo $statusCounts['approved']; ?></div>
                            <div class="uppercase text-xs font-semibold opacity-80">Approved</div>
                        </div>
                        <i class="fas fa-check-circle text-5xl opacity-20"></i>
                    </div>
                    <button onclick="openModal('approved')" class="bg-black/10 py-1 text-center text-xs hover:bg-black/20 transition w-full">View Details <i class="fas fa-arrow-circle-right"></i></button>
                </div>

                <div class="bg-[#f39c12] text-white rounded shadow-md overflow-hidden flex flex-col">
                    <div class="p-5 flex justify-between items-center">
                        <div>
                            <div class="text-4xl font-bold"><?php echo $statusCounts['pending']; ?></div>
                            <div class="uppercase text-xs font-semibold opacity-80">Pending</div>
                        </div>
                        <i class="fas fa-clock text-5xl opacity-20"></i>
                    </div>
                    <button onclick="openModal('pending')" class="bg-black/10 py-1 text-center text-xs hover:bg-black/20 transition w-full">Take Action <i class="fas fa-arrow-circle-right"></i></button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-white rounded-lg shadow-md border-t-4 border-green-500">
                    <div class="px-6 py-4 border-b border-gray-100"><h3 class="font-bold text-gray-700 uppercase text-sm">Status Share</h3></div>
                    <div class="p-6 h-[300px]"><canvas id="pieChart"></canvas></div>
                </div>
                <div class="bg-white rounded-lg shadow-md border-t-4 border-indigo-500">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                         <h3 class="font-bold text-gray-700 uppercase text-sm">Predictive Revenue</h3>
                         <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-1 rounded">AI Model</span>
                    </div>
                    <div class="p-6 h-[300px]"><canvas id="predictiveChart"></canvas></div>
                </div>
            </div>

        </div>
    </main>
</div>

<div id="infoModal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
    <div class="modal-overlay absolute w-full h-full bg-gray-900 opacity-50" onclick="closeModal()"></div>
    <div class="modal-container bg-white w-11/12 md:max-w-5xl mx-auto rounded shadow-lg z-50 overflow-y-auto max-h-[90vh]">
        <div class="modal-content py-4 text-left px-6">
            <div class="flex justify-between items-center pb-3 border-b">
                <p class="text-2xl font-bold text-gray-800" id="modalTitle">Details</p>
                <div class="cursor-pointer z-50" onclick="closeModal()"><i class="fas fa-times text-gray-500 hover:text-red-500"></i></div>
            </div>
            <div class="my-5 overflow-x-auto">
                <table class="min-w-full bg-white text-sm">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="py-3 px-4 text-left">Customer</th>
                            <th class="py-3 px-4 text-center">Room</th>
                            <th class="py-3 px-4 text-center">Dates</th>
                            <th class="py-3 px-4 text-center">Calc. Amount</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center" id="actionHeader">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 font-light" id="modalTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Pass PHP Data to JS
const allReservations = <?php echo json_encode($allReservations); ?>;
const statusCounts = {
    pending: <?php echo $statusCounts['pending']; ?>,
    approved: <?php echo $statusCounts['approved']; ?>,
    cancelled: <?php echo $statusCounts['cancelled']; ?>
};

// --- CHARTS ---
new Chart(document.getElementById('pieChart'), {
    type: 'pie',
    data: {
        labels: ['Pending', 'Approved', 'Cancelled'],
        datasets: [{
            data: [statusCounts.pending, statusCounts.approved, statusCounts.cancelled],
            backgroundColor: ['#f39c12', '#00a65a', '#dd4b39']
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('predictiveChart'), {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul (Proj)'],
        datasets: [
            { label: 'Revenue', data: [15000, 25000, 20000, 35000, 42000, 50000, null], borderColor: '#3b82f6', fill: true },
            { label: 'Prediction', data: [null, null, null, null, null, 50000, 65000], borderColor: '#9333ea', borderDash: [5, 5], pointStyle: 'star' }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

// --- MODAL LOGIC ---
function openModal(filter) {
    document.getElementById('infoModal').classList.remove('opacity-0', 'pointer-events-none');
    document.body.classList.add('modal-active');
    
    document.getElementById('modalTitle').innerText = filter.charAt(0).toUpperCase() + filter.slice(1) + ' List';
    document.getElementById('actionHeader').style.display = (filter === 'pending') ? '' : 'none';

    const tbody = document.getElementById('modalTableBody');
    tbody.innerHTML = '';

    const data = allReservations.filter(r => {
        let s = r.status.toLowerCase().trim();
        if (s === 'rejected') s = 'cancelled'; 
        return filter === 'all' ? true : s === filter;
    });

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">No records found.</td></tr>';
        return;
    }

    data.forEach(res => {
        let statusColor = 'bg-gray-200 text-gray-600';
        let displayStatus = res.status;
        
        if (res.status.toLowerCase() == 'approved') statusColor = 'bg-green-200 text-green-800';
        if (res.status.toLowerCase() == 'pending') statusColor = 'bg-yellow-200 text-yellow-800';
        if (res.status.toLowerCase() == 'rejected' || res.status.toLowerCase() == 'cancelled') {
            statusColor = 'bg-red-200 text-red-800';
            displayStatus = 'Cancelled';
        }

        let actions = '<span class="text-xs italic">Read Only</span>';
        if (filter === 'pending') {
            actions = `
                <div class="flex justify-center gap-2">
                    <form method="POST" onsubmit="return confirm('Approve?');">
                        <input type="hidden" name="reservation_id" value="${res.id}">
                        <input type="hidden" name="action" value="approve">
                        <button class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600"><i class="fas fa-check"></i></button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Cancel?');">
                        <input type="hidden" name="reservation_id" value="${res.id}">
                        <input type="hidden" name="action" value="reject">
                        <button class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600"><i class="fas fa-times"></i></button>
                    </form>
                </div>`;
        }

        const row = `
            <tr class="border-b hover:bg-gray-50">
                <td class="py-3 px-4">
                    <div class="font-bold">${res.customer_email || 'User #' + res.user_id}</div>
                    <div class="text-xs text-gray-500">${res.phone || res.contact_number || ''}</div>
                </td>
                <td class="py-3 px-4 text-center text-sm">${res.room_type}</td>
                <td class="py-3 px-4 text-center text-xs">
                    <div>In: ${res.check_in}</div>
                    <div>Out: ${res.check_out}</div>
                </td>
                <td class="py-3 px-4 text-center font-bold text-gray-700">₱${Number(res.calculated_amount).toLocaleString()}</td>
                <td class="py-3 px-4 text-center"><span class="${statusColor} py-1 px-3 rounded-full text-xs font-bold uppercase">${displayStatus}</span></td>
                <td class="py-3 px-4 text-center" style="${filter !== 'pending' ? 'display:none' : ''}">${actions}</td>
            </tr>`;
        tbody.innerHTML += row;
    });
}

function closeModal() {
    document.getElementById('infoModal').classList.add('opacity-0', 'pointer-events-none');
    document.body.classList.remove('modal-active');
}
</script>
</body>
</html>