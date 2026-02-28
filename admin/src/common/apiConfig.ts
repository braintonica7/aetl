/**
 * API Configuration for Admin Panel
 * Centralized API URL configuration for the entire admin application
 */

// API Configuration Function
export const getApiUrl = () => {
    let hostname = window.location.host;
    
    // Development environment detection - use proxy to avoid CORS
    if (hostname.includes("localhost") || hostname.includes("127.0.0.1")) {
        // Use Vite proxy during development (no CORS preflight!)
        return "/api-proxy/index.php/api/";
    } else {
        // Production environment - direct API call
        return "https://api.wiziai.com/index.php/api/";
    }
};

// Export the API URLs
export const APIUrl = getApiUrl();
export const AI_API_URL = "https://rhythm-api-h4b2cheheaf5ftgr.centralindia-01.azurewebsites.net/";

// Export default configuration object
const apiConfig = {
    APIUrl,
    AI_API_URL,
    getApiUrl
};

export default apiConfig;
