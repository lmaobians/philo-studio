<section class="cta-banner" id="book">
    <p class="section-label light">LIMITED SLOTS</p>
    <h2>Ready to book your studio session?</h2>
    <p>Slots fill fast. Book now and secure your date, we handle everything from there.</p>
    <div class="cta-btns">
        <a href="packages.php" class="btn-white">BOOK YOUR SESSION</a>
        <a href="contact.php" class="btn-outline-white">GET IN TOUCH</a>
    </div>
</section>

<footer>
    <div class="footer-grid">
        <div class="footer-brand">
            <a href="philostudio.php" class="footer-logo">
                <img src="images/footer-logo.jpg" alt="Philo Studio Logo">
            </a>
            <p>📍 Plaza Escano, 2nd Floor, beside Grab Food</p>
            <p>📞 0956 876 6873</p>
            <p>✉️ philo.studio@gmail.com</p>
        </div>

        <details class="footer-links" open>
            <summary><h4>STUDIO</h4></summary>
            <div class="footer-nav-group">
                <a href="about.php">About</a>
                <a href="gallery.php">Gallery</a>
                <a href="packages.php">Packages</a>
                <a href="faq.php">FAQ</a>
            </div>
        </details>

        <details class="footer-links" open>
            <summary><h4>SERVICES</h4></summary>
            <div class="footer-nav-group">
                <a href="packages.php">Solo</a>
                <a href="packages.php">Duo</a>
                <a href="packages.php">Group</a>
                <a href="packages.php">Darkroom</a>
                <a href="packages.php">Full Frame</a>
            </div>
        </details>

        <details class="footer-links" open>
            <summary><h4>SUPPORT</h4></summary>
            <div class="footer-nav-group">
                <a href="packages.php">Book a Session</a>
                <a href="contact.php">Contact Us</a>
                <a href="faq.php">Cancellation Policy</a>
            </div>
        </details>
    </div>

    <script>
function initFooterAccordion() {
    const footerDetails = document.querySelectorAll('.footer-links');
    const isDesktop = window.innerWidth > 768;

    footerDetails.forEach(detail => {
        if (isDesktop) {
            detail.setAttribute('open', '');
        } else {
            detail.removeAttribute('open');
        }
    });
}

window.addEventListener('DOMContentLoaded', initFooterAccordion);
window.addEventListener('resize', initFooterAccordion);
</script>
</footer>