<?php

// Action: Created SEOService class
// Purpose: Handles generation of SEO meta tags (title, description, schema) for different post types.

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

}
