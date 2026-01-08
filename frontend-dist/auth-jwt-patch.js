/**
 * OnliFin JWT Authentication Patch
 * 
 * This patch intercepts the authentication flow to:
 * 1. Handle the new auth.login JSON response format (with JWT token)
 * 2. Store JWT token in localStorage
 * 3. Inject JWT token into all API requests via Authorization header
 * 4. Handle token expiration and auto-logout
 */

(function () {
    'use strict';

    console.log('🔐 OnliFin JWT Auth Patch v1.0 - Loading...');

    // Store original fetch
    const originalFetch = window.fetch;

    // JWT Token storage key
    const TOKEN_KEY = 'onlifin_jwt_token';
    const USER_DATA_KEY = 'onlifin_user_data';

    /**
     * Get stored JWT token
     */
    function getToken() {
        try {
            return localStorage.getItem(TOKEN_KEY);
        } catch (e) {
            console.error('Failed to get token from localStorage', e);
            return null;
        }
    }

    /**
     * Store JWT token
     */
    function setToken(token) {
        try {
            localStorage.setItem(TOKEN_KEY, token);
            console.log('✅ JWT token stored');
        } catch (e) {
            console.error('Failed to store token in localStorage', e);
        }
    }

    /**
     * Remove JWT token (logout)
     */
    function clearToken() {
        try {
            localStorage.removeItem(TOKEN_KEY);
            localStorage.removeItem(USER_DATA_KEY);
            console.log('🚪 JWT token cleared (logout)');
        } catch (e) {
            console.error('Failed to clear token', e);
        }
    }

    /**
     * Store user data
     */
    function setUserData(data) {
        try {
            localStorage.setItem(USER_DATA_KEY, JSON.stringify(data));
        } catch (e) {
            console.error('Failed to store user data', e);
        }
    }

    /**
     * Get user data
     */
    function getUserData() {
        try {
            const data = localStorage.getItem(USER_DATA_KEY);
            return data ? JSON.parse(data) : null;
        } catch (e) {
            console.error('Failed to get user data', e);
            return null;
        }
    }

    /**
     * Check if token is expired
     */
    function isTokenExpired(token) {
        if (!token) return true;

        try {
            // Decode JWT payload (middle part)
            const parts = token.split('.');
            if (parts.length !== 3) return true;

            const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
            const now = Math.floor(Date.now() / 1000);

            return payload.exp && payload.exp < now;
        } catch (e) {
            console.error('Failed to decode token', e);
            return true;
        }
    }

    /**
     * Intercept fetch to add JWT token to requests
     */
    window.fetch = function (url, options = {}) {
        // Check if this is an API request
        const isApiRequest = typeof url === 'string' &&
            (url.includes('/api/rest/') || url.includes('/api/rpc/'));

        if (isApiRequest) {
            const token = getToken();

            // Check for expired token
            if (token && isTokenExpired(token)) {
                console.warn('⚠️ Token expired, clearing...');
                clearToken();
                // Optionally redirect to login
                if (window.location.pathname !== '/login') {
                    console.log('🔄 Redirecting to login...');
                    window.location.href = '/login';
                }
                return Promise.reject(new Error('Token expired'));
            }

            // Add Authorization header if we have a token
            if (token) {
                options.headers = options.headers || {};

                // Handle Headers object or plain object
                if (options.headers instanceof Headers) {
                    options.headers.set('Authorization', `Bearer ${token}`);
                } else {
                    options.headers['Authorization'] = `Bearer ${token}`;
                }

                console.log('🔑 Added JWT token to request:', url.substring(0, 50) + '...');
            } else {
                console.log('⚠️ No JWT token available for request:', url.substring(0, 50) + '...');
            }
        }

        // Call original fetch
        return originalFetch(url, options)
            .then(async response => {
                // Handle 401/403 errors (unauthorized/forbidden)
                if (isApiRequest && (response.status === 401 || response.status === 403)) {
                    console.warn(`⚠️ Auth error ${response.status}, clearing token`);
                    clearToken();

                    // Only redirect if not already on login page
                    if (window.location.pathname !== '/login') {
                        console.log('🔄 Redirecting to login due to auth error...');
                        // Small delay to allow user to see the error
                        setTimeout(() => {
                            window.location.href = '/login';
                        }, 1000);
                    }
                }

                // Intercept auth.login responses
                if (typeof url === 'string' && url.includes('/rpc/login')) {
                    const clonedResponse = response.clone();

                    try {
                        const data = await clonedResponse.json();
                        console.log('📨 Login response:', data);

                        // Handle new JSON response format
                        if (data && data.success && data.token) {
                            console.log('✅ Login successful, storing JWT token');
                            setToken(data.token);
                            setUserData({
                                user_id: data.user_id,
                                role: data.role
                            });
                        } else if (data && !data.success) {
                            console.error('❌ Login failed:', data.error);
                            clearToken();
                        }
                    } catch (e) {
                        console.error('Failed to parse login response', e);
                    }
                }

                return response;
            })
            .catch(error => {
                console.error('Fetch error:', error);
                throw error;
            });
    };

    // Check for existing token on load
    const existingToken = getToken();
    if (existingToken) {
        if (isTokenExpired(existingToken)) {
            console.log('⚠️ Existing token is expired, clearing...');
            clearToken();
        } else {
            console.log('✅ Valid JWT token found in storage');
            const userData = getUserData();
            if (userData) {
                console.log('👤 User data:', userData);
            }
        }
    } else {
        console.log('ℹ️ No existing JWT token found');
    }

    // Expose helper functions to window for debugging
    window.onlifinAuth = {
        getToken,
        setToken,
        clearToken,
        getUserData,
        isTokenExpired,
        logout: () => {
            clearToken();
            window.location.href = '/login';
        }
    };

    console.log('✅ OnliFin JWT Auth Patch loaded successfully');
    console.log('💡 Debug commands: window.onlifinAuth.getToken(), window.onlifinAuth.logout()');
})();
