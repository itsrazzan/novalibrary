<?php
// File: src/Core/Controller.php
namespace App\Core;

class Controller 
{
    /**
     * Memuat file View dari folder src/views/
     * @param string $view Nama view dengan path relatif (misal: 'home/index' atau 'templates/header')
     * @param array $data Data yang akan diteruskan ke view
     */
    protected function view($view, $data = [])
    {
        // Ubah array $data menjadi variabel lokal ($title, $user, dll.)
        extract($data); 

        // Tentukan path absolut ke file view.
        // Asumsi struktur: Core -> .. -> src -> views
        $viewFile = __DIR__ . '/../views/' . $view . '.php';

        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            // Ini adalah penanganan error sederhana jika view tidak ditemukan
            echo "Error: View file tidak ditemukan di $viewFile";
            die();
        }
    }
}