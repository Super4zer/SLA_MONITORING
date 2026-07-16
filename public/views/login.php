<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - SLA Monitoring Command Center</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="/css/login.css">

</head>

<body>
    <div class="login-container">
        <!-- Sisi Kiri (Branding) -->
        <div class="login-branding">
            <!-- Logo di pojok kiri atas -->
            <img src="/images/DSI.png" alt="DSI Logo" class="brand-logo" width="500px" height="auto" />

            <!-- Foto Dummy Vertikal -->
            <div class="dummy-photo-wrapper">
                <!-- Menggunakan placeholder gambar teknologi bernuansa server/data -->
                <img src="https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=600&auto=format&fit=crop"
                    alt="Server Operations" />
            </div>

            <!-- Teks Informasi -->
            <div class="branding-text">
                <h2>Command Center</h2>
                <p>
                    Sistem pemantauan SLA dan manajemen tiket terintegrasi. Akses panel
                    utama agen.
                </p>
            </div>
        </div>

        <!-- Sisi Kanan (Form) -->
        <div class="login-form-wrapper">
            <div class="form-header">
                <h3>Selamat Datang</h3>
                <p>Masukkan kredensial Anda untuk melanjutkan.</p>
            </div>

            <form id="loginForm">
                <div class=" mb-4">
                    <label class="form-label">Email atau Username</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <span class="material-symbols-outlined fs-5">person</span>
                        </span>
                        <input type="text" class="form-control" id="username" placeholder="admin@dsi.com" required />
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Password</label>
                        <a href="#" class="forgot-password">Lupa Password?</a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text">
                            <span class="material-symbols-outlined fs-5">lock</span>
                        </span>
                        <input type="password" class="form-control" id="password" placeholder="••••••••" required />
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="rememberMe" />
                    <label class="form-check-label" for="rememberMe">
                        Ingat saya di perangkat ini
                    </label>
                </div>

                <button type="submit" class="btn btn-login d-flex justify-content-center align-items-center gap-2">
                    Masuk
                    <span class="material-symbols-outlined fs-5">arrow_forward</span>
                </button>
            </form>
        </div>
    </div>
    <script src="/js/api.js"></script>
    <script src="/js/login.js"></script>
</body>

</html>