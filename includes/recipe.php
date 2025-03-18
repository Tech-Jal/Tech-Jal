<?php
require_once __DIR__ . '/../config/database.php';

class Recipe {
    private $conn;
    private $table_name = "recipes";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($data) {
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO recipes (user_id, title, description, ingredients, instructions, 
                                    prep_time, cook_time, servings, difficulty, image_url) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $data['user_id'],
                $data['title'],
                $data['description'],
                $data['ingredients'],
                $data['instructions'],
                $data['prep_time'],
                $data['cook_time'],
                $data['servings'],
                $data['difficulty'],
                $data['image_url']
            ]);

            $recipe_id = $this->conn->lastInsertId();

            // Add categories
            if (!empty($data['categories'])) {
                $this->updateCategories($recipe_id, $data['categories']);
            }

            return ["success" => true, "recipe_id" => $recipe_id];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Failed to create recipe: " . $e->getMessage()];
        }
    }

    public function update($recipe_id, $data) {
        try {
            $allowed_fields = [
                'title', 'description', 'ingredients', 'instructions', 
                'prep_time', 'cook_time', 'servings', 'difficulty', 'image_url', 'status'
            ];
            $updates = [];
            $values = [];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowed_fields)) {
                    $updates[] = "$field = ?";
                    $values[] = $value;
                }
            }

            if (empty($updates)) {
                return ["success" => false, "message" => "No valid fields to update"];
            }

            $values[] = $recipe_id;
            $sql = "UPDATE recipes SET " . implode(", ", $updates) . " WHERE recipe_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($values);

            // Update categories if provided
            if (isset($data['categories'])) {
                $this->updateCategories($recipe_id, $data['categories']);
            }

            return ["success" => true, "message" => "Recipe updated successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Update failed: " . $e->getMessage()];
        }
    }

    private function updateCategories($recipe_id, $categories) {
        // Remove existing categories
        $stmt = $this->conn->prepare("DELETE FROM recipe_categories WHERE recipe_id = ?");
        $stmt->execute([$recipe_id]);

        // Add new categories
        $stmt = $this->conn->prepare(
            "INSERT INTO recipe_categories (recipe_id, category_id) VALUES (?, ?)"
        );
        foreach ($categories as $category_id) {
            $stmt->execute([$recipe_id, $category_id]);
        }
    }

    public function getById($recipe_id) {
        try {
            $stmt = $this->conn->prepare(
                "SELECT r.*, u.username, 
                        GROUP_CONCAT(DISTINCT c.name) as categories,
                        COUNT(DISTINCT com.comment_id) as comment_count,
                        COALESCE(AVG(rat.rating), 0) as average_rating,
                        COUNT(DISTINCT rat.rating_id) as rating_count
                 FROM recipes r
                 LEFT JOIN users u ON r.user_id = u.user_id
                 LEFT JOIN recipe_categories rc ON r.recipe_id = rc.recipe_id
                 LEFT JOIN categories c ON rc.category_id = c.category_id
                 LEFT JOIN comments com ON r.recipe_id = com.recipe_id
                 LEFT JOIN ratings rat ON r.recipe_id = rat.recipe_id
                 WHERE r.recipe_id = ?
                 GROUP BY r.recipe_id"
            );
            $stmt->execute([$recipe_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }

    public function search($params = []) {
        try {
            $conditions = [];
            $values = [];
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
            $offset = isset($params['offset']) ? (int)$params['offset'] : 0;

            $sql = "SELECT r.*, u.username, 
                           GROUP_CONCAT(DISTINCT c.name) as categories,
                           COUNT(DISTINCT com.comment_id) as comment_count,
                           COALESCE(AVG(rat.rating), 0) as average_rating
                    FROM recipes r
                    LEFT JOIN users u ON r.user_id = u.user_id
                    LEFT JOIN recipe_categories rc ON r.recipe_id = rc.recipe_id
                    LEFT JOIN categories c ON rc.category_id = c.category_id
                    LEFT JOIN comments com ON r.recipe_id = com.recipe_id
                    LEFT JOIN ratings rat ON r.recipe_id = rat.recipe_id";

            if (!empty($params['category'])) {
                $conditions[] = "c.name = ?";
                $values[] = $params['category'];
            }

            if (!empty($params['difficulty'])) {
                $conditions[] = "r.difficulty = ?";
                $values[] = $params['difficulty'];
            }

            if (!empty($params['search'])) {
                $conditions[] = "(r.title LIKE ? OR r.description LIKE ?)";
                $values[] = "%{$params['search']}%";
                $values[] = "%{$params['search']}%";
            }

            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }

            $sql .= " GROUP BY r.recipe_id";

            if (!empty($params['sort'])) {
                switch ($params['sort']) {
                    case 'rating':
                        $sql .= " ORDER BY average_rating DESC";
                        break;
                    case 'newest':
                        $sql .= " ORDER BY r.created_at DESC";
                        break;
                    case 'popular':
                        $sql .= " ORDER BY comment_count DESC";
                        break;
                }
            }

            $sql .= " LIMIT ? OFFSET ?";
            $values[] = $limit;
            $values[] = $offset;

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($values);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function addComment($recipe_id, $user_id, $content) {
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO comments (recipe_id, user_id, content) VALUES (?, ?, ?)"
            );
            $stmt->execute([$recipe_id, $user_id, $content]);
            return ["success" => true, "comment_id" => $this->conn->lastInsertId()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Failed to add comment"];
        }
    }

    public function addRating($recipe_id, $user_id, $rating) {
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO ratings (recipe_id, user_id, rating) 
                 VALUES (?, ?, ?) 
                 ON DUPLICATE KEY UPDATE rating = VALUES(rating)"
            );
            $stmt->execute([$recipe_id, $user_id, $rating]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Failed to add rating"];
        }
    }

    public function saveRecipe($user_id, $recipe_id) {
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO saved_recipes (user_id, recipe_id) VALUES (?, ?)"
            );
            $stmt->execute([$user_id, $recipe_id]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Failed to save recipe"];
        }
    }

    public function unsaveRecipe($user_id, $recipe_id) {
        try {
            $stmt = $this->conn->prepare(
                "DELETE FROM saved_recipes WHERE user_id = ? AND recipe_id = ?"
            );
            $stmt->execute([$user_id, $recipe_id]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Failed to unsave recipe"];
        }
    }

    public function getSavedRecipes($user_id) {
        try {
            $stmt = $this->conn->prepare(
                "SELECT r.*, u.username
                 FROM recipes r
                 JOIN saved_recipes sr ON r.recipe_id = sr.recipe_id
                 JOIN users u ON r.user_id = u.user_id
                 WHERE sr.user_id = ?
                 ORDER BY sr.saved_at DESC"
            );
            $stmt->execute([$user_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
} 