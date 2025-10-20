<?php
require_once "config.php";

// Eğer müşteri zaten giriş yapmışsa, yönlendir
if(isset($_SESSION["customer_loggedin"]) && $_SESSION["customer_loggedin"] === true){
    header("location: customer_dashboard.php");
    exit;
}

$customer_id = $password = "";
$customer_id_err = $password_err = $login_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty(trim($_POST["customer_id"]))){
        $customer_id_err = "Lütfen müşteri ID'nizi girin.";
    } else{
        $customer_id = trim($_POST["customer_id"]);
    }
    
    if(empty(trim($_POST["password"]))){
        $password_err = "Lütfen şifrenizi girin.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    if(empty($customer_id_err) && empty($password_err)){
        $sql = "SELECT id, customer_id, customer_password, name FROM customers WHERE customer_id = ?";
        
        if($stmt = $mysqli->prepare($sql)){
            $stmt->bind_param("s", $param_customer_id);
            $param_customer_id = $customer_id;
            
            if($stmt->execute()){
                $stmt->store_result();
                
                if($stmt->num_rows == 1){                    
                    $stmt->bind_result($id, $customer_id, $hashed_password, $name);
                    if($stmt->fetch()){
                        if(password_verify($password, $hashed_password)){
                            session_start();
                            
                            $_SESSION["customer_loggedin"] = true;
                            $_SESSION["customer_id"] = $id;
                            $_SESSION["customer_unique_id"] = $customer_id;
                            $_SESSION["customer_name"] = $name;
                            
                            header("location: customer_dashboard.php");
                        } else{
                            $login_err = "Geçersiz müşteri ID veya şifre.";
                        }
                    }
                } else{
                    $login_err = "Geçersiz müşteri ID veya şifre.";
                }
            } else{
                echo "Bir şeyler ters gitti. Lütfen daha sonra tekrar deneyin.";
            }
            $stmt->close();
        }
    }
    $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="tr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Girişi - Lisans Yönetimi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-600 mb-4">
                        <i data-lucide="user-circle" class="w-8 h-8 text-white"></i>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Müşteri Girişi</h1>
                    <p class="text-gray-500 text-sm">Lisans bilgilerinizi görüntülemek için giriş yapın</p>
                </div>
                
                <?php 
                if(!empty($login_err)){
                    echo '<div class="bg-red-50 text-red-600 border border-red-200 p-3 rounded-lg mb-4 text-sm">' . $login_err . '</div>';
                }        
                ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Müşteri ID</label>
                        <input name="customer_id" type="text" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition <?php echo (!empty($customer_id_err)) ? 'border-red-500' : ''; ?>" value="<?php echo $customer_id; ?>" placeholder="12345" maxlength="5">
                        <?php if(!empty($customer_id_err)): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $customer_id_err; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Şifre</label>
                        <input name="password" type="password" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition <?php echo (!empty($password_err)) ? 'border-red-500' : ''; ?>" placeholder="••••••">
                        <?php if(!empty($password_err)): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $password_err; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition shadow-lg">
                        Giriş Yap
                    </button>
                </form>
                
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Giriş bilgilerinizi almak için satıcınızla iletişime geçin
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
