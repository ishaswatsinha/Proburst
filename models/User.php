<?php

class User {

    private mysqli $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    public function findByEmail(string $email): array|false {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: false;
    }

    public function findById(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: false;
    }

    public function create(string $name, string $email, string $phone, string $password): int|false {
        if ($this->findByEmail($email)) return false;

        $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt   = $this->db->prepare(
            "INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('ssss', $name, $email, $phone, $hashed);
        $stmt->execute();
        $id = $this->db->insert_id;
        $stmt->close();
        return $id ?: false;
    }

    public function authenticate(string $email, string $password): array|false {
        $user = $this->findByEmail($email);
        if (!$user) return false;
        if (!password_verify($password, $user['password'])) return false;
        return $user;
    }

    public function updateProfile(int $id, string $name, string $phone): bool {
        $stmt = $this->db->prepare("UPDATE users SET name=?, phone=? WHERE id=?");
        $stmt->bind_param('ssi', $name, $phone, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function updatePassword(int $id, string $newPassword): bool {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt   = $this->db->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param('si', $hashed, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
