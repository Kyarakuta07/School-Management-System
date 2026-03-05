<?= $this->extend('layouts/base') ?>

<?= $this->section('head') ?>
<?php // Global User CSS ?>
<link rel="stylesheet" href="<?= asset_v('css/shared/global.css') ?>">
<link rel="stylesheet" href="<?= asset_v('css/shared/navbar.css') ?>">

<?php // Page CSS ?>
<?= $this->renderSection('css') ?>
<?= $this->endSection() ?>

<?= $this->section('body') ?>
<?php // Page content ?>
<?= $this->renderSection('content') ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->renderSection('page_scripts') ?>
<?= $this->endSection() ?>