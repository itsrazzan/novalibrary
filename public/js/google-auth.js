/**
 * Google OAuth Authentication Handler
 * Handles Google Sign-In button click dan token submission
 */

/**
 * Callback function yang dipanggil setelah Google Sign-In berhasil
 * Function ini akan otomatis dipanggil oleh Google Sign-In library
 * 
 * @param {Object} response - Response dari Google berisi credential (ID token)
 */
function handleGoogleSignIn(response) {
    // Get ID token dari response
    const credential = response.credential;
    
    if (!credential) {
        console.error('No credential received from Google');
        showError('Google authentication failed. Please try again.');
        return;
    }
    
    // Show loading state
    showLoadingState();
    
    // Send credential ke server untuk verification
    sendTokenToServer(credential);
}

/**
 * Send Google ID token ke PHP backend untuk verification
 * 
 * @param {string} credential - Google ID token
 */
function sendTokenToServer(credential) {
    // Create form data
    const formData = new FormData();
    formData.append('credential', credential);
    
    // Send POST request ke google-callback.php
    fetch('../controllers/google-callback.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Check if redirect occurred (successful login)
        if (response.redirected) {
            // Redirect ke dashboard
            window.location.href = response.url;
            return;
        }
        
        // If not redirected, check response
        return response.text();
    })
    .then(data => {
        if (data) {
            // If there's data, it means there was an error
            console.error('Google login error:', data);
            hideLoadingState();
            showError('Google authentication failed. Please try again.');
        }
    })
    .catch(error => {
        console.error('Network error:', error);
        hideLoadingState();
        showError('Network error. Please check your connection and try again.');
    });
}

/**
 * Show loading state saat proses authentication
 */
function showLoadingState() {
    // Disable Google button
    const googleBtn = document.querySelector('.google-btn');
    if (googleBtn) {
        googleBtn.disabled = true;
        googleBtn.style.opacity = '0.6';
        googleBtn.style.cursor = 'not-allowed';
    }
    
    // Optional: Show loading spinner
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.style.opacity = '0.6';
    }
}

/**
 * Hide loading state
 */
function hideLoadingState() {
    // Enable Google button
    const googleBtn = document.querySelector('.google-btn');
    if (googleBtn) {
        googleBtn.disabled = false;
        googleBtn.style.opacity = '1';
        googleBtn.style.cursor = 'pointer';
    }
    
    // Reset form opacity
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.style.opacity = '1';
    }
}

/**
 * Show error message ke user
 * 
 * @param {string} message - Error message
 */
function showError(message) {
    // Check if error container exists
    let errorContainer = document.querySelector('.google-error');
    
    if (!errorContainer) {
        // Create error container if doesn't exist
        errorContainer = document.createElement('div');
        errorContainer.className = 'google-error p-4 bg-red-50 border-l-4 border-red-500 rounded-lg mb-4';
        
        // Insert before login form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.parentNode.insertBefore(errorContainer, loginForm);
        }
    }
    
    // Set error message
    errorContainer.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <p class="text-sm text-red-700">${message}</p>
        </div>
    `;
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        errorContainer.style.transition = 'opacity 0.5s';
        errorContainer.style.opacity = '0';
        setTimeout(() => {
            errorContainer.remove();
        }, 500);
    }, 5000);
}

// Log untuk debugging
console.log('Google Auth handler loaded');
