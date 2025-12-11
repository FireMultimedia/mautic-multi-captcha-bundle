/**
 * Altcha Widget Handler for Mautic Forms
 * Handles widget initialization, event management, and jQuery conflict prevention
 */
(function() {
    'use strict';
    
    // Global flag to prevent multiple script loads
    window.altchaHandlerLoaded = window.altchaHandlerLoaded || false;
    
    if (window.altchaHandlerLoaded) {
        return;
    }
    
    window.altchaHandlerLoaded = true;
    
    /**
     * Initialize Altcha widget with event handlers
     * @param {string} widgetId - The ID of the altcha widget
     * @param {boolean} invisible - Whether the widget should be invisible initially
     */
    function initializeAltchaWidget(widgetId, invisible) {
        var widget = document.getElementById(widgetId);
        
        if (!widget) {
            console.warn('Altcha widget not found:', widgetId);
            return;
        }
        
        // Prevent the widget from triggering jQuery events that cause infinite loops
        widget.addEventListener('statechange', function(ev) {
            console.log('Altcha state:', ev.detail);
            // Stop event propagation to prevent jQuery from catching it
            if (ev.stopPropagation) ev.stopPropagation();
            if (ev.stopImmediatePropagation) ev.stopImmediatePropagation();
            
            // Handle invisible mode visibility
            if (invisible && ev.detail) {
                if (ev.detail.state === 'code' || ev.detail.state === 'unverified') {
                    widget.style.display = 'block';
                    var label = document.querySelector('label[for="' + widgetId + '"]');
                    if (label) label.style.display = 'block';
                }
            }
        }, true);
        
        widget.addEventListener('verified', function(ev) {
            console.log('Altcha verified:', ev.detail);
            if (ev.stopPropagation) ev.stopPropagation();
            if (ev.stopImmediatePropagation) ev.stopImmediatePropagation();
        }, true);
        
        widget.addEventListener('error', function(ev) {
            console.error('Altcha error:', ev.detail);
            if (ev.stopPropagation) ev.stopPropagation();
            if (ev.stopImmediatePropagation) ev.stopImmediatePropagation();
        }, true);
        
        // Prevent all events from the widget from bubbling to jQuery
        ['change', 'input', 'submit', 'click'].forEach(function(eventType) {
            widget.addEventListener(eventType, function(ev) {
                if (ev.stopPropagation) ev.stopPropagation();
                if (ev.stopImmediatePropagation) ev.stopImmediatePropagation();
            }, true);
        });
        
        // Also prevent events from the widget's shadow DOM
        if (widget.shadowRoot) {
            widget.shadowRoot.addEventListener('change', function(ev) {
                if (ev.stopPropagation) ev.stopPropagation();
            }, true);
        }
        
        // Handle invisible mode initial state
        if (invisible) {
            widget.style.display = 'none';
            var label = document.querySelector('label[for="' + widgetId + '"]');
            if (label) label.style.display = 'none';
        }
    }
    
    /**
     * Load Altcha core script if not already loaded
     * @param {string} scriptUrl - URL to the altcha.min.js file
     * @param {Function} callback - Callback function to execute after script loads
     */
    function loadAltchaScript(scriptUrl, callback) {
        if (window.altchaLoaded) {
            if (callback) callback();
            return;
        }
        
        window.altchaLoaded = true;
        var script = document.createElement('script');
        script.src = scriptUrl;
        script.type = 'module';
        script.crossOrigin = 'anonymous';
        script.onload = function() {
            if (callback) callback();
        };
        script.onerror = function() {
            console.error('Failed to load Altcha script:', scriptUrl);
            window.altchaLoaded = false;
        };
        document.head.appendChild(script);
    }
    
    // Expose functions globally for use in templates
    window.MauticAltcha = {
        loadScript: loadAltchaScript,
        initWidget: initializeAltchaWidget
    };
    
    // Auto-initialize widgets when DOM is ready
    function autoInitialize() {
        var widgets = document.querySelectorAll('altcha-widget[id]');
        widgets.forEach(function(widget) {
            var invisible = widget.hasAttribute('auto') && widget.getAttribute('auto') === 'onload';
            initializeAltchaWidget(widget.id, invisible);
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInitialize);
    } else {
        autoInitialize();
    }
})();