<?php
require_once __DIR__ . '/config/bootstrap.php';

// If already logged in, send them to the appropriate dashboard
if (is_logged_in()) {
    $role = current_user()['role'];
    
    // 1. Admins go to the admin panel
    if ($role === 'admin') {
        redirect('/admin/index.php');
    }
    
    // 2. IMPORTANT: Both 'staff' and 'customer' roles go to your NEW LUXURY DESIGN
    // We point them to home.php inside the customer folder
    if ($role === 'staff' || $role === 'customer') {
        redirect('/customer/home.php'); 
    }

    // 3. Fallback for any other logic
    redirect('/auth/login.php');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grand Horizon Hotel | Welcome</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        /* Smooth transitions for slider */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased" x-data="slider()" x-init="init()" x-cloak>

    <!-- Navigation with Backdrop Blur -->
    <nav class="fixed top-0 w-full z-50 flex justify-between items-center px-8 py-6 text-white backdrop-blur-md bg-black/20">
        <div class="text-2xl font-serif font-bold tracking-widest">GRAND HORIZON</div>
        <div class="space-x-8 text-sm font-semibold uppercase tracking-widest">
            <a href="auth/login.php" class="hover:text-amber-400 transition">Login</a>
            <a href="auth/register.php" class="bg-gray-900 hover:bg-black px-6 py-3 rounded-full transition shadow-lg">Book Now</a>
        </div>
    </nav>

    <!-- Hero Section with 5-Slide Professional Slider -->
    <div class="relative h-screen overflow-hidden">
        <!-- Slide Container -->
        <div class="absolute inset-0">
            <template x-for="(slide, index) in slides" :key="index">
                <div 
                    x-show="currentSlide === index"
                    x-transition:enter="transition ease-out duration-800"
                    x-transition:enter-start="opacity-0 scale-110"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-800"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-90"
                    class="absolute inset-0"
                >
                    <div 
                        class="absolute inset-0 bg-cover bg-center bg-no-repeat transition-transform duration-[800ms] ease-out"
                        :class="currentSlide === index ? 'scale-100' : 'scale-110'"
                        :style="`background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('${slide.image}')`"
                    ></div>
                    <div class="absolute inset-0 flex items-center justify-center text-center text-white px-4">
                        <div class="max-w-4xl">
                            <div class="backdrop-blur-sm bg-black/30 rounded-[2rem] px-8 py-6 mb-6 inline-block">
                                <h2 class="text-2xl md:text-3xl font-serif font-bold" x-text="slide.title"></h2>
                            </div>
                            <h1 class="text-5xl md:text-7xl font-serif font-bold mb-6">A Sanctuary of Modern Luxury</h1>
                            <p class="text-xl md:text-2xl font-light mb-10 opacity-90 leading-relaxed">
                                Discover an oasis of tranquility in the heart of the city. <br class="hidden md:block"> 
                                Exquisite rooms, world-class dining, and memories that last a lifetime.
                            </p>
                            <div class="flex flex-col md:flex-row justify-center gap-4">
                                <a href="auth/register.php" class="bg-gray-900 hover:bg-black text-white px-10 py-4 rounded-xl font-bold text-lg transition shadow-2xl">
                                    Create an Account
                                </a>
                                <a href="auth/login.php" class="border-2 border-white text-white px-10 py-4 rounded-xl font-bold text-lg hover:bg-white/10 transition">
                                    Member Login
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Slide Indicators -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 flex gap-3 z-40">
            <template x-for="(slide, index) in slides" :key="index">
                <button 
                    @click="goToSlide(index)"
                    class="w-3 h-3 rounded-full transition-all duration-300"
                    :class="currentSlide === index ? 'bg-white w-8' : 'bg-white/50 hover:bg-white/75'"
                ></button>
            </template>
        </div>

        <!-- Navigation Arrows -->
        <button 
            @click="previousSlide()"
            class="absolute left-8 top-1/2 transform -translate-y-1/2 z-40 bg-black/30 hover:bg-black/50 backdrop-blur-sm text-white p-4 rounded-full transition-all duration-300"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </button>
        <button 
            @click="nextSlide()"
            class="absolute right-8 top-1/2 transform -translate-y-1/2 z-40 bg-black/30 hover:bg-black/50 backdrop-blur-sm text-white p-4 rounded-full transition-all duration-300"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </button>
    </div>

    <!-- Features Section with Premium UI -->
    <div class="py-24 bg-gray-50 text-center px-8">
        <div class="backdrop-blur-sm bg-white/50 rounded-[2rem] px-8 py-4 mb-8 inline-block">
            <h2 class="text-amber-600 font-bold uppercase tracking-widest mb-2">The Experience</h2>
        </div>
        <h3 class="text-4xl font-serif text-gray-900 mb-16">Why Stay With Us?</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
            <div class="bg-white p-10 rounded-[2rem] shadow-lg hover:shadow-xl transition-shadow duration-300">
                <div class="text-amber-600 text-4xl mb-4">✦</div>
                <h4 class="text-xl font-bold mb-3 text-gray-900">Royal Suites</h4>
                <p class="text-gray-600 leading-relaxed">Experience comfort like never before in our signature royal-themed suites.</p>
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <span class="text-2xl font-bold text-gray-900">₱3,000</span>
                    <span class="text-sm text-gray-500 ml-2">per night</span>
                </div>
            </div>
            <div class="bg-white p-10 rounded-[2rem] shadow-lg hover:shadow-xl transition-shadow duration-300">
                <div class="text-amber-600 text-4xl mb-4">✦</div>
                <h4 class="text-xl font-bold mb-3 text-gray-900">24/7 Concierge</h4>
                <p class="text-gray-600 leading-relaxed">Our world-class staff is dedicated to making your stay seamless and personalized.</p>
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <span class="text-2xl font-bold text-gray-900">Included</span>
                    <span class="text-sm text-gray-500 ml-2">with every stay</span>
                </div>
            </div>
            <div class="bg-white p-10 rounded-[2rem] shadow-lg hover:shadow-xl transition-shadow duration-300">
                <div class="text-amber-600 text-4xl mb-4">✦</div>
                <h4 class="text-xl font-bold mb-3 text-gray-900">Sky Pool</h4>
                <p class="text-gray-600 leading-relaxed">Breathtaking views of the skyline from our temperature-controlled rooftop pool.</p>
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <span class="text-2xl font-bold text-gray-900">Free</span>
                    <span class="text-sm text-gray-500 ml-2">for all guests</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        function slider() {
            return {
                currentSlide: 0,
                slides: [
                    {
                        title: 'Grand Entrance',
                        image: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&q=80&w=2000',
                        type: 'hall'
                    },
                    {
                        title: 'Fine Dining Restaurant',
                        image: 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&q=80&w=2000',
                        type: 'hall'
                    },
                    {
                        title: 'Master Bedroom Suite',
                        image: 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&q=80&w=2000',
                        type: 'suite'
                    },
                    {
                        title: 'Conference Room',
                        image: 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80&w=2000',
                        type: 'hall'
                    },
                    {
                        title: 'Elegant Lobby',
                        image: 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&q=80&w=2000',
                        type: 'hall'
                    }
                ],
                init() {
                    // Auto-advance slides every 5 seconds
                    setInterval(() => {
                        this.nextSlide();
                    }, 5000);
                },
                nextSlide() {
                    this.currentSlide = (this.currentSlide + 1) % this.slides.length;
                },
                previousSlide() {
                    this.currentSlide = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
                },
                goToSlide(index) {
                    this.currentSlide = index;
                },
                getRoomPhoto(type) {
                    // Better logic to distinguish between Hall (Lobby/Public space) and Suite (Private Bedroom)
                    if (type === 'suite') {
                        return 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&q=80&w=2000';
                    } else {
                        // Hall/Lobby/Public spaces
                        return 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&q=80&w=2000';
                    }
                }
            }
        }
    </script>

</body>
</html>