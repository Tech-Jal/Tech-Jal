    <!-- Footer -->
    <footer class="bg-dark text-light py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3">About FoodFusion</h5>
                    <p class="mb-3">Your ultimate destination for culinary exploration and creativity. Join our community of food enthusiasts and discover amazing recipes.</p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-light me-3"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-light me-3"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-light me-3"><i class="bi bi-youtube"></i></a>
                        <a href="#" class="text-light"><i class="bi bi-pinterest"></i></a>
                    </div>
                </div>
                
                <div class="col-md-2 mb-4">
                    <h5 class="mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/recipes.php" class="text-light text-decoration-none">Recipes</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/cookbook.php" class="text-light text-decoration-none">Cookbook</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/resources.php" class="text-light text-decoration-none">Resources</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/about.php" class="text-light text-decoration-none">About Us</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/contact.php" class="text-light text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3">Categories</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/recipes.php?category=breakfast" class="text-light text-decoration-none">Breakfast</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/recipes.php?category=lunch" class="text-light text-decoration-none">Lunch</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/recipes.php?category=dinner" class="text-light text-decoration-none">Dinner</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/recipes.php?category=desserts" class="text-light text-decoration-none">Desserts</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/recipes.php?category=healthy" class="text-light text-decoration-none">Healthy</a></li>
                    </ul>
                </div>
                
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3">Newsletter</h5>
                    <p class="mb-3">Subscribe to our newsletter for the latest recipes and cooking tips.</p>
                    <form action="<?php echo SITE_URL; ?>/subscribe.php" method="POST" class="mb-3">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <div class="input-group">
                            <input type="email" class="form-control" name="email" placeholder="Your email address" required>
                            <button class="btn btn-primary" type="submit">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> FoodFusion. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item">
                            <a href="<?php echo SITE_URL; ?>/terms.php" class="text-light text-decoration-none">Terms of Service</a>
                        </li>
                        <li class="list-inline-item mx-3">|</li>
                        <li class="list-inline-item">
                            <a href="<?php echo SITE_URL; ?>/privacy.php" class="text-light text-decoration-none">Privacy Policy</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="<?php echo SITE_URL; ?>/bstp/js/bootstrap.bundle.min.js"></script>
    
    <!-- Cookie Consent JS -->
    <script>
        function acceptCookies() {
            document.getElementById('cookieConsent').style.display = 'none';
            fetch('<?php echo SITE_URL; ?>/ajax/cookies.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=accept'
            });
        }

        function rejectCookies() {
            document.getElementById('cookieConsent').style.display = 'none';
            fetch('<?php echo SITE_URL; ?>/ajax/cookies.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=reject'
            });
        }
    </script>

    <?php if (isset($additionalJs)): ?>
        <script><?php echo $additionalJs; ?></script>
    <?php endif; ?>
</body>
</html> 