/**
 * DISABLED: Session Manager for Runnix App
 * 
 * This session manager has been disabled to remove automatic session timeout.
 * Users will now stay logged in for 30 days instead of being logged out due to inactivity.
 * 
 * To re-enable: Remove this comment block and the return statement below.
 */

// Exit early - session manager is disabled
console.log("Session manager is disabled. Users will not be automatically logged out.");
return;

/**
 * Session Manager for Runnix App
 * Handles inactivity tracking and auto-logout functionality
 */

class SessionManager {
    constructor() {
        this.activityInterval = null;
        this.warningInterval = null;
        this.warningThreshold = 5; // minutes
        this.activityTimeout = 30000; // 30 seconds
        this.warningTimeout = 60000; // 1 minute
        this.baseUrl = '/backend/api';
        this.isWarningShown = false;
        this.warningModal = null;
        
        this.init();
    }

    init() {
        // Start activity tracking
        this.startActivityTracking();
        
        // Start session monitoring
        this.startSessionMonitoring();
        
        // Set up event listeners for user activity
        this.setupActivityListeners();
    }

    setupActivityListeners() {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        events.forEach(event => {
            document.addEventListener(event, () => {
                this.recordActivity();
            }, true);
        });

        // Handle visibility change (tab switching)
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.recordActivity();
            }
        });
    }

    startActivityTracking() {
        // Record activity every 30 seconds
        this.activityInterval = setInterval(() => {
            this.recordActivity();
        }, this.activityTimeout);
    }

    startSessionMonitoring() {
        // Check session status every minute
        this.warningInterval = setInterval(() => {
            this.checkSessionStatus();
        }, this.warningTimeout);
    }

    async recordActivity() {
        try {
            const token = this.getAuthToken();
            if (!token) return;

            const response = await fetch(`${this.baseUrl}/update_activity.php`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();
            
            if (data.status === 'success') {
                // Check if session is about to expire
                if (data.data.warning && !this.isWarningShown) {
                    this.showWarning(data.data.remaining_minutes);
                }
            } else if (data.status === 'error' && response.status === 401) {
                // Session expired, logout user
                this.forceLogout('Session expired due to inactivity');
            }
        } catch (error) {
            console.error('Error recording activity:', error);
        }
    }

    async checkSessionStatus() {
        try {
            const token = this.getAuthToken();
            if (!token) return;

            const response = await fetch(`${this.baseUrl}/session_status.php`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();
            
            if (data.status === 'success') {
                if (data.data.is_expired) {
                    this.forceLogout('Session expired due to inactivity');
                } else if (data.data.warning && !this.isWarningShown) {
                    this.showWarning(data.data.remaining_minutes);
                }
            } else if (response.status === 401) {
                this.forceLogout('Session expired');
            }
        } catch (error) {
            console.error('Error checking session status:', error);
        }
    }

    showWarning(remainingMinutes) {
        this.isWarningShown = true;
        
        // Create warning modal
        this.warningModal = document.createElement('div');
        this.warningModal.className = 'session-warning-modal';
        this.warningModal.innerHTML = `
            <div class="session-warning-content">
                <h3>Session Timeout Warning</h3>
                <p>Your session will expire in ${Math.round(remainingMinutes)} minutes due to inactivity.</p>
                <p>Click "Continue Session" to stay logged in.</p>
                <div class="session-warning-buttons">
                    <button id="continue-session" class="btn btn-primary">Continue Session</button>
                    <button id="logout-now" class="btn btn-secondary">Logout Now</button>
                </div>
            </div>
        `;

        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .session-warning-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
            }
            .session-warning-content {
                background: white;
                padding: 30px;
                border-radius: 10px;
                max-width: 400px;
                text-align: center;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            }
            .session-warning-buttons {
                margin-top: 20px;
                display: flex;
                gap: 10px;
                justify-content: center;
            }
            .btn {
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-weight: bold;
            }
            .btn-primary {
                background: #007bff;
                color: white;
            }
            .btn-secondary {
                background: #6c757d;
                color: white;
            }
        `;
        document.head.appendChild(style);

        // Add event listeners
        document.body.appendChild(this.warningModal);
        
        document.getElementById('continue-session').addEventListener('click', () => {
            this.continueSession();
        });
        
        document.getElementById('logout-now').addEventListener('click', () => {
            this.logout();
        });

        // Auto-hide warning after 30 seconds if not acted upon
        setTimeout(() => {
            if (this.warningModal && this.warningModal.parentNode) {
                this.hideWarning();
                this.forceLogout('Session expired - no response to warning');
            }
        }, 30000);
    }

    hideWarning() {
        if (this.warningModal && this.warningModal.parentNode) {
            this.warningModal.parentNode.removeChild(this.warningModal);
            this.warningModal = null;
        }
        this.isWarningShown = false;
    }

    async continueSession() {
        try {
            await this.recordActivity();
            this.hideWarning();
            
            // Show success message
            this.showMessage('Session extended successfully!', 'success');
        } catch (error) {
            console.error('Error continuing session:', error);
            this.forceLogout('Failed to extend session');
        }
    }

    async logout() {
        try {
            const token = this.getAuthToken();
            if (token) {
                const response = await fetch(`${this.baseUrl}/logout.php`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                
                if (data.status === 'success') {
                    // Show logout success message with session duration
                    const sessionDuration = data.data.session_duration_minutes || 0;
                    const message = sessionDuration > 0 
                        ? `Logged out successfully. Session duration: ${Math.round(sessionDuration)} minutes`
                        : 'Logged out successfully';
                    
                    this.showMessage(message, 'success');
                    
                    // Log logout analytics if available
                    if (data.data.logout_logged) {
                        console.log('Logout logged successfully');
                    }
                } else {
                    this.showMessage('Logout completed with some issues', 'warning');
                }
            }
        } catch (error) {
            console.error('Error during logout:', error);
            this.showMessage('Logout completed with errors', 'error');
        } finally {
            // Always proceed with logout even if there are issues
            setTimeout(() => {
                this.forceLogout('Logged out successfully');
            }, 2000);
        }
    }

    forceLogout(message) {
        // Clear intervals
        if (this.activityInterval) {
            clearInterval(this.activityInterval);
        }
        if (this.warningInterval) {
            clearInterval(this.warningInterval);
        }

        // Hide warning if shown
        this.hideWarning();

        // Clear token
        localStorage.removeItem('auth_token');
        sessionStorage.removeItem('auth_token');

        // Show logout message
        this.showMessage(message, 'info');

        // Redirect to login page
        setTimeout(() => {
            window.location.href = '/login';
        }, 2000);
    }

    getAuthToken() {
        return localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
    }

    showMessage(message, type = 'info') {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;

        // Add background color based on type
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
        toast.style.background = colors[type] || colors.info;

        document.body.appendChild(toast);

        // Remove after 3 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 3000);
    }

    destroy() {
        if (this.activityInterval) {
            clearInterval(this.activityInterval);
        }
        if (this.warningInterval) {
            clearInterval(this.warningInterval);
        }
        this.hideWarning();
    }
}

// Initialize session manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize if user is logged in
    if (localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token')) {
        window.sessionManager = new SessionManager();
    }
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SessionManager;
}
