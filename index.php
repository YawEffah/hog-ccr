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
    <meta name="description" content="Adom Fie CCR — A vibrant community dedicated to spiritual growth, fellowship, and serving others in love. House of Grace, Ghana.">
    <title>Welcome to Adom Fie CCR | House of Grace</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <!-- Landing Stylesheet -->
    <link rel="stylesheet" href="assets/css/landing.css">
</head>

<body>

    <div class="container">

        <!-- ── Navigation ─────────────────────────────────────────── -->
        <nav>
            <a href="#" class="brand">
                <img src="assets/images/logo.png" alt="Adom Fie CCR Logo">
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

        <!-- ── Hero ───────────────────────────────────────────────── -->
        <div class="hero">

            <div class="hero-badge">
                <span class="badge-dot"></span>
                Serving our community
            </div>

            <h1>Discover Your <em>Spiritual Home</em> Today</h1>
            <p>A vibrant community dedicated to spiritual growth, fellowship, and serving others in love.</p>

            <!-- Floating Quick-Action Bar -->
            <div class="floating-cta">
                <div class="cta-col">
                    <span>Action</span>
                    <strong onclick="document.getElementById('what-we-do').scrollIntoView({behavior:'smooth'});">
                        Join a Ministry <i class="ph ph-caret-right"></i>
                    </strong>
                </div>
                <div class="cta-col">
                    <span>Information</span>
                    <strong onclick="document.getElementById('service-times').scrollIntoView({behavior:'smooth'});">
                        Service Times <i class="ph ph-caret-right"></i>
                    </strong>
                </div>
                <div class="cta-col">
                    <span>Connect</span>
                    <strong onclick="document.getElementById('contact').scrollIntoView({behavior:'smooth'});">
                        Contact Us <i class="ph ph-caret-right"></i>
                    </strong>
                </div>
                <button class="cta-btn" onclick="document.getElementById('what-we-do').scrollIntoView({behavior:'smooth'});">
                    <i class="ph-bold ph-compass"></i> Explore
                </button>
            </div>
        </div>

        <!-- ── Stats Strip ─────────────────────────────────────────── -->
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

        <!-- ── What We Do — Ministries ────────────────────────────── -->
        <section class="section-gap" id="what-we-do">
            <div class="section-header">
                <span class="section-eyebrow">What We Do</span>
                <h2 class="section-title">Our Ministries</h2>
                <p class="section-subtitle">Join one of our active ministries and find your place in our growing community of faith.</p>
            </div>

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
                    <p style="text-align: center; color: var(--gray); grid-column: 1 / -1; padding: 40px 0;">No ministries found.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- ── Our Teams ───────────────────────────────────────────── -->
        <section class="section-gap" id="teams">
            <div class="section-header">
                <span class="section-eyebrow">Community</span>
                <h2 class="section-title">Our Teams</h2>
                <p class="section-subtitle">Dedicated groups working together to strengthen and serve our community.</p>
            </div>

            <div class="ministry-grid">
                <div class="ministry-card">
                    <div class="m-icon"><i class="ph-bold ph-heart"></i></div>
                    <h4>Marriage and Family Life Team</h4>
                    <p>Supporting and strengthening marriages and family life within the community.</p>
                    <span class="arrow-link"><i class="ph-bold ph-arrow-up-right"></i></span>
                </div>
                <div class="ministry-card">
                    <div class="m-icon"><i class="ph-bold ph-globe"></i></div>
                    <h4>Outreach and Evangelization Team</h4>
                    <p>Spreading the Word and reaching out to our community with love and purpose.</p>
                    <span class="arrow-link"><i class="ph-bold ph-arrow-up-right"></i></span>
                </div>
                <div class="ministry-card">
                    <div class="m-icon"><i class="ph-bold ph-handshake"></i></div>
                    <h4>Welcoming and Hospitality Team</h4>
                    <p>Ensuring every visitor and member feels at home in God's presence.</p>
                    <span class="arrow-link"><i class="ph-bold ph-arrow-up-right"></i></span>
                </div>
            </div>
        </section>

        <!-- ── Standing Committees ────────────────────────────────── -->
        <section class="section-gap" id="committees">
            <div class="section-header">
                <span class="section-eyebrow">Organisation</span>
                <h2 class="section-title">Standing Committees</h2>
                <p class="section-subtitle">Committees that keep our community running smoothly and beautifully.</p>
            </div>

            <div class="ministry-grid">
                <div class="ministry-card">
                    <div class="m-icon"><i class="ph-bold ph-fork-knife"></i></div>
                    <h4>Food Committee</h4>
                    <p>Managing culinary needs and refreshments for community events.</p>
                    <span class="arrow-link"><i class="ph-bold ph-arrow-up-right"></i></span>
                </div>
                <div class="ministry-card">
                    <div class="m-icon"><i class="ph-bold ph-kanban"></i></div>
                    <h4>Projects Committee</h4>
                    <p>Overseeing and executing special projects and developments.</p>
                    <span class="arrow-link"><i class="ph-bold ph-arrow-up-right"></i></span>
                </div>
                <div class="ministry-card">
                    <div class="m-icon"><i class="ph-bold ph-hand-heart"></i></div>
                    <h4>Welfare Committee</h4>
                    <p>Providing care and support for the well-being of all members.</p>
                    <span class="arrow-link"><i class="ph-bold ph-arrow-up-right"></i></span>
                </div>
                <div class="ministry-card">
                    <div class="m-icon"><i class="ph-bold ph-sparkle"></i></div>
                    <h4>Decorations &amp; Beautification</h4>
                    <p>Enhancing our environment for a more welcoming worship experience.</p>
                    <span class="arrow-link"><i class="ph-bold ph-arrow-up-right"></i></span>
                </div>
                <div class="ministry-card">
                    <div class="m-icon"><i class="ph-bold ph-book-open"></i></div>
                    <h4>Liturgical Committee</h4>
                    <p>Organising and preparing liturgical activities and spiritual events.</p>
                    <span class="arrow-link"><i class="ph-bold ph-arrow-up-right"></i></span>
                </div>
            </div>
        </section>

        <!-- ── Service Times ──────────────────────────────────────── -->
        <section class="section-gap" id="service-times">
            <div class="section-header">
                <span class="section-eyebrow">Join Us</span>
                <h2 class="section-title">Service Times</h2>
                <p class="section-subtitle">We'd love to see you. Come worship with us any day of the week.</p>
            </div>

            <div class="service-grid">
                <div class="service-card service-light">
                    <div class="service-icon">
                        <i class="ph-bold ph-sun"></i>
                    </div>
                    <h3>Sunday Service</h3>
                    <div class="service-time">8:30 AM</div>
                    <div class="service-detail">Main Sanctuary &mdash; Every Sunday</div>
                </div>

                <div class="service-card service-featured">
                    <div class="service-icon">
                        <i class="ph-bold ph-hands-praying"></i>
                    </div>
                    <h3>Morning Mass</h3>
                    <div class="service-time">6:00 AM</div>
                    <div class="service-detail">Wednesday &mdash; Main Sanctuary</div>
                    <div class="service-watermark">Mass</div>
                </div>

                <div class="service-card service-light">
                    <div class="service-icon">
                        <i class="ph-bold ph-moon"></i>
                    </div>
                    <h3>Prayer Meetings</h3>
                    <div class="service-time">7:00 PM</div>
                    <div class="service-detail">Mid-week &mdash; All Welcome</div>
                </div>
            </div>
        </section>

        <!-- ── Contact ────────────────────────────────────────────── -->
        <section class="section-gap" id="contact">
            <div class="section-header">
                <span class="section-eyebrow">Get in Touch</span>
                <h2 class="section-title">Contact Us</h2>
                <p class="section-subtitle">We'd love to hear from you. Reach out any time and we'll get back to you.</p>
            </div>

            <div class="contact-grid">
                <div class="contact-card">
                    <i class="ph-fill ph-phone-call contact-icon"></i>
                    <h4>Phone</h4>
                    <p>+233 540 207 812</p>
                </div>
                <div class="contact-card">
                    <i class="ph-fill ph-envelope-simple contact-icon"></i>
                    <h4>Email</h4>
                    <p>info@adomfieccr.com</p>
                </div>
                <div class="contact-card">
                    <i class="ph-fill ph-map-pin contact-icon"></i>
                    <h4>Address</h4>
                    <p>House of Grace, Ghana</p>
                </div>
            </div>
        </section>

        <!-- ── Footer ─────────────────────────────────────────────── -->
        <footer class="site-footer">
            <div class="footer-inner">
                <div class="footer-brand">
                    <img src="assets/images/logo.png" alt="Logo">
                    Adom Fie CCR
                </div>
                <p class="footer-copy">&copy; <?= date('Y') ?> Adom Fie CCR Community. All rights reserved.</p>
                <p class="footer-tagline">Built for the Community</p>
            </div>
        </footer>

    </div><!-- .container -->

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const observer = new IntersectionObserver((entries, obs) => {
                entries.forEach((entry, i) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        obs.unobserve(entry.target);
                    }
                });
            }, { root: null, rootMargin: '0px', threshold: 0.08 });

            document.querySelectorAll(
                '.stat-card, .ministry-card, .contact-card, .service-card, .section-header'
            ).forEach((el, index) => {
                el.classList.add('animate-on-scroll');
                el.style.transitionDelay = `${(index % 5) * 0.08}s`;
                observer.observe(el);
            });
        });
    </script>

</body>
</html>