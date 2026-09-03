<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['customer_id']) || !isset($_SESSION['booking_package']) || !isset($_SESSION['booking_date'])) {
    header("Location: booking.php");
    exit();
}

$pkg_name = $_SESSION['booking_package']['name'];
$is_darkroom = (strpos(strtolower($pkg_name), 'darkroom') !== false);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['booking_backdrop'] = $_POST['backdrop_color'];
    $_SESSION['booking_has_pets'] = $_POST['has_pets'];
    
    if ($_POST['has_pets'] === 'Yes') {
        $num_pets = trim($_POST['num_pets'] ?? '');
        $pet_size = trim($_POST['pet_size'] ?? '');
        $pet_breed = trim($_POST['pet_breed'] ?? '');
        $_SESSION['booking_pet_details'] = "Quantity: {$num_pets}, Size: {$pet_size}, Breed: {$pet_breed}";
    } else {
        $_SESSION['booking_pet_details'] = '';
    }

    header("Location: booking_step4.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customizations | PHILO Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="booking-customization-page">

    <?php include 'includes/header.php'; ?>

    <div class="booking-container">
        <div class="step-header">
            <p class="booking-step-label booking-step-label-short">Step 3 of 4</p>
            <h1>Shoot Customization</h1>
        </div>

        <div class="card-panel">
            <form method="POST" id="customForm">
                <input type="hidden" name="backdrop_color" id="backdrop_color" required>
                <input type="hidden" name="has_pets" id="has_pets" value="No">

                <label class="section-label">Select Backdrop Color</label>
                <div class="color-grid">
                    <?php if ($is_darkroom): ?>
                        <div class="color-swatch" data-color="Slate Grey" onclick="selectColor(this, 'Slate Grey')"><span class="dot dot-slate-grey"></span> Slate Grey</div>
                        <div class="color-swatch" data-color="Red" onclick="selectColor(this, 'Red')"><span class="dot dot-red"></span> Red</div>
                    <?php else: ?>
                        <div class="color-swatch" data-color="Grey" onclick="selectColor(this, 'Grey')"><span class="dot dot-grey"></span> Grey</div>
                        <div class="color-swatch" data-color="White" onclick="selectColor(this, 'White')"><span class="dot dot-white"></span> White</div>
                        <div class="color-swatch" data-color="Sage" onclick="selectColor(this, 'Sage')"><span class="dot dot-sage"></span> Sage</div>
                        <div class="color-swatch" data-color="Brown" onclick="selectColor(this, 'Brown')"><span class="dot dot-brown"></span> Brown</div>
                        <div class="color-swatch" data-color="Latte" onclick="selectColor(this, 'Latte')"><span class="dot dot-latte"></span> Latte</div>
                        <div class="color-swatch" data-color="Blush" onclick="selectColor(this, 'Blush')"><span class="dot dot-blush"></span> Blush</div>
                    <?php endif; ?>
                </div>

                <label class="section-label">Bringing Pets?</label>
                <div class="toggle-group">
                    <div class="toggle-btn selected" id="pet_no" onclick="setPetToggle('No')">No</div>
                    <div class="toggle-btn" id="pet_yes" onclick="setPetToggle('Yes')">Yes</div>
                </div>

                <div class="pet-details-card" id="pet_info_card">
                    <div class="pet-grid-2col">
                        <div>
                            <span class="field-sublabel">Number of pets</span>
                            <input type="text" name="num_pets" class="custom-input" placeholder="e.g. 2">
                        </div>
                        <div>
                            <span class="field-sublabel">Size</span>
                            <select name="pet_size" class="custom-select">
                                <option value="" disabled selected>Select size</option>
                                <option value="Small">Small</option>
                                <option value="Medium">Medium</option>
                                <option value="Large">Large</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <span class="field-sublabel">Breed</span>
                        <input type="text" name="pet_breed" class="custom-input" placeholder="e.g. Golden Retriever, Persian Cat">
                    </div>
                </div>

                <div class="booking-form-actions">
                    <a href="booking_step2.php" class="btn-primary booking-back-button">&larr; Back</a>
                    <button type="submit" id="submit_btn" class="btn-primary booking-submit-button" disabled>Proceed to Review &rarr;</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function selectColor(element, colorName) {
            document.querySelectorAll('.color-swatch').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('backdrop_color').value = colorName;
            checkValidity();
        }

        function setPetToggle(val) {
            document.getElementById('has_pets').value = val;
            if (val === 'Yes') {
                document.getElementById('pet_yes').classList.add('selected');
                document.getElementById('pet_no').classList.remove('selected');
                document.getElementById('pet_info_card').classList.add('is-visible');
            } else {
                document.getElementById('pet_no').classList.add('selected');
                document.getElementById('pet_yes').classList.remove('selected');
                document.getElementById('pet_info_card').classList.remove('is-visible');
            }
        }

        function checkValidity() {
            if (document.getElementById('backdrop_color').value !== '') {
                document.getElementById('submit_btn').disabled = false;
            }
        }
    </script>
</body>
</html>