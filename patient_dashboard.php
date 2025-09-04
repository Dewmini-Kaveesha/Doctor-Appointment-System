<?php
require_once 'config.php';

// PHPMailer includes for email functionality
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

if (!isLoggedIn('patient')) {
    redirectTo('login.php');
}

$patient_id = $_SESSION['patient_id'];
$patient_name = $_SESSION['patient_name'];

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $doctor_id = sanitize($_POST['doctor_id']);
    $appointment_date = sanitize($_POST['appointment_date']);
    $appointment_time = sanitize($_POST['appointment_time']);
    $symptoms = sanitize($_POST['symptoms']);
    
    if (!empty($doctor_id) && !empty($appointment_date) && !empty($appointment_time)) {
        try {
            // Check if the time slot is available
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
            $stmt->execute([$doctor_id, $appointment_date, $appointment_time]);
            
            if ($stmt->fetchColumn() == 0) {
                // Book the appointment
                $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, symptoms) VALUES (?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$patient_id, $doctor_id, $appointment_date, $appointment_time, $symptoms])) {
                    // Get patient email and doctor details for email notification
                    $stmt_patient = $pdo->prepare("SELECT email, name FROM patients WHERE id = ?");
                    $stmt_patient->execute([$patient_id]);
                    $patient_info = $stmt_patient->fetch();
                    
                    $stmt_doctor = $pdo->prepare("SELECT name, specialization, consultation_fee FROM doctors WHERE id = ?");
                    $stmt_doctor->execute([$doctor_id]);
                    $doctor_info = $stmt_doctor->fetch();
                    
                    // Send email notification
                    $mail = new PHPMailer(true);
                    try {
                        // Server settings from environment variables
                        $mail->isSMTP();
                        $mail->Host       = env('SMTP_HOST', 'smtp.gmail.com');
                        $mail->SMTPAuth   = true;
                        $mail->Username   = env('SMTP_USERNAME', 'your_email@gmail.com');
                        $mail->Password   = env('SMTP_PASSWORD', 'your_app_password');
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = env('SMTP_PORT', 587);

                        // Recipients
                        $mail->setFrom(env('SMTP_USERNAME', 'noreply@docq.com'), env('APP_NAME', 'DocQ Healthcare'));
                        $mail->addAddress($patient_info['email'], $patient_info['name']);

                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Appointment Confirmation - ' . $doctor_info['name'];
                        
                        $formatted_date = date('l, F j, Y', strtotime($appointment_date));
                        $formatted_time = date('g:i A', strtotime($appointment_time));
                        
                        $mail->Body = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                            <h2 style='color: #2c5aa0;'>Appointment Confirmation</h2>
                            
                            <p>Dear {$patient_info['name']},</p>
                            
                            <p>Your appointment has been successfully booked. Here are the details:</p>
                            
                            <div style='background-color: #f7fafc; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                                <h3 style='margin-top: 0; color: #2c5aa0;'>Appointment Details</h3>
                                <p><strong>Doctor:</strong> {$doctor_info['name']}</p>
                                <p><strong>Specialization:</strong> {$doctor_info['specialization']}</p>
                                <p><strong>Date:</strong> {$formatted_date}</p>
                                <p><strong>Time:</strong> {$formatted_time}</p>
                                <p><strong>Consultation Fee:</strong> Rs.{$doctor_info['consultation_fee']}</p>
                            </div>
                            
                            <p>Please arrive 15 minutes before your scheduled appointment time.</p>
                            
                            <p>If you need to cancel or reschedule, please do so through your patient dashboard.</p>
                            
                            <p>Thank you for choosing DocQ Healthcare!</p>
                            
                            <p>Best regards,<br>DocQ Healthcare Team</p>
                        </div>";

                        $mail->send();
                        $success = 'Appointment booked successfully! A confirmation email has been sent to your registered email address.';
                    } catch (Exception $e) {
                        $success = 'Appointment booked successfully! However, we could not send the confirmation email. Error: ' . $e->getMessage();
                    }
                } else {
                    $error = 'Failed to book appointment. Please try again.';
                }
            } else {
                $error = 'This time slot is already booked. Please choose another time.';
            }
        } catch (PDOException $e) {
            $error = 'Failed to book appointment. Please try again.';
        }
    } else {
        $error = 'Please fill all required fields.';
    }
}

// Handle appointment cancellation
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    try {
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND patient_id = ?");
        if ($stmt->execute([$_GET['cancel'], $patient_id])) {
            $success = 'Appointment cancelled successfully.';
        }
    } catch (PDOException $e) {
        $error = 'Failed to cancel appointment.';
    }
}

// Get patient's appointments
try {
    $stmt = $pdo->prepare("
        SELECT a.*, d.name as doctor_name, d.specialization, d.consultation_fee 
        FROM appointments a 
        JOIN doctors d ON a.doctor_id = d.id 
        WHERE a.patient_id = ? 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->execute([$patient_id]);
    $appointments = $stmt->fetchAll();
} catch (PDOException $e) {
    $appointments = [];
}

// Get available doctors
try {
    $stmt = $pdo->query("SELECT * FROM doctors WHERE status = 'active' ORDER BY name");
    $doctors = $stmt->fetchAll();
} catch (PDOException $e) {
    $doctors = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - HealthCare Plus</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="dashboard-nav">
                <h1><i class="fas fa-user"></i> Welcome, <?php echo htmlspecialchars($patient_name); ?></h1>
                <div>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-content">
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Book New Appointment -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-calendar-plus"></i> Book New Appointment</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="appointmentForm">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label for="doctor_id">Select Doctor</label>
                            <select name="doctor_id" id="doctor_id" required onchange="loadDoctorAvailability()">
                                <option value="">Choose a doctor</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?php echo $doctor['id']; ?>" 
                                            data-specialization="<?php echo htmlspecialchars($doctor['specialization']); ?>"
                                            data-fee="<?php echo $doctor['consultation_fee']; ?>">
                                        <?php echo htmlspecialchars($doctor['name']); ?> - <?php echo htmlspecialchars($doctor['specialization']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="appointment_date">Appointment Date</label>
                            <input type="date" name="appointment_date" id="appointment_date" 
                                   min="<?php echo date('Y-m-d'); ?>" 
                                   max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" 
                                   required onchange="loadAvailableSlots()">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label for="appointment_time">Available Time Slots</label>
                            <select name="appointment_time" id="appointment_time" required>
                                <option value="">Select date first</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Consultation Fee</label>
                            <div id="consultation_fee" style="padding: 1rem; background: #f7fafc; border-radius: 10px; font-weight: 600; color: #2c5aa0;">
                                Select a doctor to see fee
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="symptoms">Symptoms / Reason for Visit</label>
                        <textarea name="symptoms" id="symptoms" placeholder="Describe your symptoms or reason for the appointment" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" name="book_appointment" class="btn btn-primary" >
                        <i class="fas fa-calendar-check"></i> Book Appointment
                    </button>
                </form>
            </div>
        </div>

        <!-- My Appointments -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-calendar-alt"></i> My Appointments</h3>
            </div>
            <div class="card-body">
                <?php if (empty($appointments)): ?>
                    <div style="text-align: center; padding: 2rem; color: #4a5568;">
                        <i class="fas fa-calendar-times" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No appointments found. Book your first appointment above.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Doctor</th>
                                    <th>Specialization</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Fee</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['specialization']); ?></td>
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
                                            <?php if ($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed'): ?>
                                                <a href="?cancel=<?php echo $appointment['id']; ?>" 
                                                   class="btn btn-outline" 
                                                   style="padding: 0.25rem 0.75rem; font-size: 0.85rem;"
                                                   onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                    <i class="fas fa-times"></i> Cancel
                                                </a>
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

        <!-- Today's Doctor Schedule Widget -->
        <?php include 'timetable_widget.php'; ?>
    </div>

    <script src="js/script.js"></script>
    <script>
        function loadDoctorAvailability() {
            const doctorSelect = document.getElementById('doctor_id');
            const feeDiv = document.getElementById('consultation_fee');
            const selectedOption = doctorSelect.options[doctorSelect.selectedIndex];
            
            if (selectedOption.value) {
                const fee = selectedOption.dataset.fee;
                feeDiv.innerHTML = 'Rs.' + parseFloat(fee).toFixed(2);
            } else {
                feeDiv.innerHTML = 'Select a doctor to see fee';
            }
            
            // Reset time slots
            const timeSelect = document.getElementById('appointment_time');
            timeSelect.innerHTML = '<option value="">Select date to see available slots</option>';
        }
        
        function loadAvailableSlots() {
            const doctorId = document.getElementById('doctor_id').value;
            const selectedDate = document.getElementById('appointment_date').value;
            const timeSelect = document.getElementById('appointment_time');
            
            if (!doctorId || !selectedDate) {
                timeSelect.innerHTML = '<option value="">Select doctor and date first</option>';
                return;
            }
            
            // Generate time slots (this is a simplified version)
            const slots = [];
            const selectedDay = new Date(selectedDate).toLocaleDateString('en-US', { weekday: 'long' });
            
            // Generate hourly slots from 9 AM to 5 PM (this should ideally come from doctor availability)
            for (let hour = 9; hour <= 17; hour++) {
                const time = String(hour).padStart(2, '0') + ':00';
                const timeLabel = (hour <= 12 ? hour : hour - 12) + ':00 ' + (hour < 12 ? 'AM' : 'PM');
                slots.push({ value: time, label: timeLabel });
            }
            
            timeSelect.innerHTML = '<option value="">Select a time slot</option>';
            slots.forEach(slot => {
                const option = document.createElement('option');
                option.value = slot.value;
                option.textContent = slot.label;
                timeSelect.appendChild(option);
            });
        }
    </script>
</body>
</html>