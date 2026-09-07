<?php include 'includes/header.php'; ?>

    <section class="contact-hero">
        <p class="subtitle">GET IN TOUCH</p>
        <h1>Let’s capture your moments.</h1>
        <p class="description">Have questions about our studio packages or custom sessions? Send us a message and we’ll get back to you shortly.</p>
    </section>

    <section class="contact-container">
        
        <div class="contact-info">
            <h2>Studio Details</h2>
            <p class="info-desc">Feel free to reach out directly via phone or email, or drop by our studio during operating hours.</p>

            <div class="info-list">
                <div class="info-item">
                    <i class="fa-solid fa-location-dot"></i>
                    <div>
                        <strong>Location</strong>
                        <p>123 Studio Street, Suite 402<br>Mandaue City, Cebu, Philippines</p>
                    </div>
                </div>

                <div class="info-item">
                    <i class="fa-solid fa-clock"></i>
                    <div>
                        <strong>Hours</strong>
                        <p>Mon – Sun: 9:00 AM – 7:00 PM</p>
                    </div>
                </div>

                <div class="info-item">
                    <i class="fa-solid fa-envelope"></i>
                    <div>
                        <strong>Email</strong>
                        <p>hello@philostudio.ph</p>
                    </div>
                </div>

                <div class="info-item">
                    <i class="fa-solid fa-phone"></i>
                    <div>
                        <strong>Phone</strong>
                        <p>+63 912 345 6789</p>
                    </div>
                </div>
            </div>

            <div class="social-links">
                <a href="#" class="social-icon"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" class="social-icon"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#" class="social-icon"><i class="fa-brands fa-tiktok"></i></a>
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