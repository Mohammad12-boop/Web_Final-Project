<?php
// This file handles the multi-step checkout process for buying services

require_once "Service.php";
// Start session and connect to database
session_start();
require_once "db.php.inc";

// Check if the user is a client, otherwise redirect to login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Client') {
    header("Location: login.php?error=Please login as client to checkout");
    exit();
}
if (empty($_SESSION['cart'])) {
    header("Location: browse-services.php?error=Your cart is empty.");
    exit();
}

// Make sure the cart items are of the Service class
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if (!($item instanceof Service)) {
            $_SESSION['cart'] = [];
            $_SESSION['error_msg'] = "Cart session reset. Please add services again.";
            header("Location: browse-services.php");
            exit();
        }
    }
}

// Set the default step to 1 if not already set
if (!isset($_SESSION['checkout_step'])) {
    $_SESSION['checkout_step'] = 1;
}
if (!isset($_SESSION['checkout_data'])) {
    $_SESSION['checkout_data'] = [
        'items' => [],
        'payment' => [],
        'terms' => false
    ];
}

$current_step = $_SESSION['checkout_step'];
$errors = [];

// Handle all form submissions for the checkout steps
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // Step 1: Handling service requirements and file uploads
    if ($action == 'step1') {
        $valid = true;
        foreach ($_SESSION['cart'] as $index => $item) {
            $req_key = "req_" . $index;
            $ins_key = "ins_" . $index;
            $dead_key = "dead_" . $index;
            $req_val = isset($_POST[$req_key]) ? trim($_POST[$req_key]) : '';
            $ins_val = isset($_POST[$ins_key]) ? trim($_POST[$ins_key]) : '';
            $dead_val = isset($_POST[$dead_key]) ? $_POST[$dead_key] : '';

            if (strlen($req_val) < 50 || strlen($req_val) > 1000) {
                $errors[$req_key] = "Requirements must be 50-1000 characters.";
                $valid = false;
            }
            if (!empty($ins_val) && strlen($ins_val) > 500) {
                $errors[$ins_key] = "Special instructions max 500 characters.";
                $valid = false;
            }
            if (!empty($dead_val)) {
                $delivery_days = $item->getDeliveryTime();
                $min_date = date('Y-m-d', strtotime("+$delivery_days days"));
                if ($dead_val < $min_date) {
                    $errors[$dead_key] = "Deadline must be at least $delivery_days days from today.";
                    $valid = false;
                }
            }

            $_SESSION['checkout_data']['items'][$index] = [
                'requirements' => $req_val,
                'instructions' => $ins_val,
                'deadline' => $dead_val,
            ];

            for ($f = 1; $f <= 3; $f++) {
                $f_input = "file_" . $index . "_" . $f;
                if (isset($_FILES[$f_input]) && $_FILES[$f_input]['error'] == 0) {
                    if ($_FILES[$f_input]['size'] > 10 * 1024 * 1024) {
                        $errors[$f_input] = "File too large (Max 10MB).";
                        $valid = false;
                    } else {
                        $ext = pathinfo($_FILES[$f_input]['name'], PATHINFO_EXTENSION);
                        if (!in_array(strtolower($ext), ['pdf', 'doc', 'docx', 'txt', 'zip', 'jpg', 'png'])) {
                            $errors[$f_input] = "Invalid file type.";
                            $valid = false;
                        } else {
                            $tmp_dir = "uploads/temp/";
                            if (!is_dir($tmp_dir))
                                mkdir($tmp_dir, 0777, true);
                            $new_path = $tmp_dir . uniqid() . "_req." . $ext;
                            if (move_uploaded_file($_FILES[$f_input]['tmp_name'], $new_path)) {
                                $_SESSION['checkout_data']['items'][$index]['files'][] = [
                                    'path' => $new_path,
                                    'name' => $_FILES[$f_input]['name'],
                                    'size' => $_FILES[$f_input]['size'],
                                    'type' => $_FILES[$f_input]['type']
                                ];
                            }
                        }
                    }
                }
            }
        }

        if ($valid) {
            $_SESSION['checkout_step'] = 2;
            header("Location: checkout.php");
            exit();
        }
    }
    // Step 2: Form validation for payment details
    elseif ($action == 'step2') {
        $method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
        $card_name = isset($_POST['card_name']) ? trim($_POST['card_name']) : '';
        $card_num = isset($_POST['card_number']) ? trim($_POST['card_number']) : '';
        $card_exp = isset($_POST['card_expiry']) ? trim($_POST['card_expiry']) : '';
        $card_cvv = isset($_POST['card_cvv']) ? trim($_POST['card_cvv']) : '';

        $addr1 = isset($_POST['addr_line1']) ? trim($_POST['addr_line1']) : '';
        $city = isset($_POST['city']) ? trim($_POST['city']) : '';
        $state = isset($_POST['state']) ? trim($_POST['state']) : '';
        $zip = isset($_POST['zip']) ? trim($_POST['zip']) : '';
        $country = isset($_POST['country']) ? trim($_POST['country']) : '';

        $valid = true;
        if ($method == 'credit_card') {
            if (!preg_match('/^[0-9]{16}$/', $card_num)) {
                $errors['card_number'] = "Card Number must be exactly 16 digits.";
                $valid = false;
            }
            if (strlen($card_name) < 2 || !preg_match('/^[a-zA-Z ]+$/', $card_name)) {
                $errors['card_name'] = "Cardholder Name must be alphabetic/spaces (2-100 chars).";
                $valid = false;
            }
            if (!preg_match('/^[0-9]{3}$/', $card_cvv)) {
                $errors['card_cvv'] = "CVV must be 3 digits.";
                $valid = false;
            }
            // Expiry Future check (MM/YY)
            if (!preg_match('/^(0[1-9]|1[0-2])\/?([0-9]{2})$/', $card_exp, $matches)) {
                $errors['card_expiry'] = "Invalid Expiry Format (MM/YY).";
                $valid = false;
            } else {
                $exp_month = $matches[1];
                $exp_year = "20" . $matches[2]; // Assume 20xx
                // Check if future (Last day of month)
                $exp_timestamp = strtotime("$exp_year-$exp_month-01 +1 month -1 day");
                if ($exp_timestamp < time()) {
                    $errors['card_expiry'] = "Card has expired.";
                    $valid = false;
                }
            }
        }

        if (empty($addr1) || empty($city) || empty($state) || empty($zip) || empty($country)) {
            $errors['address'] = "All address fields are required.";
            $valid = false;
        }

        $_SESSION['checkout_data']['payment'] = [
            'method' => $method,
            'masked_card' => $method == 'credit_card' ? substr($card_num, -4) : 'N/A',
            'billing' => "$addr1, $city, $state $zip, $country"
        ];

        if ($valid) {
            $_SESSION['checkout_step'] = 3;
            header("Location: checkout.php");
            exit();
        }
    } elseif ($action == 'place_order') {
        $terms = isset($_POST['terms']) ? $_POST['terms'] : '';
        if (!$terms) {
            $errors['terms'] = "You must agree to the Terms of Service.";
        } else {
            try {
                // Start transaction to save all orders at once
                $pdo->beginTransaction();
                $txn_id = "TXN" . time() . rand(1000, 9999);
                $order_ids = [];
                $upload_root_dir = "uploads/orders/";

                foreach ($_SESSION['cart'] as $index => $item) {
                    $sData = $_SESSION['checkout_data']['items'][$index];

                    $order_id = mt_rand(1000000000, 9999999999);

                    $price = $item->getPrice();
                    $fee = $item->calculateServiceFee();
                    $total = $item->getTotalWithFee();
                    $revs = $item->getRevisionsIncluded();

                    $dl = !empty($sData['expected_delivery']) ? $sData['expected_delivery'] : date('Y-m-d', strtotime("+" . $item->getDeliveryTime() . " days"));
                    // $dl = !empty($sData['deadline']) ? $sData['deadline'] : date('Y-m-d', strtotime("+" . $item->getDeliveryTime() . " days"));

                    $expected = date('Y-m-d', strtotime("+" . $item->getDeliveryTime() . " days"));

                    // $sql = "INSERT INTO orders (order_id, transaction_id, client_id, freelancer_id, service_id, status, requirements, special_instructions, expected_delivery, price, service_fee, total_amount, revisions_included) 
                    //         VALUES (:oid, :txn, :cid, :fid, :sid, 'Pending', :req, :ins, :dl, :price, :fee, :total, :revs)";

                    $sql = "INSERT INTO orders (order_id, transaction_id, client_id, freelancer_id, service_id, service_title, delivery_time, status, requirements, special_instructions, expected_delivery, price, service_fee, total_amount, revisions_included) 
                            VALUES (:oid, :txn, :cid, :fid, :sid, :title, :delivery, 'Pending', :req, :ins, :dl, :price, :fee, :total, :revs)";

                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':oid', $order_id);
                    $stmt->bindValue(':txn', $txn_id);
                    $stmt->bindValue(':cid', $_SESSION['user_id']);
                    $stmt->bindValue(':fid', $item->getFreelancerId());
                    $stmt->bindValue(':sid', $item->getServiceId());
                    $stmt->bindValue(':title', $item->getTitle());
                    $stmt->bindValue(':delivery', $item->getDeliveryTime());
                    $stmt->bindValue(':req', $sData['requirements']);
                    $stmt->bindValue(':ins', $sData['instructions']);
                    $stmt->bindValue(':dl', $dl);
                    $stmt->bindValue(':price', $price);
                    $stmt->bindValue(':fee', $fee);
                    $stmt->bindValue(':total', $total);
                    $stmt->bindValue(':revs', $revs);
                    $stmt->execute();

                    $order_ids[] = $order_id;

                    $order_file_dir = $upload_root_dir . $order_id . "/requirements/";
                    if (!is_dir($order_file_dir))
                        mkdir($order_file_dir, 0777, true);

                    if (!empty($sData['files'])) {
                        foreach ($sData['files'] as $f) {
                            $tmp_path = $f['path'];
                            if (file_exists($tmp_path)) {
                                $new_name = $f['name'];
                                $final_path = $order_file_dir . $new_name;
                                if (rename($tmp_path, $final_path)) {
                                    $fsql = "INSERT INTO file_attachments (order_id, file_path, original_filename, file_size, file_type) VALUES (:oid, :path, :name, :size, 'requirement')";
                                    $fstmt = $pdo->prepare($fsql);
                                    $fstmt->bindValue(':oid', $order_id);
                                    $fstmt->bindValue(':path', $final_path);
                                    $fstmt->bindValue(':name', $f['name']);
                                    $fstmt->bindValue(':size', $f['size']);
                                    $fstmt->execute();
                                }
                            }
                        }
                    }
                }

                $pdo->commit();

                $_SESSION['last_orders'] = $order_ids;
                unset($_SESSION['cart']);
                unset($_SESSION['checkout_data']);
                unset($_SESSION['checkout_step']);

                header("Location: order-success.php");
                exit();

            } catch (Exception $e) {
                $pdo->rollBack();
                $errors['general'] = "Order Failed: " . $e->getMessage();
            }
        }
    }

    if (isset($_POST['back_step'])) {
        $_SESSION['checkout_step'] = (int) $_POST['back_step'];
        header("Location: checkout.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Checkout - Step <?php echo $current_step; ?></title>
    <link rel="stylesheet" href="styles.css" />
</head>

<body class="main-body">
    <?php include("header.php"); ?>

    <div class="page-layout">

        <?php include("nav.php"); ?>

        <main class="main-content checkout-main-content">
            <br>
            <!-- Show breadcrumbs for navigation -->
            <div class="breadcrumb">
                <a href="browse-services.php">Home</a> > <a href="cart.php">Cart</a> > <span>Checkout</span>
            </div>

            <!-- Visual progress bar for the 3 steps -->
            <div class="checkout-progress">
                <div class="progress-step <?php echo $current_step > 1 ? 'completed' : ($current_step == 1 ? 'active' : ''); ?>"
                    data-step="1">Step 1</div>
                <div class="progress-step <?php echo $current_step > 2 ? 'completed' : ($current_step == 2 ? 'active' : ''); ?>"
                    data-step="2">Step 2</div>
                <div class="progress-step <?php echo $current_step > 3 ? 'completed' : ($current_step == 3 ? 'active' : ''); ?>"
                    data-step="3">Step 3</div>
            </div>

            <?php if (isset($errors['general'])): ?>
                <div class="error-message"><?php echo htmlspecialchars($errors['general']); ?></div>
            <?php endif; ?>

            <div class="cart-layout">

                <div class="cart-main">

                    <?php if ($current_step == 1): ?>
                        <h2 class="container-h2">Step 1: Service Requirements</h2>
                        <form action="checkout.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="step1">

                            <?php foreach ($_SESSION['cart'] as $index => $item): ?>
                                <div class="checkout-card">
                                    <div class="checkout-service-title"><?php echo htmlspecialchars($item->getTitle()); ?></div>
                                    <div class="checkout-service-meta">
                                        Freelancer: <?php echo htmlspecialchars($item->getFreelancerName()); ?> |
                                        Delivery: <?php echo $item->getFormattedDelivery(); ?>
                                    </div>
                                    <hr class="checkout-hr">

                                    <div class="form-group">
                                        <label class="form-label">Requirements (Describe what you need) <span
                                                class="required">*</span></label>
                                        <textarea name="req_<?php echo $index; ?>" class="form-textarea" required minlength="50"
                                            maxlength="1000"><?php echo isset($_SESSION['checkout_data']['items'][$index]['requirements']) ? htmlspecialchars($_SESSION['checkout_data']['items'][$index]['requirements']) : ''; ?></textarea>
                                        <?php if (isset($errors["req_$index"]))
                                            echo '<span class="error-message">' . $errors["req_$index"] . '</span>'; ?>
                                        <div class="form-note">50-100 characters</div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Special Instructions (Optional)</label>
                                        <textarea name="ins_<?php echo $index; ?>"
                                            class="form-textarea textarea-short"><?php echo isset($_SESSION['checkout_data']['items'][$index]['instructions']) ? htmlspecialchars($_SESSION['checkout_data']['items'][$index]['instructions']) : ''; ?></textarea>
                                        <?php if (isset($errors["ins_$index"]))
                                            echo '<span class="error-message">' . $errors["ins_$index"] . '</span>'; ?>
                                    </div>

                                    <?php
                                    $min_date = date('Y-m-d', strtotime("+" . $item->getDeliveryTime() . " days"));
                                    ?>
                                    <div class="form-group">
                                        <label class="form-label">Preferred Deadline (Optional)</label>
                                        <input type="date" name="dead_<?php echo $index; ?>" class="form-input"
                                            min="<?php echo $min_date; ?>"
                                            value="<?php echo isset($_SESSION['checkout_data']['items'][$index]['expected_delivery']) ? htmlspecialchars($_SESSION['checkout_data']['items'][$index]['expected_delivery']) : ''; ?>">
                                        <?php if (isset($errors["dead_$index"]))
                                            echo '<span class="error-message">' . $errors["dead_$index"] . '</span>'; ?>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Requirement Files (Optional, Max 3, 10MB each)</label>
                                        <div class="file-inputs">
                                            <input type="file" name="file_<?php echo $index; ?>_1" class="form-input mb-10">
                                            <input type="file" name="file_<?php echo $index; ?>_2" class="form-input mb-10">
                                            <input type="file" name="file_<?php echo $index; ?>_3" class="form-input mb-10">
                                        </div>
                                        <?php if (isset($errors["file_" . $index . "_1"]))
                                            echo '<span class="error-message">' . $errors["file_" . $index . "_1"] . '</span>'; ?>

                                        <?php if (isset($_SESSION['checkout_data']['items'][$index]['files'])): ?>
                                            <ul class="file-upload-list">
                                                <?php foreach ($_SESSION['checkout_data']['items'][$index]['files'] as $f): ?>
                                                    <li>Uploaded: <?php echo htmlspecialchars($f['name']); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <button type="submit" class="btn-submit w-100">Continue to Payment</button>
                            <a href="cart.php" class="btn-submit secondary-btn w-100 btn-mt-10 btn-block-center">Edit
                                Cart</a>
                        </form>

                    <?php elseif ($current_step == 2): ?>
                        <h2 class="container-h2">Step 2: Payment Information</h2>
                        <form action="checkout.php" method="POST">
                            <input type="hidden" name="action" value="step2">

                            <div class="checkout-card">
                                <h3 class="form-section-title">Payment Method</h3>
                                <div class="form-group">
                                    <select name="payment_method" class="form-select">
                                        <option value="credit_card">Credit Card</option>
                                        <option value="paypal">PayPal</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                    </select>
                                </div>
                                <hr>
                                <h3 class="form-section-title">Credit Card Details</h3>
                                <div class="form-group">
                                    <label class="form-label">Cardholder Name <span class="required">*</span></label>
                                    <input type="text" name="card_name" class="form-input" required placeholder="John Doe">
                                    <?php if (isset($errors["card_name"]))
                                        echo '<span class="error-message">' . $errors["card_name"] . '</span>'; ?>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Card Number (16 digits) <span
                                            class="required">*</span></label>
                                    <input type="text" name="card_number" class="form-input" maxlength="16" required
                                        placeholder="1234123412341234">
                                    <?php if (isset($errors["card_number"]))
                                        echo '<span class="error-message">' . $errors["card_number"] . '</span>'; ?>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Expiry (MM/YY) <span class="required">*</span></label>
                                        <input type="text" name="card_expiry" class="form-input" placeholder="12/26"
                                            required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">CVV (3 digits) <span class="required">*</span></label>
                                        <input type="text" name="card_cvv" class="form-input" maxlength="3" required
                                            placeholder="123">
                                        <?php if (isset($errors["card_cvv"]))
                                            echo '<span class="error-message">' . $errors["card_cvv"] . '</span>'; ?>
                                    </div>
                                </div>

                                <h3 class="form-section-title mt-30">Billing Address</h3>
                                <div class="form-group">
                                    <label class="form-label">Address Line 1 <span class="required">*</span></label>
                                    <input type="text" name="addr_line1" class="form-input" required>
                                </div>
                                <div class="form-row">
                                    <div class="form-group"><input type="text" name="city" placeholder="City"
                                            class="form-input" required></div>
                                    <div class="form-group"><input type="text" name="state" placeholder="State"
                                            class="form-input" required></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group"><input type="text" name="zip" placeholder="Postal Code"
                                            class="form-input" required></div>
                                    <div class="form-group"><input type="text" name="country" placeholder="Country"
                                            class="form-input" required></div>
                                </div>
                                <?php if (isset($errors["address"]))
                                    echo '<span class="error-message">' . $errors["address"] . '</span>'; ?>
                            </div>

                            <button type="submit" class="btn-submit w-100">Continue to Review</button>
                            <button type="submit" name="back_step" value="1"
                                class="btn-submit secondary-btn w-100 btn-mt-10" formnovalidate>Back to
                                Requirements</button>
                        </form>

                    <?php elseif ($current_step == 3): ?>
                        <h2 class="container-h2">Step 3: Review & Confirm</h2>
                        <form action="checkout.php" method="POST">
                            <input type="hidden" name="action" value="place_order">

                            <?php foreach ($_SESSION['cart'] as $index => $item):
                                $sData = $_SESSION['checkout_data']['items'][$index];
                                ?>
                                <div class="checkout-card">
                                    <div class="checkout-service-title"><?php echo htmlspecialchars($item->getTitle()); ?></div>
                                    <p><strong>Freelancer:</strong> <?php echo htmlspecialchars($item->getFreelancerName()); ?>
                                    </p>
                                    <hr>
                                    <p><strong>Requirements:</strong><br><?php echo nl2br(htmlspecialchars($sData['requirements'])); ?>
                                    </p>
                                    <?php if (!empty($sData['instructions'])): ?>
                                        <p><strong>Instructions:</strong> <?php echo htmlspecialchars($sData['instructions']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <!-- <?php //if (!empty($sData['expected_delivery'])): ?> -->
                                    <?php if (!empty($sData['deadline'])): ?>
                                        <p><strong>Preferred Deadline:</strong>
                                            <!-- <?php //echo htmlspecialchars($sData['expected_delivery']); ?> -->
                                            <?php echo htmlspecialchars($sData['deadline']); ?></p>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (!empty($sData['files'])): ?>
                                        <p><strong>Files:</strong></p>
                                        <ul>
                                            <?php foreach ($sData['files'] as $f): ?>
                                                <li><?php echo htmlspecialchars($f['name']); ?> (<?php echo round($f['size'] / 1024); ?>
                                                    KB)</li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <div class="checkout-card">
                                <h3>Payment Information</h3>
                                <p><strong>Method:</strong>
                                    <?php echo ucfirst(str_replace('_', ' ', $_SESSION['checkout_data']['payment']['method'])); ?>
                                </p>
                                <p><strong>Card:</strong> **** **** ****
                                    <?php echo $_SESSION['checkout_data']['payment']['masked_card']; ?>
                                </p>
                                <p><strong>Billing:</strong>
                                    <?php echo htmlspecialchars($_SESSION['checkout_data']['payment']['billing']); ?></p>
                            </div>

                            <div class="form-group mt-20 terms-box">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="terms" value="1" required> I agree to the <a href="#">Terms
                                        of Service</a> and <a href="#">Privacy Policy</a>.
                                </label>
                                <?php if (isset($errors["terms"]))
                                    echo '<div class="error-message">' . $errors["terms"] . '</div>'; ?>
                            </div>

                            <button type="submit" class="btn-submit w-100">Place Order</button>
                            <button type="submit" name="back_step" value="2"
                                class="btn-submit secondary-btn w-100 btn-mt-10">Edit Payment</button>
                        </form>
                    <?php endif; ?>

                </div>

                <div class="cart-sidebar sticky-sidebar">
                    <div class="summary-title">Order Summary</div>
                    <?php
                    $subtotal = 0;
                    foreach ($_SESSION['cart'] as $item) {
                        $subtotal += $item->getPrice();
                    }
                    $fee = $subtotal * 0.05;
                    $total = $subtotal + $fee;
                    ?>
                    <p class="order-count-text">You will place <strong><?php echo count($_SESSION['cart']); ?></strong>
                        order(s).</p>

                    <?php foreach ($_SESSION['cart'] as $item): ?>
                        <div class="sidebar-item-card">
                            <div class="sidebar-item-title"><?php echo htmlspecialchars($item->getTitle()); ?></div>
                            <div class="sidebar-item-meta"><?php echo htmlspecialchars($item->getFreelancerName()); ?></div>
                            <div class="sidebar-item-price"><?php echo $item->getFormattedPrice(); ?></div>
                        </div>
                    <?php endforeach; ?>

                    <hr class="sidebar-hr">

                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Service Fee (5%):</span>
                        <span>$<?php echo number_format($fee, 2); ?></span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <?php include("footer.php"); ?>
</body>

</html>