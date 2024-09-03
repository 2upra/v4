<?php get_header(); ?>

<ul class="social-post-list">

    <li class="social-post">
        <?php
        $current_user_id = get_current_user_id();
        $es_suscriptor = get_user_meta($current_user_id, 'subscriber_id', true) ? true : false;
        $likes = get_post_meta(get_the_ID(), '_post_likes', true);
        $like_count = is_array($likes) ? count($likes) : 0;
        $user_has_liked = is_array($likes) && in_array($current_user_id, $likes);
        $liked_class = $user_has_liked ? 'liked' : '';
        $author_id = get_the_author_meta('ID');
        $author_name = get_the_author();
        $author_avatar = get_avatar_url($author_id);
        $audio_id = get_post_meta(get_the_ID(), 'post_audio', true);
        $audio_url = wp_get_attachment_url($audio_id);
        $post_date = get_the_date();
        $contenido_para_suscriptores = get_post_meta(get_the_ID(), 'content-block', true);
        $puntuacion_final = get_post_meta(get_the_ID(), '_post_puntuacion_final', true);

        ?>

        <div class="profile-info">
            <img src="<?php echo esc_url($author_avatar); ?>" alt="Avatar" class="profile-picture">
            <div class="text-container"> 
              <p class="nombre-usuario"><a href="<?php echo esc_url(get_author_posts_url($author_id)); ?>" class="profile-link"><?php echo esc_html($author_name); ?></a></p>
              <p class="post-date"><a href="<?php echo esc_url(get_permalink()); ?>" class="post-link"><?php echo esc_html($post_date); ?></a></p>
          </div>
          <?php 
          if ($current_user_id == $author_id || current_user_can('delete_others_posts')) {
            echo '<button class="delete-post-button" data-post_id="' . get_the_ID() . '" data-nonce="' . wp_create_nonce('delete_post_nonce') . '"><i class="fa fa-trash"></i></button>';
        }
        ?>
    </div>

    <?php if ($contenido_para_suscriptores && !$es_suscriptor): ?>
        <div class="content-blocked-message">
            <p>Esta publicación es exclusiva para suscriptores de <?php echo esc_html($author_name); ?></p>
            <?php if (has_post_thumbnail()): 
                $post_thumbnail_id = get_post_thumbnail_id();
                $post_thumbnail_data = wp_get_attachment_image_src($post_thumbnail_id, 'thumbnail'); // 'thumbnail' es el tamaño de la imagen
                $post_thumbnail_url = $post_thumbnail_data[0];
                ?>
                <div style="position: relative; text-align: center;">
                    <img src="<?php echo esc_url($post_thumbnail_url); ?>" alt="Post Image" style="filter: blur(9px); width: 100%; max-width: 100%; opacity: 60%;">
                    
                    <?php 
                    $subscription_price_id = 'price_1OqGjlCdHJpmDkrryMzL0BCK';
                    $output = '<button style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);" ';
                $output .= 'class="custom-subscribe-btn" ';
                $output .= 'data-offering-user-id="' . esc_attr($author_id) . '" ';
                $output .= 'data-offering-user-login="' . esc_attr($author_name) . '" '; 
                $output .= 'data-offering-user-email="' . esc_attr(get_the_author_meta('user_email', $author_id)) . '" ';
                $output .= 'data-subscriber-user-id="' . esc_attr($current_user_id) . '" ';
                $output .= 'data-subscriber-user-login="' . esc_attr(wp_get_current_user()->user_login) . '" ';
                $output .= 'data-subscriber-user-email="' . esc_attr(wp_get_current_user()->user_email) . '" ';
                $output .= 'data-price="' . esc_attr($subscription_price_id) . '" '; 
                $output .= 'data-url="' . esc_url(get_permalink()) . '" ';
                $output .= '>Suscribirse</button>'; 
                echo $output;
                ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
 
    <div class="social-post-content" style="font-size: 13px;">
         <?php 
        if (current_user_can('administrator')): ?>
            <div class="puntuacion-post">
                <p><?php echo esc_html($puntuacion_final); ?></p>
            </div>
        <?php endif; ?>
        <?php the_content(); ?>
        <?php if ($audio_url) : ?>

            <div id="waveform-<?php echo get_the_ID(); ?>" class="waveform-container <?php echo has_post_thumbnail() ? 'with-image' : 'without-image'; ?>" data-audio-url="<?php echo esc_attr($audio_url); ?>">

                <?php if (has_post_thumbnail()) : 
                    $post_thumbnail_id = get_post_thumbnail_id();
                    $post_thumbnail_url = wp_get_attachment_image_url($post_thumbnail_id, 'full');?>
                    <div class="waveform-background-2" style="background-image: url('<?php echo esc_url($post_thumbnail_url); ?>');"></div>
                <?php endif; ?>
                <div class="waveform-background" style="background-image: url('http://2upra.com/wp-content/uploads/2024/02/descarga-1.png');"></div>
                <div class="waveform-message"></div>
                <div class="waveform-loading" style="display: none;">Cargando...</div>
            </div>
                <?php elseif (has_post_thumbnail()) : ?>
                <?php 
                $post_thumbnail_id = get_post_thumbnail_id();
                $post_thumbnail_url = wp_get_attachment_image_url($post_thumbnail_id, 'large'); ?>
                <div class="post-image-container" style="margin-top: 20px;">
                    <img src="<?php echo esc_url($post_thumbnail_url); ?>" alt="Imagen del post" style="width: 100%; display: block;">
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php
    echo '<div class="data-post">';
    $categories = get_the_category();
    $separator = ', ';
    $output = '';

    if (!empty($categories)) {
      foreach ($categories as $category) {
        $output .= '<a href="' . esc_url(get_category_link($category->term_id)) . '" alt="' . esc_attr(sprintf(__('View all posts in %s', 'textdomain'), $category->name)) . '">' . esc_html($category->name) . '</a>' . $separator;
    }
    echo '<p class="post-categories">' . trim($output, $separator) . '</p>';
}
echo get_the_tag_list('<p class="post-tags">Tags: ', ', ', '</p>');
echo '</div>';?>

<div class="social-post-like">
    <button class="post-like-button <?php echo esc_attr($liked_class); ?>" data-post_id="<?php echo get_the_ID(); ?>" data-nonce="<?php echo wp_create_nonce('like_post_nonce'); ?>"><i class="fa-heart fas"></i></button>
    <span class="like-count"><?php echo $like_count; ?></span>

    <?php
    $views_count = get_post_meta(get_the_ID(), 'avada_post_views_count', true);
    if (!empty($views_count)) {
        echo '<span class="fa-play fas"></span> <span class="views-text"> ' . $views_count . ' </span>';}?>
        <span class="icon-bubble"></span> <span class="comments-text"> <?php echo get_comments_number(); ?></span>

        <?php
        $allow_download = get_post_meta(get_the_ID(), 'allow_download', true);
        if ($allow_download) {
            echo '<a href="' . esc_url($audio_url) . '" class="icon-arrow-down"></a>';}?>

            <?php
            $post_price = get_post_meta(get_the_ID(), 'post_price', true);
            $current_user = wp_get_current_user();
            $author_id = get_post_field('post_author', get_the_ID());
            $author_userdata = get_userdata($author_id);
            $post_thumbnail_id = get_post_thumbnail_id();
            $post_thumbnail_url = wp_get_attachment_image_url($post_thumbnail_id, 'full');
            $audio_id = get_post_meta(get_the_ID(), 'post_audio', true);
            $audio_url = wp_get_attachment_url($audio_id);

            if (!empty($post_price)) : ?>
                <button class="stripe-checkout-button"
                data-product-id="<?php echo get_the_ID(); ?>" 
                data-product-price="<?php echo esc_attr($post_price); ?>"
                data-buyer-id="<?php echo esc_attr($current_user->ID); ?>" 
                data-buyer-username="<?php echo esc_attr($current_user->user_login); ?>" 
                data-seller-id="<?php echo esc_attr($author_id); ?>" 
                data-seller-username="<?php echo esc_attr($author_userdata->user_login); ?>"
                data-image-url="<?php echo esc_url($post_thumbnail_url); ?>" 
                data-audio-url="<?php echo esc_attr($audio_url); ?>">
                COMPRAR <?php echo esc_html($post_price); ?>$
            </button>
        <?php endif; ?>


    </div>
</li>

<?php if (comments_open() || get_comments_number()) :
comments_template();
endif; ?> 
</ul>

<?php get_footer(); ?>