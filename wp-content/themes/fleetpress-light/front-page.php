<?php /* Template: Home (Search + Featured) */ if (!defined('ABSPATH')) exit; get_header(); ?>
<main class="fpr-home">
  <h1><?php echo esc_html__('Find your ride', 'fpr'); ?></h1>
  <form id="fpr-search" class="fpr-search">
    <label><?php _e('From', 'fpr'); ?><input type="date" name="from" required></label>
    <label><?php _e('To', 'fpr'); ?><input type="date" name="to" required></label>
    <label><?php _e('Type', 'fpr'); ?>
      <select name="type">
        <option value=""><?php _e('Any', 'fpr'); ?></option>
        <option value="car"><?php _e('Car', 'fpr'); ?></option>
        <option value="scooter"><?php _e('Scooter', 'fpr'); ?></option>
      </select>
    </label>
    <button type="submit"><?php _e('Search', 'fpr'); ?></button>
  </form>
  <section id="fpr-results"></section>
</main>
<?php get_footer(); ?>
