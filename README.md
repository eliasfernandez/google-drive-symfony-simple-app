# Google Drive File and Folder Manager

This project is a PHP Symfony application that allows users to connect their Google Drive accounts and perform CRUD operations on files and folders. It uses the `League\OAuth2\Client\Provider\Google` library for OAuth 2.0 authentication and the Google Drive API for file management.

## Features

- Authenticate users via Google OAuth 2.0.
- Create, read, update, and delete files and folders on Google Drive.
- Select and upload files to specific folders.
- Check if a folder exists and retrieve the default application folder ID.
- Supports the `https://www.googleapis.com/auth/drive.file` scope for secure file access.

## Technologies Used

- PHP
- Symfony
- League\OAuth2\Client
- Google Drive API

## Setup Instructions

### Prerequisites

- PHP 8.1 or higher
- Composer
- Symfony CLI
- A Google Cloud Project with the Drive API enabled 
- Access to a MongoDB database 

### 1. Google Cloud Setup

1. Go to the Google Cloud Console: https://console.cloud.google.com/
2. Create a new project or select an existing project.
3. Enable the **Google Drive API**.
4. Set up OAuth 2.0 credentials:
   - Create a new OAuth 2.0 client ID.
   - Set the authorized redirect URI to `http://your-domain/google-drive/callback`.
5. Note down the **Client ID** and **Client Secret**.

### 2. Clone the Repository

```bash
git clone https://github.com/eliasfernandez/google-drive-manager.git
cd google-drive-manager
```

### 3. Install Dependencies

```bash
composer install
```

### 4. Configure Environment Variables
Rename .env to .env.local and update the following values:

```
MONGODB_DB=your-database-name
OAUTH_GOOGLE_WEB_ID=your-google-client-id
OAUTH_GOOGLE_WEB_SECRET=your-google-client-secret

```

### 5. Set Up the Database

```
docker-compose up -d
php bin/console doctrine:mongodb:schema:create
```

### 6. Start the Development Server

```bash
symfony serve
```

## License
This project is open-source and available under the MIT License.

## Acknowledgements
Symfony Framework: https://symfony.com/

League\OAuth2\Client: https://github.com/thephpleague/oauth2-client

Google Drive API Documentation: https://developers.google.com/drive/api/v3/about-sdk

Tailwind css: https://tailwindcss.com/

## Future Enhancements

Let me know if you think there is something that can be improved
