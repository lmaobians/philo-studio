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
    <title>Frequently Asked Questions | PHILO Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="faq-page">

    <?php include 'includes/header.php'; ?>

    <main class="faq-main-wrapper">
        <section class="faq-hero">
            <p class="hero-subtitle">GOT QUESTIONS?</p>
            <h1 class="hero-title">Frequently Asked Questions</h1>
            <p class="faq-hero-desc">Everything you need to know about booking, self-shoot sessions, and studio policies.</p>
        </section>

        <section class="faq-container">

            <div class="faq-group">
                <h2 class="faq-group-title">Studio Policies & Rescheduling</h2>

                <details class="faq-item">
                    <summary class="faq-question">What is the Reschedule and Cancellation Policy?</summary>
                    <div class="faq-answer">
                        <p>You may reschedule your slot for free after your payment has been made.</p>
                        <p><strong>Lock-in Period:</strong> No refunds and no rebooking within 2 days before your reserved time slot.</p>
                        <p>A rescheduling fee (45% of your package) will be charged should you wish to reschedule your slot after the lock-in period.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">What are the essential studio rules and reminders?</summary>
                    <div class="faq-answer">
                        <ul>
                            <li><strong>Payment Policy:</strong> We allow up to one (1) hour for payment to secure your booking slot.</li>
                            <li><strong>Grace Period:</strong> We strictly observe a 10-minute grace period per client.</li>
                            <li><strong>Headcount:</strong> The maximum number of pax depends on your package. Any additional head will be ₱100.00 each.</li>
                            <li><strong>Children:</strong> Children 2 years old and below are free.</li>
                            <li><strong>Slot Duration:</strong> Each slot shall be treated as 1 slot, not an accumulated slot. This is to provide ample time for the equipment, especially the strobes, to cool down.</li>
                            <li><strong>Add-ons:</strong> You may pay for add-ons with your chosen package or at the studio on the day of your photo session.</li>
                        </ul>
                    </div>
                </details>
            </div>

            <div class="faq-group">
                <h2 class="faq-group-title">Props & Shoot Details</h2>

                <details class="faq-item">
                    <summary class="faq-question">Can I bring props or outfits?</summary>
                    <div class="faq-answer">
                        <p>Yes, please do! We do not provide props, so feel free to bring your own (hats, flowers, small decor, etc.).</p>
                        <p>🍰 <strong>Cakes</strong> are allowed for birthdays or celebrations, but please clean as you go. Stay mindful and careful so as not to damage any equipment.</p>
                        <p>⚠️ <strong>Sparklers and bubbles are not allowed</strong> as they may damage the backdrops.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">What kind of shots do you offer?</summary>
                    <div class="faq-answer">
                        <p>We only offer <strong>portrait shots (up to mid-body)</strong>.</p>
                        <p>Full body shots (where the floor is visible) are not offered.</p>
                    </div>
                </details>
            </div>

            <div class="faq-group">
                <h2 class="faq-group-title">During Your Session</h2>

                <details class="faq-item">
                    <summary class="faq-question">Do you have a dressing area?</summary>
                    <div class="faq-answer">
                        <p>Yes! We have a dressing area and a CR available for you to use if you need to change outfits during your session.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">Can we change the backdrops ourselves?</summary>
                    <div class="faq-answer">
                        <p>No. Backdrops may not be pulled down by clients. Only our staff members are allowed to adjust them to ensure no damage is made.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">Do you stop the timer?</summary>
                    <div class="faq-answer">
                        <p>We do not stop the timer unless you need the backdrop color changed. The timer resumes once the backdrop is ready.</p>
                        <p><em>Note: For outfit changes, the timer continues running.</em></p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">Do you accept walk-ins?</summary>
                    <div class="faq-answer">
                        <p>We prioritize online bookings. Walk-ins are possible if there are open slots, but we recommend booking in advance to secure your time.</p>
                    </div>
                </details>
            </div>

            <div class="faq-group">
                <h2 class="faq-group-title">Pet Policy</h2>

                <details class="faq-item">
                    <summary class="faq-question">Are pets allowed in the studio?</summary>
                    <div class="faq-answer">
                        <ul>
                            <li>Maximum of 3 pets are allowed per session.</li>
                            <li>Pets must wear a leash and diapers, or be carried at all times inside.</li>
                            <li>A ₱500 cleaning fee applies if your pet litters indoors.</li>
                            <li>If a pet becomes restless, we may ask that they wait outside until calm.</li>
                            <li>Any damage caused by pets will be charged accordingly.</li>
                        </ul>
                    </div>
                </details>
            </div>

        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

</body>
</html>