<?php
require_once __DIR__ . '/config.php';

// Handle AJAX requests
if (isset($_GET['action'])) {
    $con = db_connect();
    
    if ($con->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }
    
    // Check if user is logged in for AJAX requests
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        json_response(['error' => 'Unauthorized'], 401);
    }

    // Get next sequential quotation number
    if ($_GET['action'] === 'get_next_quotation_no') {
        $company_id = $_SESSION['company_id'];
        
        $stmt = $con->prepare("SELECT quotation_no FROM quotations WHERE company_id = ? ORDER BY quotation_id DESC LIMIT 1");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $next_no = 'QT-0001';
        if ($result->num_rows === 1) {
            $last_no = $result->fetch_assoc()['quotation_no'];
            if (preg_match('/^(.*?)([0-9]+)$/', $last_no, $matches)) {
                $prefix = $matches[1];
                $num_str = $matches[2];
                $num_val = intval($num_str) + 1;
                $next_no = $prefix . str_pad($num_val, strlen($num_str), '0', STR_PAD_LEFT);
            } else {
                $next_no = $last_no . '-1';
            }
        }
        
        json_response(['next_quotation_no' => $next_no]);
        $stmt->close();
        $con->close();
        exit();
    }
    
    // Get Products - Only for current user's company
    if ($_GET['action'] === 'get_products') {
        $company_id = $_SESSION['company_id'];
        
        $stmt = $con->prepare("SELECT instrument_id, instrument_name, description, price, image, hsn_code FROM instruments WHERE company_id = ? ORDER BY instrument_name ASC");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $products[] = [
                    'id' => $row['instrument_id'],
                    'name' => $row['instrument_name'],
                    'description' => $row['description'],
                    'price' => floatval($row['price']),
                    'image' => $row['image'],
                    'hsn' => $row['hsn_code']
                ];
            }
        }
        
        json_response($products);
        $stmt->close();
        $con->close();
        exit();
    }
    
    // Get Companies (Customers) - Only for current user's company
    if ($_GET['action'] === 'get_companies') {
        $company_id = $_SESSION['company_id'];
        
        $stmt = $con->prepare("SELECT customer_id, customer_company_name, customer_address, contact, email_id, customer_gstin FROM customer_companies WHERE company_id = ? ORDER BY customer_company_name ASC");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $companies = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $companies[] = [
                    'id' => $row['customer_id'],
                    'name' => $row['customer_company_name'],
                    'address' => $row['customer_address'],
                    'contact' => $row['contact'],
                    'email' => $row['email_id'],
                    'gst' => $row['customer_gstin']
                ];
            }
        }
        
        json_response($companies);
        $stmt->close();
        $con->close();
        exit();
    }

    // List Quotations - Only for current user's company
    if ($_GET['action'] === 'get_quotations') {
        $company_id = $_SESSION['company_id'];

        $stmt = $con->prepare("SELECT q.quotation_no, c.customer_company_name AS company_name, q.quotation_date, q.total_amount AS grand_total, q.created_at, TIMESTAMPDIFF(HOUR, q.created_at, NOW()) AS age_hours FROM quotations q JOIN customer_companies c ON q.customer_id = c.customer_id WHERE q.company_id = ? ORDER BY q.created_at DESC");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $quotations = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $quotations[] = [
                    'quotation_no' => $row['quotation_no'],
                    'company_name' => $row['company_name'],
                    'quotation_date' => $row['quotation_date'],
                    'grand_total' => floatval($row['grand_total']),
                    'created_at' => $row['created_at'],
                    'editable' => (intval($row['age_hours']) <= 24)
                ];
            }
        }

        json_response($quotations);
        $stmt->close();
        $con->close();
        exit();
    }

    // Get single quotation by number - Only for owner's company
    if ($_GET['action'] === 'get_quotation') {
        $qno = $_GET['qno'] ?? '';
        if ($qno === '') {
            json_response(['error' => 'Missing quotation number'], 400);
        }
        $company_id = $_SESSION['company_id'];

        $stmt = $con->prepare("SELECT q.quotation_id, q.template_format, q.quotation_no, q.customer_id AS company_id, c.customer_company_name AS company_name, c.customer_address AS company_address, c.contact AS company_contact, c.email_id AS company_email, c.customer_gstin AS gst_no, q.order_by_person, q.quotation_date, q.subtotal AS sub_total, q.gst_amount, q.total_amount AS grand_total, q.created_at, TIMESTAMPDIFF(HOUR, q.created_at, NOW()) AS age_hours FROM quotations q JOIN customer_companies c ON q.customer_id = c.customer_id WHERE q.quotation_no = ? AND q.company_id = ?");
        $stmt->bind_param("si", $qno, $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $row['editable'] = (intval($row['age_hours']) <= 24);
            unset($row['age_hours']);

            // Fetch quotation items from quotation_items table
            $qid = $row['quotation_id'];
            $stmt_items = $con->prepare("SELECT instrument_id AS id, item_name AS name, quantity AS qty, price, hsn_code AS hsn FROM quotation_items WHERE quotation_id = ?");
            $stmt_items->bind_param("i", $qid);
            $stmt_items->execute();
            $res_items = $stmt_items->get_result();
            $items = [];
            while ($item_row = $res_items->fetch_assoc()) {
                $items[] = [
                    'id' => $item_row['id'],
                    'name' => $item_row['name'],
                    'qty' => intval($item_row['qty']),
                    'price' => floatval($item_row['price']),
                    'hsn' => $item_row['hsn']
                ];
            }
            $stmt_items->close();

            $row['items_json'] = json_encode($items);
            json_response($row);
        } else {
            json_response(['error' => 'Quotation not found'], 404);
        }
        $stmt->close();
        $con->close();
        exit();
    }

    // Create quotation - idempotent on quotation_no per company
    if ($_GET['action'] === 'create_quotation') {
        $data = json_decode(file_get_contents('php://input'), true);
        $required = ['quotationNo','quotationDate','companyId','companyName','companyAddress','companyContact','companyEmail','gst_no','orderByPerson','items','subTotal','gstAmount','grandTotal'];
        foreach ($required as $f) {
            if (!isset($data[$f])) { json_response(['error' => 'Missing field: ' . $f], 400); }
        }
        $user_id = $_SESSION['user_id'];
        $company_id = $_SESSION['company_id'];

        // Check if quotation exists
        $stmt = $con->prepare("SELECT quotation_id, created_at, TIMESTAMPDIFF(HOUR, created_at, NOW()) AS age_hours FROM quotations WHERE quotation_no = ? AND company_id = ?");
        $stmt->bind_param("si", $data['quotationNo'], $company_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $exists = ($res && $res->num_rows === 1);
        $editable = false;
        $qid = null;
        if ($exists) {
            $row = $res->fetch_assoc();
            $qid = $row['quotation_id'];
            $editable = (intval($row['age_hours']) <= 24);
        }
        $stmt->close();

        if ($exists && $editable) {
            // Updating existing quotation
            $templateFormat = $data['templateFormat'] ?? 'format1';
            
            // Handle PDF Upload to Cloudinary
            $pdf_url = null;
            if (!empty($data['pdfBase64'])) {
                $base64_string = explode(',', $data['pdfBase64']);
                if (isset($base64_string[1])) {
                    $pdf_data = base64_decode($base64_string[1]);
                    $tmpFilePath = tempnam(sys_get_temp_dir(), 'pdf_') . '.pdf';
                    file_put_contents($tmpFilePath, $pdf_data);
                    
                    $cloud_name = "div48nrko";
                    $upload_preset = "quatation_managment";
                    $url = "https://api.cloudinary.com/v1_1/$cloud_name/auto/upload";
                    $postData = [
                        "file" => new CURLFile($tmpFilePath, 'application/pdf', 'quotation.pdf'),
                        "upload_preset" => $upload_preset,
                        "folder" => "user_" . $user_id . "/quotations"
                    ];
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $response = curl_exec($ch);
                    curl_close($ch);
                    
                    $result = json_decode($response, true);
                    if (isset($result['secure_url'])) {
                        $pdf_url = $result['secure_url'];
                    }
                    unlink($tmpFilePath);
                }
            }

            $stmt = $con->prepare("UPDATE quotations SET customer_id = ?, order_by_person = ?, quotation_date = ?, subtotal = ?, gst_amount = ?, total_amount = ?, template_format = ?, pdf_url = IFNULL(?, pdf_url), updated_at = NOW() WHERE quotation_id = ?");
            $stmt->bind_param(
                "issdddssi",
                $data['companyId'],
                $data['orderByPerson'],
                $data['quotationDate'],
                $data['subTotal'],
                $data['gstAmount'],
                $data['grandTotal'],
                $templateFormat,
                $pdf_url,
                $qid
            );
            if ($stmt->execute()) {
                // Delete old items
                $del_stmt = $con->prepare("DELETE FROM quotation_items WHERE quotation_id = ?");
                $del_stmt->bind_param("i", $qid);
                $del_stmt->execute();
                $del_stmt->close();

                // Insert new items
                $ins_stmt = $con->prepare("INSERT INTO quotation_items (quotation_id, instrument_id, item_name, quantity, price, gst_percent, amount, hsn_code) VALUES (?, ?, ?, ?, ?, 18.00, ?, ?)");
                foreach ($data['items'] as $item) {
                    $item_name = $item['name'];
                    $qty = intval($item['qty']);
                    $price = floatval($item['price']);
                    $amt = $qty * $price;
                    $item_inst_id = intval($item['product_id']);
                    $item_hsn = isset($item['hsn']) ? $item['hsn'] : null;
                    $ins_stmt->bind_param("iisddds", $qid, $item_inst_id, $item_name, $qty, $price, $amt, $item_hsn);
                    $ins_stmt->execute();
                }
                $ins_stmt->close();

                json_response(['success' => true, 'updated' => true]);
            } else {
                json_response(['error' => 'Failed to update quotation'], 500);
            }
            $stmt->close();
            $con->close();
            exit();
        } elseif ($exists && !$editable) {
            json_response(['error' => 'Quotation locked after 24 hours'], 403);
        } else {
            // Creating new quotation - check trial limits
            if (!can_create_quotation($user_id)) {
                $trial_counts = get_trial_counts($user_id);
                json_response([
                    'error' => 'Trial limit reached! You can create up to 2 quotations in the trial. Remaining: ' . $trial_counts['quotations'] . '. Please upgrade to Premium for unlimited access.',
                    'upgrade_required' => true,
                    'remaining' => $trial_counts['quotations']
                ], 403);
                $con->close();
                exit();
            }
            
            $templateFormat = $data['templateFormat'] ?? 'format1';
            
            // Handle PDF Upload to Cloudinary
            $pdf_url = null;
            if (!empty($data['pdfBase64'])) {
                $base64_string = explode(',', $data['pdfBase64']);
                if (isset($base64_string[1])) {
                    $pdf_data = base64_decode($base64_string[1]);
                    $tmpFilePath = tempnam(sys_get_temp_dir(), 'pdf_') . '.pdf';
                    file_put_contents($tmpFilePath, $pdf_data);
                    
                    $cloud_name = "div48nrko";
                    $upload_preset = "quatation_managment";
                    $url = "https://api.cloudinary.com/v1_1/$cloud_name/auto/upload";
                    $postData = [
                        "file" => new CURLFile($tmpFilePath, 'application/pdf', 'quotation.pdf'),
                        "upload_preset" => $upload_preset,
                        "folder" => "user_" . $user_id . "/quotations"
                    ];
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $response = curl_exec($ch);
                    curl_close($ch);
                    
                    $result = json_decode($response, true);
                    if (isset($result['secure_url'])) {
                        $pdf_url = $result['secure_url'];
                    }
                    unlink($tmpFilePath);
                }
            }

            $stmt = $con->prepare("INSERT INTO quotations (quotation_no, company_id, customer_id, quotation_date, subtotal, gst_amount, total_amount, order_by_person, template_format, pdf_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'sent')");
            $stmt->bind_param(
                "siisdddsss",
                $data['quotationNo'],
                $company_id,
                $data['companyId'],
                $data['quotationDate'],
                $data['subTotal'],
                $data['gstAmount'],
                $data['grandTotal'],
                $data['orderByPerson'],
                $templateFormat,
                $pdf_url
            );
            if ($stmt->execute()) {
                $new_qid = $con->insert_id;

                // Insert items
                $ins_stmt = $con->prepare("INSERT INTO quotation_items (quotation_id, instrument_id, item_name, quantity, price, gst_percent, amount, hsn_code) VALUES (?, ?, ?, ?, ?, 18.00, ?, ?)");
                foreach ($data['items'] as $item) {
                    $item_name = $item['name'];
                    $qty = intval($item['qty']);
                    $price = floatval($item['price']);
                    $amt = $qty * $price;
                    $item_inst_id = intval($item['product_id']);
                    $item_hsn = isset($item['hsn']) ? $item['hsn'] : null;
                    $ins_stmt->bind_param("iisddds", $new_qid, $item_inst_id, $item_name, $qty, $price, $amt, $item_hsn);
                    $ins_stmt->execute();
                }
                $ins_stmt->close();

                // Increment trial quotation count if on trial
                if (is_trial_user($user_id)) {
                    increment_trial_quotations($user_id);
                }
                json_response(['success' => true, 'created' => true]);
            } else {
                json_response(['error' => 'Failed to create quotation: ' . $stmt->error], 500);
            }
            $stmt->close();
            $con->close();
            exit();
        }
    }
    
    // Delete quotation
    if ($_GET['action'] === 'delete_quotation') {
        $qno = isset($_GET['qno']) ? trim($_GET['qno']) : '';
        if (!$qno) {
            json_response(['error' => 'Missing quotation number'], 400);
        }
        
        $company_id = $_SESSION['company_id'];
        
        // Find quotation_id
        $stmt = $con->prepare("SELECT quotation_id FROM quotations WHERE quotation_no = ? AND company_id = ?");
        $stmt->bind_param("si", $qno, $company_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res && $res->num_rows === 1) {
            $row = $res->fetch_assoc();
            $qid = $row['quotation_id'];
            
            // Delete items
            $stmt_items = $con->prepare("DELETE FROM quotation_items WHERE quotation_id = ?");
            $stmt_items->bind_param("i", $qid);
            $stmt_items->execute();
            $stmt_items->close();
            
            // Delete quotation
            $stmt_del = $con->prepare("DELETE FROM quotations WHERE quotation_id = ?");
            $stmt_del->bind_param("i", $qid);
            $stmt_del->execute();
            $stmt_del->close();
            
            json_response(['success' => true, 'message' => 'Quotation deleted successfully']);
        } else {
            json_response(['error' => 'Quotation not found or permission denied'], 404);
        }
    }
    
    // Update Product - Only if owned by current user's company
    if ($_GET['action'] === 'update_product') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id']) || !isset($data['name']) || !isset($data['description']) || !isset($data['price'])) {
            json_response(['error' => 'Missing required fields'], 400);
        }
        
        $company_id = $_SESSION['company_id'];
        $hsn = isset($data['hsn']) ? $data['hsn'] : null;
        
        $stmt = $con->prepare("UPDATE instruments SET instrument_name = ?, description = ?, price = ?, image = ?, hsn_code = ? WHERE instrument_id = ? AND company_id = ?");
        $image = isset($data['image']) ? $data['image'] : null;
        $stmt->bind_param("ssdsisi", $data['name'], $data['description'], $data['price'], $image, $hsn, $data['id'], $company_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                json_response(['success' => true, 'message' => 'Product updated successfully']);
            } else {
                json_response(['error' => 'Product not found or you do not have permission to update it'], 403);
            }
        } else {
            json_response(['error' => 'Failed to update product'], 500);
        }
        
        $stmt->close();
        $con->close();
        exit();
    }

    // Delete Product - Only if owned by current user's company
    if ($_GET['action'] === 'delete_product') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            json_response(['error' => 'Missing product ID'], 400);
        }
        
        $company_id = $_SESSION['company_id'];
        
        $stmt = $con->prepare("DELETE FROM instruments WHERE instrument_id = ? AND company_id = ?");
        $stmt->bind_param("ii", $data['id'], $company_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                json_response(['success' => true, 'message' => 'Product deleted successfully']);
            } else {
                json_response(['error' => 'Product not found or you do not have permission to delete it'], 403);
            }
        } else {
            json_response(['error' => 'Failed to delete product'], 500);
        }
        
        $stmt->close();
        $con->close();
        exit();
    }

    // Update Company - Only if owned by current user's company
    if ($_GET['action'] === 'update_company') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id']) || !isset($data['name']) || !isset($data['address']) || !isset($data['contact']) || !isset($data['email']) || !isset($data['gst'])) {
            json_response(['error' => 'Missing required fields'], 400);
        }
        
        $company_id = $_SESSION['company_id'];
        
        $stmt = $con->prepare("UPDATE customer_companies SET customer_company_name = ?, customer_address = ?, contact = ?, email_id = ?, customer_gstin = ? WHERE customer_id = ? AND company_id = ?");
        $stmt->bind_param("sssssii", $data['name'], $data['address'], $data['contact'], $data['email'], $data['gst'], $data['id'], $company_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                json_response(['success' => true, 'message' => 'Company updated successfully']);
            } else {
                json_response(['error' => 'Company not found or you do not have permission to update it'], 403);
            }
        } else {
            json_response(['error' => 'Failed to update company'], 500);
        }
        
        $stmt->close();
        $con->close();
        exit();
    }

    // Delete Company - Only if owned by current user's company
    if ($_GET['action'] === 'delete_company') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            json_response(['error' => 'Missing company ID'], 400);
        }
        
        $company_id = $_SESSION['company_id'];
        
        $stmt = $con->prepare("DELETE FROM customer_companies WHERE customer_id = ? AND company_id = ?");
        $stmt->bind_param("ii", $data['id'], $company_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                json_response(['success' => true, 'message' => 'Company deleted successfully']);
            } else {
                json_response(['error' => 'Company not found or you do not have permission to delete it'], 403);
            }
        } else {
            json_response(['error' => 'Failed to delete company'], 500);
        }
        
        $stmt->close();
        $con->close();
        exit();
    }
}

// Check if user is logged in for page access
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Fetch user's format preference
$con = db_connect();
$stmt = $con->prepare("SELECT format_preference, product_ui_preference FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
$format_pref = null;
$product_ui_pref = null;
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $format_pref = $row['format_preference'];
    $product_ui_pref = $row['product_ui_preference'];
}
$stmt->close();
$con->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
<link rel="icon" type="image/png" href="logo-new.png">
<link rel="manifest" href="manifest.json">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>QuaTation — Dashboard</title>
    <link rel="stylesheet" href="sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="home.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="theme.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="components.css?v=<?php echo time(); ?>">
    <script src="utils.js" defer></script>
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'dark';
            if (theme === 'light') {
                document.documentElement.classList.add('light-mode');
            }
        })();
    </script>
    <style>
        /* ── Dashboard Layout ── */

        /* Stats Row */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        @media (min-width: 600px) { .stats-row { grid-template-columns: repeat(4, 1fr); } }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--teal-border);
            border-radius: 11px;
            padding: 13px 14px;
            transition: all .18s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            border-color: rgba(45,212,191,.3);
            box-shadow: 0 6px 20px rgba(45,212,191,.1);
        }
        .stat-label { font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px; font-weight: 600; }
        .stat-value { font-family: var(--font-head); font-size: 22px; font-weight: 700; color: var(--teal); line-height: 1.1; }
        .stat-sub   { font-size: 10px; color: var(--text-muted); margin-top: 3px; }

        /* Tab Navigation (pill style) */
        .tab-container {
            display: flex;
            gap: 6px;
            margin-bottom: 16px;
            background: var(--surface);
            padding: 4px;
            border-radius: 10px;
            border: 1px solid var(--teal-border);
        }
        .tab-btn {
            flex: 1;
            padding: 8px 4px;
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 7px;
            transition: all .18s;
            font-family: var(--font-body);
            white-space: nowrap;
        }
        .tab-btn:hover { color: var(--teal); }
        .tab-btn.active {
            background: var(--teal);
            color: #000;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn .22s ease; }

        /* Section Header */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
            gap: 10px;
            flex-wrap: wrap;
        }
        .section-title {
            font-family: var(--font-head);
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
        }
        .section-title-count {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 400;
            margin-left: 6px;
        }

        /* Search Bar */
        .search-bar {
            position: relative;
            flex: 1;
            min-width: 180px;
            max-width: 320px;
        }
        .search-bar input {
            width: 100%;
            padding: 9px 36px 9px 36px;
            border: 1px solid var(--teal-border);
            border-radius: 8px;
            background: var(--bg);
            color: var(--text);
            font-size: 13px;
            font-family: var(--font-body);
            transition: all .18s;
        }
        .search-bar input:focus {
            outline: none;
            border-color: var(--teal);
            box-shadow: 0 0 0 3px rgba(45,212,191,.08);
        }
        .search-bar input::placeholder { color: var(--text-faint); }
        .search-bar::before {
            content: "";
            position: absolute;
            left: 11px; top: 50%;
            transform: translateY(-50%);
            width: 14px; height: 14px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%235e8080' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-size: contain;
            pointer-events: none;
        }
        .clear-search {
            position: absolute;
            right: 10px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 15px;
            display: none;
            line-height: 1;
            padding: 0;
        }
        .clear-search.visible { display: block; }
        .result-count { font-size: 11px; color: var(--text-muted); white-space: nowrap; }

        /* Add Button */
        .add-btn {
            background: var(--teal);
            color: #000;
            border: none;
            padding: 9px 15px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: all .18s;
            display: inline-flex; align-items: center; gap: 5px;
            font-family: var(--font-body);
        }
        .add-btn:hover { background: #1ec6b0; transform: translateY(-1px); }

        /* ── Quotations Grid ── */
        .quotations-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }
        @media (min-width: 560px) { .quotations-grid { grid-template-columns: 1fr 1fr; } }

        .qt-card {
            background: var(--surface);
            border: 1px solid var(--teal-border);
            border-radius: 12px;
            padding: 14px 15px;
            transition: all .18s;
            animation: slideInUp .35s ease forwards;
            opacity: 0;
        }
        .qt-card:hover {
            transform: translateY(-2px);
            border-color: rgba(45,212,191,.35);
            box-shadow: 0 6px 22px rgba(45,212,191,.12);
        }
        .qt-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }
        .qt-no {
            font-family: var(--font-head);
            font-size: 14px;
            font-weight: 700;
            color: var(--teal);
        }
        .qt-card-company {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .qt-card-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .qt-card-date { font-size: 11px; color: var(--text-muted); }
        .qt-card-amount {
            font-family: var(--font-head);
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
        }
        .qt-open-btn {
            width: 100%;
            padding: 8px;
            background: var(--teal-dim);
            color: var(--teal);
            border: 1px solid var(--teal-border);
            border-radius: 7px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            font-family: var(--font-body);
            transition: all .18s;
        }
        .qt-open-btn:hover { background: var(--teal); color: #000; }

        /* ── Product Cards ── */
        .product-container {
            display: flex;
            background: var(--surface);
            border: 1px solid var(--teal-border);
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 10px;
            transition: all .18s;
            animation: slideInUp .35s ease forwards;
            opacity: 0;
            position: relative;
            overflow: hidden;
        }
        .product-container:hover {
            transform: translateY(-2px);
            border-color: rgba(45,212,191,.35);
            box-shadow: 0 6px 22px rgba(45,212,191,.1);
        }
        .product-image {
            width: 52px; height: 52px;
            border-radius: 9px;
            overflow: hidden;
            flex-shrink: 0;
            border: 1px solid var(--teal-border);
            background: var(--surface3);
            margin-right: 13px;
        }
        .product-image img { width: 100%; height: 100%; object-fit: cover; }
        .product-image-placeholder {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
        }
        .product-details { flex: 1; min-width: 0; }
        .product-details h3 { font-size: 14px; font-weight: 600; color: var(--text); margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .product-details p { font-size: 11px; color: var(--text-muted); margin-bottom: 4px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .product-price { font-family: var(--font-head); font-size: 14px; font-weight: 700; color: var(--teal); }
        .product-actions {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-left: 10px;
            flex-shrink: 0;
        }
        .edit-btn, .delete-btn {
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
            transition: all .18s;
            display: flex; align-items: center; gap: 4px;
            font-family: var(--font-body);
            white-space: nowrap;
        }
        .edit-btn { background: var(--teal-dim); color: var(--teal); border: 1px solid var(--teal-border); }
        .edit-btn:hover { background: var(--teal); color: #000; }
        .delete-btn { background: rgba(240,69,96,.08); color: var(--danger); border: 1px solid rgba(240,69,96,.2); }
        .delete-btn:hover { background: var(--danger); color: #fff; }

        /* ── Company Cards ── */
        .company-card {
            background: var(--surface);
            border: 1px solid var(--teal-border);
            border-radius: 12px;
            padding: 15px 16px;
            margin-bottom: 10px;
            transition: all .18s;
            animation: slideInUp .35s ease forwards;
            opacity: 0;
        }
        .company-card:hover {
            transform: translateY(-2px);
            border-color: rgba(45,212,191,.35);
            box-shadow: 0 6px 22px rgba(45,212,191,.1);
        }
        .company-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            gap: 10px;
        }
        .company-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 7px;
            flex: 1;
            min-width: 0;
        }
        .company-actions { display: flex; gap: 6px; flex-shrink: 0; }
        .company-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            font-size: 12px;
            color: var(--text-muted);
        }
        .company-detail-item { display: flex; align-items: flex-start; gap: 5px; }
        .company-detail-item strong { color: var(--teal); min-width: 55px; font-size: 11px; }

        .alphabet-header {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 18px 0 8px 2px;
            padding-bottom: 5px;
            border-bottom: 1px solid var(--teal-border);
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .2px;
        }
        .badge-success { background: rgba(16,185,129,.12); color: #10b981; border: 1px solid rgba(16,185,129,.25); }
        .badge-warn    { background: rgba(245,158,11,.12); color: var(--warning); border: 1px solid rgba(245,158,11,.25); }
        .badge-info    { background: rgba(59,130,246,.12); color: #60a5fa; border: 1px solid rgba(59,130,246,.25); }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }
        .empty-state-icon { font-size: 40px; margin-bottom: 12px; }
        .empty-state h3 { font-size: 16px; font-weight: 700; color: var(--text); margin-bottom: 6px; }
        .empty-state p { font-size: 13px; margin-bottom: 16px; line-height: 1.5; }

        /* Skeleton loader */
        .skeleton {
            background: linear-gradient(90deg, var(--surface) 25%, var(--surface3) 50%, var(--surface) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.4s infinite;
            border-radius: 10px;
        }
        .skeleton-stat { height: 78px; }
        .skeleton-card { height: 80px; margin-bottom: 10px; }
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            inset: 0;
            background: rgba(0,0,0,.75);
            backdrop-filter: blur(6px);
            align-items: center;
            justify-content: center;
        }
        .modal[style*="block"] { display: flex !important; }
        .modal-content {
            background: var(--surface2);
            border: 1px solid var(--teal-border);
            border-radius: 16px;
            padding: 24px;
            width: 90%;
            max-width: 460px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideInUp .25s ease;
        }
        .modal-content h2 {
            font-family: var(--font-head);
            font-size: 17px;
            color: var(--teal);
            margin-bottom: 18px;
        }
        .close {
            float: right;
            font-size: 22px;
            color: var(--text-muted);
            cursor: pointer;
            line-height: 1;
            transition: color .15s;
        }
        .close:hover { color: var(--danger); }
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 18px;
        }
        .cancel-btn {
            background: transparent;
            color: var(--text-muted);
            border: 1px solid var(--teal-border);
            padding: 9px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            font-family: var(--font-body);
            transition: all .15s;
        }
        .cancel-btn:hover { color: var(--text); border-color: var(--text-muted); }
        .save-btn {
            background: var(--teal);
            color: #000;
            border: none;
            padding: 9px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            font-family: var(--font-body);
            transition: all .15s;
        }
        .save-btn:hover { background: #1ec6b0; }

        /* Quote by Company grouping */
        .company-quote-group { margin-bottom: 18px; }
        .company-quote-group-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 12px;
            background: var(--surface3);
            border: 1px solid var(--teal-border);
            border-radius: 10px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all .18s;
        }
        .company-quote-group-header:hover { background: var(--surface4); }
        .cqg-name { font-size: 13px; font-weight: 700; color: var(--text); }
        .cqg-meta { display: flex; align-items: center; gap: 8px; }
        .cqg-count { font-size: 11px; color: var(--text-muted); }
        .cqg-total { font-family: var(--font-head); font-size: 13px; font-weight: 700; color: var(--teal); }
        .cqg-chevron { color: var(--text-muted); transition: transform .2s; }
        .cqg-chevron.open { transform: rotate(90deg); }
        .cqg-list { display: none; padding-left: 10px; }
        .cqg-list.open { display: block; animation: fadeIn .2s ease; }
    </style>
</head>

<body>
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
      <?php if (empty($format_pref) || empty($product_ui_pref)): ?>
      
      <?php if (empty($format_pref)): ?>
      <!-- Format Selection Modal -->
      <div id="formatSelectionModal" class="modal" style="display: flex; z-index: 9999;">
        <div class="modal-content" style="max-width: 800px; width: 95%; background: var(--surface2); border: 1px solid var(--teal-border); text-align: center; border-radius: 16px;">
          <h2 style="color: var(--teal); font-size: 24px; margin-bottom: 10px;"><?php echo icon('sparkle', 22); ?> Choose Your Default Quotation Format</h2>
          <p style="color: var(--text-muted); margin-bottom: 30px;">You can change this later in Settings.</p>
          
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; text-align: left;">
            <!-- Format 1 -->
            <div class="format-card" onclick="selectFormat('old')" style="border: 2px solid var(--teal-border); border-radius: 12px; padding: 20px; cursor: pointer; transition: all 0.3s; background: var(--surface);">
              <h3 style="color: var(--text); margin-top: 0;">Old Format (Standard)</h3>
              <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">A simple, traditional layout perfect for standard products.</p>
              <img src="format1_preview.png" alt="Format 1 Preview" style="width: 100%; border-radius: 8px; margin-bottom: 15px; border: 1px solid rgba(45, 212, 191, 0.2);" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'100%\' height=\'150\'><rect width=\'100%\' height=\'150\' fill=\'%23111\'/><text x=\'50%\' y=\'50%\' fill=\'%23555\' dominant-baseline=\'middle\' text-anchor=\'middle\'>Preview Image (Upload format1_preview.png)</text></svg>'">
            </div>
            
            <!-- Format 2 -->
            <div class="format-card" onclick="selectFormat('new')" style="border: 2px solid var(--teal-border); border-radius: 12px; padding: 20px; cursor: pointer; transition: all 0.3s; background: var(--surface);">
              <h3 style="color: var(--text); margin-top: 0;">New Format (Visual)</h3>
              <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">Includes product images, terms, and a highlights page.</p>
              <img src="format2_preview.png" alt="Format 2 Preview" style="width: 100%; border-radius: 8px; margin-bottom: 15px; border: 1px solid rgba(45, 212, 191, 0.2);" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'100%\' height=\'150\'><rect width=\'100%\' height=\'150\' fill=\'%23111\'/><text x=\'50%\' y=\'50%\' fill=\'%23555\' dominant-baseline=\'middle\' text-anchor=\'middle\'>Preview Image (Upload format2_preview.png)</text></svg>'">
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if (empty($product_ui_pref)): ?>
      <!-- Product UI Selection Modal -->
      <div id="uiSelectionModal" class="modal" style="display: <?php echo empty($format_pref) ? 'none' : 'flex'; ?>; z-index: 9999;">
        <div class="modal-content" style="max-width: 800px; width: 95%; background: var(--surface2); border: 1px solid var(--teal-border); text-align: center; border-radius: 16px;">
          <h2 style="color: var(--teal); font-size: 24px; margin-bottom: 10px;"><?php echo icon('box', 22); ?> Choose Your Product Selection Style</h2>
          <p style="color: var(--text-muted); margin-bottom: 30px;">How do you prefer to add items to your quotations?</p>
          
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; text-align: left;">
            <div class="format-card" onclick="selectUI('list')" style="border: 2px solid var(--teal-border); border-radius: 12px; padding: 20px; cursor: pointer; transition: all 0.3s; background: var(--surface);">
              <h3 style="color: var(--text); margin-top: 0;">List Style (Table)</h3>
              <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">A clean table view. Click an empty row to search and select products.</p>
              <img src="ui_list_preview.png" alt="List Style Preview" style="width: 100%; border-radius: 8px; border: 1px solid rgba(45, 212, 191, 0.2);" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'100%\' height=\'150\'><rect width=\'100%\' height=\'150\' fill=\'%23111\'/><text x=\'50%\' y=\'50%\' fill=\'%23555\' dominant-baseline=\'middle\' text-anchor=\'middle\'>Preview Image (Upload ui_list_preview.png)</text></svg>'">
            </div>
            
            <div class="format-card" onclick="selectUI('card')" style="border: 2px solid var(--teal-border); border-radius: 12px; padding: 20px; cursor: pointer; transition: all 0.3s; background: var(--surface);">
              <h3 style="color: var(--text); margin-top: 0;">Card Style (Visual)</h3>
              <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">Shows all instruments as interactive cards. Type quantities to add them.</p>
              <img src="ui_card_preview.png" alt="Card Style Preview" style="width: 100%; border-radius: 8px; border: 1px solid rgba(45, 212, 191, 0.2);" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'100%\' height=\'150\'><rect width=\'100%\' height=\'150\' fill=\'%23111\'/><text x=\'50%\' y=\'50%\' fill=\'%23555\' dominant-baseline=\'middle\' text-anchor=\'middle\'>Preview Image (Upload ui_card_preview.png)</text></svg>'">
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <style>
        .format-card:hover {
          border-color: var(--teal) !important;
          transform: translateY(-5px);
          box-shadow: var(--shadow-teal);
        }
      </style>
      <script>
        async function selectFormat(format) {
          try {
            const res = await fetch('settings.php?action=set_format', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ format: format })
            });
            const data = await res.json();
            if (res.ok && data.success) {
              const formatModal = document.getElementById('formatSelectionModal');
              if (formatModal) formatModal.style.display = 'none';
              
              const uiModal = document.getElementById('uiSelectionModal');
              if (uiModal) {
                  uiModal.style.display = 'flex';
              } else {
                  QT.toastSuccess('Format selected successfully!');
              }
            }
          } catch (e) {
            console.error(e);
            QT.toastError('Failed to save format preference.');
          }
        }

        async function selectUI(uiPref) {
          try {
            const res = await fetch('settings.php?action=set_ui_pref', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ ui_pref: uiPref })
            });
            const data = await res.json();
            if (res.ok && data.success) {
              document.getElementById('uiSelectionModal').style.display = 'none';
              QT.toastSuccess('Interface preference saved!');
            }
          } catch (e) {
            console.error(e);
            QT.toastError('Failed to save interface preference.');
          }
        }
      </script>
      <?php endif; ?>


            <!-- KPI Stats Row -->
            <div class="stats-row" id="statsRow">
                <div class="skeleton skeleton-stat"></div>
                <div class="skeleton skeleton-stat"></div>
                <div class="skeleton skeleton-stat"></div>
                <div class="skeleton skeleton-stat"></div>
            </div>

            <!-- Tab Navigation (Moved to bottom nav) -->

            <!-- Tab: Recent Quotations -->
            <div id="recent-tab" class="tab-content active">
                <div class="section-header">
                    <span class="section-title">Recent Quotations<span class="section-title-count" id="quotationCount"></span></span>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <div class="search-bar">
                            <input type="text" id="quotationSearch" placeholder="Search quotations...">
                            <button class="clear-search" id="clearQuotationSearch" onclick="clearQuotationSearch()">✕</button>
                        </div>
                        <button class="add-btn" onclick="window.location.href='form2.php'">+ New</button>
                    </div>
                </div>
                <div id="quotationsContainer"></div>
            </div>

            <!-- Tab: Quotations By Company -->
            <div id="bycompany-tab" class="tab-content">
                <div class="section-header">
                    <span class="section-title">Quotations by Company</span>
                    <div class="search-bar">
                        <input type="text" id="byCompanySearch" placeholder="Filter by company...">
                        <button class="clear-search" id="clearByCompanySearch" onclick="clearByCompanySearch()">✕</button>
                    </div>
                </div>
                <div id="byCompanyContainer"></div>
            </div>

            <!-- Tab: Customer Companies -->
            <div id="companies-tab" class="tab-content">
                <div class="section-header">
                    <span class="section-title">Customers<span class="section-title-count" id="companyCount"></span></span>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <div class="search-bar">
                            <input type="text" id="companySearch" placeholder="Search companies...">
                            <button class="clear-search" id="clearCompanySearch" onclick="clearCompanySearch()">✕</button>
                        </div>
                        <button class="add-btn" onclick="window.location.href='add-company.php'">+ Add Customer</button>
                    </div>
                </div>
                <div id="companiesContainer"></div>
            </div>

            <!-- Tab: Products -->
            <div id="products-tab" class="tab-content">
                <div class="section-header">
                    <span class="section-title">Products<span class="section-title-count" id="productCount"></span></span>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <div class="search-bar">
                            <input type="text" id="productSearch" placeholder="Search products...">
                            <button class="clear-search" id="clearProductSearch" onclick="clearProductSearch()">✕</button>
                        </div>
                        <button class="add-btn" onclick="window.location.href='add-Product.php'">+ Add Product</button>
                    </div>
                </div>
                <div id="productsContainer"></div>
            </div>

        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Product</h2>
            <form id="editForm">
                <input type="hidden" id="editId">
                <div class="form-group">
                    <label for="editName">Product Name:</label>
                    <input type="text" id="editName" required>
                </div>
                <div class="form-group">
                    <label for="editDescription">Description:</label>
                    <textarea id="editDescription" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="editPrice">Price (₹):</label>
                    <input type="number" id="editPrice" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="editHsn">HSN Code:</label>
                    <input type="text" id="editHsn" placeholder="Enter HSN code">
                </div>
                <div class="form-group">
                    <label for="editImage">Image URL:</label>
                    <input type="text" id="editImage" placeholder="Enter image URL">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="save-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Company Modal -->
    <div id="editCompanyModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditCompanyModal()">&times;</span>
            <h2>Edit Company</h2>
            <form id="editCompanyForm">
                <input type="hidden" id="editCompanyId">
                <div class="form-group">
                    <label for="editCompanyName">Company Name:</label>
                    <input type="text" id="editCompanyName" required>
                </div>
                <div class="form-group">
                    <label for="editCompanyAddress">Address:</label>
                    <textarea id="editCompanyAddress" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="editCompanyContact">Contact:</label>
                    <input type="tel" id="editCompanyContact" required>
                </div>
                <div class="form-group">
                    <label for="editCompanyEmail">Email:</label>
                    <input type="email" id="editCompanyEmail" required>
                </div>
                <div class="form-group">
                    <label for="editCompanyGst">GST Number:</label>
                    <input type="text" id="editCompanyGst" required>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" onclick="closeEditCompanyModal()">Cancel</button>
                    <button type="submit" class="save-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Format Selection Intro Modal -->
    <div id="formatIntroModal" style="display: none; z-index: 10000; align-items: center; justify-content: center; position: fixed; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); backdrop-filter: blur(8px);">
        <div class="modal-content" style="position: relative; background: var(--surface); padding: 30px; border-radius: 16px; border: 1px solid var(--teal-border); max-width: 700px; text-align: center; animation: slideInUp 0.5s ease; box-shadow: 0 10px 40px rgba(45, 212, 191, 0.2);">
            <span class="close" onclick="document.getElementById('formatIntroModal').style.display='none'" style="position: absolute; right: 20px; top: 15px; font-size: 28px; cursor: pointer; color: var(--text-muted);">&times;</span>
            <h2 style="color: var(--teal); margin-bottom: 10px; font-size: 1.5rem;"><?php echo icon('sparkle', 20); ?> New Feature Introduced!</h2>
            <p style="color: var(--text-muted); margin-bottom: 25px;">We have introduced a brand new Event & Playout style quotation format. Select your preferred default format below. You can always change this later in Settings.</p>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; text-align: left;">
                <!-- Format 1 Option -->
                <div class="format-option" onclick="selectFormatPreference('format1')" style="border: 2px solid var(--teal-border); border-radius: 12px; padding: 15px; cursor: pointer; transition: 0.3s; background: var(--surface3);">
                    <h3 style="color: var(--text); margin-top: 0;">Format 1</h3>
                    <p style="font-size: 12px; color: var(--text-muted);">Standard Shreeji style table view suitable for most business needs.</p>
                    <div style="margin-top: 15px; border-radius: 6px; overflow: hidden; border: 1px solid #333;">
                        <img src="https://placehold.co/300x200/2a2a2a/fff?text=Format+1+Sample" alt="Format 1 Sample" style="width: 100%; display: block;">
                    </div>
                    <div style="margin-top: 15px; text-align: center;">
                        <button type="button" class="dock-button" style="padding: 8px 20px; font-size: 14px;">Select Format 1</button>
                    </div>
                </div>

                <!-- Format 2 Option -->
                <div class="format-option" onclick="selectFormatPreference('format2')" style="border: 2px solid var(--teal); border-radius: 12px; padding: 15px; cursor: pointer; transition: 0.3s; background: rgba(45, 212, 191, 0.1); box-shadow: 0 0 15px rgba(45,212,191,0.2);">
                    <h3 style="color: var(--teal); margin-top: 0;">Format 2 <?php echo icon('sparkle', 16); ?></h3>
                    <p style="font-size: 12px; color: var(--text-muted);">Event / Playout Service style with modern visual styling.</p>
                    <div style="margin-top: 15px; border-radius: 6px; overflow: hidden; border: 1px solid #333;">
                        <img src="https://placehold.co/300x200/2dd4bf/000?text=Format+2+Sample" alt="Format 2 Sample" style="width: 100%; display: block;">
                    </div>
                    <div style="margin-top: 15px; text-align: center;">
                        <button type="button" class="dock-button" style="padding: 8px 20px; font-size: 14px; background: var(--teal); color: #000;">Select Format 2</button>
                    </div>
                </div>

                <!-- Format 3 Option -->
                <div class="format-option" onclick="selectFormatPreference('format3')" style="border: 2px solid var(--teal); border-radius: 12px; padding: 15px; cursor: pointer; transition: 0.3s; background: rgba(45, 212, 191, 0.05);">
                    <h3 style="color: var(--teal); margin-top: 0;">Format 3</h3>
                    <p style="font-size: 12px; color: var(--text-muted);">Quote Style with Product Thumbnails in the description.</p>
                    <div style="margin-top: 15px; border-radius: 6px; overflow: hidden; border: 1px solid #333;">
                        <img src="https://placehold.co/300x200/2a2a2a/fff?text=Format+3+Sample" alt="Format 3 Sample" style="width: 100%; display: block;">
                    </div>
                    <div style="margin-top: 15px; text-align: center;">
                        <button type="button" class="dock-button" style="padding: 8px 20px; font-size: 14px;">Select Format 3</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let productsData = [];
        let companiesData = [];
        let filteredProducts = [];
        let filteredCompanies = [];
        let quotationsData = [];
        let filteredQuotations = [];
        const defaultIcons = ['⚖️', '🌡️', '📏', '🔧', '🔬', '⚙️', '🔩', '🛠️', '📊', '💡'];

        document.addEventListener('DOMContentLoaded', function() {
            QT.showSkeletons('productsContainer', 4);
            QT.showSkeletons('companiesContainer', 3);
            QT.showSkeletons('quotationsContainer', 4);

            Promise.all([loadProducts(), loadCompanies(), loadQuotations()])
                .then(function() { renderStats(); });

            document.getElementById('productSearch').addEventListener('input',
                QT.debounce(searchProducts, 280));
            document.getElementById('companySearch').addEventListener('input',
                QT.debounce(searchCompanies, 280));
            document.getElementById('quotationSearch').addEventListener('input',
                QT.debounce(searchQuotations, 280));
            document.getElementById('byCompanySearch').addEventListener('input',
                QT.debounce(searchByCompany, 280));

            // Handle hash-based tab navigation from bottom nav
            if (window.location.hash === '#quotations' || window.location.hash === '#recent') switchTab('recent');
            if (window.location.hash === '#companies') switchTab('companies');
            if (window.location.hash === '#products') switchTab('products');
            if (window.location.hash === '#bycompany') switchTab('bycompany');

            <?php if (empty($format_pref)): ?>
            const introModal = document.getElementById('formatIntroModal');
            if (introModal) {
                introModal.style.display = 'flex';
            }
            <?php endif; ?>
        });

        function selectFormatPreference(format) {
            fetch('settings.php?action=set_format', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ format: format })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    QT.toastSuccess('Format preference saved!');
                    document.getElementById('formatIntroModal').style.display = 'none';
                }
            })
            .catch(err => {
                console.error(err);
                document.getElementById('formatIntroModal').style.display = 'none';
            });
        }

        function renderStats() {
            var totalValue = quotationsData.reduce(function(sum, q) {
                return sum + (parseFloat(q.grand_total) || 0);
            }, 0);
            document.getElementById('statsRow').innerHTML =
                '<div class="stat-card"><div class="stat-label">Products</div><div class="stat-value">' + productsData.length + '</div><div class="stat-sub">in catalogue</div></div>' +
                '<div class="stat-card"><div class="stat-label">Customers</div><div class="stat-value">' + companiesData.length + '</div><div class="stat-sub">companies</div></div>' +
                '<div class="stat-card"><div class="stat-label">Quotations</div><div class="stat-value">' + quotationsData.length + '</div><div class="stat-sub">total created</div></div>' +
                '<div class="stat-card"><div class="stat-label">Total Value</div><div class="stat-value" style="font-size:18px">' + QT.formatINR(totalValue) + '</div><div class="stat-sub">across all quotes</div></div>';
        }

        function updateCount(elId, count) {
            var el = document.getElementById(elId);
            if (el) el.textContent = count + (count === 1 ? ' result' : ' results');
        }

        // Tab switching (4 tabs)
        const TAB_MAP = {
            'recent':     { btn: 'bnavRecent',    content: 'recent-tab' },
            'bycompany':  { btn: 'bnavByCompany', content: 'bycompany-tab' },
            'companies':  { btn: 'bnavCompanies', content: 'companies-tab' },
            'products':   { btn: 'bnavProducts',  content: 'products-tab' },
        };

        function switchTab(tab) {
            document.querySelectorAll('.bnav-item').forEach(t => {
                // don't remove active from center button if it had it, but actually the center button is only active on its own page
                if(t.id !== 'bnavMoreBtn') t.classList.remove('active');
            });
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            const m = TAB_MAP[tab] || TAB_MAP['recent'];
            const btn = document.getElementById(m.btn);
            const content = document.getElementById(m.content);
            
            if (btn) btn.classList.add('active');
            if (content) content.classList.add('active');
            
            // Populate by-company on first open
            if (tab === 'bycompany') renderByCompany(quotationsData);
        }

        // Expose tab helpers to bottom nav
        window.showQuotationsTab = function() { switchTab('recent'); };
        window.showCompaniesTab  = function() { switchTab('companies'); };

        // Load Quotations
        async function loadQuotations() {
            try {
                const response = await fetch('?action=get_quotations');
                if (!response.ok) throw new Error('Failed to fetch quotations');
                quotationsData = await response.json();
                filteredQuotations = [...quotationsData];
                renderQuotations(filteredQuotations);
            } catch (error) {
                console.error('Error loading quotations:', error);
                document.getElementById('quotationsContainer').innerHTML =
                    '<div class="empty-state"><div class="empty-state-icon"><?php echo icon('x-circle', 40); ?></div><h3>Failed to Load</h3><p>Could not load quotations. Please refresh.</p></div>';
            }
        }

        // Render Recent Quotations list
        function renderQuotations(items) {
            const container = document.getElementById('quotationsContainer');
            const countEl = document.getElementById('quotationCount');
            if (countEl) countEl.textContent = items && items.length ? ' • ' + items.length : '';
            if (!items || items.length === 0) {
                container.innerHTML =
                    '<div class="empty-state"><div class="empty-state-icon"><?php echo icon('clipboard', 40); ?></div><h3>No Quotations Yet</h3><p>Create your first quotation to start sending professional quotes.</p><a href="form2.php" class="btn-teal" style="text-decoration:none;display:inline-flex;">+ Create Quotation</a></div>';
                return;
            }
            container.innerHTML = '<div class="quotations-grid">' + items.map(createQuotationCard).join('') + '</div>';
        }

        // Render By-Company grouped quotations
        function renderByCompany(items, filterTerm) {
            const container = document.getElementById('byCompanyContainer');
            if (!items || items.length === 0) {
                container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><?php echo icon('building', 40); ?></div><h3>No Quotations</h3><p>Generate your first quotation to see it here.</p></div>';
                return;
            }
            // Group by company
            const groups = {};
            items.forEach(q => {
                const name = q.company_name || 'Unknown';
                if (!groups[name]) groups[name] = { quotes: [], total: 0 };
                groups[name].quotes.push(q);
                groups[name].total += parseFloat(q.grand_total) || 0;
            });
            // Filter
            let html = '';
            const sorted = Object.keys(groups).sort();
            sorted.forEach(name => {
                if (filterTerm && !name.toLowerCase().includes(filterTerm.toLowerCase())) return;
                const g = groups[name];
                const gId = 'cqg_' + encodeURIComponent(name).replace(/%/g,'');
                html += `
                    <div class="company-quote-group">
                        <div class="company-quote-group-header" onclick="toggleCQG('${gId}')">
                            <div class="cqg-name">🏢 ${escapeHtml(name)}</div>
                            <div class="cqg-meta">
                                <span class="cqg-count">${g.quotes.length} quote${g.quotes.length!==1?'s':''}</span>
                                <span class="cqg-total">${QT.formatINR(g.total)}</span>
                                <svg class="cqg-chevron" id="${gId}_chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><polyline points="9 18 15 12 9 6"/></svg>
                            </div>
                        </div>
                        <div class="cqg-list" id="${gId}">
                            <div class="quotations-grid">${g.quotes.map(createQuotationCard).join('')}</div>
                        </div>
                    </div>`;
            });
            container.innerHTML = html || '<div class="empty-state"><div class="empty-state-icon"><?php echo icon('search', 40); ?></div><h3>No Results</h3><p>No companies match your filter.</p></div>';
        }

        function toggleCQG(id) {
            const list = document.getElementById(id);
            const chev = document.getElementById(id + '_chev');
            if (list) list.classList.toggle('open');
            if (chev) chev.classList.toggle('open');
        }

        // By-company search
        function searchByCompany() {
            const term = document.getElementById('byCompanySearch').value;
            const clearBtn = document.getElementById('clearByCompanySearch');
            clearBtn.classList.toggle('visible', !!term);
            renderByCompany(quotationsData, term);
        }
        function clearByCompanySearch() {
            document.getElementById('byCompanySearch').value = '';
            document.getElementById('clearByCompanySearch').classList.remove('visible');
            renderByCompany(quotationsData);
        }

        function createQuotationCard(q) {
            const statusBadge = q.editable
                ? '<span class="badge badge-success">✏️ Editable</span>'
                : '<span class="badge badge-warn">🔒 Locked</span>';
            return `
                <div class="qt-card">
                    <div class="qt-card-top">
                        <span class="qt-no">${escapeHtml(q.quotation_no)}</span>
                        ${statusBadge}
                    </div>
                    <div class="qt-card-company">${escapeHtml(q.company_name)}</div>
                    <div class="qt-card-row">
                        <span class="qt-card-date">📅 ${escapeHtml(q.quotation_date)}</span>
                        <span class="qt-card-amount">${QT.formatINR(q.grand_total)}</span>
                    </div>
                    <div class="qt-card-actions" style="display: flex; gap: 8px; margin-top: 10px;">
                        <button class="btn-teal qt-open-btn" style="flex: 1;" onclick="openQuotation('${encodeURIComponent(q.quotation_no)}')">🔎 Open</button>
                        <button class="btn-danger qt-del-btn" style="padding: 8px 12px; border-radius: 6px; border: none; background: #ef4444; color: white; cursor: pointer;" onclick="deleteQuotation('${encodeURIComponent(q.quotation_no)}')">🗑️</button>
                    </div>
                </div>
            `;
        }

        async function deleteQuotation(qno) {
            const decoded = decodeURIComponent(qno);
            QT.confirm(`Are you sure you want to delete quotation ${decoded}?`, async () => {
                try {
                    const res = await fetch(`home.php?action=delete_quotation&qno=${encodeURIComponent(decoded)}`, {
                        method: 'POST'
                    });
                    const data = await res.json();
                    if (data.success) {
                        QT.toastSuccess('Quotation deleted successfully');
                        loadQuotations(); // refresh the list
                    } else {
                        QT.toastError(data.error || 'Failed to delete quotation');
                    }
                } catch (e) {
                    console.error(e);
                    QT.toastError('Failed to delete quotation');
                }
            });
        }

        function openQuotation(qno) {
            const decoded = decodeURIComponent(qno);
            window.location.href = 'form2.php?qno=' + encodeURIComponent(decoded);
        }

        // Search Quotations
        function searchQuotations() {
            const searchTerm = document.getElementById('quotationSearch').value.toLowerCase();
            const clearBtn = document.getElementById('clearQuotationSearch');
            if (searchTerm) {
                clearBtn.classList.add('visible');
                filteredQuotations = quotationsData.filter(q =>
                    q.quotation_no.toLowerCase().includes(searchTerm) ||
                    (q.company_name && q.company_name.toLowerCase().includes(searchTerm))
                );
            } else {
                clearBtn.classList.remove('visible');
                filteredQuotations = [...quotationsData];
            }
            renderQuotations(filteredQuotations);
        }

        function clearQuotationSearch() {
            document.getElementById('quotationSearch').value = '';
            document.getElementById('clearQuotationSearch').classList.remove('visible');
            filteredQuotations = [...quotationsData];
            renderQuotations(filteredQuotations);
        }

        // Load Products
        async function loadProducts() {
            try {
                const response = await fetch('?action=get_products');
                if (!response.ok) throw new Error('Failed to fetch products');
                productsData = await response.json();
                filteredProducts = [...productsData];
                renderProducts(filteredProducts);
            } catch (error) {
                console.error('Error loading products:', error);
                document.getElementById('productsContainer').innerHTML =
                    '<div class="empty-state"><div class="empty-state-icon"><?php echo icon('x-circle', 40); ?></div><h3>Failed to Load</h3><p>Could not load products. Please refresh the page.</p></div>';
            }
        }

        // Load Companies
        async function loadCompanies() {
            try {
                const response = await fetch('?action=get_companies');
                if (!response.ok) throw new Error('Failed to fetch companies');
                companiesData = await response.json();
                filteredCompanies = [...companiesData];
                renderCompanies(filteredCompanies);
            } catch (error) {
                console.error('Error loading companies:', error);
                document.getElementById('companiesContainer').innerHTML =
                    '<div class="empty-state"><div class="empty-state-icon"><?php echo icon('x-circle', 40); ?></div><h3>Failed to Load</h3><p>Could not load companies. Please refresh the page.</p></div>';
            }
        }

        // Render Products
        function renderProducts(products) {
            const container = document.getElementById('productsContainer');
            updateCount('productCount', products.length);
            if (products.length === 0) {
                container.innerHTML =
                    '<div class="empty-state"><div class="empty-state-icon"><?php echo icon('box', 40); ?></div><h3>No Products Found</h3><p>Add your first product to the catalogue to start creating quotations.</p><a href="add-Product.php" class="btn-teal" style="text-decoration:none;display:inline-block;">+ Add Product</a></div>';
                return;
            }
            container.innerHTML = products.map((product, index) => createProductCard(product, index)).join('');
        }

        // Create Product Card
        function createProductCard(product, index) {
            const icon = defaultIcons[index % defaultIcons.length];
            const imageContent = product.image 
                ? `<img src="${escapeHtml(product.image)}" alt="${escapeHtml(product.name)}" onerror="this.parentElement.innerHTML='<div class=\\'product-image-placeholder\\'>${icon}</div>'" />`
                : `<div class="product-image-placeholder">${icon}</div>`;

            return `
                <div class="product-container" style="animation-delay: ${index * 0.1}s">
                    <div class="product-image">
                        ${imageContent}
                    </div>
                    <div class="product-details">
                        <h3>${escapeHtml(product.name)}</h3>
                        <p>${escapeHtml(product.description)}</p>
                        ${product.hsn ? `<p style="font-size: 13px; color: #14b8a6; margin-bottom: 8px;"><strong>HSN:</strong> ${escapeHtml(product.hsn)}</p>` : ''}
                        <div class="product-price">₹${parseFloat(product.price).toFixed(2)}</div>
                    </div>
                    <div class="product-actions">
                        <button class="edit-btn" onclick="editProduct(${product.id})" title="Edit Product">
                            ✏️ Edit
                        </button>
                         <button class="delete-btn" onclick="deleteProduct(${product.id}, '${escapeHtml(product.name)}')" title="Delete Product">
                            🗑️ Delete
                        </button>
                    </div>
                </div>
            `;
        }

        // Render Companies with Alphabetical Grouping
        function renderCompanies(companies) {
            const container = document.getElementById('companiesContainer');
            updateCount('companyCount', companies.length);
            if (companies.length === 0) {
                container.innerHTML =
                    '<div class="empty-state"><div class="empty-state-icon"><?php echo icon('building', 40); ?></div><h3>No Companies Yet</h3><p>Add customer companies to start sending quotations to them.</p><a href="add-company.php" class="btn-teal" style="text-decoration:none;display:inline-block;">+ Add Company</a></div>';
                return;
            }

            // Group companies by first letter
            const grouped = {};
            companies.forEach(company => {
                const firstLetter = company.name.charAt(0).toUpperCase();
                if (!grouped[firstLetter]) {
                    grouped[firstLetter] = [];
                }
                grouped[firstLetter].push(company);
            });

            // Sort alphabetically
            const sortedLetters = Object.keys(grouped).sort();
            
            let html = '';
            sortedLetters.forEach(letter => {
                html += `<div class="alphabet-header">${letter}</div>`;
                grouped[letter].forEach((company, index) => {
                    html += createCompanyCard(company, index);
                });
            });

            container.innerHTML = html;
        }

        // Create Company Card
        function createCompanyCard(company, index) {
            return `
                <div class="company-card" style="animation-delay: ${index * 0.05}s">
                    <div class="company-header">
                        <div class="company-name">
                            <span>🏢</span>
                            ${escapeHtml(company.name)}
                        </div>
                        <div class="company-actions">
                            <button class="edit-btn" onclick="editCompany(${company.id})" title="Edit Company">
                                ✏️ Edit
                            </button>
                            <button class="delete-btn" onclick="deleteCompany(${company.id}, '${escapeHtml(company.name)}')" title="Delete Company">
                                🗑️ Delete
                            </button>
                        </div>
                    </div>
                    <div class="company-details">
                        <div class="company-detail-item">
                            <strong>📍 Address:</strong>
                            <span>${escapeHtml(company.address)}</span>
                        </div>
                        <div class="company-detail-item">
                            <strong>📞 Contact:</strong>
                            <span>${escapeHtml(company.contact)}</span>
                        </div>
                        <div class="company-detail-item">
                            <strong>📧 Email:</strong>
                            <span>${escapeHtml(company.email)}</span>
                        </div>
                        <div class="company-detail-item">
                            <strong>🏛️ GST:</strong>
                            <span>${escapeHtml(company.gst)}</span>
                        </div>
                    </div>
                </div>
            `;
        }

        // Search Products
        function searchProducts() {
            const searchTerm = document.getElementById('productSearch').value.toLowerCase();
            const clearBtn = document.getElementById('clearProductSearch');
            
            if (searchTerm) {
                clearBtn.classList.add('visible');
                filteredProducts = productsData.filter(product => 
                    product.name.toLowerCase().includes(searchTerm) ||
                    product.description.toLowerCase().includes(searchTerm)
                );
            } else {
                clearBtn.classList.remove('visible');
                filteredProducts = [...productsData];
            }
            
            renderProducts(filteredProducts);
        }

        // Search Companies
        function searchCompanies() {
            const searchTerm = document.getElementById('companySearch').value.toLowerCase();
            const clearBtn = document.getElementById('clearCompanySearch');
            
            if (searchTerm) {
                clearBtn.classList.add('visible');
                filteredCompanies = companiesData.filter(company => 
                    company.name.toLowerCase().includes(searchTerm) ||
                    company.address.toLowerCase().includes(searchTerm) ||
                    company.email.toLowerCase().includes(searchTerm) ||
                    company.gst.toLowerCase().includes(searchTerm)
                );
            } else {
                clearBtn.classList.remove('visible');
                filteredCompanies = [...companiesData];
            }
            
            renderCompanies(filteredCompanies);
        }

        // Clear Product Search
        function clearProductSearch() {
            document.getElementById('productSearch').value = '';
            document.getElementById('clearProductSearch').classList.remove('visible');
            filteredProducts = [...productsData];
            renderProducts(filteredProducts);
        }

        // Clear Company Search
        function clearCompanySearch() {
            document.getElementById('companySearch').value = '';
            document.getElementById('clearCompanySearch').classList.remove('visible');
            filteredCompanies = [...companiesData];
            renderCompanies(filteredCompanies);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Edit Product
        function editProduct(id) {
            const product = productsData.find(p => p.id === id);
            if (!product) {
                QT.toastError('Product not found!');
                return;
            }

            document.getElementById('editId').value = product.id;
            document.getElementById('editName').value = product.name;
            document.getElementById('editDescription').value = product.description;
            document.getElementById('editPrice').value = product.price;
            document.getElementById('editHsn').value = product.hsn || '';
            document.getElementById('editImage').value = product.image || '';

            document.getElementById('editModal').style.display = 'block';
        }

        // Delete Product
        function deleteProduct(id, productName) {
            QT.confirm(`Delete "${productName}"? This action cannot be undone.`, async function() {
                try {
                    const response = await fetch('?action=delete_product', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id })
                    });
                    const result = await response.json();
                    if (response.ok && result.success) {
                        productsData = productsData.filter(p => p.id !== id);
                        filteredProducts = filteredProducts.filter(p => p.id !== id);
                        renderProducts(filteredProducts);
                        QT.toastSuccess(`"${productName}" deleted successfully.`);
                    } else {
                        QT.toastError('Failed to delete product: ' + (result.error || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error deleting product:', error);
                    QT.toastError('Failed to delete product. Please try again.');
                }
            });
        }

        // Close Product Edit Modal
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('editForm').reset();
        }

        // Product Edit Form Submit
        document.getElementById('editForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = {
                id: parseInt(document.getElementById('editId').value),
                name: document.getElementById('editName').value,
                description: document.getElementById('editDescription').value,
                price: parseFloat(document.getElementById('editPrice').value),
                hsn: document.getElementById('editHsn').value.trim(),
                image: document.getElementById('editImage').value || null
            };

            try {
                const response = await fetch('?action=update_product', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    const index = productsData.findIndex(p => p.id === formData.id);
                    if (index !== -1) {
                        productsData[index] = { ...productsData[index], ...formData };
                    }

                    const filteredIndex = filteredProducts.findIndex(p => p.id === formData.id);
                    if (filteredIndex !== -1) {
                        filteredProducts[filteredIndex] = { ...filteredProducts[filteredIndex], ...formData };
                    }

                    renderProducts(filteredProducts);
                    closeEditModal();
                    QT.toastSuccess('Product updated successfully!');
                } else {
                    QT.toastError('Failed to update product: ' + (result.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error updating product:', error);
                QT.toastError('Failed to update product. Please try again.');
            }
        });

        // Edit Company
        function editCompany(id) {
            const company = companiesData.find(c => c.id === id);
            if (!company) {
                QT.toastError('Company not found!');
                return;
            }

            document.getElementById('editCompanyId').value = company.id;
            document.getElementById('editCompanyName').value = company.name;
            document.getElementById('editCompanyAddress').value = company.address;
            document.getElementById('editCompanyContact').value = company.contact;
            document.getElementById('editCompanyEmail').value = company.email;
            document.getElementById('editCompanyGst').value = company.gst;

            document.getElementById('editCompanyModal').style.display = 'block';
        }

        // Delete Company
        function deleteCompany(id, companyName) {
            QT.confirm(`Delete "${companyName}"? This action cannot be undone.`, async function() {
                try {
                    const response = await fetch('?action=delete_company', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id })
                    });
                    const result = await response.json();
                    if (response.ok && result.success) {
                        companiesData = companiesData.filter(c => c.id !== id);
                        filteredCompanies = filteredCompanies.filter(c => c.id !== id);
                        renderCompanies(filteredCompanies);
                        QT.toastSuccess(`"${companyName}" deleted successfully.`);
                    } else {
                        QT.toastError('Failed to delete company: ' + (result.error || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error deleting company:', error);
                    QT.toastError('Failed to delete company. Please try again.');
                }
            });
        }

        // Close Company Edit Modal
        function closeEditCompanyModal() {
            document.getElementById('editCompanyModal').style.display = 'none';
            document.getElementById('editCompanyForm').reset();
        }

        // Company Edit Form Submit
        document.getElementById('editCompanyForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = {
                id: parseInt(document.getElementById('editCompanyId').value),
                name: document.getElementById('editCompanyName').value,
                address: document.getElementById('editCompanyAddress').value,
                contact: document.getElementById('editCompanyContact').value,
                email: document.getElementById('editCompanyEmail').value,
                gst: document.getElementById('editCompanyGst').value
            };

            try {
                const response = await fetch('?action=update_company', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    const index = companiesData.findIndex(c => c.id === formData.id);
                    if (index !== -1) {
                        companiesData[index] = { ...companiesData[index], ...formData };
                    }

                    const filteredIndex = filteredCompanies.findIndex(c => c.id === formData.id);
                    if (filteredIndex !== -1) {
                        filteredCompanies[filteredIndex] = { ...filteredCompanies[filteredIndex], ...formData };
                    }

                    renderCompanies(filteredCompanies);
                    closeEditCompanyModal();
                    QT.toastSuccess('Company updated successfully!');
                } else {
                    QT.toastError('Failed to update company: ' + (result.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error updating company:', error);
                QT.toastError('Failed to update company. Please try again.');
            }
        });

        // Close modals when clicking outside
        window.onclick = function(event) {
            const productModal = document.getElementById('editModal');
            const companyModal = document.getElementById('editCompanyModal');
            
            if (event.target === productModal) {
                closeEditModal();
            }
            if (event.target === companyModal) {
                closeEditCompanyModal();
            }
        }

        console.log('User logged in:', {
            username: <?php echo json_encode($_SESSION['username'] ?? ''); ?>,
            email: <?php echo json_encode($_SESSION['email'] ?? ''); ?>,
            user_id: <?php echo json_encode($_SESSION['user_id'] ?? ''); ?>
        });
    </script>
</body>
</html>