# Role-Based Delete Restrictions Implementation

## Overview
This implementation restricts delete functionality to only users with `role_id = 1` (admin users) across all screens in the admin panel.

## Implementation Details

### Files Modified

1. **`/admin/src/common/roleUtils.ts`** - New utility file
   - Added `isAdminUser()` function
   - Added `canDelete()` function  
   - Added `getCurrentUserRoleId()` function
   - Added `hasPermission()` function
   - Added `processPermissions()` function for consistent permission handling

2. **`/admin/src/common/FormToolbar.tsx`**
   - Added role-based delete restriction: `if (props.hasDelete && canDelete())`
   - Import: `import { canDelete } from './roleUtils';`

3. **`/admin/src/screens/users/UsersList.tsx`**
   - Added role-based delete restriction in permission processing
   - Import: `import { canDelete, processPermissions } from "../../common/roleUtils";`
   - Updated permission logic: `propsObj.hasDelete = (p.Delete == 1) && canDelete();`

4. **`/admin/src/screens/users/UsersEdit.tsx`**
   - Added role-based delete restriction in permission processing
   - Import: `import { canDelete, processPermissions } from "../../common/roleUtils";`
   - Updated to use `processPermissions()` utility function

## How It Works

### Delete Restriction Logic
```typescript
// Only admin users (role_id = 1) can delete
export const canDelete = (): boolean => {
    const userRoleId = localStorage.getItem("role_id");
    const roleId = userRoleId ? parseInt(userRoleId) : null;
    return roleId === 1;
};
```

### FormToolbar Integration
The `FormToolbar` component now checks both:
1. The `hasDelete` prop (from menu permissions)
2. The user's role via `canDelete()` function

```typescript
// Role-based delete restriction: Only admin users (role_id = 1) can delete
if (props.hasDelete && canDelete()) {
    showDelete = props.hasDelete;
}
```

### Screen Components Affected

#### Edit Components (using FormToolbar):
- `/screens/users/UsersEdit.tsx` ✅ Updated
- `/screens/subject/SubjectEdit.tsx` ✅ Protected via FormToolbar
- `/screens/exam/ExamEdit.tsx` ✅ Protected via FormToolbar  
- `/screens/chapter/ChapterEdit.tsx` ✅ Protected via FormToolbar
- `/screens/topic/TopicEdit.tsx` ✅ Protected via FormToolbar
- `/screens/question/QuestionEdit.tsx` ✅ Protected via FormToolbar

#### List Components:
- `/screens/users/UsersList.tsx` ✅ Updated with role-based logic
- Other List components (ChapterList, QuestionList, etc.) ✅ Don't have delete buttons

## Role-Based Access Matrix

| User Role | role_id | Delete Access | Description |
|-----------|---------|---------------|-------------|
| Admin     | 1       | ✅ Yes        | Full delete access to all screens |
| Other     | ≠ 1     | ❌ No         | No delete access to any screen |

## Screen-Specific Behavior

### For Admin Users (role_id = 1):
- ✅ Delete buttons visible in edit forms
- ✅ Delete functionality works normally
- ✅ All existing behavior preserved

### For Non-Admin Users (role_id ≠ 1):
- ❌ Delete buttons hidden in edit forms
- ❌ Delete functionality blocked
- ✅ All other functionality (create, edit, view) works normally

## Testing Instructions

### Test Admin User Delete Access:
1. Login with admin credentials (role_id = 1)
2. Navigate to any edit screen (User Edit, Question Edit, etc.)
3. ✅ Verify delete button is visible
4. ✅ Verify delete functionality works

### Test Non-Admin User Delete Restrictions:
1. Login with non-admin credentials (role_id ≠ 1)  
2. Navigate to any edit screen
3. ❌ Verify delete button is hidden
4. ✅ Verify save and other buttons still work

### Debug Information:
Check browser console for role information:
- FormToolbar will show whether delete is allowed
- Menu component shows role-based filtering

## Security Notes

⚠️ **Important**: This is UI-level restriction only. Ensure backend APIs also validate:
- User's role_id before allowing delete operations
- Proper authentication tokens
- Permission checks on all delete endpoints

## Code Locations

### Edit Components with FormToolbar:
```typescript
// All these components now respect role-based delete restrictions
<SimpleForm toolbar={<FormToolbar {...props} hasDelete={true}/>}>
```

### Permission Processing:
```typescript
// UsersList and UsersEdit use the processPermissions utility
const propsObj = processPermissions(permissions, resource);
```

### Role Checking:
```typescript
// FormToolbar checks canDelete() before showing delete button
if (props.hasDelete && canDelete()) {
    showDelete = props.hasDelete;
}
```

## Future Enhancements

1. **Granular Permissions**: Add different permission levels for different resources
2. **Audit Logging**: Log delete attempts for security monitoring  
3. **Soft Deletes**: Implement soft delete functionality for data recovery
4. **Bulk Operations**: Extend restrictions to bulk delete operations

## Maintenance

- **Adding New Screens**: New edit components using FormToolbar automatically inherit delete restrictions
- **Role Changes**: Users need to logout/login for role changes to take effect
- **Permission Updates**: Modify `canDelete()` function to change delete access logic
