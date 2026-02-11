<?php
/**
 * Microsoft Graph Service
 * Handles user invitation and role assignment via Microsoft Graph API
 * Requires Azure App Permissions: User.Invite.All and AppRoleAssignment.ReadWrite.All
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class MicrosoftGraphService {
    
    private $accessToken;
    private $httpClient;
    private $servicePrincipalId;
    
    /**
     * Role mapping: role values to their respective Azure App Role IDs
     * TODO: Replace placeholder IDs with actual Azure App Role IDs from your Azure Portal
     * Each role must have a unique GUID from Azure AD App Roles
     */
    private const ROLE_MAPPING = [
        'anwaerter' => 'PLACEHOLDER_ANWAERTER_ID',
        'mitglied' => 'PLACEHOLDER_MITGLIED_ID',
        'vorstand_intern' => 'PLACEHOLDER_VORSTAND_INTERN_ID',
        'vorstand_extern' => 'PLACEHOLDER_VORSTAND_EXTERN_ID',
        'berater' => 'PLACEHOLDER_BERATER_ID',
        'alumni' => 'PLACEHOLDER_ALUMNI_ID',
        'ehrenmitglied' => 'PLACEHOLDER_EHRENMITGLIED_ID',
        'senior' => 'PLACEHOLDER_SENIOR_ID',
        'partner' => 'PLACEHOLDER_PARTNER_ID',
        'gast' => 'PLACEHOLDER_GAST_ID'
    ];
    
    /**
     * Constructor: Obtain access token via Client Credentials Flow
     * 
     * @throws Exception If authentication fails or environment variables are missing
     */
    public function __construct() {
        // Verify required environment variables are set
        $tenantId = defined('AZURE_TENANT_ID') ? AZURE_TENANT_ID : '';
        $clientId = defined('AZURE_CLIENT_ID') ? AZURE_CLIENT_ID : '';
        $clientSecret = defined('AZURE_CLIENT_SECRET') ? AZURE_CLIENT_SECRET : '';
        
        if (empty($tenantId) || empty($clientId) || empty($clientSecret)) {
            throw new Exception('Azure credentials not configured. Check AZURE_TENANT_ID, AZURE_CLIENT_ID, and AZURE_CLIENT_SECRET in .env file.');
        }
        
        // Initialize Guzzle HTTP client
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
        
        // Obtain access token using Client Credentials Flow
        $this->accessToken = $this->getAccessToken($tenantId, $clientId, $clientSecret);
    }
    
    /**
     * Get access token using Client Credentials Flow
     * 
     * @param string $tenantId Azure Tenant ID
     * @param string $clientId Azure Client ID
     * @param string $clientSecret Azure Client Secret
     * @return string Access token
     * @throws Exception If token request fails
     */
    private function getAccessToken(string $tenantId, string $clientId, string $clientSecret): string {
        $tokenUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";
        
        try {
            $response = $this->httpClient->post($tokenUrl, [
                'form_params' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials'
                ]
            ]);
            
            $body = json_decode($response->getBody()->getContents(), true);
            
            if (!isset($body['access_token'])) {
                throw new Exception('Access token not found in response');
            }
            
            return $body['access_token'];
            
        } catch (GuzzleException $e) {
            throw new Exception('Failed to obtain access token: ' . $e->getMessage());
        }
    }
    
    /**
     * Invite a user via Microsoft Graph API
     * 
     * @param string $email User's email address
     * @param string $name User's display name
     * @param string $redirectUrl URL to redirect user after accepting invitation
     * @return string User ID of the newly invited user
     * @throws Exception If invitation fails
     */
    public function inviteUser(string $email, string $name, string $redirectUrl): string {
        $invitationUrl = 'https://graph.microsoft.com/v1.0/invitations';
        
        try {
            $response = $this->httpClient->post($invitationUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'invitedUserEmailAddress' => $email,
                    'invitedUserDisplayName' => $name,
                    'inviteRedirectUrl' => $redirectUrl,
                    'sendInvitationMessage' => true
                ]
            ]);
            
            $body = json_decode($response->getBody()->getContents(), true);
            
            if (!isset($body['invitedUser']['id'])) {
                throw new Exception('User ID not found in invitation response');
            }
            
            return $body['invitedUser']['id'];
            
        } catch (GuzzleException $e) {
            throw new Exception('Failed to invite user: ' . $e->getMessage());
        }
    }
    
    /**
     * Assign a role to a user
     * 
     * @param string $userId User ID (Object ID from Azure AD)
     * @param string $roleValue Role value (e.g., 'anwaerter', 'mitglied')
     * @return bool True if role assignment succeeded
     * @throws Exception If role assignment fails or role is invalid
     */
    public function assignRole(string $userId, string $roleValue): bool {
        // Validate role exists in mapping
        if (!isset(self::ROLE_MAPPING[$roleValue])) {
            throw new Exception("Invalid role value: {$roleValue}");
        }
        
        $roleId = self::ROLE_MAPPING[$roleValue];
        
        // Validate that role ID has been configured (not using placeholder)
        if (strpos($roleId, 'PLACEHOLDER_') === 0) {
            throw new Exception("Role ID for '{$roleValue}' is not configured. Please update ROLE_MAPPING with actual Azure App Role IDs.");
        }
        
        // Get Service Principal ID (cached)
        $resourceId = $this->getServicePrincipalId();
        
        $assignmentUrl = "https://graph.microsoft.com/v1.0/users/{$userId}/appRoleAssignments";
        
        try {
            $response = $this->httpClient->post($assignmentUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'principalId' => $userId,
                    'resourceId' => $resourceId,
                    'appRoleId' => $roleId
                ]
            ]);
            
            return $response->getStatusCode() === 201;
            
        } catch (GuzzleException $e) {
            throw new Exception('Failed to assign role: ' . $e->getMessage());
        }
    }
    
    /**
     * Get Service Principal ID (Object ID) for the application
     * This ID is cached to avoid repeated API calls
     * 
     * @return string Service Principal Object ID
     * @throws Exception If Service Principal cannot be retrieved
     */
    private function getServicePrincipalId(): string {
        // Return cached value if available
        if ($this->servicePrincipalId !== null) {
            return $this->servicePrincipalId;
        }
        
        $clientId = defined('AZURE_CLIENT_ID') ? AZURE_CLIENT_ID : '';
        $spUrl = "https://graph.microsoft.com/v1.0/servicePrincipals(appId='{$clientId}')";
        
        try {
            $response = $this->httpClient->get($spUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ]
            ]);
            
            $body = json_decode($response->getBody()->getContents(), true);
            
            if (!isset($body['id'])) {
                throw new Exception('Service Principal ID not found in response');
            }
            
            // Cache the ID
            $this->servicePrincipalId = $body['id'];
            
            return $this->servicePrincipalId;
            
        } catch (GuzzleException $e) {
            throw new Exception('Failed to get Service Principal ID: ' . $e->getMessage());
        }
    }
}
