# Poll Site

A simple, modern poll website for recurring polls with PHP, Apache, and SQLite3.

## Features

### User Features
- **Easy Poll Participation**: Users simply add their name to participate
- **Email Notifications**: Optional email registration to receive notifications when others join
- **Unsubscribe Link**: Users can unsubscribe from email notifications via link
- **Dark Mode**: Switch between light and dark themes
- **Bootstrap Design**: Modern, responsive UI

### Admin Features
- **Admin Dashboard**: Secure login for administrators
- **Entry Management**: Delete and edit poll entries
- **Poll Configuration**: Add/edit poll descriptions, set recurring interval, set auto-clear date
- **Data History**: View and search historical poll data
- **Persistent Storage**: Cleared entries remain in database for historical purposes

## Requirements

- PHP 7.4+
- Apache 2.4+
- SQLite3
- Bootstrap 5
- Modern web browser

## Installation

1. Clone the repository
2. Place files in your Apache web root (e.g., `/var/www/html/poll`)
3. Ensure the `data` directory is writable by Apache
4. Copy `.env.example` to `.env` and configure settings
5. Configure email settings in `.env`
6. Access the poll site at `http://localhost/poll`

## License

MIT