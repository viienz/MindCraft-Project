        </main>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <div class="logo">
                        <i class="fas fa-brain logo-icon"></i>
                        <span>MindCraft</span>
                    </div>
                    <p class="footer-description">Platform pembelajaran online untuk mengembangkan keterampilan dan pengetahuan.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-links">
                    <div class="footer-column">
                        <h4 class="footer-title">Perusahaan</h4>
                        <ul>
                            <li><a href="#">Tentang Kami</a></li>
                            <li><a href="#">Karir</a></li>
                            <li><a href="#">Blog</a></li>
                            <li><a href="#">Mitra</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h4 class="footer-title">Untuk Mentor</h4>
                        <ul>
                            <li><a href="#">Mulai Mengajar</a></li>
                            <li><a href="#">Panduan Mentor</a></li>
                            <li><a href="#">Kebijakan</a></li>
                            <li><a href="#">FAQ</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h4 class="footer-title">Dukungan</h4>
                        <ul>
                            <li><a href="#">Bantuan</a></li>
                            <li><a href="#">Hubungi Kami</a></li>
                            <li><a href="#">Kebijakan Privasi</a></li>
                            <li><a href="#">Syarat & Ketentuan</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p class="copyright">&copy; <?php echo date('Y'); ?> MindCraft. All rights reserved.</p>
                <div class="footer-legal">
                    <a href="#">Kebijakan Privasi</a>
                    <a href="#">Syarat & Ketentuan</a>
                </div>
            </div>
        </div>
    </footer>

    <style>
        .main-footer {
            background-color: var(--gray-900);
            color: white;
            padding: 3rem 0;
            margin-left: 280px;
            transition: all 0.3s ease;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }
        
        .footer-brand {
            max-width: 300px;
        }
        
        .footer-brand .logo {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: white;
        }
        
        .footer-description {
            color: var(--gray-400);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
        }
        
        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: var(--rounded-full);
            background-color: var(--gray-700);
            color: white;
            transition: all 0.2s ease;
        }
        
        .social-link:hover {
            background-color: var(--primary);
            transform: translateY(-2px);
        }
        
        .footer-links {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }
        
        .footer-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            color: white;
        }
        
        .footer-column ul {
            list-style: none;
        }
        
        .footer-column li {
            margin-bottom: 0.75rem;
        }
        
        .footer-column a {
            color: var(--gray-400);
            transition: all 0.2s ease;
        }
        
        .footer-column a:hover {
            color: white;
        }
        
        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-700);
        }
        
        .copyright {
            color: var(--gray-400);
            font-size: 0.875rem;
        }
        
        .footer-legal {
            display: flex;
            gap: 1.5rem;
        }
        
        .footer-legal a {
            color: var(--gray-400);
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .footer-legal a:hover {
            color: white;
        }
        
        /* Responsive Styles */
        @media (max-width: 1024px) {
            .main-footer {
                margin-left: 0;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
            
            .footer-brand {
                max-width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .footer-links {
                grid-template-columns: 1fr;
            }
            
            .footer-bottom {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .footer-legal {
                justify-content: center;
            }
        }
    </style>

    <script>
        // Mobile menu toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.getElementById('sidebar');
            
            if (mobileMenuToggle && sidebar) {
                mobileMenuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const isClickInsideSidebar = sidebar.contains(event.target);
                const isClickOnMenuToggle = mobileMenuToggle.contains(event.target);
                
                if (window.innerWidth <= 1024 && !isClickInsideSidebar && !isClickOnMenuToggle) {
                    sidebar.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>