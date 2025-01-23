<?php

declare(strict_types=1);
// src/Plugins/Blog/Plugin.php 20250122 - 20250123
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace Markc\Pablo\Plugins\Blog;

use Markc\Pablo\Core\Plugin as BasePlugin;
use PDO;

class Plugin extends BasePlugin
{
    private const DB_PATH = __DIR__ . '/blog.sqlite';
    private ?PDO $db = null;

    public function __construct($theme)
    {
        parent::__construct($theme);
        error_log('Blog Plugin: Starting initialization');
        error_log('Blog Plugin: Database path = ' . self::DB_PATH);
        $this->initializeDatabase();
        error_log('Blog Plugin: Initialization complete');
    }

    private function initializeDatabase(): void
    {
        try {
            error_log('Blog Plugin: Connecting to SQLite database');

            // Create database directory if it doesn't exist
            $dbDir = dirname(self::DB_PATH);
            if (!is_dir($dbDir)) {
                if (!mkdir($dbDir, 0755, true)) {
                    throw new \RuntimeException("Failed to create database directory: $dbDir");
                }
            }

            // Connect to SQLite database
            $this->db = new PDO('sqlite:' . self::DB_PATH);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            error_log('Blog Plugin: Connected to database successfully');

            // Check if tables already exist
            $checkTable = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='posts'");

            if ($checkTable->fetch()) {
                error_log('Blog Plugin: Database already initialized, skipping schema creation');
                return;
            }

            // Initialize schema
            $schemaPath = __DIR__ . '/blog.sql';
            if (!file_exists($schemaPath)) {
                throw new \RuntimeException('Schema file not found: ' . $schemaPath);
            }

            $schema = file_get_contents($schemaPath);
            if ($schema === false) {
                throw new \RuntimeException('Failed to read schema file');
            }

            error_log('Blog Plugin: Initializing database schema');
            $this->db->exec($schema);
            error_log('Blog Plugin: Schema initialized successfully');
        } catch (\Exception $e) {
            error_log('Blog Plugin: Database initialization error: ' . $e->getMessage());
            error_log('Blog Plugin: Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    public function execute(): mixed
    {
        // Handle API request
        if (isset($_GET['api']) && $_GET['api'] === 'data') {
            return $this->api_data();
        }

        // Return main blog page with datatable
        return $this->renderDataTable();
    }

    private function renderDataTable(): string
    {
        $html = <<<HTML
        <div class="container">
            <div id="blog-list">
                <h1>Blog Posts</h1>
                <div class="mb-3">
                    <input type="text" id="blog-search" class="form-control" placeholder="Search...">
                </div>
                <table id="blog-table" class="table table-striped">
                    <thead>
                        <tr>
                            <th data-sort="id">ID</th>
                            <th data-sort="title">Title</th>
                            <th data-sort="excerpt">Excerpt</th>
                            <th data-sort="created_at">Created</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <nav>
                    <ul class="pagination" id="blog-pagination"></ul>
                </nav>
            </div>
            <div id="blog-content" class="mt-3" style="display: none;"></div>
        </div>
        <script>
        // Initialize blog functionality
        function initializeBlog() {
            // Get CSRF token from meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) {
                console.error('CSRF token not found');
                document.getElementById('blog-content').innerHTML = `
                    <div class="alert alert-danger">Error: CSRF token not found.</div>
                `;
                return;
            }

            // Load post content
            async function loadPost(id) {
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.set('api', 'data');
                    url.searchParams.set('id', id.toString());

                    const response = await fetch(url, {
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }

                    const post = await response.json();
                    const contentDiv = document.getElementById('blog-content');
                    const listDiv = document.getElementById('blog-list');
                    
                    // Hide list and show content
                    listDiv.style.display = 'none';
                    contentDiv.style.display = 'block';
                    
                    contentDiv.innerHTML = `
                        <h2>\${post.title}</h2>
                        <div class="text-muted mb-3">Posted on \${post.created_at}</div>
                        <div class="blog-content">\${post.content}</div>
                    `;
                } catch (error) {
                    console.error('Error loading post:', error);
                    document.getElementById('blog-content').innerHTML = `
                        <div class="alert alert-danger">Error loading post content.</div>
                    `;
                }
            }

            function initializeBlogTable() {
            console.log('Blog table initialization starting...');
            let currentPage = 1;
            let currentSort = 'id';
            let currentDirection = 'ASC';
            const searchInput = document.getElementById('blog-search');
            const tableBody = document.querySelector('#blog-table tbody');
            const pagination = document.getElementById('blog-pagination');

            // Load data function
            async function loadData() {
                const searchTerm = searchInput.value;
                const url = new URL(window.location.href);
                url.searchParams.set('api', 'data');
                url.searchParams.set('page', currentPage.toString());
                url.searchParams.set('search', searchTerm);
                url.searchParams.set('sort', currentSort);
                url.searchParams.set('direction', currentDirection);

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    
                    const data = await response.json();
                    
                    // Clear and populate table
                    tableBody.innerHTML = '';
                    data.data.forEach(post => {
                        const row = document.createElement('tr');
                        row.style.cursor = 'pointer';
                        row.innerHTML = `
                            <td>\${post.id}</td>
                            <td class="post-title" data-id="\${post.id}">\${post.title}</td>
                            <td>\${post.excerpt}</td>
                            <td>\${post.created_at}</td>
                        `;
                        row.onclick = () => loadPost(post.id);
                        tableBody.appendChild(row);
                    });

                    // Update pagination
                    updatePagination(data.pages);
                } catch (error) {
                    console.error('Error loading data:', error.message);
                    if (error.stack) console.error('Stack trace:', error.stack);
                }
            }

            // Update pagination controls
            function updatePagination(totalPages) {
                pagination.innerHTML = '';
                
                // Previous button
                const prevLi = document.createElement('li');
                prevLi.className = `page-item \${currentPage === 1 ? 'disabled' : ''}`;
                prevLi.innerHTML = '<a class="page-link" href="#">Previous</a>';
                prevLi.onclick = (e) => {
                    e.preventDefault();
                    if (currentPage > 1) {
                        currentPage--;
                        loadData();
                    }
                };
                pagination.appendChild(prevLi);

                // Page numbers
                for (let i = 1; i <= totalPages; i++) {
                    const li = document.createElement('li');
                    li.className = `page-item \${currentPage === i ? 'active' : ''}`;
                    li.innerHTML = `<a class="page-link" href="#">\${i}</a>`;
                    li.onclick = (e) => {
                        e.preventDefault();
                        currentPage = i;
                        loadData();
                    };
                    pagination.appendChild(li);
                }

                // Next button
                const nextLi = document.createElement('li');
                nextLi.className = `page-item \${currentPage === totalPages ? 'disabled' : ''}`;
                nextLi.innerHTML = '<a class="page-link" href="#">Next</a>';
                nextLi.onclick = (e) => {
                    e.preventDefault();
                    if (currentPage < totalPages) {
                        currentPage++;
                        loadData();
                    }
                };
                pagination.appendChild(nextLi);
            }

            // Sort handling
            document.querySelectorAll('#blog-table th[data-sort]').forEach(th => {
                th.style.cursor = 'pointer';
                th.onclick = () => {
                    const sort = th.dataset.sort;
                    if (currentSort === sort) {
                        currentDirection = currentDirection === 'ASC' ? 'DESC' : 'ASC';
                    } else {
                        currentSort = sort;
                        currentDirection = 'ASC';
                    }
                    loadData();
                };
            });

            // Search handling
            let searchTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentPage = 1;
                    loadData();
                }, 300);
            });

            // Initial load
            loadData();
            console.log('Blog table initialization complete');
        }

            // Initialize table
            initializeBlogTable();
        }

        // Start initialization
        initializeBlog();
        </script>
        HTML;

        return $html;
    }

    private function api_data(): void
    {
        try {
            // Ensure no output has been sent yet
            if (headers_sent($file, $line)) {
                error_log("Headers already sent in $file:$line");
                throw new \RuntimeException("Headers already sent");
            }

            // Clear any output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Check if requesting a single post
            if (isset($_GET['id'])) {
                $id = (int) $_GET['id'];
                $stmt = $this->db->prepare('SELECT * FROM posts WHERE id = :id');
                $stmt->execute([':id' => $id]);
                $post = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$post) {
                    throw new \RuntimeException('Post not found');
                }

                header('Content-Type: application/json');
                echo json_encode($post);
                exit;
            }

            // Handle list request
            $page = (int) ($_GET['page'] ?? 1);
            $perPage = 10;
            $search = $_GET['search'] ?? '';
            $sortColumn = $_GET['sort'] ?? 'id';
            $sortDirection = strtoupper($_GET['direction'] ?? 'ASC');

            // Build query
            $query = 'SELECT id, title, excerpt, created_at FROM posts';
            $params = [];

            if ($search !== '') {
                $query .= ' WHERE title LIKE :search';
                $params[':search'] = "%$search%";
            }

            // Get total count for pagination
            $countQuery = str_replace('SELECT id, title, excerpt, created_at', 'SELECT COUNT(*)', $query);
            $stmt = $this->db->prepare($countQuery);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();

            // Add sorting and pagination
            $query .= " ORDER BY $sortColumn $sortDirection";
            $query .= ' LIMIT :limit OFFSET :offset';
            $params[':limit'] = $perPage;
            $params[':offset'] = ($page - 1) * $perPage;

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $pages = ceil($total / $perPage);

            $result = [
                'data' => $data,
                'total' => $total,
                'pages' => $pages
            ];

            error_log('Blog Plugin: Preparing JSON response');

            // Set headers
            header('Content-Type: application/json');
            header('X-Content-Type-Options: nosniff');

            // Encode response
            $json = json_encode($result, JSON_PRETTY_PRINT);
            if ($json === false) {
                throw new \RuntimeException('JSON encoding failed: ' . json_last_error_msg());
            }

            error_log('Blog Plugin: JSON response length: ' . strlen($json));
            echo $json;
            exit; // Ensure no additional output

        } catch (\Exception $e) {
            error_log('Blog Plugin: Error in api_data: ' . $e->getMessage());
            error_log('Blog Plugin: Stack trace: ' . $e->getTraceAsString());

            if (!headers_sent()) {
                header('HTTP/1.1 500 Internal Server Error');
                header('Content-Type: application/json');
            }

            echo json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
}
