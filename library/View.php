<?php

class View {

    function __construct() {
        Session::start();
        $this->db = new Database();
    }

    public function render($name, $noInclude = false) {
        if ($noInclude == true) {
            require 'views/' . $name . '.php';
        } else {
            $this->menulist = $this->MenuConstruct();
            require 'views/header.php';
            require 'views/sidebar.php';
            require 'views/' . $name . '.php';
            require 'views/footer.php';
        }
    }

    public function MenuConstruct() {
        $access_level = Session::get('access_level');
        $x = 0;
        $m_id = $this->db->SelectData("SELECT allowed_access FROM csh_user_levels WHERE access_denotor=:id",
                array('id' => $access_level));
        $menu_set = explode(',', $m_id[0]['allowed_access']);

        foreach ($menu_set as $key => $value) {
            $res = $this->db->SelectData("SELECT * FROM csh_access_rights WHERE parent_option = 0 AND id=:id", array('id' => $value));
            if (count($res)) {
                $menu[$x]['id'] = $res[0]['id'];
                $menu[$x]['menu_title'] = $res[0]['menu_title'];
                $menu[$x]['load_page'] = $res[0]['load_page'];
                $menu[$x]['tier'] = $res[0]['tier'];
                $menu[$x]['parent_option'] = $res[0]['parent_option'];
                $menu[$x]['rank'] = $res[0]['rank'];
                $menu[$x]['css'] = $res[0]['css'];
                $x++;
            }
        }
        return $menu;
    }

    public function GetSubMenu($id) {
        $access_level = Session::get('access_level');
        $x = 0;

        $m_id = $this->db->SelectData("SELECT allowed_access FROM csh_user_levels WHERE access_denotor=:id", array('id' => $access_level));
        $menu_set = explode(',', $m_id[0]['allowed_access']);

        foreach ($menu_set as $key => $value) {
            $res = $this->db->SelectData("SELECT * FROM csh_access_rights WHERE parent_option = :parent_option AND id=:id", array('parent_option' => $id, 'id' => $value));
            if (count($res)) {
                $menu[$x]['id'] = $res[0]['id'];
                $menu[$x]['menu_type'] = $res[0]['menu_type'];
                $menu[$x]['menu_title'] = $res[0]['menu_title'];
                $menu[$x]['load_page'] = $res[0]['load_page'];
                $menu[$x]['tier'] = $res[0]['tier'];
                $menu[$x]['parent_option'] = $res[0]['parent_option'];
                $menu[$x]['rank'] = $res[0]['rank'];
                $menu[$x]['css'] = $res[0]['css'];
                $x++;
            }
        }
        return $menu;
    }

    function GetCasscadingMenu($id) {
        $x = 0;
        $user_id = $_SESSION['uid'];
        $res = $this->db->SelectData("SELECT * FROM csh_access_rights WHERE id=:id", array('id' => $id));
        if ($res[0]['menu_title'] == 'Students List') {
            $opt_array = $this->db->SelectData("SELECT * FROM csh_school s JOIN csh_level_classes c
                ON  s.school_sections=c.level_id WHERE school_id =:sid", array('sid' => $user_id));
        }

        foreach ($opt_array as $key => $value) {
            $menu[$x]['id'] = $res[0]['id'];
            $menu[$x]['menu_title'] = $value['class_name'].' Class List';
            $menu[$x]['load_page'] = $res[0]['load_page'].'/'.$value['class_id'];
            $x++;
        }

        return $menu;
    }
}
?>
