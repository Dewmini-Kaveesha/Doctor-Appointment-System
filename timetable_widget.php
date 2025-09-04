<?php
// This is a widget version of the timetable that can be included in dashboards
if (!isset($pdo)) {
    require_once 'config.php';
}

// Get today's date and day
$today = date('Y-m-d');
$currentDay = date('l');

// Get doctors available today
$todayAvailabilityQuery = "
    SELECT d.*, da.start_time, da.end_time 
    FROM doctors d 
    JOIN doctor_availability da ON d.id = da.doctor_id 
    WHERE d.status = 'active' 
    AND da.day_of_week = ? 
    AND da.status = 'available'
    ORDER BY d.name
";
$todayDoctors = $pdo->prepare($todayAvailabilityQuery);
$todayDoctors->execute([$currentDay]);
$availableDoctors = $todayDoctors->fetchAll();

// Get today's appointments
$todayAppointmentsQuery = "
    SELECT a.*, d.name as doctor_name 
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    WHERE a.appointment_date = ?
    AND a.status IN ('pending', 'confirmed')
    ORDER BY a.appointment_time
";
$todayAppointments = $pdo->prepare($todayAppointmentsQuery);
$todayAppointments->execute([$today]);
$appointments = $todayAppointments->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-calendar-check"></i> Today's Schedule (<?php echo date('l, F j'); ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($availableDoctors)): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <?php foreach ($availableDoctors as $doctor): ?>
                    <div style="border: 1px solid #e1e8ed; border-radius: 8px; padding: 1rem;">
                        <h4 style="margin: 0 0 0.5rem 0; color: #333;">
                            <i class="fas fa-user-md"></i> <?php echo htmlspecialchars($doctor['name']); ?>
                        </h4>
                        <p style="margin: 0 0 0.5rem 0; color: #666; font-size: 0.9rem;">
                            <?php echo htmlspecialchars($doctor['specialization']); ?>
                        </p>
                        <p style="margin: 0 0 1rem 0; color: #48bb78; font-weight: 500;">
                            <i class="fas fa-clock"></i> 
                            <?php echo date('g:i A', strtotime($doctor['start_time'])); ?> - 
                            <?php echo date('g:i A', strtotime($doctor['end_time'])); ?>
                        </p>
                        
                        <!-- Show booked slots for this doctor -->
                        <?php
                        $doctorBookings = array_filter($appointments, function($apt) use ($doctor) {
                            return $apt['doctor_id'] == $doctor['id'];
                        });
                        ?>
                        
                        <?php if (!empty($doctorBookings)): ?>
                            <div style="background: #f8f9fa; padding: 0.5rem; border-radius: 4px;">
                                <strong style="font-size: 0.8rem; color: #666;">Booked:</strong>
                                <?php foreach ($doctorBookings as $booking): ?>
                                    <div style="font-size: 0.8rem; color: #dc3545; margin-top: 0.25rem;">
                                        <i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($booking['appointment_time'])); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="font-size: 0.8rem; color: #38a169;">
                                <i class="fas fa-check-circle"></i> Available for appointments
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 1rem;">
                <a href="timetable.php" class="btn btn-primary">
                    <i class="fas fa-calendar-alt"></i> View Full Timetable
                </a>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 2rem; color: #666;">
                <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                <p>No doctors available today (<?php echo $currentDay; ?>)</p>
                <a href="timetable.php" class="btn btn-outline">View Weekly Schedule</a>
            </div>
        <?php endif; ?>
    </div>
</div>
