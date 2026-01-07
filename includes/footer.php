<?php if (isset($isLoggedIn) && $isLoggedIn): ?>
        </div> <!-- Close main-content -->
    </div> <!-- Close container -->

<?php else: ?>
    </div> <!-- Close login-page -->

    <footer class="login-footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> Subdivision Management System</p>
            <p>Secure Access Portal</p>
        </div>
    </footer>
<?php endif; ?>

<!-- Common JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'red';
                } else {
                    field.style.borderColor = '';
                }
            });
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });

    const messages = document.querySelectorAll('.error, .success, .message');
    messages.forEach(message => {
        setTimeout(() => {
            message.style.display = 'none';
        }, 5000);
    });
});
</script>