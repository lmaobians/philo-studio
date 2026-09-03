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
    <style>
        body { background-color: #f7f7f8; }
        .booking-container { max-width: 650px; margin: 50px auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
        .step-header { text-align: center; margin-bottom: 30px; }
        .step-header h1 { font-size: 1.8rem; font-weight: 700; color: #cb6b5; margin-top: 5px; }
        
        .card-panel { background: #ffffff; border: 1px solid #eaeaea; border-radius: 16px; padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
        .section-label { font-size: 0.9rem; font-weight: 600; color: #333; margin-bottom: 12px; display: block; }
        
        /* Interactive Backdrop Swatches */
        .color-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; margin-bottom: 30px; }
        .color-swatch {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.2s ease;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .color-swatch .dot { width: 14px; height: 14px; border-radius: 50%; display: inline-block; border: 1px solid rgba(0,0,0,0.1); }
        
        /* Active Swatch Color Styles */
        .color-swatch[data-color="Grey"].selected { background-color: #808080 !important; color: #fff !important; border-color: #808080 !important; }
        .color-swatch[data-color="White"].selected { background-color: #ffffff !important; color: #111 !important; border-color: #111 !important; }
        .color-swatch[data-color="Sage"].selected { background-color: #9caf88 !important; color: #fff !important; border-color: #9caf88 !important; }
        .color-swatch[data-color="Brown"].selected { background-color: #795548 !important; color: #fff !important; border-color: #795548 !important; }
        .color-swatch[data-color="Latte"].selected { background-color: #c5a059 !important; color: #fff !important; border-color: #c5a059 !important; }
        .color-swatch[data-color="Blush"].selected { background-color: #f4c2c2 !important; color: #111 !important; border-color: #f4c2c2 !important; }
        .color-swatch[data-color="Slate Grey"].selected { background-color: #708090 !important; color: #fff !important; border-color: #708090 !important; }
        .color-swatch[data-color="Red"].selected { background-color: #d32f2f !important; color: #fff !important; border-color: #d32f2f !important; }

        /* Pet Toggle Buttons */
        .toggle-group { display: flex; gap: 12px; margin-bottom: 25px; }
        .toggle-btn {
            flex: 1;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
            text-align: center;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .toggle-btn.selected { background: #111; color: #fff; border-color: #111; }

        /* Image-Matched Pet Form Block */
        .pet-details-card {
            background: #ffffff;
            border: 1px solid #eeeeee;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }
        .pet-grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        
        .custom-input, .custom-select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.9rem;
            outline: none;
            box-sizing: border-box;
            background: #fff;
            font-family: inherit;
        }
        .custom-input::placeholder { color: #aaaaaa; }
        .field-sublabel { font-size: 0.8rem; color: #888; font-weight: 500; margin-bottom: 8px; display: block; }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="booking-container">
        <div class="step-header">
            <p style="text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem; font-weight: 700; color: #888;">Step 3 of 4</p>
            <h1>Shoot Customization</h1>
        </div>

        <div class="card-panel">
            <form method="POST" id="customForm">
                <input type="hidden" name="backdrop_color" id="backdrop_color" required>
                <input type="hidden" name="has_pets" id="has_pets" value="No">

                <!-- Backdrop Color Selection Swatches -->
                <label class="section-label">Select Backdrop Color</label>
                <div class="color-grid">
                    <?php if ($is_darkroom): ?>
                        <div class="color-swatch" data-color="Slate Grey" onclick="selectColor(this, 'Slate Grey')"><span class="dot" style="background: #708090;"></span> Slate Grey</div>
                        <div class="color-swatch" data-color="Red" onclick="selectColor(this, 'Red')"><span class="dot" style="background: #d32f2f;"></span> Red</div>
                    <?php else: ?>
                        <div class="color-swatch" data-color="Grey" onclick="selectColor(this, 'Grey')"><span class="dot" style="background: #808080;"></span> Grey</div>
                        <div class="color-swatch" data-color="White" onclick="selectColor(this, 'White')"><span class="dot" style="background: #ffffff;"></span> White</div>
                        <div class="color-swatch" data-color="Sage" onclick="selectColor(this, 'Sage')"><span class="dot" style="background: #9caf88;"></span> Sage</div>
                        <div class="color-swatch" data-color="Brown" onclick="selectColor(this, 'Brown')"><span class="dot" style="background: #795548;"></span> Brown</div>
                        <div class="color-swatch" data-color="Latte" onclick="selectColor(this, 'Latte')"><span class="dot" style="background: #c5a059;"></span> Latte</div>
                        <div class="color-swatch" data-color="Blush" onclick="selectColor(this, 'Blush')"><span class="dot" style="background: #f4c2c2;"></span> Blush</div>
                    <?php endif; ?>
                </div>

                <!-- Pet Toggle Buttons -->
                <label class="section-label">Bringing Pets?</label>
                <div class="toggle-group">
                    <div class="toggle-btn selected" id="pet_no" onclick="setPetToggle('No')">No</div>
                    <div class="toggle-btn" id="pet_yes" onclick="setPetToggle('Yes')">Yes</div>
                </div>

                <!-- Pet Input Block (Matched to provided design image) -->
                <div class="pet-details-card" id="pet_info_card" style="display: none;">
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

                <div style="display: flex; gap: 12px; margin-top: 20px;">
                    <a href="booking_step2.php" class="btn-primary" style="background: #f0f0f0; color: #333; text-decoration: none; text-align: center; flex: 1; padding: 14px; border-radius: 10px;">&larr; Back</a>
                    <button type="submit" id="submit_btn" class="btn-primary" style="flex: 2; padding: 14px; border-radius: 10px;" disabled>Proceed to Review &rarr;</button>
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
                document.getElementById('pet_info_card').style.display = 'block';
            } else {
                document.getElementById('pet_no').classList.add('selected');
                document.getElementById('pet_yes').classList.remove('selected');
                document.getElementById('pet_info_card').style.display = 'none';
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