<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Philo Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

  <section class="hero" id="home">
        <div class="hero-text">
            <p class="subtitle">CAPTURE YOUR MOMENTS</p>
            <h1>Studio photos, <br><em>made simple.</em></h1>
            <p class="description">Choose a package, book your slot, and let us take care of the rest. Walk in, snap, and take home memories.</p>
            <div class="hero-btns">
                <a href="packages.php" class="btn-primary hero-btn">BOOK YOUR SESSION</a>
                <a href="packages.php" class="view-all">VIEW PACKAGES &rarr;</a>
            </div>
        </div>

        <div class="hero-gallery-grid">
            <img src="images/hero-1.jpg" alt="Solo portrait" class="hero-img-tall">

            <div class="hero-right-col">
                <div class="hero-top-row">
                    <img src="images/hero-2.jpg" alt="Duo vertical">
                    <img src="images/hero-3.jpg" alt="Pet portrait">
                </div>
                <img src="images/hero-4.jpg" alt="Group family photo" class="hero-img-bottom">
            </div>
        </div>
    </section>

    <section class="features">
    <p class="subtitle">WHY PHILO</p>
    <h1>Everything you need, nothing you don't.</h1>
    <div class="features-grid">
        <div class="feature-card">
            <img src="images/camera.png" alt="Professional Equipment" class="feature-icon">
            <h3>Professional Equipment</h3>
            <p>High quality studio equipment.</p>
        </div>
        <div class="feature-card">
            <img src="images/layers.png" alt="Multiple Backdrops" class="feature-icon">
            <h3>Multiple Backdrops</h3>
            <p>Vibrant backdrops to choose from.</p>
        </div>
        <div class="feature-card">
            <img src="images/bookmark.png" alt="Fast Booking" class="feature-icon">
            <h3>Fast Booking</h3>
            <p>Book your slot online in under 2 minutes.</p>
        </div>
        <div class="feature-card">
            <img src="images/printer.png" alt="Printed Keepsakes" class="feature-icon">
            <h3>Printed Keepsakes</h3>
            <p>Select and print customized souvenirs.</p>
        </div>
    </div>
    </section>

<section class="packages">
    <div class="section-header">
        <div>
            <p class="section-label">PRICES</p>
            <h1>Choose your package.</h1>
        </div>
        <a href="packages.php" class="view-all">VIEW PACKAGES &rarr;</a>
    </div>

    <div class="carousel-wrapper">
        <button class="carousel-btn prev-btn" id="prevBtn" aria-label="Previous">&larr;</button>
        
        <div class="packages-grid" id="packagesGrid">
            <div class="package-card">
                <img src="images/solo-package.jpg" alt="Solo Package">
                <div class="card-body">
                    <div class="card-head">
                        <h3>Solo</h3>
                        <span class="price">₱500</span>
                    </div>
                    <p>A personal session designed for individuals.</p>
                    <a href="packages.php" class="btn-primary full-width">BOOK THIS PACKAGE &rarr;</a>
                </div>
            </div>

            <div class="package-card">
                <img src="images/duo-package.jpg" alt="Duo Package">
                <div class="card-body">
                    <div class="card-head">
                        <h3>Duo</h3>
                        <span class="price">₱700</span>
                    </div>
                    <p>Perfect for couples, best friends, or mother-and-child shoots.</p>
                    <a href="packages.php" class="btn-primary full-width">BOOK THIS PACKAGE &rarr;</a>
                </div>
            </div>

            <div class="package-card">
                <img src="images/group-package.jpg" alt="Group Package">
                <div class="card-body">
                    <div class="card-head">
                        <h3>Group</h3>
                        <span class="price">₱1000</span>
                    </div>
                    <p>Barkada sessions, family portraits, team shoots — everyone gets their moment.</p>
                    <a href="packages.php" class="btn-primary full-width">BOOK THIS PACKAGE &rarr;</a>
                </div>
            </div>

            <div class="package-card">
                <img src="images/darkroom-package.jpg" alt="Darkroom Package">
                <div class="card-body">
                    <div class="card-head">
                        <h3>Darkroom</h3>
                        <span class="price">₱700</span>
                    </div>
                    <p>Moody lighting and custom edits for creative, dramatic portraits.</p>
                    <a href="packages.php" class="btn-primary full-width">BOOK THIS PACKAGE &rarr;</a>
                </div>
            </div>

            <div class="package-card">
                <img src="images/fullgrid-package.jpg" alt="Full Frame Package">
                <div class="card-body">
                    <div class="card-head">
                        <h3>Full Grid</h3>
                        <span class="price">₱1000</span>
                    </div>
                    <p>For professional and for any celebration, whole-body shots.</p>
                    <a href="packages.php" class="btn-primary full-width">BOOK THIS PACKAGE &rarr;</a>
                </div>
            </div>
        </div>

        <button class="carousel-btn next-btn" id="nextBtn" aria-label="Next">&rarr;</button>
    </div>
</section>

<section class="gallery-section" id="gallery">
    <p class="subtitle">A GLIMPSE OF OUR WORK</p>
    <h2 class="gallery-title">The Gallery</h2>
    
    <div class="gallery-masonry">
        <div class="gallery-item"><img src="images/gallery-1.jpg" alt="Gallery Photo 1"></div>
        <div class="gallery-item"><img src="images/gallery-2.jpg" alt="Gallery Photo 2"></div>
        <div class="gallery-item"><img src="images/gallery-3.jpg" alt="Gallery Photo 3"></div>
        <div class="gallery-item"><img src="images/gallery-4.jpg" alt="Gallery Photo 4"></div>
        <div class="gallery-item"><img src="images/gallery-5.jpg" alt="Gallery Photo 5"></div>
        <div class="gallery-item"><img src="images/gallery-6.jpg" alt="Gallery Photo 6"></div>
        <div class="gallery-item"><img src="images/gallery-7.jpg" alt="Gallery Photo 7"></div>
        <div class="gallery-item"><img src="images/gallery-8.jpg" alt="Gallery Photo 8"></div>
        <div class="gallery-item"><img src="images/gallery-9.jpg" alt="Gallery Photo 9"></div>
        <div class="gallery-item"><img src="images/gallery-10.jpg" alt="Gallery Photo 10"></div>
        <div class="gallery-item"><img src="images/gallery-11.jpg" alt="Gallery Photo 11"></div>
        <div class="gallery-item"><img src="images/gallery-12.jpg" alt="Gallery Photo 12"></div>
        <div class="gallery-item"><img src="images/gallery-13.jpg" alt="Gallery Photo 13"></div>
        <div class="gallery-item"><img src="images/gallery-14.jpg" alt="Gallery Photo 14"></div>
        <div class="gallery-item"><img src="images/gallery-15.jpg" alt="Gallery Photo 15"></div>
        <div class="gallery-item"><img src="images/gallery-16.jpg" alt="Gallery Photo 16"></div>
        <div class="gallery-item"><img src="images/gallery-17.jpg" alt="Gallery Photo 17"></div>
        <div class="gallery-item"><img src="images/gallery-18.jpg" alt="Gallery Photo 18"></div>
        <div class="gallery-item"><img src="images/gallery-19.jpg" alt="Gallery Photo 19"></div>
        <div class="gallery-item"><img src="images/gallery-20.jpg" alt="Gallery Photo 20"></div>
    </div>
</section>

    <section class="testimonials">
        <p class="subtitle">CLIENT WORDS</p>
        <h1>What our clients say</h1>
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="stars">★★★★★</div>
                <p>"I really like the service!"</p>
                <div class="client-info">
                    <strong>Anne Luna</strong>
                    <span>Model & Influencer</span>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="stars">★★★★★</div>
                <p>"Affordable and professional setup."</p>
                <div class="client-info">
                    <strong>Ding Dong Ang</strong>
                    <span>Sibulan Councilor</span>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="stars">★★★★★</div>
                <p>"Pet friendly and great service!"</p>
                <div class="client-info">
                    <strong>Princess Mae</strong>
                    <span>Mother of two (dogs)</span>
                </div>
            </div>
        </div>
    </section>
    
    <section class="instagram-section" id="instagram">
    <h2 class="instagram-title">Follow us on Instagram!</h2>
    
    <div class="instagram-grid">
        <a href="https://www.instagram.com/p/DRGoqd4Ce3Q/?img_index=1" target="_blank" rel="noopener noreferrer" class="ig-card">
            <img src="images/instapost1.jpg" alt="Instagram Post 1">
            <div class="ig-icon-overlay">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
            </div>
        </a>

        <a href="https://www.instagram.com/p/DLXMTgmpnxZ/" target="_blank" rel="noopener noreferrer" class="ig-card">
            <img src="images/instapost2.jpg" alt="Instagram Post 2">
            <div class="ig-icon-overlay">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
            </div>
        </a>

        <a href="https://www.instagram.com/p/DS7QzskCUuQ/?img_index=1" target="_blank" rel="noopener noreferrer" class="ig-card">
            <img src="images/instapost3.jpg" alt="Instagram Post 3">
            <div class="ig-icon-overlay">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
            </div>
        </a>

        <a href="https://www.instagram.com/p/DaHEB-MGEk6/?img_index=1" target="_blank" rel="noopener noreferrer" class="ig-card">
            <img src="images/instapost4.jpg" alt="Instagram Post 4">
            <div class="ig-icon-overlay">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
            </div>
        </a>
    </div>

    <a href="https://www.instagram.com/philo.studioph" target="_blank" rel="noopener noreferrer" class="ig-handle">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
        <span>@philo.studioph</span>
    </a>
    </section>

<?php include 'includes/footer.php'; ?>


<script async src="//www.instagram.com/embed.js"></script>
<script>
    const container = document.getElementById('packagesGrid');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    const scrollAmount = 340;

    nextBtn.addEventListener('click', () => {
        container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    });

    prevBtn.addEventListener('click', () => {
        container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    });
</script>
</body>
</html>