<?php

// Action: Created SEOService class
// Purpose: Handles generation of SEO meta tags (title, description, schema) for different post types.

// Refactor: Ensure BrowserUtils is available for language detection
require_once get_template_directory() . '/app/Utils/BrowserUtils.php';

class SEOService {

    /**
     * Generates the SEO title for a given post.
     *
     * @param WP_Post|null $post The post object. Defaults to global $post.
     * @return string The generated SEO title.
     */
    public function generateTitle($post = null) {
        if (!$post) {
            global $post;
        }
        if (!$post) {
            return get_bloginfo('name'); // Fallback title
        }

        // TODO: Implement title generation logic based on post type, custom fields, etc.
        $title = get_the_title($post);
        $site_name = get_bloginfo('name');

        // Example: Append site name
        return $title . ' - ' . $site_name;
    }

    /**
     * Generates the meta description for a given post.
     *
     * @param WP_Post|null $post The post object. Defaults to global $post.
     * @return string The generated meta description.
     */
    public function generateDescription($post = null) {
        if (!$post) {
            global $post;
        }
        if (!$post) {
            return get_bloginfo('description'); // Fallback description
        }

        // TODO: Implement description generation logic (e.g., excerpt, custom field)
        $description = '';
        if (has_excerpt($post)) {
            $description = get_the_excerpt($post);
        } else {
            // Maybe generate from content or use a default
             $description = wp_trim_words(strip_shortcodes(strip_tags($post->post_content)), 55, '...');
        }

        if (empty($description)) {
            $description = get_bloginfo('description');
        }

        return esc_attr($description);
    }

    /**
     * Generates Schema.org markup for a given post.
     *
     * @param WP_Post|null $post The post object. Defaults to global $post.
     * @return string JSON-LD Schema markup.
     */
    public function generateSchema($post = null) {
         if (!$post) {
            global $post;
        }
        if (!$post) {
            return ''; // No schema if no post context
        }

        // TODO: Implement detailed Schema generation based on post type
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage', // Default type
            'name' => $this->generateTitle($post),
            'description' => $this->generateDescription($post),
            'url' => get_permalink($post),
            // Add more properties based on post type (Article, Product, etc.)
        ];

        // Example for a generic post (could be refined for 'post', 'page', etc.)
        if (is_singular()) { // More specific types for single posts/pages
             $schema['@type'] = 'Article'; // Or BlogPosting, NewsArticle, etc.
             $schema['headline'] = get_the_title($post);
             $schema['datePublished'] = get_the_date('c', $post);
             $schema['dateModified'] = get_the_modified_date('c', $post);
             // Add author, publisher, image etc.
             $schema['author'] = [
                 '@type' => 'Person', // Or Organization
                 'name' => get_the_author_meta('display_name', $post->post_author)
             ];
             // Add publisher info if available
             $schema['publisher'] = [
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                // 'logo' => [
                //     '@type' => 'ImageObject',
                //     'url' => 'URL_TO_LOGO_IMAGE'
                // ]
             ];
             if (has_post_thumbnail($post)) {
                $schema['image'] = [
                    '@type' => 'ImageObject',
                    'url' => get_the_post_thumbnail_url($post, 'full'), // Or another appropriate size
                ];
             }
        }


        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }

    /**
     * Outputs all standard SEO meta tags for the current context.
     * Typically called within the <head> section.
     */
    public function outputMetaTags() {
        global $post; // Use the global post object by default

        $title = $this->generateTitle($post);
        $description = $this->generateDescription($post);
        $schema = $this->generateSchema($post);

        echo '<title>' . esc_html($title) . '</title>' . "\n";
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        // Add other meta tags like Open Graph, Twitter Cards etc. here
        // Example Open Graph
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_permalink($post)) . '">' . "\n";
        echo '<meta property="og:type" content="' . (is_singular() ? 'article' : 'website') . '">' . "\n"; // Basic type
        if (is_singular() && has_post_thumbnail($post)) {
             echo '<meta property="og:image" content="' . esc_url(get_the_post_thumbnail_url($post, 'large')) . '">' . "\n";
        }
        // Example Twitter Card
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n"; // Or 'summary'
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
         if (is_singular() && has_post_thumbnail($post)) {
             echo '<meta name="twitter:image" content="' . esc_url(get_the_post_thumbnail_url($post, 'large')) . '">' . "\n";
        }

        // Output Schema
        echo $schema . "\n"; // Schema already includes <script> tags
    }

    /**
     * Generates SEO meta tags specifically for 'colecciones' post type.
     *
     * @param int $postId The ID of the 'coleccion' post.
     * @return array An array containing 'title', 'description', and 'schema'.
     */
    public function generateColeccionMetaTags($postId) {
        // Refactor: Moved SEO generation logic from single-colecciones.php

        // Obtener el título de la colección
        $post_title = get_the_title($postId);

        // Generar el título SEO: Primera letra en mayúscula y añadir "| Drum kit & Sample Pack"
        $seo_title = ucfirst($post_title) . ' | Drum kit & Sample Pack';

        // Meta descripción
        $meta_description_full = get_post_field('post_content', $postId); // Obtener el contenido completo
        $meta_description = mb_substr(wp_strip_all_tags($meta_description_full), 0, 160);
        $meta_description = esc_attr($meta_description);

        // Esquema JSON-LD
        $schema = [
            "@context"    => "https://schema.org",
            "@type"       => "CollectionPage", // Tipo de esquema para una colección
            "name"        => $seo_title,
            "description" => $meta_description,
            "datePublished" => get_the_date('c', $postId),
            "author"      => [
                "@type" => "Person",
                "name"  => get_the_author_meta('display_name', get_post_field('post_author', $postId))
            ]
        ];

        // Return the calculated SEO data
        return ['title' => $seo_title, 'description' => $meta_description, 'schema' => $schema];
    }

    /**
     * Generates SEO meta tags specifically for 'social_post' post type.
     *
     * @param int $postId The ID of the 'social_post' post.
     * @return array An array containing 'title', 'description', and 'schema'.
     */
    public function generateSocialPostMetaTags($postId) {
        // Refactor(Org): Moved SEO meta generation logic from single-social_post.php
        $active_lang     = obtenerIdiomaDelNavegador();
        $filtro          = 'single'; // This might not be needed here, depends on htmlPost usage context

        $datos_algoritmo_pri      = get_post_meta($postId, 'datosAlgoritmo', true);
        $datos_algoritmo_respaldo = get_post_meta($postId, 'datosAlgoritmo_respaldo', true);

        $datosAlgoritmo = empty($datos_algoritmo_pri) ? $datos_algoritmo_respaldo : $datos_algoritmo_pri;
        $datos_decoded  = is_string($datosAlgoritmo) ? json_decode($datosAlgoritmo, true) : $datosAlgoritmo;

        // Generar el título SEO
        $post_title = get_the_title($postId);
        $tipo_audio = isset($datos_decoded['tipo_audio'][$active_lang][0]) ? $datos_decoded['tipo_audio'][$active_lang][0] : '';

        // Para evitar duplicar "Sample": si el título ya lo tiene, no se añade al tipo de audio
        if (stripos($post_title, 'sample') === false && stripos($tipo_audio, 'sample') === false) {
            $tipo_audio .= ' Sample';
        }

        // Añadir el sufijo (en este ejemplo se usa "free" para ambos idiomas)
        $seo_suffix = 'free';
        if (stripos($tipo_audio, $seo_suffix) === false) {
            $tipo_audio .= ' ' . $seo_suffix;
        }

        $seo_title = $post_title . ' | ' . $tipo_audio;

        // Construir la parte base de la descripción (usando sugerencias o descripción corta)
        $base_desc = '';
        if (isset($datos_decoded['sugerencia_busqueda'][$active_lang]) && ! empty($datos_decoded['sugerencia_busqueda'][$active_lang])) {
            $base_desc = implode(', ', $datos_decoded['sugerencia_busqueda'][$active_lang]);
        } elseif (isset($datos_decoded['descripcion_corta'][$active_lang]) && ! empty($datos_decoded['descripcion_corta'][$active_lang])) {
            $base_desc = implode(', ', $datos_decoded['descripcion_corta'][$active_lang]);
        }

        // Definir el sufijo fijo para la descripción
        $desc_suffix = 'Download samples, beats and drum kits free';

        // Armar la descripción final:
        // - Se asegura que la parte base inicie en mayúscula
        // - Se agrega el separador " - " antes del sufijo
        $final_desc = ucfirst(trim($base_desc));
        if (! empty($final_desc)) {
            $final_desc .= ' - ' . $desc_suffix;
        } else {
            $final_desc = $desc_suffix;
        }

        // Si la descripción excede los 160 caracteres, se trunca y se añade "..."
        if (mb_strlen($final_desc) > 160) {
            $final_desc = mb_substr($final_desc, 0, 160 - 3) . '...';
        }

        $meta_description = esc_attr(trim($final_desc));

        // Esquema JSON-LD
        $schema = [
            "@context"      => "https://schema.org",
            "@type"         => "AudioObject",
            "name"          => $seo_title,
            "description"   => $meta_description,
            "datePublished" => get_the_date('c', $postId),
            "author"        => [
                "@type" => "Person",
                "name"  => get_the_author_meta('display_name', get_post_field('post_author', $postId))
            ]
        ];

        if (! empty($datos_decoded)) {
            if (isset($datos_decoded['genero_posible'][$active_lang])) {
                $schema['genre'] = esc_html(implode(", ", $datos_decoded['genero_posible'][$active_lang]));
            }
            if (isset($datos_decoded['tags_posibles'][$active_lang])) {
                // Limitar a 5 tags para el SEO
                $tags_array         = array_slice($datos_decoded['tags_posibles'][$active_lang], 0, 5);
                $schema['keywords'] = esc_html(implode(", ", $tags_array));
            }
        }

        // Return the calculated SEO data
        return ['title' => $seo_title, 'description' => $meta_description, 'schema' => $schema];
    }

}
