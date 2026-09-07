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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['booking_backdrop'] = $_POST['backdrop_color'];
    $_SESSION['booking_has_pets'] = $_POST['has_pets'];
    
    if ($_POST['has_pets'] === 'Yes') {
        $num_pets  = (int)($_POST['num_pets'] ?? 0);
        $pet_size  = trim($_POST['pet_size'] ?? '');
        $pet_breed = trim($_POST['pet_breed'] ?? '');

        // Pet Policy Logic Validation
        $max_allowed = 0;
        if ($pet_size === 'S (1-10 lbs)') {
            $max_allowed = 3;
        } elseif (in_array($pet_size, ['M (11-25 lbs)', 'L (26-40 lbs)'])) {
            $max_allowed = 2;
        } elseif (in_array($pet_size, ['XL (41-70 lbs)', 'XXL (91-119 lbs)'])) {
            $max_allowed = 1;
        }

        $breed_items = array_filter(array_map('trim', explode(',', $pet_breed)));
        $valid_keywords = [
            'Dog', 'Cat', 'Kitten', 'Puppy', 'Rabbit', 'Bunny', 'Hamster', 'Guinea Pig', 'Bird', 'Parrot', 
            'Turtle', 'Tortoise', 'Lizard', 'Snake', 'Ferret', 'Chinchilla', 'Hedgehog', 'Fish', 'Poodle', 
            'Golden Retriever', 'Labrador Retriever', 'Bulldog', 'Beagle', 'Persian Cat', 'Siamese Cat', 
            'Shih Tzu', 'Corgi', 'Siberian Husky', 'Pomeranian', 'Terrier', 'Pug', 'Chihuahua', 
            'German Shepherd', 'Dachshund', 'Aspin', 'Puspin'
        ];

        $invalid_breeds = [];
        foreach ($breed_items as $b) {
            $is_known = false;
            foreach ($valid_keywords as $kw) {
                if (stripos($b, $kw) !== false) {
                    $is_known = true;
                    break;
                }
            }
            if (!$is_known) {
                $invalid_breeds[] = $b;
            }
        }

        if (empty($pet_size)) {
            $error = "Please select a pet size.";
        } elseif ($num_pets < 1) {
            $error = "Please enter a valid number of pets.";
        } elseif ($max_allowed > 0 && $num_pets > $max_allowed) {
            $error = "Size {$pet_size} allows a maximum of {$max_allowed} pet(s).";
        } elseif (empty($pet_breed)) {
            $error = "Please specify the pet breed(s).";
        } elseif (count($breed_items) !== $num_pets) {
            $error = "You indicated {$num_pets} pet(s), but listed " . count($breed_items) . " breed(s). Please separate " . $num_pets . " breed(s) with commas.";
        } elseif (!empty($invalid_breeds)) {
            $error = "Unrecognized pet or breed: \"" . implode(', ', $invalid_breeds) . "\". Please select from the suggested breeds.";
        }

        $_SESSION['booking_pet_details'] = "Quantity: {$num_pets}, Size: {$pet_size}, Breed: {$pet_breed}";
    } else {
        $_SESSION['booking_pet_details'] = '';
    }

    if (empty($error)) {
        header("Location: booking_step4.php");
        exit();
    }
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

        <?php if (!empty($error)): ?>
            <div class="auth-error booking-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

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
                            <span class="field-sublabel">Size</span>
                            <select name="pet_size" id="pet_size" class="custom-select" onchange="validatePetLimits()">
                                <option value="" disabled selected>Select size</option>
                                <option value="S (1-10 lbs)" data-max="3">S (1-10 lbs) — Max 3 pets</option>
                                <option value="M (11-25 lbs)" data-max="2">M (11-25 lbs) — Max 2 pets</option>
                                <option value="L (26-40 lbs)" data-max="2">L (26-40 lbs) — Max 2 pets</option>
                                <option value="XL (41-70 lbs)" data-max="1">XL (41-70 lbs) — Max 1 pet</option>
                                <option value="XXL (91-119 lbs)" data-max="1">XXL (91-119 lbs) — Max 1 pet</option>
                            </select>
                        </div>
                        <div>
                            <span class="field-sublabel">Number of pets</span>
                            <input type="number" min="1" max="3" name="num_pets" id="num_pets" class="custom-input" placeholder="e.g. 1" oninput="validatePetLimits()">
                        </div>
                    </div>
                    <div>
                        <span class="field-sublabel">Breed (type to see suggestions)</span>
                        <div class="autocomplete-wrapper">
                            <input type="text" name="pet_breed" id="pet_breed" class="custom-input" placeholder="e.g. Golden Retriever, Persian Cat" autocomplete="off" oninput="onBreedInput()">
                            <div id="breed_suggestions" class="autocomplete-suggestions"></div>
                        </div>
                    </div>
                    <p id="pet_policy_msg" style="color: #c0392b; font-size: 0.85rem; margin-top: 0.5rem; display: none;"></p>
                </div>

                <div class="booking-form-actions">
                    <a href="booking_step2.php" class="btn-primary booking-back-button">&larr; Back</a>
                    <button type="submit" id="submit_btn" class="btn-primary booking-submit-button" disabled>Proceed to Review &rarr;</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const validPetKeywords = [
            'Dog', 'Cat', 'Kitten', 'Puppy', 'Rabbit', 'Bunny', 'Hamster', 'Guinea Pig', 'Bird', 'Parrot', 
            'Turtle', 'Tortoise', 'Lizard', 'Snake', 'Ferret', 'Chinchilla', 'Hedgehog', 'Fish', 'Poodle', 
            'Golden Retriever', 'Labrador Retriever', 'Bulldog', 'Beagle', 'Persian Cat', 'Siamese Cat', 
            'Shih Tzu', 'Corgi', 'Siberian Husky', 'Pomeranian', 'Terrier', 'Pug', 'Chihuahua', 
            'German Shepherd', 'Dachshund', 'Aspin', 'Puspin'
        ];

        function selectColor(element, colorName) {
            document.querySelectorAll('.color-swatch').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('backdrop_color').value = colorName;
            checkValidity();
        }

        function setPetToggle(val) {
            document.getElementById('has_pets').value = val;
            const petCard = document.getElementById('pet_info_card');
            
            if (val === 'Yes') {
                document.getElementById('pet_yes').classList.add('selected');
                document.getElementById('pet_no').classList.remove('selected');
                petCard.classList.add('is-visible');
                document.getElementById('pet_size').required = true;
                document.getElementById('num_pets').required = true;
                document.getElementById('pet_breed').required = true;
            } else {
                document.getElementById('pet_no').classList.add('selected');
                document.getElementById('pet_yes').classList.remove('selected');
                petCard.classList.remove('is-visible');
                document.getElementById('pet_size').required = false;
                document.getElementById('num_pets').required = false;
                document.getElementById('pet_breed').required = false;
            }
            checkValidity();
        }

        function onBreedInput() {
            const input = document.getElementById('pet_breed');
            const suggestionsBox = document.getElementById('breed_suggestions');
            const val = input.value;
            
            const terms = val.split(',');
            const currentTerm = terms[terms.length - 1].trim().toLowerCase();

            if (currentTerm.length === 0) {
                suggestionsBox.style.display = 'none';
                validatePetLimits();
                return;
            }

            const matches = validPetKeywords.filter(item => item.toLowerCase().includes(currentTerm));

            if (matches.length > 0) {
                suggestionsBox.innerHTML = '';
                matches.forEach(match => {
                    const div = document.createElement('div');
                    div.className = 'autocomplete-item';
                    div.innerText = match;
                    div.onclick = function() {
                        terms[terms.length - 1] = ' ' + match;
                        input.value = terms.join(',').trim() + ', ';
                        suggestionsBox.style.display = 'none';
                        validatePetLimits();
                        input.focus();
                    };
                    suggestionsBox.appendChild(div);
                });
                suggestionsBox.style.display = 'block';
            } else {
                suggestionsBox.style.display = 'none';
            }

            validatePetLimits();
        }

        document.addEventListener('click', function(e) {
            if (e.target.id !== 'pet_breed') {
                document.getElementById('breed_suggestions').style.display = 'none';
            }
        });

        function validatePetLimits() {
            const sizeSelect = document.getElementById('pet_size');
            const numInput = document.getElementById('num_pets');
            const breedInput = document.getElementById('pet_breed');
            const msg = document.getElementById('pet_policy_msg');
            const selectedOption = sizeSelect.options[sizeSelect.selectedIndex];

            let errorMessage = '';

            if (selectedOption && selectedOption.dataset.max) {
                const maxAllowed = parseInt(selectedOption.dataset.max);
                numInput.max = maxAllowed;
                const numVal = parseInt(numInput.value || 0);

                if (numVal > maxAllowed) {
                    errorMessage = `Size "${sizeSelect.value}" allows a maximum of ${maxAllowed} pet(s).`;
                }
            }

            if (!errorMessage && breedInput.value.trim() !== '' && numInput.value > 0) {
                const breeds = breedInput.value.split(',').map(b => b.trim()).filter(b => b.length > 0);
                const numVal = parseInt(numInput.value);

                if (breeds.length !== numVal) {
                    errorMessage = `You entered ${numVal} pet(s), but specified ${breeds.length} breed(s). Separate each breed with a comma.`;
                } else {
                    const invalid = breeds.filter(b => !validPetKeywords.some(kw => b.toLowerCase().includes(kw.toLowerCase())));
                    if (invalid.length > 0) {
                        errorMessage = `Unrecognized pet/breed: "${invalid.join(', ')}". Please select from the suggestions.`;
                    }
                }
            }

            if (errorMessage) {
                msg.innerText = errorMessage;
                msg.style.display = 'block';
            } else {
                msg.style.display = 'none';
            }

            checkValidity();
        }

        function checkValidity() {
            const backdropSelected = document.getElementById('backdrop_color').value !== '';
            const hasPets = document.getElementById('has_pets').value === 'Yes';
            let petsValid = true;

            if (hasPets) {
                const sizeVal = document.getElementById('pet_size').value;
                const numVal = parseInt(document.getElementById('num_pets').value || 0);
                const breedVal = document.getElementById('pet_breed').value.trim();
                const sizeSelect = document.getElementById('pet_size');
                const selectedOption = sizeSelect.options[sizeSelect.selectedIndex];
                const maxAllowed = selectedOption ? parseInt(selectedOption.dataset.max || 0) : 0;

                const msgVisible = document.getElementById('pet_policy_msg').style.display === 'block';

                if (!sizeVal || numVal < 1 || numVal > maxAllowed || breedVal === '' || msgVisible) {
                    petsValid = false;
                }
            }

            document.getElementById('submit_btn').disabled = !(backdropSelected && petsValid);
        }
    </script>
</body>
</html>