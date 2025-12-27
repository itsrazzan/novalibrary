/**
 * Register.js - Registration Form Handler
 * Handles client-side validation and UX enhancements
 */

// ============================================
// PASSWORD VISIBILITY TOGGLE
// ============================================

/**
 * Toggle password visibility untuk field password
 */
const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');
const eyeIcon = document.getElementById('eyeIcon');

if (togglePassword && passwordInput) {
    togglePassword.addEventListener('click', () => {
        // Toggle type antara 'password' dan 'text'
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.type = type;
        
        // Update icon mata
        updateEyeIcon(eyeIcon, type);
    });
}

/**
 * Toggle password visibility untuk field confirm password
 */
const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
const confirmPasswordInput = document.getElementById('confirm_password');
const eyeIconConfirm = document.getElementById('eyeIconConfirm');

if (toggleConfirmPassword && confirmPasswordInput) {
    toggleConfirmPassword.addEventListener('click', () => {
        const type = confirmPasswordInput.type === 'password' ? 'text' : 'password';
        confirmPasswordInput.type = type;
        updateEyeIcon(eyeIconConfirm, type);
    });
}

/**
 * Update icon mata berdasarkan visibility state
 * @param {HTMLElement} icon - SVG icon element
 * @param {string} type - 'password' atau 'text'
 */
function updateEyeIcon(icon, type) {
    if (!icon) return;
    
    if (type === 'password') {
        // Icon mata terbuka (password hidden)
        icon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        `;
    } else {
        // Icon mata tertutup (password visible)
        icon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
        `;
    }
}

// ============================================
// PASSWORD STRENGTH CHECKER
// ============================================

/**
 * Check password strength dan update visual indicator
 */
const passwordStrength = document.getElementById('passwordStrength');
const strengthText = document.getElementById('strengthText');

if (passwordInput && passwordStrength && strengthText) {
    passwordInput.addEventListener('input', (e) => {
        const password = e.target.value;
        const strength = checkPasswordStrength(password);

        // Update progress bar
        passwordStrength.style.width = strength.percentage + '%';
        passwordStrength.style.backgroundColor = strength.color;
        
        // Update text
        strengthText.textContent = strength.text;
        strengthText.style.color = strength.color;
    });
}

/**
 * Calculate password strength berdasarkan kriteria
 * @param {string} password - Password yang akan dicek
 * @returns {Object} - {percentage, color, text}
 */
function checkPasswordStrength(password) {
    let strength = 0;
    
    // Kriteria 1: Panjang minimal 8 karakter
    if (password.length >= 8) strength += 25;
    
    // Kriteria 2: Panjang 10+ karakter (bonus)
    if (password.length >= 10) strength += 25;
    
    // Kriteria 3: Kombinasi huruf besar dan kecil
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
    
    // Kriteria 4: Mengandung angka
    if (/[0-9]/.test(password)) strength += 15;
    
    // Kriteria 5: Mengandung karakter spesial
    if (/[^a-zA-Z0-9]/.test(password)) strength += 10;

    // Return hasil berdasarkan total strength
    if (strength <= 25) {
        return { percentage: 25, color: '#ef4444', text: 'Password lemah' };
    } else if (strength <= 50) {
        return { percentage: 50, color: '#f59e0b', text: 'Password cukup' };
    } else if (strength <= 75) {
        return { percentage: 75, color: '#3b82f6', text: 'Password baik' };
    } else {
        return { percentage: 100, color: '#10b981', text: 'Password kuat' };
    }
}

// ============================================
// FORM VALIDATION (CLIENT-SIDE)
// ============================================

/**
 * Client-side validation sebelum submit
 * Note: Server-side validation tetap dilakukan di signup.php
 */
const registerForm = document.getElementById('registerForm');

if (registerForm) {
    registerForm.addEventListener('submit', (e) => {
        // Get form values
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        // Check password match
        if (password !== confirmPassword) {
            e.preventDefault(); // Stop form submission
            
            // Show error message
            alert('Password dan konfirmasi password tidak cocok!');
            
            // Focus ke confirm password field
            document.getElementById('confirm_password').focus();
            
            return false;
        }
        
        // Jika validasi lolos, form akan submit ke server
        // Server-side validation di signup.php akan handle sisanya
        return true;
    });
}

// ============================================
// AUTO-DISMISS ERROR MESSAGES
// ============================================

/**
 * Auto-hide error/success messages setelah 5 detik
 */
window.addEventListener('DOMContentLoaded', () => {
    const errorMessage = document.querySelector('.bg-red-50');
    const successMessage = document.querySelector('.bg-green-50');
    
    // Auto-dismiss error message
    if (errorMessage) {
        setTimeout(() => {
            errorMessage.style.transition = 'opacity 0.5s';
            errorMessage.style.opacity = '0';
            setTimeout(() => {
                errorMessage.remove();
            }, 500);
        }, 5000); // 5 seconds
    }
    
    // Auto-dismiss success message
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.transition = 'opacity 0.5s';
            successMessage.style.opacity = '0';
            setTimeout(() => {
                successMessage.remove();
            }, 500);
        }, 5000); // 5 seconds
    }
});

// ============================================
// CONSOLE LOG (DEVELOPMENT)
// ============================================

console.log('Register.js loaded successfully');
