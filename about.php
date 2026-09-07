<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | PHILO Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="about-page">

    <?php include 'includes/header.php'; ?>

    <section class="page-title-section text-center">
        <p class="booking-step-label">OUR STORY & VISION</p>
        <h1 class="page-title">Capturing Genuine Moments</h1>
        <p class="page-subtitle">A warm, self-photo studio space created for effortless memories, unfiltered smiles, and creative freedom.</p>
    </section>

    <section class="about-container">
        <div class="about-grid">
            <div class="about-image-column">
                <div class="about-img-frame">
                    <img src="images/solo-package.jpg" alt="Philo Studio Experience">
                </div>
            </div>
            <div class="about-content-column">
                <p class="category-subtitle-label">WELCOME TO PHILO STUDIO</p>
                <h2>Your Space to Just Be You</h2>
                <p>Founded on the idea that photos should feel natural and fun, PHILO Studio offers a private self-photo studio experience. No stiff poses, no pressure from behind the lens—just you, a clicker, and professional lighting calibrated to make everyone shine.</p>
                <p>Whether you are celebrating a milestone, taking portrait updates with your pets, or simply creating candid keepsakes with friends, we provide the canvas and controls to capture your narrative.</p>
            </div>
        </div>
    </section>

    <section class="about-highlights-section">
        <div class="about-container">
            <div class="highlights-grid">
                <div class="highlight-card">
                    <div class="highlight-icon">✨</div>
                    <h3>Total Privacy</h3>
                    <p>Enjoy private studio sessions where you can express yourself freely without any audience.</p>
                </div>
                <div class="highlight-card">
                    <div class="highlight-icon">🐾</div>
                    <h3>Pet Friendly</h3>
                    <p>Your furry companions are family too. Bring them along to capture high-quality portraits together.</p>
                </div>
                <div class="highlight-card">
                    <div class="highlight-icon">📸</div>
                    <h3>Studio Quality</h3>
                    <p>Professional cameras, studio lighting, and curated backdrops ensure high-grade photos every single time.</p>
                </div>
            </div>
        </div>
    </section>
    
<?php include 'includes/footer.php'; ?>

</body>
</html>