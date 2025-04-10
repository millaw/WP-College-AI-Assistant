<?php
use PDF\PDFHelper;

class OllamaStream {
    public static function handle_request() {
        set_time_limit(0); // allow unlimited execution time

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no'); // disable Nginx buffering

        if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['q'])) {
            http_response_code(400);
            echo "event: error\ndata: Invalid request\n\n";
            exit;
        }

        $prompt = trim(strip_tags($_GET['q']));

        $ch = curl_init(API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'mistral:instruct',
                'prompt' => $prompt,
                'stream' => true
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-KEY: ' . API_KEY
            ],
            CURLOPT_WRITEFUNCTION => function($ch, $chunk) {
                $lines = explode("\n", $chunk);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!$line) continue;

                    $json = json_decode($line, true);
                    if (isset($json['response'])) {
                        $response = $json['response'];

                        // Escape special characters for SSE
                        $response = str_replace(["\r"], '', $response); // Remove \r
                        $response = str_replace("\n", "\\n", $response); // Temporarily escape newlines for transmission

                        echo "data: {$response}\n\n";
                        ob_flush();
                        flush();
                    }
                }
                return strlen($chunk);
            }
        ]);

        curl_exec($ch);
        curl_close($ch);
    }

    public static function handle_search($query) {
        // Sanitize the search query
        $query = sanitize_text_field($query);

        // Perform the search using WP_Query
        $search_query = new WP_Query([
            's' => $query,
            'posts_per_page' => 5, // Limit the number of results
        ]);

        $results = [];

        if ($search_query->have_posts()) {
            while ($search_query->have_posts()) {
                $search_query->the_post();
                $results[] = [
                    'title' => get_the_title(),
                    'link' => get_permalink(),
                    'excerpt' => wp_trim_words(get_the_excerpt(), 20),
                ];
            }
            wp_reset_postdata();
        }

        // Return the results as JSON
        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }

    public static function handle_ai_query($query) {
        // Sanitize the user query
        $query = sanitize_text_field($query);

        // Step 1: Search WordPress content first
        ob_start();
        self::handle_search($query);
        $search_results = ob_get_clean();

        $results = json_decode($search_results, true);
        $pdf_links = [];

        if (!empty($results)) {
            // Step 2: Check for PDFs in the content
            foreach ($results as $result) {
                $post_id = url_to_postid($result['link']);
                if ($post_id) {
                    $pdf_text = PDFHelper::extractTextFromPost($post_id);
                    if (stripos($pdf_text, $query) !== false) {
                        $pdf_links[] = $result['link'];
                    }
                }
            }

            // If PDFs are found, return them
            if (!empty($pdf_links)) {
                header('Content-Type: application/json');
                echo json_encode(['pdfs' => $pdf_links]);
                exit;
            }

            // If no PDFs are relevant, return the WordPress content
            header('Content-Type: application/json');
            echo json_encode(['response' => $results]);
            exit;
        }

        // Step 3: If no WordPress content is found, query the AI model
        $ch = curl_init(API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'mistral:instruct',
                'prompt' => $query,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-KEY: ' . API_KEY
            ],
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || !$response) {
            // Return an error response if the AI request fails
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to process the query.']);
            exit;
        }

        $ai_response = json_decode($response, true);

        // Return the AI response
        header('Content-Type: application/json');
        echo json_encode(['response' => $ai_response['response']]);
        exit;
    }

    public static function handle_pdf_search($query) {
        // Sanitize the search query
        $query = sanitize_text_field($query);

        // Include the PDF helper functions
        require_once plugin_dir_path(__FILE__) . '../pdf-helper.php';

        // Search PDFs for the query
        $matching_pdfs = search_pdfs_for_term($query);

        // Return the matching PDFs as JSON
        header('Content-Type: application/json');
        echo json_encode(['pdfs' => $matching_pdfs]);
        exit;
    }
}