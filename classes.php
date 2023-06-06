<?php

class Database
{

    private $db_host = "localhost";
    private $db_username = "root";
    private $db_password = "";
    private $db_name = "cc";
    protected $result = array();
    private $connection = false;
    protected $mysqli = "";



    public function __construct()
    {

        if (!$this->connection) {

            $this->mysqli = new mysqli($this->db_host, $this->db_username, $this->db_password, $this->db_name);
            $this->connection = true;
            // print_r($this->mysqli);
            if (!$this->mysqli->connect_error) {

                return true;
            } else {

                array_push($this->result, $this->mysqli->connect_error);
                return false;
            }
        }
    }

    public function insert($table, $params = array())
    {

        // print_r($params);
        if ($this->table_exists($table)) {

            $table_columns = implode(', ', array_keys($params));
            $table_values = implode(', ', array_values($params));

            $query = "INSERT INTO $table ($table_columns) VALUES ($table_values)";

            if ($this->mysqli->query($query)) {

                array_push($this->result, $this->mysqli->affected_rows);
                return true;
            } else {

                array_push($this->result, $this->mysqli->error);
                return false;
            }
        } else {

            return false;
        }
    }

    public function update($table, $params = array(), $where = null)
    {

        if ($this->table_exists($table)) {

            $arguments = array();
            foreach ($params as $key => $value) {

                $arguments[] = "$key = '$value'";
            }

            $query = "UPDATE $table SET " . implode(', ', $arguments);

            if ($where != null) {

                $query .= " WHERE $where";
            }

            if ($this->mysqli->query($query)) {

                array_push($this->result, $this->mysqli->affected_rows);

                return true;
            } else {

                array_push($this->result, $this->mysqli->error);
                return false;
            }
        } else {

            return false;
        }
    }



    public function delete($table, $where = null)
    {

        if ($this->table_exists($table)) {

            $query = "DELETE FROM $table";

            if ($where != null) {

                $query .= " WHERE $where";
            }

            if ($this->mysqli->query($query)) {

                array_push($this->result, $this->mysqli->affected_rows);
                return true;
            } else {

                array_push($this->result, $this->mysqli->error);
                return false;
            }
        } else {

            return false;
        }
    }


    public function select($row = "*", $table, $join = null, $where = null, $order = null, $limit = null, $num_rows = null)
    {

        if ($this->table_exists($table)) {

            $query = "SELECT $row FROM $table";

            if ($join != null) {

                $query .= " JOIN $join";
            }

            if ($where != null) {

                $query .= " WHERE  $where";
            }

            if ($order != null) {

                $query .= " ORDER BY $order";
            }

            if ($limit != null) {

                $query .= " LIMIT 0, $limit";
            }

            $sql = $this->mysqli->query($query);

            if ($sql) {

                $this->result = $sql->fetch_assoc();
                return true;
            } else {

                $this->result = $this->mysqli->error;
                return false;
            }
        } else {

            return false;
        }
    }


    public function sql_num_rows($sql)
    {
        $query = $this->mysqli->query($sql);

        if ($query->num_rows >= 1) {

            $this->result = $query->fetch_assoc();
            return true;
        } else {

            $this->result = $this->mysqli->error;
            return false;
        }
    }


    private function table_exists($table)
    {

        $query = "SHOW TABLES FROM $this->db_name LIKE '$table'";

        $table_in_db = $this->mysqli->query($query);

        if ($table_in_db->num_rows == 1) {

            return true;
        } else {

            array_push($this->result, $table . ",  this Table does not exist in Database");
            return false;
        }
    }

    public function get_result()
    {

        $values = $this->result;
        $this->result = array();
        print "<pre>";
        print_r($values);
        print "</pre>";
        // foreach ($values as $key => $value) {

        //     echo $key." = ".$value;
        //     echo "<br>";
        // }
    }


    public function __destruct()
    {

        if ($this->connection) {

            if ($this->mysqli->close()) {

                $this->connection = false;
                return true;
            } else {

                return false;
            }
        } else {

            return false;
        }
    }
}

class Signup extends Database
{

    public function registerUser($full_name, $username, $email, $password)
    {
        // Check if the username or email already exists
        if ($this->isUsernameExists($username)) {
            return "Username already exists";
        }

        if ($this->isEmailExists($email)) {
            return "Email already exists";
        }

        // Generate a salt for password hashing
        $salt = $this->generateSalt();

        // Hash the password with the salt
        $hashedPassword = $this->hashPassword($password, $salt);

        // Insert the user into the database
        $sql = "INSERT INTO user (full_name, username, email, password, salt) VALUES ('$full_name', '$username', '$email', '$hashedPassword', '$salt')";

        if ($this->mysqli->query($sql)) {
            return "User registered successfully";
        } else {
            $error = $this->mysqli->error;
            return "Error registering user: ". $error;
        }
    }

    private function isUsernameExists($username)
    {
        $sql = "SELECT * FROM user WHERE username = '$username'";
        // $result = $this->conn->query($sql);
        // return $result->num_rows > 0;
        // $obj = new Database();
        if ($this->sql_num_rows($sql)) {

            return true;
        } else {

            return false;
        }
    }

    private function isEmailExists($email)
    {
        $sql = "SELECT * FROM user WHERE email = '$email'";
        // $result = $this->conn->query($sql);
        // return $result->num_rows > 0;

        // $obj = new Database();
        if ($this->sql_num_rows($sql)) {

            return true;
        } else {

            return false;
        }
    }

    private function generateSalt()
    {
        return bin2hex(random_bytes(16));
    }

    private function hashPassword($password, $salt)
    {
        $saltedPassword = $password . $salt;
        return password_hash($saltedPassword, PASSWORD_DEFAULT);
    }

    public function close()
    {
        // $obj = new Database();
        $this->mysqli->close();
    }
}


class Login extends Database
{
    private $username;
    private $password;

    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function authenticate()
    {
        // $obj = new Database();

        if ($this->sql_num_rows('SELECT * FROM `user` WHERE name = "' . $this->username . '" AND password = "' . $this->password . '"')) {

            return true;
        } else {

            return false;
        }
    }
}

class Session
{
    public function start()
    {
        session_start();
    }

    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function get($key)
    {
        return $_SESSION[$key] ?? null;
    }

    public function destroy()
    {
        session_destroy();
    }
}

