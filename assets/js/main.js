// Main JavaScript for Coffee Factory CMS

// Auto-hide flash messages after 5 seconds
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function (event) {
        const profileDropdown = document.getElementById('profileDropdown');
        const userProfile = document.querySelector('.user-profile');

        if (profileDropdown && !userProfile.contains(event.target) && !profileDropdown.contains(event.target)) {
            profileDropdown.classList.remove('show');
        }
    });
});

// Sidebar Toggle
// Sidebar Toggle
function toggleSidebar() {
    const toggleBtn = document.getElementById('sidebarToggle');
    if (window.innerWidth > 768) {
        // Desktop: Toggle expansion
        document.body.classList.toggle('sidebar-expanded');
        // Save preference (optional, implementing locally for session mainly)
        const isExpanded = document.body.classList.contains('sidebar-expanded');
        localStorage.setItem('sidebarExpanded', isExpanded);
    } else {
        // Mobile: Toggle visibility (drawer)
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');

        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
        if (toggleBtn) toggleBtn.classList.toggle('active');

        // Prevent body scroll when mobile menu is open
        if (sidebar.classList.contains('show')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
}

// Initialize Sidebar State
document.addEventListener('DOMContentLoaded', () => {
    // Check local storage
    if (localStorage.getItem('sidebarExpanded') === 'true' && window.innerWidth > 768) {
        document.body.classList.add('sidebar-expanded');
    }

    // Handle resize
    window.addEventListener('resize', () => {
        if (window.innerWidth <= 768) {
            document.body.classList.remove('sidebar-expanded');
        } else {
            if (localStorage.getItem('sidebarExpanded') === 'true') {
                document.body.classList.add('sidebar-expanded');
            }
        }
    });
});

// Profile Dropdown Toggle
function toggleProfileDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('show');
}

// Open Profile Settings Modal
function openProfileSettings(event) {
    if (event) event.preventDefault();
    const modal = document.getElementById('profileSettingsModal');
    modal.classList.add('show');
    document.getElementById('profileDropdown').classList.remove('show');
}

// Close Profile Settings Modal
function closeProfileSettings() {
    const modal = document.getElementById('profileSettingsModal');
    modal.classList.remove('show');
}

// Helper to get CSRF Token
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

// Save Profile Settings
async function saveProfileSettings(event) {
    event.preventDefault();
    const btn = event.target.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;

    // Simple validation
    const name = document.getElementById('profileName').value;
    const email = document.getElementById('profileEmail').value;
    const phone = document.getElementById('profilePhone').value;

    if (!name || !email) {
        showToast('Name and Email are required', 'error');
        return;
    }

    try {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        const formData = new FormData();
        formData.append('action', 'update_profile');
        formData.append('name', name);
        formData.append('email', email);
        formData.append('phone', phone);

        const response = await fetch('/coffee-factory-management-system/users/profile_action.php', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            closeProfileSettings();
            showToast('Success! Your profile has been updated.', 'success');
            // Optional: Reload to show new name in header
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast(data.message || 'Update failed', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// Open Change Password Modal
function openChangePassword(event) {
    if (event) event.preventDefault();
    const modal = document.getElementById('changePasswordModal');
    modal.classList.add('show');
    document.getElementById('profileDropdown').classList.remove('show');
}

// Close Change Password Modal
function closeChangePassword() {
    const modal = document.getElementById('changePasswordModal');
    modal.classList.remove('show');
}

// Save New Password
async function saveNewPassword(event) {
    event.preventDefault();
    const btn = event.target.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;

    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (newPassword !== confirmPassword) {
        showToast('Passwords do not match!', 'error');
        return;
    }

    try {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> processing...';

        const formData = new FormData();
        formData.append('action', 'change_password');
        formData.append('current_password', currentPassword);
        formData.append('new_password', newPassword);

        const response = await fetch('/coffee-factory-management-system/users/profile_action.php', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            closeChangePassword();
            showToast('Success! Your password has been changed.', 'success');
            document.getElementById('passwordForm').reset();
        } else {
            showToast(data.message || 'Change failed', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// Toggle Password Visibility
function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const btn = field.nextElementSibling;
    const icon = btn.querySelector('i');

    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Confirm delete actions
async function confirmDelete(event, message = 'Are you sure you want to delete this item?') {
    if (event) event.preventDefault();
    const href = event.target.tagName === 'A' ? event.target.href : event.target.closest('a').href;

    const result = await Swal.fire({
        title: 'Are you sure?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3E2723',
        cancelButtonColor: '#8D6E63',
        confirmButtonText: 'Yes, delete it!'
    });

    if (result.isConfirmed) {
        window.location.href = href;
    }
}

// Form validation helper
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    const inputs = form.querySelectorAll('[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = 'var(--danger)';
            isValid = false;
        } else {
            input.style.borderColor = '';
        }
    });

    return isValid;
}

// Number formatting
function formatNumber(num) {
    return new Intl.NumberFormat().format(num);
}

// Date formatting
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

// Show loading spinner
function showLoading() {
    const loader = document.createElement('div');
    loader.id = 'loading-spinner';
    loader.innerHTML = '<div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;"><div style="background: white; padding: 20px; border-radius: 8px;"><i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i></div></div>';
    document.body.appendChild(loader);
}

// Hide loading spinner
function hideLoading() {
    const loader = document.getElementById('loading-spinner');
    if (loader) loader.remove();
}

// Toast notification
function showToast(message, type = 'info') {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    Toast.fire({
        icon: type === 'error' ? 'error' : (type === 'success' ? 'success' : 'info'),
        title: message
    });
}

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Print function
function printReport() {
    window.print();
}

// Export table to CSV
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];
        cols.forEach(col => {
            csvRow.push('"' + col.textContent.trim() + '"');
        });
        csv.push(csvRow.join(','));
    });

    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}
