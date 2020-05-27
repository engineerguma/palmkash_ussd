<?php

class Session {

    public static function start() {
        @session_start();
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key) {
        return $_SESSION[$key];
    }

    public static function destroy() {
        session_destroy();
    }

    public function checksession() {

        //If you are logged in
        if (!isset($_SESSION['loggedin'])) {
            echo 'Logged Out';
            header("Location:" . URL . 'login');
        }
    }

}

?>
