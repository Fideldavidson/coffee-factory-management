            </div>
        </main>
    </div>
    
    <!-- Profile Settings Modal -->
    <div class="modal" id="profileSettingsModal">
        <div class="modal-overlay" onclick="closeProfileSettings()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Profile Settings</h3>
                <button class="modal-close" onclick="closeProfileSettings()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="modal-subtitle">Update your profile information</p>
                <form id="profileForm" onsubmit="saveProfileSettings(event)">
                    <div class="form-group">
                        <label for="profileName">Name</label>
                        <input type="text" id="profileName" class="form-input" value="<?php echo e($currentUser); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="profileEmail">Email</label>
                        <input type="email" id="profileEmail" class="form-input" value="<?php echo e($_SESSION['user_email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="profilePhone">Phone</label>
                        <input type="tel" id="profilePhone" class="form-input" value="<?php echo e($_SESSION['user_phone'] ?? ''); ?>" placeholder="+254712345678">
                    </div>
                    <div class="form-group">
                        <label for="profileRole">Role</label>
                        <input type="text" id="profileRole" class="form-input" value="<?php echo ucfirst(e($currentRole)); ?>" disabled>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeProfileSettings()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Change Password Modal -->
    <div class="modal" id="changePasswordModal">
        <div class="modal-overlay" onclick="closeChangePassword()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Change Password</h3>
                <button class="modal-close" onclick="closeChangePassword()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="modal-subtitle">Enter your current password and choose a new one</p>
                <form id="passwordForm" onsubmit="saveNewPassword(event)">
                    <div class="form-group">
                        <label for="currentPassword">Current Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="currentPassword" class="form-input" required>
                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('currentPassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="newPassword" class="form-input" required>
                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('newPassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm New Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="confirmPassword" class="form-input" required>
                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirmPassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeChangePassword()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="<?php echo baseUrl('/assets/js/main.js?v=' . time()); ?>"></script>
</body>
</html>
