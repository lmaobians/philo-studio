<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['customer_id']) || !isset($_SESSION['booking_package'])) {
    header("Location: booking_step3.php");
    exit();
}

$pkg = $_SESSION['booking_package'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['booking_date'] = $_POST['schedule_date'];
    $_SESSION['booking_time'] = $_POST['schedule_time'];
    header("Location: booking_step3.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Date & Time | PHILO Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .booking-container { max-width: 800px; margin: 40px auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
        .step-header { text-align: center; margin-bottom: 25px; }
        .selected-pkg-bar { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }

        /* Custom Visual Calendar UI */
        .calendar-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 25px; }
        .cal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .cal-header h3 { margin: 0; font-size: 1.1rem; }
        .cal-nav { background: none; border: 1px solid #ddd; padding: 5px 12px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; text-align: center; }
        .cal-day-label { font-size: 0.8rem; font-weight: 700; color: #888; padding-bottom: 8px; }
        .cal-date { height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid #f0f0f0; font-size: 0.9rem; cursor: pointer; transition: 0.15s; }
        .cal-date:hover:not(.disabled) { border-color: #111; background: #fafafa; }
        .cal-date.selected { background: #111 !important; color: #fff !important; border-color: #111 !important; }
        .cal-date.disabled { background: #f5f5f5; color: #ccc; cursor: not-allowed; text-decoration: line-through; border-color: transparent; }

        /* Time Slots Grid */
        .slot-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 10px; margin-top: 15px; }
        .slot-btn { padding: 10px; border: 1px solid #ddd; border-radius: 6px; background: #fff; text-align: center; cursor: pointer; font-size: 0.88rem; font-weight: 500; }
        .slot-btn.selected { background: #111; color: #fff; border-color: #111; }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <div class="booking-container">
        <div class="step-header">
            <p style="text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem; font-weight: 700; color: #888;">Step 2 of 4</p>
            <h1>Select Date & Time Slot</h1>
        </div>

        <div class="selected-pkg-bar">
            <div>
                <strong><?= htmlspecialchars($pkg['name']) ?></strong> 
                <span style="color: #666; font-size: 0.9rem;">(₱<?= number_format($pkg['price'], 0) ?> · <?= $pkg['slot_minutes'] ?>-min slots)</span>
            </div>
            <a href="booking.php" style="font-size: 0.85rem; color: #666; text-decoration: underline;">Change Package</a>
        </div>

        <form method="POST">
            <input type="hidden" name="schedule_date" id="schedule_date" required>
            <input type="hidden" name="schedule_time" id="schedule_time" required>

            <!-- Calendar Widget -->
            <div class="calendar-card">
                <div class="cal-header">
                    <button type="button" class="cal-nav" onclick="changeMonth(-1)">&lt; Prev</button>
                    <h3 id="calendar_month_year"></h3>
                    <button type="button" class="cal-nav" onclick="changeMonth(1)">Next &gt;</button>
                </div>
                <div class="cal-grid" id="calendar_days"></div>
            </div>

            <!-- Time Slots -->
            <div class="calendar-card" id="time_section" style="display: none;">
                <h3 style="margin-top: 0; font-size: 1.05rem;">Available Time Slots (10:00 AM – 7:00 PM)</h3>
                <div class="slot-grid" id="slots_container"></div>
            </div>

            <button type="submit" id="next_btn" class="btn-primary full-width" style="padding: 14px; border-radius: 8px; margin-top: 10px;" disabled>Proceed to Customization &rarr;</button>
        </form>
    </div>

    <script>
        const slotMinutes = <?= (int)$pkg['slot_minutes'] ?>;
        let currentDate = new Date();
        let selectedDateStr = '';

        function renderCalendar() {
            const monthYearText = document.getElementById('calendar_month_year');
            const grid = document.getElementById('calendar_days');
            grid.innerHTML = '';

            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();

            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            monthYearText.innerText = `${monthNames[month]} ${year}`;

            // Add Day Labels
            const days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
            days.forEach(d => {
                const label = document.createElement('div');
                label.className = 'cal-day-label';
                label.innerText = d;
                grid.appendChild(label);
            });

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Blank spaces before day 1
            for (let i = 0; i < firstDay; i++) {
                grid.appendChild(document.createElement('div'));
            }

            // Render Days
            for (let day = 1; day <= daysInMonth; day++) {
                const dateObj = new Date(year, month, day);
                const dayOfWeek = dateObj.getDay();
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

                const dayCell = document.createElement('div');
                dayCell.className = 'cal-date';
                dayCell.innerText = day;

                // Disable past dates and Mondays (1 = Monday)
                if (dateObj < today || dayOfWeek === 1) {
                    dayCell.classList.add('disabled');
                    if (dayOfWeek === 1) dayCell.title = "Closed on Mondays";
                } else {
                    if (selectedDateStr === dateStr) dayCell.classList.add('selected');
                    dayCell.onclick = function() {
                        document.querySelectorAll('.cal-date').forEach(c => c.classList.remove('selected'));
                        dayCell.classList.add('selected');
                        selectedDateStr = dateStr;
                        document.getElementById('schedule_date').value = dateStr;
                        generateTimeSlots();
                    };
                }
                grid.appendChild(dayCell);
            }
        }

        function changeMonth(direction) {
            currentDate.setMonth(currentDate.getMonth() + direction);
            renderCalendar();
        }

        function generateTimeSlots() {
            const container = document.getElementById('slots_container');
            container.innerHTML = '';
            document.getElementById('time_section').style.display = 'block';

            let start = 10 * 60; // 10:00 AM
            let end = 19 * 60;   // 7:00 PM

            while (start + slotMinutes <= end) {
                let hours = Math.floor(start / 60);
                let minutes = start % 60;
                let displayHours = hours % 12 || 12;
                let ampm = hours < 12 ? 'AM' : 'PM';
                let timeStr = `${String(displayHours).padStart(2, '0')}:${String(minutes).padStart(2, '0')} ${ampm}`;
                let rawValue = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:00`;

                let btn = document.createElement('div');
                btn.className = 'slot-btn';
                btn.innerText = timeStr;
                btn.onclick = function() {
                    document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
                    btn.classList.add('selected');
                    document.getElementById('schedule_time').value = rawValue;
                    document.getElementById('next_btn').disabled = false;
                };

                container.appendChild(btn);
                start += slotMinutes;
            }
        }

        renderCalendar();
    </script>
</body>
</html>