<?php
require_once 'config.php';

// Get all active doctors with their availability
$doctorsQuery = "
    SELECT d.*, da.day_of_week, da.start_time, da.end_time, da.status as availability_status
    FROM doctors d 
    LEFT JOIN doctor_availability da ON d.id = da.doctor_id 
    WHERE d.status = 'active' 
    ORDER BY d.name, 
    FIELD(da.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
";

$doctors = $pdo->query($doctorsQuery)->fetchAll();

// Group doctors by their ID
$doctorSchedules = [];
foreach ($doctors as $doctor) {
    $doctorId = $doctor['id'];
    if (!isset($doctorSchedules[$doctorId])) {
        $doctorSchedules[$doctorId] = [
            'info' => $doctor,
            'schedule' => []
        ];
    }
    if ($doctor['day_of_week']) {
        $doctorSchedules[$doctorId]['schedule'][] = $doctor;
    }
}

// Get appointments for the next 7 days to show booked slots
$today = date('Y-m-d');
$nextWeek = date('Y-m-d', strtotime('+7 days'));
$appointmentsQuery = "
    SELECT a.*, d.name as doctor_name, p.name as patient_name
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN patients p ON a.patient_id = p.id
    WHERE a.appointment_date BETWEEN ? AND ?
    AND a.status IN ('pending', 'confirmed')
    ORDER BY a.appointment_date, a.appointment_time
";
$weekAppointments = $pdo->prepare($appointmentsQuery);
$weekAppointments->execute([$today, $nextWeek]);
$appointments = $weekAppointments->fetchAll();

// Group appointments by doctor and date
$doctorAppointments = [];
foreach ($appointments as $appointment) {
    $doctorId = $appointment['doctor_id'];
    $appointmentDate = $appointment['appointment_date'];
    $doctorAppointments[$doctorId][$appointmentDate][] = $appointment;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Timetable - DocQ</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .timetable-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }
        .timetable-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .doctor-schedule {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .doctor-header {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 1.5rem;
        }
        .doctor-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .doctor-avatar {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            padding: 1.5rem;
        }
        .day-schedule {
            border: 1px solid #e1e8ed;
            border-radius: 10px;
            overflow: hidden;
        }
        .day-header {
            background: #f8f9fa;
            padding: 0.75rem;
            font-weight: 600;
            text-align: center;
            border-bottom: 1px solid #e1e8ed;
        }
        .time-slots {
            padding: 1rem;
        }
        .time-slot {
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .available {
            background: #c6f6d5;
            border: 1px solid #9ae6b4;
            color: #22543d;
        }
        .booked {
            background: #fed7d7;
            border: 1px solid #feb2b2;
            color: #742a2a;
        }
        .unavailable {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #6c757d;
        }
        .no-schedule {
            padding: 1rem;
            text-align: center;
            color: #6c757d;
            font-style: italic;
        }
        .today-highlight {
            border: 2px solid #48bb78;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <i class="fas fa-heartbeat"></i>
                <span>DocQ</span>
            </div>
            <div class="nav-menu">
                <a href="indexx.php" class="nav-link">Home</a>
                <?php if (isset($_SESSION['patient_id'])): ?>
                    <a href="patient_dashboard.php" class="nav-link">Dashboard</a>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                <?php elseif (isset($_SESSION['doctor_id'])): ?>
                    <a href="doctor_dashboard.php" class="nav-link">Dashboard</a>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                <?php elseif (isset($_SESSION['admin_id'])): ?>
                    <a href="admin_dashboard.php" class="nav-link">Dashboard</a>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="patient_register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="timetable-container">
        <div class="timetable-header">
            <h1><i class="fas fa-calendar-alt"></i> Doctor Timetable</h1>
            <p>View doctor availability and booking status</p>
            <p><strong>Today: <?php echo date('l, F j, Y'); ?></strong></p>
        </div>

        <?php foreach ($doctorSchedules as $doctorId => $doctorData): ?>
            <div class="doctor-schedule">
                <div class="doctor-header">
                    <div class="doctor-info">
                        <div class="doctor-avatar">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div>
                            <h3><?php echo htmlspecialchars($doctorData['info']['name']); ?></h3>
                            <p><?php echo htmlspecialchars($doctorData['info']['specialization']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="schedule-grid">
                    <?php 
                    $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    $currentDay = date('l'); // Current day name
                    
                    foreach ($daysOfWeek as $day): 
                        // Find schedule for this day
                        $daySchedule = null;
                        foreach ($doctorData['schedule'] as $schedule) {
                            if ($schedule['day_of_week'] === $day) {
                                $daySchedule = $schedule;
                                break;
                            }
                        }
                    ?>
                        <div class="day-schedule <?php echo ($day === $currentDay) ? 'today-highlight' : ''; ?>">
                            <div class="day-header">
                                <?php echo $day; ?>
                                <?php if ($day === $currentDay): ?>
                                    <span style="color: #48bb78;"> (Today)</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($daySchedule && $daySchedule['availability_status'] === 'available'): ?>
                                <div class="time-slots">
                                    <?php
                                    $startTime = strtotime($daySchedule['start_time']);
                                    $endTime = strtotime($daySchedule['end_time']);
                                    
                                    // Generate hourly slots
                                    for ($time = $startTime; $time < $endTime; $time += 3600) {
                                        $slotTime = date('H:i', $time);
                                        $slotEndTime = date('H:i', $time + 3600);
                                        
                        // Check if this slot is booked
                        $isBooked = false;
                        $patientName = '';
                        
                        // Calculate the date for this day of the week
                        $currentWeekStart = strtotime('monday this week');
                        $dayIndex = array_search($day, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
                        $dayDate = date('Y-m-d', $currentWeekStart + ($dayIndex * 24 * 60 * 60));
                        
                        if (isset($doctorAppointments[$doctorId][$dayDate])) {
                            foreach ($doctorAppointments[$doctorId][$dayDate] as $appointment) {
                                $appointmentTime = date('H:i', strtotime($appointment['appointment_time']));
                                if ($appointmentTime === $slotTime) {
                                    $isBooked = true;
                                    $patientName = $appointment['patient_name'];
                                    break;
                                }
                            }
                        }
                        
                        $slotClass = $isBooked ? 'booked' : 'available';
                        $slotText = $slotTime . ' - ' . $slotEndTime;
                        if ($isBooked) {
                            $slotText .= '<br><small>Booked</small>';
                        }
                                    ?>
                                        <div class="time-slot <?php echo $slotClass; ?>">
                                            <?php echo $slotText; ?>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php else: ?>
                                <div class="no-schedule">
                                    <div class="time-slot unavailable">
                                        Not Available
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($doctorSchedules)): ?>
            <div class="doctor-schedule">
                <div class="no-schedule">
                    <h3>No doctors available</h3>
                    <p>Please check back later or contact administration.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Legend -->
    <div class="timetable-container">
        <div style="background: white; padding: 1rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h4>Legend:</h4>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <div class="time-slot available" style="margin: 0;">Available</div>
                <div class="time-slot booked" style="margin: 0;">Booked</div>
                <div class="time-slot unavailable" style="margin: 0;">Not Available</div>
            </div>
        </div>
    </div>

    <script src="javascript.js"></script>
</body>
</html>
