    </main>
    <!-- End Main Content -->

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section about">
                    <h3>About GameVerse</h3>
                    <p>Join the ultimate gaming community where players compete, collaborate, and rise through the ranks in our unique house-based gaming platform.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-discord"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-section links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php?page=home">Home</a></li>
                        <li><a href="index.php?page=about">About Us</a></li>
                        <li><a href="index.php?page=games">Games</a></li>
                        <li><a href="index.php?page=houses">Houses</a></li>
                        <li><a href="index.php?page=leaderboard">Leaderboards</a></li>
                    </ul>
                </div>
                
                <div class="footer-section contact">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-envelope"></i> support@gameverse.com</p>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                    <div class="newsletter">
                        <h4>Subscribe to our newsletter</h4>
                        <form action="#" method="post" class="newsletter-form">
                            <input type="email" name="email" placeholder="Your email" required>
                            <button type="submit" class="btn btn-primary">Subscribe</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> GameVerse Portal. All rights reserved.</p>
                <div class="footer-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="back-to-top" class="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="assets/js/main.js"></script>
    
    <!-- Page-specific JavaScript -->
    <?php if (isset($page_js)): ?>
        <script src="assets/js/<?php echo $page_js; ?>.js"></script>
    <?php endif; ?>
</body>
</html>
