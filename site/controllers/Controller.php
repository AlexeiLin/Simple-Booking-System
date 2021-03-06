<?php

    class Controller {
    
        protected $params;
        protected $root;
        protected $siteURL;
        protected $user;

        private $registerMessage = "";
        
        public function __construct($params) {
            View::$root = dirname(__FILE__)."/../tpl/";
            $this->params = $params;
            $this->root = dirname(__FILE__)."/../";
            $this->siteURL = "http://".$_SERVER["HTTP_HOST"];
            $this->initMysql();
            if($this->isLogin()) {
                $this->run();
            } else {
                $this->renderLayout(view("login.html", array(
                    "registerMessage" => $this->registerMessage
                )));
            }
        }
        protected function run() {
            
        }
        protected function renderLayout($content) {
            $mainMenuAddons = '';
            if($this->isAdmin()) {
                $mainMenuAddons = view("adminsmenu.html", array(
                    "siteURL" => $this->siteURL
                ));
            }
            die(view("layout.html", array(
                "content" => $content,
                "siteURL" => $this->siteURL,
                "main-menu-addons" => $mainMenuAddons
            )));
        }
        protected function isLogin() {         
            $action = isset($_POST["action"]) ? $_POST["action"] : "";
            switch($action) {
                case "login":
                    $password = isset($_POST["password"]) ? $_POST["password"] : "";
                    $email = isset($_POST["email"]) ? $_POST["email"] : "";
                    if($this->getCurrentUser($password, $email)) {
                        $_SESSION["futbolsessionpassword"] = $password;
                        $_SESSION["futbolsessionemail"] = $email;
                        return true;
                    };
                break;
                case "register":
                    $name = isset($_POST["nameRegister"]) ? $_POST["nameRegister"] : "";
                    $password = isset($_POST["passwordRegister"]) ? $_POST["passwordRegister"] : "";
                    $phone = isset($_POST["phoneRegister"]) ? $_POST["phoneRegister"] : "";
                    $email = isset($_POST["emailRegister"]) ? $_POST["emailRegister"] : "";
                    if($name == "" || $password == "" || $phone == "" || $email == "") {
                        $this->registerMessage = '<div class="alert alert-error">Моля попълнете всички полета.</div>';
                        return false;
                    }
                    $q = "SELECT * FROM futbol_users WHERE email = '".$email."'";
                    $res = $this->query($q);
                    if(count($res->result) > 0) {
                        $this->registerMessage = '<div class="alert alert-error">Вече има създаден акаунт асоцииран с <b><u>'.$email.'</u></b>.</div>';
                        return false;   
                    }
                    $q = "INSERT INTO futbol_users (name, password, email, phone) VALUES ('".$name."', '".$password."', '".$email."', '".$phone."')";
                    $this->query($q);
                    $_SESSION["futbolsessionpassword"] = $password;
                    $_SESSION["futbolsessionemail"] = $email;
                    return true;
                break;
                default:
                    if($this->getCurrentUser()) {
                        return true;
                    };
                break;
            }
            return false;
        }
        protected function logout() {
            $_SESSION["futbolsessionpassword"] = "";
            $_SESSION["futbolsessionemail"] = "";
            header("Location: ".$this->siteURL);
        }
        protected function isAdmin() {
            global $admins;
            if($this->user && in_array($this->user->email, $admins)) {
                return true;
            } else {
                return false;
            }
        }
        protected function getUser($id) {
            $q = "SELECT * FROM futbol_users WHERE id = '".$id."'";
            $res = $this->query($q);
            return isset($res->result[0]) ? $res->result[0] : false;
        }
        protected function areOptionsAvailable($record) {
            if($record->userId == $this->user->id || $this->isAdmin()) {
                return true;
            } else {
                return false;
            }
        }
        // mysql
        private function initMysql() {
            mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS);
            mysql_select_db(MYSQL_DB);
            mysql_query("SET NAMES 'utf8'");
        }
        protected function query($q) {
            $res = mysql_query($q);
            $result = array();
            if(!is_bool($res)) {
                $numOfRes = mysql_num_rows($res);
                while($r = mysql_fetch_object($res)) {
                    array_push($result, $r);
                }
            }
            return (object) array(
                "result" => $result,
                "raw" => $res
            );
        }
        // utils
        protected function getFormatedDate($date) {
            $date = explode("-", $date);
            $gameTime = mktime(0, 0, 0, $date[1], $date[0], $date[2]);
            $day = date("l", $gameTime);
            $month = date("F", $gameTime);
            return $date[0]." ".$month." (".$day.")";
        }
        protected function getBookedPlayers($game) {
            $res = $this->query("SELECT * FROM futbol_players WHERE gameId = '".$game->id."'");
            $totalPlayers = 0;
            if(isset($res->result)) {
                $numOfRecords = count($res->result);
                for($i=0; $i<$numOfRecords; $i++) {
                    $totalPlayers += $res->result[$i]->numOfPlayers;
                }
            }
            return $totalPlayers;
        }
        protected function getCurrentUser($password = null, $email = null) {
            $password = $password != null ? $password : (isset($_SESSION["futbolsessionpassword"]) ? $_SESSION["futbolsessionpassword"] : "");
            $email = $email != null ? $email : (isset($_SESSION["futbolsessionemail"]) ? $_SESSION["futbolsessionemail"] : "");
            $q = "SELECT * FROM futbol_users WHERE password = '".$password."' AND email = '".$email."'";
            $res = $this->query($q);
            return $this->user = isset($res->result[0]) ? $res->result[0] : false;
        }
        protected function sendMessage($emails, $subject, $message) {
            if(!is_array($emails)) {
                $emails = explode(",", $emails);
            }
            foreach($emails as $to) {
                if($to != "") {
                    var_dump($to);
                    $headers  = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
                    $headers .= 'From: info@krasimirtsonev.com' . "\r\n" .
                        'Reply-To: info@krasimirtsonev.com' . "\r\n";
                    @mail($to, $subject, $message, $headers);
                }
            }
                // die();
        }
    }

?>