/**
 * Kisankart Chatbot Integration Script
 *
 * This script can be included in any page to add the chatbot widget.
 * It automatically loads the required dependencies and initializes the chatbot.
 */

(function() {
    'use strict';

    // Configuration
    const CHATBOT_CONFIG = {
        apiBaseUrl: window.location.origin + '/Kisankart/api/chatbot',
        position: 'bottom-right', // bottom-right, bottom-left
        theme: 'green',
        autoOpen: false,
        enableOnPages: ['all'], // ['all'] or specific page patterns
        excludePages: [], // Pages to exclude
        enableForRoles: ['all'], // ['all', 'customer', 'seller', 'admin'] or specific roles
        enableOfflineMode: true,
        offlineMessage: 'I\'m currently offline. Please leave a message and I\'ll get back to you soon!',
        debug: true // Enable debug mode
    };

    // Check if chatbot should be enabled on current page
    function shouldEnableChatbot() {
        const currentPath = window.location.pathname;

        // Check excluded pages
        if (CHATBOT_CONFIG.excludePages.some(pattern => currentPath.includes(pattern))) {
            return false;
        }

        // Check enabled pages
        if (CHATBOT_CONFIG.enableOnPages.includes('all')) {
            return true;
        }

        return CHATBOT_CONFIG.enableOnPages.some(pattern => currentPath.includes(pattern));
    }

    // Check user role (if applicable)
    function checkUserRole() {
        if (CHATBOT_CONFIG.enableForRoles.includes('all')) {
            return true;
        }

        // Try to get user role from localStorage or session
        const userRole = localStorage.getItem('user_role') || 'guest';
        return CHATBOT_CONFIG.enableForRoles.includes(userRole);
    }

    // Load external dependencies
    function loadDependencies() {
        return new Promise((resolve) => {
            // Check if Font Awesome is already loaded
            if (!document.querySelector('link[href*="font-awesome"]') &&
                !document.querySelector('link[href*="fontawesome"]')) {

                const fontAwesome = document.createElement('link');
                fontAwesome.rel = 'stylesheet';
                fontAwesome.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css';
                document.head.appendChild(fontAwesome);
            }

            // Load chatbot widget script if not already loaded
            if (!window.KisanKartChatbot) {
                const script = document.createElement('script');
                script.src = 'js/chatbot-widget.js?v=' + Date.now();
                script.onload = resolve;
                script.onerror = () => {
                    console.error('Failed to load chatbot widget script');
                    resolve();
                };
                document.head.appendChild(script);
            } else {
                resolve();
            }
        });
    }

    // Initialize chatbot
    async function initializeChatbot() {
        try {
            // Check if chatbot should be enabled
            if (!shouldEnableChatbot() || !checkUserRole()) {
                console.log('Chatbot disabled for this page/user');
                return;
            }

            // Load dependencies
            await loadDependencies();

            // Wait a bit for Font Awesome to load
            setTimeout(() => {
                if (window.KisanKartChatbot) {
                    // Set global config
                    window.kisanKartChatbotConfig = CHATBOT_CONFIG;

                    // Initialize chatbot
                    window.kisanKartChatbot = new window.KisanKartChatbot(CHATBOT_CONFIG);

                    console.log('Kisankart Chatbot initialized successfully');

                    // Auto-open if configured
                    if (CHATBOT_CONFIG.autoOpen) {
                        setTimeout(() => {
                            window.kisanKartChatbot.openChat();
                        }, 2000);
                    }
                } else {
                    console.error('KisanKartChatbot class not available');
                }
            }, 500);

        } catch (error) {
            console.error('Error initializing chatbot:', error);
        }
    }

    // Utility functions for external use
    window.KisanKartChatbotUtils = {
        // Open chatbot programmatically
        openChat: function() {
            if (window.kisanKartChatbot) {
                window.kisanKartChatbot.openChat();
            }
        },

        // Close chatbot programmatically
        closeChat: function() {
            if (window.kisanKartChatbot) {
                window.kisanKartChatbot.closeChat();
            }
        },

        // Send a message programmatically
        sendMessage: function(message) {
            if (window.kisanKartChatbot) {
                // This would need to be implemented in the main chatbot class
                console.log('Programmatic message sending not yet implemented');
            }
        },

        // Update configuration
        updateConfig: function(newConfig) {
            Object.assign(CHATBOT_CONFIG, newConfig);
            if (window.kisanKartChatbot) {
                // Reinitialize with new config
                initializeChatbot();
            }
        },

        // Get current configuration
        getConfig: function() {
            return { ...CHATBOT_CONFIG };
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeChatbot);
    } else {
        initializeChatbot();
    }

    // Handle page navigation for SPAs
    let lastUrl = location.href;
    new MutationObserver(() => {
        const url = location.href;
        if (url !== lastUrl) {
            lastUrl = url;
            // Reinitialize chatbot for new page
            setTimeout(initializeChatbot, 1000);
        }
    }).observe(document, { subtree: true, childList: true });

})();

// CSS for integration-specific styles
const integrationStyles = `
    /* Ensure chatbot appears above all other elements */
    #kisankart-chatbot {
        z-index: 2147483647 !important;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        #kisankart-chatbot .chatbot-container {
            position: fixed !important;
            bottom: 0 !important;
            right: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 70vh !important;
            border-radius: 12px 12px 0 0 !important;
        }

        #kisankart-chatbot.bottom-left .chatbot-container {
            left: 0 !important;
        }
    }

    /* Print media - hide chatbot */
    @media print {
        #kisankart-chatbot {
            display: none !important;
        }
    }
`;

// Add integration styles
const styleSheet = document.createElement('style');
styleSheet.textContent = integrationStyles;
document.head.appendChild(styleSheet);
