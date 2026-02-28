/**
 * Role-based utility functions for admin panel
 */

/**
 * Check if the current user is an admin (role_id = 1)
 * @returns boolean - true if user is admin, false otherwise
 */
export const isAdminUser = (): boolean => {
    const userRoleId = localStorage.getItem("role_id");
    const roleId = userRoleId ? parseInt(userRoleId) : null;
    const isAdmin = roleId === 1;
    
    console.log(`isAdminUser check - role_id from localStorage: ${userRoleId}, parsed roleId: ${roleId}, Is Admin: ${isAdmin}`);
    
    return isAdmin;
};

/**
 * Check if the current user can perform delete operations
 * Only admin users (role_id = 1) can delete
 * @returns boolean - true if user can delete, false otherwise
 */
export const canDelete = (): boolean => {
    return isAdminUser();
};

/**
 * Get the current user's role ID
 * @returns number | null - the role ID or null if not found
 */
export const getCurrentUserRoleId = (): number | null => {
    const userRoleId = localStorage.getItem("role_id");
    return userRoleId ? parseInt(userRoleId) : null;
};

/**
 * Check if user has specific permission based on role
 * @param permission - the permission type ('create', 'edit', 'delete', 'view', 'export')
 * @returns boolean - true if user has permission
 */
export const hasPermission = (permission: 'create' | 'edit' | 'delete' | 'view' | 'export'): boolean => {
    const roleId = getCurrentUserRoleId();
    
    // Admin users have all permissions
    if (roleId === 1) {
        return true;
    }
    
    // Non-admin users cannot delete
    if (permission === 'delete') {
        return false;
    }
    
    // For other permissions, non-admin users have basic access
    // (this can be expanded based on specific role requirements)
    return true;
};

/**
 * Process permissions from menu items and apply role-based restrictions
 * @param permissions - array of menu permissions
 * @param resource - the resource name (e.g., '/user', '/question')
 * @returns processed permissions object with role-based restrictions applied
 */
export const processPermissions = (permissions: any[], resource: string): any => {
    const propsObj: any = {};
    
    // Check if user is admin
    const isAdmin = isAdminUser();
    
    if (permissions && Array.isArray(permissions)) {
        const resourcename = `/${resource}`;
        const myPermissions = permissions.filter(item => 
            item && 
            item.NavigateUrl && 
            typeof item.NavigateUrl === 'string' && 
            item.NavigateUrl.toLowerCase().trim() === resourcename
        );
        
        if (myPermissions.length > 0) {
            const p = myPermissions[0];
            propsObj.hasList = (p.View == 1) ? true : false;
            propsObj.hasShow = (p.View == 1) ? true : false;
            propsObj.hasEdit = (p.Edit == 1) ? true : false;
            // Role-based delete restriction: Only admin users (role_id = 1) can delete
            propsObj.hasDelete = (p.Delete == 1) && canDelete();
            propsObj.hasCreate = (p.Create == 1) ? true : false;
            propsObj.hasExport = (p.Export == 1) ? true : false;
        } else if (isAdmin) {
            // If no specific permissions found but user is admin, grant all permissions
            //console.log(`No specific permissions found for resource ${resourcename}, but user is admin - granting full access`);
            propsObj.hasList = true;
            propsObj.hasShow = true;
            propsObj.hasEdit = true;
            propsObj.hasDelete = true;
            propsObj.hasCreate = true;
            propsObj.hasExport = true;
        }
    } else if (isAdmin) {
        // If permissions array is null/undefined but user is admin, grant all permissions
        //console.log('Permissions array is null/undefined, but user is admin - granting full access');
        propsObj.hasList = true;
        propsObj.hasShow = true;
        propsObj.hasEdit = true;
        propsObj.hasDelete = true;
        propsObj.hasCreate = true;
        propsObj.hasExport = true;
    }
    
    return propsObj;
};
