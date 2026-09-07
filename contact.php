<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | PHILO Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
        <div id="toast-notification" class="toast-notification">
            <i class="fa-solid fa-circle-check toast-icon"></i>
            <div class="toast-text">
                <strong>Message Sent!</strong>
                <span>We'll get back to you shortly.</span>
            </div>
            <button class="toast-close" onclick="closeToast()">&times;</button>
        </div>
    <?php endif; ?>

    <section class="hero-section">
        <p class="hero-subtitle">GET IN TOUCH</p>
        <h1 class="hero-title">Contact Us</h1>
        <p class="hero-description">Have questions about our studio packages or custom sessions? Send us a message and we’ll get back to you shortly.</p>
    </section>

    <section class="contact-container">
        
        <div class="contact-info">
            <h2>Studio Details</h2>
            <p class="info-desc">Feel free to reach out directly via phone or email, or drop by our studio during operating hours.</p>

            <div class="info-list">
                <div class="info-item">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div>
                        <strong>Location</strong>
                        <p>Plaza Escano, 2nd Floor, beside Grab Food<br>Dumaguete City, Philippines</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <strong>Hours</strong>
                        <p>Tues – Sun: 10:00 AM – 7:00 PM</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <div>
                        <strong>Email</strong>
                        <p>philo.studio@gmail.com</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <div>
                        <strong>Phone</strong>
                        <p>0956 876 6873</p>
                    </div>
                </div>
            </div>

            <div class="social-links">
                <a href="https://www.instagram.com/philo.studioph/" class="social-icon"><i class="fa-brands fa-instagram"></i></a>
                <a href="https://www.facebook.com/profile.php?id=100087134534958" class="social-icon"><i class="fa-brands fa-facebook-f"></i></a>
            </div>
        </div>

        <div class="contact-form-card">
            <h2>Send a Message</h2>
            <form action="process-contact.php" method="POST" class="contact-form">
                
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="e.g. Jane Doe" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="jane@example.com" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="0912 345 6789">
                    </div>
                </div>

                <div class="form-group">
                    <label for="package">Interested Package</label>
                    <select id="package" name="package">
                        <option value="general">General Inquiry</option>
                        <option value="duo">Duo Package (₱700)</option>
                        <option value="group">Group Package (₱1000)</option>
                        <option value="darkroom">Darkroom Package (₱700)</option>
                        <option value="full-grid">Full Grid Package (₱1000)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="message">Your Message</label>
                    <textarea id="message" name="message" rows="5" placeholder="Tell us about your shoot ideas or questions..." required></textarea>
                </div>

                <button type="submit" class="btn-submit">Send Message →</button>
            </form>
        </div>

    </section>

<?php include 'includes/footer.php'; ?>

<script>
    function closeToast() {
        const toast = document.getElementById('toast-notification');
        if (toast) {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }
    }

    window.addEventListener('DOMContentLoaded', () => {
        const toast = document.getElementById('toast-notification');
        if (toast) {
            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => closeToast(), 5000);
        }
    });
</script>
</body>
</html>