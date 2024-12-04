<?php
/**
 * Charge les produits depuis une API REST
 * 
 * @param string $apiUrl L'URL de l'API à interroger
 * @return array|null Tableau associatif contenant les produits ou null en cas d'erreur
 */
function loadProducts( $apiUrl ) {
    $response = file_get_contents($apiUrl);
    $data = json_decode($response, true);
    
    return $data;
}


/**
 * Filtre les produits par catégorie
 * 
 * @param array $products Tableau des produits à filtrer
 * @param string $category Catégorie à filtrer
 * @return array Tableau des produits filtrés par catégorie
 */
function getProductsByCategory( $products, $category ) {
    return array_filter($products, function($product) use ($category) {
        return empty($category) || $product['category'] === $category;
    });
}


/**
 * Filtre les produits par tranche de prix
 * 
 * @param array $products Tableau des produits
 * @param float|null $minPrice Prix minimum (null pour pas de minimum)
 * @param float|null $maxPrice Prix maximum (null pour pas de maximum)
 * @return array Produits filtrés
 */
function getProductsByPriceRange($products, $minPrice, $maxPrice) {
    return array_filter($products, function($product) use ($minPrice, $maxPrice) {
        return ($minPrice === null || $product['price'] >= $minPrice) && 
               ($maxPrice === null || $product['price'] <= $maxPrice);
    });
}


/**
 * Trie les produits par prix
 * 
 * @param array $products Produits à trier
 * @param string $order 'asc' ou 'desc'
 * @return array Produits triés
 */
function sortProductsByPrice($products, $order) {
    usort($products, function($a, $b) use ($order) {
        if ($order === 'asc') {
            return $a['price'] - $b['price'];
        } else {
            return $b['price'] - $a['price'];
        }
    });
    return $products;
}

/**
 * Obtient toutes les catégories uniques des produits
 * Approche avec foreach
 * 
 * @param array $products Tableau des produits
 * @return array Tableau des catégories uniques
 */
function getAllCategories($products) {
    $categories = [];
    foreach ($products as $product) {
        if (!in_array($product['category'], $categories)) {
            $categories[] = $product['category'];
        }
    }
    return $categories;
}

$products = loadProducts("https://fakestoreapi.com/products");
$categories = getAllCategories($products);

$category = $_POST['category'] ?? '';
$minPrice = $_POST['min_price'] ?? null;
$maxPrice = $_POST['max_price'] ?? null;
$sort = $_POST['sort'] ?? '';

if ($category) {
    $products = getProductsByCategory($products, $category);
}

if ($minPrice || $maxPrice) {
    $products = getProductsByPriceRange($products, $minPrice, $maxPrice);
}

if ($sort) {
    $products = sortProductsByPrice($products, $sort);
}

?>





<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue de Produits </title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .filters {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filter-group {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .filter-item {
            flex: 1;
            min-width: 250px;
            margin-bottom: 15px;
        }

        .filter-item label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        select, input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .price-range {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .price-range input {
            flex: 1;
        }

        .products-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: flex-start;
        }

        .product-card {
            flex: 0 1 calc(25% - 15px); /* 4 cartes par ligne avec gap */
            min-width: 250px;
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
        }


        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: contain;
            margin-bottom: 10px;
        }

        .product-title {
            font-size: 1rem;
            margin-bottom: 10px;
            height: 2.4em;
            overflow: hidden;
        }

        .product-category {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .product-price {
            font-weight: bold;
            color: #2c3e50;
            font-size: 1.2rem;
            margin-top: auto; /* Pousse le prix vers le bas de la carte */
        }
        .filter-button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
            margin-top: 20px;
            width: 100%;
            max-width: 200px;
        }

        .filter-button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <form method="post">
            <div class="filters">
                <div class="filter-group">
                    <div class="filter-item">
                        <label for="category">Catégorie:</label>
                        <select name="category">
                            <option value="">Toutes les catégories</option>
                            <?php 
                            foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                    <?= ucfirst($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-item">
                        <label>Prix:</label>
                        <div class="price-range">
                                <input type="number" name="min_price" placeholder="Min" min="0" >
                                <input type="number" name="max_price" placeholder="Max" min="0">
                        </div>
                    </div>

                    <div class="filter-item">
                        <label for="sort">Trier par:</label>
                        <select id="sort" name="sort">
                                <option value="">Aucun tri</option>
                                <option value="asc">
                                    Prix croissant
                                </option>
                                <option value="desc">
                                    Prix décroissant
                                </option>
                            </select>
                        
                    </div>
                    <button type="submit" class="filter-button">Filtrer les produits</button>   
                </div>
            </form>
        </div>
           
      
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <img src="<?= $product['image'] ?>" alt="<?= $product['title'] ?>" class="product-image">
                    <h3 class="product-title"><?= $product['title'] ?></h3>
                    <div class="product-category"><?= ucfirst($product['category']) ?></div>
                    <div class="product-price"><?= $product['price'] ?> dh</div>
                </div>
            <?php endforeach; ?>         
        </div>
    </div>

</body>
</html>