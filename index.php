<?php
require_once 'includes/db.php';
try {
    $db = getDB();
    $ministries = $db->query("SELECT * FROM ministries ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {
    $ministries = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Adom Fie CCR | House of Grace</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        :root {
            --bg: #F5F7F8;
            --white: #FFFFFF;
            --black: #111111;
            --gold: #1E40AF;
            --gold-light: #6D28D9;
            --gold-pale: #EEF2FF;
            --gray-light: #E9ECEF;
            --gray: #6C757D;
            --radius-lg: 32px;
            --radius-md: 24px;
            --radius-sm: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            color: var(--black);
            min-height: 100vh;
            padding: 20px;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 20px 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        }

        /* Navbar */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0 30px;
        }

        .brand {
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--black);
            text-decoration: none;
        }

        .brand img {
            width: 32px;
            height: 32px;
        }

        .nav-links {
            display: flex;
            gap: 32px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--gray);
            font-weight: 500;
            font-size: 15px;
            transition: color 0.2s;
            position: relative;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: var(--gold);
        }

        .nav-links a.active::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 50%;
            transform: translateX(-50%);
            width: 12px;
            height: 2px;
            background: var(--gold);
            border-radius: 2px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: var(--white);
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 100px;
            font-weight: 500;
            font-size: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.3);
        }

        /* Hero Area */
        .hero {
            position: relative;
            background: linear-gradient(to right, rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.1)), url('assets/images/back.jpg') center/cover;
            height: 500px;
            border-radius: var(--radius-lg);
            padding: 60px;
            color: var(--white);
            margin-bottom: 24px;
            display: flex;
            flex-direction: column;
        }

        .hero h1 {
            font-size: 48px;
            font-weight: 600;
            max-width: 500px;
            line-height: 1.2;
            margin-bottom: 16px;
        }

        .hero p {
            font-size: 18px;
            max-width: 400px;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.5;
        }

        /* Floating CTA Box */
        .floating-cta {
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--white);
            border-radius: var(--radius-md);
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            width: 90%;
            max-width: 800px;
            color: var(--black);
        }

        .cta-col {
            flex: 1;
        }

        .cta-col span {
            display: block;
            font-size: 13px;
            color: var(--gray);
            font-weight: 500;
            margin-bottom: 4px;
        }

        .cta-col strong {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            color: var(--gold);
        }

        .cta-btn {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: var(--white);
            border: none;
            padding: 16px 32px;
            border-radius: var(--radius-sm);
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .cta-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.3);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1.2fr;
            gap: 24px;
            margin-top: 60px;
            margin-bottom: 80px;
        }

        .stat-card {
            border-radius: var(--radius-md);
            padding: 32px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 180px;
            position: relative;
            overflow: hidden;
        }

        .stat-light {
            background: var(--bg);
        }

        .stat-light h3 {
            font-size: 36px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--gold);
        }

        .stat-light p {
            color: var(--gray);
            font-size: 15px;
        }

        .stat-image {
            background: linear-gradient(rgba(30, 64, 175, 0.4), rgba(109, 40, 217, 0.4)), url('assets/images/back.jpg') center/cover;
            color: var(--white);
        }

        .stat-image h3 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .stat-image p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 15px;
        }

        .stat-dark {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: var(--white);
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 20px;
        }

        .stat-dark h3 {
            font-size: 48px;
            font-weight: 500;
        }

        .stat-dark p {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.9);
        }

        .watermark {
            position: absolute;
            right: 0;
            bottom: -20px;
            font-size: 120px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.1);
            line-height: 1;
            pointer-events: none;
        }

        /* What We Do */
        .section-title {
            text-align: center;
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 40px;
            color: var(--black);
        }

        .ministry-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }

        .ministry-card {
            background: var(--bg);
            border-radius: var(--radius-md);
            padding: 32px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid transparent;
        }

        .ministry-card:hover {
            transform: translateY(-4px);
            background: var(--white);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border-color: var(--gray-light);
        }

        .m-icon {
            font-size: 32px;
            background: var(--white);
            color: var(--gold);
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.1);
        }

        .ministry-card h4 {
            font-size: 20px;
            font-weight: 600;
        }

        .ministry-card p {
            color: var(--gray);
            font-size: 15px;
            line-height: 1.5;
        }

        .arrow-link {
            position: absolute;
            top: 24px;
            right: 24px;
            width: 40px;
            height: 40px;
            background: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--black);
            text-decoration: none;
            transition: background 0.2s, color 0.2s, transform 0.2s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .ministry-card:hover .arrow-link {
            background: var(--gold);
            color: var(--white);
            transform: scale(1.05);
        }

        /* Service Times & Contact */
        .mt-80 {
            margin-top: 80px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
        }

        .contact-card {
            background: var(--white);
            padding: 32px;
            border-radius: var(--radius-md);
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .contact-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .contact-icon {
            font-size: 40px;
            color: var(--gold);
            margin-bottom: 16px;
        }

        /* Footer */
        footer {
            margin-top: 80px;
            padding-top: 40px;
            border-top: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            color: var(--gray);
            font-size: 14px;
        }

        @media (max-width: 900px) {
            .hero {
                padding: 40px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .floating-cta {
                flex-direction: column;
                gap: 20px;
                position: relative;
                bottom: 0;
                transform: none;
                left: 0;
                width: 100%;
                margin-top: -30px;
            }

            .cta-col {
                width: 100%;
            }

            .cta-btn {
                width: 100%;
                justify-content: center;
            }

            .nav-links {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <nav>
            <a href="#" class="brand">
                <img src="assets/images/logo.png" alt="Logo">
                Adom Fie
            </a>
            <div class="nav-links">
                <a href="#" class="active">Home</a>
                <a href="#what-we-do">Ministries</a>
                <a href="#service-times">Service Times</a>
                <a href="#contact">Contact</a>
            </div>
            <div class="nav-actions">
                <a href="login.php" class="btn-primary">Admin Portal</a>
            </div>
        </nav>

        <div class="hero">
            <h1>Discover Your Spiritual Home Today</h1>
            <p>A vibrant community dedicated to spiritual growth, fellowship, and serving others in love.</p>

            <div class="floating-cta">
                <div class="cta-col">
                    <span>Action</span>
                    <strong>Join a Ministry <i class="ph ph-caret-down"></i></strong>
                </div>
                <div class="cta-col">
                    <span>Information</span>
                    <strong onclick="document.getElementById('service-times').scrollIntoView();" style="cursor:pointer;">Service Times <i class="ph ph-caret-down"></i></strong>
                </div>
                <div class="cta-col">
                    <span>Connect</span>
                    <strong onclick="document.getElementById('contact').scrollIntoView();" style="cursor:pointer;">Contact Us <i class="ph ph-caret-down"></i></strong>
                </div>
                <button class="cta-btn" onclick="document.getElementById('what-we-do').scrollIntoView();">
                    <i class="ph-bold ph-magnifying-glass"></i> Explore
                </button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card stat-light">
                <h3>10+</h3>
                <p>Active Ministries</p>
            </div>
            <div class="stat-card stat-image">
                <h3>1.2k+</h3>
                <p>Vibrant Members</p>
            </div>
            <div class="stat-card stat-dark">
                <h3>8+</h3>
                <p>Years of Grace &<br>Community Service</p>
                <div class="watermark">Grace</div>
            </div>
        </div>

        <h2 class="section-title" id="what-we-do">What we do</h2>

        <div class="ministry-grid">
            <?php if (!empty($ministries)): ?>
                <?php foreach ($ministries as $min): ?>
                    <div class="ministry-card">
                        <div class="m-icon"><?= htmlspecialchars($min['icon']) ?></div>
                        <h4><?= htmlspecialchars($min['name']) ?></h4>
                        <p><?= htmlspecialchars($min['description'] ?? 'Join our active group.') ?></p>
                        <span class="arrow-link">
                            <i class="ph-bold ph-arrow-up-right"></i>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: var(--gray); grid-column: 1 / -1;">No ministries found.</p>
            <?php endif; ?>
        </div>

        <h2 class="section-title mt-80" id="service-times">Service Times</h2>
        <div class="stats-grid" style="margin-top: 0;">
            <div class="stat-card stat-light" style="align-items: center; text-align: center;">
                <h3 style="font-size: 28px; margin-bottom: 12px; color: var(--gold);">Sunday Service</h3>
                <p style="font-size: 16px; color: var(--black); font-weight: 500; margin-bottom: 4px;">9:00 AM - 11:30 AM</p>
                <p>Main Sanctuary</p>
            </div>
            <div class="stat-card stat-dark" style="align-items: center; text-align: center; flex-direction: column; justify-content: center; gap: 0;">
                <h3 style="font-size: 28px; margin-bottom: 12px; font-weight: 600;">Wednesday</h3>
                <p style="font-size: 16px; font-weight: 500; margin-bottom: 4px; color: var(--white);">6:30 PM - 8:00 PM</p>
                <p style="color: rgba(255,255,255,0.8);">Interactive Bible Study</p>
                <div class="watermark" style="font-size: 80px; bottom: -10px;">Word</div>
            </div>
            <div class="stat-card stat-light" style="align-items: center; text-align: center;">
                <h3 style="font-size: 28px; margin-bottom: 12px; color: var(--gold);">Friday Prayer</h3>
                <p style="font-size: 16px; color: var(--black); font-weight: 500; margin-bottom: 4px;">7:00 PM - 9:00 PM</p>
                <p>Intercession</p>
            </div>
        </div>

        <h2 class="section-title mt-80" id="contact">Contact Us</h2>
        <div class="contact-grid">
            <div class="contact-card">
                <i class="ph-fill ph-phone-call contact-icon"></i>
                <h4 style="font-size: 20px; font-weight: 600; margin-bottom: 8px;">Phone</h4>
                <p style="color: var(--gray);">+233 540 207 812</p>
            </div>
            <div class="contact-card">
                <i class="ph-fill ph-envelope-simple contact-icon"></i>
                <h4 style="font-size: 20px; font-weight: 600; margin-bottom: 8px;">Email</h4>
                <p style="color: var(--gray);">info@adomfieccr.org</p>
            </div>
            <div class="contact-card">
                <i class="ph-fill ph-map-pin contact-icon"></i>
                <h4 style="font-size: 20px; font-weight: 600; margin-bottom: 8px;">Address</h4>
                <p style="color: var(--gray);">House of Grace, Ghana</p>
            </div>
        </div>

        <footer>
            <div>&copy; <?= date('Y') ?> Adom Fie CCR Community. All rights reserved.</div>
            <div>Built for the Community</div>
        </footer>
    </div>

</body>

</html>