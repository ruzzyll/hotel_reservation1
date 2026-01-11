<?php
require_once __DIR__ . '/../config/bootstrap.php';

$error = get_flash('error');
$success = get_flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'staff';

    if (!in_array($role, ['admin', 'staff'], true)) {
        flash('error', 'Invalid role selected.');
        redirect('register.php');
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        flash('error', 'Email already registered.');
        redirect('register.php');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")
        ->execute([$name, $email, $hash, $role]);
    $userId = (int)$pdo->lastInsertId();
    log_action($pdo, $userId, "registered as {$role}");
    flash('success', 'Account created, you can now login.');
    redirect('login.php');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Grand Horizon Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .register-bg {
            background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), 
                              url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&q=80&w=2000');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">

<div class="flex min-h-screen">
    <div class="hidden lg:flex lg:w-3/5 register-bg items-center justify-center relative">
        <div class="relative z-10 text-center px-16 text-white">
            <h1 class="text-6xl font-serif font-bold mb-6">Join Our World</h1>
            <p class="text-2xl font-light italic opacity-90">"Start your journey to unparalleled comfort today."</p>
        </div>
    </div>

    <div class="w-full lg:w-2/5 flex flex-col justify-center items-center bg-white px-8 py-12">
        <div class="max-w-md w-full">
            <div class="mb-10">
                <h2 class="text-4xl font-bold text-gray-800 mb-2">Create Account</h2>
                <p class="text-gray-500">Please provide your details to register.</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 mb-6 rounded shadow-sm">
                    <p class="font-medium"><?php echo safe_output($error); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 text-green-800 p-4 mb-6 rounded shadow-sm">
                    <p class="font-medium"><?php echo safe_output($success); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1">Full Name</label>
                    <input type="text" name="name" required placeholder="John Doe"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1">Email Address</label>
                    <input type="email" name="email" required placeholder="john@example.com"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1">Password</label>
                    <input type="password" name="password" required minlength="6" placeholder="••••••••"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1">I am a:</label>
                    <select name="role" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 outline-none bg-white">
                        <option value="staff">Customer</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>

                <button type="submit" class="w-full bg-gray-900 hover:bg-black text-white font-bold py-4 rounded-xl shadow-lg transform transition active:scale-[0.98] mt-4">
                    Register Now
                </button>
            </form>

            <div class="mt-10 text-center border-t border-gray-100 pt-6">
                <p class="text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="text-amber-600 font-bold hover:text-amber-700 border-b-2 border-amber-600 ml-1">
                        Back to login
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

</body>
</html>