<?php //TODO make this a smarty template
include('db.php');
include('function.php');
include('related_articles.php');

// Require id in query params
$id = $_GET["id"];

$related_article_ids = get_related_articles($id);

$statement = $connection->prepare(
    "SELECT * FROM articles WHERE id=:id"
);

$statement->execute(
    array(
        ":id" => $id
    )
);

// Grab article info
$article = $statement->fetchObject("ArticleDTO");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <title>Article</title>
</head>


<body>
    <a id="add_button" data-toggle="modal" data-target="#userModal" class="btn btn-info btn-lg"
        href="edit_article.php?id=<?= $article->id ?>">
        Edit This Article
    </a>

    <p>
        <b>Author name:</b>
        <?= $article->author_name ?>
    </p>

    <div class="container">
        <?= $article->article_text ?>
    </div>

    <p>
        <b><br>Related Articles</b>
    </p>
</body>

</html>

<?php

foreach ($related_article_ids as $related_id) {
    $statement = $connection->prepare("SELECT * FROM articles WHERE id = :id");
    $statement->execute(array(":id" => $related_id));
    $related_article = $statement->fetch(PDO::FETCH_ASSOC);

     // Display the related article (modify this as per your design)
     echo "<h5>" . $related_article['author_name'] . "</h5>";
     echo "<p>" . $related_article['article_text'] . "</p>";
     echo "<a href='view_article.php?id=" . $related_article['id'] . "'>Read More</a>";
     echo "<hr>";  // a line separator for each article
 }
?>