# Novel Archive — Website Functionalities (Non‑Technical)

This document describes what a normal user can do on the Novel Archive website.

## What Novel Archive is

Novel Archive is a web platform for amateur writers and readers:

- **Writers** can upload stories/novels.
- **Readers** can browse the catalogue and read/download novels (depending on their plan).

## Plans (Free vs Pro)

The website supports different access levels:

- **Free plan**
  - Can access **Free** novels.
- **Pro plan**
  - Can access **Free** and **Pro** novels.
- **Admin**
  - Can do everything a normal user can do.
  - Can also manage other users (see “Admin features”).

## Main pages you’ll use

- **Homepage**
  - See a short description of the platform
  - View pricing (Free vs Premium/Pro)
  - Links to Login and Register
  - Team section

- **Register**
  - Create a new account with username, email, and password
  - Password strength is checked (weak passwords are rejected)
  - After registering, you are expected to **verify your email** (see below)

- **Verify user (Email verification)**
  - After registration, the website sends you an email with a verification link
  - Opening that link verifies/activates your account

- **Login**
  - Log in using email + password
  - “Forgot password?” link is available

- **Forgot password**
  - Enter your email to request a password reset link

- **Reset password**
  - Set a new password using the link you received by email
  - Password strength is checked here too

- **Dashboard** (after login)
  - Contains the catalogue and upload features (and admin tools for admins)

- **Logout**
  - Logs you out and redirects you back to the homepage after a short countdown

## Catalogue (Browse / Read / Download)

In the Dashboard, the **Catalogue** lets you:

- **Browse novels** with pagination (next/previous pages)
- **Filter the list**
  - Latest (default)
  - Only PDF
  - Only Text
- **See basic info for each novel**
  - Title
  - Author username
  - Whether it is Free or Pro content

Reading/downloading depends on the file type:

- **Text novels**
  - Open and read directly in the browser (a reading popup/modal)
- **PDF novels**
  - Download the PDF to your device

Access rules:

- If a novel is marked **Pro**, only **Pro** users (and Admin) can access it.

## Upload a novel (Writers)

In the Dashboard, you can upload content in two ways:

- **Upload a PDF**
  - Select a PDF file from your computer
  - There is a file size limit (the UI mentions a max of 2MB)
- **Upload Text**
  - Provide a **title**
  - Paste/type the text content directly

When uploading, you can choose the novel category:

- **Free** (available to everyone)
- **Pro** (available only to Pro users and Admin)

## Admin features (Manage Users)

If you are logged in as **Admin**, the Dashboard includes a **Manage Users** section:

- View a paginated list of users (ID, username, email, role)
- Change a user’s role between:
  - Free
  - Pro

## Notes a normal user may notice

- **Email is part of the account flow**
  - Registration and password reset expect the platform to send emails.
- **Strong passwords are required**
  - The server can reject passwords even if they “look strong” in a typical meter, especially if they include your name/email or common patterns.

