<?php
/**
 * Admin Dashboard Controller
 * Handles dashboard statistics and data via AJAX
 */

header('Content-Type: application/json');

// Allow CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Book.php';
require_once __DIR__ . '/../../models/BookLending.php';
require_once __DIR__ . '/../../models/BookReturn.php';
require_once __DIR__ . '/../../models/Penalty.php';
require_once __DIR__ . '/../../models/User.php';

$database = new Database();
$db = $database->getConnection();

$book = new Book($db);
$lending = new BookLending($db);
$bookReturn = new BookReturn($db);
$penalty = new Penalty($db);
$user = new User($db);

$action = isset($_GET['action']) ? $_GET['action'] : 'stats';

try {
    switch ($action) {
        case 'stats':
            // Get dashboard statistics
            $stats = [
                'total_books' => $book->countBooks(),
                'available_books' => $book->countAvailableBooks(),
                'total_members' => $user->countMembers(),
                'active_borrowings' => $lending->countActiveBorrowings(),
                'overdue_borrowings' => $lending->countOverdueBorrowings(),
                'total_fines' => $penalty->formatRupiah($penalty->getTotalUnpaidFines()),
                'total_fines_raw' => $penalty->getTotalUnpaidFines()
            ];
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'recent_borrowings':
            // Get recent borrowings for dashboard
            $stmt = $lending->getRecentBorrowings(5);
            if ($stmt) {
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                echo json_encode(['success' => true, 'data' => []]);
            }
            break;

        case 'overdue':
            // Get overdue borrowings for dashboard
            $stmt = $lending->getOverdueBorrowings();
            if ($stmt) {
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // Add calculated fines
                $fine_per_day = $penalty->getFinePerDay();
                foreach ($data as &$item) {
                    $item['fine_amount'] = $item['days_overdue'] * $fine_per_day;
                    $item['fine_formatted'] = $penalty->formatRupiah($item['fine_amount']);
                }
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                echo json_encode(['success' => true, 'data' => []]);
            }
            break;

        case 'borrowings':
            // Get all active borrowings
            $stmt = $lending->getActiveBorrowings();
            if ($stmt) {
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                echo json_encode(['success' => true, 'data' => []]);
            }
            break;

        case 'returns':
            // Get all returns
            $stmt = $bookReturn->getAllReturns();
            if ($stmt) {
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                echo json_encode(['success' => true, 'data' => []]);
            }
            break;

        case 'fines':
            // Get all fines/penalties
            $stmt = $penalty->getPenaltiesWithDetails();
            if ($stmt) {
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // Format currency
                foreach ($data as &$item) {
                    $item['fines_formatted'] = $penalty->formatRupiah($item['large_fines']);
                }
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                echo json_encode(['success' => true, 'data' => []]);
            }
            break;

        case 'members':
            // Get all members
            $stmt = $user->getAllMembers();
            if ($stmt) {
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                echo json_encode(['success' => true, 'data' => []]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("AdminDashboardController Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>
