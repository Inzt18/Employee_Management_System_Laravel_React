# Google and Facebook Login Setup

Both buttons are optional. Each one appears on the login page only after its keys are in `backend/.env`.
An employee can sign in with Google or Facebook when that account's email matches their employee email.

After changing `.env`, run `php artisan config:clear` and restart `php artisan serve`.

## 1. Add the database column

```
cd backend
php artisan migrate
```

This adds the `facebook_id` column to `employees`. The `google_id` column already exists.

## 2. Google

1. Open the Google Cloud Console (console.cloud.google.com) and create a project.
2. Go to **APIs & Services → OAuth consent screen**, choose **External**, and add your Gmail as a test user.
3. Go to **Credentials → Create Credentials → OAuth client ID → Web application**.
4. Under **Authorized redirect URIs**, add `http://127.0.0.1:8000/api/auth/google/callback`.
5. Copy the Client ID and Client Secret into `backend/.env`:

```
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI="${APP_URL}/api/auth/google/callback"
```

## 3. Facebook

1. Open developers.facebook.com, click **My Apps → Create App**, and choose the use case **Authenticate and request data from users with Facebook Login**.
2. In the app, open **Use cases → Customize** for Facebook Login and make sure the **email** permission is added.
3. Under **Facebook Login → Settings**, add `http://127.0.0.1:8000/api/auth/facebook/callback` to **Valid OAuth Redirect URIs**. Facebook allows `localhost` and `127.0.0.1` redirects while the app is in development mode.
4. Go to **App settings → Basic** and copy the **App ID** and **App Secret** into `backend/.env`:

```
FACEBOOK_CLIENT_ID=your-app-id
FACEBOOK_CLIENT_SECRET=your-app-secret
FACEBOOK_REDIRECT_URI="${APP_URL}/api/auth/facebook/callback"
```

While the app is in development mode, only people with a role on the app (you, plus any testers you add under **App roles**) can log in.

## Checklist

- `APP_URL=http://127.0.0.1:8000` and `FRONTEND_URL=http://localhost:5173` in `backend/.env`.
- The redirect URI in Google or Facebook must match `.env` exactly, including `127.0.0.1` versus `localhost`.
- The employee's email in the system must be the same email used on Google or Facebook.
- A Facebook account that signed up with only a phone number has no email to share, so it cannot be matched. The login page shows a message explaining this.

## How it works

| Step | Where |
| --- | --- |
| Login page asks which providers are configured | `GET /api/auth/providers` |
| Button sends the browser to Google or Facebook | `GET /api/auth/{provider}/redirect` |
| Provider sends the user back to Laravel | `GET /api/auth/{provider}/callback` |
| Laravel finds the employee by email, saves `google_id` or `facebook_id`, and creates a Sanctum token | `SocialAuthController` |
| React reads the token from the URL and logs the user in | `src/pages/auth/SocialCallbackPage.jsx` |
