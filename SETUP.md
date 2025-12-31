# Tech Tank Management - Setup Guide

## 🔧 Environment Configuration

The inventory management system requires Airtable API credentials to function. Follow these steps to configure your environment.

---

## 📋 Prerequisites

1. An Airtable account with an Inventory base
2. Access to create Airtable Personal Access Tokens
3. A web server with PHP support (or Heroku for deployment)

---

## 🔑 Step 1: Get Your Airtable Credentials

### Get Your API Key (Personal Access Token)

1. Go to [https://airtable.com/create/tokens](https://airtable.com/create/tokens)
2. Click "Create new token"
3. Give it a name like "Tech Tank Management"
4. Add these scopes:
   - `data.records:read`
   - `data.records:write`
   - `schema.bases:read`
5. Add access to your Inventory base
6. Click "Create token"
7. **IMPORTANT:** Copy the token immediately (it won't be shown again!)

### Get Your Base ID

1. Open your Airtable Inventory base
2. Look at the URL in your browser:
   ```
   https://airtable.com/appXXXXXXXXXXXXXX/...
                        ^^^^^^^^^^^^^^^^^^
                        This is your Base ID
   ```
3. Copy the part that starts with `app` (e.g., `appAbCdEfGhIjKlMn`)

---

## 💻 Step 2: Local Development Setup

### Option A: Using .env File (Recommended for Local)

1. Open the `.env` file in the project root
2. Replace the placeholder values:

```env
AIRTABLE_API_KEY=patAbC123YourActualTokenHere456XyZ
AIRTABLE_INVENTORY_BASE_ID=appXXXXXXXXXXXXXX
```

3. Save the file
4. **NEVER commit this file to GitHub!** (it's already in .gitignore)

---

## ☁️ Step 3: Heroku Deployment Setup

If deploying to Heroku, you need to set environment variables through Heroku's config vars:

### Using Heroku CLI

```bash
heroku config:set AIRTABLE_API_KEY=patAbC123YourActualTokenHere456XyZ
heroku config:set AIRTABLE_INVENTORY_BASE_ID=appXXXXXXXXXXXXXX
```

### Using Heroku Dashboard

1. Go to [https://dashboard.heroku.com/apps](https://dashboard.heroku.com/apps)
2. Click on your app name
3. Go to "Settings" tab
4. Click "Reveal Config Vars"
5. Add these config vars:
   - Key: `AIRTABLE_API_KEY` → Value: `patAbC123YourActualTokenHere456XyZ`
   - Key: `AIRTABLE_INVENTORY_BASE_ID` → Value: `appXXXXXXXXXXXXXX`
6. Click "Add" for each

---

## 🗄️ Step 4: Set Up Your Airtable Base

Your Airtable "Inventory" table should have these fields:

| Field Name | Field Type | Description |
|------------|------------|-------------|
| `name` | Single line text | Item name |
| `sku` | Single line text | SKU code |
| `quantity` | Number | Quantity in stock |
| `cost` | Number (Currency) | Unit cost |
| `location` | Single line text | Storage location |

**Field names are case-sensitive!** Make sure they match exactly.

---

## ✅ Step 5: Verify Configuration

### Test the API Status

1. Open your browser and go to:
   ```
   http://localhost/api.php?action=status
   ```
   Or on Heroku:
   ```
   https://your-app-name.herokuapp.com/api.php?action=status
   ```

2. You should see:
   ```json
   {
     "success": true,
     "configured": true,
     "status": {
       "env_file_exists": true,
       "api_key_set": true,
       "base_id_set": true,
       "api_key_length": 56,
       "base_id_length": 17,
       "php_version": "8.1.0",
       "curl_available": true
     },
     "message": "API is properly configured"
   }
   ```

3. If you see `"configured": false`, check your environment variables!

---

## 🔍 Troubleshooting

### Problem: Data not showing across browsers

**Solution:** Make sure:
1. Your Airtable credentials are set correctly
2. The API status endpoint shows `"configured": true`
3. Your browser console shows no errors
4. You can access `/api.php?action=read` and see data

### Problem: "Server configuration error"

**Solution:**
1. Check that `.env` file exists with correct values
2. On Heroku, check config vars are set
3. Run `/api.php?action=status` to diagnose

### Problem: "Airtable API error (HTTP 401)"

**Solution:**
- Your API key is invalid or expired
- Create a new Personal Access Token in Airtable
- Make sure the token has the required scopes

### Problem: "Airtable API error (HTTP 404)"

**Solution:**
- Your Base ID is incorrect
- Double-check the Base ID from your Airtable URL
- Make sure the "Inventory" table exists in that base

### Problem: "Network error"

**Solution:**
- Check your internet connection
- Verify Airtable API is accessible
- Check firewall settings

---

## 🔒 Security Best Practices

1. **NEVER commit `.env` file to GitHub**
   - It's already in `.gitignore`
   - If accidentally committed, immediately:
     - Delete the token in Airtable
     - Create a new token
     - Update your configuration

2. **Use different tokens for development and production**

3. **Regularly rotate your API tokens**

4. **Only grant minimum required scopes**

---

## 🚀 Next Steps

Once configured:
1. Open `inventory_custom_layout.html` in your browser
2. You should see the sync indicator in the top-right corner
3. It should show "Synced (X items)" in green
4. Open the same page in another browser to test cross-browser sync
5. Add an item in one browser and watch it appear in the other within 5 seconds!

---

## 📞 Support

If you encounter issues:
1. Check the browser console for JavaScript errors
2. Check the API status endpoint: `/api.php?action=status`
3. Review the troubleshooting section above
4. Check Airtable API documentation: [https://airtable.com/developers/web/api/introduction](https://airtable.com/developers/web/api/introduction)

---

**Last Updated:** December 31, 2025
