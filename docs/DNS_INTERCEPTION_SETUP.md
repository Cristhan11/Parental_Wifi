# DNS Interception Setup - DEPRECATED

## ⚠️ Important Notice

**This feature has been removed from the system.** The system now only supports HTTP request interception. HTTPS requests are not intercepted.

## Why This Was Removed

After testing and evaluation, we decided to keep the system simple and only intercept HTTP requests. HTTPS interception would require:
- DNS interception (redirecting all DNS queries to gateway)
- SSL certificate management
- More complex configuration
- Potential security concerns

## Current System Behavior

- **HTTP Requests**: ✅ Intercepted and redirected to portal
- **HTTPS Requests**: ❌ Not intercepted (devices can access HTTPS sites directly)

## What This Means

When a device's time expires:
- HTTP sites (e.g., `http://google.com`) will be intercepted and redirected to the portal
- HTTPS sites (e.g., `https://google.com`) will NOT be intercepted and will load normally

This is an acceptable limitation for the current use case, as most modern browsers will attempt HTTP first for captive portal detection, and many sites still support HTTP.

## Future Considerations

If HTTPS interception becomes necessary in the future, the following approaches could be considered:
1. DNS interception with SSL certificate management
2. Transparent proxy with SSL termination
3. Browser-based captive portal detection (using HTTP endpoints)

For now, the system focuses on HTTP interception which is simpler and sufficient for the use case.
