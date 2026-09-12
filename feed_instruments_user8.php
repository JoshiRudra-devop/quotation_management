<?php
/**
 * Database Feeder Script for User ID = 8
 * Uses AJAX batch processing to prevent PHP timeouts or memory limits.
 */
if (php_sapi_name() === 'cli' && empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

require_once __DIR__ . '/config.php';

$target_user_id = 8;

// AJAX Batch Request Handler
if (isset($_GET['action']) && $_GET['action'] === 'process_batch') {
    header('Content-Type: application/json; charset=utf-8');
    
    $con = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    if ($con->connect_error) {
        $socket = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';
        $con = @new mysqli("localhost", "root", "", "quotation_managment", 3306, $socket);
    }
    if ($con->connect_error) {
        $con = @new mysqli("127.0.0.1", "root", "", "quotation_managment");
    }
    
    if ($con->connect_error) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $con->connect_error]);
        exit();
    }
    
    // Resolve company_id for user_id = 8
    $company_id = null;
    $stmt_comp = $con->prepare("SELECT company_id FROM companies WHERE user_id = ?");
    if ($stmt_comp) {
        $stmt_comp->bind_param("i", $target_user_id);
        $stmt_comp->execute();
        $res_comp = $stmt_comp->get_result();
        if ($row_comp = $res_comp->fetch_assoc()) {
            $company_id = $row_comp['company_id'];
        }
        $stmt_comp->close();
    }
    if (!$company_id) {
        $company_id = $target_user_id;
    }
    
    // Schema upgrades
    $con->query("ALTER TABLE instruments ADD COLUMN IF NOT EXISTS hsn_code VARCHAR(20) DEFAULT NULL");
    $con->query("ALTER TABLE instruments ADD COLUMN IF NOT EXISTS image_url VARCHAR(500) DEFAULT NULL");
    
    // Get batch items from POST JSON payload
    $raw_input = file_get_input_data();
    $data = json_decode($raw_input, true);
    
    if (!isset($data['items']) || !is_array($data['items'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid batch data received']);
        exit();
    }
    
    $batch_items = $data['items'];
    $inserted = 0;
    $updated = 0;
    $errors = 0;
    
    $stmt_check = $con->prepare("SELECT instrument_id FROM instruments WHERE company_id = ? AND instrument_name = ?");
    $stmt_insert = $con->prepare("INSERT INTO instruments (company_id, instrument_name, price, description, image, hsn_code) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_update = $con->prepare("UPDATE instruments SET price = ?, description = ?, image = ?, hsn_code = ? WHERE instrument_id = ? AND company_id = ?");
    
    foreach ($batch_items as $item) {
        $name = $item['name'];
        $price = (float)$item['price'];
        $desc = $item['description'];
        $img = $item['image'];
        $hsn = $item['hsn_code'];
        
        $stmt_check->bind_param("is", $company_id, $name);
        $stmt_check->execute();
        $res = $stmt_check->get_result();
        
        if ($res && $row = $res->fetch_assoc()) {
            $inst_id = $row['instrument_id'];
            $stmt_update->bind_param("dsssii", $price, $desc, $img, $hsn, $inst_id, $company_id);
            if ($stmt_update->execute()) {
                $updated++;
            } else {
                $errors++;
            }
        } else {
            $stmt_insert->bind_param("isdsss", $company_id, $name, $price, $desc, $img, $hsn);
            if ($stmt_insert->execute()) {
                $inserted++;
            } else {
                $errors++;
            }
        }
    }
    
    $con->close();
    echo json_encode([
        'success' => true,
        'company_id' => $company_id,
        'inserted' => $inserted,
        'updated' => $updated,
        'errors' => $errors,
        'count' => count($batch_items)
    ]);
    exit();
}

function file_get_input_data() {
    return file_get_contents('php://input');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Feeder - User ID 8</title>
    <style>
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 20px auto; background: #1e293b; padding: 30px; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.5); }
        h1 { color: #2dd4bf; margin-top: 0; }
        .progress-box { background: #0f172a; border-radius: 10px; padding: 20px; margin: 20px 0; border: 1px solid #334155; }
        .bar-outer { background: #334155; height: 24px; border-radius: 12px; overflow: hidden; margin: 15px 0; position: relative; }
        .bar-inner { background: linear-gradient(90deg, #10b981, #2dd4bf); height: 100%; width: 0%; transition: width 0.3s ease; border-radius: 12px; }
        .bar-text { position: absolute; width: 100%; text-align: center; top: 3px; font-size: 13px; font-weight: bold; color: #ffffff; text-shadow: 0 1px 3px rgba(0,0,0,0.8); }
        .status-log { max-height: 250px; overflow-y: auto; background: #0b0f19; padding: 12px 15px; border-radius: 8px; font-family: monospace; font-size: 12.5px; line-height: 1.6; border: 1px solid #1e293b; }
        .log-entry { margin-bottom: 4px; }
        .log-success { color: #34d399; }
        .log-info { color: #38bdf8; }
        .log-error { color: #f87171; }
        .summary-card { background: #064e3b; border: 2px solid #10b981; padding: 25px; border-radius: 12px; margin-top: 25px; display: none; }
        .btn-dash { display: inline-block; background: #2dd4bf; color: #0f172a; font-weight: bold; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-size: 15px; transition: all 0.2s; margin-top: 15px; }
        .btn-dash:hover { background: #14b8a6; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(45,212,191,0.4); }
    </style>
</head>
<body>

<div class="container">
    <h1>🚀 High-Speed Database Feeder (User ID 8)</h1>
    <p style="color: #94a3b8; font-size: 15px;">Loading <strong>324 products</strong> into the <code>instruments</code> table using AJAX chunked processing...</p>
    
    <div class="progress-box">
        <div style="display: flex; justify-content: space-between; font-weight: bold;">
            <span id="batchStatus">Initializing Data Feed...</span>
            <span id="percentText">0%</span>
        </div>
        <div class="bar-outer">
            <div class="bar-inner" id="progressBar"></div>
            <div class="bar-text" id="barLabel">0 / 324 Products Processed</div>
        </div>
    </div>

    <h3>📋 Real-Time Execution Log</h3>
    <div class="status-log" id="logBox">
        <div class="log-entry log-info">[INFO] Fetching product catalog payload...</div>
    </div>

    <div class="summary-card" id="summaryCard">
        <h2 style="margin-top:0; color:#34d399;">🎉 Products Loaded Successfully!</h2>
        <p style="font-size:15px; margin: 6px 0;"><strong>Target User ID:</strong> 8 | <strong>Company ID:</strong> <span id="compResult">8</span></p>
        <p style="font-size:15px; margin: 6px 0;"><strong>New Instruments Inserted:</strong> <span id="totalInserted" style="color:#34d399; font-weight:bold;">0</span></p>
        <p style="font-size:15px; margin: 6px 0;"><strong>Existing Instruments Updated:</strong> <span id="totalUpdated" style="color:#38bdf8; font-weight:bold;">0</span></p>
        <p style="font-size:15px; margin: 6px 0;"><strong>Total Products Processed:</strong> <span id="totalProcessed" style="color:#2dd4bf; font-weight:bold;">324</span></p>
        <div>
            <a href="home.php#products" class="btn-dash">Go to Dashboard to View Products →</a>
        </div>
    </div>
</div>

<script>
async function startFeeding() {
    const logBox = document.getElementById('logBox');
    const progressBar = document.getElementById('progressBar');
    const percentText = document.getElementById('percentText');
    const barLabel = document.getElementById('barLabel');
    const batchStatus = document.getElementById('batchStatus');
    
    function log(msg, type='info') {
        const div = document.createElement('div');
        div.className = 'log-entry log-' + type;
        div.textContent = `[${new Date().toLocaleTimeString()}] ${msg}`;
        logBox.appendChild(div);
        logBox.scrollTop = logBox.scrollHeight;
    }
    
    try {
        log('Loading catalog JSON file...', 'info');
        const res = await fetch('feed_catalog_user8.json');
        if (!res.ok) throw new Error('Failed to load feed_catalog_user8.json');
        
        const allItems = await res.json();
        const totalItems = allItems.length;
        log(`Loaded ${totalItems} products into memory. Starting batch ingestion...`, 'success');
        
        const batchSize = 15;
        let insertedAcc = 0;
        let updatedAcc = 0;
        let errorsAcc = 0;
        let processedAcc = 0;
        let resolvedCompanyId = 8;
        
        for (let i = 0; i < totalItems; i += batchSize) {
            const chunk = allItems.slice(i, i + batchSize);
            const batchNum = Math.floor(i / batchSize) + 1;
            const totalBatches = Math.ceil(totalItems / batchSize);
            
            batchStatus.textContent = `Processing Batch ${batchNum} of ${totalBatches}...`;
            
            const response = await fetch('feed_instruments_user8.php?action=process_batch', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ items: chunk })
            });
            
            if (!response.ok) {
                const errText = await response.text();
                log(`Batch ${batchNum} failed HTTP ${response.status}: ${errText.substring(0, 100)}`, 'error');
                errorsAcc += chunk.length;
                continue;
            }
            
            const result = await response.json();
            if (!result.success) {
                log(`Batch ${batchNum} DB Error: ${result.error}`, 'error');
                errorsAcc += chunk.length;
                continue;
            }
            
            insertedAcc += result.inserted;
            updatedAcc += result.updated;
            errorsAcc += result.errors;
            processedAcc += result.count;
            if (result.company_id) resolvedCompanyId = result.company_id;
            
            const pct = Math.round((processedAcc / totalItems) * 100);
            progressBar.style.width = pct + '%';
            percentText.textContent = pct + '%';
            barLabel.textContent = `${processedAcc} / ${totalItems} Products Processed`;
            
            log(`Batch ${batchNum}/${totalBatches} done: +${result.inserted} inserted, +${result.updated} updated`, 'success');
        }
        
        batchStatus.textContent = '🎉 Ingestion Completed Successfully!';
        document.getElementById('compResult').textContent = resolvedCompanyId;
        document.getElementById('totalInserted').textContent = insertedAcc;
        document.getElementById('totalUpdated').textContent = updatedAcc;
        document.getElementById('totalProcessed').textContent = processedAcc;
        document.getElementById('summaryCard').style.display = 'block';
        
        setTimeout(() => {
            alert(`🎉 SUCCESS!

All ${processedAcc} Products have been loaded into database for User ID 8!

- Inserted: ${insertedAcc}
- Updated: ${updatedAcc}`);
        }, 400);
        
    } catch (e) {
        log('Error executing data feed: ' + e.message, 'error');
        batchStatus.textContent = '❌ Execution Error';
    }
}

window.addEventListener('DOMContentLoaded', startFeeding);
</script>
</body>
</html>
