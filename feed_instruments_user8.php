<?php
/**
 * Database Feeder Script specifically for USER ID = 8
 * Dynamically resolves company_id for user_id = 8 and feeds all 324 products.
 * Includes interactive JS toast notification & completion banner.
 */
if (php_sapi_name() === 'cli' && empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

$target_user_id = 8;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Feeder - User ID 8</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; background: #1e293b; padding: 25px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h1, h2, h3 { color: #2dd4bf; }
        .alert-bar { padding: 15px 20px; background: #065f46; border: 1px solid #10b981; color: #ecfdf5; border-radius: 8px; font-weight: bold; font-size: 16px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
        .btn-dash { display: inline-block; background: #2dd4bf; color: #0f172a; font-weight: bold; text-decoration: none; padding: 10px 20px; border-radius: 6px; transition: all 0.2s; }
        .btn-dash:hover { background: #14b8a6; transform: translateY(-2px); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #0f172a; border-radius: 8px; overflow: hidden; }
        th { background: #104D38; color: #ffffff; padding: 12px; text-align: left; font-size: 13px; }
        td { padding: 10px 12px; border-bottom: 1px solid #334155; font-size: 13px; }
        tr:nth-child(even) { background: #1e293b; }
        .badge-inserted { color: #34d399; font-weight: bold; background: #064e3b; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .badge-updated { color: #38bdf8; font-weight: bold; background: #075985; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .badge-error { color: #f87171; font-weight: bold; background: #7f1d1d; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
        .summary-card { background: #064e3b; border: 2px solid #10b981; padding: 20px; border-radius: 10px; margin-top: 25px; }
    </style>
</head>
<body>
<div class="container">
<?php
echo "<h1>🚀 Database Feeder — User ID {$target_user_id}</h1>";

// Connect to Database
$con = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($con->connect_error) {
    $socket = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';
    $con = @new mysqli("localhost", "root", "", "quotation_managment", 3306, $socket);
}
if ($con->connect_error) {
    $con = @new mysqli("127.0.0.1", "root", "", "quotation_managment");
}

if ($con->connect_error) {
    die("<div style='color:#f87171; font-size:16px; font-weight:bold;'>❌ Database Connection Error: " . htmlspecialchars($con->connect_error) . "</div></div></body></html>");
}

echo "<div style='color:#34d399; font-weight:bold;'>✅ Connected successfully to Database: {$DB_NAME}</div><hr style='border-color:#334155;'>";

// 1. Resolve company_id for user_id = 8
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
    echo "<div style='color:#fbbf24;'>⚠️ Notice: No company record found in `companies` table for user_id = {$target_user_id}. Defaulting company_id to {$company_id}.</div><br>";
} else {
    echo "<div style='color:#38bdf8; font-weight:bold;'>🏢 Target Company ID: {$company_id} (Linked to User ID: {$target_user_id})</div><br>";
}

// Ensure database schema columns exist
$con->query("ALTER TABLE instruments ADD COLUMN IF NOT EXISTS hsn_code VARCHAR(20) DEFAULT NULL");
$con->query("ALTER TABLE instruments ADD COLUMN IF NOT EXISTS image_url VARCHAR(500) DEFAULT NULL");

$items = [
  {
    "name": "RM-800 Concrete Mixer Machine",
    "price": 450000.0,
    "description": "Batch Capacity: 1 Bag (10/7 CFT) | Mixer Type: With Mechanical Hopper | Power Source: Electric | Engine Power: 6.5 HP | Capacity: 10 cm/hour | Engine/Motor Brand: Kirloskar | Number of Wheels: 4 Wheels | Power: 10 HP | Chassis Type: Heavy Duty Channel | Wheel Type: Pneumatic Tyre | Speed: 800 RPM | Water Pump: 2 HP -- 3 bag reversible concrete mixer machine (rm-800) with 10hp electric motor with 6. 00x16 pneumatic tyre description:",
    "image": "https://4.imimg.com/data4/VP/HF/MY-2095577/rm-800-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Concrete measuring Farma",
    "price": 2850.0,
    "description": "Voltage: 110-230 V | Drum Capacity: 115 - 125 L | Frequency: 50/60 Hz | Motor Power: 370W | Carton Size: 750x577x430 - 750x577x430 mm | Net Weight: 47.5 kg -- Durable finish standards High efficiency",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453185119/RN/MO/ZS/2095577/concrete-farma-tool-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Lab Concrete Mixer",
    "price": 35500.0,
    "description": "Concrete Strength Grade: M30 | Power Source: Electric | Output Capacity: 560 Liters | Brand/Make: SHREEJI | Type Of The Drum Mixer: Tilting Drum Mixer | Material: Cast Iron | Speed Of Mixing Drum: 15r/min | Country of Origin: Made in India -- Sturdy design Mounted on a sturdy rubber tyre stand",
    "image": "https://5.imimg.com/data5/SELLER/Default/2020/8/FY/ET/PQ/2095577/lab-concrete-mixer-500x500.JPG",
    "hsn_code": "9031"
  },
  {
    "name": "Baby Concrete Mixer",
    "price": 80000.0,
    "description": "Power Source: Diesel Engine | Machine Type: Fully Automatic | Output Capacity: 480 Liters | Concrete Strength Grade: M40 | Usage/Application: Construction | Material: Cast Iron -- Capacity: 500/600L Engine: Gasoline/ Diesel engine /Motor",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453799228/RZ/IF/SX/2095577/baby-concrete-mixer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Mini Concrete Mixer",
    "price": 75000.0,
    "description": "Capacity: 230 L | Concrete Strength Grade: M10 | Motor/Engine Power: 3 HP | Drum Capacity: 30 L | Power Source: Electric | Type Of The Drum Mixer: Non- Tilting Drum Mixer | Automation Grade: Semi-Automatic | Country of Origin: Made in India | Brand: Crompton | Usage/Application: LIFTING MATIRIAL | Model Name/Number: SHREEJI -- Quartz plate with round size diameter 10mm-500mm Quartz plate with square size L: 100mm-500mm W: 10mm-500mm",
    "image": "https://5.imimg.com/data5/SELLER/Default/2023/12/372014608/RN/DU/DB/2095577/img-5774-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Walk Behind Vibratory Roller",
    "price": 195000.0,
    "description": "Roller Type: Double Drum Roller | Brand/Make: SHREEJI | Surface of Application: Soil Surface | Automation Grade: Semi-Automatic | Capacity: 10 TOONE | Condition: New -- Fist class in compaction Static weght 800kgs.",
    "image": "https://4.imimg.com/data4/NI/PW/MY-2095577/walk-behind-vibratory-roller-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Concrete Mixer Machine",
    "price": 75000.0,
    "description": "Brand/Make: Shree Ji | Wheels: MS Wheels | Batch Capacity: 140Lts.(7/5cft.) | Power Requirement: 6.5 HP | Engine Cooling: Air/Water cooled Diesel Engine | Phase: 3 Phase -- Excellent performance Auto shut off (option)",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452900029/XJ/NN/SL/2095577/concrete-mixer-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Hydraulic Test Pump",
    "price": 7500.0,
    "description": "Pump Type: Vaccum Pump | Brand: SHREEJI | Motor Speed: 2000 RPM | Discharge: 70-100 LPH | Motor Horsepower: 2 HP -- TP-5 35 kg/cm2 500 TP-10 70 kg/ cm2 1000",
    "image": "https://5.imimg.com/data5/SELLER/Default/2023/11/358674896/CX/NE/TQ/2095577/hydraulic-test-pump-500x500.jpeg",
    "hsn_code": "9031"
  },
  {
    "name": "Hand Operated Concrete Mixer",
    "price": 34500.0,
    "description": "Power Source: Diesel Engine | Output Capacity: 40 litres(5 CFT)- Unmixed, 100 litres(3.5 CFT)- Mixed | Chassis Material: Mild Steel | Color: Yellow | Drum: Made from heavy gauge sheet,Balance on heavy duty bearing & rotates on fixed shaft with doubl | Tyre Material: MS -- Excellent performance Reliable operation",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451664571/TI/RU/JE/2095577/hand-operated-concrete-mixer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Material Handling Mini Lift",
    "price": 65000.0,
    "description": "Capacity: 150 Kg upto 50 feet height | Motor Power: 3ph | Environment: Outdoor | Phase: Three phase electric motor | Country of Origin: Made in India | Structure: Heavy Duty,Robust Steel Structure M S Angle structure | Power: 3 HP Single -- Excellent performance Reliable operation",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455074004/JU/UX/GC/2095577/material-handling-mini-lift-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Concrete Needle Vibrator",
    "price": 14500.0,
    "description": "Length: 3 m | IP Rating: IP55 | Phase: Three Phase | Motor Power: 5 HP | Needles: 40/60mm Needle with 6 mtr. | Usage: Laboratory -- We are involved in offering advanced Needle Vibrators that operates on Diesel fuel. This vibrator is primarily used for proper mixing of concrete and also ensure that the prepared concrete mixture is smooth and free of air bubbles. With the development of industrial requirements, we are employing latest technology to make it in sync with the international standards. Our range is acclaimed at global level for its robust construction, excellent durability and easy operations. Advantages :",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455075234/PQ/GS/XF/2095577/concrete-needle-vibrator-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Rotary Type Sand Screening",
    "price": 32500.0,
    "description": "Capacity: 2CU.M./hour | Brand: SHREEJI | Country of Origin: Made in India | Power: 2HP single 0 motor | Transmission: CD 50130 oil filled | Cylinder: 600 mm x 1500 mm (L)",
    "image": "https://5.imimg.com/data5/UK/LF/MY-2095577/rotary-type-sand-screening-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Bushnell Radar Speed Gun",
    "price": 21500.0,
    "description": "Brand: Bushnell | Application: Laboratory, Industrial, spots | Country of Origin: Made in India | Color: Grey,Black | Body Material: ABS,PVC -- Product Details: Provides +/- 1 MPH accuracy instantaneously",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452906360/LX/FG/VV/2095577/bushnell-radar-speed-gun-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "High Voltage Holiday Detector",
    "price": 55000.0,
    "description": "Brand: shreeji | Voltage: 110-240V | Country of Origin: Made in India | Frequency: 50Hz | Power Source: Electric -- We have exported our Instruments to More Than 26 Countries like USA, Colombia, UK, Belarus, Czech Republic, Germany, Italy, Poland, Romania, Spain, Switzerland, Turkey, Congo, Ghana, Saudi Arabia, Morocco, Zimbabwe, Bangladesh, Iran, Nepal, Pakistan, South Korea, China, Malta, etc.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452900210/JR/CL/TT/2095577/high-voltage-holiday-detector-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Gauging Trowel Cement Testing Instruments",
    "price": 300.0,
    "description": "Trowel Type: Finishing Trowel | Blade Length: 150 mm | Blade Material: Stainless Steel | Handle Type: Soft Grip | Blade Width: 150 mm | Usage/Application: Gauging | Material: Carbon Steel Trowel | Brand: ShreeJi | Size/Dimension: 12\",14\",16\",18\",20\" | Finishing: Polished | Handle Material: Wood Handle -- Optimum quality Fine finish",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453483353/LH/KE/AM/2095577/gauging-trowel-cement-testing-instruments-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Rain Gauge Cylinder",
    "price": 5500.0,
    "description": "Material: PS, PP | Usage/Application: rain gauge | Size: 5lt | Model Name/Number: shreeji | Measuring Range: 200 ml | Net Weight: 2.5 kg | Usage: Laboratory -- Net Weight: 0.09g Size: 24.5(L)*8.3(up Dia.)*3.5(Down Dia.)Cm",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451665216/ZF/WK/TI/2095577/rain-gauge-cylinder-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Cone Penetrometer Soil",
    "price": 14500.0,
    "description": "Display Type: Analog | Brand: SHREEJI | Usage/Application: Laboratory | Surface Treatment: Color Coated -- Dimensional accuracy and compact size Durable and long lasting performances",
    "image": "https://5.imimg.com/data5/SELLER/Default/2026/1/576438257/DX/OI/US/2095577/cone-penetrometer-soil-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Digital Universal Testing Machine",
    "price": 755000.0,
    "description": "Usage/Application: Industrial | Brand: SHREEJI | Material: Stainless Steel | Frequency: 50Hz | Voltage: 220V/240V | Power Source: Electric -- Loading accuracy as high as \u00b1 1% Straining at variable speeds to suit a wide range of materials",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453183187/NF/MP/NH/2095577/universal-testing-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Slump Cone Test Apparatus",
    "price": 1750.0,
    "description": "Slump Cone Height: 300 mm | Slump Cone Diameter: 200x100 mm | Surface Finish: Painted | Standard: IS 1199 | Brand: SHREEJI | Bottom Diameter: 150mm | Top Diameter: 100mm | Height: 300mm | Tamping Rod Size: 16x600 mm | Base Plate Size: 300x300 mm | Grade: Manual | Usage Area: Lab | Country of Origin: Made in India | Material: MS | Surface Treatment: Color Coated -- Highly reliable Operator friendly",
    "image": "https://4.imimg.com/data4/PA/OS/MY-2095577/slump-cone-test-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Digital Rebound Testing Hammer",
    "price": 185000.0,
    "description": "Measuring Range: 10~100N/mm2 | Material: Stainless Steel | Capacity: 20 ton | Usage/Application: Laboratory | Warranty: 1 YEAR | Weight: 0.225 kg | Is It Portable: Portable | Accuracy: 1% | Battery Capacity: 220 | Packaging Type: Box | Brand: CONTROL | Usage: Strength Testing Machine",
    "image": "https://5.imimg.com/data5/VX/KQ/MY-2095577/rebound-hammer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Beam Mould",
    "price": 3600.0,
    "description": "Usage: Flexural Test | Material: Cast Iron | Beam Size: 150\u00d7150\u00d7700 mm | Mold Material: Cast Iron | Size: 700X150X150 | Usage/Application: Construction | Weight: 18KG | Number Of Gang: Single Gang | Surface Finish: Machined | Standard: IS 9399 | Automation Grade: Manual | Clamping Type: Screwed | Finish Coating: Painted | Brand: Sheeji | Casting Material: Concrete | Mould Life: BEAM MOULDS 700X150X150 / 750X150X150 | Product Material: MS | Cavity: BEAM CASTING",
    "image": "https://2.imimg.com/data2/KM/RE/MY-2095577/beam-mould-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Plate Load Testing Apparatus",
    "price": 85000.0,
    "description": "Load plate: 2500 cm2,1000cm2 | Hydraulic jack max load: 50T | Hydraulic jack journey: 80mm | Electric oil pump rated pressure: 63Mpa | Pressure range: 0-500KN | Use: Industrial -- Simple design Smooth operation",
    "image": "https://4.imimg.com/data4/TR/NL/MY-2095577/plate-load-testing-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Vicat Needle Test Apparatus Automatic Recording",
    "price": 125000.0,
    "description": "Total rating: 25 W | Voltage: 230 V | Frequency: 50-60 Hz | Phase: 1 phase | Length: 180 mm | Width: 300 mm | Height: 440 mm -- Long service life High accuracy",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455074404/NV/GB/NL/2095577/vicat-needle-test-apparatus-automatic-recording-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Hydraulic Vibratory Hammer",
    "price": 48500.0,
    "description": "Brand: Shreeji | Warranty: 1 year | Power Consumption (Watt): 372 | No Load Speed (rpm): 2800 RPM | Item Weight (Kgs): 30-40 kg -- Used for changing the soil formation with the use of its vibration. Used for driving hammers into heavy or hard piles.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2026/1/576442600/EP/BX/HY/2095577/hydraulic-vibratory-hammer-500x500.png",
    "hsn_code": "9031"
  },
  {
    "name": "Air Entertainment Meter",
    "price": 24500.0,
    "description": "Application: Laboratory | Brand: SHREEJI | Automation Grade: Manual | Is It Portable: Portable -- An entertainment of air in limited percentage improves durability of concrete and very low percentages deteriorate it, measurement of air entrapped in freshly mixed concrete becomes important. The use of chemical additives to increase workability of concrete requires an air content check to be made. Air Entertainment Meters are used to determine air entertained in freshly mixed concrete by pressure method. Specifications:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2026/1/576437863/RG/ZD/FS/2095577/air-entertainment-meter-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Marshall Stability Testing Machine",
    "price": 45000.0,
    "description": "Power: 380V,50Hz,550W | Test Speed With load: 50+/- 5mm/min | Max Load: 50kN | Accuracy: +/-1% | Flow range: 0~100(0~10 mm) | Specimen size: 152.4x 95.3 mm,101.6x 63.5mm | Net weight: 205kg | Dimensions: 770x530x950mm",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455079721/NY/HQ/IG/2095577/marshall-stability-testing-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Vicat Needle Apparatus",
    "price": 2850.0,
    "description": "Brand: Shreeji | Automation Grade: Semi-Automatic | Material: Bass | Capacity: 3-5 L -- Consists of a handling system and 6 measuring stations Equipped with a drop rod with frictionless electronic measuring system",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453185803/YJ/TI/GH/2095577/vicat-needle-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Compaction Factor Apparatus",
    "price": 15000.0,
    "description": "Cylinder Volume: 15 L | Hopper Shape: Top & Bottom Hopper | Frame Material: Mild Steel | Surface Finish: Painted | Cylinder Diameter: 150 mm | Standard: IS 1199 | Material: MS | Automation Grade: Manual | Surface Treatment: Color Coated | Thickness: 10-15 mm -- Power: Manual Material: cast iron",
    "image": "https://4.imimg.com/data4/OC/ND/MY-2095577/compaction-factor-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Aggregate Crushing Value Apparatus",
    "price": 6500.0,
    "description": "Cylindrical Container: 150 mm +0.5 mm dia x 130 mm to 140 mm | Base Plate: 200 to 230 mm. sqr x 6mm | Plinger: 148mm +0.5mm dia x 100 to 115 mm high | Tamping Rod: 16mm dia x 600mm long | Metal Measure: 115 +0.5 mm dia x 180 0.5 mm high -- Under the strict guidance of seasoned professionals, we are manufacturing and supplying Aggregate Crushing Value Apparatus. Our offered apparatus are manufactured utilizing finest quality material and advanced technology following the standards of industry. The apparatus offered by us are checked in terms of quality before delivering at customers end. We are giving these apparatus from us on diverse specifications. Superior performance",
    "image": "https://5.imimg.com/data5/SELLER/Default/2026/1/576438931/IK/MX/DT/2095577/aggregate-crushing-value-apparatus-500x500.jpeg",
    "hsn_code": "9031"
  },
  {
    "name": "Cylindrical Mould",
    "price": 4200.0,
    "description": "Diameter: 12-16 inch | Shape: Round | Automation Grade: Manual | Surface Treatment: Color Coated | Material: MS -- These are available in different sizes and are made according to Indian and British standards. For the metric size cube molds, the faces are machined flat to 0.02mm accuracy and finished to within 0.02mm. For the inch size molds, the faces are machined flat to 0.01 inches and finished to within 0.01 in. All molds are supplied complete with base plate. Specifications:",
    "image": "https://4.imimg.com/data4/BF/SG/MY-2095577/cylindrical-mould-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Tile Abrasion Testing Machine",
    "price": 48000.0,
    "description": "Usage: Abrasion Tester | Voltage: 440 Volt | Power: Electric | Phase: 3 Phase | Display: Automatic digital | Dimension: 1100x780x100mm",
    "image": "https://5.imimg.com/data5/SELLER/Default/2021/5/TH/RP/XX/2095577/tile-abrasion-testing-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Concrete Cube Vibrating Table",
    "price": 21500.0,
    "description": "Weight: 200 Kg | Material: Iron | Automation Grade: Semi-Automatic | Brand: shreeji | Voltage: 440 volts | Capacity: 140 kg | Phase: 3 Phase | Cycles: 50 cycles and A.C. supply | Table top Size: 50 cm x 50 cm | Vibrating tables of table top size: 75 cm x 75 cm as well as 100 cm x 100 cm | Vibrations: 3600 vibrations per minute -- Having years of experience in this domain, we are capable of providing our customers with Vibrating Tables. All our products are available in various sizes, shapes and designs at market leading prices at industry leading prices to meet the specific requirement of our customers. Moreover, our products are widely used for compacting concrete cubes and cylinders and are known for their longer service life, accuracy and sturdy construction. The apparatus consists of a motor fitted with a variable pitch pulley housed in a cabinet",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453191118/BK/TG/JT/2095577/vibrating-table-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Sand Content Kit, Weight: 0.7 Kg",
    "price": 18500.0,
    "description": "Capacity: 1 L | Automation Grade: Manual | Material: PVC | Brand: SHREEJI | Scale Range: 0-100 % | Test Standard: IS 2720 | Accuracy: \u00b11 % | Color: White -- Api Defines Sand-Sized Particles As Any Material Larger Than 74 \u00b5m (200-Mesh) In Size The Kit Consists Of A Glass Tube Graduated To Read Percent (%) By Volume, A Funnel, and A 200-Mesh Sieve Contained In A Cylindrical Shaped Holder",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451659403/AY/IZ/VN/2095577/sand-content-kit-weight-0-7-kg-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Unconfined Compression Tester Proving Ring Type (Motorized)",
    "price": 80000.0,
    "description": "Capacity: 50 kN | Display Type: Analog | Brand: SHREEJI | Height: 1-2 Feet | Surface Treatment: Polished -- Simple in operation Easy to use",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451659787/QS/UZ/QD/2095577/unconfined-compression-tester-proving-ring-type-motorized-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "CBR Test Apparatus",
    "price": 48500.0,
    "description": "Automation Grade: Semi-Automatic | Brand: Shreeji | Power Source: Electricity | Power: SINGAL PH | Surface treatment: Color Plated -- Excellent performance Low operating cost",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451658961/SS/PT/MI/2095577/cbr-test-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Standard Penetration Test Kit Spt",
    "price": 70000.0,
    "description": "Display Type: No Display | Equipment Type: Dynamic Cone Penetrometer | Testing Method: Manual | Brand: SHEEJI | Color: Blue | Power Source: Manual | Material: MS | Automation Grade: Manual | Height: 3-7 Feet -- Well designed Reliable operation",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453183540/QG/PN/YF/2095577/standard-penetration-test-kit-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Soil Sampling Auger",
    "price": 3500.0,
    "description": "Brand: SHREEJI | Material: MS/SS | Surface Finished: Polished | Height: 2-3 Feet -- Soil Auger /Dutch Auger, one meter long with handle Size:15cm dia",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452903004/BT/JI/NH/2095577/soil-sampling-auger-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Mud Balance",
    "price": 16500.0,
    "description": "Material: S S | Brand: shreeji | Color: Silver | Accuracy: 100% | Country of Origin: Made in India -- 100-00 - Mud Balance, 4 Scale, Plastic Specifications \u2022 Density Ranges 8.0 - 25.0 lb / gal 960 - 3000 Specific Gravity, kg/meter3 60 - 189 lb / ft3 420 - 1300 pounds per inch2 / 1000 ft \u2022 Size: 21.5\" \u00d7 5\" \u00d7 4.5\" (55 \u00d7 13 \u00d7 11 cm) \u2022 Weight: 3 lb (1.4 kg) Designed to find out specific gravities of semi liquids like mud and other liquids. It has a stainless steel bam calibrated. A stainless steel cup with lid and overflow vent is fitted on one side of the beam. A counter weights with cursor slides over the graduated scale. The beam has a knife-edge at center which rests in a fulcrum fitted in the stand. Leveling screws and spirit level are fitted to the stand. Supplied complete with wooden box.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2022/11/EU/FM/CI/2095577/mud-balance-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Gyratory Sieve Shaker",
    "price": 28500.0,
    "description": "Material: Mild Steel | Automation Grade: Semi-Automatic | Voltage: 220V/230V | Automation Type: Automatic | Frequency: 50Hz -- Our immaculate range of Sieve Shaker is highly demanded in the market by our eminent customers. These Shakers are designed using excellent metal, which provides exceptional durability level with long lasting feature. Clients can avail in in various sizes, designs and shapes as per need with proper customization. Our experts design these trainers in complete adherence to the international standards so that we successfully meet the expectations of our patrons. Two types of sieve shaker available with us:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451660129/RM/HG/IU/2095577/gyratory-sieve-shaker-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Swell Test Apparatus",
    "price": 38500.0,
    "description": "Automation Grade: Semi-Automatic | Material: MS | Brand: SHREEJI | Color: Blue | Surface Treatment: Color Coated -- One loading unit Hand. operated 5000 kg capacity with two rates of travel One Gun metal mould, 100mm dia x 127.3 cm height X 1000 cc volume with base plate & collar",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451657477/WA/RH/WB/2095577/swell-test-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Rapid Moisture Meter",
    "price": 4250.0,
    "description": "Usage/Application: Laboratory | Operating Temperature: 0-50 Degree Celsius | Automation Grade: Manual | Packaging Type: Box | Box Material: Wooden -- Balance Base Balance Arm",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451660797/UW/SN/YF/2095577/rapid-moisture-meter-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Earth Soil Land Auger Digger Drill Machine",
    "price": 3250.0,
    "description": "Drill Diameter: All inch | Material: Tungsten Carbide | Length: 0-30 mm, 60-100 mm, 30-60 mm | Diameter: 75 mm - 150 mm | Automatic Grade: Manual | Model Type: Sb-K2 -- Augers are used to collect distributed soil samples at reasonable depths for laboratory tests. Augers are available in two type and each in different sizes. Blade type (post hole type) and Helical type (screw type). Each auger outfit consists one each of Auger head, one meter long rod, Tee piece and handle. Depths of excavating can be increased by using additional extension rods shreeji instruments are leding supplaey in gujarat .Posthole Type Sampling Augers: 150 mm dia.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453480974/LN/DT/YI/2095577/earth-soil-land-auger-digger-drill-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Flow Table Hand Operated",
    "price": 6500.0,
    "description": "Application Media: Water | Material: MS | Height: 10-16 inch | Brand: SHREEJI | Surface Treatment: Color Coated -- Our clients can avail from us Flow Tables that are widely acknowledged for their longer functional life, corrosion resistance and dimensional accuracy. Our products are widely used in various industries laboratories and research institutes. Apart from this, our products are thoroughly checked by our team of expert quality controllers to ensure flawlessness. These products are also used for determining the workability of building limes. Specifications:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/449959258/CU/JJ/QN/2095577/flow-table-hand-operated-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Soil Cone Penetrometer",
    "price": 2850.0,
    "description": "Material: Cast Iron | Diameter: 50 mm | Weight: 148 +0.5 gms. | Length: 30.50mm | Depth: 50 mm -- It will consist of metallic cone with half angle of 15' -30 \u00b115' and 30.50mm coned length It will be fixed at the end of a metallic rod with a disc at the top of the rod so as to have a total sliding weight of 148\u00b1 0.5 gms. The total rod shall pass through two guides (to ensure vertical movement) fixed to a stand",
    "image": "https://5.imimg.com/data5/SELLER/PDFImage/2024/9/449978553/HN/FV/UZ/2095577/soil-cone-penetrometer-500x500.png",
    "hsn_code": "9031"
  },
  {
    "name": "Relative Density Apparatus",
    "price": 65000.0,
    "description": "Container Volume: 500 ml | Application: Laboratory | Material: Mild Steel | Automation Grade: Semi-Automatic | No. Of Cylinders: 2 Cylinders | Stand Type: Fixed Stand | Color: BLUE | Industrial Use: Soil Testing | Condition: New | Brand: SHREEJI -- Specimen mould: 250 ml; Inner diameter 5 cm; Height 12.7 cm Rammer: Speed 32 beats/min; Weight 1.25kg; Diameter 5cm; Drop height 15cm",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451659259/KG/FS/XI/2095577/relative-density-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Core Cutter With Dolly And Rammer",
    "price": 2150.0,
    "description": "Brand: Shreeji | Blade material: Carbon Steel, Plastic | Operation: Manual | Application: Metal Sheet | Surface Treatment: Chrome | Diameter (mm): 175 mm -- Cylindrical core cutter 100mm i. D. X 175mm long Test form pad of 50",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451658381/GR/PS/QA/2095577/core-cutter-with-dolly-and-rammer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Liquid Limit Apparatus",
    "price": 3150.0,
    "description": "Usage/Application: Laboratory | Net Weight: 6.2 kgs | Accessories: Grooving Tool | Model: 22-T0031/E | Distance: 1. 25 cm -- Net Weight: 6.2kgs Accessories: Grooving Tool",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451659081/UG/WH/VC/2095577/liquid-limit-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Proctor Compaction Test Apparatus",
    "price": 5800.0,
    "description": "Hammer Weight: 4.89 kg | Brand: SHREEJI | Capacity: 2250 cc | Material: Brass and SS | Diameter: 150 mm | Color: Silver | Automation Grade: Manual | Surface Finished: Polished -- High efficiency Hassle free performance",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451668510/ZE/NA/TT/2095577/proctor-compaction-test-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Triaxial Shear Test Apparatus",
    "price": 110000.0,
    "description": "Capacity: 50KN | Material: Mild Steel | Automation Grade: Semi-Automatic | Usage/Application: Laboratory | Phase: SINGAL PH | Brand: SHREEJI | Country of Origin: Made in India",
    "image": "https://5.imimg.com/data5/SELLER/Default/2021/6/YX/CH/NM/2095577/triaxial-shear-test-apparatus-500x500.JPG",
    "hsn_code": "9031"
  },
  {
    "name": "Direct Shear Test Apparatus",
    "price": 91000.0,
    "description": "Color: Blue | Display: DIAL | Brand: SHREEJI | Automation Grade: Semi-Automatic | Phase: SINGAL PH -- AS PER IS: 2720 (Part-VIII), ASTM D-3080 For determination of the direct shear strength of soils on specimen size 60 mm x 60 mm x 25 mm. Specification: The apparatus comprises of",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451661197/YL/CM/RI/2095577/direct-shear-test-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Triaxal Testing Machine",
    "price": 100000.0,
    "description": "Capacity: 50 kN | Automation Grade: Semi-Automated | Material: Mild Steel | Usage/Application: Industrial | Country of Origin: Made in India | Shear Rate: 0.001-5.000mm/Min | Confining Pressure: 0-1MPa | Aperture Pressure: 0-2MPa | Size: 39.1x80mm | Volume Change: 0-50ml -- Needs less maintenance Easy to operate",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453502064/WO/BW/SC/2095577/triaxal-testing-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Sand Equivalent Test Apparatus",
    "price": 7500.0,
    "description": "Brand: SHREEJI | Material: Wood and Glass | Country of Origin: Made in India | Jar Capacity: 1-5 L | Glass Thickness: 1-2 mm -- Compact design Sturdy construction",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452915029/VH/NW/DS/2095577/sand-equivalent-test-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Marsh Cone With Stand Brass Cone",
    "price": 7200.0,
    "description": "Material: Mild Steel | Surface Treatment: Polished and Color Coated | Rod Material: SS | Cone Material: Brass | Height: 3-4 Feet",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452908081/EK/KG/RG/2095577/marsh-cone-with-stand-brass-cone-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Automatic Compactor For Marshal Test",
    "price": 50000.0,
    "description": "Display Type: Digital | Automatic Grade: Semi-Automatic | Grade: Semi-Automatic | Brand: SHREEJI | Type of Testing Machines: marshal test -- Durable finish standards High efficiency",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451660548/EZ/ZU/ZI/2095577/automatic-compactor-for-marshal-test-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Soil Permeability Apparatus",
    "price": 48500.0,
    "description": "Automation Grade: Semi-Automatic | Brand: Shreeji | Weight: 3.1 kg | Max water leakage: 100 Kpa | Pressure resistant performance: 200 kpa -- The apparatus is used to determine permeability of soils using a constant or variable head. This test is recommended for soils with coefficient of permeability in the range of 10-3 to 10-7 cm / sec. The maximum particle size of the soil, which can be tested in the mould is 10 mm.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451657500/FH/ST/GI/2095577/soil-permeability-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Grain Size Analysis Apparatus",
    "price": 40000.0,
    "description": "Automation Grade: Semi-Automatic | Brand: SHREEJI | Material: MS | Glass Thickness: 1-2 mm | Surface Treatment: Color Coated -- Specifications :The apparatus consists of a sliding panel which moves up and down by means of a screw allowing Anderson pipette fixed to it to be raised or lowered vertically. A sedimentation tube is held by a laboratory clamp provided on the stand below the pipette. The depth of immersion is measured by a scale graduated in mm at the side of the sliding panel. Supplied complete with Anderson pipette 10 ml. at the side capacity made from glass, and a sedimentation tube also of glass of 500 ml capacity and 50 Nos Test forms. Accessories & Spares :1) Sedimentation pipette (Anderson pipette) 25ml. 2) Sedimentation tube 1000ml.3) Sedimentation pipette 10ml.4) Sedimentation tube 500ml.5) Test forms pad of 50.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451657404/DD/HA/FW/2095577/grain-size-analysis-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Soil PH and Moisture Meter",
    "price": 7000.0,
    "description": "Warranty: 1 Year | Application: Laboratory | Resolution: +/-0.2 pH | Range: 3 - 8 pH | pH Moisture: 1 - 810%-80% -- Operating Instructions for the SOIL PH AND MOISTURE TESTER. Notes :-Soil pH value is a very important factor in the production of quality crops. Most cropscannot survive in soil that is too acid or too alkaline. Therefore the correct pH reading isessential to achieve optimum results.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453790512/KP/TI/QZ/2095577/soil-ph-and-moisture-meter-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Static Cone Penetrometer",
    "price": 17500.0,
    "description": "Drop Height: 500 mm | Rammer Weight: 10kg+/-10g | Max Penetration Depth: 6000 mm | Penetration Rammer Angle Max Diameter: 40mm | ISO: ISO9000 | Penetration Rammer Extent: 60",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451661013/LR/IN/XU/2095577/static-cone-penetrometer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Dynamic Cone Penetration Apparatus",
    "price": 21500.0,
    "description": "Brand: SHREEJI | Usage/Application: Laboratory | Automation Grade: Manual | Material: SS | Box Material: Wooden -- Type: Penetration Test Equipment Power Source: AC220V",
    "image": "https://4.imimg.com/data4/RP/GS/MY-2095577/dynamic-cone-penetration-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Core Cutter Dolly with Rammer",
    "price": 1800.0,
    "description": "Material: MS and SS | Brand: SHREEJI | I Deal In: New Only | Bottom Shape: Round | Thickness: 2-6 mm -- We are engaged in providing our clients with Field Density Kits (Core Cutter) that are used for determining the situ dry density of natural or compacted fine grained soil. Use for making the soil free from aggregates, our products have a cylindrical cutter that is used to extract a sample of the soil with the help of a dolley and rammer. Moreover, our products helps in readily calculating the weight, density, moisture and dry density of the soil. Specifications: It consists one each of:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453193510/CG/DE/LU/2095577/core-cutter-dolly-with-rammer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Magnetic Stirrer With Hot Plate",
    "price": 4500.0,
    "description": "Material: Stainless Steel | Frequency: 50 Hz | Capacity: 500 ml | Model Name: SHREEJI | Brand: SHREEJI | Hotplate Surface: with hotplate | Operational mode: ELECTEICAL | Packaging Type: Box | Country of Origin: Made in India | Power Source: Electric | Voltage: 220 V -- Having years of experience in this domain, we are capable of providing our customers with Magnetic Stirrers. Our products are available in various sizes, shapes and designs at market leading prices at industry leading prices to meet the specific requirement of our customers. Moreover, our products are widely used for density tests on aggregates as per procedure laid down and are known for their longer service life, accuracy and sturdy construction. Stainless steel body",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/8/447307876/FD/TH/EJ/2095577/magnetic-stirrer-with-hot-plate-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Sokkia Total Station Im 55",
    "price": 325000.0,
    "description": "Model: IM55 | Model Name/Number: IM 55 | Angle Accuracy: 5\u2033 | Prism Range: 5000 m | Distance Accuracy: \u00b12 mm+2 ppm | Laser Type: Red Laser | Number of Prism: Single | Data Storage: USB Pen Drive | Brand: SOKKIA | Distance Measurement: 1 km | Resolving Power: 2.5 inch | Angle Measurement Accuracy: 5 second | Magnification: 32X | Material: Mild Steel | Minimum Readout: 0.5 mm | Minimum Focus: 0.5m | Battery Backup: 36 hrs | Usage/Application: Surveying | Data Storages: 10000 points | LCD Display Panel: Single Side | Measuring Time: 0.5 second | Battery Type: Li-ion Rechargeable | Warranty: 1 year | Weight: 6Kg | Objective Aperture: 32mm | Distance Measurement (km): 2.5 Km | Data Storages ( Points ): 40000 Points | Battery Backup (hours): 8 hrs. | Color: BLUE -- 2 GB External Memory Absolute Encoding",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455084040/YR/DI/TR/2095577/sokkia-total-station-im-55-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Sokkia Im 55 Total Station",
    "price": 310000.0,
    "description": "Model Name/Number: IM 55 | Brand: SOKKIA | Usage/Application: Land Survey | Distance Measurement: 1000 METER | Angle Measurement Accuracy: 5\" -- Justifying our reputation in this market, we are readily immersed in the arena of offering to our patrons a broad consignment of Sokkia IM 55 Total Station. Other Details:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455083926/MW/XV/JX/2095577/sokkia-im-55-total-station-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Sokkia Total Station Im 55 in Jamnagar",
    "price": 325000.0,
    "description": "Model Name/Number: IM 55 | Number of Prism: Single | Brand: SOKKIA | Distance Measurement: 1000 METER | Usage/Application: Survey | Magnification: 32X | Resolving Power: 2.5 inch | Material: Brass | Minimum Readout: 0.5 mm | Minimum Focus: 0.5m | Battery Backup: 36 hrs | Data Storages: 10000 points | LCD Display Panel: Single Side | Measuring Time: 0.5 second | Battery Type: Li-ion Rechargeable | Warranty: 1 year | Angle Measurement Accuracy: 5\" -- The iM-100 (Intelligent Measurement Total Station) incorporates all the features you need at a cost-efficient price. It will handle your most demanding survey layout or as-built project needs.You get fast, accurate, and powerful EDM technology, best-in-class accuracy up to 5,000 m with a prism and up to 1000 m in reflectorless mode, and a battery life of up to 28 hours. The iM-100 is ready to be your hardest worker out in the field.Features:SOKKIA iM 100 achieves Prism level distance accuracy on non-Prism & Sheet modeFast, accurate and powerful EDM measures with accuracy 1.5mm Prism mode & 2mm Non Prism, Sheet modeDual axis compensationInternal memory 50000 points & support USB memory upto 32GBWaterproof IP66 rating ( Operating Temp -20 to 60 degrees )Up to 28 hours of battery lifeWorld\u2019s First Advanced Security & TS Shield Theft protect instrument tracking system Other Details:RED-tech Technology Reflectorless EDMHigher accuracy 2mm on relflectorless mode with Long range upto 1000 mtrsFast distance measurement of 0.9s regardless of object.SOKKIA traditional pinpoint precision in reflectorless distance measurement.Reflectorless operation from 30cm to 1000m.Coaxial EDM beam and laser-pointer provide fast and accurate aiming.Ensures Prism level accuracy even with reflective sheets.Double sided backlit keyboard & Guide lightsiM features SOKKIA's original absolute encoders that provide long-term reliability in any job site condition.Dual-axis compensator ensures stable measurements even when setup on uneven terrain.Sokkia\u2019s traditional motion clamp and tangent screw are employed to ensure stable angle measurement.IM101 and IM102 feature groundbreaking IACS (Independent Angle Calibration System) technology for extremely reliable angle measurement.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455093885/ZO/PX/UA/2095577/sokkia-total-station-im-55-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Sokkia Total Station Im 101 in Vadodara",
    "price": 520000.0,
    "description": "Model Name/Number: IM 101 | Number of Prism: Single | Brand: SOKKIA | Distance Measurement: 1 km | Usage/Application: Land Survey | Magnification: 32X | Material: Mild Steel | Minimum Readout: 0.5 mm | Minimum Focus: 0.5m | Battery Backup: 36 hrs | Data Storages: 100000 points | LCD Display Panel: Double Side | Operating Temperature Range: -10 DegreeC -- 50 DegreeC | Measuring Time: 1 second | Weight (kg): 4KG | Battery Type: Li-ion Rechargeable | Warranty: 1 year | Angle Measurement Accuracy: 1\" -- BDC70 Rechargeable Battery x 1 Quick Charger and charger cable",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455093754/ND/XF/TB/2095577/sokkia-total-station-im-101-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Sokkia Total Station",
    "price": 330000.0,
    "description": "Model Name/Number: IM 55 | Packaging Type: Box | Battery Backup: 36 hrs | Warranty: 1 year | Field Of View: 26m/1000m | Accuracy: +/-(3mm+2ppm@ D) | Shortest Sighting Distance: 1.0m -- Shortest Sighting Distance: 1.0m Field of View: 26m/1000m",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455093652/BU/AM/LC/2095577/sokkia-total-station-im-55-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Portable Fire Extinguisher",
    "price": 1850.0,
    "description": "Capacity: 5 Kg | Color: White | Weight: 5 KG | Working Pressure: 8bar --20bar | Adhesion Strength: Greater than 30n | Length: 10m-30m | Lining: PVC/ Rubber Hose | Inner Diameter: 19mm-300mm | Bursting Pressure: 18bar--60bar -- Best performance against flame Reliable usage",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453484078/FK/CW/CQ/2095577/portable-fire-extinguisher-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Fire Safety Blankets",
    "price": 3500.0,
    "description": "Material: Fiberglass | Usage/Application: Fire Fighting | Color: Red | Thickness: 0.13 mm - 3.7 mm | Width: 1-2 m | Packing Type: Bag",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451858928/CB/OF/GP/2095577/fire-safety-blankets-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Digital Theodolite",
    "price": 55000.0,
    "description": "Angle Accuracy: 2\u2033 | Telescope Length: 180 mm | Least Count: 1\u2033 | Battery Type: Rechargeable Ni-MH | Usage: Levels angle, Alignment of lines | Brand: SETL | Usage/Application: Surveying, Meteorology, Construction, Mining | Magnification: 30x | Display Panel: Double Side | Display Type: Single Side -- Being the preferred choice of our customers, we are indulged in providing a flawless quality range of SETL Digital Theodolite to our customers. This SETL Digital Theodolite provides correct and accurate measurements during survey and mapping of a large scale area. Also, these products are nominal in prices. Specification:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451656936/JA/EK/JO/2095577/digital-theodolite-setl-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Sokkia Electronic Digital Theodolite",
    "price": 185000.0,
    "description": "Model Name/Number: DT 02 | Display Panel: Double Side | Usage/Application: Surveying | Material: ABS, Aluminium | Magnification: 24X -- Quality, Performance, and Affordability that Ensure Maximum Profitability The laser models incorporate built-in coaxial laser pointers that maximize construction work efficiency. The applications include: - Layout (Setting-Out) - Horizontal/Vertical Alignment - Leveling - Grading - Squaring",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451657039/MS/EW/EY/2095577/sokkia-electronic-digital-theodolite-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Garmin GPSMAP 64 Devices",
    "price": 28500.0,
    "description": "Screen Size: HAND HELD | Usage/Application: Hand Held | Type: Wireless | Model Name/Number: Etrex 10 | Brand: Garmin -- Specifications: 2.6\" sunlight-readable color screen",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452908731/BG/CH/HN/2095577/garmin-gpsmap-64-devices-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Garmin 64s GPS Devices",
    "price": 29500.0,
    "description": "Screen Size: 2.5 Inch | Type: Wireless | Model Name/Number: Etrex 10 | Packaging Type: Box | Body Material: ABS -- size: 32 X 54 X 13.5mm Display: 0.96 \" OLED 128x64",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452908389/ZP/GS/DT/2095577/garmin-64s-gps-devices-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Flakiness and Elongation Gauge",
    "price": 1050.0,
    "description": "Material: Stainless Steel | Packaging Type: Box | Weight: 480g | Nominal Size: 1.8 mm | Usage: Laboratory -- Corrosion resistance Fine finish",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451666216/ZR/AF/DW/2095577/flakiness-and-elongation-gauge-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Aggregate Density Basket",
    "price": 1050.0,
    "description": "Material: Made of brass with stainless steel wire mesh | Height: 20 cm high | Wire Mesh Size: 6.3 mm | Diameter: 20 cm | Shape: Round | Type: Complete with handle -- We are successfully engaged in offering a commendable array of Density Basket. The offered density basket is designed keeping in mind the standards of market using excellent quality of material. This density basket is used for density tests on aggregates as per procedure laid down and are known for their longer service life. Customers can easily avail this density basket from us in a confine time at nominal rates. Superior performance",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453190924/US/IF/BC/2095577/aggregate-density-basket-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Bulk Density Basket",
    "price": 7200.0,
    "description": "Capacity: 3 LTR,15 LTR,30 LTR | Material: Brass | Application: CONSTRUCTION | Brand: Shreeji | Country of Origin: Made in India",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453187017/RD/MT/CA/2095577/bulk-density-basket-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Riffle Sample Divider",
    "price": 12500.0,
    "description": "Feeding size: Not more than 10mm | Sample Quantity: 6pcs or 8pcs | Feeding volume: not more than 4000ml | Sample bottle volume: 100/300/500ml | Time setting: 1-99min | Sample flow rate: 0-5l/min | Power supply: 220V | Frequency: 50Hz | Total weight: About 80kg | Dimension: 520x400x830mm",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453789420/YN/KV/NA/2095577/riffle-sample-divider-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Riffle Sampler Divider",
    "price": 8500.0,
    "description": "Brand: Shreeji | Dividing Quantity: 200-1500g | Motor Power: 25W | Motor Rotational Speed: 0-1440r/min | Dividing Error: + 0.5% per kilogram | Working Voltage: 220V 50Hz -- Dividing quantity: 200-1500g Motor power: 25W",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453789489/QB/XS/MR/2095577/riffle-sampler-divider-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Electrically Operated Bitument Extractor",
    "price": 7000.0,
    "description": "Automation Grade: Manual | Material: Cast Iron | Brand: SHREEJI | Hand Operated: Yes | Installation Services: No | Packaging Type: Box | Surface Treatment: Color Coated -- Centrifuge Extractor (Motorized): Same as above but shaft is rotated by an electric motor and gear. Care is taken to prevent solvent entering into the rotor of electrical motor.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453797789/IF/GH/ON/2095577/electrically-operated-bitument-extractor-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Vibrating Machine",
    "price": 22500.0,
    "description": "Max Weight: 0-200 kg | Power: 1-2 kw, 0-1 kw | Vibrating Range (Amplitude): 2-3 mm, 1-2 mm | Frequency: 50/60 hz | Automation Grade: Automatic | Portable: Yes | Power Source: Electricity -- We are offering our clients with Vibrating Machines that are also known as Mould Vibrator or Mortar Cube Vibrator. These concrete moulds are easily cast by using a tamping bar or a vibrating table. However air tapped in cement mortar paste can not be thus removed while casting cement mortar moulds. Easy method is to impart greater vibration of lesser amplitude to the mould while casting. This is achieved in a vibrating machine. Vibration machine is used for the preparation of mortar cubes for the determination of compression strength of ordinary and rapid hardening Portland cement, low heat portland cement, portland bleast furnace cement and high alumina cements. Specifications:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452901274/FD/RF/GT/2095577/vibrating-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Flexural Testing Machine",
    "price": 48500.0,
    "description": "Capacity: 50 kN | Control Type: Analog | Loading Type: Hydraulic | Automation Grade: Manual | Material: Iron | Specimen Type: Concrete Beam | Power Supply: Manual | Support Span: 150-700 mm | Make: SHREEJI | Warranty: 1 YEAR | Brand: SHREEJI | Power: HYDRAULIC | Measuring Range: 0.1~12000mm/s(L/m2.s) | Test Heads: 5cm2, 20cm2, 50cm2, 100cm2 and 50mm (25sq.cm), 70mm (38sq.cm) | Test Pressure: 1~4000 mPa | Measuring Accuracy: -+/-2% | Nozzles: 11 | Max thickness of specimen: Less then 12mm -- Long service life Corrosion resistance",
    "image": "https://5.imimg.com/data5/GP/NU/ZK/SELLER-2095577/flexure-testing-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Deval Abrasion Testing Machine",
    "price": 55000.0,
    "description": "Specimen Size: 200 x 200 mm | Revolution Counter: Mechanical | Standard: IS 1237 | Material: Mild Steel | Disc Diameter: 200 mm | Abrasive Charge: 20 N | Phase: 3 ph | Grade: Semi-Automatic | Frequency: 50Hz | Voltage: 3 ph | Brand: SHREEJI | Surface Treatment: Color Coated -- Optimum strength Corrosion resistance",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453797551/ZM/TN/FH/2095577/deval-abrasion-testing-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Aggregate Crushing Value",
    "price": 7500.0,
    "description": "Cylinder size: 150x130 mm | Plunger size: 148x100 mm | Body material: Mild Steel | Hammer weight: 13.5 kg | Standard: IS 2386 | Accessories: Tamping Rod | Material: Cast Iron | Brand: Shreeji | Automation Grade: Manual | Packaging Type: Box | Diameter: 16mm dia x 600mm | Surface Finish: Color Coated -- For Measuring of Resistance of Aggregate to Crushing. Specification:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453797298/AX/AV/JX/2095577/aggregate-crushing-value-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Low Temperature Water Bath",
    "price": 48500.0,
    "description": "Material: Stainless Steel | Temperature Range: 5 deg c to 50 deg c | Automation Type: Semi-Automatic | Color: Grey | Chamber Size: 250 x 200 x 200 mm",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453504100/VY/OF/RN/2095577/low-temperature-water-bath-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Aggregate Impact Tester",
    "price": 7500.0,
    "description": "Voltage: 220 V | Speed: 28-30 RPM | Packaging Type: Box | Brand: Shreeji | Usage: Laboratory -- We are engaged in providing our clients with Dorry Abrasion Testing Machines that are procured from the reliable manufacturers of the market. Our products are widely used in various industries and sectors for diverse applications. Furthermore, our products are available in various sizes & designs at most competitive prices to fulfill their exact requirements and demands. Specification:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455081740/FT/LC/MO/2095577/aggregate-impact-tester-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Bulk Density Cylindrical Measure",
    "price": 7500.0,
    "description": "Shape: Round | Material: Mild Steel | Speed: 1500 RPM | Surface Treatment: Color Coated | Working Temperature: 0~50 Degree C | Linear Speed: 25 m/s -- highest input shaft speed is not more than 1500 RPM. the gear meshing linear speed is not more than 25 m/s;",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453481434/KG/RI/BU/2095577/bulk-density-cylindrical-measure-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "cool and hot room thermometer",
    "price": 450.0,
    "description": "Usage/Application: Laboratory | Material: Brass | Temperature Range: -58 to 536 Degree F, -50 to 280 Degree C | Power: 2 kW | Brand: Shreeji | Packaging Type: Box",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453188772/SP/CZ/EP/2095577/soil-thermometer-price-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Los Angeles Abrasion Testing Machine",
    "price": 75000.0,
    "description": "Usage/Application: Laboratory | Material: Mild Steel | Power: 5 kW | Frequency: 60 Hz | Voltage: 240 V -- The Los Angeles abrasion-testing machine is used for determining the resistance to wear, of small sized aggregates and crushed rocks. It consist of a hollow cylinder, which is mounted on a sturdy frame having ball bearings. Rotational speed of the drum is 30-33 rpm, which is powered by an electric motor supply with 12 abrasive charges of 48 mm.our company shreeji instrument are leading suppler in this instruments in pan india. Salient Features Hollow cylinder mounted on a sturdy frame",
    "image": "https://5.imimg.com/data5/IE/HR/MY-2095577/abrasion-tester-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Specific Gravity Buoyancy Balance",
    "price": 38500.0,
    "description": "Application: CONSTRUCTION USER | Capacity: 200 g | Readability: 0.1 g | Sample Type: Solid + Liquid | Measurement Range: 0\u20133 g/cm\u00b3 | Fixture Type: Density Kit | Use: Industrial | Material to be measured: Metals | Pan Size: 100 mm | Display Type: LCD | Interface: No Interface | Automation Grade: Semi-Automatic | Length: 510 mm | Width: 510 mm | Height: 1150 mm | Weight approx: 50 kg",
    "image": "https://4.imimg.com/data4/BA/FO/MY-2095577/buoyancy-balance-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Pycnometer Bottle With Brass Cone",
    "price": 850.0,
    "description": "Capacity: 100 ml | Type: Specific Gravity | Calibration Temp: 27\u00b0C | Type Of Glassware: Heavy Wall Glass | Material: Glass | Design Type: Bottle | With Thermometer: No | Standard: ASTM | Usage/Application: Chemical Laboratory | Packaging Type: Box | Application: Chemical Laboratory | Country of Origin: Made in India",
    "image": "https://5.imimg.com/data5/HW/JJ/MY-2095577/pycnometer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Viscometer Standard Tar Viscometer",
    "price": 14500.0,
    "description": "Number Of Cups: 1 Cup | Bath Type: Oil & Water Bath | Temperature Range: 0\u2013200\u00b0C | Cup Type: Bitumen Cup | Heating Control: Thermostatic | Usage/Application: Laboratory | Power Supply: 230 V AC | Power Source: Electric | Brand: Shreeji | Bath Material: Stainless Steel | Stirrer Type: Manual Stirrer | Standard: ASTM D2170 | Display Type: Digital",
    "image": "https://4.imimg.com/data4/PO/PP/MY-2095577/tar-viscometer-standard-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Hot Plate Rectangular",
    "price": 5500.0,
    "description": "Application: Hot Plate | Power Source: Electricity | Shape: Square | Automation Grade: Automatic | Portable: Yes | Material: Stainless Steel -- Our clients can avail from us Hot Plates (Rectangular) that are procured from the reliable vendors of the market. These are made of thick PCRC sheet-power coated or stainless steel (S.S.304). In our products, top plates are made of heavy C.I. or stainless steel and are fixed with the body in such a way that the body gets minimum heat, while the top plate is fully heated. Apart from this, our products are available with pilot indicating lamps, power plug and cord wire to work on 220/230 AC, Single phase. These products are also available with three heat control switch and energy regulator.",
    "image": "https://5.imimg.com/data5/BX/TP/MY-2095577/hot-plate-rectangular-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Auto Level Survey",
    "price": 19500.0,
    "description": "Magnification: 32X | Service Type: Building Level Survey | Accuracy per km: 1.5 mm | Project Type: Building Project | Compensator range: \u00b115 arc min | Objective aperture: 32 mm | Shortest focus: 1.5 m | Deliverables: Level Book | Dust water rating: IP54 | Instrument Used: Automatic Level | Usage/Application: Industrial -- Quartz plate with round size diameter 10mm-500mm Quartz plate with square size L: 100mm-500mm W: 10mm-500mm",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/3/396524789/EX/AF/ZX/2095577/surveying-levels-ahmedabad-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Leica Automatic Level",
    "price": 28500.0,
    "description": "Magnification: 32X | Accuracy per km: 1.5 mm | Compensator range: \u00b115 arc min | Objective aperture: 32 mm | Shortest focus: 1.5 m | Dust water rating: IP55 | Weight: 1.6 Kg | Accuracy: Per km double run 2.5 mm | Product Code: NA720 | Setting accuracy: Less than 0.5\" | Compensator Working range: +/-15' | Impact standard: ISO 9022-33-5 | Operation Temperature: -20 to 50C | Storage Temperature: -40 to 70C | Dimensions: 19x12x12 cm",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455072657/SZ/PA/WY/2095577/leica-automatic-level-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Sokkia B40a Automatic Level Instrument",
    "price": 20500.0,
    "description": "Usage/Application: Leveling | Color: Blue | Model Name/Number: B40A | Compensator Range: 250 | Bubble: 1 | Brand: SOKKIA -- We bring forth this Sokkia Automatic Level (B40), which is largely admired by the clients for excellent quality and reliable performance even in the harsh environment. Designed with the help of technologically advanced machines and tools, this Sokkia Automatic Level ensures world- class quality and durable working life. The raw materials and components used in the production process of Sokkia Automatic Level (B40), are sourced from reliable vendors in the markets. Thus, this Sokkia Automatic Level (B40) is better than any other automatic level available in the markets. Specifications:",
    "image": "https://5.imimg.com/data5/HW/AH/MY-2095577/sokkia-automatic-levels-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "South Auto Level in Gandhinagar",
    "price": 12500.0,
    "description": "Usage/Application: Leveling | Packaging Type: Box | Color: Yellow | Model Name/Number: DSC-24 | Minimum Focus: 32 | Material: ABS | Brand: SOUTH -- Superb sealed structure Friction-braked rotation and endless horizontal drive",
    "image": "https://3.imimg.com/data3/PD/WU/MY-2095577/south-auto-level-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Bosch GLM 150 Professional Laser Distance Meter",
    "price": 21500.0,
    "description": "Measuring Range: 0.05 to 120 m | Model: GLM150 | Accuracy: \u00b11.5 mm | Laser Class: Class 2 | Functions: Volume, Pythagoras, Area | Bluetooth: Yes | Range: 150 m | Warranty: 1 YEAR | Power Source: AAA Battery | Brand: Bosch -- With enriched industrial experience and knowledge, we are providing an excellent range of Bosch GLM-150 Professional Laser Distance Meters to our clients. Designed with utmost precision, the offered products are manufactured using optimum quality material and advanced technology by our reliable vendors. Our offered meters are highly acknowledged for excellent design and performance. In addition to this, our products undergo various tests under the supervision of quality controllers, in order to ensure its trouble free performance. Clients can avail these products from us, at market leading rates. Reliability",
    "image": "https://5.imimg.com/data5/SELLER/Default/2026/1/576436815/ZE/CP/PZ/2095577/bosch-glm-150-professional-laser-distance-meter-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Bosch Optical Level",
    "price": 17500.0,
    "description": "Magnification: 32X | Accuracy per km: 1.5 mm | Compensator range: \u00b115 arc min | Objective aperture: 32 mm | Shortest focus: 1.5 m | Dust water rating: IP55 | Brand: Bosch | Includes tripod: Yes | Includes staff: Yes | Staff length: 4 m | Accuracy: Up to +/-1/16-in @ 100-ft | Length: 3.25\" | Leveling Type: Magnetically Dampened Compensator | Range: Up to 330-ft | Use: Optical Level -- Self Leveling Compensator with Transport Lock which protects pendulum in carrying case against damage and loss of calibration Both Horizontal and Vertical Cross-hairs and Stadia Lines - Measures level, alignment & estimates distance",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453503685/VY/NN/EX/2095577/bosch-optical-level-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "GLM100C Professional Laser Distance Meter",
    "price": 19500.0,
    "description": "Model: GLM 100C | Accuracy: 0.1 | Measuring Range: 100 Meters | Usage/Application: Distance Measurement | Range: 100 METER | Warranty: 6 months | Battery: aa+ | Brand: Bosch",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453479902/RV/RW/WX/2095577/glm100c-professional-laser-distance-meter-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Automatic Level Instrument",
    "price": 20500.0,
    "description": "Instrument type: Automatic level | Magnification: 32X | Accuracy per km: 1.5 mm | Objective aperture: 36 mm | Type: Digital | Shortest focus: 1.5 m | Tripod material: Aluminium | Usage/Application: Industrial Tanks and Vessels | Material: Stainless Steel | Application: Survey | Country of Origin: Made in India -- Quartz plate with round size diameter 10mm-500mm Quartz plate with square size L: 100mm-500mm W: 10mm-500mm",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/3/396524979/AQ/IR/YF/2095577/engineering-leveling-instruments-ahmedabad-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "GRL 300 HV Set Professional Rotation Laser",
    "price": 74500.0,
    "description": "Application: Construction Leveling | Working Range: 500 m | Self Leveling: Horizontal | Model Name/Number: GRL300HV | Laser Type: YES | Accuracy: \u00b11 mm/10 m | Rotation Speed: 300 rpm | Battery Type: LITHIUM | Automatic Self Levelling Laser: YES | Remote Control: No | Brand: Bosch | Included Accessories: Tripod",
    "image": "https://5.imimg.com/data5/SELLER/Default/2026/4/598841393/JF/GD/WX/2095577/grl-300-hv-set-professional-rotation-laser-500x500.jpeg",
    "hsn_code": "9031"
  },
  {
    "name": "Bosch GLL 3-80 CG Professional",
    "price": 37500.0,
    "description": "Brand: Bosch | Warranty: 1 year",
    "image": "https://5.imimg.com/data5/AW/BA/LS/SELLER-2095577/bosch-gll-3-80-cg-professional-500x500.png",
    "hsn_code": "9031"
  },
  {
    "name": "Bosch Glm 250 VF Laser Distance Meter",
    "price": 29500.0,
    "description": "Range: 250 m | Model: GLM 250 | Material: ABS | Brand: Bosch | Display Type: Digital -- Bosch GLM 250 VF Laser Range Finder / Distance Measurer 250m Range Metric & Imperial Measuring",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453799847/ST/VO/BF/2095577/bosch-glm-250-vf-laser-distance-meter-500x500.png",
    "hsn_code": "9031"
  },
  {
    "name": "Horizontal Dumpy Levels",
    "price": 6000.0,
    "description": "Magnification: 24X | Accuracy per km: 1.5 mm | Telescope length: 400 mm | Shortest focus: 1.5 m | Tripod material: Aluminium | Includes tripod: Yes | Includes staff: Yes | Weight: 1.7kgs (3.7lb) | Objective aperture: 32MM(13IN) | Elescope magnification: 24X | Minimum focus: 0.3m (1.0ft. ) | Standard deviation: 2.0mm (0.08in. ) for 1km double-run leveling(Without micrometer) | Compensator Type: 4 wire pendulum compensator with magnetic damping system | Compensator Working range: +/-15' | Water protection: IPX4 (IEC 60529) | Horizontal circle graduation: 1 degree (1gon) -- Our organization has gained recognition as a flourishing organization for providing Horizontal Dumpy Levels. The offered dumpy level is a compact and sturdy instrument delivers precise, erect image magnification 20X in all working conditions. We offer this dumpy level on number of specifications as per the variegated needs of customers. Our offered dumpy level is packed with quality material to enhance its life. Lightweight",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455079264/UQ/LS/PW/2095577/horizontal-dumpy-levels-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Dumpy Level Surveying Instruments",
    "price": 8500.0,
    "description": "Telescope Model: DL-9 | Image Erect Magnification: 24 | Field of View: 1 Degree 15' | Resolution: 0.01 cm at 100 mt | Short Focus: 2.5 mm | Sensitivity: 45 Degree /2 mm | Objective Aperture: 40 mm -- Light weight Easy installation",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453186537/YD/QO/KC/2095577/dumpy-level-instrument-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Constant Temperature Bath",
    "price": 58000.0,
    "description": "Material: Aluminium Alloy | Power Source: Electric | Frequency: 220 V | Packaging Type: Box | Country of Origin: Made in India -- Backed by a team of highly skilled and talented professionals, we are engaged in providing a wide range of Constant Temperature Bath. Our offered temperature baths are manufactured utilizing finest quality material and advanced technology following the standards of industry. The temperature baths offered by us are used to maintain in water the Marshal specimens to be tested. We are giving these temperature baths from us on diverse specifications.Features: High performance",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452902753/MC/IS/PA/2095577/constant-temperature-bath-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Concrete Road And Floor Cutting Machine",
    "price": 72000.0,
    "description": "Automation Grade: Semi Automatic | Max Depth Cut: 14\" | Model Name/Number: SHREEJI | Usage/Application: CONCRET RCC ROAD CUTTING | Weight: 150KG | Material: STEEL | Heating System: ELECTRICAL | Country of Origin: Made in India -- Quartz plate with round size diameter 10mm-500mm Quartz plate with square size L: 100mm-500mm W: 10mm-500mm",
    "image": "https://5.imimg.com/data5/SELLER/Default/2022/11/EG/RW/FY/2095577/rcc-groove-cutter-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Bosch GLM 500 50m Laser Distance Meter",
    "price": 5800.0,
    "description": "Measuring Range: 50 Meters | Usage/Application: Industrial | Brand: Bosch | Model: GLM 500 | Frequency: 50Hz -- The GLM 500 Professional measures distances up to 50 meters with an accuracy of \u00b11.5 millimeters. It continues the successful GLM series and enhances it with new functions. Measuring Units m, in, ft",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452906005/XQ/QY/KV/2095577/bosch-glm-500-50m-laser-distance-meter-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Digital Temperature And Humidity Meter",
    "price": 850.0,
    "description": "Data Logging: Yes | Display Type: Digital | Mounting Type: Portable | Brand: Fluke | Temperature Range: 0-100 \u00b0C | Humidity Accuracy: \u00b11 % RH | Resolution for the Humidity Reading: 0.01 %RH | Body Material: ABS Plastic | Model Number: HTC-1 -- High efficiency Hassle free performance",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453184764/GB/GU/XB/2095577/humidity-meter-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Steel Bar Bending Machine 40mm",
    "price": 99500.0,
    "description": "Application: Rebar | Max Bending Capacity: 32 mm | Max Work Size: Up To 32 mm | Operation Type: Hydraulic | Suitable Steel Grade: MS | Phase Type: Three Phase | Max Bending Radius: 0-50 mm | Power Source: Hydraulic Pressure | Body Material: Cast Iron | Control Interface: Push Button | Brand: SHREEJI | Automation Grade: Automatic | Operating System: Semi Automatic -- Shreeji instruments are leding suppleyer is Bar Bending Machine is used for bending of reinforcement steel and bars of various forms. A semi-automatic machine, it increases the production capacity of the steel yard, minimizing the use of manual labour. Further, ensuring durability and reliable performance, this cost effective machine is widely appreciated for its quality. Specification:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2022/9/EY/FT/NW/2095577/steel-bar-bending-machine-500x500.jpeg",
    "hsn_code": "9031"
  },
  {
    "name": "Bar Cutting Machine",
    "price": 110000.0,
    "description": "Voltage: 415 V | Power: 5 HP | Phase: Three Phase | Country of Origin: Made in India | Blade: Heavy duty alloy steel 3 x 3 x 1\" Blade | Cut Capacity: Upto 32 mm",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/457437751/BG/ZE/FD/2095577/bar-cutting-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Steel Bar Cutting Machine",
    "price": 110000.0,
    "description": "Capacity: 32 mm to 40 mm | Power: 3ph | Automatic Grade: Semi-Automatic | Brand: SHREEJI -- Power rating: 5HP 3 Phase Electric Motor Cut Capacity: Upto 32 mm",
    "image": "https://4.imimg.com/data4/IG/RI/MY-2095577/bar-cutting-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Electric Needle  Vibrator",
    "price": 10500.0,
    "description": "Length: 3 m | Automation Grade: Semi-Automatic | Material: Iron, Stainless Steel | Is It Portable: Portable | Application: Industry | Power: 2hp / 3hp | Weight: 25 Kg | Voltage: 380V",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455080876/KZ/LM/UN/2095577/electric-needle-vibrator-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Concrete Vibrator Needle",
    "price": 12500.0,
    "description": "Weight: 8 kg | Brand/Make: Shreeji | Is It Portable: Portable | Speed: 3000 RPM | Country of Origin: Made in India -- Standard Lengths: 4,5,6 Weight(Kg): 18",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453187652/LZ/NE/ZZ/2095577/graves-needle-vibrators-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Needle Vibratory Motor",
    "price": 15000.0,
    "description": "Brand/Make: Shreeji | Phase: Three Phase | Automation Grade: Semi-Automatic | Packaging Type: Box | Is It Portable: Portable -- The vibrator is mounted on a spring loaded chair based acting as drip tray and 360\u00b0 turn table with craving handle for easy portability. Auto air cooled needle",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453186166/VA/CA/DK/2095577/needle-vibratory-motor-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "sun Automatic Levels",
    "price": 14500.0,
    "description": "Magnification: 32X | Accuracy per km: 1.5 mm | Compensator range: \u00b115 arc min | Objective aperture: 32 mm | Shortest focus: 1.5 m | Dust water rating: IP55 | Usage/Application: Industrial | Staff length: 4 m -- Quartz plate with round size diameter 10mm-500mm Quartz plate with square size L: 100mm-500mm W: 10mm-500mm",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/3/396525158/CA/BB/LL/2095577/automatic-levels-ahmedabad-500x500.jpeg",
    "hsn_code": "9031"
  },
  {
    "name": "Bruton Compass equipment",
    "price": 3850.0,
    "description": "Measurement Type: Strike Dip | Housing Material: Aluminum | Clinometer: Built In | Material: Brass, Acrylic | Scale Graduation: 0.5 Degree | Liquid Filled: Yes | Usage: Geological | Illumination: Luminous Marking | Carrying Case: Leather Case | Country Of Origin: Imported | Width: 57mm | Length: 67 mm | Height: 57 mm | Capsule size: 45mm | Use: Industrial -- Fast needle Ultra Stable needle,needle settling time:very fast(0.5-1sec)",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453188474/JP/LS/PS/2095577/bruton-compass-equipment-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Dumpy Level Theodolite",
    "price": 8000.0,
    "description": "Accuracy: +- 2-3 mm / km | Telescope model: 9 | Length: 288 mm | Magnificaton: 28x | Objective aperture: 38mm | Object Glass Diameter: 40mm | Short focus: 1. 5m | Stadia ratio: 024000 AM | Circle diameter: 95 mm | Graduation: 10 minutes | Plate level sensitivity: 40\" / 2mm",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451657109/UM/XG/VU/2095577/dumpy-level-theodolite-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Transit Vernier Theodolite",
    "price": 15500.0,
    "description": "Resolving Power: 4\" | Field Of View: 2.6 M at 100M | Material: Aluminum | Short Focus: 1.5M | Stadia Ratio: 1100 | Length: 178 mm | Magnificaton: 25X | Effective Aperture: 38mm | Circle Diameter: Hz113mm, V100 mm -- We are engaged in providing our clients with Transit Verniers Theodolites that are procured from the reliable manufacturers of the market. All our products are widely used in various industries and sectors for diverse applications. Apart from this, our products are available in various sizes & designs at most competitive prices to fulfill their exact requirements and demands. Magnificaton",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451656989/LZ/QE/SI/2095577/transit-vernier-theodolite-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Survey Measuring Chain",
    "price": 3250.0,
    "description": "Usage/Application: Industrial | Brand: SHREEJI | Measuring Range: 30 Meters | Length: 2-3 Feet | Material: MS | Surface Finished: Polished -- Measuring chain resistance, to ensure that the range of 2.5 \u00b1 20% \u03a9 Measuring distance space of the SELV two parts, denoted as X,unit (/ cm)",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455072395/YA/OC/PP/2095577/survey-measuring-chain-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Brass Sextant With Box",
    "price": 3250.0,
    "description": "Packaging Type: Box | Size: 3\" x 3\" x 1.75\" | Material: SS | Finish: Antique -- Export quality Reasonable price",
    "image": "https://5.imimg.com/data5/SELLER/Default/2026/1/576436684/LX/RH/CD/2095577/brass-sextant-with-box-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Precision Measuring Instrument",
    "price": 5000.0,
    "description": "Model: HZ-2008 UV | UV Temperature: 50 Degree C - 75 Degree C | Testing Capacity: 48 piece(75 x 150m m ),50 piece( 75 x 150m m ) | Dimension: 137 x 53 x 136cm (W x D x H) | Weight: 136kg",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453484997/XB/MG/TR/2095577/precision-measuring-instrument-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Aluminum Tripod Stand",
    "price": 3000.0,
    "description": "Tripod Type: Aluminium Tripod | Max Height: 1.8 m | Head Type: Flat Head | Clamp Type: Quick Clamp | Mount Thread: 5/8 in | Material: Aluminium | Model: AT-1 | Tripod: Head Flat | Head Size: 150 (mm) | Head Bore: 60 (mm) | Extended Length: 1675 (mm) | Closed Length: 975 mm | Head Screw: 5/8 X11 or M16 | Weight: 3.65 (Approx Kg.) | Package: 6 (Pcs. per Carton) -- We are engaged in offering our clients with Stands (Tripod) that are procured from the reliable vendors of the market. Conform to international quality norms, our products are available in various sizes, shapes and designs at market leading prices. In addition to this, all our products are thoroughly checked by expert quality controllers to ensure flawlessness. Tripod Head",
    "image": "https://3.imimg.com/data3/SO/IC/MY-2095577/tripod-stand-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Rodometer",
    "price": 3650.0,
    "description": "Wheel Diameter: 318 mm (12 in) | Type: MANUAL | Measuring Range: 10000 METER | Display Type: Analog Counter | Wheel Material: Aluminium | Usage/Application: Distance Measurement | Application: Industrial | Foldable Handle: Yes | Brand: Shree Ji | Model Name/Number: GMT Q70 | Range: 100 meter | Laser Type: MANUAL | Warranty: 6 MONTH | Weight: 2.8KG | Available: 0.5 meter & 1 meter circumference wheel | Material: Aluminium | Accuracy: 0.1 meter | Measures length of land: Cable & road in meters up to 9,999.9 meters",
    "image": "https://4.imimg.com/data4/MD/OG/MY-2095577/rodometer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Micro Optic Theodolites",
    "price": 15550.0,
    "description": "Magnification: 24X | Usage: Levels angle, Alignment of lines | Usage/Application: Surveying, Construction, Meteorology, Mining | Material: Stainless Steel, SS | Surface Treatment: Color Coated -- Owing to the extensive industry experience, we are capable of providing our customersGeneral Micro Optic Theodolites. Our products are procured from the trustworthy vendors of the market, who make these as per international quality norms & standards. Further, our products are available in various sizes, shapes & designs at industry leading prices. Product detail:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451656899/SQ/UE/SK/2095577/micro-optic-theodolites-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Telescopic Alidade Sight",
    "price": 3500.0,
    "description": "Material: Brass | Height: 5-6 inch | Country of Origin: Made in India | Length: 8-12 inch | Surface Finish: Polished -- Reliable and efficient Robust construction",
    "image": "https://5.imimg.com/data5/SELLER/Default/2026/1/576437713/UT/IB/EM/2095577/telescopic-alidade-sight-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Abney Hand Level",
    "price": 2950.0,
    "description": "Color: Yellow | Usage/Application: Industrial | Circle diameter: 60MM | Vernier graduation: 10' | Frame: Metal frame",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453480529/PN/KK/DE/2095577/abney-hand-level-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Steel Ranging Rod 3mtrs",
    "price": 1250.0,
    "description": "Weight: 0.75 Approx K.g | Model: R- 1 | Length Per Sec: 1 Meter | Extended Length: 2 (Mtr) | Nos of Sec: 2 | Diamter of the Sec: 25 (mm) | Thick of the Sec: 1.5 (mm) | Package: 10 (Pcs. per Carton) -- We are instrumental in offering our clients with Ranging Rods that are known for their dimensional accuracy, wear & tear resistance and longer functional life. All our rods are available in various sizes, shapes and designs at affordable prices to meet customers requirements. Moreover, all our products are packed using premium packaging material to ensure complete safety during transportation. Length Per Sec. (Mtr)",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453189640/SA/SR/MO/2095577/ranging-rods-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Steel Ranging Rods",
    "price": 750.0,
    "description": "Specific gravity: 2.6~2.8g/cm3 | Bend strength: Greater than300kg | Hardness: Greater than 9 MOHS | Tensile strength: Greater than 150kg/cm3 | Porosity rate: Less than 30%",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453185339/HJ/ZI/DQ/2095577/steel-ranging-rods-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Tangent Clinometer",
    "price": 3500.0,
    "description": "Usage/Application: Survey | Packaging Type: Box | AC Voltage: 250V | DC Voltage: 24 V | Frequency: 50/60Hz -- Excellent functionality Sturdy construction",
    "image": "https://5.imimg.com/data5/SELLER/Default/2026/1/576436905/VN/PO/LX/2095577/tangent-clinometer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Underground Metal Detector",
    "price": 75000.0,
    "description": "Range: 4 meter | Max Detection Depth: 2 To 3 Meters | Disk Water Resistant Probe: 8.2-Inch | Operating Current: 65mA, Max - 150mA | Signal Frequency: 7.5KHZ (+/- 1KHZ) | Battery: 6 x 1.5V AA Alkaline Batteries | Main Product Weight: 1050g -- Detection Alarm Metal Type Recognition: All Metals",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/456869538/IT/NG/VN/2095577/underground-metal-detector-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Barricade Caution Tape",
    "price": 300.0,
    "description": "Tape Type: Barricading Tape | Usage/Application: Warning | Color: Red | Roll: 1 pack of 4 rolls. | Weight: 0.8 Kg -- High durability Optimum quality",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453789161/EO/XK/JG/2095577/barricade-caution-tape-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Shreeji Green Polyester Safety Jackets",
    "price": 75.0,
    "description": "Color: Green | Material: Nylon | Size: Free Size | Gender: Unisex | Sleeves Type: Half Sleeves | Usage/Application: Industrial Use | Country of Origin: Made in India",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453499905/FS/NE/WJ/2095577/shreeji-green-polyester-safety-jackets-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Leather Hand Gloves Red",
    "price": 95.0,
    "description": "Color: Red | Usage/Application: Industrial | Categories: Safety Gloves | Size: Medium | Material: Leather | Brand: acme | Type: Full Finger | Gender: Unisex",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453788803/DM/VZ/LO/2095577/leather-hand-gloves-red-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Yellow and Orange  Polyester Safety Vests",
    "price": 95.0,
    "description": "Material: Nylon | Usage/Application: Traffic Control | Sleeves Type: Without Sleeves | Pattern: Net | Color: Green | Size: Medium",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453486179/NR/NX/XR/2095577/yellow-and-orange-polyester-safety-vests-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Cotton Safety Glove",
    "price": 20.0,
    "description": "Material: Cotton | Color: White | Glove Material: Cotton canvas cloth | Size: Medium | Pattern: Knitted | Gender: Unisex",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453788559/AX/HU/FX/2095577/cotton-safety-glove-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Royal Pro Safety Shoes",
    "price": 1569.0,
    "description": "Usage/Application: Construction | Brand: ROYAL | Gender: Male | Occasion: industrial | Color: Black | Size: 9 | Certification: ISI | Material: Synthetic Leather | Length: Low ankle | Country of Origin: Made in India -- Design: Design S1Standard-ISI-15298 H, EN-I5O 20345-2011 Upper: Barton Grain Breathable Leather",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451602644/TF/BG/ZM/2095577/royal-pro-safety-shoes-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "PVC Traffic Cones",
    "price": 300.0,
    "description": "Color: Red | Material: PVC | Model: Lz-203 | Height: 700 mm | Base Size: 420x420 mm | Weight: 2.3 kg -- Reliability Light in weight",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451607738/OU/UR/NX/2095577/pvc-traffic-cones-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Orange Net Safety Jackets",
    "price": 65.0,
    "description": "Size: Free Size | Color: Orange | Material: Nylon | Usage/Application: Construction | Brand: acme | Pattern: Net | Sleeves Type: Without Sleeves",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453486381/OM/GZ/CH/2095577/orange-net-safety-jackets-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Safety Equipment Goggles",
    "price": 110.0,
    "description": "Material: HDPE, Glass, Plastic | Brand: Shreeji | Packaging Type: Box | Country of Origin: Made in India | Usage: Laboratory -- Easy to use Longer life",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452904774/YT/HI/PH/2095577/safety-equipment-goggles-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Industrial Safety Products",
    "price": 1000.0,
    "description": "Product Category: Head Protection | Type: Wheel Stoppers | Application: Construction | Brand Category: Premium | Material: Polyester | Compliance Standard: IS | Material Group: Plastic | Usage Type: Reusable | Packaging Type: Packet | Country of Origin: Made in India",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452900445/ML/MW/PG/2095577/industrial-safety-products-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Crane Lifting Belt",
    "price": 1850.0,
    "description": "Packaging Type: Packet | Material: Cotton | Color: Green | Length: 1-2 m | Country of Origin: Made in India",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452898849/CG/GW/CT/2095577/crane-lifting-belt-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Flame Retardant Safety Suits",
    "price": 850.0,
    "description": "Suit Material: Aluminized Glass Fibre | Size: M- 5XL | Material: Microporous+SMS | Weight: 50-60gsm+50-55gsm SMS | Code No: AHMC100 | Packing: 1pc/bag,50bags/ctn or customized as per your request -- High protection Longer life",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453797993/UK/ZK/VB/2095577/flame-retardant-safety-suits-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Digital Weighing Balance",
    "price": 9500.0,
    "description": "Weighing Capacity: 50-100kg | Material to be measured: Metals | Balance Type: Digital | Automation Grade: Automatic | Usage/Application: Laboratory -- High Precision Load Cell. Large LCD or LED Display.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452915868/QT/KM/DT/2095577/weighing-balance-600-gm-3-kg-20-kg-100kg-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Tamping Rammer Supplier",
    "price": 51000.0,
    "description": "Capacity: 2 ton | Power Source Type: Electric | Machine Type: Vibratory | Brand: Honda | Engine Power: 5 hp | Weight: 80 kg | Travel Speed: 10 to 13 m/min | Impacting Force: 10 kn | Voltage: 240 v | Material: Cast Iron | Bounce Height: 40 - 75 MM | Advanced Speed: 10 - 13 m/min | Ramming Frequency: 600 - 700 time/min | Net Torque: 7.6 IB-ft (10.3 Nm) @ 2500 rpm | Motor Power: 4 kW | Engine Type: G X 160 Honda Air Cooled 4 stroke OHV | Gross Weight: 90 Kg -- Require less maintenance Long service life",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452901805/DW/WX/BM/2095577/tamping-rammer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Vibrating Rammer Electrical",
    "price": 42000.0,
    "description": "Engine Power: 3 Hp | Brand/Make: shreeji | Automation Grade: Semi-Automatic | Usage/Application: Construction | Voltage: 415 V | Ramming Frequency: 600 - 700 time/min | Motor Power: 3 kW | Gross Weight: 90 Kg | Rotation Speed of Motor: 2800 r/min -- VIBRATING Product Details: Less power consumption",
    "image": "https://5.imimg.com/data5/YQ/CQ/MY-2095577/vibrating-impact-rammer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Jumping Compactor",
    "price": 50000.0,
    "description": "Power Source Type: Petrol | Brand: Honda | Capacity: 3 ton | Power: 4.0 kW | Weight: 90 kg | Advance Speed: 10 to 13 m/min | Brand/Make: shreeji | Engine Type: Petrol Engine | Impact Force: 18 kN | Fuel Tank Capacity: 3.0 L | Bounce Height: 40 - 75 MM | Advanced Speed: 10 - 13 m/min | Ramming Frequency: 600 - 700 time/min | Gross Weight: 90 Kg -- Rugged design Smooth functioning",
    "image": "https://5.imimg.com/data5/IA/TJ/MY-2095577/jumping-compactor-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Brass Frame Testing Sieve 200mm",
    "price": 750.0,
    "description": "Diameter: 8 inch | Country of Origin: Made in India | Brand: Shreeji | Color: Gold | Packaging: Box -- We are offering a quality range Test Sieves, which are widely appreciated for high consistency, fit and stable functions. These are sourced from reliable vendors of the industry and are offered in customized solutions to meet client's requirements and specifications. Our range of Test Sieves are manufactured for various Household and Industrial usages. B.S.S(410/1969)Mesh Nos",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451599816/YV/CG/SC/2095577/brass-frame-testing-sieve-200mm-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Laboratory Electric Stirrer",
    "price": 7500.0,
    "description": "Stirrer Type: Magnetic | Max Stirring Volume: 2 L | Speed Range: 50-1500 rpm | Display Type: Analog | Speed Control: Stepless | Capacity: 6-8 ltrs | Speed: 4000 RPM | Motor Power: 1/20 HP | Type: Light Duty Stirrer | Shaft: S.S. shaft with chuck & mount on electronic speed regulator | Use: Industrial",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453188910/RP/YD/IK/2095577/laboratory-stirrers-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Samsonic Digital Infrared Thermometer",
    "price": 3000.0,
    "description": "Accuracy: +/-2C/ +/-2% | Distance Spot Ratio: 121 | Emissivity: 0.95 (Pre- Set) | Resolution: 0.1 C or 0.1 F | Response Time: 500ms | Measurement range: -32C ~ 380 C (-26 F ~ 716F) | Wavelength: 5 - 14 um | Battery Power: DC 9V battery operation",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455081103/EH/SI/EW/2095577/samsonic-digital-infrared-thermometer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Specific Gravity Hydrometer",
    "price": 1200.0,
    "description": "I Deal In: New Only | Measurement Range: 0~40 - 0~30 (kg/m 3) | Country of Origin: Made in India | Division Value: 0.1 1.0 (kg/m 3) | Full Length: 200 - 250 mm | Standard Temperature: 15 Degree - 20 Degree -- Density hydrometers Brix hydrometers with thermometer",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453484818/PY/HA/AC/2095577/specific-gravity-hydrometer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Length Comparator With Gauge",
    "price": 18500.0,
    "description": "Usage/Application: Measurement | Material: MS,SS | Brand: SHREEJI | Surface Treatment: Color Coated | Display Type: Analog -- Robust construction High strength",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451665966/QO/UC/CA/2095577/length-comparator-with-gauge-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "B.O.D Incubator (Biological Oxygen Demand)",
    "price": 78000.0,
    "description": "Temperature Range: 5 Degree to 60 Degree | Voltage: 230 Volt | Accuracy: 0.2 C | Frequency (Hertz): 50 Hz | Resolution: 0.1 C | Standard Model: Interior Cabinet all S.S. LM 304 grade. Exterior Cabinet powder coated | Uniformity: 0.1C | Display Control: Digital Temperature Control | Refrigeration: CFC free compressors utilizing R134a eco-friendly refrigerant. | Observation: Inner transparent glass/acrylic door and outer door key lockable.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455071617/BE/SP/KM/2095577/b-o-d-incubator-biological-oxygen-demand-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Frp Discharge Rod, 11 Kv To 400 Kv, Size: 18 Ft",
    "price": 3000.0,
    "description": "Material: FRP | Discharge Range: 11KV to 400KV | Color: Off White & Yellow | Diameter: 40 MM & 45 MM | Size: 18feet | Voltage: 3300 kv",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453799633/DX/VE/MW/2095577/frp-discharge-rod-11-kv-to-400-kv-size-18-ft-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Multi Stem Digital Thermometer",
    "price": 1250.0,
    "description": "Usage/Application: Laboratory | Material: Plastic | Color: White | Temperature Range: 0 to 350 | Accuracy: 0.5%, 0.1% | Warranty: 1 Year | Display Type: Digital | Packaging Type: Box -- Imported make Digital Multi Thermometer with 5\" Pointed Probe & 1 Meter Long Wire having Alarm Facility for Max./Min. Temperature Range:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453187429/LC/XF/XU/2095577/multi-stem-thermometer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Cement Autoclave Laboratory Type",
    "price": 55000.0,
    "description": "Brand: sheeji | Material: MS | Capacity: 0-500 (kg/hr) | Warranty: 1 year | Country of Origin: Made in India",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452905409/BB/HJ/TT/2095577/cement-autoclave-laboratory-type-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Measuring Cylinder",
    "price": 210.0,
    "description": "Automation Grade: Semi-Automatic | Application: Industrial | Color available: White Opaque Color | Sizes available: 5 ml to 2 Liters | Raw Material Used: 33 Borosilicate Glass | Capacity: 50ml,100ml,250ml,500ml -- Highly Accurate Available with a broad base for providing stability",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452901615/GW/ET/AU/2095577/measuring-cylinder-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Vertical Laboratory Autoclave",
    "price": 55000.0,
    "description": "Automation Grade: Semi Automatic | Material: S S | Temperature Range: 0 TO 100 | Country of Origin: Made in India",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451858406/VQ/DN/LY/2095577/vertical-laboratory-autoclave-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "S S Scoops Open Type",
    "price": 650.0,
    "description": "Scoop Type: Open | Steel Grade: SS 316 | Set Contains: 1 piece | Capacity: 0.25 kg | Diameter: 50 mm | Weight: 50 g | Application: Pharmaceutical / Chemical Industry, Construction, Automobile Industry, Oil & Gas Industry | Material: SS | Color: Silver | Thickness: 4-10 mm | Handle Length: 3-4 inch -- Precise design High strength",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453483704/QV/QQ/VO/2095577/s-s-scoops-open-type-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Plummet Balance equipment",
    "price": 8500.0,
    "description": "Accuracy: (Upward/Downward laser) +/-1mm/100m | Compensator Accuracy: +/-1 | Compensator range: +/-3 Degree | Laser wave length: 635nm | Laser power: 5mw/ Emit 1mw | Laser range: (Upward/Downward) 150m | Laser spot size: Less than equal to 20mm/100m | Remote range: 40m | Rechargable battery: DC 4.8V | Operating temperature: -10 deg C-+50 deg C | Net weight: 3.8kgs -- Conventionally particle size distribution analysis is carried out using pipette and hydrometer methods. Whereas in hydrometer method it is possible to determine particle sizes in the range 75microns, the method involves computation and it is time consuming. The pipette method can be used for determining only the percentage of specific sizes less than 0.02, 0.006 and 0.002mm as a percentage of total soil sample. The plummet balance method to determine sub sieve particle size for the entire range is very rapid and only manipulation of height of the balance, so that plummet sinks to the right depth is required. The percentage of soil in suspension is directly indicated by a pointer over a graduated scale. A vertical rod is mounted on a sturdy base having leveling screws. A pointer with steel pivots turns is jewel bearing an moves over a graduated scale. Scale graduations are market 0-100% x 2% To the other end of the pointer a plummet is hanged. Rack and pinion arrangement is provided on the vertical rod for adjusting the height. Supplied with a chart showing relationship between \u201cK\u201d and temperature of suspension of soils of varying specific gravity from 2.4 to 2.8 to help in solving stroke's equation. Supplied complete with one Perspex plummet one measuring jar and one rider weight for zero adjustment and rider weight for adjusting the pointer to 100%.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451657435/RF/WJ/JX/2095577/plummet-balance-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Humidity Chamber Oven",
    "price": 58000.0,
    "description": "Number of Shelves: 3, 4 | Temperature Range: 50-250 Degree Celsius | Temp range from ambient: 5C to 60C | Accuracy: - 0.5 Degree | Humidity range: From atmospheric humidity to 95 % | Controller: (dry and wet bulb method) with 3 % RH -- Compact design High accuracy",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453189268/DJ/CP/KG/2095577/humidity-oven-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Stainless Steel Vertical Autoclave",
    "price": 48500.0,
    "description": "Usage/Application: Laboratory | Shape: Vertical | Brand: SHREEJI | Approval Certificate: CE Certificate | Material: Stainless Steel -- Application specific design Accurate measurement",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453189568/SX/EF/LE/2095577/ss-vertical-autoclave-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Cross Staff With Pole",
    "price": 950.0,
    "description": "Usage/Application: Survey | Packaging Type: Box | Material: Cast Iron | Usage: Laboratory | Surface Treatment: Color Coated -- The cross-staff consists of a long staff with a perpendicular vane which slides to and fro upon it. We mark the staffs with graduated measurements \u2013 which are precise. The angles can be then measured by holding it so that the ends of the vane are in level with the points that need to be measured. It is widely used to measure the altitude of the sun, and also different latitudes, aside from being used as a navigation instrument. We offer the cross staff at a competitive price and can modify it according to customer needs.our company shreeji instruments . Specifications:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453485156/PH/KO/MH/2095577/cross-staff-with-pole-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Round Hot Plate",
    "price": 2850.0,
    "description": "Number Of Plates: 1 Plate | Power Rating: 1000 W | Plate Type: Cast Iron Plate | Usage: Home Kitchen | Control Type: Rotary Knob | Color: White | Country Of Origin: Made In India | Material Grade: SS304 | Voltage: AC 220/230 V | Phase: Single Phase | Material: Stainless Steel | Body Material: Mild Steel | Body Shape: Rectangular -- We are engaged in providing our clients with Hot Plates (Round) that are procured from the reliable manufacturers of the market. All our products are widely used in various industries and sectors for diverse applications. In addition to this, our products are available in various sizes & designs at most competitive prices to fulfill their exact requirements and demands. Body made of thick PCRC sheet-power coated or full stainless steel (S.S.304)",
    "image": "https://4.imimg.com/data4/VQ/SO/MY-2095577/round-hot-plate-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Total Station Calibration Services",
    "price": 8500.0,
    "description": "Brand: Pentax | Usage/Application: Land Survey | Magnification: 30X | Angle Accuracy: 2\" | Made in: India -- We offer our clients Total Station Calibration Services across country at competitive prices.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452903984/GP/JV/ND/2095577/total-station-calibration-services-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Kinematic Viscosity Bath",
    "price": 100000.0,
    "description": "Material: Mild Steel | Voltage: 220-230 V | Frequency: 50-60 Hz | Display Type: Digital | Country of Origin: Made in India",
    "image": "https://5.imimg.com/data5/SELLER/Default/2021/5/HS/QU/KV/2095577/kinematic-viscosity-bath-500x500.jpeg",
    "hsn_code": "9031"
  },
  {
    "name": "Kinematic Viscosity Of Bitumen",
    "price": 85000.0,
    "description": "Material: Steel | Shape: Round | Brand: shreeji | Automation Type: Automatic | Country of Origin: Made in India",
    "image": "https://5.imimg.com/data5/YR/ZZ/RY/SELLER-2095577/kinematic-viscosity-of-bitumen-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Softening Point Apparatus",
    "price": 7200.0,
    "description": "Usage/Application: Laboratory | Material: SS, MS | Voltage: 220V | Phase: Single Phase -- Measurment temperature accuracy: \u00b101\u00baC Automatically increase of temperature per minute: 5\u00baC",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452902069/VL/EW/PF/2095577/softening-point-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Flow Cup Viscometer Ford Cup",
    "price": 2450.0,
    "description": "Cup Type: Ford | Cup Number: No. 4 | Material: Stainless Steel | Orifice Diameter: 4 mm | Stand Included: With Stand | Usage/Application: Laboratory | Standard: ASTM D1200 | Number Of Cups: 1 | Viscosity: YES | Brand: SHREEJI | Country of Origin: Made in India | Surface Treatment: Polished | Stand Material: SS | Cup Material: Brass | Usage: Laboratory -- We are engaged in providing our clients with Flow Cups that are procured from the reliable manufacturers of the market. Our products are widely used in various industries and sectors for diverse applications. Furthermore, our products are available in various sizes & designs at most competitive prices to fulfill their exact requirements and demands. Specification:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/8/447312560/UC/PZ/WR/2095577/flow-cup-viscometer-ford-cup-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Ductility Testing Apparatus",
    "price": 48500.0,
    "description": "Capacity: 500 N | Material: Stainless Steel | Automation Grade: Semi-Automatic | Phase: Single Phase | Brand: SHREEJI | Country of Origin: Made in India | Driven Type: Electric | Use: Industrial -- Excellent performance Low operating cost",
    "image": "https://4.imimg.com/data4/BO/EF/MY-2095577/ductility-testing-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Bitumen Testing Apparatus",
    "price": 21500.0,
    "description": "Phase: 1 Phase | Weight: 206 Kg | Voltage: 220 Volt | Frequency: 50Hz | Sizes: 2250 x540x1160mm | Power: 3 kw | Environment humidity: Less than 85 percent -- Sturdy construction Perfect reading",
    "image": "https://5.imimg.com/data5/QN/VC/ND/SELLER-2095577/bitumen-testing-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "spirit level and frame structure",
    "price": 7500.0,
    "description": "Length: 300 mm | Number of vials: 3 vials | Display Type: MANUAL | Accuracy: 0.5 mm per m | Magnetic base: Yes | Vial material: ALUMINUM | Type: LAB | Material: Mild Steel | Profile type: Box | Color: Black | Size: 3 meter | Body material: Aluminium | End caps: Metal",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453479725/TR/XZ/TF/2095577/chamber-board-road-leveling-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Standard Penetrometer Manual Control",
    "price": 8200.0,
    "description": "Application Type: Bitumen | Automation Grade: Automatic | Machine Type: Semi-Automatic | Color: Blue | Brand: Humboldt | Capacity: 2 kn | Material: MS, SS | Usage/Application: Laboratory | Display Type: Analog | Surface Treatment: Color Coated -- It consists of a vertical pillar mounted on a base provided with levelling screws The head, together with dial plunger rod and cone (or needle) slides on a pillar and can be clamped at any desired height",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453190620/LL/LG/DL/2095577/standard-penetrometer-manually-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Digital Benkelman Beam",
    "price": 25000.0,
    "description": "Packaging Type: Wooden Box | Display Type: Analog | Material: MS | Brand: Shreeji | Grade: Manual -- Our clients can avail from us Benkleman Beams that are widely acknowledged for their longer functional life, corrosion resistance and dimensional accuracy. Our products are widely used in various industries laboratories and research institutes. Furthermore, these are thoroughly checked by our team of expert quality controllers to ensure flawlessness. Specification:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453189944/HV/QW/UI/2095577/digital-benkelman-beam-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Benkelman Beam Deflection Test Apparatus",
    "price": 24500.0,
    "description": "Material: Mild Steel | Brand: shreeji | Usage/Application: Measurement | Dial Gauge: 0.025 MM | Power: MANUALY",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451664389/ED/NS/FA/2095577/benkelman-beam-deflection-test-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Pocket Concrete Penetrometer",
    "price": 1250.0,
    "description": "Weight: 80 g | Size: 15x180 mm | Range: 0-5 kgf/cm2 | Usage: Laboratory -- Excellent performance Superior quality",
    "image": "https://5.imimg.com/data5/SELLER/Default/2026/1/575967492/ZS/EZ/NE/2095577/pocket-concrete-penetrometer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Automatic Compactor, Automation Grade: Semi-Automatic",
    "price": 35000.0,
    "description": "Material: MS | Color: Blue | Automatic Grade: Automatic | Automation Grade: Automatic | Voltage: 220-380V | Surface Treatment: Color Coated -- Hammer weight: 4.536Kg(STMJ-1A), 10.21kg(STMJ-2D) Height of fall:457.2 mm",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453186674/TI/ZJ/OX/2095577/automatic-compactor-automation-grade-semi-automatic-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Universal Automatic Compaction Apparatus",
    "price": 58000.0,
    "description": "Cylinder Volume: 15 L | Hopper Shape: Single Hopper | Frame Material: Mild Steel | Surface Finish: Painted | Cylinder Diameter: 150 mm | Standard: IS 1199 | Material: MS | Automation Grade: Automatic | Usage Area: Lab | Frequency: 50Hz | Accessories: Trowel | Voltage: 220-280V -- Power: 700 W Ignition Way: Automatic Digital Ignition",
    "image": "https://4.imimg.com/data4/XA/YY/MY-2095577/universal-automatic-compaction-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Constant Temperature Water Bath",
    "price": 45000.0,
    "description": "Shape: Rectangular | Display Type: Digital | Automation Type: Automatic | Circulation Type: Circulating | Window Type: Glass | Brand: Marshal | Power Supply: AC220V+/-10%,50Hz+/-5% | Temperature Range: Ambient to 100 C | Heating power: 1700W | Stirring motor: Power 6W, rate 1200r/min | Temperature control precision: +/-0.1C | Constant temperature bath: 20L, double shell | Working environment: Ambient -10C~+35C,RH Less than 85% | Temperature sensor: Pt100, RTD | Maximum power consumption: 1800W | Overall dimension: 530mmx400mmx670mm(Bath is included)",
    "image": "https://2.imimg.com/data2/DF/UE/MY-2095577/constant-temeperature-water-bath-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Abel Flash Point Apparatus",
    "price": 8000.0,
    "description": "Dimension: 397*305*240 mm | Resolution: 0.1C | Test range: Room temperature-350C | Printer: High speed thermal printer,paper size55mm | Temperature Detection: Imported platinum resistance (Pt100) | Ignition mode: Automatically electronic ignition with airs source | Self checking: Automatic fault checking | Power consumption: Less than 550 w | Temperature: Atmospheric temperature 10C-45C,humidity 30-80% | Power: AC220V+/-10% | Weight: About 15 kg -- Flash & Fire Point Apparatus used for determining the flash point of fuel oils and lubricating oil, bitumen other than cutback bitumen and suspension of solids in liquids, having a flash point above 49\u00b0C. The apparatus consists of brass test cup with handle removable cup cover with the spring operated rotated shutter having oil test jet flame device, stirrer with flexible shaft. The assembly rests in air bath which is covered with dome shape metal top. The cup assembly is positioned in a cast iron air bath, fitted with a chrome plated brass top having electric heater with temperature controller. Suitable for operation on 220V, 1Ph, 50Hz AC Supply.",
    "image": "https://4.imimg.com/data4/RD/FI/MY-2095577/flash-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Flocculator Jar Testing Apparatus",
    "price": 18500.0,
    "description": "Number Of Jars: 4 Jars | Usage/Application: Laboratory | Brand: SHREEJI | Material: MS | Weight: 15KG | Size: 3X3 | Automation Grade: Semi-Automatic | Country of Origin: Made in India | Frequency: 50Hz",
    "image": "https://5.imimg.com/data5/BM/IH/MY-2095577/jar-test-apparatus-500x500.png",
    "hsn_code": "9031"
  },
  {
    "name": "Le Chatelier Moulds",
    "price": 1950.0,
    "description": "Material: Brass, Stainless Steel | Packaging Type: Box | Brand: SHREEJI | Box Color: Red -- Le Chatelier Moulds The soundness of cements and limes is determined using the expansion test with Le Chatelier moulds according to the relevant standard. The mould consists of a spring tensioned split cylinder 30 mm internal diameter x 30 mm high with two indicator stems, which measure 165 mm from the points to the center line of the cylinder and O-ring.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452901962/MK/HX/QW/2095577/le-chatelier-moulds-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Laboratory Weighing Scale",
    "price": 10500.0,
    "description": "Capacity: 1-5 L | Usage/Application: Laboratory | Type Of Weighing Scale: Digital | Country of Origin: Made in India | Plate Material: SS -- Excellent performance Auto shut off (option)",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452899564/JA/OY/CF/2095577/laboratory-weighing-scale-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Blain Air Permeability Apparatus",
    "price": 8500.0,
    "description": "Thickness: 1.1 mm | Diameter: 12.7 mm | Height: 15mm | Hole Diameter: 1.0 mm | Net Weight: 6 kg -- Accurate detection Used for determining the fineness of cement",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453502547/VS/UA/WI/2095577/blain-air-permeability-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Planetary Mixer Mortar Mixer",
    "price": 75000.0,
    "description": "Material: Mild Steel | Phase: Single | Speed: 140 + 5 R.P.M. and 285 + 10 R.P.M. | Voltage: 230 volts | Frequency: 50 Hz | Cycle Life: 50 cycles -- Backed by our efficient workforce, we are capable of providing our customers with Planetary Mixer- Mortar Mixers. These products are available at most competitive prices and in various designs and shapes. Moreover, all our products are widely used for mixing cement pastes, mortars and pozzolanas.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455081982/XP/DM/CY/2095577/planetary-mixer-mortar-mixer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Loss On Heating Oven",
    "price": 55000.0,
    "description": "Oven Type: Cabinet | Power Source: electrical | Max Temperature: 250\u00b0C | Temperature: 200-300 deg. Celsius | Internal Volume: 2 m\u00b3 | Air Circulation: Natural Convection | Application: Drying | Brand: shreeji | Capacity: 100-200 | Material: Stainless Steel | Air-Flow Direction: Vertical Down Airflow | Door Type: Single Door | Automation Grade: Semi-Automatic | Display Type: Digital | Voltage: signal ph | Warranty: 1 year | Country of Origin: Made in India",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453799430/JK/EO/IH/2095577/loss-on-heating-oven-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Laboratory Hot Air Oven",
    "price": 14500.0,
    "description": "Capacity: 90 L | Max Temperature: 200 \u00b0C | Chamber Type: Single Wall | Temperature Control: Digital | No. of Shelves: 2 Shelves | Inner Chamber Material: SS 304 | No Of Trays: 3 | Usage/Application: Laboratory | Material: Mild Steel | Power Rating: 1.5 kW | Power Supply: 230 V AC | Door Type: Solid Door | Brand: Equitron | Voltage: 220V -- Product Description: Interior of oven is made of specular stainless steel by argon arc-welding technics, and the exterior of oven is made of high-quality steel sheet with a beautiful and novel appearance.",
    "image": "https://5.imimg.com/data5/PB/WY/MY-2095577/hot-air-oven-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Air Entrainment Meter",
    "price": 31500.0,
    "description": "Display Type: Analog | Brand: Shreeji | Application: Laboratory | Grade: Semi-Automatic | Material: SS -- Longer service life Low maintenance",
    "image": "https://3.imimg.com/data3/CT/RI/MY-2095577/air-entrainment-meter-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Standard Sand",
    "price": 1350.0,
    "description": "Product Type: M-Sand | Color: White | Packaging Size: 25kg | Form: Sand STANDARDS | Washed Type: Double Washed | Usage/Application: Construction | IS Standard Compliance: IS 1542 | Packaging Type: Bag | Sand Grade (Zone): Zone II | Silt Content: Up to 5% -- Grade-1 - above 12mm Grade-II - 6mm to 12mm",
    "image": "https://3.imimg.com/data3/GW/SW/MY-2095577/standard-sand-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Bar Bending Machine",
    "price": 110000.0,
    "description": "Machine Type: Bar Bender | Operation Type: Hydraulic | Max Work Size: Up To 32 mm | Usage/Application: Industrial | Application: Rebar | Automation Grade: Semi Automatic | Phase Type: Three Phase | Body Material: Cast Iron | Brand Type: Indian Brand | Country of Origin: Made in India | Power: Electric -- Horizontal Angle Measurement Easy to adjust",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/457435141/AJ/ZQ/QV/2095577/bending-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Earth Compactor Machine",
    "price": 48500.0,
    "description": "Engine Power: 5 Hp | Machine Type: Portable | Usage/Application: Industrial | Capacity: 1.5 Ton | Country of Origin: Made in India -- Excellent functionality Sturdy construction",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/457665125/PI/WA/PF/2095577/earth-compactor-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Sample Extractor Frame Hydraulic Type",
    "price": 18500.0,
    "description": "Brand: Shreeji | Usage/Application: Industrial | Material: MS | Surface Treatment: Color Coated | Height: 1.5-2 Feet -- Accessories : Set of Plunger adapters and thrust plates for 38mm, 50mm and 75mm diameter specimen.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452918053/VZ/HK/ER/2095577/sample-extractor-frame-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Handy Needle Vibrator Electrical",
    "price": 4200.0,
    "description": "Type: Needle Vibrator | Automation Grade: Semi-Automatic | Needle Diameter: 35 mm | Power Source: Electric Motor | Flexible Shaft Length: 3 m | Vibration Frequency: 10000 rpm | Motor Power: 0.75 hp | Brand/Make: SHREEJI | Is It Portable: Non Portable | Application: Beam | Condition: New | Country of Origin: Made in India -- Excellent performance Low operating cost",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452905086/MI/JN/FA/2095577/handy-needle-vibrator-electrical-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Handy Needle Vibrator",
    "price": 2850.0,
    "description": "Brand/Make: Shreeji | Material: Iron, Rubber, Mild Steel | Packaging Type: Box | Length: 1-2 m -- Excellent functionality Sturdy construction",
    "image": "https://4.imimg.com/data4/UQ/FV/MY-2095577/handy-needle-vibrator-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Total Station South N4 In Gandhinagar",
    "price": 240000.0,
    "description": "Brand: SOUTH | Usage/Application: Land Survey | Angle Measurement Angle Accuracy: 2\" | Telescope Shortest Focus Distance: 1.5m | Angle Measurement Method: Absolute Code | Angle Measurement Minimum Reading: 1\" | Compensator Setting Accuracy: 1\" | Compensator System: Single-axis liquid-electric tilt sensor Compensator | Compensator Working Range: +/- 3' | Telescope Magnification: 30X | Telescope Reticle: Illuminated -- Rugged design Compact size",
    "image": "https://5.imimg.com/data5/WY/BI/UE/SELLER-2095577/south-total-station-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Garmin Oregon 750",
    "price": 56000.0,
    "description": "Screen Size: 3.5 Inch | Type: Wireless | Packaging Type: Box | Usage/Application: HAND USE | Brand: garmin -- High-sensitivity dual GPS and GLONASS satellite reception for better performance in challenging environments than GPS alone Redesigned antenna enables better reception and performance; 3-axis compass with accelerometer and barometric altimeter sensors",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/457661564/JM/KM/GY/2095577/garmin-oregon-750-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Garmin eTrex 10 GPS",
    "price": 14500.0,
    "description": "Screen Size: 3.5 Inch | Type: Wireless | Usage/Application: hand haled | Mobile Access: No | Brand: Garmin | Condition: New -- Light-weight Water-proof",
    "image": "https://4.imimg.com/data4/WU/QJ/MY-2095577/garmin-etrex-10-gps-devices-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "GARMIN GPS 72H Devices",
    "price": 21500.0,
    "description": "Screen Size: 2.5 Inch | Type: Wireless | Usage/Application: Car | Mobile Access: No -- GPS 72H, a lightweight, waterproof handheld that floats. Simple yet robust, the GPS 72H features high-sensitivity GPS and a USB connection along with its large screen, simple operation and rock-solid performance. Find your way effortlessly with the GPS 72H's high-sensitivity GPS receiver. GPS 72H acquires satellite signals quickly and tracks your location in challenging conditions, such as heavy tree cover or deep canyons. Use on Land or Water",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/457661871/YQ/QM/WM/2095577/garmin-gps-72h-devices-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Garmin ETrex 20 Devices",
    "price": 26500.0,
    "description": "Brand: Garmin | Display: 2.2\" 65K color, sunlight-readable | Batteries: 25-hour battery life with 2 AA | Usage: Laboratory | Storage: 1.7 GB -- Specifications: Worldwide basemap",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/457661670/GK/GI/XG/2095577/garmin-etrex-20-devices-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Garmin eTrex 20 GPS Devices",
    "price": 19500.0,
    "description": "Screen Size: 2.5 Inch | Type: Wireless | Usage/Application: Car | Mobile Access: No | Brand: GARMIN -- Reliable GPS Expanded mapping capabilities",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453798660/WH/AN/YA/2095577/garmin-etrex-20-gps-devices-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Garmin- Etrex-22x GPS Tracking Device",
    "price": 24500.0,
    "description": "Screen Size: 2.6 inch | Battery Type: AA | Brand: garmin | Weight: 141 g | Battery Life: 25 hours | Maximum Range: 1 m | Warranty: 1 Year | Type: Wireless | Color: Black | Display Resolution: 128 x 160 pixels | Power Consumption: 2 W | Operating Temperature: -20 - 60 degree c -- Waypoints: 2000 Track log: 10,000 points, 200 saved inks",
    "image": "https://4.imimg.com/data4/WT/MY/MY-2095577/garmin-etrex-20x-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Garmin eTrex 30 GPS Devices",
    "price": 28500.0,
    "description": "Screen Size: 2.5 Inch | Battery Type: AA | Brand: GARMIN | Weight: 141 g | Battery Life: 25 hours | Maximum Range: 1 m | Mobile Access: No | Type: Wireless -- Reliable operation User-friendly",
    "image": "https://4.imimg.com/data4/GV/PG/MY-2095577/garmin-etrex-30-gps-devices-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Garmin GPSMAP 78s Devices",
    "price": 23500.0,
    "description": "Product Line: Garmin Etrex | Screen Size: 2.5 Inch | Device Type: Handheld GPS | Usage: Marine, Aviation, Outdoor | Power Source: Internal Battery | Ingress Protection: NOS | Packaging Type: Box | Mobile Access: No | Features: Topo Maps | Color: Grey and Black -- For boaters and watersports enthusiasts who want to run with the best, the rugged GPSMAP 78s features a 3-axis compass, barometric altimeter, crisp color mapping, high-sensitivity receiver, new molded rubber side grips, plus a microSD\u2122 card slot for loading additional maps. And it floats! You've been busy exploring and now you want to store and analyze your activities. With a simple connection to your computer and to the Internet, you can get a detailed analysis of your activities and send tracks to your outdoor device using Garmin Connect\u2122. This one-stop site offers an activity table and allows you to view your activities on a map using Google\u2122 Earth. Explore other routes uploaded by millions of Garmin Connect users and share your experiences on Twitter\u00ae and Facebook\u00ae. Getting started is easy, so get out there, explore and share.",
    "image": "https://4.imimg.com/data4/HB/LB/MY-2095577/garmin-gpsmap-78s-devices-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Garmin Montana 650 Devices",
    "price": 62500.0,
    "description": "Screen Size: 2.2 inch | Battery Type: AA | Brand: Garmin | Weight: 141 g | Battery Life: 25 hours | Maximum Range: 1 m | Camera: 5 MP | Display Resolution: 128 x 160 pixels | Power Consumption: 2 W | Operating Temperature: -20 - 60 degree c | Display: Touchscreen | Display Size: 4\" -- Specifications: 4\" dual-orientation, glove-friendly touchscreen display",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/6/430029124/PW/ZH/TE/2095577/garmin-montana-650-devices-500x500.jpeg",
    "hsn_code": "9031"
  },
  {
    "name": "Garmin Montana 680 Devices",
    "price": 62500.0,
    "description": "Screen Size: 2.6 inch | Battery Type: AAA | Brand: Garmin | Weight: 141 g | Battery Life: 25 hours | Maximum Range: 1 m | Camera: 8 mega pixel | Display: Touch Screen | Compass: 3 axis | Display Size: 4.5\" | Packaging Type: Box -- Specifications: 4-inch dual-orientation, glove-friendly touchscreen display",
    "image": "https://4.imimg.com/data4/CI/MD/MY-2095577/garmin-montana-680-devices-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "GARMIN eTrex 30 Devices",
    "price": 23800.0,
    "description": "Screen Size: 3.5 Inch | Type: Wireless | Mobile Access: No | Brand: GARMIN | Body Material: ABS -- Specifications: Worldwide basemap",
    "image": "https://4.imimg.com/data4/VR/IL/MY-2095577/garmin-etrex-30-devices-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Garmin Monterra",
    "price": 48500.0,
    "description": "Screen Size: 2.5 Inch | Battery Type: AA | Brand: GARMIN | Weight: 141 g | Battery Life: 25 hours | Maximum Range: 1 m | Usage/Application: Mobile | Mobile Access: No | Type: Wireless | Internal Memory: 16 GB | Display Resolution: 128 x 160 pixels | Power Consumption: 2 W | Operating Temperature: -20 - 60 degree c -- Bluetooth\u00ae wireless technology: yes Wi-Fi connectivity: yes",
    "image": "https://5.imimg.com/data5/UA/UB/MY-2095577/garmin-monterra-500x500.png",
    "hsn_code": "9031"
  },
  {
    "name": "Professional Metal Detector",
    "price": 75000.0,
    "description": "Detection Depth: 3\u20135 m | Technology: Multi Frequency | Target Type: Gold & Metals | Range: 2 meter, 4 meter, 6 meter, 8 meter | Waterproof Rating: IP65 | Power Source: AA Battery | Alarm Mode: Sound, Led Lights | Features: Sensitivity Adjustment, Rechargeable Batteries, Built in Speaker | Display Type: Digital -- Features:\u2022 Fully Automatic And All Metal Detectors: The MD-3010II detector detects all kinds of metal objects. Unless you have set for some objects that you do not want to detect. \u2022 LCD: The LCD comes with light, it improve to identify the metal in night or caliginous outside.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/456869646/UQ/MO/FH/2095577/professional-metal-detector-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Underground Pipeline Detector",
    "price": 185000.0,
    "description": "Country of Origin: Made in India | Type: Pipe Detector | Power Source: Battery | Display: Digital Screen | Application: Detection of pipe",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452899135/RY/KX/AO/2095577/underground-pipe-detector-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Underground Pipe And Cable Locator",
    "price": 220000.0,
    "description": "Location Method: Active Locate | Max Depth: 2 m | Power Source: Rechargeable Battery | Display: Digital | Display Type: LED | Frequency Range: 50-60 Hz | Cable Type: Fiber Optic | Usage: Laboratory, Industrial | Frequency: 50Hz | Color: Orange | Protection Degree: IP65 | Interface Port: USB | Body Material: ABS",
    "image": "https://5.imimg.com/data5/JI/DT/MY-2095577/pipe-and-cable-locator-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "MXL2 Precision Pipe & Cable Locator",
    "price": 190000.0,
    "description": "Location Method: Active Locate | Max Depth: 2 m | Power Source: Rechargeable Battery | Display Type: LCD | Frequency Range: 50-60 Hz | Cable Type: Fiber Optic | Product Dimensions: 720 x 280 x 65 mm | Weight: 2.4kg (including batteries) | Power Supply: 8 x \"AA\"cells alkaline batteries | Battery Life: Up to 40 hours intermittent use | Environment case: Sealed to IP 65 | Temperature Range: -20C to +50C | Approvals: CE | Item Code: ST-MXL -- The MXL2 is a high performance precision pipe & cable locator developed using the latest in advanced digital signal processing technology packed with the most advanced features . It is the ideal equipment for utilization in job sites demanding quick & clear position of buried pipe and cables prior to excavation in even the most difficult of environments & very specific route tracing of buried services. Designed to detect, identify and route trace specific buried pipes and cables reliably and accurately, even in the most congested areas .The MXT2 transmitter provides longer distance tracing than previously possible. Depth Measurement.",
    "image": "https://5.imimg.com/data5/VO/BY/MY-2095577/mxl2-precision-pipe-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Underground Cable Tracker",
    "price": 224000.0,
    "description": "Country of Origin: Made in India | Voltage: 220V/240V | Frequency: 50Hz | Usage: Industrial,Laboratory | Power Source: Electric",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452906914/FP/AE/CO/2095577/underground-cable-tracker-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "cat 4 Cable and Pipe Locators",
    "price": 240000.0,
    "description": "Material: Plastic | Usage: Laboratory | Packaging Type: Box | Accuracy: 3 m -- Dual active simultaneous frequency scan for hard to find utilities GPS built-in with 3m accuracy",
    "image": "https://5.imimg.com/data5/UE/KM/MY-2095577/cebal-locator-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Long Range Gold Detector",
    "price": 75000.0,
    "description": "Range: 4 meter, 6 meter | Alarm Mode: Led Lights, Sound | Features: Sensitivity Adjustment, Rechargable Batteries | Automation Grade: Semi-Automatic | Frequency: 50Hz -- Description This MD-6250 underground metal detector, the upgraded MD-6150, is a kind of professional detecting equipment. It is easy to carry and operate. It can scan the metal objects easily and quickly, which can help professionals a lot when they do the research. And it is won't make mistake and fake alert. The MD-6250 is designed with exclusive Graphic Target ID technology to distinct the metal objects. It can select the type of metals in gold, silver, iron and so on. You also have color green and yellow to choose.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453485628/VM/UO/UB/2095577/long-range-gold-detector-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Auto Level Instrument, Model Name/Number: B-40a",
    "price": 20500.0,
    "description": "Usage/Application: Land Survey | Model Number: B40A | Brand: SOKKIA | Country of Origin: Made in India | Magnification: 24X",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455097815/LZ/TS/QO/2095577/sokkia-b40a-auto-level-instrument-model-name-number-b-40a-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Sokkia Auto Level Instrument",
    "price": 20500.0,
    "description": "Magnification: 32X | Usage/Application: Survey | Accuracy per km: 1.5 mm | Compensator range: \u00b115 arc min | Packaging Type: Box | Objective aperture: 32 mm | Shortest focus: 1.5 m | Dust water rating: IP55 | Brand: Sokkia | Size: 32x | Weight: 2kg | Model Name/Number: SOKKIA B40A | Packaging Size: 1",
    "image": "https://5.imimg.com/data5/SELLER/Default/2022/4/VK/WH/LX/2095577/sokkia-auto-level-500x500.jpeg",
    "hsn_code": "9031"
  },
  {
    "name": "Sokkia B40 Auto Level Instrument",
    "price": 20500.0,
    "description": "Magnification: 32X | Usage/Application: Leveling | Accuracy per km: 1.5 mm | Compensator range: \u00b115 arc min | Packaging Type: Box | Material: Brass | Objective aperture: 32 mm | Shortest focus: 1.5 m | Dust water rating: IP55 | Brand: Sokkia | Size: 32x | Weight: 2kg | Model Name/Number: SOKKIA B40A | Minimum Focus: 250 METER | Packaging Size: 1 | Is It Magnetic: Non Magnetic -- 3 Models - 32x, 28x, and 24x Magnifications 3 Models - 32x, 28x, and 24x Magnifications",
    "image": "https://5.imimg.com/data5/SELLER/Default/2022/12/YS/UR/JF/2095577/auto-level-500x500.jpeg",
    "hsn_code": "9031"
  },
  {
    "name": "Leica Auto Level Instrument",
    "price": 19500.0,
    "description": "Usage/Application: Survey | Accuracy per km: 1.5 mm | Compensator range: \u00b115 arc min | Objective aperture: 32 mm | Shortest focus: 1.5 m | Dust water rating: NOS | Brand: LEICA | Model Number: NA 324 | Includes tripod: Yes | Includes staff: Yes | Staff length: 4 m | Magnification: 30X -- 24x Magnification; 2.0 mm Standard Deviation per km (double-run leveling), Telescope: 36 mm clear objective aperture,",
    "image": "https://4.imimg.com/data4/LP/QV/MY-2095577/leica-auto-level-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Leica NA324 Automatic Level",
    "price": 19500.0,
    "description": "Usage/Application: Leveling | Accuracy per km: 1.5 mm | Compensator range: \u00b115 arc min | Color: Black | Objective aperture: 32 mm | Shortest focus: 1.5 m | Dust water rating: IP55 | Model Name/Number: NA324 | Minimum Focus: 32X | Resolving Power: 4\" | Brand: Leica | Includes tripod: Yes | Includes staff: Yes | Image: Erect | Magnification: 32X | Length: 215mm",
    "image": "https://5.imimg.com/data5/QE/LY/TV/SELLER-2095577/leica-auto-level-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Bosch Auto Level",
    "price": 16500.0,
    "description": "Instrument type: Automatic level | Magnification: 32X | Accuracy per km: 1.0 mm | Color: Blue | Objective aperture: 32 mm | Shortest focus: 1.5 m | Tripod material: Aluminium | Brand: Bosch | Usage/Application: Land Survey | Weight: 5.1 kg | Angle Measurement Accuracy: 1 second | Application: Survey | Includes tripod: Yes | Includes staff: Yes | Dust water rating: IP55 | Operating Temperature Range: -20 DegreeC -- 50 DegreeC -- The Bosch Gol 26D Optical Site Level has been designed for the experienced and novice users, with a build quality worth of the Bosch.Bosch optical levels provide a perfect combination of precision and robustness. Reliable and exact results. Compact metal design suitable for construction sites and tough outdoor conditions.Description: Every aspect of the Bosch GOL 26D demonstrates how series Bosch are with getting their survey tools designed and built to meet the users requirements. The Bosch Site Level GOL 26 D is not just another level. The Site proof casing surrounds the very high quality 26x magnification optics that offers clear images of the levelling staff over long and short distances. The housing on the Bosch 26D is solid with an easy 360 degree rotation plate with clear presice position marks. The base plate of the sIte level sits firmly on the Bosch Site tripod included with this kit. The site tripod is strong enough to be loaded used time and time again in all site environments offering a very stable platform for the Site Level. The Bosch 5 Metre levelling staff complements this excellent value package \u2013 The Bosch GOL 26 D Optical Site Level. shreeji instrumnet",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/454977301/AA/FK/HH/2095577/ts-02-plus-r500-leica-flex-line-total-station-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "GPS Navigation Device",
    "price": 45000.0,
    "description": "Screen Size: 7 inch | Brand: Garmin | Display Type: TFT | Usage/Application: MAPPING | Type: Wireless | Display Resolution: 800 x 480 pixels | Model Name/Number: Etrex 32x | Packaging Type: Box | Power Consumption: 2 W | Battery Life: 16 hours",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/8/441937343/LV/SA/LU/2095577/garmin-gps-64s-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Prism Poles",
    "price": 4500.0,
    "description": "Pole Type: GPS Pole | Max Length: 2.5 m | Material: ALUMINIUM | Size: 2.4 | Graduation: cm | Number Of Sections: 1 Section | Lock Type: Flip Lock | Usage/Application: surveying | Packaging Type: Box | Color: RED/WHITE | Brand: Sokkia -- Unmatched quality Precise design",
    "image": "https://5.imimg.com/data5/JH/XF/BE/SELLER-2095577/prism-poles-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Tmt Bar Bending Machine",
    "price": 100000.0,
    "description": "Max Bending Capacity: 40 mm | Automation Grade: Semi-Automatic | Usage/Application: Leveling | Power: Electric | Bar Dimensions: 32 | Product Type: Steel Bar, Steel Rebar | Max Bending Angle: 180 degree | Max Bending Radius: 50 mm | Bend Direction: Clockwise | Power Source: Electric | TMT Bar Steel Dia.: 32MM | Body Material: Iron | Operating System: Semi Automatic | Brand: Shreeji Instruments | Country of Origin: Made in India | Voltage: 220-240 V | Automation Type: Semi Automated -- Horizontal Angle Measurement Easy to adjust",
    "image": "https://5.imimg.com/data5/SELLER/Default/2022/11/MY/FZ/VL/2095577/bar-bending-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Digital Coating Thickness Gauge",
    "price": 18500.0,
    "description": "Display: Digital | Material: Plastic | Display Type: Digital | Country of Origin: Made in India | Wire Length: 2-3 m -- Paint thickness gauge / DFT gauge is widely used in India by: Powder Coating Shops",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451667199/XU/GA/PD/2095577/digital-coating-thickness-gauge-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Paint Micron Coating Thickness Gauge",
    "price": 8500.0,
    "description": "Elcometer Series: Elcometer 3230 | Substrate Type: F Only | Measuring Range: 0~1250 micro meter (0 ~50 mil) | Probe Type: Micro Probe | Resolution: 10 \u00b5m | Data Interface: USB | Display: Graphic LCD with backlight | Operating Temperature: 0 to 50 C | Weight: 110 g | Test Method: Magnetic induction | Data Memory: 500 readings | Power Supply: 1.5V*3 (AAA alkaline batteries) | Dimension: 150X50.5X29mm | Certificate: 1 Year NABL Trecebility Certificate free -- DFT 111Digital Coating Thickness Gauge \u2022 Magnetic induction method (ISO 2178, ASTM D7091), Measurement of non-magnetic coatings on magnetic substrates.Features",
    "image": "https://5.imimg.com/data5/QV/FP/MY-2095577/paint-micron-coating-thickness-gauge-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Concrete Test Hammer",
    "price": 7200.0,
    "description": "Measuring Range: 100 MPa | Hammer Type: NR | Impact Energy: 2207 | Material: Stainless Steel | Usage/Application: impact test | Brand: shreeji | Country of Origin: Made in India | Measuring Ranges: 10-60MPa | Spring Constant: 785N/m | Spring Extension: 75mm -- Compact design High accuracy",
    "image": "https://5.imimg.com/data5/SELLER/Default/2025/6/522282686/FO/IX/IJ/2095577/concrete-test-hammer-500x500.jpeg",
    "hsn_code": "9031"
  },
  {
    "name": "Digital Hardness Tester",
    "price": 150000.0,
    "description": "Weight: Weight 300g | Humidity: 20%-85% | Temperature: 20C-+50C | HL display range: 170-960HLD | Repeatability: 6HLD | Storage environment Temperature: -30C-+70C | Dimensions: 130mm*86mm*28mm | Operating voltage: 3.6V -- Testing direction All direction Optional Impact Device D, DC, DL, D+15, C, G",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/454976893/QE/FX/QG/2095577/digital-hardness-tester-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Concrete Thickness Gauge",
    "price": 250000.0,
    "description": "Material: Plastic | Type: Digital | Usage/Application: Laboratory | Country of Origin: Made in India | Wire Length: 2-3 m -- Brief Introduction:TC300 is used for measuring the thickness of nonmetallic plate indirectly, especially for concrete slab. This gauge is to measure the concrete slab thickness mainly by using distribution characteristics of electromagnetic field and possesses functions of thickness measurement, data analysis, data storage output etc. It is a kind of intelligent thickness measuring instrument that is portable, convenient and accurate. Measuring the thickness of concrete, rock glass and other nonmetallic plate",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451667003/IU/BV/CJ/2095577/concrete-thickness-gauge-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Survey Instruments Accessories",
    "price": 1000.0,
    "description": "Instrument Type: Accessory | Brand: Topcon | Application: Land Survey | Usage: Land Survey | Power Source: Rechargeable | Material: Plastic POM/PA | Ingress Protection: IP54 | Packaging Type: Box | Size: 0.75\" 1\" 1.2\" 1.5\" 2\" | Prism Pole: Leica/ Sokkia type | Product Type: Level | Usage/Application: Survey -- Downloading Cables Plastic Boxes",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/8/447224456/TS/CX/XM/2095577/survey-instruments-accessories-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Digital Measuring Wheel and Rodo Meter",
    "price": 3850.0,
    "description": "Color: Black Yellow Silver | Material: Aluminum alloy PC | Max measuring distance: 9999.99 m | Accuracy: 0.5% | Minimum display value: 0.01m | Powered by: 2 x AAA batteries | Wheel Diameter: 15.7 cm -- With metric and inch dual system With digital display and memory",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455073643/BB/CY/BN/2095577/digital-measuring-wheel-and-rodo-meter-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "GPS Survey Equipment",
    "price": 24500.0,
    "description": "System type: Handheld GIS | Receiver type: Single freq | Horizontal accuracy: <0.5 m | Usage/Application: SURVEYING | Screen Size: 3.5 inch | GNSS system: GPS+GLONASS | Positioning mode: DGPS | Data interface: 4G modem | Type: Wireless | Range: SETLATIE | Is It Mobile Access: Non Mobile Access | Brand: GARMIN | Battery backup: 6 hr | Operating System: SETTELITE | Battery Life: AA++ -- Quartz plate with round size diameter 10mm-500mm Quartz plate with square size L: 100mm-500mm W: 10mm-500mm",
    "image": "https://5.imimg.com/data5/SELLER/Default/2023/12/372015378/PM/LH/KV/2095577/710xvz4yysl-sl1500-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Data Downloading Cable",
    "price": 5000.0,
    "description": "Brand: sokkia,topcon | Usage/Application: Survey | Packaging Type: Box | Weight: 25 g | Length: 1 m | Power Souce: Electric -- Temperature resistance Optimum quality",
    "image": "https://4.imimg.com/data4/FX/IP/MY-2095577/img_5777-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Rodometer Measuring Wheel",
    "price": 3850.0,
    "description": "Measuring Range: 10000 | Brand: Freemans | Usage/Application: Distance Measurement | Model Name/Number: LDM 100H | Range: 100 meter | Warranty: 6 month | Handle Grip Material: PVC | Handl Material: Aluminium | Wheel Diameter: 10-15 inch -- Shreeji instruments ofer to Freemans Rodo meter 12\" aluminum wheel counts up to 10,000 feet (10,000m) with convenient push-button reset",
    "image": "https://4.imimg.com/data4/SP/JL/MY-2095577/measuring-wheel-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Digital Planimeter, Industrial",
    "price": 38500.0,
    "description": "Usage/Application: Industrial | Power Source: Electric | Voltage: 240 V | Frequency: 50/60Hz | Display Type: Digital -- With an objective to fulfill the ever evolving demands of our clients, we are engaged in offering a wide assortment of Digital Planimeter. \u2022 Robustness \u2022 High strength \u2022 Impeccable finish",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453188084/RS/PR/VL/2095577/digital-planimeter-industrial-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Round Optical Square Survey instrument",
    "price": 1000.0,
    "description": "Shape: Round | Usage/Application: Survey | Material: Brass | Country of Origin: Made in India | Packaging Type: Box | Usage: Laboratory",
    "image": "https://4.imimg.com/data4/LF/AW/MY-2095577/round-optical-square-survey-instrument-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Freemans Measuring Tapes",
    "price": 80.0,
    "description": "Tape Length: 100 m | Usage/Application: Measurement | Features: Durable | Country of Origin: Made in India | Packaging Type: Box | Width: 3 Meter - 30 Meter | Thickess: 1-2 mm -- 15 meter, 13mm width 15 meter, 9.5mm width",
    "image": "https://4.imimg.com/data4/HC/YP/MY-2095577/measuring-tapes-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Surveying Line Ranger",
    "price": 2500.0,
    "description": "Weight: 200 | Light Source: 1310+20nm LD | Fiber: 9/125um Single-mode optical fiber | Interface: FC/PC | Reflection Event: 40 km - 80 km | Non Reflection Event: 20m | Power Supply: 3V 6V | Battery Working Time: Provide 10, 000 times of measurements | Temperature: 0~40 Degree -10~60 Degree | Humidity: 0~85%Non-condensing",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453481181/ZB/BK/QC/2095577/surveying-line-ranger-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Bosch GLM 40 - 40m Laser Distance Meter",
    "price": 4500.0,
    "description": "Model: GLM 40 | Accuracy: \u00b11.5 mm | Laser Class: Class 2 | Usage/Application: Distance Measurement | Functions: Area | Bluetooth: No | Brand: Bosch | Unit Measurements: m/cm,Ft/Inch | Dust and Splash Protection: IP 54 | Measuring Range: 50 Meters | Range: 40 m -- Distance measurement, area calculation, volume calculation all intuitive to use Illuminated, three-line display maximises readability",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453480330/SX/TT/ED/2095577/bosch-glm-40-40m-laser-distance-meter-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Leica Total Station Sales",
    "price": 280000.0,
    "description": "Model: TS01 | Angle Accuracy: 5\u2033 | Prism Range: 5000 m | Magnification: 32X | Distance Measurement: 1 km | Reflectorless Range: 500 m | Resolving Power: 2.5 inch | Distance Accuracy: \u00b11 mm+1.5 ppm | Number of Prism: Single | Data Storage: 10000 points | Model Name/Number: TS01 | Angle Measurement Accuracy: 1 second, 5 second | Communication: USB | Minimum Focus: 0.5m | Battery Backup: 36 hrs | Protection Class: IP55 | Usage/Application: Surveying | Measuring Time: 0.5 second | Battery Type: Li-ion Rechargeable | Warranty: 1 year",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/3/396520773/OB/KP/LP/2095577/leica-total-station-sales-ahmedabad-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Surveying Equipment",
    "price": 280000.0,
    "description": "Usage/Application: Land survey, Leveling Instrument | Packaging Type: Carton | Material: Aluminium | Packaging Size: Customized as per requirement -- Designed to speed up crushing of Aggregates, Ores, Mineral, Coal and Similar Materials Compact and rugged for laboratory and small production units",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/3/396528818/VE/TP/WQ/2095577/surveying-equipment-ahmedabad-500x500.jpeg",
    "hsn_code": "9031"
  },
  {
    "name": "California Bearing Ratio Apparatus",
    "price": 38500.0,
    "description": "Automation Grade: Semi-Automatic | Color: Blue | Material: Steel | I Deal In: New Only | Country of Origin: Made in India -- High efficiency Hassle free performance",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/457664178/HU/FE/NO/2095577/california-bearing-ratio-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "concrete weigh batcher",
    "price": 38500.0,
    "description": "Brand/Make: shreeji | Material: Cast Iron | Automation Grade: Manual | Surface Treatment: Color Coated | No of Wheel: 4 Wheel | Capacity: 200-300 L -- Specifications: Chassis: Heavy Duty, Robust Steel Chassis from 75X40 mm Channel",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/457440044/ED/MX/HT/2095577/concrete-weigh-batcher-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Plane Table Survey Board",
    "price": 6500.0,
    "description": "Material: Brass/Aluminium | Usage/Application: Survey | Cover Material: Canvas | Height: 3-3.5 Feet | Table Thickness: 1 inch -- Light weight Easy installation",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453186360/ZS/ZS/NB/2095577/plane-table-board-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Labor  Safety Shoes",
    "price": 260.0,
    "description": "Upper Shoe Material: Leather | Usage/Application: Construction | Sole: PU | Length: Low ankle | Outsole Material: Real Leather | With Lining: Yes | Closure: Laces | Packaging Type: Box | Sole Color: Black | Gender: Male | Material: Leather | Features: Acid Resistant & Anti Skid | Size: 6 - 11 | Brand: Metro",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451602105/TC/SD/OF/2095577/labor-safety-shoes-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Industrial Lifting Belts",
    "price": 245.0,
    "description": "Capacity: 5 ton | Belt Length: 4 m | Belt Width: 100 mm | Material: Nylon, Polyester | Sling Type: Flat Belt | Safety Factor: 5:1 | Color: White Green | Brand: Shree Ji | Structure Type: Roller Conveyor | Ply: 3 Ply | Usage/Application: Lifting | Purity: 100%",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451668105/JQ/UL/RE/2095577/industrial-lifting-belts-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Industrial Safety Shoes",
    "price": 950.0,
    "description": "Usage/Application: Construction | Outsole Material: Real Leather | Size: All Sizes | Length: High ankle | Brand: Acme | Application: Construction | Ankle Length: Low Ankle & High Ankle | Features: Acid Resistant & Anti Skid | Available Size: 6 - 11 | Certification: ISI | Sole: PU | Material: Leather | Sole Color: Black | Gender: Male | Closure: Laces | Packaging Type: Box | With Lining: Yes",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451603131/YD/WB/WC/2095577/industrial-safety-shoes-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Hillson Safety Shoes",
    "price": 600.0,
    "description": "Usage/Application: Construction | Application: Construction | Ankle Length: Medium Ankle & High Ankle | Length: Low ankle, High ankle | Available Size: 6 - 11 | Is It With Lining: With Lining | Material: Leather | Size: 6-11 | Sole Color: Black | Gender: Male | Closure: Laces | Brand: Tiger | Insole Material: Leather | Packaging Type: Box | With Lining: Yes | Outsole Material: Real Leather | Sole: PU -- Product Details:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451602570/CN/MW/IP/2095577/hillson-safety-shoes-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Tiger High Ankle Safety Shoes",
    "price": 1050.0,
    "description": "Usage/Application: Construction | Color: Black | Sole Type: PU | Size: 8 -- We have specialization in offering a wide assortment of Tiger High Ankle Safety Shoes Known for high durability and thermal resistant, these shoes are very popular among our clients. These high ankle shoes with steel toe cap are suitable to wear for engineering, construction, and chemical industries. Our offered products are procured from trusted vendors, thus ensuring leather quality and hard sole material. These shoes are available with us in different sizes for diverse needs of customers. Anti sweating polyester lining",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451602536/XQ/ID/LL/2095577/tiger-high-ankle-safety-shoes-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Tiger Safety Shoes",
    "price": 850.0,
    "description": "Sole Type: Leather | Application: Construction | Shoe Color: Black | Length: Low ankle -- Precisely designed Flawless finish",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451602441/DL/YZ/FZ/2095577/tiger-safety-shoes-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Hillson PU Sole Double Density Safety Shoes",
    "price": 1050.0,
    "description": "Ankle Length: Low Ankle | Shoe Color: Black | Available Size: 8 | Sole: Rubber | Certification: ISI -- Product Details:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451602904/DH/RW/IY/2095577/hillson-pu-sole-double-density-safety-shoes-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Karam Safety Shoes",
    "price": 1250.0,
    "description": "Usage/Application: Construction | Sole: PU | Color: Black | Certification: ISI | Sole Type: PU | Application: Construction | Outsole Material: PU | Ankle Length: Low Ankle, High Ankle | Size: 6 - 11 | Shoe Color: Black | Available Size: 6 - 11 | Features: Acid Resistant & Anti Skid | Insole Material: PU | Material: Leather & PU | Length: High ankle | Sole Color: Black | Gender: Male | Closure: Laces | Packaging Type: Box | Is It With Lining: With Lining | With Lining: Yes | Insole Metarial: Leather -- Description: Worker\u2019s Safety Shoe providing safety with comfort.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451602835/TR/NE/QD/2095577/karam-safety-shoes-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Metro Safety Shoes",
    "price": 850.0,
    "description": "Usage/Application: Construction | Size: All Sizes | Certification: ISI | Sole Type: PU | Application: Construction | Outsole Material: Real Leather | Ankle Length: Low Ankle & High Ankle | Available Size: 6 - 11 | Sole: PU | Material: Leather | Length: High ankle | Sole Color: Black | Gender: Male | Closure: Laces | Brand: Acme | Packaging Type: Box | With Lining: Yes",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451602741/OX/ZK/CD/2095577/metro-safety-shoes-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Hillson Rockland Safety Shoes",
    "price": 750.0,
    "description": "Size: 6, 7, 8, 9, 10 | Material: PU Sole upper layer made from soft leather, steel toe, inside lining also black. | Brand: Hillson | Code: 968 | Weight: 935 grams | Standard Packing: 60 Pairs | Retail Packing: 20 Pairs",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451602212/NL/FO/JW/2095577/hillson-rockland-safety-shoes-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Safety Shoes",
    "price": 900.0,
    "description": "Features: Anti-Skid, Acid Resistant | Insole Material: Leather | Sole: PU | Outsole Material: PU | Ankle Length: High Ankle | Lining: Provided | With Lining: Yes | Sole Color: Black & Brown | Available Size: 6 - 11 | Gender: Male | Closure: Laces | Brand: Acme | Packaging Type: Box -- Product Details:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451602963/CT/QD/PA/2095577/safety-shoes-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Hillson PVC Gumboot",
    "price": 240.0,
    "description": "Size: 6 to 10 | Type: Full Size | Application: Suitable for road construction, snow agriculture, chemical industries | Color: Black | Brand: Hillson | Weight: 1400 Grams | Standard Packing: 24 Pairs -- We are one of the leading traders & suppliers of Hillson PVC Gum Boot.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451602400/AD/SV/LS/2095577/hillson-pvc-gumboot-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Brown Safety Shoes",
    "price": 1050.0,
    "description": "Usage/Application: Construction | Size: 6 - 11 | Brand: Metro | Length: Low ankle | Sole Color: Black | Sole: PU | Outsole Material: PU | Gender: Male | Closure: Laces | Packaging Type: Box | With Lining: Yes | Lining: Yes | Material: Leather & PU | Features: Acid Resistant & Anti Skid | Insole Metarial: Leather -- With our rich industry experience, we are engaged in offering an extensive assortment of Metro Brown Safety Shoes. These shoes are precisely designed from the best quality leather as well ultra-modern techniques by adept professionals at vendors\u2019 end. Our offered shoes give maximum safety and comfort to the wearer due to their sturdy design and impeccable finish. The provided shoes are rigorously tested against diverse quality parameters to ensure their quality. Comfortable to wear",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451602692/AO/YV/RS/2095577/brown-safety-shoes-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Bitumen Extractor Electrically Operated",
    "price": 24500.0,
    "description": "Automation Grade: Semi-Automatic | Material: MS | Brand: Shreeji | Voltage: 220 | I Deal In: New Only | Power: 220 | Hand Operated: No | Usage/Application: BITUMIN | Installation Services: No | Country of Origin: Made in India | Surface Treatment: Color Coated -- Superior performance Easy installation",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/8/447317190/LH/CY/HX/2095577/bitumen-extractor-500x500.jpeg",
    "hsn_code": "9031"
  },
  {
    "name": "Aluminum Telescopic Tripod Stand",
    "price": 3000.0,
    "description": "Material: Aluminium | Packaging Type: Box | Surface Treatment: Color Coated | No of Leg: 3 Leg -- Fully adjustable aluminium telescopic legs. Having two auxiliary eye bolts and mounted pulleys as attachment points.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451600677/GC/EE/XT/2095577/aluminum-telescopic-tripod-stand-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Aluminum Levelling Staves",
    "price": 1500.0,
    "description": "Material: Aluminium | Section type: Telescopic | Graduation: mm and cm | Number of sections: 4 section | Effective Length: 1M,1.5M,2M | Maximal Graduation: Error(dm)less than or equal to +/-0.1mm | Expansion Rate: 2 x 10-6/ Degree C | Square Value of the Spirit Level: 20 Feet 1/2mm | Kiev Error: 4.5mm+/-0.05mm",
    "image": "https://2.imimg.com/data2/KF/IO/MY-2095577/aluminum-levelling-staves-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Wooden Tripod Stand",
    "price": 6500.0,
    "description": "Maximum Height: 1200 mm (4 ft) | Load Capacity: 5 kg | Material: Aluminium | Head Type: Ball Head | Folded Height: 800 mm | Leg Sections: 2 Section | Tripod Material: Wooden Tripod | Primary Material: Aluminium, Solid Wood | Brand: Shreeji | Number Of Legs: 4 | Mounting Thread: 1/4 inch | Application: Survey | Finish: Matt Finish | Country Of Origin: India -- Suitable for all Brand Total Station universe model. 3.5 Kg easy to travel anywhere",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453800198/UP/OO/IO/2095577/wooden-tripod-stand-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Sand Pouring Cylinder Apparatus",
    "price": 5800.0,
    "description": "Automation Grade: Manual | Color: Blue | Packaging Type: Wooden Box | Material: MS | Surface Treatment: Color Coated -- We are engaged in providing our customers with Sand Pouring Cylinders that are widely used for the in place determination of the dry density of compact, fine and medium grained soils. These are also used for determining the layers that should not exceeding 50 cm thickness. In addition to this, our products are available in various sizes, shapes and designs at most competitive prices to fulfill the diverse requirements of our customers. Specifications:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453182933/HP/ED/UQ/2095577/sand-pouring-cylinder-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Cut Off Machine",
    "price": 7800.0,
    "description": "Weight: 86 kg | Blade Diameter: 16 Inch | Wattage: 2200 W | Power: 2.2 kW | Speed: RPM 2290 -- High performance Durable finish standards",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455077085/ZV/YQ/MT/2095577/cut-off-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Sokkia Total Station Tribrach With Adaptor",
    "price": 30000.0,
    "description": "Brand: SOKKIA | Accuracy: 0.1 mm | Material: SS | Packaging Type: Box | Type: Universal | Color: Yellow | With Optical Plummer: Yes | I Deal In: New | Usage/Application: Laboratory | Country of Origin: Made in India",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455093190/XJ/VJ/BM/2095577/sokkia-total-station-tribrach-with-adaptor-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Cube Testing Machine Pillar Type",
    "price": 34500.0,
    "description": "Phase: Single phase | Display Type: Analog | Capacity: 1500 Kn, 1000 Kn | Control Type: Hand operated | Max Cube Size: 150 mm | Accuracy Class: Class 1 | Usage/Application: concrete cube testing machine | Gauge: masta | Model Name/Number: shreeji | Display: 1200kn | Automation Grade: Manual | Power: Hydraulic | Size: 1000 kn / 1200kn | Weight: 280kg | Brand: Shreeji | Material: Iron | Packaging Type: Wooden",
    "image": "https://5.imimg.com/data5/VI/EK/MY-2095577/pier-type-cube-testing-machine-500x500.png",
    "hsn_code": "9031"
  },
  {
    "name": "Skid Resistance Tester",
    "price": 34500.0,
    "description": "Weight: 1500g+30g | Length: 126mm+1mm | Gravity: 410mm+5g | Pressure: 22.2N+0.5N | Rubber Size: 6.35mmx25.4mmx76.2mm(pavement) | Center Distance: 510mm+2mm | Rubber Shaw: 55+5 | Gauge: 30mm -- Light weight Optimum performance",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451666691/PV/VS/YQ/2095577/skid-resistance-tester-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Dial Gauge measuring",
    "price": 2500.0,
    "description": "Gauge Type: Dial Thickness | Measuring Range: 0\u201325 mm | Resolution: 0.01 mm | Brand: Baker | Material: Stainless Steel | Usage/Application: Measurement | Display Type: Analog | Application: Sheet Metal | Throat Depth: 30 mm | Meter Type: Analog | Shape: Round | Packaging Type: Box -- Resolution: 0.001mm Precision: 0.002mm",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451665551/PZ/LO/JB/2095577/dial-gauge-measuring-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Clinical Digital Thermometer",
    "price": 1450.0,
    "description": "Measurement Type: Digital | Brand: Shreeji | Temperature Range: 0 to 300 | Accuracy: 0.1% | Packaging Type: Box | Is It Portable: Portable -- Beep sound alert Clean vision of temperature",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453187196/GI/VX/QT/2095577/digital-thermometer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Vernier Transit Theodolite",
    "price": 14500.0,
    "description": "Display Panel: Single Side | Material: Stainless Steel | Brand: SHREEJI | Usage: Alignment of lines | Magnification: 24X -- Rugged design Compact size",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451656915/EI/LE/NW/2095577/vernier-transit-theodolite-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Cube Cylindrical Mould",
    "price": 3850.0,
    "description": "Weight: 15 KG | Shape: Round | Automation Grade: Manual | Surface Treatment: Color Coated | Material: Cast Iron | Dimension: 10 cm x 20 cm -- Enhanced life Compact design",
    "image": "https://2.imimg.com/data2/MM/BM/MY-2095577/cylinderical-moulds-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Soil Testing Equipment",
    "price": 2850.0,
    "description": "Material: Mild Steel | Automation Grade: Automatic | Color: Blue | I Deal In: New Only | Country of Origin: Made in India -- Needs less maintenance Easy to operate",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/457663543/DA/AO/YL/2095577/soil-testing-equipment-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Marsh Cone Funnel",
    "price": 8500.0,
    "description": "Material: Cast Iron | Usage/Application: Laboratory | Max Feeding: 35-90mm | Speed of Rotary: 200-350r/Min | Capacity: 5-40t/Hour -- Max Feeding: 35-90mm Speed of Rotary: 200-350r/Min",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452907808/BX/US/HW/2095577/marsh-cone-funnel-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Permeability Test Apparatus Constant  Falling Head",
    "price": 38500.0,
    "description": "Test Method: Falling Head | Brand: SHREEJI | Capacity: 1000 ml | Automation Grade: Automatic | Color: Blue | Diameter: 6 mm | Dial Gauge: Analog | Material: Mild Steel | Usage/Application: SOIL TESTING | Pressure: 10 kpa | Test Material: Soil | Test Type: Permeability -- As Per IS 2720 (Part XBII)- 1966 BS 1377; EN DD ENV 1997-2; ASTM D2434;AASHTO T215 This equipment is used for testing the permeability of granular soils (sands and gravels).The specimen is formed in a permeability cell and water is passed through it from",
    "image": "https://5.imimg.com/data5/SELLER/PDFImage/2023/11/358471648/CW/FI/VB/2095577/permeability-test-apparatus-constant-falling-head-500x500.png",
    "hsn_code": "9031"
  },
  {
    "name": "Digital Weighing Balances",
    "price": 9500.0,
    "description": "Size: 300x216x21.5mm | Box Size: 315x231x31.5mm | Carton Size: 330x246x276mm | Net Weight: 8.96kg | Gross Weight: 10.5 kg -- Reliability Flexibility",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/454977171/OR/SI/PL/2095577/digital-weighing-balances-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Standard Calibration Weights",
    "price": 4500.0,
    "description": "Material: Stainless Steel | Usage: Laboratory | Weights: 20 kg | Range: 1mg to 200 g -- High precision metrological standard weights and weight box complete range:- 1mg to 200 g weight box and loose weights upto 20 kg",
    "image": "https://5.imimg.com/data5/SELLER/Default/2026/1/576440026/IN/ST/QJ/2095577/standard-calibration-weights-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Portable Compression Testing Machine",
    "price": 85000.0,
    "description": "Capacity: 1000 kN | Automation Grade: Semi-Automatic | Usage/Application: CONCRET CUBE TESTING | Gauge: DIGITAL | Brand: SHREEJI | Material: IRON | Phase: SINGAL PH | Power Supply: 220 | Weight: 300 KG | Country of Origin: Made in India -- Being a well established-organization, we are engaged in manufacturing and supplying Electric Cube Testing Machine Dual Gauges. Our offered gauges are manufactured utilizing finest quality material and advanced technology following the standards of industry. The gauges offered by us are ideal for concrete, cement, tube, brick compression and flexural test. We are giving these gauges from us on diverse specifications. Innovative design",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/8/443163053/OK/ZV/DM/2095577/compression-testing-machine-manufacturers-ahmedabad-500x500.jpeg",
    "hsn_code": "9031"
  },
  {
    "name": "Cube Testing Machine",
    "price": 37500.0,
    "description": "Display Type: Analog | Phase: MANUAL | Capacity: 1000 Kn, 1500 Kn | Packaging Type: Iron | Control Type: Hand operated | Max Cube Size: 150 mm | Accuracy Class: Class 1 | Automation Grade: Manual | Frame Type: Channel type | Power: Hydraulic | Brand: SHREEJI | Is It Portable: Portable | Material: MS",
    "image": "https://5.imimg.com/data5/UE/YT/MY-2095577/cube-testing-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Earth Rammer",
    "price": 45000.0,
    "description": "Capacity: 2 ton | Power Source Type: Petrol | Machine Type: Vibratory | Brand: Honda | Engine Power: 3 hp | Weight: 90 kg | Brand/Make: shreeji | Bounce Height: 40 - 75 MM | Advanced Speed: 10 - 13 m/min | Ramming Frequency: 600 - 700 time/min | Net Torque: 7.6 IB-ft (10.3 Nm) @ 2500 rpm | Engine Type: G X 160 Honda Air Cooled 4 stroke OHV | Motor Power: 4 kW | Gross Weight: 90 Kg -- Less power consumption Require less maintenance",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/457434595/FS/SN/RQ/2095577/earth-rammer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Digital Prismatic Compass",
    "price": 3500.0,
    "description": "Material: ABS | Usage/Application: Laboratory | Display Type: Digital | Packaging Type: Carton Box | Brand: Bushnell -- The degree on below is the Mekka degree. It indicate how many degree you should adjust. The degree on above is current degree. We should adjust current degree same as Mekka degree.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453484652/OV/OP/PV/2095577/digital-prismatic-compass-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Nautical Brass Compass",
    "price": 320.0,
    "description": "Size/Diameter: 70-80 mm | Display Type: Analog | Packaging Size: Box | Material: Brass -- Beautiful finish Durable finish standards",
    "image": "https://5.imimg.com/data5/SELLER/Default/2026/1/576439600/NT/OK/SO/2095577/nautical-brass-compass-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Handheld GPS Device",
    "price": 56000.0,
    "description": "Screen Size: 3.5 Inch | Type: Wireless | Packaging Type: Box | Country of Origin: Made in India -- High-sensitivity dual GPS and GLONASS satellite reception for better performance in challenging environments than GPS alone Redesigned antenna enables better reception and performance; 3-axis compass with accelerometer and barometric altimeter sensors",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/457662140/BM/AE/FQ/2095577/handheld-gps-device-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Weigh Batcher",
    "price": 38500.0,
    "description": "Brand/Make: shreeji | I Deal In: New Only | Country of Origin: Made in India | Surface Treatment: Color Coated | No of Wheel: 4 Wheel -- Specifications: Chassis: Heavy Duty, Robust Steel Chassis from 75X40 mm Channel",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/457440106/YL/AD/GD/2095577/weigh-batcher-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Concrete Weigh Batcher, Capacity: 2 Hoppers Of 250 Kg Each",
    "price": 35500.0,
    "description": "Weighing Capacity: 300 kg | Number Of Hoppers: 2 Hopper | Operation Type: Manual | Capacity: 2 Hopper for 250 kg each for sand & metal | Application: Concrete Batching | Frame Material: Mild Steel | Wheel Type: Pneumatic Tyre | Weight: 1 kg - 50 kg | Automation Grade: Manual | Bucket Type: Twin Bucket | Weighing Type: Dial Type | Surface Finish: Paint Coated | Color: YELLOW | Is It Portable: Non Portable | Country of Origin: Made in India | Wheels: M.S.Wheels - 4 Nos. | Channel Size: 75x40 mm -- Owing to a long-term destination for our business, we are engaged in offering a wide gamut of Concrete Batcher. The offered concrete batcher is designed keeping in mind the standards of market using excellent quality of material. This concrete batcher is valued among customer due to its superior finish. Customers can easily avail this concrete batcher from us in a confine time at nominal rates.Features: Light weight",
    "image": "https://2.imimg.com/data2/FW/BY/MY-2095577/concrete-batcher-weigh-batcher-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Bushnell Speed Radar Gun",
    "price": 18500.0,
    "description": "Type Of Tachometer: Digital Tachometer | Brand: Bushnell | Usage/Application: Laboratory | Operating Time: Up to 20 hours | Operating Temperature Range: -32- 04 F / 0 -40 Degree C -- BALL :- 10-110MPH from 90 Feet/16-177 KPH from 27 Meters CAR :- 10-200 MPH from 1,500 Feet/16-322 KPH from 457 Meters",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455072214/JO/UI/GF/2095577/bushnell-speed-radar-gun-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Night Vision Binocular",
    "price": 85000.0,
    "description": "Color: Black | Brand: Shreeji | Material: Plastic | Is It Waterproof: Waterproof | Width: 2.5 mm | Length: 42 mm | Type: Night Vision -- Comfortable to use over long periods Fine finish",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453503893/YJ/KK/PC/2095577/night-vision-binocular-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Concrete Loading Lift",
    "price": 250000.0,
    "description": "Automation Grade: Manual | Weight: 6100 kg | Brand/Make: Shreeji | Dimension: 7205x1910x32700 mm | Max Speed: 98 (Km/h) | Maximum Power: 72kw / 98hp -- Precise design Robust construction",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453501004/HZ/CB/ZU/2095577/concrete-loading-lift-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "RCC Groove Cutter Machine",
    "price": 50000.0,
    "description": "Power Source: Electric | Brand/Make: SHREEJI | Model Name/Number: SI-2120 | Driving Method: Electric | Brand: Shreeji | Capacity: 14\" | Cutting Material: Concrete | Drive Mechanism: Manual | Handel Adjustable: Yes | Type: Diesel Engine -- Power:7.5 H.P.. 3 phase. 2900 R.P.M. Electric Motor Maximum cutting Depth 5\"",
    "image": "https://5.imimg.com/data5/SELLER/Default/2022/11/SF/CI/PP/2095577/rcc-groove-cutter-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Reverse Drum Concrete Mixer",
    "price": 385000.0,
    "description": "Usage/Application: Construction | Type Of The Drum Mixer: Tilting Drum Mixer | Automatic Grade: Semi-Automatic | Power Source: Diesel Engine | Drum Capacity: 1000 L, 750 L | Chassis Material: Mild Steel -- Simple & easy to operate Compact design",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453501318/RY/OU/YR/2095577/reverse-drum-concrete-mixer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Half Bag Concrete Mixture",
    "price": 95000.0,
    "description": "Power Source: Diesel Engine | Automatic Grade: Manual | Output Capacity: 100-140L | Material: Cast Iron | Brand/Make: Shreeji -- High durability Convinced optimized design",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453798359/ZS/HL/BG/2095577/half-bag-concrete-mixture-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Vibrating Earth Compaction Rammer",
    "price": 38500.0,
    "description": "Engine Power: 3 Hp | Material: Hard Iron | Weight: 850 kg | Phase: 3 Phase | Depth: 6 Inch | Compaction Area: 4000 sq.ft. -- These models are available in three different types: Little Giant Model: Impact force: 850kg. Depth effect: 6 inch, Compaction area: 4000 sq.ft.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455074988/GG/PH/IF/2095577/vibrating-earth-compaction-rammer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Pan Mixer",
    "price": 85000.0,
    "description": "Capacity: 350 L | Material: Stainless Steel | Usage/Application: Construction | Size: 1100x1050(high) mm | Brand: shreeji | Power Source: electrical | Power: 8.8 kW | Country of Origin: Made in India | Rotational Speed: 1440r/min | Drum Rotating Speed: 20 RPM | Batch Capacity: 100 Kg | Weight: 300kg",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452901438/GF/YW/QZ/2095577/pan-mixer-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Single Wheel Barrow",
    "price": 5200.0,
    "description": "Tray Capacity: 90 L | Load Capacity: 150 kg | Wheel Type: Pneumatic | Number Of Wheels: 1 | Tray Material: Mild Steel | Frame Material: Mild Steel | Loading Capacity(kg): 50 kg, 0.1 cum | Tray Shape: Pan | Wheel Diameter: 12 inch | Handle Type: Straight | Application: Construction | Type: Wheel Berrow Single Tyre | Chassis: Heavy Duty,Robust Steel Chassis angle and pipe structure | Body: Made from 14 swg sheet | Wheels: 3.5 x 8 Pneumatic Wheels(Scooter Tyre)- 1 No. -- Enhanced service life Operational fluency",
    "image": "https://2.imimg.com/data2/OP/BC/MY-2095577/wheel-barrow-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Concrete Groove Cutter",
    "price": 48000.0,
    "description": "Brand: Shreeji | Cutting Material: Concrete | Speed: 2800 RPM | Cutting Disc: 14 Inch or 16 Inch | Motor Power: 7.5HP - 10HP | Phase: Three Phase | Depth: 4 Inch -- Concrete Groove Cutter machine is also known as concrete cutter. It is mainly used to cut Grooves in RCC roads. Specifications:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455080582/UT/IO/GG/2095577/concrete-groove-cutter-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Bosch Gsh 11 E Demolition Hammer",
    "price": 30000.0,
    "description": "Tool Length: All Leanth | Weight: 5 kg,15 kg | Usage/Application: Laboratory | Brand: Bosch | Material: Brass | Warranty: 1 month",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453187876/BC/LA/GI/2095577/bosch-demolition-hammers-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Tower Hoist Concrete Lift",
    "price": 200000.0,
    "description": "Power Source: Electric | Capacity: 1000 kg | Motor Power: 15 H.P. /18 H.P. | Wire Rope: 12mm | Speed: 33 meters/min | Engine Power: 12.5 H.P.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453483889/JM/OL/SS/2095577/tower-hoist-concrete-lift-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Jaw Crusher Machine",
    "price": 75000.0,
    "description": "Voltage: 220/240V | Automation Grade: Automatic | Frequency: 60Hz | Country of Origin: Made in India | Surface Treatment: Color Coated -- Designed to speed up crushing of Aggregates, Ores, Mineral, Coal and Similar Materials Compact and rugged for laboratory and small production units",
    "image": "https://5.imimg.com/data5/SELLER/Default/2026/1/576437436/XD/AI/JR/2095577/jaw-crusher-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Testing Sieves",
    "price": 800.0,
    "description": "Aperture Size: 90 \u00b5m | Sieve Diameter: 8 inch | Frame Material: Brass | Diameter.: 8\" | Mesh Type: Wire Mesh | Mesh Material: Brass | Compliance Standard: IS 460 | Mesh Size: 1mm | Material: Brass | Brand: shreeji,jayant | Frame Thickness: 1-2 mm | Hole Shape: Round | Country of Origin: Made in India -- Compact design Easy operation",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/457443961/CQ/ZN/QR/2095577/testing-sieves-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Length Thickness Gauge",
    "price": 1100.0,
    "description": "Material: Brass | Brand: Shreeji | Country of Origin: Made in India | Packaging Type: Box | Usage: Laboratory",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451666438/JD/NI/JV/2095577/length-thickness-gauge-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Nautical Brass Alidade",
    "price": 2250.0,
    "description": "Type: Plain | Material: Brass | Packaging Type: Box | Diameter of Lens: 2mm | Usage/Application: Land Survey | Brand: Shreeji | Size: 9 Inch | Height: 5 Inch -- Excellent in quality Fine finish",
    "image": "https://2.imimg.com/data2/GG/CU/MY-2095577/alidade-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Handheld Digital Compass",
    "price": 2100.0,
    "description": "Material: Plastic | Usage/Application: Laboratory | Display Type: Digital | Weight: 70 gm | Dimension: 2.1x3.25x.88 inches WxHxD | Brand: Bushnell -- Superior performance Easy installation",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453184620/ER/TR/HZ/2095577/handheld-digital-compass-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Leveling Staff",
    "price": 1500.0,
    "description": "Model Name/Number: AS-43 | I Deal In: New Only | Measuring range: 4 (Mtr) | Country of Origin: Made in India | Weight: 1.5 Kg Approx -- Superior performance Robust construction",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/457436530/PY/ML/AV/2095577/leveling-staff-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Steel Bar Bending Machine",
    "price": 135000.0,
    "description": "Max Bending Capacity: 40 mm | Automation Grade: Automatic | Max Bending Radius: 50 mm | Power Source: Electric | Country of Origin: Made in India | Body Material: Iron",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/8/447238391/FC/MC/SD/2095577/bar-bending-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Industrial Digital Theodolite",
    "price": 65000.0,
    "description": "Model Name/Number: DT-02 | Magnification: 32X | Distance Measurement: 500 m | Angle Measurement Accuracy: 5 second | Display Panel: Double Side | Brand: LANGRY | LCD Display Panel: Double Side | Minimum Readout: 0.5 mm | Battery Backup: 36 hrs | Usage/Application: Surveying | Material: Brass | Measuring Time: 0.5 second | Operating Temperature Range: -20 DegreeC -- 50 DegreeC | Battery Type: Li-ion Rechargeable | Weight: 3KG | Warranty: 1 year | Battery Operation: YES | Keyboard: 2 | Least Count: 1\"",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/8/445423799/OY/GI/IA/2095577/digital-theodolite-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Survey Prism Big",
    "price": 9500.0,
    "description": "Product Type: Total Station | Packaging Type: Box | Power Source: Battery | Ingress Protection: IP55 | Color: Red and Yellow | Tripod Included: No | Carrying Case: Hard Case | Country Of Origin: India | Body Material: ABS,SS | Size: L 100mm-500mm,W 10mm-500mm | Usage: Land Survey -- Quartz plate with round size diameter 10mm-500mm Quartz plate with square size L: 100mm-500mm W: 10mm-500mm",
    "image": "https://5.imimg.com/data5/CY/OT/EU/SELLER-2095577/reflecting-prism-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Compression Testing Machine",
    "price": 36500.0,
    "description": "Capacity: 1000 kN | Automation Grade: Manual | Display Type: Analog | Usage/Application: CONCRET CUBE TESTING | Gauge: MASTA | Brand: SHREEJI | Material: IRON | Country of Origin: Made in India -- Being a well established-organization, we are engaged in manufacturing and supplying Electric Cube Testing Machine Dual Gauges. Our offered gauges are manufactured utilizing finest quality material and advanced technology following the standards of industry. The gauges offered by us are ideal for concrete, cement, tube, brick compression and flexural test. We are giving these gauges from us on diverse specifications. Innovative design",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/8/441937976/RN/XU/TN/2095577/1500-kn-hand-ctm-500x500.jpeg",
    "hsn_code": "9031"
  },
  {
    "name": "Digital Compression Testing Machine",
    "price": 130000.0,
    "description": "Control Type: Semi automatic | Phase: Single phase | Service Location: gujarat | Max Cube Size: 300 mm | Accuracy Class: Class 1 | Data Output: RS232 | Display Type: Digital | Usage/Application: CUBE TESTING MACHINE CALIBRATION | Gauge: ALL INSTRUMENTS CALIBRATION | Mode Of Repairing: Replacing Damaged Parts | Capacity: 1000kn , 2000kn , 3000 kn",
    "image": "https://5.imimg.com/data5/SELLER/Default/2021/11/JM/SA/IQ/2095577/digital-compression-testing-machine-repair-service-500x500.JPG",
    "hsn_code": "9031"
  },
  {
    "name": "Topcon Total Station Os 101 in Vadodara",
    "price": 620000.0,
    "description": "Model Name/Number: OS-101 | Distance Measurement: 5 km | Brand: TOPCON | Angle Measurement Accuracy: 1 second | Battery Backup: 20 hrs | Operating Temperature Range: -20 DegreeC -- 50 DegreeC | Usage/Application: Land Survey -- With the latest total station technology in an IP65 rated housing, the OS can handle whatever you need to accomplish. Built for on-the-go tasks with a far reaching, precise firing EDM, extended use batteries, and data collection software right on the instrument. Take charge of sites with world-class accuracy from the OS series by Topcon. Modern, intuitive MAGNET\u00ae Field on-board data collection software",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455095853/BK/RS/ZU/2095577/topcon-total-station-os-101-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Topcon Total Station Gm 105",
    "price": 390000.0,
    "description": "Model: GM-55 | Model Name/Number: GM-55 | Angle Accuracy: 5\u2033 | Prism Range: 5000 m | Magnification: 24X | Resolving Power: 2.5 inch | Reflectorless Range: 500 m | Distance Measurement: 1 km | Distance Accuracy: \u00b11 mm+1 ppm | Number of Prism: Double | Data Storage: USB Disk | Angle Measurement Accuracy: 2 second | Material: Brass | Communication: USB | Minimum Readout: 0.5 mm | Minimum Focus: 0.5m | Battery Backup: 36 hrs | LCD Display Panel: Double Side | Usage/Application: Surveying | Operating Temperature Range: -20 DegreeC -- 50 DegreeC | Battery Type: Li-ion Rechargeable | Warranty: 1 year -- With the help of our state-of-the-art infrastructure unit, we are able to export, trade and supply the superlative quality of Total Station Battery. To maintain industry defined quality standards, the entire range is manufactured using quality proven components and cutting-edge technology. Also, the entire range is examined properly by our quality experts upon distinct parameters to ensure the long life of battery. Moreover, these batteries are manufactured in such a manner to meet with the safety measures and powerful backup. Specifications:",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/3/396523256/HT/EH/GR/2095577/land-surveying-instruments-ahmedabad-500x500.jpeg",
    "hsn_code": "9031"
  },
  {
    "name": "Topcon Total Station",
    "price": 390000.0,
    "description": "Model: GM-55 | Model Name/Number: GM-55 | Angle Accuracy: 5\u2033 | Prism Range: 5000 m | Reflectorless Range: 500 m | Distance Measurement: 5 km | Distance Accuracy: \u00b11 mm+1 ppm | Data Storage: USB Disk | Brand: TOPCON | Usage/Application: Land Survey | Angle Measurement Accuracy: 5 second | Battery Backup: 36 hrs | Operating Temperature Range: -20 DegreeC -- 50 DegreeC | Weight: 5.6 Kg | Battery Type: Li-ion Rechargeable",
    "image": "https://5.imimg.com/data5/SELLER/Default/2022/9/NU/BV/AM/2095577/topcon-total-station-gm105-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Big Prism For Total Station",
    "price": 6500.0,
    "description": "Usage/Application: Laboratory | Packaging Type: Box | Brand: Shreeji | Material: SS, Aluminium | Surface Treatment: Color Coated",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453484437/UQ/XJ/OV/2095577/big-prism-for-total-station-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Road Barriers Tape",
    "price": 300.0,
    "description": "Material: PVC | Usage/Application: Road Barriers | Brand: Shree Ji | Pattern: Printed | Packaging Type: Roll | Size: 250 miter 500 miter , 100 miter | Color: Red/ White,Yellow / White -- High durability Optimum quality",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453789121/KT/QH/DO/2095577/road-barriers-tape-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Plastic Fence Barrier",
    "price": 12500.0,
    "description": "Material: FRP | Size: L 2000 X H 1200 mm | Color: Red, Yellow | Brand: Lion | Code: 2041 | Weight: 12 Kg | Base width: 50 mm | Standard Packing: 12 Pieces | Retail Packing: 08 Pieces -- We are a noteworthy organization in the domain, occupied in providing superior quality Laboratory Scales. Our offered laboratory scales are manufactured utilizing finest quality material and advanced technology following the standards of industry. The laboratory scales offered by us responds quickly with accurate results. We are giving these laboratory scales from us on diverse specifications.Features: Excellent performance",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452899399/NP/KE/ZK/2095577/plastic-fence-barrier-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Shreeji PVC Speed Bump",
    "price": 450.0,
    "description": "Color: Black | No. Of Bolts: 4 | Bearing Capacity: 2 TOONE | Material: Plastic | Country of Origin: Made in India",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/452905686/XK/CB/WP/2095577/shreeji-pvc-speed-bump-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Laboratory CBR Test Apparatus",
    "price": 38500.0,
    "description": "Material: Steel | Brand: SHREEJI | Power Source: Electric | Voltage (V): 280 V | Frequency (Hz): 50 hz -- High efficiency Hassle free performance",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455082171/MU/LS/IH/2095577/laboratory-cbr-test-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "California Bearing Ratio Test Apparatus",
    "price": 52000.0,
    "description": "Material: Mild Steel | Horizontal Clearance: 255 mm | Gauge: 0.01 * 25mm | Dimensions: 12 x 18 x 42 in (31 x 46 x 107 cm) (L x W x H) | Maximum Vertical Clearance: 800 mm -- Relevant Standard IS: 2720 p-xvi Accurate dimensions",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451666791/SJ/ZS/SJ/2095577/california-bearing-ratio-test-apparatus-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Gold Metal Detector",
    "price": 75000.0,
    "description": "Range: 10 Feet | Alarm Mode: Sound | Model Name/Number: 4 meter, 6 meter, 2 meter, 8 meter | Country of Origin: Made in India | Feature: Built in Speaker, Rechargeable Batteries, Sensitivity Adjustment | Display Type: Digital -- Detection Alarm Metal Type Recognition: All Metals",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/456869735/XH/QJ/CN/2095577/gold-metal-detector-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Nikon Auto Level",
    "price": 22500.0,
    "description": "Accuracy per km: 1.5 mm | Compensator range: \u00b115 arc min | Objective aperture: 32 mm | Shortest focus: 1.5 m | Dust water rating: IP55 | Brand: NIKON | Includes tripod: Yes | Includes staff: Yes | Staff length: 4 m | Country of Origin: Made in India | Magnification: 28x | Stadia Ratio: 1 Ratio 100 | Tube Length: 190 mm (7.5 in) -- The Nikon AX-2S. AC-2S and AP-8 Automatic Levels for construction are highly affordable, highly accurate, and capable of withstanding severe conditions, including rain, extreme temperatures, dust, and vibration. Featuring magnetic-dampened com pensators and rugged metal housing as well as built-in bubble mirrors and sighting lines, these auto-levels are exceptionally easy to set up and use in any environment. They are ideal for a variety of construction and survey-",
    "image": "https://5.imimg.com/data5/EG/TM/MY-2095577/nikon-auto-level-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Road Safety Traffic Cone",
    "price": 310.0,
    "description": "Shape: pvc | Color: Red | Material: PVC | Usage/Application: Road Safety | Flexibility: Yes",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/451607394/VG/BM/TY/2095577/road-safety-traffic-cone-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Safety Information Board",
    "price": 300.0,
    "description": "Material: PVC | Usage/Application: Laboratory | Shape: Rectangular | Film Type: Reflective | Installation Type: Wall Mounted -- Weather resistance Flawless finish",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453800404/CM/ES/RX/2095577/safety-information-board-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Automatic Stirrup Bar Bending Machine",
    "price": 54000.0,
    "description": "Automation Grade: Automatic | Max Bending Radius: 8MM TO 18 MM | Capacity: 8 MM TO 18 MM | Model Name/Number: SI 18 | Weight: 200 | Voltage: 3PH | Material: STEEL | Usage/Application: Industrial -- Bending rebar diameter Plate diameter",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453300740/HF/OI/JY/2095577/stirrup-bar-bending-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Tmt Steel Bar Cutting Machine",
    "price": 140000.0,
    "description": "Cutting Disc Size: 5 inch | Machine Type: Fully Automatic | Model Name/Number: SI 42 | Weight: 500 | Warranty: 6 months | Country of Origin: Made in India",
    "image": "https://5.imimg.com/data5/SELLER/Default/2025/6/522272217/RV/SR/QG/2095577/steel-bar-tmt-cutting-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Tmt Bar Cutting Machine",
    "price": 110000.0,
    "description": "Phase: Three Phase | Blade: Heavy duty alloy steel 3 x 3 x 1\" Blade | Power: 5 HP | Cut Capacity: Upto 32 mm | Strokes per Minute: 25 to 30 | Length: 1350 mm | Width: 360 mm | Height: 870 mm -- Shreeji instruments are one of the leaders in Bar Cutting machines that feature rugged built body holding its life on any construction site. Widely used for cutting reinforcement steel this machine can cut bars in any specified diameters. Further, it's durability & accuracy in cutting makes us stand ahead of our counterparts in the industry.",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/457437715/MP/GO/YN/2095577/tmt-bar-cutting-machine-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Sokkia Sdl50 Digital Auto Level",
    "price": 120000.0,
    "description": "Magnification: 32X | Usage/Application: Leveling | Accuracy per km: 1.5 mm | Compensator range: \u00b115 arc min | Packaging Type: Box | Objective aperture: 32 mm | Shortest focus: 1.5 m | Dust water rating: IP55 | Brand: Sokkia | Model Name/Number: SOKKIA | Minimum Focus: 250 METER | Includes tripod: Yes | Includes staff: Yes | Staff length: 4 m | Packaging Size: 1",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/10/455083476/VF/WE/AI/2095577/sokkia-sdl50-digital-auto-level-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Sokkia Auto Level",
    "price": 20500.0,
    "description": "Instrument Type: Auto Level | Brand: RUUNER | Angle Accuracy: 1\u2033 | Packaging Type: Box | Distance Accuracy: \u00b11 mm | Measurement Range: Up to 300 m | Data Storage: No Storage | Usage/Application: AUTOMATICE LEVELING | Material: Brass -- 24x Magnification; 2.0 mm Standard Deviation per km (double-run leveling), Telescope: 36 mm clear objective aperture,",
    "image": "https://5.imimg.com/data5/SELLER/Default/2026/5/611432175/JU/BV/WJ/2095577/land-surveying-instruments-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Geomax Automatic Level",
    "price": 16500.0,
    "description": "Usage/Application: Leveling | Accuracy: 2.0 mm | Brand: LEICA | Magnification: 32x | Min focus: 1.0m | Horizontal circle: 360 deg | Use: Industrial -- Compact design Easy operation",
    "image": "https://5.imimg.com/data5/SELLER/Default/2024/9/453485897/LN/FM/KL/2095577/geomax-automatic-level-500x500.jpg",
    "hsn_code": "9031"
  },
  {
    "name": "Leica Auto Levels",
    "price": 23500.0,
    "description": "Usage/Application: Land Survey | Brand: LEICA | Country of Origin: Made in India | Compensator Setting Accuracy: +/-0.3\" | Sensitivity of Bubble: 8'/2mm",
    "image": "https://5.imimg.com/data5/OU/HG/IG/SELLER-2095577/leica-auto-levels-500x500.jpg",
    "hsn_code": "9031"
  }
];


$inserted = 0;
$updated = 0;
$errors = 0;

$stmt_check = $con->prepare("SELECT instrument_id FROM instruments WHERE company_id = ? AND instrument_name = ?");
$stmt_insert = $con->prepare("INSERT INTO instruments (company_id, instrument_name, price, description, image, hsn_code) VALUES (?, ?, ?, ?, ?, ?)");
$stmt_update = $con->prepare("UPDATE instruments SET price = ?, description = ?, image = ?, hsn_code = ? WHERE instrument_id = ? AND company_id = ?");

if (!$stmt_insert) {
    die("<div style='color:#f87171;'>Prepare Statement Error: " . htmlspecialchars($con->error) . "</div></div></body></html>");
}

echo "<table>";
echo "<tr><th>#</th><th>Status</th><th>Instrument Name</th><th>Price (INR)</th><th>Company ID</th><th>User ID</th></tr>";

foreach ($items as $idx => $item) {
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
            $status_td = "<span class='badge-updated'>UPDATED</span>";
        } else {
            $errors++;
            $status_td = "<span class='badge-error'>ERROR</span>";
        }
    } else {
        $stmt_insert->bind_param("isdsss", $company_id, $name, $price, $desc, $img, $hsn);
        if ($stmt_insert->execute()) {
            $inserted++;
            $status_td = "<span class='badge-inserted'>INSERTED</span>";
        } else {
            $errors++;
            $status_td = "<span class='badge-error'>ERROR</span>";
        }
    }
    
    $row_num = $idx + 1;
    echo "<tr><td align='center'>{$row_num}</td><td align='center'>{$status_td}</td><td>" . htmlspecialchars($name) . "</td><td align='right'>₹ " . number_format($price, 2) . "</td><td align='center'>{$company_id}</td><td align='center'>{$target_user_id}</td></tr>";
}

echo "</table>";

$total_processed = count($items);

echo "<div class='summary-card'>";
echo "<h2 style='margin-top:0; color:#34d399;'>🎉 Products Loaded Successfully!</h2>";
echo "<p style='font-size:15px;'><strong>User ID:</strong> {$target_user_id} | <strong>Company ID:</strong> {$company_id}</p>";
echo "<p style='font-size:15px;'><strong>New Instruments Inserted:</strong> <span style='color:#34d399; font-weight:bold;'>{$inserted}</span></p>";
echo "<p style='font-size:15px;'><strong>Existing Instruments Updated:</strong> <span style='color:#38bdf8; font-weight:bold;'>{$updated}</span></p>";
echo "<p style='font-size:15px;'><strong>Total Products Processed:</strong> <span style='color:#2dd4bf; font-weight:bold;'>{$total_processed}</span></p>";
echo "<p style='margin-top:20px;'><a href='home.php#products' class='btn-dash'>Go to Dashboard to View Products →</a></p>";
echo "</div>";

$con->close();
?>
</div>

<script>
    // Show visual popup notification when page finishes loading
    window.addEventListener('load', function() {
        setTimeout(function() {
            alert('🎉 SUCCESS!

All <?php echo $total_processed; ?> Products have been loaded successfully into the database for User ID <?php echo $target_user_id; ?>!

- Inserted: <?php echo $inserted; ?>
- Updated: <?php echo $updated; ?>');
        }, 300);
    });
</script>
</body>
</html>
