<?php
include 'homeheader.php';

// Get parameters from URL
$email = isset($_GET['email']) ? $_GET['email'] : '';
$verification_code = isset($_GET['code']) ? $_GET['code'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Email Verification - Ecocycle</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="author" content="">
    <meta name="keywords" content="">
    <meta name="description" content="">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="main-content">
                <div class="container-lg mt-5">
                    <!-- Breadcrumb -->
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="home.php" class="text-decoration-none">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Email Verification</li>
                        </ol>
                    </nav>

                    <div class="row justify-content-center">
                        <div class="col-lg-6">
                            <!-- Payment Details Form -->
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Details</h5>
                                </div>
                                <div class="card-body">
                                    <form id="paymentDetailsForm">
                                        <div class="mb-3">
                                            <label for="emailInput" class="form-label">Email *</label>
                                            <input type="email" class="form-control" id="emailInput" name="email" placeholder="Enter your email" required value="<?php echo htmlspecialchars($email); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="paymentMethodInput" class="form-label">Payment Method *</label>
                                            <input type="text" class="form-control" id="paymentMethodInput" name="payment_method" value="GCash" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="totalPaymentInput" class="form-label">Total Payment *</label>
                                            <input type="text" class="form-control" id="totalPaymentDisplay" value="₱<?php echo isset($_GET['total']) ? number_format((float)$_GET['total'], 2) : '0.00'; ?>" readonly>
                                            <input type="hidden" id="totalPaymentInput" name="total_payment" value="<?php echo isset($_GET['total']) ? htmlspecialchars($_GET['total']) : ''; ?>">
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-lg w-100" id="submitPaymentBtn">
                                            Pay
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Back to Home -->
                            <div class="text-center">
                                <a href="home.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Home
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalLabel">
                        <i class="fas fa-check-circle me-2"></i>Email Verified!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h6>Email Verified Successfully!</h6>
                    <p class="text-muted">Your email address has been verified.</p>
                </div>
                <div class="modal-footer">
                    <a href="home.php" class="btn btn-success">Go to Home</a>
                </div>
            </div>
        </div>
    </div>

    <style>
        .payment-logo i {
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 20px;
            background: #f8f9fa;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
        #verificationCodeInput {
            font-size: 1.5rem;
            letter-spacing: 0.5rem;
            font-weight: bold;
            text-align: center;
        }
        .btn-primary {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #218838;
            border-color: #1e7e34;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentDetailsForm = document.getElementById('paymentDetailsForm');
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            const submitPaymentBtn = document.getElementById('submitPaymentBtn');

            // Payment Details Form Submit
            paymentDetailsForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (paymentDetailsForm.checkValidity()) {
                    // Show loading state
                    submitPaymentBtn.disabled = true;
                    submitPaymentBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                    // Simulate API call delay
                    setTimeout(() => {
                        // Show success modal
                        successModal.show();
                        // Reset button
                        submitPaymentBtn.disabled = false;
                        submitPaymentBtn.innerHTML = 'Pay';
                    }, 2000);
                } else {
                    paymentDetailsForm.reportValidity();
                }
            });
        });
    </script>
</body>
</html> 