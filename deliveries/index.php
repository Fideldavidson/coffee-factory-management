<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['manager', 'clerk']);

// Handle Form Submission
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $farmer_id = $_POST['farmer_id'] ?? '';
    $quantity = $_POST['quantity'] ?? '';
    $grade = $_POST['grade'] ?? '';
    $moisture = $_POST['moisture_content'] ?? '';
    $notes = $_POST['notes'] ?? '';

    if ($farmer_id && $quantity && $grade && $moisture) {
        $batch_number = 'BATCH-' . date('Ymd') . '-' . rand(1000, 9999);
        $delivery_date = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO deliveries (farmer_id, batch_number, quantity, grade, moisture_content, delivery_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        
        if (query($sql, [$farmer_id, $batch_number, $quantity, $grade, $moisture, $delivery_date])) {
            setFlashMessage('success', "Delivery recorded successfully! Batch: " . $batch_number);
            redirect($_SERVER['PHP_SELF']);
        } else {
            $error_msg = "Failed to record delivery. Please try again.";
        }
    } else {
        $error_msg = "Please fill in all required fields.";
    }
}

if ($error_msg) {
    setFlashMessage('error', $error_msg);
}

// Fetch Today's Deliveries for the "Recent" Lists
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');
$recent_deliveries = queryAll(
    "SELECT d.*, f.name as farmer_name FROM deliveries d 
     JOIN farmers f ON d.farmer_id = f.id 
     WHERE d.delivery_date BETWEEN ? AND ? 
     ORDER BY d.delivery_date DESC LIMIT 10",
    [$today_start, $today_end]
);

// Fetch Farmers for Dropdown
$farmers = queryAll("SELECT id, name, farmer_id FROM farmers WHERE status = 'active' ORDER BY name ASC");

$pageTitle = "Coffee Delivery Entry";
include __DIR__ . '/../includes/header.php';
?>

<div class="welcome-section">
    <div>
        <h1 class="welcome-title">Coffee Delivery Entry</h1>
        <p class="welcome-subtitle">Record and track new coffee deliveries from farmers</p>
    </div>
    <div class="date-display">
        <div style="font-size: var(--font-size-sm); color: var(--text-muted);">Today</div>
        <div style="font-weight: 600; color: var(--foreground); font-size: var(--font-size-base);"><?php echo date('l, d F Y'); ?></div>
    </div>
</div>

<div class="deliveries-grid">
    <!-- Left Column: Delivery Form -->
    <div class="delivery-form-card">
        <div class="form-section-title">
            <i class="fas fa-truck-loading"></i>
            New Delivery Entry
        </div>
        <p class="form-section-subtitle">Fill in the details for the coffee delivery</p>

        <form action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="form-group" style="position: relative;">
                <label class="form-label">Select Farmer *</label>
                <div class="input-with-icon">
                    <i class="fas fa-search" style="color: var(--primary-color);"></i>
                    <input 
                        type="text" 
                        id="farmerSelectionInput" 
                        class="form-control-styled" 
                        placeholder="Search farmer by name or ID..."
                        autocomplete="off"
                        value=""
                    >
                </div>
                <input type="hidden" id="farmer_id" name="farmer_id" value="" required>

                <!-- Floating Results Dropdown -->
                <div class="farmer-results-dropdown" id="resultsDropdown" style="position: absolute; top: 100%; left: 0; right: 0; z-index: 1001; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); margin-top: 5px; display: none; overflow: hidden; border: 1px solid #E0D5CE;">
                    <div class="popup-results-list" id="dropdownResults" style="max-height: 250px; overflow-y: auto; padding: 5px;">
                        <!-- Results injected here -->
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Quantity (kg) *</label>
                        <div class="input-with-icon">
                            <i class="fas fa-weight-hanging"></i>
                            <input type="number" step="0.01" name="quantity" class="form-control-styled" placeholder="0" required>
                        </div>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">Coffee Grade *</label>
                        <div class="input-with-icon">
                            <!-- No icon for select in design, used no-icon class if needed or keep standard -->
                            <select name="grade" class="form-select no-icon" required>
                                <option value="AA">AA Grade (Premium)</option>
                                <option value="AB">AB Grade</option>
                                <option value="PB">PB Grade</option>
                                <option value="C">C Grade</option>
                                <option value="HE">HE Grade</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Moisture Content (%) *</label>
                <div class="input-with-icon">
                    <i class="fas fa-tint"></i>
                    <input type="number" step="0.1" name="moisture_content" class="form-control-styled" placeholder="0" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Additional Notes</label>
                <textarea name="notes" class="form-control-styled" rows="3" placeholder="Quality observations, special handling notes..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-submit">
                <i class="fas fa-save"></i> Record Delivery
            </button>
        </form>
    </div>

    <!-- Right Column: Recent Deliveries -->
    <div class="activity-section" style="margin-top: 0; padding: 32px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #F5F5F5;">
        <h3 class="section-title">Recent Records</h3>
        <p class="section-subtitle">Latest entries for today</p>
        
        <div class="activity-list" style="margin-top: 24px;">
            <?php if (empty($recent_deliveries)): ?>
                <div class="activity-item">
                    <div class="activity-content">
                        <p style="color: var(--text-muted); text-align: center;">No deliveries recorded today</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($recent_deliveries as $delivery): ?>
                    <div class="activity-item" style="padding: 16px 0;">
                        <div class="activity-dot dot-green"></div>
                        <div class="activity-content">
                            <h4 style="display: flex; justify-content: space-between; align-items: center;">
                                <?php echo e($delivery['farmer_name']); ?>
                                <span style="font-size: 16px; font-weight: 700; color: var(--primary-dark);"><?php echo formatWeight($delivery['quantity']); ?></span>
                            </h4>
                            <p style="margin-top: 4px;"><?php echo e($delivery['grade']); ?> Grade • Batch: <?php echo e($delivery['batch_number']); ?></p>
                            <small style="color: var(--text-muted); font-size: var(--font-size-xs);">
                                <?php echo date('H:i', strtotime($delivery['delivery_date'])); ?> Today
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const farmers = <?php echo json_encode($farmers); ?>;
    const searchInput = document.getElementById('farmerSelectionInput');
    const resultsDropdown = document.getElementById('resultsDropdown');
    const resultsContainer = document.getElementById('dropdownResults');
    const idInput = document.getElementById('farmer_id');

    // Show results on focus
    searchInput.addEventListener('focus', () => {
        renderResults(searchInput.value);
    });

    // Search Logic
    searchInput.addEventListener('input', (e) => {
        renderResults(e.target.value);
    });

    function renderResults(term) {
        const lowerTerm = term.toLowerCase();
        const filtered = farmers.filter(f => 
            f.name.toLowerCase().includes(lowerTerm) || 
            f.farmer_id.toLowerCase().includes(lowerTerm)
        );

        resultsContainer.innerHTML = '';
        if (filtered.length > 0) {
            filtered.forEach(f => {
                const item = document.createElement('div');
                item.className = 'popup-result-item';
                item.style.padding = '12px 16px';
                item.style.cursor = 'pointer';
                item.style.transition = 'background 0.2s';
                item.style.borderBottom = '1px solid #F5F5F5';
                item.innerHTML = `
                    <div style="font-weight: 600; color: #3E2723;">${f.name}</div>
                    <div style="font-size: 11px; color: #8D6E63;">ID: ${f.farmer_id}</div>
                `;
                item.onmouseover = () => item.style.background = '#FDFBF9';
                item.onmouseout = () => item.style.background = 'transparent';
                item.onclick = (e) => {
                    e.stopPropagation();
                    selectFarmer(f);
                };
                resultsContainer.appendChild(item);
            });
            resultsDropdown.style.display = 'block';
        } else {
            resultsContainer.innerHTML = '<div style="padding: 15px; text-align: center; color: #8D6E63; font-size: 13px;">No farmers found</div>';
            resultsDropdown.style.display = 'block';
        }
    }

    function selectFarmer(f) {
        searchInput.value = `${f.name} (${f.farmer_id})`;
        idInput.value = f.id;
        resultsDropdown.style.display = 'none';
    }

    // Close dropdown on click outside
    document.addEventListener('click', (e) => {
        if (!idInput.parentElement.contains(e.target) && e.target !== searchInput) {
            resultsDropdown.style.display = 'none';
        }
    });

    // Prevent closing when clicking inside dropdown
    resultsDropdown.addEventListener('click', (e) => e.stopPropagation());
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
