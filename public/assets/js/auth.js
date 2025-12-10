/**
 * JWT Authentication Utility
 * Handles JWT token storage, retrieval, and automatic inclusion in API requests
 */

const AuthManager = {
    TOKEN_KEY: 'eco_jwt_token',
    
    /**
     * Initialize auth - fetch JWT token after successful login
     */
    async initialize(forceRefresh = false) {
        // Always fetch new token if forceRefresh is true, or if no token exists
        if (forceRefresh || !this.hasToken()) {
            try {
                const response = await fetch('/auth/token', {
                    credentials: 'include' // Send session cookie
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.setToken(data.token);
                    console.log('JWT token obtained successfully');
                    return true;
                }
            } catch (error) {
                console.error('Failed to obtain JWT token:', error);
            }
            return false;
        }
        return true;
    },
    
    /**
     * Store JWT token in localStorage
     */
    setToken(token) {
        localStorage.setItem(this.TOKEN_KEY, token);
    },
    
    /**
     * Get JWT token from localStorage
     */
    getToken() {
        return localStorage.getItem(this.TOKEN_KEY);
    },
    
    /**
     * Check if token exists
     */
    hasToken() {
        return !!this.getToken();
    },
    
    /**
     * Remove JWT token (on logout)
     */
    clearToken() {
        localStorage.removeItem(this.TOKEN_KEY);
    },
    
    /**
     * Get authorization headers with JWT token
     */
    getAuthHeaders() {
        const token = this.getToken();
        if (token) {
            return {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            };
        }
        return {
            'Content-Type': 'application/json'
        };
    },
    
    /**
     * Enhanced fetch with automatic JWT token inclusion
     */
    fetch: async function(url, options = {}) {
        // Ensure we have a token
        if (!AuthManager.hasToken()) {
            await AuthManager.initialize();
        }
        
        // Merge auth headers
        const headers = {
            ...AuthManager.getAuthHeaders(),
            ...(options.headers || {})
        };
        
        // Make the request
        const response = await window.fetch(url, {
            ...options,
            headers
        });
        
        // Handle 401 - token expired or invalid
        if (response.status === 401) {
            AuthManager.clearToken();
            
            // Try to get a new token once
            const retryInit = await AuthManager.initialize();
            if (retryInit) {
                // Retry the request with new token
                const retryHeaders = {
                    ...AuthManager.getAuthHeaders(),
                    ...(options.headers || {})
                };
                
                return window.fetch(url, {
                    ...options,
                    headers: retryHeaders
                });
            } else {
                // Redirect to login if we can't get a new token
                console.error('Authentication failed, redirecting to login');
                window.location.href = '/login';
                throw new Error('Authentication required');
            }
        }
        
        return response;
    },
    
    /**
     * Logout - clear token and redirect
     */
    logout() {
        this.clearToken();
        window.location.href = '/logout';
    }
};

// Initialize on page load (skip on login/register pages)
document.addEventListener('DOMContentLoaded', async () => {
    const isPublicPage = window.location.pathname === '/login' || 
                         window.location.pathname === '/register' ||
                         window.location.pathname.startsWith('/reset-password');
    
    if (!isPublicPage) {
        // Force refresh token to ensure it matches the current session user
        await AuthManager.initialize(true);
    }
});

// Also initialize on Turbo navigation
document.addEventListener('turbo:load', async () => {
    const isPublicPage = window.location.pathname === '/login' || 
                         window.location.pathname === '/register' ||
                         window.location.pathname.startsWith('/reset-password');
    
    if (!isPublicPage) {
        // Force refresh token to ensure it matches the current session user
        await AuthManager.initialize(true);
    }
});

// Export for use in other scripts
window.AuthManager = AuthManager;
