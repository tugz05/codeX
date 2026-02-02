# 🔧 Session Timeout JSON Response Fix

## Problem

When the webpage is inactive for a long period (e.g., 1+ hour), the session expires. Instead of redirecting to the login page, the application displays a JSON response error.

This happens because:
1. User's session expires (after `SESSION_LIFETIME` minutes)
2. User tries to interact with the page (click, navigate, etc.)
3. Inertia.js makes an AJAX request
4. Laravel detects unauthenticated user and tries to redirect
5. Since it's an AJAX request, Laravel returns JSON instead of redirecting
6. User sees raw JSON response instead of login page

---

## ✅ Fix Applied

### 1. **Backend: Exception Handling** (`bootstrap/app.php`)

Added proper handling for authentication and CSRF token errors for Inertia requests:

```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->respond(function ($response, $exception, $request) {
        // Handle authentication exceptions for Inertia requests
        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            if ($request->header('X-Inertia')) {
                // For Inertia requests, return a 409 response to force a client-side reload
                return response()->json([
                    'message' => 'Your session has expired. Please log in again.',
                ], 409)->header('X-Inertia-Location', route('login'));
            }
        }
        
        // Handle 419 CSRF token mismatch for Inertia
        if ($exception instanceof \Illuminate\Session\TokenMismatchException && $request->header('X-Inertia')) {
            return response()->json([
                'message' => 'Page expired. Please refresh and try again.',
            ], 419)->header('X-Inertia-Location', $request->url());
        }
        
        return $response;
    });
})
```

**What this does:**
- Detects when an Inertia request hits an authentication error
- Returns proper HTTP status codes (401, 419, or 409)
- Includes `X-Inertia-Location` header to tell frontend where to redirect
- Prevents JSON responses from being shown to users

### 2. **Frontend: Error Handling** (`resources/js/app.ts`)

Added global error handlers to detect session expiration and force page reload:

```typescript
// Handle session timeout and authentication errors
let isHandlingSessionTimeout = false;

router.on('error', (event) => {
    if (isHandlingSessionTimeout) return;
    
    const response = event.detail.response;
    const status = response?.status;
    
    // Handle authentication and session errors
    if (status === 401 || status === 419 || status === 409) {
        isHandlingSessionTimeout = true;
        
        // Check for redirect location in header
        const redirectUrl = response.headers?.['x-inertia-location'] || '/login';
        
        // Force a full page reload
        window.location.href = redirectUrl;
    }
});

// Additional error handling for network issues
router.on('exception', (event) => {
    if (isHandlingSessionTimeout) return;
    
    console.error('Inertia exception:', event.detail);
    
    const error = event.detail.error;
    const status = error?.response?.status;
    
    // Handle authentication errors
    if (status === 401 || status === 419 || status === 409) {
        isHandlingSessionTimeout = true;
        window.location.href = '/login';
    }
});
```

**What this does:**
- Listens for HTTP status codes 401, 419, and 409
- Automatically redirects to login page when session expires
- Prevents duplicate redirects with `isHandlingSessionTimeout` flag
- Handles both normal errors and exceptions

---

## 🔒 Session Configuration

Session timeout is controlled by the `SESSION_LIFETIME` environment variable.

### Current Configuration

**In `.env`:**
```env
SESSION_LIFETIME=120  # Minutes (2 hours)
```

### Adjust Session Timeout

**For longer sessions (e.g., 8 hours):**
```env
SESSION_LIFETIME=480  # 8 hours
```

**For shorter sessions (e.g., 30 minutes):**
```env
SESSION_LIFETIME=30  # 30 minutes
```

**After changing `.env`:**
```bash
php artisan config:clear
php artisan config:cache
```

---

## 🧪 Testing

### Test 1: Wait for Session Expiration

1. **Set short session for testing:**
   ```env
   SESSION_LIFETIME=1  # 1 minute
   ```

2. **Clear config:**
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

3. **Log in and wait 2 minutes**

4. **Try to navigate or click something**

5. **Expected result:** Automatically redirected to login page (not JSON response)

6. **Reset to normal:**
   ```env
   SESSION_LIFETIME=120
   ```

### Test 2: Manual Session Clear

1. **Log in**

2. **Open browser console and run:**
   ```javascript
   // Clear session cookie
   document.cookie = 'laravel_session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
   ```

3. **Try to navigate**

4. **Expected result:** Automatically redirected to login page

---

## 📊 HTTP Status Codes

| Code | Meaning | When It Happens | Response |
|------|---------|-----------------|----------|
| **401** | Unauthorized | User not authenticated | Redirect to login |
| **419** | Page Expired | CSRF token mismatch/session expired | Redirect to login |
| **409** | Conflict | Custom code for session expiration | Redirect to login |

---

## 🔍 Troubleshooting

### Issue: Still seeing JSON responses

**Solution:**
```bash
# 1. Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 2. Rebuild frontend
npm run build

# 3. Clear browser cache and hard refresh (Ctrl+Shift+R)
```

### Issue: Session expires too quickly

**Check `.env` file:**
```env
SESSION_LIFETIME=120  # Should be in minutes, not seconds
```

**Check session driver:**
```env
SESSION_DRIVER=database  # Should be database or redis for production
```

### Issue: Constant redirects to login

**Possible causes:**
1. Session cookies not being saved (check browser settings)
2. HTTPS/HTTP mismatch in production
3. Domain mismatch in session configuration

**Check `config/session.php`:**
```php
'domain' => env('SESSION_DOMAIN'),  // Should match your domain
'secure' => env('SESSION_SECURE_COOKIE', true),  // true for HTTPS
'same_site' => 'lax',  // Allow cookies on same site
```

---

## 🚀 Production Deployment

When deploying this fix:

1. **Upload updated files:**
   - `bootstrap/app.php`
   - `resources/js/app.ts`

2. **Rebuild assets:**
   ```bash
   npm run build
   ```

3. **Clear caches:**
   ```bash
   php artisan config:clear
   php artisan config:cache
   php artisan route:clear
   php artisan view:clear
   ```

4. **Test:**
   - Log in
   - Wait for session to expire
   - Try to interact with page
   - Should redirect to login (not JSON)

---

## 📝 Additional Recommendations

### 1. **Increase Session Lifetime for Production**

For better user experience:
```env
SESSION_LIFETIME=480  # 8 hours
```

### 2. **Enable Session Warning (Optional)**

Add a frontend timer to warn users before session expires:

```typescript
// In a global component or composable
let sessionWarningShown = false;
const sessionLifetime = 120; // Minutes from .env

setTimeout(() => {
    if (!sessionWarningShown) {
        sessionWarningShown = true;
        alert('Your session will expire in 5 minutes. Please save your work.');
    }
}, (sessionLifetime - 5) * 60 * 1000); // Warn 5 minutes before expiration
```

### 3. **Use Redis for Sessions (Production)**

For better performance:

```env
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## ✅ Summary

**Before Fix:**
- Session expires → User clicks → JSON response displayed ❌

**After Fix:**
- Session expires → User clicks → Automatically redirected to login ✅

**Status codes handled:**
- 401 (Unauthorized)
- 419 (Page Expired/CSRF)
- 409 (Conflict/Session Timeout)

**Files modified:**
- `bootstrap/app.php` - Backend exception handling
- `resources/js/app.ts` - Frontend error handling

---

**Last Updated:** February 2, 2026  
**Laravel Version:** 11+  
**Inertia.js Version:** 1.0+
