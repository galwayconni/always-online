# Enhanced Notify-Me System for Offline Pages

A comprehensive email notification system for collecting and notifying users when your website comes back online after maintenance or downtime.

## Features

- **Multiple Storage Options**: File-based storage, MailChimp integration, or both
- **Email Notifications**: Automatically send beautiful HTML emails when site is restored
- **Security Features**: Rate limiting, input validation, duplicate prevention
- **Admin Dashboard**: View registered emails, send notifications, monitor logs
- **Responsive Design**: Modern, mobile-friendly offline page
- **Logging System**: Comprehensive logging for monitoring and debugging
- **Batch Processing**: Send notifications in batches to avoid SMTP limits

## Quick Start

1. **Setup Configuration**
   ```bash
   cp config.example.php config.php
   # Edit config.php with your settings
   ```

2. **Create Data Directory**
   ```bash
   mkdir -p data
   chmod 755 data
   ```

3. **Configure Email Settings**
   - For Gmail: Use app-specific passwords
   - Update SMTP settings in config.php

4. **Test the System**
   - Visit `temp_offline.html` to see the offline page
   - Register an email address
   - Check `admin.php` to view registered emails

## File Structure

```
├── notify-me.php              # Enhanced email collection script
├── send-notifications.php     # Email notification sender
├── admin.php                 # Admin dashboard
├── temp_offline.html         # Enhanced offline page
├── config.example.php        # Configuration template
├── data/                     # Data storage directory
│   ├── notify_emails.txt     # Stored email addresses
│   ├── notifications_sent.txt # Sent notifications log
│   ├── notify_log.txt        # Registration activity log
│   └── notification_log.txt  # Email sending log
└── NOTIFY_README.md          # This documentation
```

## Configuration Options

### Storage Modes

- **file**: Store emails in text files (default)
- **mailchimp**: Store emails in MailChimp list
- **both**: Store in both file and MailChimp for redundancy

### Security Settings

- **Rate Limiting**: Limit submissions per IP address
- **Session Timeout**: Auto-reset rate limits after timeout
- **Input Validation**: Email format validation and sanitization
- **Duplicate Prevention**: Avoid storing duplicate email addresses

### Email Settings

Configure SMTP settings for sending notifications:

```php
'SMTP_HOST' => 'smtp.gmail.com',
'SMTP_PORT' => 587,
'SMTP_USERNAME' => 'your-email@gmail.com',
'SMTP_PASSWORD' => 'your-app-password',
```

## Usage Guide

### 1. Email Collection (notify-me.php)

The enhanced script automatically:
- Validates email addresses
- Prevents duplicate registrations
- Implements rate limiting
- Logs all activities
- Stores emails with timestamps and IP addresses

### 2. Sending Notifications (send-notifications.php)

**Command Line Usage:**
```bash
php send-notifications.php
```

**Web Usage:**
```
https://yoursite.com/send-notifications.php?token=your-secure-token
```

**Features:**
- Sends emails in batches to avoid SMTP limits
- Tracks sent notifications to prevent duplicates
- Comprehensive logging
- Test mode for development

### 3. Admin Dashboard (admin.php)

Access the admin interface at `/admin.php` with configured credentials.

**Features:**
- View all registered emails
- Monitor sent notifications
- Trigger notification sending
- Export email lists as CSV
- View activity logs
- Real-time statistics

### 4. Offline Page (temp_offline.html)

Enhanced offline page with:
- Modern, responsive design
- Integrated email collection form
- Real-time form validation
- Success/error messaging
- Contact information display

## Email Template

The notification email includes:
- Professional HTML design
- Call-to-action button
- Registration timestamp
- Plain text alternative
- Unsubscribe information

## Security Considerations

1. **Change Default Credentials**
   ```php
   'ADMIN_USERNAME' => 'your-username',
   'ADMIN_PASSWORD' => 'secure-password',
   'ADMIN_TOKEN' => 'random-secure-token',
   ```

2. **File Permissions**
   ```bash
   chmod 755 data/
   chmod 644 data/*.txt
   ```

3. **HTTPS Configuration**
   - Always use HTTPS in production
   - Configure proper SSL certificates

4. **Environment Variables**
   - Store sensitive data in environment variables
   - Never commit credentials to version control

## Troubleshooting

### Common Issues

1. **Email Not Sending**
   - Check SMTP credentials
   - Verify app-specific passwords for Gmail
   - Check firewall/port restrictions
   - Review notification logs

2. **File Permission Errors**
   ```bash
   chmod 755 data/
   chmod 644 data/*.txt
   ```

3. **Rate Limiting Issues**
   - Adjust `MAX_EMAILS_PER_IP` setting
   - Clear sessions if needed
   - Check IP detection in logs

### Log Files

Monitor these log files for issues:
- `data/notify_log.txt` - Registration activities
- `data/notification_log.txt` - Email sending activities

### Test Mode

Enable test mode for development:
```php
'TEST_MODE' => true,
```

This logs email sending without actually sending emails.

## Deployment on GitHub Pages

Since GitHub Pages only supports static sites, you'll need:

1. **Separate Server** for PHP scripts:
   - Host notify-me.php on your web server
   - Update form action in temp_offline.html

2. **Configuration**:
   ```html
   <form action="https://your-server.com/notify-me.php" method="post">
   ```

3. **CORS Headers**:
   The notify-me.php script includes CORS headers for cross-origin requests.

## Advanced Features

### Automated Deployment

Create a cron job to automatically send notifications:
```bash
# Check every 5 minutes if site is back online
*/5 * * * * /usr/bin/php /path/to/send-notifications.php
```

### Integration with Monitoring

Integrate with uptime monitoring services to automatically trigger notifications when site is restored.

### Custom Email Templates

Modify the email template in `send-notifications.php` to match your brand:
- Update HTML styling
- Add your logo
- Customize messaging

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review log files for error details
3. Verify configuration settings
4. Test in different environments

## License

This enhanced notify-me system is provided as-is for use with your offline pages.

---

**Note**: Remember to test the complete workflow in a staging environment before deploying to production. 