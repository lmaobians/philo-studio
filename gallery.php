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
    <title>Gallery | PHILO Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

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
<?php include 'includes/footer.php'; ?>
</body>
</html>