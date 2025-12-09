# Altcha Assets - CORS Configuration

## Problem
When Mautic forms are embedded in external websites, the browser needs to load the `altcha.min.js` file as an ES6 module from the Mautic server. This requires proper CORS (Cross-Origin Resource Sharing) headers.

## Solution for nginx

Add the following configuration to your nginx server block to allow CORS for the Altcha assets:

```nginx
# CORS headers for MauticMultiCaptchaBundle assets
location ~* ^/plugins/MauticMultiCaptchaBundle/Assets/.*\.js$ {
    add_header Access-Control-Allow-Origin "*" always;
    add_header Access-Control-Allow-Methods "GET, OPTIONS" always;
    add_header Access-Control-Allow-Headers "Content-Type" always;
    
    # Handle preflight requests
    if ($request_method = 'OPTIONS') {
        return 204;
    }
}
```

### Alternative: More restrictive CORS

If you want to limit which domains can embed your forms, replace the wildcard with specific domains:

```nginx
location ~* ^/plugins/MauticMultiCaptchaBundle/Assets/.*\.js$ {
    # Replace with your allowed domains
    add_header Access-Control-Allow-Origin "https://example.com" always;
    add_header Access-Control-Allow-Methods "GET, OPTIONS" always;
    add_header Access-Control-Allow-Headers "Content-Type" always;
    
    if ($request_method = 'OPTIONS') {
        return 204;
    }
}
```

## Testing

After applying the configuration:

1. Reload nginx: `sudo nginx -s reload`
2. Test the CORS headers:
   ```bash
   curl -I -H "Origin: https://example.com" https://your-mautic-server.com/plugins/MauticMultiCaptchaBundle/Assets/altcha.min.js
   ```
3. You should see the `Access-Control-Allow-Origin` header in the response

## Troubleshooting

If you still see CORS errors:
- Clear your browser cache
- Check nginx error logs: `sudo tail -f /var/log/nginx/error.log`
- Verify the location block is being matched
- Ensure no other location blocks are overriding this configuration
