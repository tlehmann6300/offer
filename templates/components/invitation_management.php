<?php
/**
 * Invitation Management Component
 * Modern UI for creating and managing user invitations
 * Required permissions: admin, board, or alumni_board
 */

// This component expects to be included in a page that has already done auth checks
// Also requires CSRFHandler to be loaded
require_once __DIR__ . '/../../includes/handlers/CSRFHandler.php';
?>

<div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">
        <i class="fas fa-envelope text-purple-600 mr-2"></i>
        Einladungs-Management
    </h2>
    <p class="text-gray-600">Erstellen Sie Einladungslinks für neue Mitglieder und Alumni</p>
</div>

<!-- Invitation Creation Card -->
<div class="card p-6 mb-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">
        <i class="fas fa-link text-green-600 mr-2"></i>
        Einladung erstellen
    </h3>
    
    <form id="invitationForm" class="space-y-4 mb-4">
        <input type="hidden" name="csrf_token" value="<?php echo CSRFHandler::getToken(); ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">E-Mail-Adresse</label>
                <input 
                    type="email" 
                    id="invitationEmail" 
                    name="email" 
                    required 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="benutzer@beispiel.de"
                >
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rolle</label>
                <select 
                    id="invitationRole" 
                    name="role" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
                    <option value="member">Mitglied</option>
                    <option value="alumni">Alumni</option>
                    <option value="manager">Ressortleiter</option>
                    <option value="alumni_board">Alumni-Vorstand</option>
                    <option value="board">Vorstand</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
        </div>
        
        <!-- Email Send Option -->
        <div class="flex items-center space-x-3 p-4 bg-gray-50 border border-gray-200 rounded-lg">
            <div class="flex items-center">
                <input 
                    type="checkbox" 
                    id="sendMailCheckbox" 
                    name="send_mail" 
                    value="1"
                    checked
                    class="w-5 h-5 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 focus:ring-2"
                >
            </div>
            <label for="sendMailCheckbox" class="flex-1 cursor-pointer">
                <span class="text-sm font-medium text-gray-700">Einladung direkt per E-Mail senden</span>
                <p class="text-xs text-gray-500 mt-1">
                    <i class="fas fa-envelope text-purple-500 mr-1"></i>
                    Der Einladungslink wird automatisch per E-Mail an die angegebene Adresse versendet
                </p>
            </label>
        </div>
        
        <div>
            <button type="submit" class="w-full btn-primary">
                <i class="fas fa-magic mr-2"></i>Link erstellen
            </button>
        </div>
    </form>
    
    <!-- Generated Link Display -->
    <div id="generatedLinkContainer" class="hidden mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
        <div class="flex items-start justify-between mb-2">
            <div class="flex-1">
                <p class="text-sm font-medium text-green-800 mb-1">
                    <i class="fas fa-check-circle mr-1"></i>
                    Einladungslink erfolgreich erstellt!
                </p>
                <p class="text-xs text-green-600 mb-2">
                    E-Mail: <span id="generatedEmail" class="font-semibold"></span> | 
                    Rolle: <span id="generatedRole" class="font-semibold"></span>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <input 
                type="text" 
                id="generatedLink" 
                readonly 
                class="flex-1 px-3 py-2 bg-white border border-green-300 rounded-lg text-sm font-mono text-gray-700 focus:outline-none"
            >
            <button 
                type="button" 
                id="copyLinkBtn" 
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2"
                title="Link kopieren"
            >
                <i class="fas fa-copy"></i>
                Kopieren
            </button>
        </div>
    </div>
</div>

<!-- Open Invitations List -->
<div class="card overflow-hidden">
    <div class="p-6 bg-gray-50 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-list text-blue-600 mr-2"></i>
                Offene Einladungen
            </h3>
            <button 
                type="button" 
                id="refreshInvitationsBtn" 
                class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition"
            >
                <i class="fas fa-sync-alt mr-1"></i>Aktualisieren
            </button>
        </div>
    </div>
    
    <div id="invitationsLoader" class="p-6 text-center">
        <i class="fas fa-spinner fa-spin text-2xl text-purple-600"></i>
        <p class="text-gray-600 mt-2">Lade Einladungen...</p>
    </div>
    
    <div id="invitationsContainer" class="hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-Mail</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rolle</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Erstellt am</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Läuft ab</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Erstellt von</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Link</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                    </tr>
                </thead>
                <tbody id="invitationsList" class="bg-white divide-y divide-gray-200">
                    <!-- Dynamically populated -->
                </tbody>
            </table>
        </div>
    </div>
    
    <div id="noInvitationsMessage" class="hidden p-6 text-center text-gray-500">
        <i class="fas fa-inbox text-4xl mb-2"></i>
        <p>Keine offenen Einladungen vorhanden</p>
    </div>
</div>

<script>
// Invitation Management JavaScript
(function() {
    const form = document.getElementById('invitationForm');
    const generatedLinkContainer = document.getElementById('generatedLinkContainer');
    const generatedLink = document.getElementById('generatedLink');
    const generatedEmail = document.getElementById('generatedEmail');
    const generatedRole = document.getElementById('generatedRole');
    const copyLinkBtn = document.getElementById('copyLinkBtn');
    const refreshBtn = document.getElementById('refreshInvitationsBtn');
    const invitationsLoader = document.getElementById('invitationsLoader');
    const invitationsContainer = document.getElementById('invitationsContainer');
    const noInvitationsMessage = document.getElementById('noInvitationsMessage');
    const invitationsList = document.getElementById('invitationsList');
    
    // Role name mapping
    const roleNames = {
        'member': 'Mitglied',
        'alumni': 'Alumni',
        'manager': 'Ressortleiter',
        'alumni_board': 'Alumni-Vorstand',
        'board': 'Vorstand',
        'admin': 'Administrator'
    };
    
    // Load invitations on page load
    loadInvitations();
    
    // Form submission handler
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Erstelle Link...';
        
        // Hide previous link
        generatedLinkContainer.classList.add('hidden');
        
        const formData = new FormData(form);
        
        try {
            const response = await fetch('/api/send_invitation.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Show generated link if available
                if (data.link) {
                    generatedLink.value = data.link;
                    generatedEmail.textContent = data.email;
                    generatedRole.textContent = roleNames[data.role] || data.role;
                    generatedLinkContainer.classList.remove('hidden');
                }
                
                // Reset form (but keep send_mail checked)
                const sendMailChecked = document.getElementById('sendMailCheckbox').checked;
                form.reset();
                document.getElementById('sendMailCheckbox').checked = sendMailChecked;
                
                // Reload invitations list
                loadInvitations();
                
                // Show appropriate success message
                showMessage(data.message || 'Einladungslink erfolgreich erstellt!', 'success');
            } else {
                showMessage(data.message || 'Fehler beim Erstellen des Einladungslinks', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('Netzwerkfehler beim Erstellen des Einladungslinks', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });
    
    // Copy link button handler
    copyLinkBtn.addEventListener('click', async function() {
        try {
            await navigator.clipboard.writeText(generatedLink.value);
            
            const originalText = copyLinkBtn.innerHTML;
            copyLinkBtn.innerHTML = '<i class="fas fa-check"></i> Kopiert!';
            copyLinkBtn.classList.add('bg-green-700');
            
            setTimeout(() => {
                copyLinkBtn.innerHTML = originalText;
                copyLinkBtn.classList.remove('bg-green-700');
            }, 2000);
        } catch (err) {
            // Fallback for older browsers
            generatedLink.select();
            document.execCommand('copy');
            
            const originalText = copyLinkBtn.innerHTML;
            copyLinkBtn.innerHTML = '<i class="fas fa-check"></i> Kopiert!';
            copyLinkBtn.classList.add('bg-green-700');
            
            setTimeout(() => {
                copyLinkBtn.innerHTML = originalText;
                copyLinkBtn.classList.remove('bg-green-700');
            }, 2000);
        }
    });
    
    // Refresh button handler
    refreshBtn.addEventListener('click', function() {
        loadInvitations();
    });
    
    // Load invitations function
    async function loadInvitations() {
        invitationsLoader.classList.remove('hidden');
        invitationsContainer.classList.add('hidden');
        noInvitationsMessage.classList.add('hidden');
        
        try {
            const response = await fetch('/api/get_invitations.php');
            const data = await response.json();
            
            if (data.success) {
                if (data.invitations.length === 0) {
                    noInvitationsMessage.classList.remove('hidden');
                } else {
                    renderInvitations(data.invitations);
                    invitationsContainer.classList.remove('hidden');
                }
            } else {
                showMessage(data.message || 'Fehler beim Laden der Einladungen', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('Netzwerkfehler beim Laden der Einladungen', 'error');
        } finally {
            invitationsLoader.classList.add('hidden');
        }
    }
    
    // Render invitations table
    function renderInvitations(invitations) {
        invitationsList.innerHTML = '';
        
        invitations.forEach(invitation => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            
            const createdDate = new Date(invitation.created_at);
            const expiresDate = new Date(invitation.expires_at);
            
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <i class="fas fa-envelope text-gray-400 mr-2"></i>
                        <span class="text-sm font-medium text-gray-900">${escapeHtml(invitation.email)}</span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs bg-purple-100 text-purple-700 rounded-full">
                        ${roleNames[invitation.role] || invitation.role}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    ${formatDate(createdDate)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    ${formatDate(expiresDate)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    ${escapeHtml(invitation.created_by_email || 'Unbekannt')}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <button 
                        type="button" 
                        class="text-blue-600 hover:text-blue-800 text-sm"
                        onclick="copyInvitationLink('${invitation.link}')"
                        title="Link kopieren"
                    >
                        <i class="fas fa-copy mr-1"></i>Kopieren
                    </button>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <button 
                        type="button" 
                        class="text-red-600 hover:text-red-800"
                        onclick="deleteInvitation(${invitation.id})"
                        title="Einladung löschen"
                    >
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            
            invitationsList.appendChild(row);
        });
    }
    
    // Delete invitation function (exposed globally)
    window.deleteInvitation = async function(invitationId) {
        if (!confirm('Möchten Sie diese Einladung wirklich löschen?')) {
            return;
        }
        
        const csrfTokenElement = document.querySelector('input[name="csrf_token"]');
        if (!csrfTokenElement) {
            showMessage('CSRF token nicht gefunden', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('invitation_id', invitationId);
        formData.append('csrf_token', csrfTokenElement.value);
        
        try {
            const response = await fetch('/api/delete_invitation.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showMessage('Einladung erfolgreich gelöscht', 'success');
                loadInvitations();
            } else {
                showMessage(data.message || 'Fehler beim Löschen der Einladung', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('Netzwerkfehler beim Löschen der Einladung', 'error');
        }
    };
    
    // Copy invitation link function (exposed globally)
    window.copyInvitationLink = async function(link) {
        try {
            await navigator.clipboard.writeText(link);
            showMessage('Link in die Zwischenablage kopiert', 'success');
        } catch (err) {
            // Fallback for older browsers
            const tempInput = document.createElement('input');
            tempInput.value = link;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            
            showMessage('Link in die Zwischenablage kopiert', 'success');
        }
    };
    
    // Helper functions
    function formatDate(date) {
        return date.toLocaleDateString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function showMessage(message, type) {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.ajax-message');
        existingMessages.forEach(msg => msg.remove());
        
        // Create new message element
        const messageDiv = document.createElement('div');
        messageDiv.className = 'ajax-message mb-6 p-4 rounded-lg ' + 
            (type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700');
        messageDiv.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check' : 'exclamation') + '-circle mr-2"></i>' + message;
        
        // Insert at the top of main content
        const mainContent = document.querySelector('main > div:first-child') || document.querySelector('main');
        if (mainContent) {
            mainContent.insertBefore(messageDiv, mainContent.firstChild);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }
    }
})();
</script>
