<?php
require_once __DIR__ . '/../config/bootstrap.php';

$error = get_flash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        log_action($pdo, (int)$user['id'], 'login');
        if ($user['role'] === 'admin') {
            redirect('/admin/index.php');
        }
        redirect('/customer/home.php');
    } else {
        flash('error', 'Invalid credentials');
        redirect('/auth/login.php');
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Grand Horizon Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .login-bg {
            background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), 
                              url('https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&q=80&w=2000');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">

<div class="flex min-h-screen">
    <div class="hidden lg:flex lg:w-3/5 login-bg items-center justify-center relative">
        <div class="relative z-10 text-center px-16 text-white">
            <h1 class="text-6xl font-serif font-bold mb-6">Grand Horizon</h1>
            <p class="text-2xl font-light italic opacity-90">"Experience luxury redefined."</p>
        </div>
    </div>

    <div class="w-full lg:w-2/5 flex flex-col justify-center items-center bg-white px-8 py-12">
        <div class="max-w-md w-full">
            <div class="mb-12">
                <h2 class="text-4xl font-bold text-gray-800 mb-3">Welcome Back</h2>
                <p class="text-gray-500">Enter your details to access your account.</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 mb-8 rounded shadow-sm">
                    <p class="font-medium"><?php echo safe_output($error); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1">Email</label>
                    <input type="email" name="email" required placeholder="your@email.com"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1">Password</label>
                    <input type="password" name="password" required placeholder="••••••••"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 outline-none">
                </div>

                <button type="submit" class="w-full bg-gray-900 hover:bg-black text-white font-bold py-4 rounded-xl shadow-lg transition duration-200">
                    Sign In
                </button>
            </form>

            <div class="mt-12 text-center border-t border-gray-100 pt-6">
                <p class="text-gray-600">
                    Don't have an account? 
                    <a href="register.php" class="text-amber-600 font-bold hover:text-amber-700 border-b-2 border-amber-600 ml-1">
                        Register here
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

</body>
</html>