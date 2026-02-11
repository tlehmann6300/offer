# Microsoft Entra ID Login Integration

This document describes the Microsoft Entra ID (Azure AD) OAuth login integration added to the AuthHandler class.

## Overview

The integration allows users to log in using their Microsoft Entra ID (formerly Azure Active Directory) credentials. The system automatically maps Azure roles to internal application roles and creates or updates user accounts accordingly.

## Configuration

The following environment variables must be configured in the `.env` file:

```
AZURE_CLIENT_ID="your-client-id"
AZURE_CLIENT_SECRET="your-client-secret"
AZURE_REDIRECT_URI="https://your-domain.com/auth/callback.php"
AZURE_TENANT_ID="your-tenant-id"
```

These values are automatically loaded by `config/config.php` and made available as constants.

## Azure App Registration Setup

1. Register an application in Azure Portal (https://portal.azure.com)
2. Under "Authentication", add a redirect URI matching `AZURE_REDIRECT_URI`
3. Under "API Permissions", add the following Microsoft Graph permissions:
   - `openid` (delegated)
   - `profile` (delegated)
   - `email` (delegated)
   - `offline_access` (delegated)
   - `User.Read` (delegated)
4. Create a client secret and copy the value to `AZURE_CLIENT_SECRET`
5. Copy the Application (client) ID to `AZURE_CLIENT_ID`
6. Copy the Directory (tenant) ID to `AZURE_TENANT_ID`

## Role Mapping

The system maps Azure role names to internal application roles. Azure roles must be configured in Azure AD as app roles and assigned to users.

| Azure Role Name | Internal Role | Priority |
|----------------|---------------|----------|
| anwaerter | candidate | 1 |
| mitglied | member | 2 |
| ressortleiter | head | 3 |
| alumni | alumni | 4 |
| ehrenmitglied | honorary_member | 5 |
| vorstand_finanzen | board_finance | 6 |
| vorstand_intern | board_internal | 7 |
| vorstand_extern | board_external | 8 |
| alumni_vorstand | alumni_board | 9 |
| alumni_finanz | alumni_auditor | 10 |

**Note:** Azure role names do not contain umlauts (ä, ö, ü) for technical compatibility. 'anwaerter' is used instead of 'anwärter'.

### Priority Selection

If a user has multiple Azure roles, the system selects the role with the highest priority (highest number in the table above). For example, if a user has both 'mitglied' and 'vorstand_finanzen', they will be assigned the 'board_finance' role.

## Usage

### Method 1: initiateMicrosoftLogin()

Initiates the OAuth login flow by redirecting the user to Microsoft's authorization page.

```php
// Example: In your login page
try {
    AuthHandler::initiateMicrosoftLogin();
    // User is redirected to Microsoft login page
} catch (Exception $e) {
    echo "Login error: " . $e->getMessage();
}
```

**What it does:**
1. Validates Azure configuration constants
2. Creates an Azure OAuth provider instance
3. Generates an authorization URL with the required scopes
4. Stores the OAuth state in the session for CSRF protection
5. Redirects the user to Microsoft's login page

### Method 2: handleMicrosoftCallback()

Handles the OAuth callback after the user authenticates with Microsoft.

```php
// Example: In your callback page (auth/callback.php)
try {
    AuthHandler::handleMicrosoftCallback();
    // User is logged in and redirected to dashboard
} catch (Exception $e) {
    echo "Authentication error: " . $e->getMessage();
}
```

**What it does:**
1. Validates the OAuth state parameter for CSRF protection
2. Exchanges the authorization code for an access token
3. Retrieves user details from Microsoft Graph API
4. Extracts Azure roles from user claims
5. Maps Azure roles to internal application roles
6. Creates a new user account or updates an existing one
7. Sets up the user session with appropriate variables
8. Logs the login attempt
9. Redirects to the dashboard

## Security Features

- **CSRF Protection**: Uses OAuth state parameter to prevent cross-site request forgery attacks
- **Secure Session Management**: Follows the same secure session practices as regular login
- **Audit Logging**: All Microsoft login attempts (successful and failed) are logged to system_logs
- **OAuth 2.0 Authorization Code Flow**: Uses the most secure OAuth flow for web applications
- **Random Password Generation**: New users created via OAuth get a secure random password hash (they cannot log in with password, only OAuth)

## Example Integration

### Login Page

Add a "Sign in with Microsoft" button to your login page:

```php
<form action="/" method="post">
    <!-- Regular login form fields -->
</form>

<hr>

<form action="/test_microsoft_login.php" method="post">
    <button type="submit">Sign in with Microsoft</button>
</form>
```

### Callback Handler

Create a callback handler at the redirect URI:

```php
<?php
// auth/callback.php
require_once __DIR__ . '/../includes/handlers/AuthHandler.php';

try {
    AuthHandler::handleMicrosoftCallback();
} catch (Exception $e) {
    // Handle error - redirect to login with error message
    header('Location: /pages/auth/login.php?error=' . urlencode($e->getMessage()));
    exit;
}
```

## Testing

Test files are provided for manual testing:

- `test_microsoft_login.php`: Initiates the OAuth flow
- `test_microsoft_callback.php`: Handles the callback (use as redirect URI for testing)
- `test_microsoft_entra_integration.php`: Runs basic integration tests

To test:
1. Configure Azure credentials in `.env`
2. Access `test_microsoft_login.php` in your browser
3. Log in with Microsoft credentials
4. You should be redirected back and logged in

## Troubleshooting

### "Missing Azure OAuth configuration"
- Ensure all four Azure constants are defined in `.env` file
- Check that `config/config.php` is properly loading the environment variables

### "Invalid state parameter"
- This is a CSRF protection measure
- Ensure cookies are enabled in the browser
- Check that session is working properly

### "OAuth error: access_denied"
- User cancelled the login or doesn't have permission
- Check Azure app role assignments

### Role not mapping correctly
- Verify Azure app roles are configured in Azure Portal
- Check that role names match exactly (case-sensitive)
- Ensure roles are assigned to the user in Azure AD

## Maintenance

When updating role mappings:
1. Update the `$roleMapping` array in `handleMicrosoftCallback()`
2. Update the `$roleHierarchy` array if priority changes are needed
3. Update this documentation with the new mappings
4. Update Azure app role definitions if needed
