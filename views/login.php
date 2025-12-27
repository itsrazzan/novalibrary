<?php session_start();
require_once __DIR__ . '/../config/helpers.php';
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Nova Academy Library</title>
    
    <!-- Google Sign-In Library -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo getAssetUrl('public/css/login.css'); ?>">
    <style>
    </style>
  </head>
  <body>
    <div class="gradient-bg relative overflow-hidden">
      <!-- Floating Shapes -->
      <div class="floating-shape shape-1"></div>
      <div class="floating-shape shape-2"></div>
      <div class="floating-shape shape-3"></div>

      <!-- Back to Home Button -->
      <div class="absolute top-6 left-6 z-10">
        <a
          href="index.html"
          class="flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-md text-white rounded-lg hover:bg-white/30 transition-all duration-300 shadow-lg"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
          </svg>
          <span class="font-medium">Kembali</span>
        </a>
      </div>

      <!-- Login Container -->
      <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div
          class="login-card w-full max-w-md rounded-3xl shadow-2xl p-8 md:p-10 fade-in"
        >
          <!-- Logo & Title -->
          <div class="text-center mb-8">
            <div
              class="w-16 h-16 gradient-purple rounded-2xl flex items-center justify-center mx-auto mb-4 logo-glow"
            >
              <svg
                class="w-9 h-9 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                />
              </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
              Selamat Datang!
            </h1>
            <p class="text-gray-600">Masuk ke akun Nova Academy Anda</p>
          </div>

          <!-- Login Form -->
          <form
            id="loginForm"
            action="../controllers/login.php"
            method="POST"
            class="space-y-6"
          >
          
            <?php if (isset($_SESSION['error'])): ?>
            <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
              <div class="flex items-center">
                <p class="text-sm text-red-700"><?= $_SESSION['error']; ?></p>
              </div>
            </div>
            <?php endif; ?>

            <!-- Username Input -->
            <div>
              <label
                for="username"
                class="block text-sm font-semibold text-gray-700 mb-2"
                >Username</label
              >
              <div class="relative">
                <div
                  class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
                >
                  <svg
                    class="w-5 h-5 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                    />
                  </svg>
                </div>
                <input
                  type="text"
                  id="username"
                  name="username"
                  placeholder="username"
                  required
                  class="input-field w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition-all"
                />
              </div>
            </div>

            <!-- Password Input -->
            <div>
              <label
                for="password"
                class="block text-sm font-semibold text-gray-700 mb-2"
                >Password</label
              >
              <div class="relative">
                <div
                  class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
                >
                  <svg
                    class="w-5 h-5 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                    />
                  </svg>
                </div>
                <input
                  type="password"
                  id="password"
                  name="password"
                  placeholder="••••••••"
                  required
                  class="input-field w-full pl-12 pr-12 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-purple-500 transition-all"
                />
                <button
                  type="button"
                  id="togglePassword"
                  class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-purple-600 transition-colors"
                >
                  <svg
                    id="eyeIcon"
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                    />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Remember & Forgot -->
            <div class="flex items-center justify-between">
              <label class="flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  id="remember"
                  class="checkbox-custom w-4 h-4 rounded border-2 border-gray-300 text-purple-600 focus:ring-purple-500 focus:ring-2 cursor-pointer"
                />
                <span class="ml-2 text-sm text-gray-700">Ingat saya</span>
              </label>
              <a
                href="#"
                class="text-sm font-semibold text-purple-600 hover:text-purple-700 transition-colors"
              >
                Lupa password?
              </a>
            </div>

            <!-- Submit Button -->
            <button
              type="submit"
              class="btn-primary w-full py-3 text-white font-semibold rounded-xl shadow-lg"
            >
              Masuk
            </button>
          </form>

          <!-- Divider -->
          <div class="relative my-8">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-4 bg-white text-gray-500">Atau masuk dengan</span>
            </div>
          </div>

          <!-- Google Sign-In -->
          <div class="flex justify-center">
            <!-- Google Sign-In Button (akan di-render oleh Google) -->
            <div id="g_id_onload"
                 data-client_id="903855622906-2qt28u76dhvk5cc6hj4gmfqa8m1q5sqt.apps.googleusercontent.com"
                 data-context="signin"
                 data-ux_mode="popup"
                 data-callback="handleGoogleSignIn"
                 data-auto_prompt="false">
            </div>
            
            <div class="g_id_signin"
                 data-type="standard"
                 data-shape="rectangular"
                 data-theme="outline"
                 data-text="signin_with"
                 data-size="large"
                 data-logo_alignment="left"
                 data-width="350">
            </div>
          </div>

          <!-- Register Link -->
          <p class="mt-8 text-center text-sm text-gray-600">
            Belum punya akun?
            <a
              href="register.php"
              class="font-semibold text-purple-600 hover:text-purple-700 transition-colors"
            >
              Daftar sekarang
            </a>
          </p>
        </div>
      </div>
    </div>

    <!-- Load JavaScript -->
    <script src="<?php echo getAssetUrl('public/js/login.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('public/js/google-auth.js'); ?>"></script>
  </body>
</html>
