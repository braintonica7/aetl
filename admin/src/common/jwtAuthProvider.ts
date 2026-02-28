import { AuthProvider } from 'react-admin';
import { adminJWTLogin, adminJWTLogout, adminJWTRefresh } from './apiClient';

interface JWTTokens {
    access_token: string;
    refresh_token: string;
    expires_in: number;
    refresh_expires_in: number;
}

interface AuthResponse {
    status: string;
    result: {
        user: any;
        access_token: string;
        refresh_token: string;
        token_type: string;
        expires_in: number;
        refresh_expires_in: number;
        is_admin?: boolean;
        admin_permissions?: string[];
    };
    message: string;
}

class JWTAuthService {
    private static instance: JWTAuthService;
    private refreshTokenPromise: Promise<string> | null = null;

    static getInstance(): JWTAuthService {
        if (!JWTAuthService.instance) {
            JWTAuthService.instance = new JWTAuthService();
        }
        return JWTAuthService.instance;
    }

    getTokens(): JWTTokens | null {
        const accessToken = localStorage.getItem('jwt_access_token');
        const refreshToken = localStorage.getItem('jwt_refresh_token');
        const expiresIn = localStorage.getItem('jwt_expires_in');
        const refreshExpiresIn = localStorage.getItem('jwt_refresh_expires_in');

        if (accessToken && refreshToken) {
            return {
                access_token: accessToken,
                refresh_token: refreshToken,
                expires_in: expiresIn ? parseInt(expiresIn) : 3600,
                refresh_expires_in: refreshExpiresIn ? parseInt(refreshExpiresIn) : 604800
            };
        }
        return null;
    }

    setTokens(tokens: JWTTokens): void {
        const now = Date.now();
        const accessTokenExpiry = now + (tokens.expires_in * 1000);
        const refreshTokenExpiry = now + (tokens.refresh_expires_in * 1000);

        localStorage.setItem('jwt_access_token', tokens.access_token);
        localStorage.setItem('jwt_refresh_token', tokens.refresh_token);
        localStorage.setItem('jwt_expires_in', tokens.expires_in.toString());
        localStorage.setItem('jwt_refresh_expires_in', tokens.refresh_expires_in.toString());
        localStorage.setItem('jwt_access_token_expiry', accessTokenExpiry.toString());
        localStorage.setItem('jwt_refresh_token_expiry', refreshTokenExpiry.toString());
    }

    clearTokens(): void {
        localStorage.removeItem('jwt_access_token');
        localStorage.removeItem('jwt_refresh_token');
        localStorage.removeItem('jwt_expires_in');
        localStorage.removeItem('jwt_refresh_expires_in');
        localStorage.removeItem('jwt_access_token_expiry');
        localStorage.removeItem('jwt_refresh_token_expiry');
        localStorage.removeItem('username');
        localStorage.removeItem('display_name');
        localStorage.removeItem('role_id');
        localStorage.removeItem('user_id');
        localStorage.removeItem('role');
        localStorage.removeItem('menu');
        localStorage.removeItem('admin_permissions');
    }

    isTokenExpired(token: string, expiry: string): boolean {
        if (!expiry) return true;
        const expiryTime = parseInt(expiry);
        const now = Date.now();
        const bufferTime = 5 * 60 * 1000; // 5 minutes buffer
        return now >= (expiryTime - bufferTime);
    }

    async getValidAccessToken(): Promise<string | null> {
        const tokens = this.getTokens();
        if (!tokens) return null;

        const accessTokenExpiry = localStorage.getItem('jwt_access_token_expiry');
        
        // Check if access token is still valid
        if (!this.isTokenExpired(tokens.access_token, accessTokenExpiry || '')) {
            return tokens.access_token;
        }

        // Try to refresh the token
        return this.refreshAccessToken();
    }

    async refreshAccessToken(): Promise<string | null> {
        // Prevent multiple simultaneous refresh requests
        if (this.refreshTokenPromise) {
            return this.refreshTokenPromise;
        }

        const tokens = this.getTokens();
        if (!tokens) return null;

        const refreshTokenExpiry = localStorage.getItem('jwt_refresh_token_expiry');
        
        // Check if refresh token is expired
        if (this.isTokenExpired(tokens.refresh_token, refreshTokenExpiry || '')) {
            this.clearTokens();
            return null;
        }

        this.refreshTokenPromise = this.performTokenRefresh(tokens.refresh_token);
        
        try {
            const newAccessToken = await this.refreshTokenPromise;
            return newAccessToken;
        } finally {
            this.refreshTokenPromise = null;
        }
    }

    private async performTokenRefresh(refreshToken: string): Promise<string | null> {
        try {
            const response = await adminJWTRefresh({ refresh_token: refreshToken });
            
            if (response.status === 'success' && response.result) {
                const newTokens: JWTTokens = {
                    access_token: response.result.access_token,
                    refresh_token: response.result.refresh_token,
                    expires_in: response.result.expires_in,
                    refresh_expires_in: response.result.refresh_expires_in
                };
                
                this.setTokens(newTokens);
                return response.result.access_token;
            } else {
                this.clearTokens();
                return null;
            }
        } catch (error) {
            console.error('Token refresh failed:', error);
            this.clearTokens();
            return null;
        }
    }
}

const jwtAuthService = JWTAuthService.getInstance();

const jwtAuthProvider: AuthProvider = {
    login: async ({ username, password }) => {
        try {
            const response: AuthResponse = await adminJWTLogin({ 
                username, 
                password, 
                device_info: 'Admin Panel Web'
            });
            
            if (response.status === 'success' && response.result) {
                const { user, access_token, refresh_token, expires_in, refresh_expires_in, admin_permissions } = response.result;
                
                // Store JWT tokens
                jwtAuthService.setTokens({
                    access_token,
                    refresh_token,
                    expires_in,
                    refresh_expires_in
                });
                
                // Store user information
                localStorage.setItem('username', user.username);
                localStorage.setItem('display_name', user.display_name || username);
                localStorage.setItem('role_id', user.role_id?.toString() || '');
                localStorage.setItem('user_id', user.id?.toString() || '');
                localStorage.setItem('role', user.role || '');
                
                // Store admin permissions if available
                if (admin_permissions) {
                    localStorage.setItem('admin_permissions', JSON.stringify(admin_permissions));
                }
                
                return Promise.resolve();
            } else {
                return Promise.reject(new Error(response.message || 'JWT login failed'));
            }
        } catch (error: any) {
            console.error('JWT Login Error:', error);
            return Promise.reject(new Error(error.message || 'Network error during JWT login'));
        }
    },

    logout: async () => {
        try {
            const accessToken = await jwtAuthService.getValidAccessToken();
            if (accessToken) {
                // Call logout API to revoke session
                await adminJWTLogout();
            }
        } catch (error) {
            console.error('Logout API error:', error);
        } finally {
            // Always clear local tokens
            jwtAuthService.clearTokens();
        }
        return Promise.resolve();
    },

    checkError: (error: any) => {
        const status = error.status || error.response?.status;
        if (status === 401 || status === 403) {
            // Token expired or invalid
            jwtAuthService.clearTokens();
            return Promise.reject();
        }
        return Promise.resolve();
    },

    checkAuth: async () => {
        try {
            const accessToken = await jwtAuthService.getValidAccessToken();
            return accessToken ? Promise.resolve() : Promise.reject();
        } catch (error) {
            console.error('Auth check failed:', error);
            return Promise.reject();
        }
    },

    getPermissions: () => {
        try {
            const permissionsStr = localStorage.getItem('admin_permissions');
            const permissions = permissionsStr ? JSON.parse(permissionsStr) : [];
            return Promise.resolve(permissions);
        } catch (error) {
            console.error('Error getting permissions:', error);
            return Promise.resolve([]);
        }
    },

    getIdentity: () => {
        const username = localStorage.getItem('username');
        const displayName = localStorage.getItem('display_name');
        
        if (username) {
            return Promise.resolve({
                id: username,
                fullName: displayName || username,
                avatar: undefined // You can add avatar support later
            });
        }
        
        return Promise.reject();
    },
};

// Export both the service and provider for use in API calls
export { jwtAuthService };
export default jwtAuthProvider;
