<?php
if (!defined('ABSPATH')) exit;

add_action('after_setup_theme', function () {
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
});

add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style('fpr-theme', get_stylesheet_uri(), [], '0.1.0');
});
