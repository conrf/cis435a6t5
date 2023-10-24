<?php

function term_frequency($term, $document) {
    $num_terms = count($document);
    $term_count = count(array_keys($document, $term));
    return $term_count / $num_terms;
}

function inverse_document_frequency($term, $all_documents) {
    $num_documents_with_term = 0;
    foreach ($all_documents as $document) {
        if (in_array($term, $document)) {
            $num_documents_with_term++;
        }
    }
    if ($num_documents_with_term > 0) {
        return log(count($all_documents) / $num_documents_with_term);
    } else {
        return 0;
    }
}

function cosine_similarity($vector1, $vector2) {
    $dot_product = 0.0;
    $magnitude1 = 0.0;
    $magnitude2 = 0.0;
    foreach ($vector1 as $key => $value) {
        if (isset($vector2[$key])) {
            $dot_product += $value * $vector2[$key];
        }
        $magnitude1 += $value * $value;
    }
    foreach ($vector2 as $key => $value) {
        $magnitude2 += $value * $value;
    }
    $magnitude = sqrt($magnitude1) * sqrt($magnitude2);
    return ($magnitude > 0) ? $dot_product / $magnitude : 0;
}

function normalize_vector($vector) {
    $magnitude = 0.0;
    foreach ($vector as $value) {
        $magnitude += $value * $value;
    }
    $magnitude = sqrt($magnitude);

    if ($magnitude > 0) {
        foreach ($vector as $term => $value) {
            $vector[$term] = $value / $magnitude;
        }
    }
    return $vector;
}

function get_related_articles($article_id, $num_recommendations = 3) {
    global $connection;

    // Fetch all articles
    $statement = $connection->prepare("SELECT * FROM articles");
    $statement->execute();
    $articles = $statement->fetchAll(PDO::FETCH_ASSOC);

    // Get the texts of all articles
    $all_texts = array_column($articles, 'article_text');
    $all_ids = array_column($articles, 'id');

    // Tokenize and preprocess the texts
    $tokenized_texts = [];
    foreach ($all_texts as $text) {
        $tokenized_texts[] = array_map('strtolower', preg_split('/\W+/', $text, -1, PREG_SPLIT_NO_EMPTY));
    }

   // Calculate IDF values for each unique term across all documents
   $unique_terms = [];
   foreach ($tokenized_texts as $document) {
       foreach ($document as $term) {
           if (!isset($unique_terms[$term])) {
               $unique_terms[$term] = inverse_document_frequency($term, $tokenized_texts);
           }
       }
   }

   // Calculate the TF-IDF vectors for each document
   $tfidf_vectors = [];
   foreach ($tokenized_texts as $document) {
       $tfidf_vector = [];
       foreach ($document as $term) {
           $tf = term_frequency($term, $document);
           $idf = $unique_terms[$term];
           $tfidf_vector[$term] = $tf * $idf;
       }
       $tfidf_vectors[] = normalize_vector($tfidf_vector);
   }

    // Compute cosine similarities with the given article
    $article_index = array_search($article_id, $all_ids);
    $cosine_similarities = [];
    foreach ($tfidf_vectors as $index => $tfidf_vector) {
        $cosine_similarities[$index] = cosine_similarity($tfidf_vectors[$article_index], $tfidf_vector);
    }

    // Sort articles by similarity
    arsort($cosine_similarities);

    // Get the top N most similar articles
    $related_ids = [];
    $counter = 0;
    foreach ($cosine_similarities as $index => $similarity) {
        if ($counter >= $num_recommendations) break;
        if ($index != $article_index) {
            $related_ids[] = $all_ids[$index];
            $counter++;
        }
    }

    return $related_ids;
}

?>
