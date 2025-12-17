<?php
include_once 'config/database.php';
include_once 'models/User.php';

class UserController {
    private $userModel;

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->userModel = new User($db);
    }

    public function index() {
        $stmt = $this->userModel->read();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        include 'views/index.php';
    }

    public function create() {
        include 'views/create.php';
    }

    // UPDATE: Menambahkan Validasi
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $errors = [];

            // Validasi sederhana
            if (empty($name)) {
                $errors[] = "Nama wajib diisi.";
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Format email tidak valid.";
            }

            // Jika tidak ada error, simpan
            if (empty($errors)) {
                $this->userModel->create($name, $email);
                header("Location: index.php");
            } else {
                // Jika error, kembali ke view create dengan membawa pesan error
                include 'views/create.php';
            }
        }
    }

    public function edit($id) {
        $user = $this->userModel->show($id);
        include 'views/edit.php';
    }

    // UPDATE: Menambahkan Validasi pada Update
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $errors = [];

            if (empty($name)) $errors[] = "Nama wajib diisi.";
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email tidak valid.";

            if (empty($errors)) {
                $this->userModel->update($id, $name, $email);
                header("Location: index.php");
            } else {
                // Ambil data lama agar form tidak kosong saat error
                $user = ['id' => $id, 'name' => $name, 'email' => $email];
                include 'views/edit.php';
            }
        }
    }

    public function delete($id) {
        $this->userModel->delete($id);
        header("Location: index.php");
    }
}
?>