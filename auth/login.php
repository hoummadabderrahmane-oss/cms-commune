<?php
session_start();
require_once "../config/database.php";

if (isset($_SESSION['user_id'])) {
    header("Location: ../admin/dashboard.php");
        exit();
        }

        $error = "";

        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            $email = trim($_POST['email']);
                $password = $_POST['password'];

                    if (empty($email) || empty($password)) {
                            $error = "Veuillez remplir tous les champs.";
                                } else {

                                        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                                                $stmt->execute([$email]);

                                                        if ($stmt->rowCount() == 1) {

                                                                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                                                                                if (password_verify($password, $user['password'])) {

                                                                                                session_regenerate_id(true);

                                                                                                                $_SESSION['user_id'] = $user['id'];
                                                                                                                                $_SESSION['full_name'] = $user['full_name'];
                                                                                                                                                $_SESSION['role'] = $user['role'];

                                                                                                                                                                header("Location: ../admin/dashboard.php");
                                                                                                                                                                                exit();

                                                                                                                                                                                            } else {

                                                                                                                                                                                                            $error = "Mot de passe incorrect.";

                                                                                                                                                                                                                        }

                                                                                                                                                                                                                                } else {

                                                                                                                                                                                                                                            $error = "Email introuvable.";

                                                                                                                                                                                                                                                    }

                                                                                                                                                                                                                                                        }

                                                                                                                                                                                                                                                        }
                                                                                                                                                                                                                                                        ?>

                                                                                                                                                                                                                                                        <!DOCTYPE html>
                                                                                                                                                                                                                                                        <html lang="fr">

                                                                                                                                                                                                                                                        <head>

                                                                                                                                                                                                                                                        <meta charset="UTF-8">
                                                                                                                                                                                                                                                        <meta name="viewport" content="width=device-width, initial-scale=1">

                                                                                                                                                                                                                                                        <title>SGC | Connexion</title>

                                                                                                                                                                                                                                                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

                                                                                                                                                                                                                                                        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

                                                                                                                                                                                                                                                        <style>

                                                                                                                                                                                                                                                        body{
                                                                                                                                                                                                                                                        background:#eef2f7;
                                                                                                                                                                                                                                                        height:100vh;
                                                                                                                                                                                                                                                        display:flex;
                                                                                                                                                                                                                                                        justify-content:center;
                                                                                                                                                                                                                                                        align-items:center;
                                                                                                                                                                                                                                                        font-family:Arial,sans-serif;
                                                                                                                                                                                                                                                        }

                                                                                                                                                                                                                                                        .card{

                                                                                                                                                                                                                                                        width:430px;

                                                                                                                                                                                                                                                        border:none;

                                                                                                                                                                                                                                                        border-radius:18px;

                                                                                                                                                                                                                                                        box-shadow:0 10px 30px rgba(0,0,0,.15);

                                                                                                                                                                                                                                                        }

                                                                                                                                                                                                                                                        .logo{

                                                                                                                                                                                                                                                        width:110px;

                                                                                                                                                                                                                                                        }

                                                                                                                                                                                                                                                        .btn-main{

                                                                                                                                                                                                                                                        background:#0B6E4F;

                                                                                                                                                                                                                                                        color:white;

                                                                                                                                                                                                                                                        }

                                                                                                                                                                                                                                                        .btn-main:hover{

                                                                                                                                                                                                                                                        background:#09553d;

                                                                                                                                                                                                                                                        color:white;

                                                                                                                                                                                                                                                        }

                                                                                                                                                                                                                                                        </style>

                                                                                                                                                                                                                                                        </head>

                                                                                                                                                                                                                                                        <body>

                                                                                                                                                                                                                                                        <div class="card">

                                                                                                                                                                                                                                                        <div class="card-body p-4">

                                                                                                                                                                                                                                                        <div class="text-center">

                                                                                                                                                                                                                                                        <img src="../assets/images/logo.png" class="logo mb-3">

                                                                                                                                                                                                                                                        <h4>Système de Gestion Communale</h4>

                                                                                                                                                                                                                                                        <p class="text-muted">

                                                                                                                                                                                                                                                        نظام إدارة الجماعة

                                                                                                                                                                                                                                                        </p>

                                                                                                                                                                                                                                                        </div>

                                                                                                                                                                                                                                                        <?php if($error!=""){ ?>

                                                                                                                                                                                                                                                        <div class="alert alert-danger">

                                                                                                                                                                                                                                                        <?= $error ?>

                                                                                                                                                                                                                                                        </div>

                                                                                                                                                                                                                                                        <?php } ?>

                                                                                                                                                                                                                                                        <form method="POST">

                                                                                                                                                                                                                                                        <div class="mb-3">

                                                                                                                                                                                                                                                        <label>Email</label>

                                                                                                                                                                                                                                                        <div class="input-group">

                                                                                                                                                                                                                                                        <span class="input-group-text">

                                                                                                                                                                                                                                                        <i class="fa fa-envelope"></i>

                                                                                                                                                                                                                                                        </span>

                                                                                                                                                                                                                                                        <input
                                                                                                                                                                                                                                                        type="email"
                                                                                                                                                                                                                                                        name="email"
                                                                                                                                                                                                                                                        class="form-control"
                                                                                                                                                                                                                                                        required>

                                                                                                                                                                                                                                                        </div>

                                                                                                                                                                                                                                                        </div>

                                                                                                                                                                                                                                                        <div class="mb-4">

                                                                                                                                                                                                                                                        <label>Mot de passe</label>

                                                                                                                                                                                                                                                        <div class="input-group">

                                                                                                                                                                                                                                                        <span class="input-group-text">

                                                                                                                                                                                                                                                        <i class="fa fa-lock"></i>

                                                                                                                                                                                                                                                        </span>

                                                                                                                                                                                                                                                        <input
                                                                                                                                                                                                                                                        type="password"
                                                                                                                                                                                                                                                        name="password"
                                                                                                                                                                                                                                                        class="form-control"
                                                                                                                                                                                                                                                        required>

                                                                                                                                                                                                                                                        </div>

                                                                                                                                                                                                                                                        </div>

                                                                                                                                                                                                                                                        <button class="btn btn-main w-100">

                                                                                                                                                                                                                                                        <i class="fa fa-right-to-bracket"></i>

                                                                                                                                                                                                                                                        Se connecter

                                                                                                                                                                                                                                                        </button>

                                                                                                                                                                                                                                                        </form>

                                                                                                                                                                                                                                                        <hr>

                                                                                                                                                                                                                                                        <div class="text-center text-muted">

                                                                                                                                                                                                                                                        SGC Version 1.0

                                                                                                                                                                                                                                                        </div>

                                                                                                                                                                                                                                                        </div>

                                                                                                                                                                                                                                                        </div>

                                                                                                                                                                                                                                                        </body>

                                                                                                                                                                                                                                                        </html>