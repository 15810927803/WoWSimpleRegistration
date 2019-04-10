<?php
/**
 * @author Amin Mahmoudi (MasterkinG)
 * @copyright    Copyright (c) 2019 - 2022, MsaterkinG32 Team, Inc. (https://masterking32.com)
 * @link    https://masterking32.com
 * @Description : It's not masterking32 framework !
 **/

use Gregwar\Captcha\CaptchaBuilder;
use Medoo\Medoo;

class user
{
    public static $captcha;

    public static function post_handler()
    {
        if (!empty($_POST['submit'])) {
            if (get_config('battlenet_support')) {
                self::bnet_register();
                self::bnet_changepass();
            } else {
                self::normal_register();
                self::normal_changepass();
            }
            unset($_SESSION['captcha']);
            self::$captcha = new CaptchaBuilder;
            self::$captcha->build();
            $_SESSION['captcha'] = self::$captcha->getPhrase();
        } else {
            unset($_SESSION['captcha']);
            self::$captcha = new CaptchaBuilder;
            self::$captcha->build();
            $_SESSION['captcha'] = self::$captcha->getPhrase();
        }
    }

    public static function bnet_register()
    {
        global $antiXss;
        if ($_POST['submit'] == 'register' && !empty($_POST["password"]) && !empty($_POST["repassword"]) && !empty($_POST["email"]) && !empty($_POST["captcha"]) && !empty($_SESSION['captcha'])) {
            if (strtolower($_SESSION['captcha']) == strtolower($_POST["captcha"])) {
                unset($_SESSION['captcha']);
                if (filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
                    if ($_POST["password"] == $_POST["repassword"]) {
                        if (strlen($_POST["password"]) >= 4 && strlen($_POST["password"]) <= 16) {
                            if (self::check_email_exists(strtoupper($_POST["email"]))) {
                                $bnet_hashed_pass = strtoupper(bin2hex(strrev(hex2bin(strtoupper(hash("sha256", strtoupper(hash("sha256", strtoupper($_POST["email"])) . ":" . strtoupper($_POST["password"]))))))));
                                database::$auth->insert("battlenet_accounts", [
                                    "email" => $antiXss->xss_clean(strtoupper($_POST["email"])),
                                    "sha_pass_hash" => $antiXss->xss_clean($bnet_hashed_pass)
                                ]);
                                $bnet_account_id = database::$auth->id();
                                $username = $bnet_account_id . "#1";
                                $hashed_pass = strtoupper(sha1(strtoupper($username . ":" . $_POST["password"])));
                                database::$auth->insert("account", [
                                    "username" => $antiXss->xss_clean(strtoupper($username)),
                                    "sha_pass_hash" => $antiXss->xss_clean($hashed_pass),
                                    "email" => $antiXss->xss_clean(strtoupper($_POST["email"])),
                                    "reg_mail" => $antiXss->xss_clean(strtoupper($_POST["email"])),
                                    "expansion" => $antiXss->xss_clean(get_config('expansion')),
                                    "battlenet_account" => $bnet_account_id,
                                    "battlenet_index" => 1
                                ]);
                                success_msg("Your account has been created.");
                            } else {
                                error_msg("Username or Email is exists.");
                            }
                        } else {
                            error_msg("Password length is not valid.");
                        }
                    } else {
                        error_msg("Passwords is not equal.");
                    }
                } else {
                    error_msg("Use valid email.");
                }
            } else {
                error_msg("Captcha is not valid.");
            }
        }
    }

    public static function normal_register()
    {
        global $antiXss;
        if ($_POST['submit'] == 'register' && !empty($_POST["password"]) && !empty($_POST["username"]) && !empty($_POST["repassword"]) && !empty($_POST["email"]) && !empty($_POST["captcha"]) && !empty($_SESSION['captcha'])) {
            if (strtolower($_SESSION['captcha']) == strtolower($_POST["captcha"])) {
                unset($_SESSION['captcha']);
                if (preg_match("/^[0-9A-Z-_]+$/", strtoupper($_POST["username"]))) {
                    if (filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
                        if ($_POST["password"] == $_POST["repassword"]) {
                            if (strlen($_POST["password"]) >= 4 && strlen($_POST["password"]) <= 16) {
                                if (strlen($_POST["username"]) >= 2 && strlen($_POST["username"]) <= 16) {
                                    if (self::check_email_exists(strtoupper($_POST["email"])) && self::check_username_exists(strtoupper($_POST["username"]))) {
                                        $hashed_pass = strtoupper(sha1(strtoupper($_POST["username"] . ":" . $_POST["password"])));
                                        database::$auth->insert("account", [
                                            "username" => $antiXss->xss_clean(strtoupper($_POST["username"])),
                                            "sha_pass_hash" => $antiXss->xss_clean($hashed_pass),
                                            "email" => $antiXss->xss_clean(strtoupper($_POST["email"])),
                                            "reg_mail" => $antiXss->xss_clean(strtoupper($_POST["email"])),
                                            "expansion" => $antiXss->xss_clean(get_config('expansion'))
                                        ]);
                                        success_msg("Your account has been created.");
                                    } else {
                                        error_msg("Username or Email is exists.");
                                    }
                                } else {
                                    error_msg("Username length is not valid.");
                                }
                            } else {
                                error_msg("Password length is not valid.");
                            }
                        } else {
                            error_msg("Passwords is not equal.");
                        }
                    } else {
                        error_msg("Use valid email.");
                    }
                } else {
                    error_msg("Use valid characters for username.");
                }
            } else {
                error_msg("Captcha is not valid.");
            }
        }
    }

    public static function bnet_changepass()
    {
        global $antiXss;
        if ($_POST['submit'] == 'changepass' && !empty($_POST["password"]) && !empty($_POST["old_password"]) && !empty($_POST["repassword"]) && !empty($_POST["email"]) && !empty($_POST["captcha"]) && !empty($_SESSION['captcha'])) {
            if (strtolower($_SESSION['captcha']) == strtolower($_POST["captcha"])) {
                unset($_SESSION['captcha']);
                if (filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
                    if ($_POST["password"] == $_POST["repassword"]) {
                        if (strlen($_POST["password"]) >= 4 && strlen($_POST["password"]) <= 16) {
                            $userinfo = self::get_user_by_email(strtoupper($_POST["email"]));
                            if (!empty($userinfo["username"])) {
                                $Old_hashed_pass = strtoupper(sha1(strtoupper($userinfo["username"] . ":" . $_POST["old_password"])));
                                $hashed_pass = strtoupper(sha1(strtoupper($userinfo["username"] . ":" . $_POST["password"])));
                                if ($userinfo["sha_pass_hash"] == $Old_hashed_pass) {
                                    database::$auth->update("account", [
                                        "sha_pass_hash" => $antiXss->xss_clean($hashed_pass),
                                        "sessionkey" => "",
                                        "v" => "",
                                        "s" => ""
                                    ], [
                                        "id[=]" => $userinfo["id"]
                                    ]);

                                    $bnet_hashed_pass = strtoupper(bin2hex(strrev(hex2bin(strtoupper(hash("sha256", strtoupper(hash("sha256", strtoupper($userinfo["email"])) . ":" . strtoupper($_POST["password"]))))))));

                                    database::$auth->update("battlenet_accounts", [
                                        "sha_pass_hash" => $antiXss->xss_clean($bnet_hashed_pass)
                                    ], [
                                        "id[=]" => $userinfo["battlenet_account"]
                                    ]);
                                    success_msg("Password has been changed.");
                                } else {
                                    error_msg("Old password is not valid.");
                                }
                            } else {
                                error_msg("Email is not valid.");
                            }
                        } else {
                            error_msg("Password length is not valid.");
                        }
                    } else {
                        error_msg("Passwords is not equal.");
                    }
                } else {
                    error_msg("Use valid email.");
                }
            } else {
                error_msg("Captcha is not valid.");
            }
        }
    }

    public static function normal_changepass()
    {
        global $antiXss;
        if ($_POST['submit'] == 'changepass' && !empty($_POST["password"]) && !empty($_POST["old_password"]) && !empty($_POST["repassword"]) && !empty($_POST["email"]) && !empty($_POST["captcha"]) && !empty($_SESSION['captcha'])) {
            if (strtolower($_SESSION['captcha']) == strtolower($_POST["captcha"])) {
                unset($_SESSION['captcha']);
                if (filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
                    if ($_POST["password"] == $_POST["repassword"]) {
                        if (strlen($_POST["password"]) >= 4 && strlen($_POST["password"]) <= 16) {
                            $userinfo = self::get_user_by_email(strtoupper($_POST["email"]));
                            if (!empty($userinfo["username"])) {
                                $Old_hashed_pass = strtoupper(sha1(strtoupper($userinfo["username"] . ":" . $_POST["old_password"])));
                                $hashed_pass = strtoupper(sha1(strtoupper($userinfo["username"] . ":" . $_POST["password"])));
                                if ($userinfo["sha_pass_hash"] == $Old_hashed_pass) {
                                    database::$auth->update("account", [
                                        "sha_pass_hash" => $antiXss->xss_clean($hashed_pass),
                                        "sessionkey" => "",
                                        "v" => "",
                                        "s" => ""
                                    ], [
                                        "id[=]" => $userinfo["id"]
                                    ]);
                                    success_msg("Password has been changed.");
                                } else {
                                    error_msg("Old password is not valid.");
                                }
                            } else {
                                error_msg("Email is not valid.");
                            }
                        } else {
                            error_msg("Password length is not valid.");
                        }
                    } else {
                        error_msg("Passwords is not equal.");
                    }
                } else {
                    error_msg("Use valid email.");
                }
            } else {
                error_msg("Captcha is not valid.");
            }
        }
    }

    public static function check_email_exists($email)
    {
        if (!empty($email)) {
            $datas = database::$auth->select("account", ["id"], ["email" => Medoo::raw('UPPER(:email)', [':email' => $email])]);
            if (empty($datas[0])) {
                return true;
            }
        }
        return false;
    }

    public static function get_user_by_email($email)
    {
        if (!empty($email)) {
            $datas = database::$auth->select("account", "*", ["email" => Medoo::raw('UPPER(:email)', [':email' => strtoupper($email)])]);
            if (!empty($datas[0]["username"])) {
                return $datas[0];
            }
        }
        return false;
    }

    public static function check_username_exists($username)
    {
        if (!empty($username)) {
            $datas = database::$auth->select("account", ["id"], ["username" => Medoo::raw('UPPER(:username)', [':username' => $username])]);
            if (empty($datas[0])) {
                return true;
            }
        }
        return false;
    }

    public static function get_online_players($realmID)
    {
        $datas = database::$chars[$realmID]->select("characters", array("name", "race", "class", "gender", "level"), ['LIMIT' => 49, "ORDER" => ["level" => "DESC"], "online[=]" => 1]);
        if (!empty($datas[0]["name"])) {
            return $datas;
        }
        return false;
    }
}