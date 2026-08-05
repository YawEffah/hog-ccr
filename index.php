<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Adom Fie CCR | House of Grace</title>
    <meta name="description" content="Welcome to the Adom Fie CCR Community. A place of worship, community, and spiritual growth.">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        :root {
            --gold: #1E40AF;
            --gold-light: #6D28D9;
            --gold-pale: #EEF2FF;
            --deep: #111827;
            --deep2: #111827;
            --muted: #64748B;
            --mid: #475569;
            --white: #FFFFFF;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.85), rgba(109, 40, 217, 0.85)), url('assets/images/back.jpg') no-repeat center center fixed;
            background-size: cover;
            color: var(--white);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* Navbar */
        nav {
            padding: 24px 48px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(17, 24, 39, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .brand {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--white);
        }
        
        .brand img {
            width: 40px;
            height: 40px;
        }

        /* Hero Section */
        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px 20px;
        }

        .hero-container {
            max-width: 800px;
            animation: slideUp 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 100px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 14px;
            font-weight: 500;
            color: var(--white);
            margin-bottom: 24px;
            letter-spacing: 0.5px;
        }

        h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(48px, 8vw, 84px);
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 24px;
        }

        p.subtitle {
            font-size: clamp(18px, 2vw, 22px);
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
            margin-bottom: 48px;
            max-width: 600px;
            margin-inline: auto;
        }

        .cta-group {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'DM Sans', sans-serif;
        }

        .btn-primary {
            background: rgba(255, 255, 255, 0.98);
            color: var(--gold);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 1);
        }

        .btn-primary:hover {
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 15px 40px -10px rgba(0, 0, 0, 0.7);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Feature Cards (Matching Login Card Aesthetic) */
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            max-width: 1000px;
            margin: 80px auto 0;
            width: 100%;
            padding: 0 24px;
            opacity: 0;
            animation: slideUp 1s 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            padding: 40px 32px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            backdrop-filter: blur(10px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
            color: var(--deep2);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.5);
        }

        .feature-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: var(--gold-pale);
            color: var(--gold);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 24px;
        }

        .feature-card h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--deep2);
        }

        .feature-card p {
            color: var(--muted);
            line-height: 1.6;
            font-size: 15px;
        }

        /* Footer */
        footer {
            padding: 32px 48px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 80px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            background: rgba(17, 24, 39, 0.4);
            backdrop-filter: blur(10px);
        }

        .admin-link {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s ease;
        }

        .admin-link:hover {
            color: var(--white);
        }

        @media (max-width: 768px) {
            nav { padding: 20px; }
            footer {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <nav>
        <div class="brand">
            <img src="assets/images/logo.png" alt="Logo">
            Adom Fie CCR
        </div>
    </nav>

    <main>
        <div class="hero-container">
            <div class="badge">Welcome Home</div>
            <h1>Adom Fie CCR Community</h1>
            <p class="subtitle">Join a vibrant community dedicated to spiritual growth, fellowship, and serving others. Experience faith in action.</p>
            
            <div class="cta-group">
                <a href="#" class="btn btn-primary">
                    Join a Ministry
                    <i class="ph-bold ph-arrow-right"></i>
                </a>
                <a href="#" class="btn btn-secondary">
                    Service Times
                </a>
            </div>
        </div>

        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="ph-fill ph-users-three"></i>
                </div>
                <h3>Vibrant Community</h3>
                <p>Connect with like-minded believers in our various ministries and small groups designed for all ages.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="ph-fill ph-hands-praying"></i>
                </div>
                <h3>Spiritual Growth</h3>
                <p>Deepen your faith through powerful worship sessions, Bible studies, and dedicated prayer meetings.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="ph-fill ph-heart"></i>
                </div>
                <h3>Welfare & Support</h3>
                <p>We believe in taking care of our own. Our robust welfare fund ensures no member is left behind in times of need.</p>
            </div>
        </div>
    </main>

    <footer>
        <div>&copy; <?php echo date('Y'); ?> Adom Fie CCR Community. All rights reserved.</div>
        <a href="login.php" class="admin-link">
            <i class="ph ph-lock-key"></i>
            Admin Portal
        </a>
    </footer>

</body>
</html>
