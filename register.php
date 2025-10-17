<?php 
require __DIR__ . '/config_mysqli.php'; 
require __DIR__ . '/csrf.php'; 
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; display:flex; align-items:center; }
        .login-card { max-width: 420px; width: 100%; }
    </style>
</head>
<body class="bg-light">
    <main class="container d-flex justify-content-center">
        <div class="card shadow-sm login-card p-3 p-md-4">
            <div class="card-body">
                <h1 class="h4 mb-3 text-center">Create Account</h1>

                <?php if (!empty($_SESSION['flash'])): ?>
                    <div class="alert alert-danger py-2"><?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
                <?php endif; ?>

                <form method="post" action="register_process.php" novalidate>
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label" for="name">Full Name</label>
                        <input class="form-control" type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" type="email" id="email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control" type="password" id="password" name="password" required>
                        <div class="form-text small">At least 8 characters, with uppercase, lowercase, and numbers.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password_confirm">Confirm Password</label>
                        <input class="form-control" type="password" id="password_confirm" name="password_confirm" required>
                    </div>

                    <div class="d-grid mt-3">
                        <button class="btn btn-primary" type="submit">Sign up</button>
                    </div>
                </form>

                <p class="text-center text-muted mt-3 mb-0 small">
                    Already have an account? <a href="login.php">Sign in</a>
                </p>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>