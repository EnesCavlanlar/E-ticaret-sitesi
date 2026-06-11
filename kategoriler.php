<?php
// Ana kategorileri getir
try {
    $stmt = $conn->prepare("SELECT * FROM Kategori WHERE ust_kategori_id IS NULL");
    $stmt->execute();
    $anaKategoriler = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Kategori yükleme hatası: " . $e->getMessage();
    $anaKategoriler = [];
}

// Seçili kategoriyi al
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : null;
?>

<style>
    .categories-container {
        width: 100%;
        margin: 0 0 30px 0;
        padding: 0;
        background: var(--white);
        box-shadow: var(--shadow-sm);
        border-radius: 0;
    }

    .category-menu {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-wrap: wrap;
        max-width: 100%;
        justify-content: center;
    }

    .category-menu-item {
        position: relative;
    }

    .category-menu-link {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 15px 20px;
        color: var(--text-color);
        text-decoration: none;
        transition: all 0.2s;
        font-size: 14px;
        font-weight: 500;
        white-space: nowrap;
    }

    .category-menu-link:hover,
    .category-menu-link.active {
        background: var(--primary-color);
        color: white;
    }

    .category-menu-link i {
        font-size: 12px;
        transition: transform 0.2s;
    }

    .category-menu-item:hover .category-menu-link i {
        transform: rotate(90deg);
    }

    .subcategories {
        display: none;
        position: absolute;
        left: 0;
        top: 100%;
        min-width: 200px;
        background: var(--white);
        border-radius: 0 0 8px 8px;
        box-shadow: var(--shadow-md);
        z-index: 100;
        padding: 10px 0;
    }

    .category-menu-item:hover .subcategories {
        display: block;
    }

    .subcategory-link {
        display: block;
        padding: 8px 20px;
        color: var(--text-color);
        text-decoration: none;
        font-size: 14px;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .subcategory-link:hover,
    .subcategory-link.active {
        background: var(--primary-color);
        color: white;
    }

    @media (max-width: 768px) {
        .category-menu {
            flex-direction: column;
        }
        
        .category-menu-link {
            padding: 12px 15px;
        }
        
        .subcategories {
            position: static;
            box-shadow: none;
            padding-left: 20px;
            display: none;
        }
        
        .category-menu-item:hover .subcategories {
            display: none;
        }
        
        .category-menu-item.active .subcategories {
            display: block;
        }
    }
</style>

<nav class="categories-container">
    <ul class="category-menu">
        <li class="category-menu-item">
            <a href="index.php" class="category-menu-link <?php echo !$selected_category ? 'active' : ''; ?>">
                Tüm Kategoriler
            </a>
        </li>
        <?php 
        // Alt kategorileri getir
        try {
            $stmt = $conn->prepare("SELECT * FROM Kategori WHERE ust_kategori_id = :ust_id");
            
            foreach($anaKategoriler as $anaKategori): 
                $stmt->bindParam(':ust_id', $anaKategori['kategori_id']);
                $stmt->execute();
                $altKategoriler = $stmt->fetchAll();
        ?>
            <li class="category-menu-item">
                <a href="index.php?category=<?= $anaKategori['kategori_id'] ?>" 
                   class="category-menu-link <?php echo $selected_category == $anaKategori['kategori_id'] ? 'active' : ''; ?>">
                    <?= htmlspecialchars($anaKategori['kategori_adi']) ?>
                    <?php if(count($altKategoriler) > 0): ?>
                        <i class="fas fa-chevron-right"></i>
                    <?php endif; ?>
                </a>
                <?php if(count($altKategoriler) > 0): ?>
                    <div class="subcategories">
                        <?php foreach($altKategoriler as $altKategori): ?>
                            <a href="index.php?category=<?= $altKategori['kategori_id'] ?>" 
                               class="subcategory-link <?php echo $selected_category == $altKategori['kategori_id'] ? 'active' : ''; ?>">
                                <?= htmlspecialchars($altKategori['kategori_adi']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </li>
        <?php 
            endforeach; 
        } catch(PDOException $e) {
            echo "Alt kategori yükleme hatası: " . $e->getMessage();
        }
        ?>
    </ul>
</nav> 