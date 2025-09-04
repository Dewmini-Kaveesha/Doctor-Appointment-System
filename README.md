# DocQ - Doctor Appointment Booking System

A comprehensive web-based doctor appointment booking system built with PHP and MySQL.

## 🚀 Features

- **Multi-User System**: Admin, Doctor, and Patient roles
- **Appointment Management**: Book, reschedule, and cancel appointments
- **Doctor Availability**: Manage doctor schedules and time slots
- **Email Notifications**: Automated email system using PHPMailer
- **User Authentication**: Secure login/logout system
- **Password Recovery**: Email-based password reset functionality
- **Responsive Design**: Mobile-friendly interface
- **Admin Dashboard**: Complete administrative control
- **Doctor Dashboard**: Manage appointments and availability
- **Patient Dashboard**: View and manage appointments

## 🛠️ Technology Stack

- **Backend**: PHP 8.3+
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript
- **Email**: PHPMailer
- **Architecture**: MVC-like structure

## 📋 Prerequisites

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx) or PHP built-in server
- Composer (optional, for PHPMailer dependencies)

## 🔧 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/Dewmini-Kaveesha/Doctor-Appointment-System.git
   cd doctor-appointment-system
   ```

2. **Set up environment variables**
   ```bash
   cp .env.example .env
   ```
   Edit `.env` file with your configuration:
   ```env
   DB_HOST=localhost
   DB_USERNAME=root
   DB_PASSWORD=your_password
   DB_NAME=doctor_appointment_system
   
   SMTP_HOST=smtp.gmail.com
   SMTP_USERNAME=your_email@gmail.com
   SMTP_PASSWORD=your_app_password
   ```

3. **Create and import database**
   ```bash
   mysql -u root -p -e "CREATE DATABASE doctor_appointment_system;"
   mysql -u root -p doctor_appointment_system < doctor_appointment_system.sql
   ```

4. **Start the server**
   ```bash
   php -S localhost:8000
   ```

5. **Access the application**
   Open your browser and navigate to: `http://localhost:8000/indexx.php`

## 🗄️ Database Structure

The system includes the following tables:
- `admin` / `admins` - Administrator accounts
- `doctors` - Doctor profiles and information
- `patients` - Patient registrations
- `appointments` - Appointment bookings
- `doctor_availability` - Doctor schedules
- `password_reset_tokens` - Password recovery tokens

## 📧 Email Configuration

To enable email functionality:

1. **Gmail Setup** (recommended):
   - Enable 2-factor authentication
   - Generate an app-specific password
   - Update `.env` with your credentials

2. **Other SMTP Providers**:
   - Update `SMTP_HOST`, `SMTP_PORT`, and `SMTP_ENCRYPTION` in `.env`

## 🔐 Default Admin Credentials

After installation, you can create an admin account using the default credentials in `.env`:
- Username: `admin`
- Password: `admin123`
- Email: `admin@docq.com`

**⚠️ Important**: Change these credentials immediately after first login!

## 📱 Usage

### For Administrators
1. Login with admin credentials
2. Manage doctors and patients
3. View all appointments
4. Configure system settings

### For Doctors
1. Register or get credentials from admin
2. Set availability schedules
3. Manage appointments
4. Update profile information

### For Patients
1. Register a new account
2. Browse available doctors
3. Book appointments
4. Manage existing bookings

## 🔧 Development

### File Structure
```
├── admin_dashboard.php     # Admin panel
├── doctor_dashboard.php    # Doctor interface
├── patient_dashboard.php   # Patient interface
├── config.php             # Database & app configuration
├── env_loader.php         # Environment variable loader
├── email_functions.php    # Email utilities
├── login.php              # Authentication
├── indexx.php            # Homepage
├── styles.css            # Stylesheets
├── javascript.js         # Client-side scripts
├── PHPMailer/            # Email library
└── .env                  # Environment variables (not in git)
```

### Adding New Features
1. Follow the existing code structure
2. Use environment variables for configuration
3. Implement proper error handling
4. Test thoroughly before committing

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature-name`
3. Commit changes: `git commit -am 'Add feature'`
4. Push to branch: `git push origin feature-name`
5. Submit a pull request



## 🐛 Issues & Support

- Report bugs via [GitHub Issues](https://github.com/Dewmini-Kaveesha/Doctor-Appointment-System.git)
- For support questions, please create an issue with the `question` label

## 🔄 Version History

- **v1.0.0** - Initial release with core functionality
- **v1.1.0** - Added environment variable support and Git preparation

## 📞 Contact

For questions or suggestions, please contact:
- Email: your-email@example.com
- GitHub: [Dewmini-Kaveesha](https://github.com/Dewmini-Kaveesha)

---

**Made with ❤️ for healthcare management**
