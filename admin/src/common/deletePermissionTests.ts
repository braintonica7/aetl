/**
 * Test utility for role-based delete restrictions
 * This file can be used to manually test the role-based functionality
 * 
 * Usage in browser console:
 * 1. Open browser developer tools
 * 2. Go to Console tab
 * 3. Run these commands to test different scenarios
 */

// Test current user's role and delete permissions
const testCurrentUserDeletePermissions = () => {
    const roleId = localStorage.getItem("role_id");
    const isAdmin = roleId === "1";
    
    console.log("=== Role-Based Delete Permission Test ===");
    console.log(`User role_id: ${roleId}`);
    console.log(`Is Admin: ${isAdmin}`);
    console.log(`Can Delete: ${isAdmin}`);
    console.log("=========================================");
    
    return {
        roleId,
        isAdmin,
        canDelete: isAdmin
    };
};

// Simulate different role scenarios (for testing purposes only)
const simulateRole = (roleId) => {
    const originalRole = localStorage.getItem("role_id");
    localStorage.setItem("role_id", roleId.toString());
    
    console.log(`=== Simulating role_id: ${roleId} ===`);
    testCurrentUserDeletePermissions();
    
    // Restore original role
    if (originalRole) {
        localStorage.setItem("role_id", originalRole);
    }
    
    console.log("Role restored to original value");
};

// Test admin role
const testAdminRole = () => {
    console.log("Testing Admin Role (role_id = 1):");
    simulateRole(1);
};

// Test non-admin role  
const testNonAdminRole = () => {
    console.log("Testing Non-Admin Role (role_id = 2):");
    simulateRole(2);
};

// Export for console usage
if (typeof window !== 'undefined') {
    (window as any).testDeletePermissions = {
        testCurrentUserDeletePermissions,
        testAdminRole,
        testNonAdminRole,
        simulateRole
    };
    
    console.log("Delete permission test utilities loaded!");
    console.log("Available functions:");
    console.log("- testDeletePermissions.testCurrentUserDeletePermissions()");
    console.log("- testDeletePermissions.testAdminRole()");
    console.log("- testDeletePermissions.testNonAdminRole()");
}

export {
    testCurrentUserDeletePermissions,
    testAdminRole,
    testNonAdminRole,
    simulateRole
};
