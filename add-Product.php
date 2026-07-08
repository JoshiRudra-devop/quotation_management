
<?php
require_once __DIR__ . '/config.php';

// Check if user is logged in
require_auth();

$user_id = $_SESSION['user_id'];

// Check if user can add products
if (!can_add_product($user_id)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        flash_set('Trial limit reached! Please upgrade to Premium for unlimited access.', 'error');
        header('Location: premium.php');
        exit();
    }
    $trial_counts = get_trial_counts($user_id);
    echo "<script>
        alert('Trial limit reached! You can add up to 2 instruments in the trial.\\n\\nRemaining: {$trial_counts['products']} instruments\\nPlease upgrade to Premium for unlimited access.');
        window.location.href='premium.php';
    </script>";
    exit();
}

// ========================
// PHP: Handle form submission — success/failure both redirect, with a
// flash toast shown on the next page (see flash_set()/flash_render() in config.php)
// ========================
// Cloudinary credentials
$cloud_name = "div48nrko";
$upload_preset = "quatation_managment"; // your unsigned preset

function add_product_fail($message) {
    flash_set($message, 'error');
    header('Location: add-Product.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        add_product_fail('Invalid request. Please refresh and try again.');
    }

    // Guards against a double-click or resubmission inserting the same product twice.
    // Consumed immediately so a second, near-simultaneous request (blocked behind this
    // one by PHP's session lock) sees it already used and bails out instead of re-inserting.
    if (!consume_form_token('add_product', $_POST['form_token'] ?? '')) {
        flash_set('This product was already added.', 'info');
        header('Location: home.php');
        exit();
    }

    $productName = trim($_POST['productName'] ?? '');
    $productPrice = $_POST['productPrice'] ?? 0;
    $productDescription = trim($_POST['productDescription'] ?? '');
    $productHsn = trim($_POST['productHsn'] ?? '');

    if ($productName === '' || (float)$productPrice <= 0 || $productDescription === '') {
        add_product_fail('Please fill in all required fields.');
    }

    if (!isset($_FILES['productImage']) || $_FILES['productImage']['error'] !== 0) {
        add_product_fail('Product image is required.');
    }

    $fileSize = $_FILES['productImage']['size'] ?? 0;
    $fileType = $_FILES['productImage']['type'] ?? '';
    if ($fileSize <= 0 || $fileSize > 5 * 1024 * 1024) {
        add_product_fail('Image must be less than 5MB.');
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($fileType, $allowed_types)) {
        add_product_fail('Only JPG, PNG, GIF, and WEBP images are allowed.');
    }

    $fileTmpPath = $_FILES['productImage']['tmp_name'];
    if (!file_exists($fileTmpPath)) {
        add_product_fail('Uploaded file could not be read.');
    }

    $url = "https://api.cloudinary.com/v1_1/$cloud_name/image/upload";
    $postData = [
        "file" => new CURLFile($fileTmpPath),
        "upload_preset" => $upload_preset,
        "folder" => "user_" . $user_id . "/products"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    if (!isset($result['secure_url'])) {
        add_product_fail('Cloudinary upload failed. Please try again.');
    }
    $productImageUrl = $result['secure_url'];

    $con = db_connect();
    $stmt = $con->prepare("INSERT INTO instruments (instrument_name, price, description, image, company_id, hsn_code) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdssis", $productName, $productPrice, $productDescription, $productImageUrl, $_SESSION['company_id'], $productHsn);

    if (!$stmt->execute()) {
        $stmt->close();
        add_product_fail('Database insert failed. Please try again.');
    }
    $stmt->close();

    if (is_trial_user($user_id)) {
        increment_trial_products($user_id);
    }

    flash_set('Product added successfully!', 'success');
    header('Location: home.php');
    exit();
}

$formToken = new_form_token('add_product');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="Logo.png">
<link rel="manifest" href="manifest.json">
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Add Product - Quotation Management System</title>
<link rel="stylesheet" href="sidebar.css?v=2.3">
<link rel="stylesheet" href="home.css?v=1.4">
<link rel="stylesheet" href="theme.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="components.css">
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
* { margin:0; padding:0; box-sizing:border-box; }

/* Container — uses the app's shared card variables so this respects light/dark mode like every other page */
.setup-container {
  background: var(--card-gradient);
  border: 1px solid var(--teal-border);
  border-radius: var(--radius);
  width:100%;
  max-width:600px;
  padding:25px;
  margin:25px auto;
  color: var(--text, #ccc);
  box-shadow: var(--shadow-card, 0 4px 20px rgba(45,212,191,0.12));
}

/* Header */
.header { text-align:center; margin-bottom:25px; }
.header h1 {
  font-family: var(--font-head);
  color: var(--teal);
  font-size:22px;
  font-weight:700;
  margin-bottom:8px;
}
.header p { color: var(--text-muted, #aaa); font-size:14px; }

/* Form */
.form-group { margin-bottom:15px; }
.form-group label { display:block; margin-bottom:6px; color: var(--teal); font-weight:600; font-size:14px; }
.form-group input, .form-group textarea {
  width:100%;
  padding:10px;
  border:1px solid var(--teal-border);
  border-radius:8px;
  font-size:14px;
  background: var(--bg-surface, #1a1a1a);
  color: var(--text, #e5f7f4);
}
.form-group input:focus, .form-group textarea:focus {
  outline:none;
  border-color: var(--teal);
  box-shadow:0 0 8px var(--teal-glow);
}

/* Navigation Buttons */
.navigation { display:flex; justify-content:space-between; gap:15px; margin-top:20px; }
.btn {
  padding:10px 20px;
  border:none;
  border-radius:25px;
  font-size:14px;
  font-weight:600;
  cursor:pointer;
  flex:1;
  transition:all 0.3s ease;
}
.btn-primary {
  background: var(--teal);
  color:#000;
  box-shadow:0 0 10px var(--teal-glow);
}
.btn-primary:hover { background: var(--teal-hover); }
.btn-primary:disabled { opacity:0.6; cursor:not-allowed; }
.btn-secondary {
  background:transparent;
  color: var(--teal);
  border:2px solid var(--teal);
}
.btn-secondary:hover { background: var(--teal); color:#000; }

.back-btn {
  max-width:150px;
}

/* Steps */
.step { display:none; }
.step.active { display:block; }

/* Error Styling */
.error { border-color:#ff4444 !important; }
.error-message { color:#ff4444; font-size:0.875rem; margin-top:0.25rem; }

/* Image Preview */
.image-preview img {
  max-width:200px;
  max-height:200px;
  border-radius:12px;
  border:2px solid var(--teal);
  margin-top:10px;
  box-shadow:0 0 10px var(--teal-glow);
  object-fit: cover;
}

@media (max-width: 600px) {
  .navbar { flex-direction: column; gap: 12px; text-align: center; padding: 1.5rem 1rem; }
  .nav-links { flex-direction: column; gap: 10px; width: 100%; }
  .user-info { width: 100%; text-align: center; }
}

@media (max-width: 480px) {
  .setup-container {
    margin: 15px auto;
    padding: 15px;
    border-radius: 10px;
    width: 95%;
  }
  .header h1 {
    font-size: 18px;
  }
  .navigation {
    flex-direction: column;
    gap: 10px;
  }
  .navigation .btn {
    width: 100%;
  }
}
</style>
</head>
<body>
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="mainBody">
  <div class="main-content">

<div class="setup-container form-container">
  <div class="header">
    <h1>Add New Product</h1>
    <p>Fill out the details below to add your product</p>
  </div>
   <div>
     <button type="button" class="btn-primary btn back-btn" onclick="window.location.href='home.php'" style="margin-bottom:30px">← BACK</button>
    </div>

  <form id="productForm" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
    <input type="hidden" name="form_token" value="<?php echo htmlspecialchars($formToken); ?>">
    <!-- Step 1 -->
    <div class="step active" id="step1">
      <div class="form-group">
        <label for="productName">Product Name:</label>
        <input type="text" id="productName" name="productName" placeholder="Enter product name..." required>
        <div class="error-message" id="nameError"></div>
      </div>
      <div class="form-group">
        <label for="productPrice">Price (₹):</label>
        <input type="number" id="productPrice" name="productPrice" min="0" step="0.01" placeholder="Enter price..." required>
        <div class="error-message" id="priceError"></div>
      </div>
      <div class="form-group">
        <label for="productHsn">HSN Code:</label>
        <input type="text" id="productHsn" name="productHsn" placeholder="Enter HSN code (optional)...">
        <div class="error-message" id="hsnError"></div>
      </div>
    </div>

    <!-- Step 2 -->
    <div class="step" id="step2">
      <div class="form-group">
        <label for="productDescription">Description:</label>
        <textarea id="productDescription" name="productDescription" rows="6" placeholder="Enter detailed product description..." required></textarea>
        <div class="error-message" id="descriptionError"></div>
      </div>
    </div>

    <!-- Step 3 -->
    <div class="step" id="step3">
      <div class="form-group">
        <label for="productImage">Upload Product Image:</label>
        <input type="file" id="productImage" name="productImage" accept="image/*" required>
        <div class="image-preview" id="imagePreview"></div>
        <div class="error-message" id="imageError"></div>
      </div>
    </div>

    <div class="navigation">
      <button type="button" class="btn btn-secondary" id="prevBtn" onclick="previousStep()">← Previous</button>
      <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextStep()">Next →</button>
      <button type="submit" class="btn btn-primary" id="submitBtn" style="display:none;">✓ Submit Product</button>
    </div>
   
  </form>
</div>

<script>
// Multi-step form logic
let currentStep = 1;
const totalSteps = 3;
showStep(currentStep);

function showStep(step) {
  document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
  document.getElementById('step'+step).classList.add('active');
  document.getElementById('prevBtn').style.display = step===1?'none':'inline-block';
  document.getElementById('nextBtn').style.display = step===totalSteps?'none':'inline-block';
  document.getElementById('submitBtn').style.display = step===totalSteps?'inline-block':'none';
}

function nextStep() {
  if(validateCurrentStep() && currentStep<totalSteps){ currentStep++; showStep(currentStep);}
}

function previousStep(){ if(currentStep>1){currentStep--; showStep(currentStep);} }

function validateCurrentStep() {
  clearErrors();
  let valid=true;
  if(currentStep===1){
    const name=document.getElementById('productName').value.trim();
    const price=document.getElementById('productPrice').value;
    if(!name){showError('productName','nameError','Product name required'); valid=false;}
    if(!price || parseFloat(price)<=0){showError('productPrice','priceError','Price must be greater than 0'); valid=false;}
  }
  if(currentStep===2){
    const desc=document.getElementById('productDescription').value.trim();
    if(!desc || desc.length<10){showError('productDescription','descriptionError','Description must be at least 10 characters'); valid=false;}
  }
  if(currentStep===3){
    const image=document.getElementById('productImage').files[0];
    if(!image){showError('productImage','imageError','Product image required'); valid=false;}
  }
  return valid;
}

function showError(inputId,errorId,message){
  document.getElementById(inputId).classList.add('error');
  document.getElementById(errorId).textContent=message;
}
function clearErrors(){
  document.querySelectorAll('.error-message').forEach(el=>el.textContent='');
  document.querySelectorAll('input,textarea').forEach(el=>el.classList.remove('error'));
}

// Image preview
document.getElementById('productImage').addEventListener('change',function(e){
  const file=e.target.files[0];
  const preview=document.getElementById('imagePreview');
  if(file){
    const reader=new FileReader();
    reader.onload=function(e){ preview.innerHTML='<img src="'+e.target.result+'">'; };
    reader.readAsDataURL(file);
  }
  else{preview.innerHTML='';}
});

// Guard against double-click/double-submit: bail out immediately (before any other
// work) if a submit is already in flight. Belt-and-suspenders with the server-side
// one-time form_token check, since a purely client-side guard can be bypassed
// (disabled JS, slow network, etc.) and this app inserts a real DB row per submit.
let productSubmitting = false;
document.getElementById('productForm').addEventListener('submit', function(e){
  if (productSubmitting) { e.preventDefault(); return; }
  if (!validateCurrentStep()) {
    e.preventDefault();
    return;
  }
  productSubmitting = true;
  document.getElementById('submitBtn').disabled = true;
  document.getElementById('submitBtn').textContent = 'Saving...';
});
</script>
</div>
</div>
</body>
</html>