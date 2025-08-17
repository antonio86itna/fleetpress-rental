<?php if (!defined('ABSPATH')) exit; get_header(); the_post(); ?>
<main class="fpr-vehicle">
  <h1><?php the_title(); ?></h1>
  <?php the_post_thumbnail('large'); ?>
  <div class="fpr-meta"><?php the_content(); ?></div>

  <form id="fpr-book" class="fpr-book">
    <input type="hidden" name="vehicle_id" value="<?php echo esc_attr(get_the_ID()); ?>">
    <label><?php _e('From', 'fpr'); ?><input type="date" name="from" required></label>
    <label><?php _e('To', 'fpr'); ?><input type="date" name="to" required></label>
    <label><?php _e('Quantity', 'fpr'); ?><input type="number" name="qty" min="1" value="1" required></label>
    <div id="fpr-price"></div>
    <button type="submit"><?php _e('Book with Stripe', 'fpr'); ?></button>
  </form>
</main>
<?php get_footer(); ?>
