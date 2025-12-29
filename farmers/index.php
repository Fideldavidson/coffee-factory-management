<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['manager', 'clerk']);

// Get search and filter parameters (kept for initial server-side load if needed, though client-side will handle mostly)
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// Build query for farmers
$sql = "SELECT f.*, 
               COALESCE(COUNT(d.id), 0) as total_deliveries_real, 
               COALESCE(SUM(d.quantity), 0) as total_quantity_real
        FROM farmers f
        LEFT JOIN deliveries d ON f.id = d.farmer_id AND d.status = 'approved'
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (f.name LIKE ? OR f.farmer_id LIKE ? OR f.phone LIKE ? OR f.location LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($status) {
    $sql .= " AND f.status = ?";
    $params[] = $status;
}

$sql .= " GROUP BY f.id ORDER BY f.farmer_id ASC";
$farmers = queryAll($sql, $params);

// Get Statistics for the cards
$stats = [
    'total_farmers' => queryOne("SELECT COUNT(*) as count FROM farmers")['count'],
    'active_farmers' => queryOne("SELECT COUNT(*) as count FROM farmers WHERE status = 'active'")['count']
];

$pageTitle = "Farmer Registry";
include __DIR__ . '/../includes/header.php';
?>

<!-- Header Section with Flex Between -->
<div class="welcome-section">
    <div>
        <h1 class="welcome-title">Farmer Registry</h1>
        <p class="welcome-subtitle">Manage farmer profiles and track their contributions</p>
    </div>
    <div class="date-display">
        <button onclick="openAddModal()" class="btn btn-primary" style="height: 50px; font-weight: 600;">
            <i class="fas fa-plus"></i> Add New Farmer
        </button>
    </div>
</div>

<!-- Stats Widgets -->
<div class="inventory-stats-row">
    <div class="stat-card">
        <div class="stat-info">
            <h3>Total Farmers</h3>
            <div class="stat-value"><?php echo number_format($stats['total_farmers']); ?></div>
            <div class="stat-subtext">Registered farmers</div>
        </div>
        <div class="stat-icon blue">
            <i class="fas fa-users"></i>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-info">
            <h3>Active Farmers</h3>
            <div class="stat-value"><?php echo number_format($stats['active_farmers']); ?></div>
            <div class="stat-subtext">Currently contributing</div>
        </div>
        <div class="stat-icon green">
            <i class="fas fa-check-circle"></i>
        </div>
    </div>

    <div class="stat-card" style="padding: 16px; flex: 2; cursor: default;">
         <div class="stat-info" style="width: 100%;">
            <h3>Search Farmers</h3>
            <div class="search-input-wrapper" style="margin-top: 8px;">
                <i class="fas fa-search"></i>
                <input 
                    type="text" 
                    id="searchInput"
                    class="search-input" 
                    placeholder="Search by name, ID, or location..." 
                    value="<?php echo e($search); ?>"
                >
            </div>
         </div>
    </div>
</div>

<?php if (empty($farmers)): ?>
    <div style="text-align: center; padding: 60px; color: var(--text-muted); background: white; border-radius: 12px;">
        <i class="fas fa-users" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
        <p>No farmers found matching your search.</p>
        <a href="<?php echo baseUrl('/farmers/index.php'); ?>" style="color: var(--primary); margin-top: 8px; display: inline-block;">Clear Filters</a>
    </div>
<?php else: ?>
    <div class="farmer-grid" id="farmerGrid">
        <?php foreach ($farmers as $farmer): ?>
            <!-- Added data attributes for search and modal population -->
            <div class="farmer-card" 
                 data-name="<?php echo strtolower($farmer['name']); ?>" 
                 data-id="<?php echo strtolower($farmer['farmer_id']); ?>"
                 data-location="<?php echo strtolower($farmer['location']); ?>">
                 
                <div class="farmer-header">
                    <div class="farmer-profile">
                        <div class="farmer-avatar-large">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="farmer-identity">
                            <h3><?php echo e($farmer['name']); ?></h3>
                            <span>ID: <?php echo e($farmer['farmer_id']); ?></span>
                        </div>
                    </div>
                    <span class="badge badge-<?php echo $farmer['status'] === 'active' ? 'success' : 'danger'; ?>">
                        <?php echo e($farmer['status']); ?>
                    </span>
                </div>
                
                <div class="farmer-details">
                    <div class="detail-item">
                        <i class="fas fa-phone-alt"></i>
                        <span><?php echo e($farmer['phone']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo e($farmer['location']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="far fa-calendar-alt"></i>
                        <span>Registered: <?php echo date('d/m/Y', strtotime($farmer['registration_date'])); ?></span>
                    </div>
                </div>
                
                <div class="farmer-stats-row">
                    <div class="farmer-stat">
                        <div class="farmer-stat-value"><?php echo number_format($farmer['total_deliveries_real']); ?></div>
                        <div class="farmer-stat-label">Deliveries</div>
                    </div>
                    <div class="farmer-stat">
                        <div class="farmer-stat-value"><?php echo formatWeight($farmer['total_quantity_real']); ?></div>
                        <div class="farmer-stat-label">Total Coffee</div>
                    </div>
                </div>
                
                <div class="farmer-actions">
                    <!-- View Button -->
                    <button type="button" 
                            class="btn btn-light btn-block"
                            onclick='openViewModal(<?php echo json_encode($farmer); ?>)'>
                        <i class="far fa-eye"></i> View
                    </button>
                    <!-- Edit Button -->
                    <button type="button" 
                            class="btn btn-light btn-block"
                            onclick='openEditModal(<?php echo json_encode($farmer); ?>)'>
                        <i class="far fa-edit"></i> Edit
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ADD FARMER MODAL -->
<div id="addFarmerModal" class="modal">
    <div class="modal-overlay" onclick="closeModal('addFarmerModal')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Register New Farmer</h3>
            <button class="modal-close" onclick="closeModal('addFarmerModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p class="modal-subtitle">Add a new farmer to the registry. All fields are required.</p>
            <form action="add.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-input" placeholder="Enter farmer's full name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Farmer ID</label>
                    <input type="text" name="farmer_id" class="form-input" value="<?php echo getNextFarmerId(); ?>" readonly style="background-color: var(--muted); cursor: not-allowed;">
                    <small style="color: var(--text-muted);">Assigned automatically</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="tel" name="phone" class="form-input" placeholder="e.g., +254 712 345678" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Location *</label>
                    <input type="text" name="location" class="form-input" placeholder="e.g., Kiambu County" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addFarmerModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background-color: var(--primary-dark);">Register Farmer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- VIEW FARMER MODAL -->
<div id="viewFarmerModal" class="modal">
    <div class="modal-overlay" onclick="closeModal('viewFarmerModal')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Farmer Details</h3>
            <button class="modal-close" onclick="closeModal('viewFarmerModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p class="modal-subtitle" id="viewSubtitle">Complete information</p>
            
            <div class="form-row">
                <div class="form-group form-col">
                    <label class="form-label" style="color: var(--text-muted); font-size: 12px;">Full Name</label>
                    <div id="viewName" style="font-weight: 600; font-size: 16px; color: var(--text-main);"></div>
                </div>
                <div class="form-group form-col">
                    <label class="form-label" style="color: var(--text-muted); font-size: 12px;">Farmer ID</label>
                    <div id="viewFarmerId" style="font-weight: 600; font-size: 16px; color: var(--text-main);"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group form-col">
                    <label class="form-label" style="color: var(--text-muted); font-size: 12px;">Phone Number</label>
                    <div id="viewPhone" style="font-weight: 500;"></div>
                </div>
                <div class="form-group form-col">
                    <label class="form-label" style="color: var(--text-muted); font-size: 12px;">Location</label>
                    <div id="viewLocation" style="font-weight: 500;"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group form-col">
                    <label class="form-label" style="color: var(--text-muted); font-size: 12px;">Registration Date</label>
                    <div id="viewRegDate" style="font-weight: 500;"></div>
                </div>
                <div class="form-group form-col">
                    <label class="form-label" style="color: var(--text-muted); font-size: 12px;">Status</label>
                    <span id="viewStatus" class="badge"></span>
                </div>
            </div>

            <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 16px 0;">
            <p class="form-label" style="color: #8D6E63; margin-bottom: 12px;">Delivery Statistics</p>
            
            <div class="form-row">
                 <div class="stat-card" style="padding: 16px; flex: 1; text-align: center; box-shadow: none; border: 1px solid var(--border-color);">
                    <div class="stat-value" id="viewDeliveries" style="font-size: 24px;">0</div>
                    <div class="stat-label">Total Deliveries</div>
                 </div>
                 <div class="stat-card" style="padding: 16px; flex: 1; text-align: center; box-shadow: none; border: 1px solid var(--border-color);">
                    <div class="stat-value" id="viewQuantity" style="font-size: 24px;">0kg</div>
                    <div class="stat-label">Total Coffee</div>
                 </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" style="width: 100%; justify-content: center;" onclick="closeModal('viewFarmerModal')">Close</button>
                <button type="button" class="btn btn-primary" id="viewEditBtn" style="width: 100%; justify-content: center; background-color: var(--primary-dark);">
                    <i class="far fa-edit"></i> Edit Farmer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT FARMER MODAL -->
<div id="editFarmerModal" class="modal">
    <div class="modal-overlay" onclick="closeModal('editFarmerModal')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Farmer</h3>
            <button class="modal-close" onclick="closeModal('editFarmerModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p class="modal-subtitle" id="editSubtitle">Update farmer information</p>
            <form id="editFarmerForm" action="edit.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="id" id="editId">
                
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" id="editName" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Farmer ID</label>
                    <input type="text" id="editFarmerIdDisplay" class="form-input" disabled style="background-color: var(--bg-body);">
                    <small style="color: var(--text-muted);">Farmer ID cannot be changed</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="tel" name="phone" id="editPhone" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Location *</label>
                    <input type="text" name="location" id="editLocation" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="editStatus" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editFarmerModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background-color: var(--primary-dark);">Update Farmer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Search Functionality
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const farmers = document.querySelectorAll('.farmer-card');
        let hasVisible = false;

        farmers.forEach(card => {
            const name = card.dataset.name;
            const id = card.dataset.id;
            const location = card.dataset.location;

            if (name.includes(searchTerm) || id.includes(searchTerm) || location.includes(searchTerm)) {
                card.style.display = 'flex';
                hasVisible = true;
            } else {
                card.style.display = 'none';
            }
        });
    });

    // Modal Functions
    function openAddModal() {
        document.getElementById('addFarmerModal').classList.add('show');
        document.body.classList.add('modal-open');
    }

    function openViewModal(farmer) {
        // Populate View Modal
        document.getElementById('viewSubtitle').textContent = 'Complete information for ' + farmer.name;
        document.getElementById('viewName').textContent = farmer.name;
        document.getElementById('viewFarmerId').textContent = farmer.farmer_id;
        document.getElementById('viewPhone').textContent = farmer.phone;
        document.getElementById('viewLocation').textContent = farmer.location;
        document.getElementById('viewRegDate').textContent = new Date(farmer.registration_date).toLocaleDateString('en-GB');
        
        const statusEl = document.getElementById('viewStatus');
        statusEl.textContent = farmer.status;
        statusEl.className = 'badge badge-' + (farmer.status === 'active' ? 'success' : 'danger');

        document.getElementById('viewDeliveries').textContent = farmer.total_deliveries_real;
        document.getElementById('viewQuantity').textContent = farmer.total_quantity_real + 'kg';

        // Setup Edit Button in View Modal to open Edit Modal
        const editBtn = document.getElementById('viewEditBtn');
        editBtn.onclick = function() {
            closeModal('viewFarmerModal');
            openEditModal(farmer);
        };

        document.getElementById('viewFarmerModal').classList.add('show');
        document.body.classList.add('modal-open');
    }

    function openEditModal(farmer) {
        // Populate Edit Modal
        document.getElementById('editSubtitle').textContent = 'Update farmer information for ' + farmer.name;
        document.getElementById('editId').value = farmer.id;
        document.getElementById('editName').value = farmer.name;
        document.getElementById('editFarmerIdDisplay').value = farmer.farmer_id; // Read-only display
        document.getElementById('editPhone').value = farmer.phone;
        document.getElementById('editLocation').value = farmer.location;
        document.getElementById('editStatus').value = farmer.status;
        
        // Update form action dynamically
        document.getElementById('editFarmerForm').action = 'edit.php?id=' + farmer.id;

        document.getElementById('editFarmerModal').classList.add('show');
        document.body.classList.add('modal-open');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('show');
        document.body.classList.remove('modal-open');
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.parentElement.classList.remove('show');
            document.body.classList.remove('modal-open');
        }
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
