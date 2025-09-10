<?php

namespace App\Core; 

class Controller 
{
    
    public function view($view, $data = [])
    {
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';

        if (file_exists($viewFile)) {
            extract($data);
            require_once $viewFile;
        } else {
            die("View não encontrada: " . $viewFile);
        }
    }
}