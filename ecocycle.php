
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Type Selection</title>
    <link rel="stylesheet" href="signlogstyle.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a5f7a 0%, #2c786c 50%, #28bf4b 100%);
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        header {
            background: #fff;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo img {
            margin-right: 0.5rem;
        }
        .help {
            font-size: 0.9rem;
            color: #999;
        }
        main {
            display: flex;
            min-height: calc(100vh - 70px);
            flex-wrap: wrap;
        }
        .split {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            min-width: 300px;
        }
        .left {
            color: #fff;
            text-align: center;
            padding: 2rem;
        }
        .brand-logo img {
            width: 256px;
            height: 128px;
            margin-bottom: 1rem;
        }
        .right {
            padding: 2rem 0;
        }
        .card {
            background: #fff;
            padding: 2rem 1.5rem 1.5rem 1.5rem;
            width: 100%;
            max-width: 420px; /* Increased width */
            border-radius: 18px;
            box-shadow: 0 6px 32px rgba(44,120,108,0.10);
            text-align: center;
            margin: 0 auto;
            position: relative;
            transition: box-shadow 0.2s cubic-bezier(0.4,0,0.2,1), transform 0.2s cubic-bezier(0.4,0,0.2,1);
        }
        .card:hover {
            box-shadow: 0 12px 32px rgba(44,120,108,0.15), 0 2px 8px rgba(0,0,0,0.10);
            transform: translateY(-6px) scale(1.03);
        }
        .card h2 {
            margin-bottom: 1rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: #2c3e50;
            text-align: center;
        }
        .btn-group {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
            gap: 16px;
        }
        .btn {
            flex: 1;
            padding: 12px 0;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            font-weight: 600;
        }
        .btn-individual {
            background: linear-gradient(135deg, #1a5f7a 0%, #28bf4b 100%);
            color: #fff;
        }
        .btn-individual:hover {
            background: linear-gradient(135deg, #28bf4b 0%, #1a5f7a 100%);
        }
        .btn-group-btn {
            background: linear-gradient(135deg, #2196F3 0%, #1565C0 100%);
            color: #fff;
        }
        .btn-group-btn:hover {
            background: linear-gradient(135deg, #1565C0 0%, #2196F3 100%);
        }
        .footer-text {
            font-size: 0.65rem;
            color: #000;
            margin-top: 0;
            line-height: 1;
        }
        .footer-text a {
            color: #000;
            font-weight: 500;
        }
        @media (max-width: 768px) {
            .split { flex: 100%; }
            .left { padding: 1rem; display: none !important; }
            .card { padding: 1.5rem 1.2rem; }
            .brand-logo img { display: none !important; }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-content">
            <div class="logo">
                <img src="images/logo.png.png" alt="Ecocycle logo" width="100" height="40">
            </div>
            <a href="#" class="help">Need help?</a>
        </div>
    </header>

    <main class="container">
        <div class="split left">
            <div class="brand">
                <div class="brand-logo">
                    <img src="images/Ecocycle NLUC bee.png" alt="Ecocycle logo" style="border-radius: 50%; object-fit: cover; width: 320px; height: 320px;">
                </div>
                <div class="brand-tagline" style="margin-top: 1rem; font-size: 2rem; font-weight: 400; color: #fff; text-align: center; line-height: 1.4; font-family: Poppins, sans-serif; font-style: italic;">
                    "Nurturing a lasting unified conservation"
                </div>
            </div>
        </div>

        <div class="split right">
            <div class="card">
                <h2>Are you an Individual or a Group?</h2>
                <div class="btn-group">
                    <button class="btn btn-individual" onclick="location.href='signup.php'">Individual</button>
                    <button class="btn btn-group-btn" onclick="location.href='orgsignup.php'">Group</button>
                </div>
                <p class="footer-text" style="margin-top:24px;">
                    <span style="font-size:1rem;">Already have an account? <a href="login.php">Log In</a></span>
                </p>
            </div>
        </div>
    </main>
</body>
</html>
