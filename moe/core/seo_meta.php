<?php
/**
 * MOE School Management System - SEO Meta Tags
 * Mediterranean of Egypt School System
 * 
 * Include this file in <head> section of all pages.
 * Usage: <?php include 'path/to/core/seo_meta.php'; ?>
 * 
 * Set $page_title and $page_description before including this file
 * to customize per-page SEO.
 */

// Default SEO values (can be overridden before include)
$default_title = "MOE - Mediterranean of Egypt School System";
$default_description = "Mediterranean of Egypt School Management System - Comprehensive education platform with pet companions, gamification, and modern learning experience.";
$default_keywords = "MOE, Mediterranean of Egypt, school management, education, pet system, gamification, learning platform, student portal";
$default_og_image = "assets/images/og-image.png";

// Use page-specific values if set
$seo_title = isset($page_title) ? $page_title . " | MOE" : $default_title;
$seo_description = isset($page_description) ? $page_description : $default_description;
$seo_keywords = isset($page_keywords) ? $page_keywords : $default_keywords;
$seo_og_image = isset($page_og_image) ? $page_og_image : $default_og_image;

// Get base URL dynamically
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$current_url = $base_url . $_SERVER['REQUEST_URI'];
?>

<!-- Primary Meta Tags -->
<meta name="title" content="<?php echo htmlspecialchars($seo_title); ?>">
<meta name="description" content="<?php echo htmlspecialchars($seo_description); ?>">
<meta name="keywords" content="<?php echo htmlspecialchars($seo_keywords); ?>">
<meta name="author" content="Mediterranean of Egypt School">
<meta name="robots" content="index, follow">
<meta name="language" content="English">
<meta name="revisit-after" content="7 days">

<!-- Favicon -->
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base_url; ?>/moe/assets/images/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo $base_url; ?>/moe/assets/images/favicon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo $base_url; ?>/moe/assets/images/apple-touch-icon.png">
<link rel="shortcut icon" href="<?php echo $base_url; ?>/moe/assets/images/favicon.ico">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?php echo htmlspecialchars($current_url); ?>">
<meta property="og:title" content="<?php echo htmlspecialchars($seo_title); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($seo_description); ?>">
<meta property="og:image" content="<?php echo $base_url; ?>/moe/<?php echo $seo_og_image; ?>">
<meta property="og:site_name" content="MOE - Mediterranean of Egypt">
<meta property="og:locale" content="en_US">

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="<?php echo htmlspecialchars($current_url); ?>">
<meta property="twitter:title" content="<?php echo htmlspecialchars($seo_title); ?>">
<meta property="twitter:description" content="<?php echo htmlspecialchars($seo_description); ?>">
<meta property="twitter:image" content="<?php echo $base_url; ?>/moe/<?php echo $seo_og_image; ?>">

<!-- Theme Color -->
<meta name="theme-color" content="#0a0a0a">
<meta name="msapplication-TileColor" content="#0a0a0a">

<!-- Additional SEO -->
<link rel="canonical" href="<?php echo htmlspecialchars($current_url); ?>">