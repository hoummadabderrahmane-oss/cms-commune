<?php
/**
 * ============================================
  * SGC - Redirection vers Détails
   * ============================================
    */
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        header('Location: show.php?id=' . (int)$_GET['id']);
        } else {
            header('Location: index.php');
            }
            exit;
            