<?php
// quotation.php - Backend API Handler
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');


require_once __DIR__ . '/config.php';

// Create connection
$conn = db_connect();

// Get the action parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Route to appropriate function
switch ($action) {
    case 'get_companies':
        getCompanies($conn);
        break;
    
    case 'add_company':
        addCompany($conn);
        break;
    
    case 'get_products':
        getProducts($conn);
        break;
    
    case 'get_user_images':
        getUserImages($conn);
        break;
    
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action'
        ]);
        break;
}

$conn->close();

// Function to get all companies
function getCompanies($conn) {
    $sql = "SELECT company_id, party_name, party_add, party_contact, party_email, gst_no FROM Companies ORDER BY party_name";
    $result = $conn->query($sql);
    $companies = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $companies[] = [
                'id' => $row['company_id'],
                'name' => $row['party_name'],
                'address' => $row['party_add'],
                'contact' => $row['party_contact'],
                'email' => $row['party_email'],
                'gst_no' => $row['gst_no']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $companies
    ]);
}

// Function to add a new company
function addCompany($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        $data = $_POST;
    }
    
    // Validate required fields
    if (empty($data['party_name']) || empty($data['party_add']) || 
        empty($data['party_contact']) || empty($data['party_email']) ||
        empty($data['gst_no'])) {
        echo json_encode([
            'success' => false,
            'error' => 'All fields are required'
        ]);
        return;
    }
    
    // Prepare and bind with 5 parameters
    $stmt = $conn->prepare("INSERT INTO Companies (party_name, party_add, party_contact, party_email, gst_no) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", 
        $data['party_name'], 
        $data['party_add'], 
        $data['party_contact'], 
        $data['party_email'],
        $data['gst_no']
    );
    
    // Execute
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Company added successfully',
            'company_id' => $conn->insert_id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to add company: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
}

// Function to get all products
function getProducts($conn) {
    $sql = "SELECT `product_id`, `name`, `price`, `description`, `image`, `date_added` FROM products ORDER BY product_id ASC";
    $result = $conn->query($sql);
    $products = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = [
                'id' => $row['product_id'],
                'name' => $row['name'],
                'price' => floatval($row['price']),
                'description' => $row['description'],
                'image' => $row['image'],
                'date_added' => $row['date_added']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $products
    ]);
}

// Function to get user images
function getUserImages($conn) {
    session_start();
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 18;
    
    $sql = "SELECT header_image, footer_image, sign_image FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'status' => 'success',
            'data' => [
                'header_image' => $row['header_image'],
                'footer_image' => $row['footer_image'],
                'sign_image' => $row['sign_image']
            ]
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No images found for this user.'
        ]);
    }
    
    $stmt->close();
}
?>