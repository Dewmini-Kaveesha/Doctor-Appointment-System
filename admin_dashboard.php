<?php
require_once 'config.php';
require_once 'email_functions.php';

if (!isLoggedIn('admin')) {
    redirectTo('login.php');
}

$admin_name = $_SESSION['admin_name'];
$admin_id = $_SESSION['admin_id'];
$success = '';
$error = '';

// Handle admin password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New password and confirmation do not match.';
    } else {
        try {
            // Get current password hash from database
            $stmt = $pdo->prepare("SELECT password FROM admin WHERE id = ?");
            $stmt->execute([$admin_id]);
            $admin = $stmt->fetch();
            
            if ($admin && verifyPassword($current_password, $admin['password'])) {
                // Update password
                $new_password_hash = hashPassword($new_password);
                $stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?");
                if ($stmt->execute([$new_password_hash, $admin_id])) {
                    $success = 'Password changed successfully! Please remember your new password.';
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
            } else {
                $error = 'Current password is incorrect.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: Unable to change password.';
        }
    }
}

// Handle Add Doctor form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_doctor'])) {
    $name = sanitize($_POST['doctor_name']);
    $email = sanitize($_POST['doctor_email']);
    $password = $_POST['doctor_password'];
    $specialization = sanitize($_POST['specialization']);
    $phone = sanitize($_POST['doctor_phone']);
    $address = sanitize($_POST['doctor_address']);
    $consultation_fee = sanitize($_POST['consultation_fee']);
    $experience_years = sanitize($_POST['experience_years']);
    $qualification = sanitize($_POST['qualification']);
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($specialization) || 
        empty($phone) || empty($address) || empty($consultation_fee) || 
        empty($experience_years) || empty($qualification)) {
        $error = 'All fields are required for adding a doctor.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        try {
            // Check if email already exists across all tables
            $emailCheck = isEmailTaken($email, $pdo);
            
            if ($emailCheck['exists']) {
                $error = "Email already exists in the system as a {$emailCheck['role']} account ({$emailCheck['name']}). Please use a different email address.";
            } else {
                // Insert new doctor
                $hashed_password = hashPassword($password);
                $stmt = $pdo->prepare("
                    INSERT INTO doctors (name, email, password, specialization, phone, address, 
                                       consultation_fee, experience_years, qualification, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
                ");
                if ($stmt->execute([$name, $email, $hashed_password, $specialization, $phone, 
                                  $address, $consultation_fee, $experience_years, $qualification])) {
                    
                    // Send welcome email to the new doctor
                    $emailSent = sendDoctorWelcomeEmail($email, $name, $password);
                    
                    if ($emailSent) {
                        $success = 'Doctor added successfully! Welcome email sent to ' . $email;
                    } else {
                        $success = 'Doctor added successfully! However, email could not be sent. Please inform the doctor manually.';
                    }
                } else {
                    $error = 'Failed to add doctor. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: Unable to add doctor.';
        }
    }
}

// Handle Add Admin form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_admin'])) {
    $username = sanitize($_POST['admin_username']);
    $email = sanitize($_POST['admin_email']);
    $password = $_POST['admin_password'];
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required for adding an admin.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        try {
            // Check if email already exists across all tables
            $emailCheck = isEmailTaken($email, $pdo);
            
            if ($emailCheck['exists']) {
                $error = "Email already exists in the system as a {$emailCheck['role']} account ({$emailCheck['name']}). Please use a different email address.";
            } else {
                // Insert new admin
                $hashed_password = hashPassword($password);
                $stmt = $pdo->prepare("
                    INSERT INTO admin (username, email, password, created_at) 
                    VALUES (?, ?, ?, NOW())
                ");
                if ($stmt->execute([$username, $email, $hashed_password])) {
                    
                    // Send welcome email to the new admin
                    $emailSent = sendAdminWelcomeEmail($email, $username, $password);
                    
                    if ($emailSent) {
                        $success = 'Admin added successfully! Welcome email sent to ' . $email;
                    } else {
                        $success = 'Admin added successfully! However, email could not be sent. Please inform the admin manually.';
                    }
                } else {
                    $error = 'Failed to add admin. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: Unable to add admin.';
        }
    }
}

// Get system statistics
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM patients");
    $total_patients = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM doctors WHERE status = 'active'");
    $total_doctors = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments");
    $total_appointments = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments WHERE DATE(appointment_date) = CURDATE()");
    $today_appointments = $stmt->fetchColumn();
} catch (PDOException $e) {
    $total_patients = $total_doctors = $total_appointments = $today_appointments = 0;
}

// Get recent appointments
try {
    $stmt = $pdo->query("
        SELECT a.*, p.name as patient_name, d.name as doctor_name, d.specialization
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.id 
        JOIN doctors d ON a.doctor_id = d.id 
        ORDER BY a.created_at DESC 
        LIMIT 10
    ");
    $recent_appointments = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_appointments = [];
}

// Get all patients
try {
    $stmt = $pdo->query("SELECT * FROM patients ORDER BY created_at DESC");
    $patients = $stmt->fetchAll();
} catch (PDOException $e) {
    $patients = [];
}

// Get all doctors
try {
    $stmt = $pdo->query("SELECT * FROM doctors ORDER BY created_at DESC");
    $doctors = $stmt->fetchAll();
} catch (PDOException $e) {
    $doctors = [];
}

// Get all appointments with details
try {
    $stmt = $pdo->query("
        SELECT a.*, p.name as patient_name, p.phone as patient_phone, p.email as patient_email,
               d.name as doctor_name, d.specialization, d.consultation_fee
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.id 
        JOIN doctors d ON a.doctor_id = d.id 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $all_appointments = $stmt->fetchAll();
} catch (PDOException $e) {
    $all_appointments = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HealthCare Plus</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="dashboard-nav">
                <h1><i class="fas fa-user-shield"></i> Admin Dashboard - <?php echo htmlspecialchars($admin_name); ?></h1>
                <div>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-content">
        <!-- Alert Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <!-- Statistics Dashboard -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="card" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; color: #2c5aa0; margin-bottom: 0.5rem;">
                    <i class="fas fa-users"></i>
                </div>
                <h3 style="font-size: 1.8rem; margin-bottom: 0.5rem; color: #2c5aa0;">
                    <?php echo $total_patients; ?>
                </h3>
                <p style="color: #4a5568;">Total Patients</p>
            </div>
            
            <div class="card" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; color: #10b981; margin-bottom: 0.5rem;">
                    <i class="fas fa-user-md"></i>
                </div>
                <h3 style="font-size: 1.8rem; margin-bottom: 0.5rem; color: #10b981;">
                    <?php echo $total_doctors; ?>
                </h3>
                <p style="color: #4a5568;">Active Doctors</p>
            </div>
            
            <div class="card" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; color: #f59e0b; margin-bottom: 0.5rem;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3 style="font-size: 1.8rem; margin-bottom: 0.5rem; color: #f59e0b;">
                    <?php echo $total_appointments; ?>
                </h3>
                <p style="color: #4a5568;">Total Appointments</p>
            </div>
            
            <div class="card" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; color: #6366f1; margin-bottom: 0.5rem;">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <h3 style="font-size: 1.8rem; margin-bottom: 0.5rem; color: #6366f1;">
                    <?php echo $today_appointments; ?>
                </h3>
                <p style="color: #4a5568;">Today's Appointments</p>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div style="display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 2px solid #e2e8f0; flex-wrap: wrap;">
            <button class="tab-btn active" onclick="showTab('appointments')">
                <i class="fas fa-calendar-alt"></i> Appointments
            </button>
            <button class="tab-btn" onclick="showTab('patients')">
                <i class="fas fa-users"></i> Patients
            </button>
            <button class="tab-btn" onclick="showTab('doctors')">
                <i class="fas fa-user-md"></i> Doctors
            </button>
            <button class="tab-btn" onclick="showTab('add-doctor')">
                <i class="fas fa-user-plus"></i> Add Doctor
            </button>
            <button class="tab-btn" onclick="showTab('add-admin')">
                <i class="fas fa-user-shield"></i> Add Admin
            </button>
            <button class="tab-btn" onclick="showTab('settings')">
                <i class="fas fa-cog"></i> Account Settings
            </button>
        </div>

        <!-- Appointments Tab -->
        <div id="appointments-tab" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list-alt"></i> All Appointments</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($all_appointments)): ?>
                        <div style="text-align: center; padding: 2rem; color: #4a5568;">
                            <i class="fas fa-calendar-times" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>No appointments found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Patient</th>
                                        <th>Doctor</th>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                        <th>Fee</th>
                                        <th>Contact</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_appointments as $appointment): ?>
                                        <tr>
                                            <td>#<?php echo $appointment['id']; ?></td>
                                            <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($appointment['doctor_name']); ?><br>
                                                <small style="color: #4a5568;"><?php echo htmlspecialchars($appointment['specialization']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?><br>
                                                <small style="color: #4a5568;"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch ($appointment['status']) {
                                                    case 'pending':
                                                        $status_class = 'badge-warning';
                                                        break;
                                                    case 'confirmed':
                                                        $status_class = 'badge-success';
                                                        break;
                                                    case 'completed':
                                                        $status_class = 'badge-primary';
                                                        break;
                                                    case 'cancelled':
                                                        $status_class = 'badge-danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst($appointment['status']); ?>
                                                </span>
                                            </td>
                                            <td>Rs.<?php echo number_format($appointment['consultation_fee'], 2); ?></td>
                                            <td>
                                                <small style="color: #4a5568;">
                                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($appointment['patient_email']); ?><br>
                                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($appointment['patient_phone']); ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Patients Tab -->
        <div id="patients-tab" class="tab-content" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> All Patients</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($patients)): ?>
                        <div style="text-align: center; padding: 2rem; color: #4a5568;">
                            <i class="fas fa-user-times" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>No patients found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Personal Info</th>
                                        <th>Address</th>
                                        <th>Registered</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($patients as $patient): ?>
                                        <tr>
                                            <td>#<?php echo $patient['id']; ?></td>
                                            <td><?php echo htmlspecialchars($patient['name']); ?></td>
                                            <td>
                                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($patient['email']); ?><br>
                                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($patient['phone']); ?>
                                                <?php if ($patient['emergency_contact']): ?>
                                                    <br><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($patient['emergency_contact']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-birthday-cake"></i> <?php echo date('M d, Y', strtotime($patient['date_of_birth'])); ?><br>
                                                <i class="fas fa-venus-mars"></i> <?php echo ucfirst($patient['gender']); ?>
                                                <?php if ($patient['blood_group']): ?>
                                                    <br><i class="fas fa-tint"></i> <?php echo $patient['blood_group']; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td style="max-width: 200px; word-wrap: break-word;">
                                                <?php echo htmlspecialchars($patient['address']); ?>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($patient['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Doctors Tab -->
        <div id="doctors-tab" class="tab-content" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-md"></i> All Doctors</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($doctors)): ?>
                        <div style="text-align: center; padding: 2rem; color: #4a5568;">
                            <i class="fas fa-user-md-times" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>No doctors found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Specialization</th>
                                        <th>Contact</th>
                                        <th>Experience</th>
                                        <th>Fee</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <tr>
                                            <td>#<?php echo $doctor['id']; ?></td>
                                            <td><?php echo htmlspecialchars($doctor['name']); ?></td>
                                            <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                                            <td>
                                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($doctor['email']); ?><br>
                                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($doctor['phone']); ?>
                                            </td>
                                            <td>
                                                <?php echo $doctor['experience_years']; ?> years<br>
                                                <small style="color: #4a5568;"><?php echo htmlspecialchars($doctor['qualification']); ?></small>
                                            </td>
                                            <td>Rs.<?php echo number_format($doctor['consultation_fee'], 2); ?></td>
                                            <td>
                                                <span class="badge <?php echo $doctor['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                                    <?php echo ucfirst($doctor['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($doctor['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Add Doctor Tab -->
        <div id="add-doctor-tab" class="tab-content" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-plus"></i> Add New Doctor</h3>
                    <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.9rem;">
                        <i class="fas fa-info-circle"></i> An email with login credentials will be automatically sent to the doctor's email address.
                    </p>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                            <div class="form-group">
                                <label for="doctor_name">Doctor Name <span style="color: red;">*</span></label>
                                <input type="text" id="doctor_name" name="doctor_name" class="form-control" 
                                       placeholder="Enter doctor's full name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="doctor_email">Email Address <span style="color: red;">*</span></label>
                                <input type="email" id="doctor_email" name="doctor_email" class="form-control" 
                                       placeholder="Enter email address" required>
                                <small style="color: #6b7280;">Must be unique across the entire system (not used by any doctor, admin, or patient)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="doctor_password">Temporary Password <span style="color: red;">*</span></label>
                                <input type="password" id="doctor_password" name="doctor_password" class="form-control" 
                                       placeholder="Minimum 6 characters" required minlength="6">
                                <small style="color: #6b7280;">Doctor should change this after first login</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="specialization">Specialization <span style="color: red;">*</span></label>
                                <select id="specialization" name="specialization" class="form-control" required>
                                    <option value="">Select Specialization</option>
                                    <option value="Cardiology">Cardiology</option>
                                    <option value="Dermatology">Dermatology</option>
                                    <option value="Orthopedics">Orthopedics</option>
                                    <option value="Pediatrics">Pediatrics</option>
                                    <option value="Neurology">Neurology</option>
                                    <option value="Psychiatry">Psychiatry</option>
                                    <option value="General Medicine">General Medicine</option>
                                    <option value="Surgery">Surgery</option>
                                    <option value="Gynecology">Gynecology</option>
                                    <option value="Ophthalmology">Ophthalmology</option>
                                    <option value="ENT">ENT</option>
                                    <option value="Radiology">Radiology</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="doctor_phone">Phone Number <span style="color: red;">*</span></label>
                                <input type="tel" id="doctor_phone" name="doctor_phone" class="form-control" 
                                       placeholder="Enter phone number" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="consultation_fee">Consultation Fee (Rs.) <span style="color: red;">*</span></label>
                                <input type="number" id="consultation_fee" name="consultation_fee" class="form-control" 
                                       placeholder="Enter fee amount" min="0" step="0.01" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="experience_years">Experience (Years) <span style="color: red;">*</span></label>
                                <input type="number" id="experience_years" name="experience_years" class="form-control" 
                                       placeholder="Years of experience" min="0" max="50" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="qualification">Qualification <span style="color: red;">*</span></label>
                                <input type="text" id="qualification" name="qualification" class="form-control" 
                                       placeholder="e.g., MBBS, MD" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="doctor_address">Address <span style="color: red;">*</span></label>
                            <textarea id="doctor_address" name="doctor_address" class="form-control" 
                                    placeholder="Enter complete address" rows="3" required></textarea>
                        </div>
                        
                        <div style="background-color: #eff6ff; border: 1px solid #3b82f6; border-radius: 0.5rem; padding: 1rem; margin: 1.5rem 0;">
                            <h4 style="color: #1e40af; margin: 0 0 0.5rem 0;">
                                <i class="fas fa-envelope"></i> Automatic Email Notification
                            </h4>
                            <p style="margin: 0; color: #374151; font-size: 0.9rem;">
                                ✅ Welcome email with login credentials will be sent automatically<br>
                                ✅ Doctor will receive their username, password, and login instructions<br>
                                ✅ Doctor will be advised to change password after first login
                            </p>
                        </div>
                        
                        <div style="text-align: center; margin-top: 2rem;">
                            <button type="submit" name="add_doctor" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Add Doctor & Send Welcome Email
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Admin Tab -->
        <div id="add-admin-tab" class="tab-content" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-shield"></i> Add New Admin</h3>
                    <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.9rem;">
                        <i class="fas fa-info-circle"></i> An email with login credentials will be automatically sent to the admin's email address.
                    </p>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div style="max-width: 500px; margin: 0 auto;">
                            <div class="form-group">
                                <label for="admin_username">Username <span style="color: red;">*</span></label>
                                <input type="text" id="admin_username" name="admin_username" class="form-control" 
                                       placeholder="Enter username" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="admin_email">Email Address <span style="color: red;">*</span></label>
                                <input type="email" id="admin_email" name="admin_email" class="form-control" 
                                       placeholder="Enter email address" required>
                                <small style="color: #6b7280;">Login credentials will be sent to this email</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="admin_password">Temporary Password <span style="color: red;">*</span></label>
                                <input type="password" id="admin_password" name="admin_password" class="form-control" 
                                       placeholder="Minimum 6 characters" required minlength="6">
                                <small style="color: #6b7280;">Admin should change this after first login</small>
                            </div>
                            
                            <div style="background-color: #fef2f2; border: 1px solid #ef4444; border-radius: 0.5rem; padding: 1rem; margin: 1.5rem 0;">
                                <h4 style="color: #dc2626; margin: 0 0 0.5rem 0;">
                                    <i class="fas fa-shield-alt"></i> Admin Account Security
                                </h4>
                                <p style="margin: 0; color: #374151; font-size: 0.9rem;">
                                    🔒 Admin accounts have full system access<br>
                                    📧 Welcome email with credentials will be sent automatically<br>
                                    ⚠️ Admin should change password immediately after first login
                                </p>
                            </div>
                            
                            <div style="text-align: center; margin-top: 2rem;">
                                <button type="submit" name="add_admin" class="btn btn-primary">
                                    <i class="fas fa-user-shield"></i> Add Admin & Send Welcome Email
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Account Settings Tab -->
    <div id="settings-tab" class="tab-content" style="display: none;">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-cog"></i> Account Settings</h3>
                <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.9rem;">
                    Update your admin account password and security settings
                </p>
            </div>
            <div class="card-body">
                <div style="max-width: 500px; margin: 0 auto;">
                    <h4 style="margin-bottom: 1rem; color: #333;">
                        <i class="fas fa-key"></i> Change Admin Password
                    </h4>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="current_password">Current Password <span style="color: red;">*</span></label>
                            <input type="password" id="current_password" name="current_password" class="form-control" 
                                   placeholder="Enter your current password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password <span style="color: red;">*</span></label>
                            <input type="password" id="new_password" name="new_password" class="form-control" 
                                   placeholder="Enter new password (minimum 6 characters)" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password <span style="color: red;">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                   placeholder="Confirm your new password" required>
                        </div>
                        
                        <div style="background-color: #fef2f2; border: 1px solid #dc2626; border-radius: 0.5rem; padding: 1rem; margin: 1.5rem 0;">
                            <h4 style="color: #dc2626; margin: 0 0 0.5rem 0;">
                                <i class="fas fa-shield-alt"></i> Admin Password Security Tips
                            </h4>
                            <ul style="margin: 0; color: #374151; font-size: 0.9rem;">
                                <li>Use at least 6 characters</li>
                                <li>Include a mix of letters, numbers, and symbols</li>
                                <li>Avoid using personal information</li>
                                <li>Never share your admin credentials with anyone</li>
                                <li>Change password regularly for enhanced security</li>
                            </ul>
                        </div>
                        
                        <div style="text-align: center; margin-top: 2rem;">
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-save"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(tab => {
                tab.style.display = 'none';
            });
            
            // Remove active class from all buttons
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab and activate button
            document.getElementById(tabName + '-tab').style.display = 'block';
            event.target.classList.add('active');
        }
    </script>
    
    <style>
        .tab-btn {
            padding: 1rem 1.5rem;
            background: transparent;
            border: none;
            color: #4a5568;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .tab-btn:hover,
        .tab-btn.active {
            color: #2c5aa0;
            border-bottom-color: #2c5aa0;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-success {
            background-color: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
        }
        
        .alert-error {
            background-color: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus {
            outline: 0;
            border-color: #2c5aa0;
            box-shadow: 0 0 0 0.2rem rgba(44, 90, 160, 0.25);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
        }
        
        .btn-primary {
            background-color: #2c5aa0;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #1e3a8a;
        }
        
        @media (max-width: 768px) {
            .tab-btn {
                padding: 0.75rem 1rem;
                font-size: 0.875rem;
            }
        }
    </style>
</body>
</html>