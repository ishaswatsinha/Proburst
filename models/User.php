<?php
// =============================================
// models/User.php
// Drop this in your existing models/ folder
// =============================================

class User {

    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // ----------------------------
    // Find user by email
    // ----------------------------
    public function findByEmail(string $email): array|false {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ----------------------------
    // Find user by ID
    // ----------------------------
    public function findById(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ----------------------------
    // Register a new user
    // ----------------------------
    public function create(string $name, string $email, string $phone, string $password): int|false {
        // Check duplicate email
        if ($this->findByEmail($email)) return false;

        $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $this->db->prepare(
            "INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$name, $email, $phone, $hashed]);
        return (int) $this->db->lastInsertId();
    }

    // ----------------------------
    // Verify password and return user
    // ----------------------------
    public function authenticate(string $email, string $password): array|false {
        $user = $this->findByEmail($email);
        if (!$user) return false;
        if (!password_verify($password, $user['password'])) return false;
        return $user;
    }

    // ----------------------------
    // Update profile
    // ----------------------------
    public function updateProfile(int $id, string $name, string $phone): bool {
        $stmt = $this->db->prepare(
            "UPDATE users SET name = ?, phone = ? WHERE id = ?"
        );
        return $stmt->execute([$name, $phone, $id]);
    }

    // ----------------------------
    // Change password
    // ----------------------------
    public function updatePassword(int $id, string $newPassword): bool {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt   = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashed, $id]);
    }
}
