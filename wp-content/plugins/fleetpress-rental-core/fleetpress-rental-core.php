<?php
/**
 * Plugin Name: FleetPress Rental Core
 * Description: Custom rental engine (cars & scooters) for WordPress: inventory, rates, availability, bookings, Stripe, emails.
 * Version: 0.1.0
 * Text Domain: fpr
 */
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/includes/class-plugin.php';
Fpr\Rental\Plugin::init();
