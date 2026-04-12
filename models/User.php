<?php

class User {

    private $db; // no type hint — works on PHP 7.2+

    public function __construct($db) {
        $this->db = $db;
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        if (!$stmt) return false;
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? $row : false;
    }

    public function findById($id) {
        $id   = (int)$id;
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        if (!$stmt) return false;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? $row : false;
    }

    public function create($name, $email, $phone, $password) {
        if ($this->findByEmail($email)) return false;
        $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt   = $this->db->prepare(
            "INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)"
        );
        if (!$stmt) return false;
        $stmt->bind_param('ssss', $name, $email, $phone, $hashed);
        $stmt->execute();
        $id = $this->db->insert_id;
        $stmt->close();
        return $id ? $id : false;
    }

    public function authenticate($email, $password) {
        $user = $this->findByEmail($email);
        if (!$user) return false;
        if (!password_verify($password, $user['password'])) return false;
        return $user;
    }

    public function updateProfile($id, $name, $phone) {
        $id   = (int)$id;
        $stmt = $this->db->prepare("UPDATE users SET name=?, phone=? WHERE id=?");
        if (!$stmt) return false;
        $stmt->bind_param('ssi', $name, $phone, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function updatePassword($id, $newPassword) {
        $id     = (int)$id;
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt   = $this->db->prepare("UPDATE users SET password=? WHERE id=?");
        if (!$stmt) return false;
        $stmt->bind_param('si', $hashed, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // ── Forgot / Reset password ─────────────────────────────────────────

    public function createResetToken($email) {
        $user = $this->findByEmail($email);
        if (!$user) return false;

        $token     = bin2hex(random_bytes(32));
        $hash      = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        $stmt = $this->db->prepare(
            "UPDATE users SET reset_token=?, reset_token_expires=? WHERE email=?"
        );
        if (!$stmt) return false;
        $stmt->bind_param('sss', $hash, $expiresAt, $email);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok ? $token : false;
    }

    public function findByResetToken($token) {
        $hash = hash('sha256', $token);
        $now  = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE reset_token=? AND reset_token_expires > ? LIMIT 1"
        );
        if (!$stmt) return false;
        $stmt->bind_param('ss', $hash, $now);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? $row : false;
    }

    public function resetPasswordByToken($token, $newPassword) {
        $user = $this->findByResetToken($token);
        if (!$user) return false;
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $null1  = null;
        $null2  = null;
        $stmt   = $this->db->prepare(
            "UPDATE users SET password=?, reset_token=NULL, reset_token_expires=NULL WHERE id=?"
        );
        if (!$stmt) return false;
        $stmt->bind_param('si', $hashed, $user['id']);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
