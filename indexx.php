<?php
require_once 'config.php';
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocQ - Doctor Appointment Booking System</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
            
                <i class="fas fa-heartbeat" alt="nav-logo"></i>
                <span>DocQ</span>
            </div>
            <div class="nav-menu">
                <a href="#home" class="nav-link">Home</a>
                <a href="#doctors" class="nav-link">Doctors</a>
                <a href="timetable.php" class="nav-link">Time table</a>
                <a href="#about" class="nav-link">About</a>
                <a href="#contact" class="nav-link">Contact</a>
                <div class="nav-loginReg">
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="patient_register.php" class="btn btn-primary">Register</a>
                </div>
            </div>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

     <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Your Health, Our </h1>
                <h1>Priority</h1>
                <p>Book appointments with qualified doctors online.Get the best healthcare service with just a few clicks.</p>
                <div class="hero-buttons">
                    <a href="patient_register.php" class="btn btn-primary btn-large">Book Appointment</a>
                    <a href="#doctors" class="btn btn-outline btn-large">Find Doctors</a>
               
                <!--<div class="hero-stats">
                    <div class="stat">
                        <h3>50+</h3>
                        <p>Expert Doctors</p>
                    </div>
                    <div class="stat">
                        <h3>1000+</h3>
                        <p>Happy Patients</p>
                    </div>
                    <div class="stat">
                        <h3>24/7</h3>
                        <p>Emergency Care</p>
                    </div>
                </div>-->
            </div>
            </div>
            <div class="hero-image">
                    <img src="dr1.png" alt="Healthcare" >
            </div>
        </div>
    </section>

        <!-- Doctors Section -->
    <!--<section id="doctors" class="doctors">
        <div class="container">
            <div class="section-header">
                <h2>Our Expert Doctors</h2>
                <p>Meet our team of qualified healthcare professionals</p>
            </div>
            <div class="doctors-grid">
             
       
            </div>
            <div class="text-center">
                <a href="login.php" class="doctorbtn-primary">View All Doctors</a>
            </div>
        </div>
    </section>-->

     <!-- Doctors Section -->
    <section id="doctors" class="doctors">
        <div class="container">
            <div class="section-header">
                <h2>Our Expert Doctors</h2>
                <p>Meet our team of qualified healthcare professionals</p>
            </div>
            <div class="doctors-grid">
        </div>
            <div class="text-center">
                <a href="doctor_dashboard.php" class="doctorbtn-primary">View All Doctors</a>
            </div>
        </div>


               <!-- <?php
                try {
                    $stmt = $pdo->query("SELECT * FROM doctors WHERE status = 'active' LIMIT 4");
                    $doctors = $stmt->fetchAll();
                    
                    foreach ($doctors as $doctor) {
                        echo '<div class="doctor-card">';
                        echo '<div class="doctor-image">';
                        echo '<img src="doctor.jpg" alt="' . htmlspecialchars($doctor['name']) . '">';
                        echo '</div>';
                        echo '<div class="doctor-info">';
                        echo '<h3>' . htmlspecialchars($doctor['name']) . '</h3>';
                        echo '<p class="specialization">' . htmlspecialchars($doctor['specialization']) . '</p>';
                        echo '<p class="experience">' . $doctor['experience_years'] . ' years experience</p>';
                        echo '<p class="qualification">' . htmlspecialchars($doctor['qualification']) . '</p>';
                        echo '<div class="doctor-fee">Rs.' . $doctor['consultation_fee'] . '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                } catch (PDOException $e) {
                    echo '<p>Error loading doctors information.</p>';
                }
                ?>

    <!-About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>About DocQ</h2>
                    <p>DocQ is a leading healthcare provider committed to delivering exceptional medical services. With state-of-the-art facilities and a team of experienced professionals, we ensure that you receive the best possible care.</p>
                    <div class="about-features">
                        <!--<div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span>24/7 Emergency Services</span>
                        </div>-->
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Experienced Medical Team</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Modern Equipment</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Patient-Centered Care</span>
                        </div>
                    </div>
                </div>
                <div class="about-image">
                    <img src="dr2.png" alt="About Us">
                </div>
            </div>
        </div>
    </section>


    
    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <div class="section-header">
                <h2>Contact Us</h2>
                <p>Get in touch with us for any queries or emergency assistance</p>
            </div>
            <div class="contact-content">
                <div class="contact-info">
                    <!--<div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h4>Address</h4>
                            <p>####</p>
                        </div>
                    </div>-->
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h4>Phone</h4>
                            <p>0811111111</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>Email</h4>
                            <p>info@docq.com</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h4>Working Hours</h4>
                            <p>Mon - Fri: 8:00 AM - 10:00 PM<br>Sat - Sun: 9:00 AM - 6:00 PM</p>
                        </div>
                    </div>
                </div>
                <div class="contact-form">
                    <form>
                        <div class="form-group">
                            <input type="text" placeholder="Your Name" required>
                        </div>
                        <div class="form-group">
                            <input type="email" placeholder="Your Email" required>
                        </div>
                        <div class="form-group">
                            <input type="tel" placeholder="Your Phone" required>
                        </div>
                        <div class="form-group">
                            <textarea placeholder="Your Message" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-heartbeat"></i>
                        <span>DocQ</span>
                    </div>
                    <p>Providing quality healthcare services with compassion and excellence. Your health and well-being are our top priorities.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#doctors">Doctors</a></li>
                        <li><a href="timetable.php">Time Table</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
                <!--<div class="footer-section">
                    <h4>Services</h4>
                    <ul>
                        <li><a href="#">Emergency Care</a></li>
                        <li><a href="#">Surgery</a></li>
                        <li><a href="#">Cardiology</a></li>
                        <li><a href="#">Neurology</a></li>
                        <li><a href="#">Pediatrics</a></li>
                    </ul>
                </div>-->
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <div class="contact-info">
                        <!--<p><i class="fas fa-map-marker-alt"></i> ####</p>-->
                        <p><i class="fas fa-phone"></i> 0811111111</p>
                        <p><i class="fas fa-envelope"></i> info@docq.com</p>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 DocQ. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="javascript.js"></script>
</body>
</html>