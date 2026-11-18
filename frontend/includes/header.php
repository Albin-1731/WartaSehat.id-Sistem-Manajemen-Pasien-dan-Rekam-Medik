<?php
if (!isset($base_path)) {
    $base_path = '../../';
}
if (!isset($page_title)) {
    $page_title = 'WartaSehat.id - Sistem Manajemen Pasien & Rekam Medik';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title); ?></title>
  <!-- Bootstrap 5 CSS CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Local Custom CSS -->
  <link rel="stylesheet" href="<?= $base_path; ?>css/variables.css">
  <link rel="stylesheet" href="<?= $base_path; ?>css/style.css">
  <link rel="stylesheet" href="<?= $base_path; ?>css/responsive.css">
</head>
<body>

  <!-- Pure CSS Mobile Sidebar Toggle Mechanism -->
  <input type="checkbox" id="sidebarToggle" class="sidebar-toggle-check visually-hidden">
  <label for="sidebarToggle" class="sidebar-overlay" aria-label="Tutup Menu"></label>

  <div class="ws-app-layout">
