<?php

if (!defined('ABSPATH')) {
    exit; // Direct script access denied.
}

do_action('avada_before_comments');

if (post_password_required()) {
    return;
}

if (have_comments()) : ?>
    <div id="comments" class="comments-container">
        <ol class="comment-list commentlist">
            <?php wp_list_comments(['callback' => 'avada_comment']); ?>
        </ol>

        <?php
        if (get_comment_pages_count() > 1 && get_option('page_comments')) {
            echo '<nav class="fusion-pagination">';
            paginate_comments_links([
                'prev_text' => '<span class="page-prev"></span><span class="page-text">' . esc_html__('Previous', 'Avada') . '</span>',
                'next_text' => '<span class="page-text">' . esc_html__('Next', 'Avada') . '</span><span class="page-next"></span>',
                'type'      => 'plain',
            ]);
            echo '</nav>';
        }
        ?>
    </div>
<?php endif;
$post_id = get_the_ID();
if (comments_open()) {
    $commenter = wp_get_current_commenter();
    // Aquí usamos la función personalizada para obtener la URL de la imagen de perfil
    $avatar_url = obtener_url_imagen_perfil_o_defecto(get_current_user_id()); 

    ?>
    <div id="respond" class="comment-respond">
        <?php

        $fields = array(
            'author' => '<div class="comment-form-field comment-input-name"><input id="author" name="author" type="text" placeholder="' . __('Name *', 'Avada') . '" value="' . esc_attr($commenter['comment_author']) . '" required="required" /></div>',
        );

        $comments_args = [
    'fields'               => apply_filters('comment_form_default_fields', $fields),
    'comment_field'        => '
        <input type="hidden" name="tab_id" id="tab_id_'.$post_id.'" value="">
        <div class="comment-form-avatar-and-textarea">
            <div class="comment-form-avatar">
                <img src="' . esc_url($avatar_url) . '" alt="Avatar" class="user-avatar" />
            </div>
            <div class="comment-textarea">
                <textarea name="comment" id="comment_'.$post_id.'" cols="115" rows="8" required="required" placeholder="' . esc_html__('Escribe una respuesta...', 'Avada') . '"></textarea>
                <div class="textarea-icons">
                    <label for="comment-image-upload_'.$post_id.'" class="textarea-icon">
                        <svg data-testid="geist-icon" height="15" stroke-linejoin="round" viewBox="0 0 16 16" width="12" style="color: currentcolor;"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.5 2.5H1.5V9.18933L2.96966 7.71967L3.18933 7.5H3.49999H6.63001H6.93933L6.96966 7.46967L10.4697 3.96967L11.5303 3.96967L14.5 6.93934V2.5ZM8.00066 8.55999L9.53034 10.0897L10.0607 10.62L9.00001 11.6807L8.46968 11.1503L6.31935 9H3.81065L1.53032 11.2803L1.5 11.3106V12.5C1.5 13.0523 1.94772 13.5 2.5 13.5H13.5C14.0523 13.5 14.5 13.0523 14.5 12.5V9.06066L11 5.56066L8.03032 8.53033L8.00066 8.55999ZM4.05312e-06 10.8107V12.5C4.05312e-06 13.8807 1.11929 15 2.5 15H13.5C14.8807 15 16 13.8807 16 12.5V9.56066L16.5607 9L16.0303 8.46967L16 8.43934V2.5V1H14.5H1.5H4.05312e-06V2.5V10.6893L-0.0606689 10.75L4.05312e-06 10.8107Z" fill="currentColor"></path></svg>
                    </label>
                    <input id="comment-image-upload_'.$post_id.'" name="comment_image" type="file" accept="image/*" style="display:none;">
                    <label for="comment-audio-upload_'.$post_id.'" class="textarea-icon">
                        <svg data-testid="geist-icon" height="16" stroke-linejoin="round" viewBox="0 0 16 16" width="15" style="color: currentcolor;"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.25 1H5.5V1.75V9.40135C5.05874 9.14609 4.54643 9 4 9C2.34315 9 1 10.3431 1 12C1 13.6569 2.34315 15 4 15C5.65685 15 7 13.6569 7 12C7 11.9158 6.99653 11.8324 6.98973 11.75H7V11V2.5H13.5V6.90135C13.0587 6.64609 12.5464 6.5 12 6.5C10.3431 6.5 9 7.84315 9 9.5C9 11.1569 10.3431 12.5 12 12.5C13.6569 12.5 15 11.1569 15 9.5C15 9.41581 14.9965 9.33243 14.9897 9.25H15V8.5V1.75V1H14.25H6.25ZM10.5 9.5C10.5 10.3284 11.1716 11 12 11C12.8284 11 13.5 10.3284 13.5 9.5C13.5 8.67157 12.8284 8 12 8C11.1716 8 10.5 8.67157 10.5 9.5ZM2.5 12C2.5 12.8284 3.17157 13.5 4 13.5C4.82843 13.5 5.5 12.8284 5.5 12C5.5 11.1716 4.82843 10.5 4 10.5C3.17157 10.5 2.5 11.1716 2.5 12Z" fill="currentColor"></path></svg>
                    </label>
                    <input id="comment-audio-upload_'.$post_id.'" name="comment_audio" type="file" accept="audio/*" style="display:none;">
                </div>
            </div>
        </div>',
            'title_reply'          => '', 
            'title_reply_to'       => esc_html__('Responder a %s', 'Avada'), 
            'class_form'           => 'custom-comment-form', 
            'class_submit'         => 'botoncomentarios',
            'label_submit' => '',
            'comment_notes_before' => '',
            'comment_notes_after'  => '',
        ];

        comment_form($comments_args);
        ?>
    </div>
    <?php
}


?>
