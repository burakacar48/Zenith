# Server Panel Authentication Implementation Guide

## Overview
This guide explains how to integrate the server admin panel authentication with the customer panel at onurmedya.tr/burak.

## Architecture

### Components Created:
1. **panel/admin_auth_api.php** - API endpoint for authentication
2. **panel/admin_sessions_table.sql** - Database table for session management
3. **server/templates/admin_login.html** - Updated login page (needs completion)
4. **server/app.py** - Backend authentication handler (needs update)

## Implementation Steps

### Step 1: Database Setup
Run the SQL file to create the admin_sessions table:
```sql
mysql -u oyunmenu -p oyunmenu < panel/admin_sessions_table.sql
```

### Step 2: Update Server Backend (app.py)

The `admin_login` route in server/app.py needs to be updated to:
1. Accept customer_id and password from the login form
2. Verify credentials via the panel API (admin_auth_api.php)
3. Create a server-side session on successful authentication

Here's the updated route code:

```python
@app.route('/admin/login', methods=['GET', 'POST'])
def admin_login():
    # If already logged in, redirect to admin panel
    if session.get('admin_logged_in'):
        return redirect(url_for('admin_index'))
    
    if request.method == 'POST':
        customer_id = request.form.get('customer_id', '').strip()
        password = request.form.get('password', '')
        remember_me = request.form.get('remember')
        
        if not customer_id or not password:
            flash('Müşteri ID ve şifre gereklidir.', 'error')
            return render_template('admin_login.html')
        
        # Verify credentials with panel API
        try:
            api_url = 'https://onurmedya.tr/burak/admin_auth_api.php'
            response = requests.post(api_url, json={
                'customer_id': customer_id,
                'password': password
            }, timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                
                if data.get('success'):
                    # Authentication successful
                    session.permanent = bool(remember_me)
                    if remember_me:
                        app.permanent_session_lifetime = timedelta(days=30)
                    else:
                        app.permanent_session_lifetime = timedelta(hours=8)
                    
                    session['admin_logged_in'] = True
                    session['admin_customer_id'] = customer_id
                    session['admin_name'] = data['data']['name']
                    session['admin_company'] = data['data'].get('company', '')
                    session['panel_session_token'] = data['data']['session_token']
                    
                    flash('Başarıyla giriş yaptınız!', 'success')
                    return redirect(url_for('admin_index'))
                else:
                    flash(data.get('message', 'Geçersiz müşteri ID veya şifre.'), 'error')
            else:
                flash('Panel API\'sine bağlanılamadı.', 'error')
                
        except requests.RequestException as e:
            flash(f'Bağlantı hatası: {str(e)}', 'error')
    
    return render_template('admin_login.html')
```

### Step 3: Complete admin_login.html

The admin_login.html file needs a complete JavaScript implementation. Here's the working version:

```html
<!-- The form remains the same, but update the JavaScript section -->
<script>
// Form submission - direct POST to Flask backend
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const loginBtn = document.getElementById('loginBtn');
    const customer_id = document.getElementById('customer_id').value.trim();
    const password = document.getElementById('password').value.trim();

    // Basic validation
    if (!customer_id || !password) {
        e.preventDefault();
        showNotification('Lütfen müşteri ID ve şifrenizi girin.', 'error');
        return;
    }

    // Show loading state
    loginBtn.classList.add('loading');
    loginBtn.disabled = true;

    // Form will submit naturally to Flask backend
    // Flask will handle API communication with panel
});

// Helper function
function showNotification(message, type) {
    const alertsContainer = document.getElementById('dynamicAlerts');
    alertsContainer.innerHTML = '';
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'check-circle'}"></i>
        <span>${message}</span>
    `;
    
    alertsContainer.appendChild(alert);
    
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.3s ease';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

// Auto-hide flash messages
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});
</script>
```

### Step 4: Add Required Dependency

Add `requests` library to server/requirements.txt if not already present:
```
requests
```

Then install:
```
pip install requests
```

## Testing

1. Start the Flask server:
   ```
   cd server
   python app.py
   ```

2. Navigate to http://localhost:5088/admin/login

3. Test login with customer credentials from the panel database

4. Verify successful authentication and redirection to admin panel

## Security Notes

- Session tokens are stored both in panel database and Flask session
- Passwords are hashed using PHP's password_hash/password_verify
- HTTPS should be used in production for API calls
- Session expiration is handled on both panel and server side
- CORS headers are configured for cross-origin requests

## Troubleshooting

- **API Connection Failed**: Check if panel API is accessible at https://onurmedya.tr/burak/admin_auth_api.php
- **Database Error**: Ensure admin_sessions table is created
- **Authentication Failed**: Verify customer credentials in panel database
- **Session Not Persisting**: Check Flask SECRET_KEY configuration
