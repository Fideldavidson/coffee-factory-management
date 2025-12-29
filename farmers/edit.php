<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['manager', 'clerk']);

$id = $_GET['id'] ?? 0;
$farmer = queryOne("SELECT * FROM farmers WHERE id = ?", [$id]);

if (!$farmer) {
    setFlashMessage('error', 'Farmer not found.');
    redirectBase('/farmers/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($phone)) $errors[] = 'Phone number is required';
    if (empty($location)) $errors[] = 'Location is required';
    
    if (empty($errors)) {
        $oldPhone = $farmer['phone'];
        $sql = "UPDATE farmers SET name = ?, phone = ?, location = ?, status = ? WHERE id = ?";
        
        try {
            execute($sql, [$name, $phone, $location, $status, $id]);
            
            // Sync with users table if phone changed
            if ($phone !== $oldPhone) {
                execute("UPDATE users SET phone = ? WHERE phone = ?", [$phone, $oldPhone]);
            }

            setFlashMessage('success', 'Farmer updated successfully!');
            redirectBase('/farmers/index.php');
        } catch (Exception $e) {
            $errors[] = 'Failed to update farmer: ' . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        setFlashMessage('error', implode('<br>', $errors));
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Edit Farmer</h3>
        <p class="card-description">Update farmer information</p>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="form-group">
                <label class="form-label">Farmer ID</label>
                <input 
                    type="text" 
                    class="form-control" 
                    value="<?php echo e($farmer['farmer_id']); ?>"
                    disabled
                >
                <small style="color: var(--gray-500);">Farmer ID cannot be changed</small>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="name">Full Name *</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-control" 
                        value="<?php echo e($_POST['name'] ?? $farmer['name']); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number *</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        class="form-control" 
                        value="<?php echo e($_POST['phone'] ?? $farmer['phone']); ?>"
                        required
                    >
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="location">Location *</label>
                    <input 
                        type="text" 
                        id="location" 
                        name="location" 
                        class="form-control" 
                        value="<?php echo e($_POST['location'] ?? $farmer['location']); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="active" <?php echo ($farmer['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($farmer['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Update Farmer
                </button>
                <a href="<?php echo baseUrl('/farmers/view.php?id=' . $id); ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
