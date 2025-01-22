<?php
declare(strict_types=1);

class BlogDataTable {
    private PDO $pdo;
    
    public function __construct() {
        $this->pdo = new PDO('sqlite:blog.sqlite');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create table if not exists
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                excerpt TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Add sample data if empty
        if (!$this->pdo->query("SELECT 1 FROM posts LIMIT 1")->fetch()) {
            $stmt = $this->pdo->prepare("INSERT INTO posts (title, excerpt) VALUES (?, ?)");
            foreach ([
                ['First Post', 'This is our first blog post'],
                ['Getting Started', 'Learn how to use our platform'],
                ['Tips & Tricks', 'Helpful tips for better blogging']
            ] as [$title, $excerpt]) {
                $stmt->execute([$title, $excerpt]);
            }
        }
    }

    public function handleRequest(): void {
        header('Content-Type: application/json');
        
        try {
            match ($_GET['action'] ?? '') {
                'list' => $this->listPosts(),
                'get' => $this->getPost(),
                'update' => $this->updatePost(),
                'delete' => $this->deletePost(),
                default => throw new Exception('Invalid action')
            };
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    private function listPosts(): void {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';
        $sort = in_array($_GET['sort'] ?? '', ['id', 'title', 'created_at']) ? $_GET['sort'] : 'id';
        $dir = strtoupper($_GET['dir'] ?? '') === 'DESC' ? 'DESC' : 'ASC';
        
        $where = '';
        $params = [];
        if ($search !== '') {
            $where = "WHERE title LIKE ? OR excerpt LIKE ?";
            $params = ["%$search%", "%$search%"];
        }
        
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM posts $where");
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        $stmt = $this->pdo->prepare("
            SELECT id, title, excerpt, strftime('%Y-%m-%d %H:%M', created_at) as created_at 
            FROM posts $where 
            ORDER BY $sort $dir 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([...$params, $limit, $offset]);
        
        echo json_encode([
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]);
    }

    private function getPost(): void {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: throw new Exception('Invalid ID');
        $stmt = $this->pdo->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->execute([$id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$post) {
            throw new Exception('Post not found');
        }
        
        echo json_encode(['success' => true, 'post' => $post]);
    }

    private function updatePost(): void {
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON data: ' . json_last_error_msg());
        }
        
        ['id' => $id, 'title' => $title, 'excerpt' => $excerpt] = $data;
        if (!$id || !$title || !$excerpt) {
            throw new Exception('Missing required fields');
        }
        
        $stmt = $this->pdo->prepare("UPDATE posts SET title = ?, excerpt = ? WHERE id = ?");
        if (!$stmt->execute([$title, $excerpt, $id])) {
            throw new Exception('Failed to update post');
        }
        
        echo json_encode(['success' => true]);
    }

    private function deletePost(): void {
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON data: ' . json_last_error_msg());
        }
        
        $id = $data['id'] ?? throw new Exception('Post ID is required');
        
        $stmt = $this->pdo->prepare("DELETE FROM posts WHERE id = ?");
        if (!$stmt->execute([$id])) {
            throw new Exception('Failed to delete post');
        }
        
        echo json_encode(['success' => true]);
    }
}

// Handle API requests
if (isset($_GET['action'])) {
    (new BlogDataTable())->handleRequest();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Posts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .cursor-pointer { cursor: pointer; }
        .table th { user-select: none; }
        .sort-icon { display: inline-block; width: 1em; }
        .table-hover tbody tr:hover { background-color: rgba(0,0,0,.075); }
        .modal-backdrop { background-color: rgba(0,0,0,0.5); }
        .pagination { margin-bottom: 0; }
        .form-control:focus { box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }
        
        /* Add these new styles */
        body.modal-open {
            overflow: hidden;
            padding-right: 17px; /* Compensate for scrollbar */
        }
        
        .modal {
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container mt-4" x-data="{
        posts: [],
        total: 0,
        pages: 0,
        currentPage: 1,
        sortColumn: 'id',
        sortDirection: 'ASC',
        search: '',
        showModal: false,
        editMode: false,
        currentPost: null,
        loading: false,
        error: null,

        async fetchData() {
            this.loading = true;
            this.error = null;
            
            const params = new URLSearchParams({
                action: 'list',
                page: this.currentPage,
                sort: this.sortColumn,
                dir: this.sortDirection,
                search: this.search
            });
            
            try {
                const response = await fetch(`?${params}`);
                if (!response.ok) throw new Error('Network response was not ok');
                const result = await response.json();
                this.posts = result.data;
                this.total = result.total;
                this.pages = result.pages;
            } catch (error) {
                console.error('Error:', error);
                this.error = 'Failed to fetch posts';
            } finally {
                this.loading = false;
            }
        },

        // Add loading indicator to the table, goes here

        async viewPost(id) {
            try {
                const response = await fetch(`?action=get&id=${id}`);
                if (!response.ok) throw new Error('Network response was not ok');
                const result = await response.json();
                if (result.success) {
                    this.currentPost = result.post;
                    this.editMode = false;
                    this.showModal = true;
                    document.body.classList.add('modal-open');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to load post');
            }
        },
        
        async savePost() {
            try {
                const response = await fetch('?action=update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.currentPost)
                });
                
                if (!response.ok) throw new Error('Network response was not ok');
                const result = await response.json();
                
                if (result.success) {
                    this.showModal = false;
                    this.fetchData();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to save changes');
            }
        },

        closeModal() {
            this.showModal = false;
            this.editMode = false;
            this.currentPost = null;
            document.body.classList.remove('modal-open');
        },

        async deletePost() {
            if (!confirm('Are you sure you want to delete this post?')) return;
            
            try {
                const response = await fetch('?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: this.currentPost.id })
                });
                
                if (!response.ok) throw new Error('Network response was not ok');
                const result = await response.json();
                
                if (result.success) {
                    this.showModal = false;
                    this.fetchData();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to delete post');
            }
        }
    }" x-init="fetchData()">
        <div class="card">
            <div class="card-header">
                <h2 class="h4 mb-0">Blog Posts</h2>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input type="text" 
                           class="form-control" 
                           placeholder="Search posts..." 
                           x-model="search" 
                           @input.debounce.300ms="fetchData">
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th @click="sortColumn = 'id'; sortDirection = sortDirection === 'ASC' ? 'DESC' : 'ASC'; fetchData()" 
                                    class="cursor-pointer">
                                    ID 
                                    <template x-if="sortColumn === 'id'">
                                        <span x-text="sortDirection === 'ASC' ? '↑' : '↓'"></span>
                                    </template>
                                </th>
                                <th @click="sortColumn = 'title'; sortDirection = sortDirection === 'ASC' ? 'DESC' : 'ASC'; fetchData()" 
                                    class="cursor-pointer">
                                    Title
                                    <template x-if="sortColumn === 'title'">
                                        <span x-text="sortDirection === 'ASC' ? '↑' : '↓'"></span>
                                    </template>
                                </th>
                                <th>Excerpt</th>
                                <th @click="sortColumn = 'created_at'; sortDirection = sortDirection === 'ASC' ? 'DESC' : 'ASC'; fetchData()" 
                                    class="cursor-pointer">
                                    Created At
                                    <template x-if="sortColumn === 'created_at'">
                                        <span x-text="sortDirection === 'ASC' ? '↑' : '↓'"></span>
                                    </template>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="post in posts" :key="post.id">
                                <tr>
                                    <td x-text="post.id"></td>
                                    <td>
                                        <a href="#" 
                                           @click.prevent="viewPost(post.id)" 
                                           x-text="post.title"
                                           class="text-primary text-decoration-none">
                                        </a>
                                    </td>
                                    <td x-text="post.excerpt"></td>
                                    <td x-text="post.created_at"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        Showing <span x-text="((currentPage - 1) * 10) + 1"></span> 
                        to <span x-text="Math.min(currentPage * 10, total)"></span> 
                        of <span x-text="total"></span> posts
                    </div>
                    <nav x-show="pages > 1">
                        <ul class="pagination mb-0">
                            <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                                <button class="page-link" 
                                        @click="if(currentPage > 1) { currentPage--; fetchData(); }">
                                    Previous
                                </button>
                            </li>
                            <template x-for="page in pages" :key="page">
                                <li class="page-item" :class="{ 'active': currentPage === page }">
                                    <button class="page-link" 
                                            @click="currentPage = page; fetchData()" 
                                            x-text="page">
                                    </button>
                                </li>
                            </template>
                            <li class="page-item" :class="{ 'disabled': currentPage === pages }">
                                <button class="page-link" 
                                        @click="if(currentPage < pages) { currentPage++; fetchData(); }">
                                    Next
                                </button>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div x-show="showModal" 
            x-cloak 
            class="modal" 
            :class="{ 'show': showModal }"
            tabindex="-1" 
            :style="showModal ? 'display: block; background-color: rgba(0,0,0,0.5);' : ''"
            @click.self="showModal = false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <template x-if="!editMode">
                            <h5 class="modal-title" x-text="currentPost?.title"></h5>
                        </template>
                        <template x-if="editMode">
                            <input type="text" 
                                   class="form-control" 
                                   x-model="currentPost.title">
                        </template>
                        <button type="button" 
                            class="btn-close" 
                            @click="closeModal()">
                        </button>
                    </div>
                    <div class="modal-body">
                        <template x-if="!editMode">
                            <p x-text="currentPost?.excerpt"></p>
                        </template>
                        <template x-if="editMode">
                            <textarea class="form-control" 
                                    rows="5" 
                                    x-model="currentPost.excerpt"></textarea>
                        </template>
                    </div>
<!-- Continuing from the modal footer -->
<div class="modal-footer">
    <template x-if="!editMode">
        <div>
            <button type="button" 
                    class="btn btn-secondary" 
                    @click="closeModal()">
                Close
            </button>
            <button type="button" 
                    class="btn btn-primary" 
                    @click="editMode = true">
                Edit
            </button>
            <button type="button" 
                    class="btn btn-danger" 
                    @click="deletePost()">
                Delete
            </button>
        </div>
    </template>
    <template x-if="editMode">
        <div>
            <button type="button" 
                    class="btn btn-secondary" 
                    @click="editMode = false">
                Cancel
            </button>
            <button type="button" 
                    class="btn btn-success" 
                    @click="savePost()">
                Save Changes
            </button>
        </div>
    </template>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>