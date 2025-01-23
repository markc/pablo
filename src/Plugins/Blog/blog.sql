CREATE TABLE IF NOT EXISTS posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    excerpt TEXT NOT NULL,
    content BLOB NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create trigger to automatically update the updated_at timestamp
CREATE TRIGGER IF NOT EXISTS update_posts_timestamp 
    AFTER UPDATE ON posts
BEGIN
    UPDATE posts SET updated_at = DATETIME('now') 
    WHERE id = old.id;
END;

-- Insert sample data if table is empty
INSERT INTO posts (title, excerpt, content) VALUES 
    ('First Post', 
     'This is our first blog post',
     'Welcome to our blog! This is the very first post on our platform. We''re excited to share our journey with you and look forward to creating valuable content for our readers.'),
    
    ('Getting Started', 
     'Learn how to use our platform',
     'This comprehensive guide will walk you through the basic features of our platform. You''ll learn how to create posts, manage your content, and engage with your audience effectively.'),
    
    ('Tips & Tricks', 
     'Helpful tips for better blogging',
     'Discover proven strategies to improve your blogging skills. From writing compelling headlines to structuring your content for better readability, these tips will help you create more engaging posts.'),
    
    ('Best Practices', 
     'Follow these guidelines',
     'Learn about the best practices for content creation, SEO optimization, and community engagement. Following these guidelines will help you build a successful and sustainable blog.'),
    
    ('Advanced Topics', 
     'Deep dive into advanced features',
     'Ready to take your blogging to the next level? This post covers advanced topics including content strategy, analytics interpretation, and advanced platform customization options.');
