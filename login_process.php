<?php
session_start();
$conn = new mysqli("localhost", "root", "", "thriftin");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = trim($_POST['email']);
$password = trim($_POST['password']);
$userType = $_POST['userType'];

if ($userType === 'admin') {
    $email = trim($email);
    $password = trim($password);

    // 1) Try plain-password match against table `admin`
    $stmt = $conn->prepare("SELECT id, email FROM admin WHERE email = ? AND password = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $_SESSION['admin'] = $email;
            $_SESSION['admin_id'] = (int)($row['id'] ?? 0);
            $_SESSION['role'] = 'admin';
            header("Location: admin_dashboard.php");
            exit;
        }
        $stmt->close();
    }

    // 2) If not matched, try table `admins` (common alternate name) with plain-password column `password`
    $stmt = $conn->prepare("SELECT id, email FROM admins WHERE email = ? AND password = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $_SESSION['admin'] = $email;
            $_SESSION['admin_id'] = (int)($row['id'] ?? 0);
            $_SESSION['role'] = 'admin';
            header("Location: admin_dashboard.php");
            exit;
        }
        $stmt->close();
    }

    // 3) If your admin uses hashed passwords, support column `password_hash`
    $stmt = $conn->prepare("SELECT id, email, password_hash FROM admin WHERE email = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && ($row = $res->fetch_assoc())) {
            if (!empty($row['password_hash']) && password_verify($password, $row['password_hash'])) {
                $_SESSION['admin'] = $row['email'];
                $_SESSION['admin_id'] = (int)($row['id'] ?? 0);
                $_SESSION['role'] = 'admin';
                header("Location: admin_dashboard.php");
                exit;
            }
        }
        $stmt->close();
    }

    echo "<script>alert('Invalid admin credentials');window.location.href='login_page.html';</script>";
    exit;
}

elseif ($userType === 'seller') {
    $stmt = $conn->prepare("SELECT * FROM sellers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $seller = $result->fetch_assoc();
        if (password_verify($password, $seller['password_hash'])) {
            $_SESSION['seller'] = $email;
            $_SESSION['seller_id'] = (int)$seller['id'];
            $_SESSION['role'] = 'seller';
            header("Location: seller_dashboard.php");
            exit;
        }
    }

    echo "<script>alert('Invalid seller credentials');window.location.href='login_page.html';</script>";
    exit;
}

elseif ($userType === 'user') {
    $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = $email;
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['role'] = 'user';
            header("Location: 2nd_street_india.html"); // homepage
            exit;
        }
    }

    echo "<script>alert('Invalid customer credentials');window.location.href='login_page.html';</script>";
    exit;
}

$conn->close();
?>
