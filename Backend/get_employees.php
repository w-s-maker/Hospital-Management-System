<?php
// Include the database connection
require_once 'db_connect.php';

// Get pagination, search, and filter parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Number of records per page
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$employee_id = isset($_GET['employee_id']) ? trim($_GET['employee_id']) : '';
$employee_name = isset($_GET['employee_name']) ? trim($_GET['employee_name']) : '';
$role = isset($_GET['role']) ? trim($_GET['role']) : '';

// Build the base query using UNION
$query = "
    SELECT 
        d.id,
        d.staff_id,
        CONCAT(d.first_name, ' ', d.last_name) AS full_name,
        d.email,
        d.contact_number,
        d.created_at,
        'Doctor' AS role
    FROM doctors d
    WHERE 1=1
    UNION
    SELECT 
        n.id,
        n.staff_id,
        CONCAT(n.first_name, ' ', n.last_name) AS full_name,
        n.email,
        n.contact_number,
        n.created_at,
        'Nurse' AS role
    FROM nurses n
    WHERE 1=1
    UNION
    SELECT 
        r.id,
        r.staff_id,
        CONCAT(r.first_name, ' ', r.last_name) AS full_name,
        r.email,
        r.contact_number,
        r.created_at,
        'Receptionist' AS role
    FROM receptionists r
    WHERE 1=1
    UNION
    SELECT 
        a.id,
        a.staff_id,
        CONCAT(a.first_name, ' ', a.last_name) AS full_name,
        a.email,
        a.contact_number,
        a.created_at,
        'Admin' AS role
    FROM admins a
    WHERE 1=1
";

// Build conditions for each table dynamically
$conditions = [];
$params = [];

if (!empty($search)) {
    $searchCondition = "(CONCAT(first_name, ' ', last_name) LIKE :search OR staff_id LIKE :search OR email LIKE :search OR contact_number LIKE :search)";
    $params[':search'] = "%$search%";
    $conditions[] = $searchCondition;
}

if (!empty($employee_id)) {
    $conditions[] = "staff_id LIKE :employee_id";
    $params[':employee_id'] = "%$employee_id%";
}

if (!empty($employee_name)) {
    $conditions[] = "CONCAT(first_name, ' ', last_name) LIKE :employee_name";
    $params[':employee_name'] = "%$employee_name%";
}

if (!empty($role)) {
    $conditions[] = "role = :role";
    $params[':role'] = $role;
}

// Apply conditions to each WHERE clause in the UNION
$whereClause = !empty($conditions) ? " AND " . implode(" AND ", $conditions) : "";
$query = str_replace("WHERE 1=1", "WHERE 1=1 $whereClause", $query);

// Add sorting, limit, and offset
$query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$params[':limit'] = $limit;
$params[':offset'] = $offset;

// Count query for pagination (also apply filters)
$countQuery = "
    SELECT COUNT(*) FROM (
        SELECT id FROM doctors WHERE 1=1 $whereClause
        UNION
        SELECT id FROM nurses WHERE 1=1 $whereClause
        UNION
        SELECT id FROM receptionists WHERE 1=1 $whereClause
        UNION
        SELECT id FROM admins WHERE 1=1 $whereClause
    ) AS combined
";

// Prepare and execute the main query
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    if ($key === ':limit' || $key === ':offset') {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
}
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare and execute the count query
$countStmt = $pdo->prepare($countQuery);
foreach ($params as $key => $value) {
    if ($key !== ':limit' && $key !== ':offset') {
        $countStmt->bindValue($key, $value, PDO::PARAM_STR);
    }
}
$countStmt->execute();
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Format the response
$response = [
    'employees' => [],
    'totalPages' => $totalPages,
    'currentPage' => $page
];

foreach ($employees as $employee) {
    $response['employees'][] = [
        'id' => $employee['id'],
        'staff_id' => $employee['staff_id'],
        'full_name' => $employee['full_name'],
        'email' => $employee['email'],
        'contact_number' => $employee['contact_number'],
        'created_at' => date('d M Y', strtotime($employee['created_at'])),
        'role' => $employee['role']
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;