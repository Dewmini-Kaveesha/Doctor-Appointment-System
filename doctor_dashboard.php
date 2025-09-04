<?php
require_once 'config.php';

if (!isLoggedIn('doctor')) {
    redirectTo('login.php');
}

$doctor_id = $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'];
$success = '';
$error = '';

// Handle password change
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
            $stmt = $pdo->prepare("SELECT password FROM doctors WHERE id = ?");
            $stmt->execute([$doctor_id]);
            $doctor = $stmt->fetch();
            
            if ($doctor && verifyPassword($current_password, $doctor['password'])) {
                // Update password
                $new_password_hash = hashPassword($new_password);
                $stmt = $pdo->prepare("UPDATE doctors SET password = ? WHERE id = ?");
                if ($stmt->execute([$new_password_hash, $doctor_id])) {
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

// Handle appointment status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $appointment_id = sanitize($_POST['appointment_id']);
    $new_status = sanitize($_POST['status']);
    $notes = sanitize($_POST['notes']);
    
    try {
        $stmt = $pdo->prepare("UPDATE appointments SET status = ?, notes = ? WHERE id = ? AND doctor_id = ?");
        if ($stmt->execute([$new_status, $notes, $appointment_id, $doctor_id])) {
            $success = 'Appointment status updated successfully.';
        } else {
            $error = 'Failed to update appointment status.';
        }
    } catch (PDOException $e) {
        $error = 'Failed to update appointment status.';
    }
}

// Get doctor's appointments
try {
    $stmt = $pdo->prepare("
        SELECT a.*, p.name as patient_name, p.phone, p.email, p.date_of_birth, p.gender, p.blood_group
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.id 
        WHERE a.doctor_id = ? 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->execute([$doctor_id]);
    $appointments = $stmt->fetchAll();
} catch (PDOException $e) {
    $appointments = [];
}

// Get appointment statistics
try {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_appointments,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_appointments,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_appointments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
            SUM(CASE WHEN DATE(appointment_date) = CURDATE() THEN 1 ELSE 0 END) as today_appointments
        FROM appointments 
        WHERE doctor_id = ?
    ");
    $stmt->execute([$doctor_id]);
    $stats = $stmt->fetch();
} catch (PDOException $e) {
    $stats = ['total_appointments' => 0, 'pending_appointments' => 0, 'confirmed_appointments' => 0, 'completed_appointments' => 0, 'today_appointments' => 0];
}

// Get doctor's availability
try {
    $stmt = $pdo->prepare("SELECT * FROM doctor_availability WHERE doctor_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')");
    $stmt->execute([$doctor_id]);
    $availability = $stmt->fetchAll();
} catch (PDOException $e) {
    $availability = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - HealthCare Plus</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Tab and Form Styles */
        .quick-btn {
            padding: 1rem 1.5rem;
            background: transparent;
            border: 2px solid #e2e8f0;
            color: #4a5568;
            font-weight: 500;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quick-btn:hover {
            background: #f7fafc;
            border-color: #2c5aa0;
            color: #2c5aa0;
        }
        
        .quick-btn.active {
            background: #2c5aa0;
            border-color: #2c5aa0;
            color: white;
        }
        
        .content-section {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(44, 90, 160, 0.3);
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
    </style>
</head>
<body>
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="dashboard-nav">
                <h1><i class="fas fa-user-md"></i> <?php echo htmlspecialchars($doctor_name); ?></h1>
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

        <!-- Quick Actions -->
        <div style="display: flex; gap: 1rem; margin-bottom: 2rem; justify-content: center; flex-wrap: wrap;">
            <button onclick="showSection('dashboard')" class="quick-btn active" id="dashboard-btn">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </button>
            <button onclick="showSection('appointments')" class="quick-btn" id="appointments-btn">
                <i class="fas fa-calendar-alt"></i> Appointments
            </button>
            <button onclick="showSection('settings')" class="quick-btn" id="settings-btn">
                <i class="fas fa-cog"></i> Account Settings
            </button>
        </div>

        <!-- Dashboard Section -->
        <div id="dashboard-section" class="content-section">
        <!-- Statistics Dashboard -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="card" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; color: #2c5aa0; margin-bottom: 0.5rem;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3 style="font-size: 1.8rem; margin-bottom: 0.5rem; color: #2c5aa0;">
                    <?php echo $stats['total_appointments']; ?>
                </h3>
                <p style="color: #4a5568;">Total Appointments</p>
            </div>
            
            <div class="card" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; color: #f59e0b; margin-bottom: 0.5rem;">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 style="font-size: 1.8rem; margin-bottom: 0.5rem; color: #f59e0b;">
                    <?php echo $stats['pending_appointments']; ?>
                </h3>
                <p style="color: #4a5568;">Pending Appointments</p>
            </div>
            
            <div class="card" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; color: #10b981; margin-bottom: 0.5rem;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 style="font-size: 1.8rem; margin-bottom: 0.5rem; color: #10b981;">
                    <?php echo $stats['confirmed_appointments']; ?>
                </h3>
                <p style="color: #4a5568;">Confirmed Appointments</p>
            </div>
            
            <div class="card" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 2rem; color: #6366f1; margin-bottom: 0.5rem;">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <h3 style="font-size: 1.8rem; margin-bottom: 0.5rem; color: #6366f1;">
                    <?php echo $stats['today_appointments']; ?>
                </h3>
                <p style="color: #4a5568;">Today's Appointments</p>
            </div>
        </div>

        <!-- My Schedule -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-calendar-week"></i> My Schedule</h3>
            </div>
            <div class="card-body">
                <?php if (empty($availability)): ?>
                    <p style="text-align: center; color: #4a5568;">No schedule set. Please contact admin to set your availability.</p>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                        <?php foreach ($availability as $schedule): ?>
                            <div style="background: #f7fafc; padding: 1rem; border-radius: 10px; text-align: center;">
                                <h4 style="color: #2c5aa0; margin-bottom: 0.5rem;"><?php echo $schedule['day_of_week']; ?></h4>
                                <p style="color: #4a5568; font-size: 0.9rem;">
                                    <?php echo date('h:i A', strtotime($schedule['start_time'])); ?> - 
                                    <?php echo date('h:i A', strtotime($schedule['end_time'])); ?>
                                </p>
                                <span class="badge <?php echo $schedule['status'] == 'available' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ucfirst($schedule['status']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        </div>

        <!-- Appointments Section -->
        <div id="appointments-section" class="content-section" style="display: none;">
        <!-- Appointments Management -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list-alt"></i> Appointments Management</h3>
            </div>
            <div class="card-body">
                <?php if (empty($appointments)): ?>
                    <div style="text-align: center; padding: 2rem; color: #4a5568;">
                        <i class="fas fa-calendar-times" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No appointments found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Patient Details</th>
                                    <th>Date & Time</th>
                                    <th>Symptoms</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($appointment['patient_name']); ?></strong><br>
                                            <small style="color: #4a5568;">
                                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($appointment['email']); ?><br>
                                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($appointment['phone']); ?><br>
                                                <i class="fas fa-birthday-cake"></i> <?php echo date('M d, Y', strtotime($appointment['date_of_birth'])); ?> 
                                                (<?php echo $appointment['gender']; ?>)
                                                <?php if ($appointment['blood_group']): ?>
                                                    <br><i class="fas fa-tint"></i> <?php echo $appointment['blood_group']; ?>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?><br>
                                            <small style="color: #4a5568;"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></small>
                                        </td>
                                        <td>
                                            <?php echo $appointment['symptoms'] ? htmlspecialchars($appointment['symptoms']) : '<em>No symptoms mentioned</em>'; ?>
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
                                        <td>
                                            <?php if ($appointment['status'] != 'cancelled'): ?>
                                                <button type="button" class="btn btn-primary" 
                                                        style="padding: 0.25rem 0.75rem; font-size: 0.85rem;"
                                                        onclick="openUpdateModal(<?php echo $appointment['id']; ?>, '<?php echo $appointment['status']; ?>', '<?php echo htmlspecialchars($appointment['notes'] ?? ''); ?>')">
                                                    <i class="fas fa-edit"></i> Update
                                                </button>
                                            <?php endif; ?>
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

    <!-- Account Settings Section -->
    <div id="settings-section" class="content-section" style="display: none;">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-cog"></i> Account Settings</h3>
                <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.9rem;">
                    Update your account password and security settings
                </p>
            </div>
            <div class="card-body">
                <div style="max-width: 500px; margin: 0 auto;">
                    <h4 style="margin-bottom: 1rem; color: #333;">
                        <i class="fas fa-key"></i> Change Password
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
                        
                        <div style="background-color: #f0f9ff; border: 1px solid #3b82f6; border-radius: 0.5rem; padding: 1rem; margin: 1.5rem 0;">
                            <h4 style="color: #1e40af; margin: 0 0 0.5rem 0;">
                                <i class="fas fa-shield-alt"></i> Password Security Tips
                            </h4>
                            <ul style="margin: 0; color: #374151; font-size: 0.9rem;">
                                <li>Use at least 6 characters</li>
                                <li>Include a mix of letters, numbers, and symbols</li>
                                <li>Avoid using personal information</li>
                                <li>Don't reuse passwords from other accounts</li>
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

    <!-- Update Status Modal -->
    <div id="updateModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 15px; width: 90%; max-width: 500px;">
            <h3 style="margin-bottom: 1.5rem; color: #1a202c;">
                <i class="fas fa-edit"></i> Update Appointment Status
            </h3>
            
            <form method="POST" action="">
                <input type="hidden" name="appointment_id" id="modal_appointment_id">
                
                <div class="form-group">
                    <label for="modal_status">Status</label>
                    <select name="status" id="modal_status" required>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="modal_notes">Notes</label>
                    <textarea name="notes" id="modal_notes" placeholder="Add any notes or prescriptions..." rows="4"></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeUpdateModal()">
                        Cancel
                    </button>
                    <button type="submit" name="update_status" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        function openUpdateModal(appointmentId, currentStatus, currentNotes) {
            document.getElementById('modal_appointment_id').value = appointmentId;
            document.getElementById('modal_status').value = currentStatus;
            document.getElementById('modal_notes').value = currentNotes;
            document.getElementById('updateModal').style.display = 'block';
        }
        
        function closeUpdateModal() {
            document.getElementById('updateModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('updateModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeUpdateModal();
            }
        });
        
        // Tab switching functionality
        function showSection(sectionName) {
            // Hide all sections
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(section => {
                section.style.display = 'none';
            });
            
            // Remove active class from all buttons
            const buttons = document.querySelectorAll('.quick-btn');
            buttons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionName + '-section').style.display = 'block';
            
            // Add active class to clicked button
            document.getElementById(sectionName + '-btn').classList.add('active');
        }
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
                