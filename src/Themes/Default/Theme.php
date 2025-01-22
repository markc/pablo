<?php

declare(strict_types=1);

namespace Markc\Pablo\Themes\Default;

use Markc\Pablo\Core\{Theme as BaseTheme, NavRenderer, Init, Config};

class Theme extends BaseTheme
{
    private Init $init;
    
    public function __construct(Config $config, Init $init) 
    {
        parent::__construct($config);
        $this->init = $init;
    }

    public function lhsNav(): string
    {
        $navRenderer = new NavRenderer($this->config);
        return $navRenderer->renderPluginNav($this->init->pluginNav);
    }

    public function rhsNav(): string
    {
        $navRenderer = new NavRenderer($this->config);
        return $navRenderer->renderPluginNav($this->init->nav2);
    }

    public function render(): string
    {
        $appName = $this->config->cfg['app_name'];
        $mainContent = $this->config->out['main'];
        $lhsNav = $this->lhsNav();
        $rhsNav = $this->rhsNav();

        // Check if this is an AJAX request
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
                
        if ($isAjax) {
            // Return only the main content for AJAX requests
            return $this->config->out['main'];
        }

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$appName}</title>
            <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

            <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
            <style>
                .fw {
                    width: 1em;
                    display: inline-block;
                    text-align: center;
                    margin-right: 0.5rem;
                }
                [aria-expanded="true"] .chevron-icon {
                    transform: rotate(90deg);
                    transition: transform 0.2s ease-in-out;
                }
                [aria-expanded="false"] .chevron-icon {
                    transform: rotate(0deg);
                    transition: transform 0.2s ease-in-out;
                }

                body {
                    min-height: 100vh;
                    padding-top: 56px;
                }
                .navbar-height {
                    height: 56px;
                }
            .wrapper {
                    display: flex;
                    min-height: calc(100vh - 56px);
                }
                .sidebar {
                    width: 300px;
                    background-color: #343a40;
                    color: #fff;
                    padding-top: 20px;
                    position: fixed;
                    height: calc(100vh - 56px);
                    overflow-y: auto;
                    transition: margin-left 0.3s ease-in-out, margin-right 0.3s ease-in-out;
                    z-index: 1000;
                }
                .sidebar.left {
                    left: 0;
                }
                .sidebar.right {
                    right: 0;
                }
                .sidebar.collapsed {
                    margin-left: -300px;
                }
                .sidebar.right.collapsed {
                    margin-right: -300px;
                }
                .sidebar .nav-link {
                    color: #fff;
                    padding: 10px 20px;
                    display: flex;
                    align-items: center;
                }
                .sidebar .nav-link:hover {
                    background-color: #495057;
                }
                .main-content {
                    margin-left: 300px;
                    margin-right: 300px;
                    flex-grow: 1;
                    padding: 20px;
                    width: calc(100% - 600px);
                    transition: margin-left 0.3s ease-in-out, margin-right 0.3s ease-in-out, width 0.3s ease-in-out;
                }
                .main-content.expanded-left {
                    margin-left: 0;
                    width: calc(100% - 300px);
                }
                .main-content.expanded-right {
                    margin-right: 0;
                    width: calc(100% - 300px);
                }
                .main-content.expanded-both {
                    margin-left: 0;
                    margin-right: 0;
                    width: 100%;
                }
                .submenu {
                    padding-left: 20px;
                    background-color: #2c3136;
                }
                .submenu .nav-link {
                    padding: 8px 20px;
                    font-size: 0.9rem;
                }
                .sidebar .nav-link svg {
                    width: 1em;
                    height: 1em;
                    margin-right: 0.5em;
                    fill: currentColor;
                }
                .sidebar-toggle {
                    padding: 0.25rem 0.75rem;
                }
                #leftSidebarToggle {
                    margin-right: 1rem;
                }
                #rightSidebarToggle {
                    margin-left: 1rem;
                }
                /* Mobile-Friendly CSS */
                @media (max-width: 768px) {
                    .navbar-brand {
                        margin-left: auto;
                        margin-right: auto;
                    }
                    .sidebar {
                        position: fixed;
                        top: 56px;
                        bottom: 0;
                        background-color: #343a40;
                        z-index: 1030;
                        width: 80%;
                        max-width: 300px;
                    }

                    .sidebar.left {
                        left: -100%;
                        transition: left 0.3s ease-in-out;
                        margin-left: 0;
                    }

                    .sidebar.right {
                        right: -100%;
                        transition: right 0.3s ease-in-out;
                        margin-right: 0;
                    }

                    .sidebar.left.show {
                        left: 0;
                    }

                    .sidebar.right.show {
                        right: 0;
                    }

                    /* Main content adjustments */
                    .main-content {
                        margin-left: 0 !important;
                        margin-right: 0 !important;
                        width: 100% !important;
                        padding: 10px;
                        transition: none;
                    }
                    #leftSidebarToggle,
                    #rightSidebarToggle {
                        position: absolute;
                        top: 50%;
                        transform: translateY(-50%);
                        z-index: 1031;
                        padding: 0.25rem 0.75rem;
                        margin: 0;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }

                    #leftSidebarToggle {
                        left: 0.5rem;
                    }

                    #rightSidebarToggle {
                        right: 0.5rem;
                    }

                    /* Ensure sidebar content is visible */
                    .sidebar .nav-link {
                        color: #fff !important;
                    }

                    .submenu {
                        background-color: #2c3136 !important;
                    }
                }

                /* Non-mobile styles */
                @media (min-width: 769px) {
                    /* Show sidebars by default on larger screens */
                    .sidebar.left:not(.collapsed), .sidebar.right:not(.collapsed) {
                        margin-left: 0;
                        margin-right: 0;
                    }
                }
            </style>
        </head>
        <body>
            <nav class="navbar navbar-dark bg-dark fixed-top navbar-height">
                <div class="container-fluid">

                    <button class="btn btn-dark" id="leftSidebarToggle" type="button">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <a class="navbar-brand" href="/">
                        {$appName}
                    </a>
                    <button class="btn btn-dark" id="rightSidebarToggle" type="button">
                        <span class="navbar-toggler-icon"></span>
                    </button>
            </div>
            </nav>

            <div class="sidebar left" id="leftSidebar">
                {$lhsNav}
            </div>

            <div class="sidebar right" id="rightSidebar">
                {$rhsNav}
            </div>

            <div class="main-content" id="main">
                <div class="container-fluid">
                    <div class="row">
                        <main class="col-md-12">
                            <div class="content-section" id="content-section">
                                {$mainContent}
                            </div>
                        </main>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Sidebar elements
                    const leftSidebar = document.getElementById('leftSidebar');
                    const rightSidebar = document.getElementById('rightSidebar');
                    const mainContent = document.getElementById('main');
                    const contentSection = document.getElementById('content-section');
                    const isMobile = window.innerWidth <= 768;

                    // Handle left sidebar toggle
                    document.getElementById('leftSidebarToggle').addEventListener('click', function() {
                        if (isMobile) {
                            leftSidebar.classList.toggle('show');
                            rightSidebar.classList.remove('show');
                        } else {
                            leftSidebar.classList.toggle('collapsed');
                            if (rightSidebar.classList.contains('collapsed')) {
                                mainContent.classList.toggle('expanded-both');
                                mainContent.classList.toggle('expanded-right');
                            } else {
                                mainContent.classList.toggle('expanded-left');
                            }
                        }
                    });

                    // Handle right sidebar toggle
                    document.getElementById('rightSidebarToggle').addEventListener('click', function() {
                        if (isMobile) {
                            rightSidebar.classList.toggle('show');
                            leftSidebar.classList.remove('show');
                        } else {
                            rightSidebar.classList.toggle('collapsed');
                            if (leftSidebar.classList.contains('collapsed')) {
                                mainContent.classList.toggle('expanded-both');
                                mainContent.classList.toggle('expanded-left');
                            } else {
                                mainContent.classList.toggle('expanded-right');
                            }
                        }
                    });

                    // Close sidebars when clicking outside on mobile
                    document.addEventListener('click', function(event) {
                        if (isMobile) {
                            const isClickInsideLeftSidebar = leftSidebar.contains(event.target);
                            const isClickInsideRightSidebar = rightSidebar.contains(event.target);
                            const isClickOnLeftToggle = event.target.closest('#leftSidebarToggle');
                            const isClickOnRightToggle = event.target.closest('#rightSidebarToggle');

                            if (!isClickInsideLeftSidebar && !isClickOnLeftToggle && leftSidebar.classList.contains('show')) {
                                leftSidebar.classList.remove('show');
                            }
                            if (!isClickInsideRightSidebar && !isClickOnRightToggle && rightSidebar.classList.contains('show')) {
                                rightSidebar.classList.remove('show');
                            }
                        }
                    });

                    // Handle window resize
                    window.addEventListener('resize', function() {
                        const newIsMobile = window.innerWidth <= 768;
                        if (newIsMobile !== isMobile) {
                            location.reload();
                        }
                    });
                    
                    // AJAX Functions
                    function showLoading() {
                        contentSection.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                    }

                    function handleError(error) {
                        contentSection.innerHTML = `
                            <div class="alert alert-danger" role="alert">
                                Error loading content: \${error.message}
                            </div>
                        `;
                    }

                    function updateURL(url) {
                        history.pushState({}, '', url);
                    }

                    async function loadContent(url) {
                        try {
                            showLoading();
                            const response = await fetch(url, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: \${response.status}`);
                            }
                            
                            const data = await response.text();
                            
                            // Clear existing content first
                            contentSection.innerHTML = '';
                            
                            // Small delay to ensure proper DOM cleanup
                            setTimeout(() => {
                                contentSection.innerHTML = data;
                                updateURL(url);
                                
                                if (window.innerWidth <= 768) {
                                    leftSidebar.classList.remove('show');
                                }
                            }, 50);
                            
                        } catch (error) {
                            handleError(error);
                        }
                    }
                    
                    // Debug function to log click events
                    //function logClickDetails(event, element) {
                    //    console.log('Click event:', {
                    //        target: event.target,
                    //        currentTarget: event.currentTarget,
                    //        element: element,
                    //        href: element?.href,
                    //        classList: element?.classList
                    //    });
                    //}
                    
                    // Intercept left sidebar link clicks
                    leftSidebar.addEventListener('click', function(event) {
                        const link = event.target.closest('a');
                        
                        // If no link was clicked, exit early
                        if (!link) return;
                        
                        // Debug logging
                        //logClickDetails(event, link);
                        
                        // If the link is a collapse toggle, let it handle naturally
                        if (link.getAttribute('data-bs-toggle') === 'collapse') {
                            return;
                        }
                        
                        // At this point, we know it's a navigation link
                        event.preventDefault();
                        event.stopPropagation();
                        
                        // Check if we have a valid URL
                        if (link.href) {
                            loadContent(link.href);
                            
                            // Close mobile sidebar if needed
                            if (window.innerWidth <= 768) {
                                leftSidebar.classList.remove('show');
                            }
                            
                            // If this is inside a collapse menu, keep it open
                            const parentCollapse = link.closest('.collapse');
                            if (parentCollapse) {
                                parentCollapse.classList.add('show');
                            }
                        }
                    });

                    // Handle browser back/forward buttons
                    window.addEventListener('popstate', function(event) {
                        loadContent(window.location.href);
                    });
                });
            </script>
        </body>
        </html>
        HTML;
    }
}
